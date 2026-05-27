<?php

const DEMO_EMPLOYEE_USERNAME = 'demo.harley';
const DEMO_EMPLOYEE_PASSWORD = 'demo123';
const DEMO_EMPLOYEE_EMAIL = 'demo.harley@example.com';

function demo_table_exists(PDO $pdo, string $table): bool
{
    static $cache = [];

    if (!array_key_exists($table, $cache)) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $cache[$table] = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $cache[$table] = false;
        }
    }

    return $cache[$table];
}

function demo_table_columns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (!isset($cache[$table])) {
        $cache[$table] = [];
        try {
            $safeTable = str_replace('`', '``', $table);
            $stmt = $pdo->query("SHOW COLUMNS FROM `$safeTable`");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cache[$table][$row['Field']] = true;
            }
        } catch (PDOException $e) {
            $cache[$table] = [];
        }
    }

    return $cache[$table];
}

function demo_column_exists(PDO $pdo, string $table, string $column): bool
{
    $columns = demo_table_columns($pdo, $table);
    return isset($columns[$column]);
}

function demo_select_schedule_id(PDO $pdo): ?int
{
    if (!demo_table_exists($pdo, 'work_schedules')) {
        return null;
    }

    $stmt = $pdo->query("
        SELECT id
        FROM work_schedules
        ORDER BY CASE WHEN id = 4 THEN 0 ELSE 1 END, id
        LIMIT 1
    ");
    $scheduleId = $stmt->fetchColumn();

    return $scheduleId ? (int)$scheduleId : null;
}

function demo_write_employee(PDO $pdo, ?int $employeeId, array $values): int
{
    $columns = demo_table_columns($pdo, 'employees');
    $filtered = array_intersect_key($values, $columns);

    if ($employeeId) {
        unset($filtered['username']);
        $assignments = [];
        $params = [];
        foreach ($filtered as $column => $value) {
            $assignments[] = "`$column` = ?";
            $params[] = $value;
        }
        $params[] = $employeeId;

        if ($assignments) {
            $stmt = $pdo->prepare("UPDATE employees SET " . implode(', ', $assignments) . " WHERE id = ?");
            $stmt->execute($params);
        }

        return $employeeId;
    }

    $columnNames = array_keys($filtered);
    $safeColumns = array_map(static fn($column) => "`$column`", $columnNames);
    $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));

    $stmt = $pdo->prepare(
        "INSERT INTO employees (" . implode(', ', $safeColumns) . ") VALUES ($placeholders)"
    );
    $stmt->execute(array_values($filtered));

    return (int)$pdo->lastInsertId();
}

function demo_ensure_employee(PDO $pdo): array
{
    $scheduleId = demo_select_schedule_id($pdo);

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ? LIMIT 1");
    $stmt->execute([DEMO_EMPLOYEE_USERNAME]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeId = $existing ? (int)$existing['id'] : null;

    $values = [
        'fname' => 'Demo',
        'lname' => 'Employee',
        'email' => DEMO_EMPLOYEE_EMAIL,
        'personal_email' => DEMO_EMPLOYEE_EMAIL,
        'contact' => 'N/A',
        'position' => 'Demo Account',
        'status' => 'active',
        'Emp_Type' => 'Regular',
        'username' => DEMO_EMPLOYEE_USERNAME,
        'password' => DEMO_EMPLOYEE_PASSWORD,
        'company' => 'Demo Workspace',
        'official_sched' => $scheduleId,
        'role' => 'employee',
        'admin_rights_hdesk' => null,
    ];

    $employeeId = demo_write_employee($pdo, $employeeId, $values);

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
    $stmt->execute([$employeeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function demo_reset_employee_data(PDO $pdo, int $employeeId): void
{
    $tables = [
        'time_logs',
        'shift_completions',
        'leave_requests',
        'post_leave_requests',
        'post_ot_requests',
        'post2_overtime_requests',
        'post_overtime_requests',
        'new_ot_requests',
        'overtime_requests',
        'final_overtime_requests',
        'leave_credits_history',
        'time_adjustment_requests',
        'post_time_adjustment_requests',
        'schedule_change_requests',
        'post_schedule_change_requests',
        'month_weekly_schedule',
        'schedule_switch_requests',
        'schedule_switch_requests_history',
        'employee_schedule_history',
        'employee_daily_schedules',
        'employee_daily_schedule_cache',
        'employee_rotating_schedules',
        'notifications',
        'announcement_reactions',
        'comments',
        'post_reactions',
        'user_read_announcements',
    ];

    foreach ($tables as $table) {
        if (!demo_table_exists($pdo, $table) || !demo_column_exists($pdo, $table, 'employee_id')) {
            continue;
        }

        $safeTable = str_replace('`', '``', $table);
        $stmt = $pdo->prepare("DELETE FROM `$safeTable` WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
    }
}

function demo_ensure_checklist(PDO $pdo, int $employeeId): void
{
    if (!demo_table_exists($pdo, 'employee_checklist')) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM employee_checklist WHERE employee_id = ? LIMIT 1");
    $stmt->execute([$employeeId]);
    if ($stmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO employee_checklist (employee_id) VALUES (?)");
    $stmt->execute([$employeeId]);
}

function demo_ensure_leave_credits(PDO $pdo, int $employeeId): void
{
    if (!demo_table_exists($pdo, 'leave_credits')) {
        return;
    }

    $year = (int)date('Y');
    $credits = [
        'vacation' => 15,
        'sick' => 15,
        'solo_parent' => 7,
        'paternity' => 7,
        'maternity' => 105,
        'bereavement' => 5,
    ];

    foreach ($credits as $type => $balance) {
        $stmt = $pdo->prepare("SELECT id FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ? LIMIT 1");
        $stmt->execute([$employeeId, $type, $year]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $assignments = ['balance = ?'];
            $params = [$balance];

            if (demo_column_exists($pdo, 'leave_credits', 'updated_at')) {
                $assignments[] = 'updated_at = NOW()';
            }

            $params[] = $existingId;
            $stmt = $pdo->prepare("UPDATE leave_credits SET " . implode(', ', $assignments) . " WHERE id = ?");
            $stmt->execute($params);
            continue;
        }

        $values = [
            'employee_id' => $employeeId,
            'leave_type' => $type,
            'balance' => $balance,
            'carry_over' => 0,
            'year' => $year,
            'monthly_increment' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $columns = demo_table_columns($pdo, 'leave_credits');
        $filtered = array_intersect_key($values, $columns);
        $columnNames = array_keys($filtered);
        $safeColumns = array_map(static fn($column) => "`$column`", $columnNames);
        $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));

        $stmt = $pdo->prepare(
            "INSERT INTO leave_credits (" . implode(', ', $safeColumns) . ") VALUES ($placeholders)"
        );
        $stmt->execute(array_values($filtered));
    }
}

function demo_ensure_default_schedule(PDO $pdo, int $employeeId): void
{
    if (!demo_table_exists($pdo, 'employee_default_schedules')) {
        return;
    }

    $scheduleId = demo_select_schedule_id($pdo);
    $stmt = $pdo->prepare("DELETE FROM employee_default_schedules WHERE employee_id = ?");
    $stmt->execute([$employeeId]);

    for ($day = 0; $day <= 6; $day++) {
        $isRestDay = in_array($day, [0, 6], true) ? 1 : 0;
        $workScheduleId = $isRestDay ? null : $scheduleId;

        $stmt = $pdo->prepare("
            INSERT INTO employee_default_schedules
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$employeeId, $day, $workScheduleId, $isRestDay, date('Y-01-01')]);
    }
}

function demo_rebuild_schedule_cache(PDO $pdo, int $employeeId): void
{
    $path = __DIR__ . '/ScheduleCacheRebuilder.php';
    if (!demo_table_exists($pdo, 'employee_daily_schedule_cache') || !file_exists($path)) {
        return;
    }

    require_once $path;

    $start = date('Y-m-01');
    $end = date('Y-m-t', strtotime('+1 month'));
    try {
        $rebuilder = new ScheduleCacheRebuilder($pdo);
        $rebuilder->rebuildForEmployees([$employeeId], $start, $end);
    } catch (Throwable $e) {
        error_log('Demo schedule cache rebuild skipped: ' . $e->getMessage());
    }
}

function demo_prepare_account(PDO $pdo, bool $resetData = true): array
{
    $employee = demo_ensure_employee($pdo);
    $employeeId = (int)$employee['id'];

    if ($resetData) {
        demo_reset_employee_data($pdo, $employeeId);
    }

    demo_ensure_checklist($pdo, $employeeId);
    demo_ensure_leave_credits($pdo, $employeeId);
    demo_ensure_default_schedule($pdo, $employeeId);
    demo_rebuild_schedule_cache($pdo, $employeeId);

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
    $stmt->execute([$employeeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function demo_login_employee(array $employee): void
{
    session_regenerate_id(true);

    $_SESSION['employee'] = [
        'id' => (int)$employee['id'],
        'username' => $employee['username'] ?? DEMO_EMPLOYEE_USERNAME,
        'fname' => $employee['fname'] ?? 'Demo',
        'lname' => $employee['lname'] ?? 'Employee',
        'position' => $employee['position'] ?? 'Demo Account',
        'role' => 'employee',
    ];

    $_SESSION['user_id'] = (int)$employee['id'];
    $_SESSION['view_mode'] = 'employee';
    $_SESSION['demo_mode'] = true;
    $_SESSION['demo_started_at'] = time();
}
