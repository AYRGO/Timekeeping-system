<?php

$pageTitle = "Payroll Report";
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
require_once __DIR__ . '/../config/demo_guard.php';

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
    <title>Payroll Report</title>

</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">

    <div x-data="{ open: false }" class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php 
            $pageTitle = "Payroll Report";
            include('header.php'); 
            ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Main Container -->
                <div class="max-w-5xl mx-auto">
                    
                    <!-- Hero Card -->
                    <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden">
                        <!-- Header Section with Icon -->
                        <div class="bg-blue-600 px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-14 h-14 bg-blue-600 rounded-xl">
                                    <i class="fas fa-dollar-sign text-2xl text-white"></i>
                                </div>
                                <div>
                                    <p class="text-white/90 text-sm font-medium">Generate Report</p>
                                    <h1 class="text-2xl font-bold text-white">Payroll Tracking</h1>
                                </div>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-8">
                            <form action="../controller/generate_payroll_report.php" method="get" class="space-y-8">
                                
                                <!-- Date Selection Cards -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Start Date Card -->
                                    <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200 hover:border-blue-400 transition-all">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-calendar-plus text-white"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">From</p>
                                                <h3 class="text-sm font-bold text-gray-800">Start Date</h3>
                                            </div>
                                        </div>
                                        <input type="date" name="start_date" id="start_date" value="<?= $startOfMonth ?>" required class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                                    </div>

                                    <!-- End Date Card -->
                                    <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200 hover:border-cyan-400 transition-all">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 bg-cyan-500 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-calendar-check text-white"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs text-cyan-600 font-medium uppercase tracking-wide">To</p>
                                                <h3 class="text-sm font-bold text-gray-800">End Date</h3>
                                            </div>
                                        </div>
                                        <input type="date" name="end_date" id="end_date" value="<?= $endOfMonth ?>" required class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all" />
                                    </div>
                                </div>

                                <!-- Quick Presets Section -->
                                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="fas fa-bolt text-yellow-500"></i>
                                        <h3 class="text-sm font-semibold text-gray-700">Quick Select</h3>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <button type="button" onclick="setDateRange('current_month')" class="px-6 py-2.5 bg-white border-2 border-blue-200 text-blue-700 rounded-full text-sm font-semibold hover:bg-blue-500 hover:text-white hover:border-blue-500 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-calendar-day mr-2"></i>This Month
                                        </button>
                                        <button type="button" onclick="setDateRange('last_month')" class="px-6 py-2.5 bg-white border-2 border-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-600 hover:text-white hover:border-gray-600 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-history mr-2"></i>Last Month
                                        </button>
                                        <button type="button" onclick="setDateRange('last_30_days')" class="px-6 py-2.5 bg-white border-2 border-cyan-200 text-cyan-700 rounded-full text-sm font-semibold hover:bg-cyan-500 hover:text-white hover:border-cyan-500 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-calendar-week mr-2"></i>Last 30 Days
                                        </button>
                                    </div>
                                </div>

                                <!-- Generate Button -->
                                <div class="relative">
                                    <div class="absolute inset-0 bg-blue-500 rounded-2xl blur opacity-30"></div>
                                    <button type="submit" class="relative w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-8 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] group">
                                        <span class="flex items-center justify-center gap-3">
                                            <i class="fas fa-download text-xl group-hover:animate-bounce"></i>
                                            <span class="text-lg">Generate Report</span>
                                            <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                                        </span>
                                    </button>
                                </div>
                            </form>
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
