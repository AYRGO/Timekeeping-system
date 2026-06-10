<?php

require_once __DIR__ . '/EmployeeHolidayProfiles.php';

class ScheduleCacheRebuilder
{
    private PDO $pdo;
    private EmployeeHolidayProfiles $holidayResolver;
    private array $columnsByTable = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->holidayResolver = new EmployeeHolidayProfiles($pdo);
    }

    public function rebuildForEmployees(
        array $employeeIds,
        string $startDate,
        string $endDate,
        bool $preserveExistingSchedules = false
    ): array
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        $stats = [
            'employees' => 0,
            'days' => 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if (empty($employeeIds)) {
            return $stats;
        }

        foreach ($employeeIds as $employeeId) {
            $stats['days'] += $this->rebuildEmployee(
                $employeeId,
                $startDate,
                $endDate,
                $preserveExistingSchedules
            );
            $stats['employees']++;
        }

        return $stats;
    }

    private function rebuildEmployee(
        int $employeeId,
        string $startDate,
        string $endDate,
        bool $preserveExistingSchedules
    ): int
    {
        $existingByDate = [];
        if ($preserveExistingSchedules) {
            $existingStmt = $this->pdo->prepare("
                SELECT
                    c.schedule_date,
                    CASE WHEN ws.id IS NULL THEN NULL ELSE c.work_schedule_id END AS work_schedule_id,
                    c.is_rest_day,
                    c.schedule_name,
                    c.time_in,
                    c.time_out,
                    c.source,
                    " . ($this->columnExists('employee_daily_schedule_cache', 'source_id') ? 'c.source_id' : 'NULL AS source_id') . "
                FROM employee_daily_schedule_cache c
                LEFT JOIN work_schedules ws ON ws.id = c.work_schedule_id
                WHERE c.employee_id = ?
                  AND c.schedule_date BETWEEN ? AND ?
            ");
            $existingStmt->execute([$employeeId, $startDate, $endDate]);
            foreach ($existingStmt->fetchAll(PDO::FETCH_ASSOC) as $existing) {
                $existingByDate[$existing['schedule_date']] = $existing;
            }
        }

        $deleteStmt = $this->pdo->prepare("
            DELETE FROM employee_daily_schedule_cache
            WHERE employee_id = ?
              AND schedule_date BETWEEN ? AND ?
        ");
        $deleteStmt->execute([$employeeId, $startDate, $endDate]);

        $approvedRequestStmt = $this->pdo->prepare("
            SELECT
                CASE WHEN ws.id IS NULL THEN NULL ELSE psr.work_schedule_id END AS work_schedule_id,
                psr.is_rest_day,
                ws.name AS schedule_name,
                ws.time_in,
                ws.time_out
            FROM post_schedule_change_requests psr
            LEFT JOIN work_schedules ws ON psr.work_schedule_id = ws.id
            WHERE psr.employee_id = ?
              AND LOWER(psr.status) = 'approved'
              AND ? BETWEEN psr.start_date AND psr.end_date
            ORDER BY psr.created_at DESC
            LIMIT 1
        ");

        $defaultScheduleStmt = $this->pdo->prepare("
            SELECT
                CASE WHEN ws.id IS NULL THEN NULL ELSE eds.work_schedule_id END AS work_schedule_id,
                eds.is_rest_day,
                ws.name AS schedule_name,
                ws.time_in,
                ws.time_out
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ?
              AND eds.day_of_week = ?
              AND eds.effective_from <= ?
              AND (eds.effective_until IS NULL OR eds.effective_until >= ?)
            ORDER BY eds.effective_from DESC, eds.id DESC
            LIMIT 1
        ");

        $inserted = 0;
        $current = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $dayOfWeek = (int)$current->format('w');

            $holiday = $this->holidayResolver->resolveHolidayForEmployeeDate($employeeId, $date);
            $isHoliday = (int)($holiday['is_holiday'] ?? 0);
            $holidayName = $holiday['holiday_name'] ?? null;
            $holidayType = $holiday['holiday_type'] ?? null;
            $holidaySource = $holiday['source'] ?? null;

            if (isset($existingByDate[$date])) {
                $existing = $existingByDate[$date];
                $scheduleSource = $this->scheduleSourceOnly($existing['source'] ?? null);
                $this->insertCacheRow([
                    'employee_id' => $employeeId,
                    'schedule_date' => $date,
                    'work_schedule_id' => $existing['work_schedule_id'],
                    'is_rest_day' => $existing['is_rest_day'],
                    'is_holiday' => $isHoliday,
                    'schedule_name' => $existing['schedule_name'],
                    'time_in' => $existing['time_in'],
                    'time_out' => $existing['time_out'],
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType,
                    'source' => $holidaySource ? $holidaySource . '|' . $scheduleSource : $scheduleSource,
                    'source_id' => $existing['source_id'],
                ]);
                $inserted++;
                $current = $current->modify('+1 day');
                continue;
            }

            $approvedRequestStmt->execute([$employeeId, $date]);
            $approvedRequest = $approvedRequestStmt->fetch(PDO::FETCH_ASSOC);

            if ($approvedRequest) {
                $this->insertCacheRow([
                    'employee_id' => $employeeId,
                    'schedule_date' => $date,
                    'work_schedule_id' => $approvedRequest['work_schedule_id'],
                    'is_rest_day' => $approvedRequest['is_rest_day'] ?? 0,
                    'is_holiday' => $isHoliday,
                    'schedule_name' => $approvedRequest['schedule_name'],
                    'time_in' => $approvedRequest['time_in'],
                    'time_out' => $approvedRequest['time_out'],
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType,
                    'source' => $holidaySource ? $holidaySource . '|approved_request' : 'approved_request',
                ]);
                $inserted++;
            } else {
                $defaultScheduleStmt->execute([$employeeId, $dayOfWeek, $date, $date]);
                $defaultSchedule = $defaultScheduleStmt->fetch(PDO::FETCH_ASSOC);
                if (!$defaultSchedule) {
                    $current = $current->modify('+1 day');
                    continue;
                }

                $this->insertCacheRow([
                    'employee_id' => $employeeId,
                    'schedule_date' => $date,
                    'work_schedule_id' => $defaultSchedule['work_schedule_id'],
                    'is_rest_day' => $defaultSchedule['is_rest_day'],
                    'is_holiday' => $isHoliday,
                    'schedule_name' => $defaultSchedule['schedule_name'],
                    'time_in' => $defaultSchedule['time_in'],
                    'time_out' => $defaultSchedule['time_out'],
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType,
                    'source' => $holidaySource ? $holidaySource . '|weekly_default' : 'weekly_default',
                ]);
                $inserted++;
            }

            $current = $current->modify('+1 day');
        }

        return $inserted;
    }

    private function scheduleSourceOnly(?string $source): string
    {
        $source = trim((string)$source);
        if ($source === '') {
            return 'none';
        }

        if (str_contains($source, '|')) {
            $parts = explode('|', $source);
            return (string)end($parts);
        }

        return $source;
    }

    private function insertCacheRow(array $row): void
    {
        $columns = [
            'employee_id',
            'schedule_date',
            'work_schedule_id',
            'is_rest_day',
            'is_holiday',
            'schedule_name',
            'time_in',
            'time_out',
            'holiday_name',
            'source',
            'created_at',
        ];

        $values = [
            $row['employee_id'],
            $row['schedule_date'],
            $row['work_schedule_id'],
            $row['is_rest_day'],
            $row['is_holiday'],
            $row['schedule_name'],
            $row['time_in'],
            $row['time_out'],
            $row['holiday_name'],
            $row['source'],
            date('Y-m-d H:i:s'),
        ];

        if ($this->columnExists('employee_daily_schedule_cache', 'holiday_type')) {
            $insertAt = array_search('source', $columns, true);
            array_splice($columns, $insertAt, 0, ['holiday_type']);
            array_splice($values, $insertAt, 0, [$row['holiday_type'] ?? null]);
        }

        if ($this->columnExists('employee_daily_schedule_cache', 'source_id')) {
            $insertAt = array_search('created_at', $columns, true);
            array_splice($columns, $insertAt, 0, ['source_id']);
            array_splice($values, $insertAt, 0, [$row['source_id'] ?? null]);
        }

        if ($this->columnExists('employee_daily_schedule_cache', 'updated_at')) {
            $columns[] = 'updated_at';
            $values[] = date('Y-m-d H:i:s');
        }

        $safeColumns = array_map(static fn($column) => "`$column`", $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_daily_schedule_cache (" . implode(', ', $safeColumns) . ")
            VALUES ($placeholders)
        ");
        $stmt->execute($values);
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!isset($this->columnsByTable[$table])) {
            $this->columnsByTable[$table] = [];
            try {
                $safeTable = str_replace('`', '``', $table);
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `$safeTable`");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $this->columnsByTable[$table][$row['Field']] = true;
                }
            } catch (PDOException $e) {
                $this->columnsByTable[$table] = [];
            }
        }

        return isset($this->columnsByTable[$table][$column]);
    }
}
