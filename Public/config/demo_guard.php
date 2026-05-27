<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function demo_is_mode(): bool
{
    return !empty($_SESSION['demo_mode']);
}

function demo_is_admin_mode(): bool
{
    return !empty($_SESSION['demo_mode']) && !empty($_SESSION['demo_admin_mode']);
}

function demo_block_admin_mutation(string $message = 'This action is disabled in admin demo mode.'): void
{
    if (!demo_is_admin_mode()) {
        return;
    }

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['error'] = $message;
    $fallback = $_SERVER['HTTP_REFERER'] ?? '../views/admin_homepage.php';
    header('Location: ' . $fallback);
    exit;
}

function demo_block_mutation(string $message = 'This action is disabled in demo mode.'): void
{
    if (!demo_is_mode()) {
        return;
    }

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['error'] = $message;
    $fallback = $_SERVER['HTTP_REFERER'] ?? '../module/time_log_create.php';
    header('Location: ' . $fallback);
    exit;
}

function demo_render_admin_locked_page(string $title, string $message = ''): void
{
    if (!demo_is_admin_mode()) {
        return;
    }

    $pageTitle = $title;
    $context = strtolower($title);
    $seed = crc32($title . date('Y-m-d'));
    mt_srand($seed);

    $names = ['Ava Cruz', 'Liam Santos', 'Noah Garcia', 'Mia Reyes', 'Ethan Lim', 'Sara Velasco', 'Jade Ramos', 'Kyle Ortiz'];
    $departments = ['Operations', 'HR', 'Finance', 'IT', 'Marketing', 'Support'];
    $roles = ['Coordinator', 'Analyst', 'Supervisor', 'Specialist', 'Associate', 'Lead'];
    $statuses = ['Approved', 'Pending', 'Reviewed', 'On Hold'];

    $stats = [];
    $columns = [];
    $rows = [];
    $tableTitle = 'Sample Records';

    if (str_contains($context, 'announcement')) {
        $stats = [
            ['Published', mt_rand(6, 18)],
            ['Drafts', mt_rand(1, 5)],
            ['Reads Today', mt_rand(24, 120)],
            ['Reactions', mt_rand(10, 60)],
        ];
        $columns = ['Title', 'Category', 'Status', 'Published'];
        $tableTitle = 'Sample Announcements';
        $categories = ['Company', 'HR', 'IT', 'Operations'];
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'title' => 'Update ' . ($i + 1) . ' - Demo Notice',
                'category' => $categories[array_rand($categories)],
                'status' => $statuses[array_rand($statuses)],
                'published' => date('M d, Y', strtotime("-$i days")),
            ];
        }
    } elseif (str_contains($context, 'employee')) {
        $stats = [
            ['Active', mt_rand(40, 120)],
            ['On Leave', mt_rand(2, 10)],
            ['New Hires', mt_rand(1, 6)],
            ['Inactive', mt_rand(1, 4)],
        ];
        $columns = ['Employee', 'Department', 'Role', 'Status'];
        $tableTitle = 'Sample Employees';
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'department' => $departments[array_rand($departments)],
                'role' => $roles[array_rand($roles)],
                'status' => $i % 2 === 0 ? 'Active' : 'On Leave',
            ];
        }
    } elseif (str_contains($context, 'leave')) {
        $stats = [
            ['Pending', mt_rand(3, 12)],
            ['Approved', mt_rand(8, 22)],
            ['Rejected', mt_rand(1, 6)],
            ['Balance Used', mt_rand(40, 90) . '%'],
        ];
        $columns = ['Employee', 'Type', 'Dates', 'Status'];
        $tableTitle = 'Sample Leave Requests';
        $types = ['Vacation', 'Sick', 'Emergency', 'Paternity'];
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'type' => $types[array_rand($types)],
                'dates' => date('M d', strtotime("+$i days")) . ' - ' . date('M d', strtotime("+$i days +2 days")),
                'status' => $statuses[array_rand($statuses)],
            ];
        }
    } elseif (str_contains($context, 'overtime')) {
        $stats = [
            ['Pending', mt_rand(2, 8)],
            ['Approved', mt_rand(6, 16)],
            ['Hours', mt_rand(40, 120)],
            ['Cost Impact', '$' . mt_rand(3, 9) . 'k'],
        ];
        $columns = ['Employee', 'Date', 'Hours', 'Status'];
        $tableTitle = 'Sample Overtime Requests';
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'date' => date('M d, Y', strtotime("-$i days")),
                'hours' => mt_rand(1, 5) . ' hrs',
                'status' => $statuses[array_rand($statuses)],
            ];
        }
    } elseif (str_contains($context, 'payroll')) {
        $stats = [
            ['Net Payroll', '$' . mt_rand(120, 280) . 'k'],
            ['Deductions', '$' . mt_rand(12, 40) . 'k'],
            ['Overtime', '$' . mt_rand(8, 25) . 'k'],
            ['Allowances', '$' . mt_rand(6, 18) . 'k'],
        ];
        $columns = ['Employee', 'Period', 'Gross', 'Net'];
        $tableTitle = 'Sample Payroll Summary';
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'period' => date('M 1', strtotime('-1 month')) . ' - ' . date('M 15', strtotime('-1 month')),
                'gross' => '$' . mt_rand(18, 35) . 'k',
                'net' => '$' . mt_rand(14, 28) . 'k',
            ];
        }
    } elseif (str_contains($context, 'schedule')) {
        $stats = [
            ['Requests', mt_rand(5, 14)],
            ['Approved', mt_rand(3, 10)],
            ['Swaps', mt_rand(1, 6)],
            ['Overrides', mt_rand(2, 8)],
        ];
        $columns = ['Employee', 'Change', 'Date', 'Status'];
        $tableTitle = 'Sample Schedule Changes';
        $changes = ['Shift swap', 'Schedule update', 'Rest day', 'Rotation change'];
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'change' => $changes[array_rand($changes)],
                'date' => date('M d, Y', strtotime("+$i days")),
                'status' => $statuses[array_rand($statuses)],
            ];
        }
    } elseif (str_contains($context, 'attendance')) {
        $stats = [
            ['Present', mt_rand(35, 90)],
            ['Late', mt_rand(2, 10)],
            ['Absent', mt_rand(1, 6)],
            ['Overtime', mt_rand(5, 18)],
        ];
        $columns = ['Employee', 'Date', 'Time In', 'Status'];
        $tableTitle = 'Sample Attendance';
        $attendanceStatuses = ['Present', 'Late', 'Absent', 'Half Day'];
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'employee' => $names[$i],
                'date' => date('M d, Y', strtotime("-$i days")),
                'time_in' => (7 + mt_rand(0, 2)) . ':' . str_pad((string)mt_rand(0, 1) * 30, 2, '0', STR_PAD_LEFT) . ' AM',
                'status' => $attendanceStatuses[array_rand($attendanceStatuses)],
            ];
        }
    } else {
        $stats = [
            ['Items', mt_rand(10, 40)],
            ['Pending', mt_rand(2, 12)],
            ['Approved', mt_rand(4, 18)],
            ['Updated', mt_rand(3, 9)],
        ];
        $columns = ['Name', 'Detail', 'Status', 'Updated'];
        $tableTitle = 'Sample Records';
        for ($i = 0; $i < 6; $i++) {
            $rows[] = [
                'name' => $names[$i],
                'detail' => $departments[array_rand($departments)] . ' update',
                'status' => $statuses[array_rand($statuses)],
                'updated' => date('M d, Y', strtotime("-$i days")),
            ];
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Demo Locked</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    </head>
    <body class="bg-gray-100">
    <div x-data="{ open: false }" class="flex h-screen">
        <?php include(__DIR__ . '/../views/sidebar.php'); ?>
        <div class="flex-1 flex flex-col min-w-0">
            <?php include(__DIR__ . '/../views/header.php'); ?>
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Demo Preview</p>
                                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($title) ?></h1>
                                <p class="mt-2 text-sm text-gray-600">
                                    Showing generated sample data only. Actions that modify real records remain disabled in admin demo mode.
                                </p>
                            </div>
                            <div class="rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700">
                                Generated: <?= date('M d, Y') ?>
                            </div>
                        </div>
                    </section>

                    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <?php foreach ($stats as $stat): ?>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($stat[0]) ?></p>
                                <p class="mt-2 text-2xl font-bold text-gray-900"><?= htmlspecialchars((string)$stat[1]) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($tableTitle) ?></h2>
                            <span class="text-xs text-gray-500">Sample rows</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <?php foreach ($columns as $column): ?>
                                            <th class="px-6 py-3 text-left font-semibold uppercase tracking-wide text-xs"><?= htmlspecialchars($column) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <?php
                                                    $key = strtolower(str_replace(' ', '_', $column));
                                                    $value = $row[$key] ?? '-';
                                                ?>
                                                <td class="px-6 py-3"><?= htmlspecialchars((string)$value) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="mt-6 flex justify-end">
                        <a href="admin_homepage.php" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 font-semibold text-white hover:bg-gray-800">
                            <i class="fas fa-arrow-left"></i>
                            Back to Demo Dashboard
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

function demo_admin_sample_employees(int $count = 12): array
{
    $firstNames = ['Alex', 'Jordan', 'Taylor', 'Casey', 'Riley', 'Morgan', 'Quinn', 'Avery', 'Skyler', 'Dakota'];
    $lastNames = ['Reyes', 'Santos', 'Garcia', 'Lim', 'Cruz', 'Rivera', 'Navarro', 'Torres', 'DelaRosa', 'Ortega'];
    $positions = ['HR Associate', 'Operations Analyst', 'IT Specialist', 'Finance Coordinator', 'Support Lead', 'Marketing Assistant'];
    $departments = ['Operations', 'HR', 'Finance', 'IT', 'Marketing', 'Support'];

    $employees = [];
    for ($i = 0; $i < $count; $i++) {
        $fname = $firstNames[$i % count($firstNames)];
        $lname = $lastNames[($i + 2) % count($lastNames)];
        $employees[] = [
            'id' => 1000 + $i,
            'fname' => $fname,
            'lname' => $lname,
            'email' => strtolower($fname . '.' . $lname) . '@demo.local',
            'contact' => '09' . str_pad((string)(100000000 + ($i * 7)), 9, '0', STR_PAD_LEFT),
            'position' => $positions[$i % count($positions)],
            'department' => $departments[$i % count($departments)],
            'status' => $i % 5 === 0 ? 'Inactive' : 'Active',
            'Emp_Type' => $i % 2 === 0 ? 'Regular' : 'Probationary',
            'profile_picture' => null,
        ];
    }

    return $employees;
}

function demo_admin_sample_leave_requests(bool $history = false, int $count = 10): array
{
    $employees = demo_admin_sample_employees($count);
    $types = ['vacation_leave', 'sick_leave', 'emergency_leave', 'maternity_leave', 'paternity_leave'];
    $reasons = ['Medical appointment', 'Family event', 'Rest and recovery', 'Personal errand', 'Travel'];

    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $employee = $employees[$i % count($employees)];
        $start = date('Y-m-d', strtotime("+" . ($i + 1) . " days"));
        $end = date('Y-m-d', strtotime("+" . ($i + 2) . " days"));
        $rows[] = [
            'id' => 2000 + $i,
            'leave_type' => $types[$i % count($types)],
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reasons[$i % count($reasons)],
            'status' => $history ? ($i % 3 === 0 ? 'approved' : 'rejected') : 'pending',
            'attachment_lr' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days")),
            'explanation' => $history ? 'Reviewed in demo mode.' : '',
            'employee_id' => $employee['id'],
            'fname' => $employee['fname'],
            'lname' => $employee['lname'],
        ];
    }

    return $rows;
}

function demo_admin_sample_time_adjustments(bool $history = false, int $count = 8): array
{
    $employees = demo_admin_sample_employees($count);
    $reasons = ['Missed time out', 'System delay', 'Approved correction', 'Network issue'];

    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $employee = $employees[$i % count($employees)];
        $logDate = date('Y-m-d', strtotime("-" . ($i + 1) . " days"));
        $rows[] = [
            'id' => 3000 + $i,
            'employee_id' => $employee['id'],
            'log_date' => $logDate,
            'current_time_in' => '08:00:00',
            'current_time_out' => $history ? '17:00:00' : null,
            'requested_time_in' => '08:05:00',
            'requested_time_out' => '17:10:00',
            'reason' => $reasons[$i % count($reasons)],
            'status' => $history ? ($i % 2 === 0 ? 'approved' : 'rejected') : 'pending',
            'attachment' => null,
            'submitted_at' => date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days")),
            'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days")),
            'fname' => $employee['fname'],
            'lname' => $employee['lname'],
        ];
    }

    return $rows;
}

function demo_admin_sample_overtime_requests(bool $history = false, int $count = 8): array
{
    $employees = demo_admin_sample_employees($count);
    $types = ['Regular OT', 'Rest Day OT', 'Holiday OT'];
    $reasons = ['Project deadline', 'Client request', 'System maintenance'];

    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $employee = $employees[$i % count($employees)];
        $logDate = date('Y-m-d', strtotime("-" . ($i + 1) . " days"));
        $rows[] = [
            'id' => 4000 + $i,
            'employee_id' => $employee['id'],
            'time_log_id' => 5000 + $i,
            'time_in' => '17:30:00',
            'time_out' => '20:00:00',
            'ot_duration' => 2.5,
            'ot_type' => $types[$i % count($types)],
            'reason' => $reasons[$i % count($reasons)],
            'status' => $history ? ($i % 2 === 0 ? 'approved' : 'declined') : 'pending',
            'attachment' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days")),
            'approved_at' => $history ? date('Y-m-d H:i:s', strtotime("-" . ($i) . " days")) : null,
            'approved_by' => $history ? 'Demo Admin' : null,
            'fname' => $employee['fname'],
            'lname' => $employee['lname'],
            'log_date' => $logDate,
            'current_schedule' => [
                'schedule_id' => 5,
                'time_in' => '8:00 AM',
                'time_out' => '5:00 PM',
            ],
        ];
    }

    return $rows;
}

function demo_admin_sample_schedule_requests(string $view, int $count = 6): array
{
    $employees = demo_admin_sample_employees($count);
    $rows = [];

    for ($i = 0; $i < $count; $i++) {
        $employee = $employees[$i % count($employees)];
        $createdAt = date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days"));

        if ($view === 'monthly') {
            $rows[] = [
                'id' => 6000 + $i,
                'reason' => 'Monthly schedule adjustment',
                'status' => $i % 3 === 0 ? 'approved' : 'pending',
                'year' => date('Y'),
                'month' => date('m'),
                'created_at' => $createdAt,
                'sunday_schedule_id' => 0,
                'sunday_is_rest_day' => 1,
                'monday_schedule_id' => 5,
                'monday_is_rest_day' => 0,
                'tuesday_schedule_id' => 5,
                'tuesday_is_rest_day' => 0,
                'wednesday_schedule_id' => 5,
                'wednesday_is_rest_day' => 0,
                'thursday_schedule_id' => 5,
                'thursday_is_rest_day' => 0,
                'friday_schedule_id' => 5,
                'friday_is_rest_day' => 0,
                'saturday_schedule_id' => 0,
                'saturday_is_rest_day' => 1,
                'attachment_path' => null,
                'processed_at' => null,
                'processed_by' => null,
                'admin_notes' => null,
                'employee_id' => $employee['id'],
                'fname' => $employee['fname'],
                'lname' => $employee['lname'],
            ];
        } elseif ($view === 'swap') {
            $rows[] = [
                'id' => 7000 + $i,
                'employee_id' => $employee['id'],
                'source_date' => date('Y-m-d', strtotime("+" . ($i + 2) . " days")),
                'target_date' => date('Y-m-d', strtotime("+" . ($i + 6) . " days")),
                'reason' => 'Schedule swap for demo',
                'attachment_path' => null,
                'status' => $i % 2 === 0 ? 'pending' : 'approved',
                'created_at' => $createdAt,
                'processed_at' => $i % 2 === 0 ? null : date('Y-m-d H:i:s', strtotime("-" . $i . " days")),
                'processed_by' => $i % 2 === 0 ? null : 'Demo Admin',
                'admin_notes' => null,
                'fname' => $employee['fname'],
                'lname' => $employee['lname'],
            ];
        } else {
            $status = $view === 'history' ? ($i % 2 === 0 ? 'approved' : 'forfeited') : 'pending';
            $rows[] = [
                'id' => 8000 + $i,
                'reason' => 'Schedule change request',
                'status' => $status,
                'start_date' => date('Y-m-d', strtotime("+" . ($i + 1) . " days")),
                'end_date' => date('Y-m-d', strtotime("+" . ($i + 2) . " days")),
                'created_at' => $createdAt,
                'work_schedule_id' => 5,
                'current_work_schedule_id' => 4,
                'attachment_scr' => null,
                'explanation' => $status !== 'pending' ? 'Reviewed in demo mode.' : '',
                'approved_at' => $status !== 'pending' ? date('Y-m-d H:i:s', strtotime("-" . $i . " days")) : null,
                'employee_id' => $employee['id'],
                'is_rest_day' => 0,
                'fname' => $employee['fname'],
                'lname' => $employee['lname'],
                'time_in' => '08:00:00',
                'time_out' => '17:00:00',
                'current_time_in' => '07:00:00',
                'current_time_out' => '16:00:00',
            ];
        }
    }

    return $rows;
}

function demo_admin_sample_announcements(int $count = 4): array
{
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'announcement_id' => 9000 + $i,
            'title' => 'Demo Update #' . ($i + 1),
            'content' => 'This is a demo announcement preview used for admin mode display.',
            'admin_name' => 'Demo Admin',
            'created_at' => date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days")),
            'image' => null,
        ];
    }

    return $rows;
}

function demo_admin_sample_leave_credits(int $employeeId): array
{
    $types = ['vacation', 'sick', 'emergency'];
    $rows = [];
    foreach ($types as $index => $type) {
        $rows[] = [
            'employee_id' => $employeeId,
            'leave_type' => $type,
            'balance' => $type === 'vacation' ? 7.5 : ($type === 'sick' ? 5.0 : 2.0),
            'carry_over' => $type === 'vacation' ? 1.5 : null,
            'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        ];
    }

    return $rows;
}
