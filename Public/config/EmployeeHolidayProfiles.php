<?php

class EmployeeHolidayProfiles
{
    private PDO $pdo;
    private static ?bool $tablesAvailable = null;
    private array $assignmentCache = [];
    private array $profileHolidayCache = [];
    private array $companyHolidayCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function resolveHolidayForEmployeeDate(int $employeeId, string $date): array
    {
        $resolved = [
            'is_holiday' => 0,
            'holiday_name' => null,
            'holiday_type' => null,
            'holiday_profile_code' => null,
            'holiday_profile_name' => null,
            'source' => null,
        ];

        if (!$this->tablesAvailable()) {
            $companyHoliday = $this->getCompanyHoliday($date);
            if ($companyHoliday) {
                return [
                    'is_holiday' => 1,
                    'holiday_name' => $companyHoliday['holiday_name'],
                    'holiday_type' => $companyHoliday['holiday_type'] ?? null,
                    'holiday_profile_code' => null,
                    'holiday_profile_name' => null,
                    'source' => 'company_holiday',
                ];
            }

            return $resolved;
        }

        $profile = $this->getActiveProfileForEmployeeDate($employeeId, $date);
        if ($profile) {
            $holiday = $this->getProfileHolidayForDate((int)$profile['profile_id'], $date);
            if ($holiday) {
                return [
                    'is_holiday' => 1,
                    'holiday_name' => $holiday['holiday_name'],
                    'holiday_type' => $holiday['holiday_type'] ?? null,
                    'holiday_profile_code' => $profile['profile_code'],
                    'holiday_profile_name' => $profile['profile_name'],
                    'source' => 'holiday_profile:' . $profile['profile_code'],
                ];
            }

            return [
                'is_holiday' => 0,
                'holiday_name' => null,
                'holiday_type' => null,
                'holiday_profile_code' => $profile['profile_code'],
                'holiday_profile_name' => $profile['profile_name'],
                'source' => 'holiday_profile:' . $profile['profile_code'],
            ];
        }

        $companyHoliday = $this->getCompanyHoliday($date);
        if ($companyHoliday) {
            return [
                'is_holiday' => 1,
                'holiday_name' => $companyHoliday['holiday_name'],
                'holiday_type' => $companyHoliday['holiday_type'] ?? null,
                'holiday_profile_code' => null,
                'holiday_profile_name' => null,
                'source' => 'company_holiday',
            ];
        }

        return $resolved;
    }

    public function getCurrentProfileForEmployee(int $employeeId): ?array
    {
        return $this->getActiveProfileForEmployeeDate($employeeId, date('Y-m-d'));
    }

    public function getActiveProfileForEmployeeDate(int $employeeId, string $date): ?array
    {
        $cacheKey = $employeeId . '|' . $date;
        if (array_key_exists($cacheKey, $this->assignmentCache)) {
            return $this->assignmentCache[$cacheKey];
        }

        $stmt = $this->pdo->prepare("\n            SELECT\n                a.profile_id,\n                p.profile_code,\n                p.profile_name\n            FROM employee_holiday_profile_assignments a\n            INNER JOIN employee_holiday_profiles p ON a.profile_id = p.id\n            WHERE a.employee_id = ?\n              AND a.is_active = 1\n              AND p.is_active = 1\n              AND a.effective_from <= ?\n              AND (a.effective_until IS NULL OR a.effective_until >= ?)\n            ORDER BY a.effective_from DESC, a.id DESC\n            LIMIT 1\n        ");
        $stmt->execute([$employeeId, $date, $date]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->assignmentCache[$cacheKey] = $profile;
        return $profile;
    }

    private function getProfileHolidayForDate(int $profileId, string $date): ?array
    {
        $cacheKey = $profileId . '|' . $date;
        if (array_key_exists($cacheKey, $this->profileHolidayCache)) {
            return $this->profileHolidayCache[$cacheKey];
        }

        $stmt = $this->pdo->prepare("\n            SELECT holiday_name, holiday_type\n            FROM employee_holiday_profile_holidays\n            WHERE profile_id = ? AND holiday_date = ?\n            LIMIT 1\n        ");
        $stmt->execute([$profileId, $date]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->profileHolidayCache[$cacheKey] = $holiday;
        return $holiday;
    }

    private function getCompanyHoliday(string $date): ?array
    {
        if (array_key_exists($date, $this->companyHolidayCache)) {
            return $this->companyHolidayCache[$date];
        }

        $stmt = $this->pdo->prepare("\n            SELECT holiday_name, holiday_type\n            FROM company_holidays\n            WHERE holiday_date = ?\n            LIMIT 1\n        ");
        $stmt->execute([$date]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->companyHolidayCache[$date] = $holiday;
        return $holiday;
    }

    private function tablesAvailable(): bool
    {
        if (self::$tablesAvailable !== null) {
            return self::$tablesAvailable;
        }

        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'employee_holiday_profiles'");
            self::$tablesAvailable = $stmt && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            self::$tablesAvailable = false;
        }

        return self::$tablesAvailable;
    }
}