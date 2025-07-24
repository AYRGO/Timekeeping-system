<!-- Alpine.js for Hamburger -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Mobile Overlay -->
<div x-show="open" @click="open = false" class="fixed inset-0 z-20 bg-black bg-opacity-50 md:hidden" x-transition.opacity></div>

<!-- Sidebar -->
<aside 
    :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
    class="fixed md:relative z-30 w-64 h-full bg-green-800 text-white shadow-lg transform transition-transform duration-300 ease-in-out md:translate-x-0"
>
    <div class="p-6 flex flex-col h-full">

        <!-- Header with Logo and Close Button -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold flex items-center">
                <span class="bg-green-700 px-2 py-1 rounded mr-2">RSS</span> Admin Panel
            </h2>
            
            <!-- Close Button (X) for mobile -->
            <button @click="open = false" class="md:hidden text-white hover:text-gray-300 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="space-y-6 flex-1 overflow-y-auto pr-2">

            <!-- Admin Panel -->
            <div>
                <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Admin Panel</h3>
                <a href="admin_homepage.php" 
                   class="flex items-center px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'admin_homepage.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    <span>📊 Dashboard</span>
                </a>
            </div>

            <hr class="border-green-700 my-2">

            <!-- Management -->
            <div>
                <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Management</h3>
                <a href="employee_list.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'employee_list.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    👥 Employees
                </a>
            </div>

            <hr class="border-green-700 my-2">

            <!-- Pending Approvals -->
            <div>
                <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Pending Approvals</h3>
                <a href="leave_request_list.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'leave_request_list.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    📅 Leave Requests
                </a>
                <a href="schedule_request.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'schedule_request.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    ⏰ Schedule Changes
                </a>
                <a href="ot_request.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'ot_request.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    🕓 OT Requests
                </a>
                <a href="time_adjustment_list.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'time_adjustment_list.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    ⏳ Time Adjustments
                </a>
            </div>

            <hr class="border-green-700 my-2">

            <!-- System -->
            <div>
                <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">System</h3>
                <a href="review_logs.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'review_logs.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    📄 Review Logs
                </a>
                <a href="announcement.php" 
                   class="block px-4 py-2 rounded transition <?= (basename($_SERVER['PHP_SELF']) === 'announcement.php') ? 'bg-green-700 bg-opacity-60' : 'hover:bg-green-700' ?>">
                    📢 Announcements
                </a>
            </div>
        </nav>
    </div>
</aside>