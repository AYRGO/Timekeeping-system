<?php
// Ensure admin session exists
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="w-64 bg-gradient-to-b from-orange-600 to-red-700 text-white flex flex-col">
    <!-- Logo Section -->
    <div class="p-6 border-b border-orange-500">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mr-3">
                <i class="fas fa-building text-orange-600 text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold">Bugardi</h2>
                <p class="text-orange-200 text-sm">Admin Panel</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-2">
        <!-- Dashboard -->
        <a href="Admin_dashboard.php" 
           class="flex items-center px-4 py-3 rounded-lg transition-colors <?= ($current_page == 'Admin_dashboard.php') ? 'bg-orange-500 text-white' : 'text-orange-100 hover:bg-orange-600 hover:text-white' ?>">
            <i class="fas fa-tachometer-alt mr-3"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- Overtime Requests -->
        <div class="space-y-1">
            <div class="flex items-center px-4 py-2 text-orange-200 text-sm font-medium">
                <i class="fas fa-clock mr-3"></i>
                <span>Overtime Management</span>
            </div>
            <a href="Admin_dashboard.php?view=current" 
               class="flex items-center px-8 py-2 rounded-lg text-sm transition-colors <?= ($current_page == 'Admin_dashboard.php' && (!isset($_GET['view']) || $_GET['view'] == 'current')) ? 'bg-orange-400 text-white' : 'text-orange-100 hover:bg-orange-600 hover:text-white' ?>">
                <i class="fas fa-hourglass-half mr-3"></i>
                <span>Pending Requests</span>
            </a>
        </div>
        
        <!-- Employee Management -->
        <div class="space-y-1">
            <div class="flex items-center px-4 py-2 text-orange-200 text-sm font-medium">
                <i class="fas fa-users mr-3"></i>
                <span>Employee Management</span>
            </div>
            <a href="employee_list.php" 
               class="flex items-center px-8 py-2 rounded-lg text-sm transition-colors <?= ($current_page == 'employee_list.php') ? 'bg-orange-400 text-white' : 'text-orange-100 hover:bg-orange-600 hover:text-white' ?>">
                <i class="fas fa-list mr-3"></i>
                <span>Bugardi Employees</span>
            </a>
        </div>
        
        <!-- Time Management -->
        <div class="space-y-1">
            <div class="flex items-center px-4 py-2 text-orange-200 text-sm font-medium">
                <i class="fas fa-business-time mr-3"></i>
                <span>Time Management</span>
            </div>
            <a href="time_logs.php" 
               class="flex items-center px-8 py-2 rounded-lg text-sm transition-colors <?= ($current_page == 'time_logs.php') ? 'bg-orange-400 text-white' : 'text-orange-100 hover:bg-orange-600 hover:text-white' ?>">
                <i class="fas fa-clock mr-3"></i>
                <span>Time Logs</span>
            </a>
        </div>
        
        <!-- Reports -->
        <div class="space-y-1">
            <div class="flex items-center px-4 py-2 text-orange-200 text-sm font-medium">
                <i class="fas fa-chart-line mr-3"></i>
                <span>Reports</span>
            </div>
            <a href="reports.php" 
               class="flex items-center px-8 py-2 rounded-lg text-sm transition-colors <?= ($current_page == 'reports.php') ? 'bg-orange-400 text-white' : 'text-orange-100 hover:bg-orange-600 hover:text-white' ?>">
                <i class="fas fa-file-alt mr-3"></i>
                <span>Generate Reports</span>
            </a>
        </div>
    </nav>
    
    <!-- Bottom Section -->
    <div class="p-4 border-t border-orange-500">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-orange-400 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-user-shield text-white text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['admin']['username']) ?></p>
                    <p class="text-xs text-orange-200">Administrator</p>
                </div>
            </div>
        </div>
        
        <div class="mt-3 pt-3 border-t border-orange-500">
            <a href="../admin/logout.php" 
               class="flex items-center px-3 py-2 rounded-lg text-red-300 hover:bg-red-600 hover:text-white transition-colors text-sm">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>
