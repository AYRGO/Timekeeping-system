<?php

$pageTitle = "Leave Report";
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

// Get current year for default values
$currentYear = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <title>Leave Report</title>
    <style>
        .report-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
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
            $pageTitle = "Leave Report";
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
                                        <i class="fas fa-calendar-times mr-3"></i>
                                        Leave Report Generator
                                    </h1>
                                    <p class="text-lg opacity-90">
                                        Track and analyze employee leave balances, carry-overs, and monthly increments
                                    </p>
                                </div>
                                <div class="hidden lg:block">
                                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-clock text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Generator Form -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        <!-- Form Header -->
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-500 p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold mb-2">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Generate Leave Report
                                    </h2>
                                    <p class="text-purple-100">Track leave balances and analyze leave credit management</p>
                                </div>
                                <div class="hidden sm:block">
                                    <i class="fas fa-file-excel text-3xl opacity-80"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-6">
                            <form action="../controller/generate_leave_report.php" method="get" class="space-y-6">
                                
                                <!-- Filter Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="fas fa-filter text-purple-500 mr-2"></i>
                                            Report Filters
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-calendar text-blue-500 mr-1"></i>
                                                    Year
                                                </label>
                                                <select name="year" 
                                                        id="year" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                                    <?php for ($y = 2020; $y <= date('Y') + 1; $y++): ?>
                                                        <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label for="company_filter" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-building text-green-500 mr-1"></i>
                                                    Company Filter
                                                </label>
                                                <select name="company_filter" 
                                                        id="company_filter" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                                    <option value="">All Companies</option>
                                                    <!-- Companies will be populated dynamically -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Employee Filter Section -->
                                    <div class="space-y-4">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="fas fa-users text-indigo-500 mr-2"></i>
                                            Employee Filters (Optional)
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
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-sort text-indigo-500 mr-1"></i>
                                                    Sort By
                                                </label>
                                                <select name="sort" 
                                                        id="sort" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                                    <option value="employee_name">Employee Name</option>
                                                    <option value="updated_at">Last Updated</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                                                    <i class="fas fa-sort-amount-down text-red-500 mr-1"></i>
                                                    Order
                                                </label>
                                                <select name="order" 
                                                        id="order" 
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                                                    <option value="asc">Ascending</option>
                                                    <option value="desc">Descending</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Leave Balance Info -->
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-purple-400 text-lg"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-purple-800">Leave Credit Information</h4>
                                            <p class="text-sm text-purple-700 mt-1">
                                                This report shows employee leave balances, carry-over amounts, and monthly increments. 
                                                Leave credits are updated monthly and carry-over rules apply based on company policy.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Year Presets -->
                                <div class="border-t border-gray-200 pt-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-4 flex items-center">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i>
                                        Quick Year Selection
                                    </h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <button type="button" onclick="setYear('current')" 
                                                class="px-4 py-2 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            Current Year
                                        </button>
                                        <button type="button" onclick="setYear('previous')" 
                                                class="px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-backward mr-1"></i>
                                            Previous Year
                                        </button>
                                        <button type="button" onclick="setYear('2023')" 
                                                class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar mr-1"></i>
                                            2023
                                        </button>
                                        <button type="button" onclick="setYear('2022')" 
                                                class="px-4 py-2 bg-pink-50 text-pink-700 rounded-lg hover:bg-pink-100 transition-colors text-sm font-medium">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            2022
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="border-t border-gray-200 pt-6">
                                    <div class="flex justify-center">
                                        <button type="submit" 
                                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-xl shadow-lg hover:from-purple-600 hover:to-indigo-600 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-200 transform hover:scale-105">
                                            <i class="fas fa-download mr-3 text-lg"></i>
                                            <span class="text-lg">Generate Leave Report</span>
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
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-balance-scale text-purple-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Leave Balances</h3>
                            </div>
                            <p class="text-gray-600">
                                Track current leave balances for all employees across different leave types with real-time updates.
                            </p>
                        </div>

                        <div class="feature-card bg-white rounded-xl p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-exchange-alt text-indigo-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Carry-Over Analysis</h3>
                            </div>
                            <p class="text-gray-600">
                                Analyze leave carry-overs from previous periods and track how credits are transferred between years.
                            </p>
                        </div>

                        <div class="feature-card bg-white rounded-xl p-6 shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Monthly Increments</h3>
                            </div>
                            <p class="text-gray-600">
                                Monitor monthly leave credit increments and ensure proper accrual calculations for payroll processing.
                            </p>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script>
        function setYear(preset) {
            const yearSelect = document.getElementById('year');
            const currentYear = new Date().getFullYear();
            
            switch(preset) {
                case 'current':
                    yearSelect.value = currentYear;
                    break;
                    
                case 'previous':
                    yearSelect.value = currentYear - 1;
                    break;
                    
                default:
                    if (!isNaN(preset)) {
                        yearSelect.value = preset;
                    }
                    break;
            }
        }
    </script>

</body>
</html>