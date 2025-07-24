<!-- sidebar.php -->
<aside class="w-64 bg-white shadow-md">
    <div class="p-6">
        <h2 class="text-2xl font-bold text-gray-800">Admin Panel</h2>
        <nav class="mt-6">
            <ul>
                <li><a href="admin_homepage.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Dashboard</a></li>
                <li><a href="employee_list.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Employees</a></li>
                <li><a href="announcement.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Leave Requests</a></li>
                <li><a href="leave_request_list.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Leave Requests</a></li>
                <li><a href="schedule_request.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Schedule Requests</a></li>
                <li><a href="ot_request.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">OT Requests</a></li>
                <li><a href="time_adjustment_list.php" class="block py-2 px-4 text-gray-600 hover:bg-gray-200">Time Adjustments</a></li>
                <li><form method="POST" class="inline"><button type="submit" name="switch_to_employee" class="block w-full text-left py-2 px-4 text-gray-600 hover:bg-gray-200">Back to Employee View</button></form></li>
            </ul>
        </nav>
    </div>
</aside>
