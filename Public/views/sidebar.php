<?php

// Include database connection if not already included
if (!isset($pdo)) {
    include('../config/db.php');
}

// Fetch pending counts for each request type
try {
    // Leave Requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $stmt->execute();
    $pendingLeaveCount = $stmt->fetchColumn();

    // Schedule Change Requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedule_change_requests WHERE status = 'pending'");
    $stmt->execute();
    $pendingScheduleCount = $stmt->fetchColumn();

    // Overtime Requests - Updated to use post_ot_requests table and only count pending
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_ot_requests WHERE status = 'Pending'");
    $stmt->execute();
    $pendingOvertimeCount = $stmt->fetchColumn();

    // Time Adjustment Requests
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM time_adjustment_requests WHERE status = 'pending'");
    $stmt->execute();
    $pendingTimeAdjustmentCount = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Handle errors gracefully
    $pendingLeaveCount = 0;
    $pendingScheduleCount = 0;
    $pendingOvertimeCount = 0;
    $pendingTimeAdjustmentCount = 0;
}
?>

<!-- Alpine.js for Hamburger -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Mobile Overlay -->
<div x-show="open" @click="open = false" class="fixed inset-0 z-20 bg-black bg-opacity-50 md:hidden" x-transition.opacity></div>

<!-- Sidebar -->
<aside 
    :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
    class="fixed md:relative z-30 w-72 h-full bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white shadow-2xl transform transition-transform duration-300 ease-in-out md:translate-x-0"
>
    <div class="flex flex-col h-full">

        <!-- Header without Logo -->
        <div class="px-6 py-8 border-b border-gray-700">
            <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-white">RSS Admin</h1>
                <p class="text-xs text-gray-400">Management Panel</p>
            </div>
            
            <!-- Close Button for mobile -->
            <button @click="open = false" class="md:hidden text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">

            <!-- Dashboard Section -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase text-gray-400 tracking-wider font-semibold px-3 mb-3">Dashboard</h3>
                <a href="admin_homepage.php" 
                   class="group flex items-center px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'admin_homepage.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="w-8 h-8 rounded-lg bg-blue-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                        <i class="fas fa-chart-line text-blue-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Dashboard</span>
                </a>
            </div>

            <!-- Management Section -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase text-gray-400 tracking-wider font-semibold px-3 mb-3">Management</h3>
<a href="employee_list.php" 
   class="group flex items-center px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'employee_list.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
    <div class="w-8 h-8 rounded-lg bg-transparent flex items-center justify-center mr-3 group-hover:bg-gray-700 group-hover:bg-opacity-30 transition-all">
        <i class="fas fa-users text-gray-400 text-sm"></i>
    </div>
    <span class="font-medium">Employee Directory</span>
</a>
            </div>

            <!-- Pending Approvals Section -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase text-gray-400 tracking-wider font-semibold px-3 mb-3">Pending Approvals</h3>
                
                <a href="leave_request_list.php" 
                   class="group flex items-center justify-between px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'leave_request_list.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-orange-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                            <i class="fas fa-calendar-alt text-orange-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Leave Requests</span>
                    </div>
                    <?php if ($pendingLeaveCount > 0): ?>
                        <div class="bg-red-500 text-white text-xs rounded-full px-2.5 py-1 font-bold shadow-lg animate-pulse"><?= $pendingLeaveCount ?></div>
                    <?php endif; ?>
                </a>

                <a href="schedule_request.php" 
                   class="group flex items-center justify-between px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'schedule_request.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                            <i class="fas fa-clock text-indigo-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Schedule Changes</span>
                    </div>
                    <?php if ($pendingScheduleCount > 0): ?>
                        <div class="bg-red-500 text-white text-xs rounded-full px-2.5 py-1 font-bold shadow-lg animate-pulse"><?= $pendingScheduleCount ?></div>
                    <?php endif; ?>
                </a>

                <a href="ot_request.php" 
                   class="group flex items-center justify-between px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'ot_request.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-yellow-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                            <i class="fas fa-business-time text-yellow-400 text-sm"></i>
                        </div>
                        <span class="font-medium">OT Requests</span>
                    </div>
                    <?php if ($pendingOvertimeCount > 0): ?>
                        <div class="bg-red-500 text-white text-xs rounded-full px-2.5 py-1 font-bold shadow-lg animate-pulse"><?= $pendingOvertimeCount ?></div>
                    <?php endif; ?>
                </a>

                <a href="time_adjustment_list.php" 
                   class="group flex items-center justify-between px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'time_adjustment_list.php') ? 'bg-white bg-opacity-5 border border-gray-700' : 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-pink-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                            <i class="fas fa-edit text-pink-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Time Adjustments</span>
                    </div>
                    <?php if ($pendingTimeAdjustmentCount > 0): ?>
                        <div class="bg-red-500 text-white text-xs rounded-full px-2.5 py-1 font-bold shadow-lg animate-pulse"><?= $pendingTimeAdjustmentCount ?></div>
                    <?php endif; ?>
                </a>
            </div>

            <!-- System Section -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase text-gray-400 tracking-wider font-semibold px-3 mb-3">System</h3>

                <a href="announcement.php" 
                   class="group flex items-center px-3 py-3 rounded-xl transition-all duration-200 <?= (basename($_SERVER['PHP_SELF']) === 'announcement.php') ? 'bg-white bg-opacity-5 border border-gray-700': 'hover:bg-gray-800 hover:shadow-md' ?>">
                    <div class="w-8 h-8 rounded-lg bg-red-500 bg-opacity-20 flex items-center justify-center mr-3 group-hover:bg-opacity-30 transition-all">
                        <i class="fas fa-bullhorn text-red-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Announcements</span>
                </a>
            </div>
        </nav>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-700">
            <div class="text-center">
                <p class="text-xs text-gray-400">© 2025 RSS Admin Panel</p>
                <p class="text-xs text-gray-500 mt-1">v2.0.1</p>
            </div>
        </div>
    </div>
</aside>

<style>
/* Custom scrollbar */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: #374151;
    border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #6B7280;
    border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #9CA3AF;
}
</style>