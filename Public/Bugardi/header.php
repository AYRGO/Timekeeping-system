<?php
// Ensure admin session exists
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Get current page info
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = '';

switch($current_page) {
    case 'Admin_dashboard.php':
        if (isset($_GET['view']) && $_GET['view'] == 'history') {
            $page_title = 'Overtime Requests History';
        } else {
            $page_title = 'Overtime Requests Dashboard';
        }
        break;
    case 'employee_list.php':
        $page_title = 'Employee Management';
        break;
    case 'attendance_reports.php':
        $page_title = 'Attendance Reports';
        break;
    case 'time_logs.php':
        $page_title = 'Time Logs';
        break;
    case 'schedules.php':
        $page_title = 'Schedule Management';
        break;
    case 'reports.php':
        $page_title = 'Reports & Analytics';
        break;
    default:
        $page_title = 'Bugardi Admin Panel';
}
?>

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Page Title Section -->
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900"><?= $page_title ?></h1>
            <div class="ml-4 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                Bugardi Company
            </div>
        </div>
        
        <!-- Header Actions -->
        <div class="flex items-center space-x-4">
            <!-- Quick Stats -->
            <div class="hidden md:flex items-center space-x-6 text-sm">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-calendar-day mr-2 text-blue-600"></i>
                    <span><?= date('M d, Y') ?></span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-clock mr-2 text-green-600"></i>
                    <span id="current-time"><?= date('g:i A') ?></span>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="hidden md:block w-px h-6 bg-gray-300"></div>
            
            <!-- Admin Info -->
            <div class="flex items-center">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center mr-3">
                    <span class="text-white font-medium text-sm">
                        <?= strtoupper(substr($_SESSION['admin']['username'], 0, 1)) ?>
                    </span>
                </div>
                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['admin']['username']) ?></p>
                    <p class="text-xs text-gray-600">Admin - Bugardi</p>
                </div>
            </div>
            
            <!-- Quick Actions Dropdown -->
            <div class="relative">
                <button onclick="toggleDropdown()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                
                <div id="quick-actions-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="Admin_dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-tachometer-alt mr-3 text-blue-600"></i>
                        Dashboard
                    </a>
                    <a href="Admin_dashboard.php?view=current" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-hourglass-half mr-3 text-yellow-600"></i>
                        Pending Requests
                    </a>
                    <a href="Admin_dashboard.php?view=history" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-history mr-3 text-green-600"></i>
                        Request History
                    </a>
                    <div class="border-t border-gray-200 my-1"></div>
                    <a href="../admin/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Breadcrumb -->
    <div class="px-6 py-2 bg-gray-50 border-t border-gray-200">
        <nav class="flex items-center text-sm text-gray-600">
            <a href="Admin_dashboard.php" class="hover:text-blue-600">
                <i class="fas fa-home mr-1"></i>Home
            </a>
            <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
            <span class="text-gray-900 font-medium"><?= $page_title ?></span>
        </nav>
    </div>
</header>

<script>
// Update time every minute
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update time immediately and then every minute
updateTime();
setInterval(updateTime, 60000);

// Dropdown functionality
function toggleDropdown() {
    const dropdown = document.getElementById('quick-actions-dropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('quick-actions-dropdown');
    const button = event.target.closest('button');
    
    if (!dropdown.contains(event.target) && !button?.onclick?.toString().includes('toggleDropdown')) {
        dropdown.classList.add('hidden');
    }
});
</script>
