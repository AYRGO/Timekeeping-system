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
require_once __DIR__ . '/../config/demo_guard.php';

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
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">

    <div x-data="{ open: false }" class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php 
            $pageTitle = "Leave Report";
            include('header.php'); 
            ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Main Container -->
                <div class="max-w-5xl mx-auto">
                    
                    <!-- Hero Card -->
                    <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden">
                        <!-- Header Section with Icon -->
                        <div class="px-8 py-6" style="background-color: #7c3aed;">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-14 h-14 rounded-xl" style="background-color: #7c3aed;">
                                    <i class="fas fa-calendar-alt text-2xl" style="color: white;"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium" style="color: rgba(255, 255, 255, 0.9);">Generate Report</p>
                                    <h1 class="text-2xl font-bold" style="color: white;">Leave Tracking</h1>
                                </div>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-8">
                            <form action="../controller/generate_leave_report.php" method="get" class="space-y-8">
                                
                                <!-- Date Range Selection -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Start Date -->
                                    <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200 hover:border-violet-400 transition-all">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 bg-violet-500 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-calendar-alt text-white"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs text-violet-600 font-medium uppercase tracking-wide">From</p>
                                                <h3 class="text-sm font-bold text-gray-800">Start Date</h3>
                                            </div>
                                        </div>
                                        <input type="date" name="start_date" id="start_date" 
                                               value="<?= date('Y-m-01') ?>" 
                                               class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                                    </div>
                                    
                                    <!-- End Date -->
                                    <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200 hover:border-violet-400 transition-all">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 bg-violet-500 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-calendar-check text-white"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs text-violet-600 font-medium uppercase tracking-wide">To</p>
                                                <h3 class="text-sm font-bold text-gray-800">End Date</h3>
                                            </div>
                                        </div>
                                        <input type="date" name="end_date" id="end_date" 
                                               value="<?= date('Y-m-t') ?>" 
                                               class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                                    </div>
                                </div>

                                <!-- Quick Presets Section -->
                                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                    <div class="flex items-center gap-2 mb-4">
                                        <i class="fas fa-bolt text-yellow-500"></i>
                                        <h3 class="text-sm font-semibold text-gray-700">Quick Select</h3>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <button type="button" onclick="setDateRange('current_month')" class="px-6 py-2.5 bg-white border-2 border-violet-200 text-violet-700 rounded-full text-sm font-semibold hover:bg-violet-500 hover:text-white hover:border-violet-500 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-calendar-day mr-2"></i>Current Month
                                        </button>
                                        <button type="button" onclick="setDateRange('previous_month')" class="px-6 py-2.5 bg-white border-2 border-gray-200 text-gray-700 rounded-full text-sm font-semibold hover:bg-gray-600 hover:text-white hover:border-gray-600 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-history mr-2"></i>Previous Month
                                        </button>
                                        <button type="button" onclick="setDateRange('current_year')" class="px-6 py-2.5 bg-white border-2 border-purple-200 text-purple-700 rounded-full text-sm font-semibold hover:bg-purple-500 hover:text-white hover:border-purple-500 transition-all hover:scale-105 shadow-sm">
                                            <i class="fas fa-calendar-alt mr-2"></i>Current Year
                                        </button>
                                    </div>
                                </div>

                                <!-- Generate Button -->
                                <div class="relative">
                                    <div class="absolute inset-0 rounded-2xl blur opacity-30" style="background-color: #8b5cf6;"></div>
                                    <button type="submit" class="relative w-full font-bold py-5 px-8 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02] group" style="background-color: #7c3aed; color: white;" onmouseover="this.style.backgroundColor='#6d28d9'" onmouseout="this.style.backgroundColor='#7c3aed'">
                                        <span class="flex items-center justify-center gap-3">
                                            <i class="fas fa-download text-xl group-hover:animate-bounce" style="color: white;"></i>
                                            <span class="text-lg" style="color: white;">Generate Report</span>
                                            <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform" style="color: white;"></i>
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
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const now = new Date();
            
            switch(preset) {
                case 'current_month':
                    const currentMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);
                    const currentMonthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    startDateInput.value = currentMonthStart.toISOString().split('T')[0];
                    endDateInput.value = currentMonthEnd.toISOString().split('T')[0];
                    break;
                    
                case 'previous_month':
                    const prevMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const prevMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
                    startDateInput.value = prevMonthStart.toISOString().split('T')[0];
                    endDateInput.value = prevMonthEnd.toISOString().split('T')[0];
                    break;
                    
                case 'current_year':
                    const yearStart = new Date(now.getFullYear(), 0, 1);
                    const yearEnd = new Date(now.getFullYear(), 11, 31);
                    startDateInput.value = yearStart.toISOString().split('T')[0];
                    endDateInput.value = yearEnd.toISOString().split('T')[0];
                    break;
            }
        }
    </script>

</body>
</html>
