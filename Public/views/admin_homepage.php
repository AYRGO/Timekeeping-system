<?php
include 'header.php';
include '../config/db.php';

// Get counts
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$pendingSchedules = $pdo->query("SELECT COUNT(*) FROM schedule_change_requests WHERE status = 'pending'")->fetchColumn();

// Late clock-ins logic (e.g., after 9:15 AM)
$today = date('Y-m-d');
$lateClockins = $pdo->prepare("
    SELECT COUNT(*) FROM time_logs tl
    JOIN employee_schedules es ON tl.employee_id = es.employee_id
    JOIN work_schedules ws ON es.work_schedule_id = ws.id AND ws.day_of_week = DAYNAME(NOW())
    WHERE tl.log_date = ? AND tl.time_in > ADDTIME(ws.time_in, '00:15:00')
");
$lateClockins->execute([$today]);
$lateCount = $lateClockins->fetchColumn();
?>

<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Welcome Banner -->
        <div class="gradient-bg rounded-xl shadow-xl overflow-hidden mb-10">
            <div class="p-8 md:p-12">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-3xl font-bold leading-tight text-white sm:text-4xl">
                            Welcome to RSS Admin
                        </h2>
                        <p class="mt-2 max-w-4xl text-lg text-green-100">
                            Manage your workforce efficiently with our HR solution. Track employees, leave requests, and logs all in one place.
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <div class="inline-flex rounded-md shadow">
                            <a href="announcement.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-green-700 bg-white hover:bg-gray-50">
                                View Announcements
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-10">
            <?php
            $cards = [
                [
                    "title" => "Total Employees",
                    "value" => $totalEmployees,
                    "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zM13 7a4 4 0 11-8 0 4 4 0 018 0z" />',
                    "link" => "employee_list.php"
                ],
                [
                    "title" => "Pending Leave Requests",
                    "value" => $pendingLeaves,
                    "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
                    "link" => "leave_request_list.php"
                ],
    [
        "title" => "Pending Schedule Requests",
        "value" => $pendingSchedules,
        "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />',
        "link" => "schedule_request.php"
    ],
];

foreach ($cards as $card) {
    echo '
                <div class="bg-white shadow rounded-lg p-6 card-hover transition-transform duration-300">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-md text-green-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                                . $card["icon"] .
                            '</svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-500">' . $card["title"] . '</p>
                            <div class="text-2xl font-semibold text-gray-900">' . $card["value"] . '</div>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-right">
                        <a href="' . $card["link"] . '" class="text-green-600 hover:text-green-500 font-medium">View</a>
                    </div>
                </div>';
            }
            ?>
        </div>

        <!-- Quick Actions -->
        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
            <?php
            $actions = [
                [
                    "title" => "Manage Employees",
                    "desc" => "Add or update employee records",
                    "link" => "employee_list.php",
                    "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 0116.88 6.196M15 21H3v-1a6 6 0 0112 0v1z" />',
                ],
                [
                    "title" => "Approve Leave",
                    "desc" => "Review leave requests",
                    "link" => "leave_request_list.php",
                    "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
                ],
                [
                    "title" => "Review Logs",
                    "desc" => "Daily login activity",
                    "link" => "time_log_list.php",
                    "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                ],
            ];

            foreach ($actions as $action) {
                echo '
                <a href="' . $action["link"] . '" class="relative rounded-lg border border-gray-200 bg-white px-6 py-5 shadow-sm flex items-center space-x-4 hover:border-green-500 focus:outline-none card-hover transition-all duration-200">
                    <div class="bg-green-100 p-3 rounded-lg text-green-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                            . $action["icon"] .
                        '</svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">' . $action["title"] . '</p>
                        <p class="text-sm text-gray-500">' . $action["desc"] . '</p>
                    </div>
                </a>';
            }
            ?>
        </div>
    </div>
</main>
