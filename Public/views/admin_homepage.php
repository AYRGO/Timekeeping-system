<?php
session_start();
include '../config/db.php';

// Handle switch to employee view
if (isset($_POST['switch_to_employee'])) {
    $_SESSION['view_mode'] = 'employee';
    header("Location: ../module/time_log_create.php");
    exit;
}

// Check authorization before including any output-producing file
if (
    !isset($_SESSION['employee']['id']) ||
    $_SESSION['employee']['role'] !== 'internal' ||
    $_SESSION['view_mode'] !== 'admin'
) {
    // Unauthorized, redirect to employee view
    header("Location: ../module/time_log_create.php");
    exit;
}

// Get stats
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$pendingSchedules = $pdo->query("SELECT COUNT(*) FROM schedule_change_requests WHERE status = 'pending'")->fetchColumn();
$pendingOT = $pdo->query("SELECT COUNT(*) FROM overtime_requests WHERE status = 'Pending'")->fetchColumn();
$pendingTimeAdjustments = $pdo->query("SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'pending'")->fetchColumn();

// Late clock-ins logic
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Admin Dashboard</title>
</head>
<body class="bg-gray-100">

    <!-- Main Layout Container with Alpine.js -->
    <div x-data="{ open: false }" class="flex h-screen bg-gray-100 overflow-hidden">
        
        <!-- Include Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Include Header -->
            <?php include('header.php'); ?>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-6 overflow-y-auto">
                <div class="grid grid-cols-1 gap-4 md:gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-6 md:mb-10">
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
                        [
                            "title" => "Pending OT Requests",
                            "value" => $pendingOT,
                            "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                            "link" => "ot_request.php"
                        ],
                        [
                            "title" => "Pending Time Adjustments",
                            "value" => $pendingTimeAdjustments,
                            "icon" => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v1H8a1 1 0 000 2h1v1a3 3 0 006 0v-1h1a1 1 0 100-2h-1v-1c0-1.657-1.343-3-3-3z" />',
                            "link" => "time_adjustment_list.php"
                        ],
                    ];

                    foreach ($cards as $card) {
                        echo '
                            <div class="bg-white shadow rounded-lg p-4 md:p-6 card-hover transition-transform duration-300">
                                <div class="flex items-center">
                                    <div class="bg-green-100 p-2 md:p-3 rounded-md text-green-600">
                                        <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                                            . $card["icon"] .
                                        '</svg>
                                    </div>
                                    <div class="ml-3 md:ml-4 flex-1">
                                        <p class="text-xs md:text-sm font-medium text-gray-500">' . $card["title"] . '</p>
                                        <div class="text-xl md:text-2xl font-semibold text-gray-900">' . $card["value"] . '</div>
                                    </div>
                                </div>
                                <div class="mt-3 md:mt-4 text-sm text-right">
                                    <a href="' . $card["link"] . '" class="text-green-600 hover:text-green-500 font-medium">View</a>
                                </div>
                            </div>';
                    }
                    ?>
                </div>

                <!-- Quick Actions -->
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-16">
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
                        <a href="' . $action["link"] . '" class="relative rounded-lg border border-gray-200 bg-white px-4 md:px-6 py-4 md:py-5 shadow-sm flex items-center space-x-3 md:space-x-4 hover:border-green-500 focus:outline-none card-hover transition-all duration-200">
                            <div class="bg-green-100 p-2 md:p-3 rounded-lg text-green-600">
                                <svg class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                                    . $action["icon"] .
                                '</svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">' . $action["title"] . '</p>
                                <p class="text-xs md:text-sm text-gray-500">' . $action["desc"] . '</p>
                            </div>
                        </a>';
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

</body>
</html>