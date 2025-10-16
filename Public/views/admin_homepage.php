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
$activeEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'")->fetchColumn();
$inactiveEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'Inactive'")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$pendingSchedules = $pdo->query("SELECT COUNT(*) FROM schedule_change_requests WHERE status = 'pending'")->fetchColumn();
$pendingOT = $pdo->query("SELECT COUNT(*) FROM overtime_requests WHERE status = 'Pending'")->fetchColumn();
$pendingTimeAdjustments = $pdo->query("SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'pending'")->fetchColumn();

// Get recent activity from post tables
$recentActivity = [];

// Post Leave Requests
$leaveActivity = $pdo->query("
    SELECT 
        plr.start_date as date,
        'Leave Request' as type,
        'Approved' as status,
        CONCAT(e.fname, ' ', e.lname) as employee_name,
        plr.leave_type as details,
        plr.created_at
    FROM post_leave_requests plr
    JOIN employees e ON plr.employee_id = e.id
    ORDER BY plr.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Post Overtime Requests
$otActivity = $pdo->query("
    SELECT 
        por.date as date,
        'Overtime Request' as type,
        'Approved' as status,
        CONCAT(e.fname, ' ', e.lname) as employee_name,
        CONCAT(por.start_time, ' - ', por.end_time) as details,
        por.created_at
    FROM post_overtime_requests por
    JOIN employees e ON por.employee_id = e.id
    ORDER BY por.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Post Schedule Change Requests
$scheduleActivity = $pdo->query("
    SELECT 
        pscr.start_date as date,
        'Schedule Change' as type,
        'Approved' as status,
        CONCAT(e.fname, ' ', e.lname) as employee_name,
        CONCAT('Schedule ID: ', pscr.work_schedule_id) as details,
        pscr.created_at
    FROM post_schedule_change_requests pscr
    JOIN employees e ON pscr.employee_id = e.id
    ORDER BY pscr.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Post Time Adjustment Requests
$adjustmentActivity = $pdo->query("
    SELECT 
        ptar.log_date as date,
        'Time Adjustment' as type,
        'Approved' as status,
        CONCAT(e.fname, ' ', e.lname) as employee_name,
        'Time adjustment' as details,
        ptar.created_at
    FROM post_time_adjustment_requests ptar
    JOIN employees e ON ptar.employee_id = e.id
    ORDER BY ptar.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Combine and sort all activities
$recentActivity = array_merge($leaveActivity, $otActivity, $scheduleActivity, $adjustmentActivity);
usort($recentActivity, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentActivity = array_slice($recentActivity, 0, 30); // Show latest 30 activities

// Get chart data
$chartData = [
    'pendingRequests' => [
        'leaves' => $pendingLeaves, 
        'schedules' => $pendingSchedules,
        'overtime' => $pendingOT,
        'adjustments' => $pendingTimeAdjustments
    ],
    'monthlyActivity' => []
];

// Get monthly employee status data for the last 6 months
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months")); // Last day of the month
    
    // Count employees hired up to this month (cumulative)
    $monthlyActive = $pdo->query("
        SELECT COUNT(*) FROM employees 
        WHERE DATE(created_at) <= '$monthEnd' 
        AND status = 'Active'
    ")->fetchColumn();
    
    // Count inactive employees (assuming they became inactive after being hired)
    $monthlyInactive = $pdo->query("
        SELECT COUNT(*) FROM employees 
        WHERE DATE(created_at) <= '$monthEnd' 
        AND status = 'Inactive'
    ")->fetchColumn();
    
    // Total employees hired up to this month
    $monthlyTotal = $pdo->query("
        SELECT COUNT(*) FROM employees 
        WHERE DATE(created_at) <= '$monthEnd'
    ")->fetchColumn();
    
    // New hires for this specific month
    $newHires = $pdo->query("
        SELECT COUNT(*) FROM employees 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
    ")->fetchColumn();
    
    $chartData['monthlyActivity'][] = [
        'month' => $monthName,
        'active' => (int)$monthlyActive,
        'inactive' => (int)$monthlyInactive,
        'total' => (int)$monthlyTotal,
        'newHires' => (int)$newHires
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #10B981 0%, #047857 100%);
        }
        .activity-item {
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transform: translateX(4px);
        }
        /* Custom scrollbar for activity section */
        .activity-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .activity-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        .activity-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .activity-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Main Layout Container with Alpine.js -->
    <div x-data="{ open: false, searchTerm: '', typeFilter: 'all' }" class="flex h-screen bg-gray-50 overflow-hidden">
        
        <!-- Include Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Include Header -->
            <?php include('header.php'); ?>

            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-y-auto">
                
                <!-- Welcome Section -->
                <div class="gradient-bg rounded-2xl p-8 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">Welcome back, Admin!</h1>
                            <p class="text-green-100 text-lg">Here's what's happening in your organization today.</p>
                        </div>
                        <div class="hidden md:block">
                            <div class="bg-white bg-opacity-20 rounded-xl p-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold"><?= date('d') ?></div>
                                    <div class="text-sm"><?= date('M Y') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <?php
                    $stats = [
                        [
                            "title" => "Total Employees",
                            "value" => $totalEmployees,
                            "icon" => "fas fa-users",
                            "color" => "blue",
                            "link" => "employee_list.php"
                        ],
                        [
                            "title" => "Active Employees",
                            "value" => $activeEmployees,
                            "icon" => "fas fa-user-check",
                            "color" => "green",
                            "link" => "employee_list.php"
                        ],
                        [
                            "title" => "Pending Leaves",
                            "value" => $pendingLeaves,
                            "icon" => "fas fa-calendar-alt",
                            "color" => "orange",
                            "link" => "leave_request_list.php"
                        ],
                        [
                            "title" => "Pending OT",
                            "value" => $pendingOT,
                            "icon" => "fas fa-clock",
                            "color" => "purple",
                            "link" => "ot_request.php"
                        ],
                        [
                            "title" => "Pending Adjustments",
                            "value" => $pendingTimeAdjustments,
                            "icon" => "fas fa-edit",
                            "color" => "green",
                            "link" => "time_adjustment_list.php"
                        ]
                    ];

                    foreach ($stats as $stat) {
                        $bgColor = "bg-{$stat['color']}-500";
                        $textColor = "text-{$stat['color']}-600";
                        $bgLight = "bg-{$stat['color']}-50";
                        
                        echo "
                        <div class='bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover'>
                            <div class='flex items-center justify-between'>
                                <div>
                                    <p class='text-sm font-medium text-gray-500 mb-1'>{$stat['title']}</p>
                                    <p class='text-3xl font-bold text-gray-900'>{$stat['value']}</p>
                                </div>
                                <div class='{$bgLight} p-3 rounded-xl'>
                                    <i class='{$stat['icon']} {$textColor} text-xl'></i>
                                </div>
                            </div>
                            <div class='mt-4'>
                                <a href='{$stat['link']}' class='{$textColor} text-sm font-medium hover:underline'>View Details →</a>
                            </div>
                        </div>";
                    }
                    ?>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    
                    <!-- Pending Requests Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Pending Requests</h3>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Current Status</span>
                            </div>
                        </div>
                        <div class="relative h-64">
                            <canvas id="pendingChart"></canvas>
                        </div>
                    </div>

                    <!-- Employee Status Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Employee Status Overview</h3>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-600">Last 6 Months</span>
                            </div>
                        </div>
                        <div class="relative h-64">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        <span class="text-sm text-gray-500">Latest approved requests</span>
                    </div>
                    
                    <!-- Search and Filter Section -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-6">
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input 
                                    x-model="searchTerm" 
                                    type="text" 
                                    placeholder="Search by employee name..." 
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm"
                                >
                            </div>
                        </div>
                        <div class="sm:w-48">
                            <select 
                                x-model="typeFilter" 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm"
                            >
                                <option value="all">All Types</option>
                                <option value="Leave Request">Leave Requests</option>
                                <option value="Overtime Request">Overtime</option>
                                <option value="Schedule Change">Schedule Changes</option>
                                <option value="Time Adjustment">Time Adjustments</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (!empty($recentActivity)): ?>
                        <div class="activity-scroll overflow-y-auto" style="height: 400px;">
                            <div class="space-y-3 pr-2">
                                <?php foreach ($recentActivity as $index => $activity): ?>
                                    <div 
                                        class="activity-item p-4 rounded-lg border border-gray-100"
                                        x-show="(searchTerm === '' || '<?= strtolower(htmlspecialchars($activity['employee_name'])) ?>'.includes(searchTerm.toLowerCase())) && (typeFilter === 'all' || typeFilter === '<?= $activity['type'] ?>')"
                                        x-transition.opacity
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div class="flex-shrink-0">
                                                    <?php
                                                    $iconClass = '';
                                                    $colorClass = '';
                                                    switch($activity['type']) {
                                                        case 'Leave Request':
                                                            $iconClass = 'fas fa-calendar-alt';
                                                            $colorClass = 'text-orange-600 bg-orange-50';
                                                            break;
                                                        case 'Overtime Request':
                                                            $iconClass = 'fas fa-clock';
                                                            $colorClass = 'text-purple-600 bg-purple-50';
                                                            break;
                                                        case 'Schedule Change':
                                                            $iconClass = 'fas fa-exchange-alt';
                                                            $colorClass = 'text-blue-600 bg-blue-50';
                                                            break;
                                                        case 'Time Adjustment':
                                                            $iconClass = 'fas fa-edit';
                                                            $colorClass = 'text-green-600 bg-green-50';
                                                            break;
                                                    }
                                                    ?>
                                                    <div class="w-10 h-10 rounded-lg <?= $colorClass ?> flex items-center justify-center">
                                                        <i class="<?= $iconClass ?> text-sm"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($activity['employee_name']) ?></p>
                                                        <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full font-medium">
                                                            <?= $activity['status'] ?>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center space-x-4 mt-1">
                                                        <p class="text-sm text-gray-600"><?= $activity['type'] ?></p>
                                                        <span class="text-gray-300">•</span>
                                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['details']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-700"><?= date('M j', strtotime($activity['date'])) ?></p>
                                                <p class="text-xs text-gray-400"><?= date('g:i A', strtotime($activity['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-inbox text-gray-400 text-xl"></i>
                            </div>
                            <p class="text-gray-500">No recent activity found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Chart data from PHP
        const chartData = <?= json_encode($chartData) ?>;
        
        // Pending Requests Doughnut Chart
        const pendingCtx = document.getElementById('pendingChart').getContext('2d');
        new Chart(pendingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Leave Requests', 'Schedule Changes', 'Overtime', 'Time Adjustments'],
                datasets: [{
                    data: [
                        chartData.pendingRequests.leaves,
                        chartData.pendingRequests.schedules,
                        chartData.pendingRequests.overtime,
                        chartData.pendingRequests.adjustments
                    ],
                    backgroundColor: [
                        '#F59E0B', // Orange
                        '#3B82F6', // Blue
                        '#8B5CF6', // Purple
                        '#10B981', // Green
                        
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Employee Status Line Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: chartData.monthlyActivity.map(item => item.month),
                datasets: [
                    {
                        label: 'Total Employees (Cumulative)',
                        data: chartData.monthlyActivity.map(item => item.total),
                        borderColor: '#3B82F6',
                        backgroundColor: '#3B82F620',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Active Employees',
                        data: chartData.monthlyActivity.map(item => item.active),
                        borderColor: '#10B981',
                        backgroundColor: '#10B98120',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Inactive Employees',
                        data: chartData.monthlyActivity.map(item => item.inactive),
                        borderColor: '#EF4444',
                        backgroundColor: '#EF444420',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#EF4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'New Hires',
                        data: chartData.monthlyActivity.map(item => item.newHires),
                        borderColor: '#8B5CF6',
                        backgroundColor: '#8B5CF620',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#8B5CF6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointStyle: 'triangle'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            afterLabel: function(context) {
                                if (context.datasetIndex === 0) {
                                    return 'Hired since company start';
                                } else if (context.datasetIndex === 3) {
                                    return 'New employees this month';
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Math.floor(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: '#F3F4F6'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>

</body>
</html>