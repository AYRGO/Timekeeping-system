<?php

$pageTitle = "Overtime Report";
// Secure session cookie params
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Check authorization - must be internal employee with admin view
if (
    !isset($_SESSION['employee']['id']) ||
    $_SESSION['employee']['role'] !== 'internal' ||
    $_SESSION['view_mode'] !== 'admin'
) {
    // Unauthorized, redirect to employee view
    header("Location: ../module/time_log_create.php");
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('../config/db.php');

// Get current month for default values
$currentMonth = date('Y-m');
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <title>Overtime Report</title>
    <style>
        .report-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .feature-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">

    <div x-data="{ open: false }" class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php 
            $pageTitle = "Overtime Report";
            include('header.php'); 
            ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Main Container -->
                <div class="max-w-6xl mx-auto">
                    
                    <!-- Page Header -->
                    <div class="report-card p-8 mb-8 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white bg-opacity-10 rounded-full"></div>
                        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white bg-opacity-10 rounded-full"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-3xl font-bold mb-2">
                                        <i class="fas fa-clock mr-3"></i>
                                        Overtime Report Generator
                                    </h1>
                                    <p class="text-lg opacity-90">
                                        Track and analyze overtime hours with detailed calculations and scheduling
                                    </p>
                                </div>
                                <div class="hidden lg:block">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-business-time text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Generator Form -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <!-- Form Header -->
                        <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold mb-2">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Generate Overtime Report
                                    </h2>
                                    <p class="text-orange-100">Track overtime hours and analyze extended work periods</p>
                                </div>
                                <div class="hidden sm:block">
                                    <i class="fas fa-file-excel text-3xl opacity-80"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-6">
                            <form action="../controller/generate_overtime_report.php" method="get" class="space-y-6">
                                
                                <!-- Date Range Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="fas fa-calendar-week text-orange-500 mr-2"></i>
                                            Date Range
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-calendar-plus text-green-500 mr-1"></i>
                                                    Start Date
                                                </label>
                                                <input type="date" 
                                                       name="start_date" 
                                                       id="start_date" 
                                                       value="<?= $startOfMonth ?>"
                                                       required 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" />
                                            </div>
                                            
                                            <div>
                                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-calendar-minus text-red-500 mr-1"></i>
                                                    End Date
                                                </label>
                                                <input type="date" 
                                                       name="end_date" 
                                                       id="end_date" 
                                                       value="<?= $endOfMonth ?>"
                                                       required 
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filter Section -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="fas fa-filter text-purple-500 mr-2"></i>
                                            Filters (Optional)
                                        </h3>
                                        
                                        <div>
                                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fas fa-search text-gray-500 mr-1"></i>
                                                Search Employee
                                            </label>
                                            <input type="text" 
                                                   name="search" 
                                                   id="search" 
                                                   placeholder="Search by name or company..."
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" />
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-sort text-indigo-500 mr-1"></i>
                                                    Sort By
                                                </label>
                                                <select name="sort" 
                                                        id="sort" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                                    <option value="log_date">Date</option>
                                                    <option value="fname">Employee Name</option>
                                                    <option value="company">Company</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-sort-amount-down text-red-500 mr-1"></i>
                                                    Order
                                                </label>
                                                <select name="order" 
                                                        id="order" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                                    <option value="asc">Ascending</option>
                                                    <option value="desc">Descending</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overtime Threshold Info -->
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-orange-400 text-lg"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-orange-800">Overtime Calculation</h4>
                                            <p class="text-sm text-orange-700 mt-1">
                                                Overtime is calculated for work extending beyond the scheduled end time + 30 minutes grace period. 
                                                Only records with overtime will be included in the report.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Date Presets -->
                                <div class="border-t border-gray-200 pt-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-4 flex items-center">
                                        <i class="fas fa-clock text-orange-500 mr-2"></i>
                                        Quick Presets
                                    </h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <button type="button" onclick="setDateRange('current_month')" 
                                                class="px-4 py-2 bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            Current Month
                                        </button>
                                        <button type="button" onclick="setDateRange('last_month')" 
                                                class="px-4 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-backward mr-1"></i>
                                            Last Month
                                        </button>
                                        <button type="button" onclick="setDateRange('last_30_days')" 
                                                class="px-4 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar-week mr-1"></i>
                                            Last 30 Days
                                        </button>
                                        <button type="button" onclick="setDateRange('current_year')" 
                                                class="px-4 py-2 bg-pink-50 text-pink-700 rounded-lg hover:bg-pink-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Current Year
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="border-t border-gray-200 pt-6">
                                    <div class="flex justify-center">
                                        <button type="submit" 
                                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white font-semibold rounded-xl shadow-lg hover:from-orange-600 hover:to-red-600 focus:outline-none focus:ring-4 focus:ring-orange-300 transition-all duration-200 transform hover:scale-105">
                                            <i class="fas fa-download mr-3 text-lg"></i>
                                            <span class="text-lg">Generate Overtime Report</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Information Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="feature-card bg-white rounded-xl p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-stopwatch text-orange-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Precise Calculation</h3>
                            </div>
                            <p class="text-gray-600">
                                Accurately calculates overtime based on actual scheduled end times with 30-minute grace periods for fair tracking.
                            </p>
                        </div>

                        <div class="feature-card bg-white rounded-xl p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Overtime Alerts</h3>
                            </div>
                            <p class="text-gray-600">
                                Highlights excessive overtime (>2 hours) to help identify potential workload issues and manage employee wellness.
                            </p>
                        </div>

                        <div class="feature-card bg-white rounded-xl p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-bar text-yellow-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Summary Statistics</h3>
                            </div>
                            <p class="text-gray-600">
                                Provides total overtime hours and detailed breakdowns for comprehensive workforce analytics and cost management.
                            </p>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function setDateRange(preset) {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const today = new Date();
            
            switch(preset) {
                case 'current_month':
                    startDate.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    endDate.value = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                    break;
                    
                case 'last_month':
                    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDate.value = lastMonth.toISOString().split('T')[0];
                    endDate.value = lastMonthEnd.toISOString().split('T')[0];
                    break;
                    
                case 'last_30_days':
                    const thirtyDaysAgo = new Date(today);
                    thirtyDaysAgo.setDate(today.getDate() - 30);
                    startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
                    endDate.value = today.toISOString().split('T')[0];
                    break;
                    
                case 'current_year':
                    startDate.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                    endDate.value = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
                    break;
            }
        }
    </script>

</body>
</html>