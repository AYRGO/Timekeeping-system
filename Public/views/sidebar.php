<!-- Alpine.js for Hamburger -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<div x-data="{ open: true }" class="flex h-screen bg-gray-100 overflow-hidden">

    <!-- Sidebar -->
    <aside 
        :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
        class="fixed md:relative z-30 w-64 h-full bg-green-800 text-white shadow-lg transform transition-transform duration-300 ease-in-out md:translate-x-0"
    >
        <div class="p-6 flex flex-col h-full">

            <!-- Logo / Title -->
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <span class="bg-green-700 px-2 py-1 rounded mr-2">RSS</span> Admin Panel
            </h2>

            <nav class="space-y-6 flex-1 overflow-y-auto pr-2">

                <!-- Admin Panel -->
                <div>
                    <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Admin Panel</h3>
                    <a href="admin_homepage.php" class="flex items-center justify-between px-4 py-2 rounded hover:bg-green-700 transition">
                        <span>📊 Dashboard</span>
                        <span class="text-xs bg-green-500 text-white rounded-full px-2">8 New</span>
                    </a>
                </div>

                <hr class="border-green-700 my-2">

                <!-- Management -->
                <div>
                    <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Management</h3>
                    <a href="employee_list.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">👥 Employees</a>
                </div>

                <hr class="border-green-700 my-2">

                <!-- Pending Approvals -->
                <div>
                    <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">Pending Approvals</h3>
                    <a href="leave_request_list.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">📅 Leave Requests</a>
                    <a href="schedule_request.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">⏰ Schedule Changes</a>
                    <a href="ot_request.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">🕓 OT Requests</a>
                    <a href="time_adjustment_list.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">⏳ Time Adjustments</a>
                </div>

                <hr class="border-green-700 my-2">

                <!-- System -->
                <div>
                    <h3 class="text-xs uppercase text-green-300 tracking-wide px-4 mb-2">System</h3>
                    <a href="review_logs.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">📄 Review Logs</a>
                    <a href="announcement.php" class="block px-4 py-2 rounded hover:bg-green-700 transition">📢 Announcements</a>
                </div>

                <!-- Back to Employee -->
                <form method="POST" class="mt-6 px-4">
                    <button type="submit" name="switch_to_employee" class="w-full text-left px-4 py-2 rounded hover:bg-green-700 transition">
                        🔁 Back to Employee View
                    </button>
                </form>
            </nav>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col md:pl-64">

        <!-- Top Header -->
        <header class="bg-white shadow-md p-4 flex items-center justify-between md:justify-end">
            <!-- Hamburger button -->
            <button @click="open = !open" class="md:hidden text-green-800 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Page title if needed -->
            <h1 class="text-lg font-bold text-green-800 md:hidden">RSS HRIS</h1>
        </header>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Page content goes here -->
        </main>

    </div>
</div>
