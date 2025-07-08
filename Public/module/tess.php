<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS HR Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            width: 250px;
            transition: all 0.3s;
        }
        .content {
            margin-left: 250px;
            transition: all 0.3s;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed + .content {
            margin-left: 80px;
        }
        .form-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar bg-blue-800 text-white h-screen fixed shadow-lg">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="p-4 flex items-center justify-between border-b border-blue-700">
                <div class="flex items-center">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/43f72e29-b379-4347-be91-916255464a72.png" alt="RSS HR System logo with leaf sprout and circular border" class="rounded-full border-2 border-white" />
                    <span class="ml-3 text-xl font-bold" id="company-name">RSS HR</span>
                </div>
                <button id="toggle-sidebar" class="text-white focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <ul>
                    <li>
                        <a href="#" class="block py-3 px-4 hover:bg-blue-700 active-tab" data-tab="dashboard">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-3 px-4 hover:bg-blue-700" data-tab="leave">
                            <i class="fas fa-calendar-minus mr-3"></i>
                            <span class="nav-text">Leave Request</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-3 px-4 hover:bg-blue-700" data-tab="overtime">
                            <i class="fas fa-business-time mr-3"></i>
                            <span class="nav-text">Overtime</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-3 px-4 hover:bg-blue-700" data-tab="schedule">
                            <i class="fas fa-calendar-check mr-3"></i>
                            <span class="nav-text">Schedule Request</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="block py-3 px-4 hover:bg-blue-700" data-tab="attendance">
                            <i class="fas fa-user-clock mr-3"></i>
                            <span class="nav-text">Attendance</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- User Profile -->
            <div class="p-4 border-t border-blue-700">
                <div class="flex items-center">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/1cd10c1c-e005-43d3-9a02-249d3f255686.png" alt="User profile photo of HR manager with professional appearance" class="rounded-full border-2 border-white" />
                    <div class="ml-3 text-sm">
                        <div class="font-semibold" id="username">John Doe</div>
                        <div class="text-blue-200">HR Manager</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="content" class="content flex-1">
        <header class="bg-white shadow-sm p-4 border-b">
            <h1 class="text-2xl font-bold text-gray-800">HR Dashboard</h1>
            <div class="flex justify-between items-center mt-2">
                <p class="text-gray-600">Welcome back! Here's what's happening today.</p>
                <div class="flex items-center">
                    <div class="relative mr-4">
                        <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <button class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none" id="notifications">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6">
            <!-- Dashboard Tab -->
            <div id="dashboard-tab" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Stats Cards -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-600 text-sm">Pending Leaves</h3>
                                <p class="text-2xl font-bold">12</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-business-time text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-600 text-sm">OT Requests</h3>
                                <p class="text-2xl font-bold">8</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-calendar-alt text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-600 text-sm">Schedule Changes</h3>
                                <p class="text-2xl font-bold">5</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                <i class="fas fa-user-times text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-600 text-sm">Absences Today</h3>
                                <p class="text-2xl font-bold">3</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Leave Calendar -->
                    <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Leave Calendar</h3>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Add Leave
                            </button>
                        </div>
                        <div id="calendar" class="h-64">
                            <!-- Calendar will be implemented with JavaScript -->
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/905a4f72-dc43-47ae-9439-58f3341d7699.png" alt="Interactive calendar showing current month with leave days marked in different colors for different leave types" />
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activities</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Jennifer approved</p>
                                    <p class="text-sm text-gray-500">Vacation leave for Mark</p>
                                    <p class="text-xs text-gray-400">10 mins ago</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">OT request submitted</p>
                                    <p class="text-sm text-gray-500">From Sarah (4 hours)</p>
                                    <p class="text-xs text-gray-400">25 mins ago</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Shift change request</p>
                                    <p class="text-sm text-gray-500">From Alex (Morning to Afternoon)</p>
                                    <p class="text-xs text-gray-400">2 hours ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pending Approvals</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Michael Brown</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Vacation - 3 days</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">15-17 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-green-600 hover:text-green-900 mr-2">Approve</button>
                                        <button class="text-red-600 hover:text-red-900">Deny</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Overtime</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Lisa Ray</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Weekend OT - 8 hours</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">09 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-green-600 hover:text-green-900 mr-2">Approve</button>
                                        <button class="text-red-600 hover:text-red-900">Deny</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Schedule</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Robert Chen</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Shift change request</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Starting 12 Sep</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-green-600 hover:text-green-900 mr-2">Approve</button>
                                        <button class="text-red-600 hover:text-red-900">Deny</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Leave Request Tab (Hidden by default) -->
            <div id="leave-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6 form-card lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">New Leave Request</h3>
                        <form id="leave-form">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="leave-type">Leave Type</label>
                                <select id="leave-type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="vacation">Vacation Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="personal">Personal Leave</option>
                                    <option value="bereavement">Bereavement Leave</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="start-date">Start Date</label>
                                <input type="date" id="start-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="end-date">End Date</label>
                                <input type="date" id="end-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="leave-reason">Reason</label>
                                <textarea id="leave-reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" class="form-checkbox" checked>
                                    <span class="ml-2 text-sm text-gray-600">Notify manager</span>
                                </label>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Submit Request
                            </button>
                        </form>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">My Leave Balances</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="border rounded-lg p-4 text-center">
                                    <h4 class="text-sm text-gray-600">Vacation</h4>
                                    <p class="text-2xl font-bold text-blue-600">15/20</p>
                                    <p class="text-xs text-gray-500">Days remaining</p>
                                </div>
                                <div class="border rounded-lg p-4 text-center">
                                    <h4 class="text-sm text-gray-600">Sick</h4>
                                    <p class="text-2xl font-bold text-green-600">10/10</p>
                                    <p class="text-xs text-gray-500">Days remaining</p>
                                </div>
                                <div class="border rounded-lg p-4 text-center">
                                    <h4 class="text-sm text-gray-600">Personal</h4>
                                    <p class="text-2xl font-bold text-purple-600">5/5</p>
                                    <p class="text-xs text-gray-500">Days remaining</p>
                                </div>
                                <div class="border rounded-lg p-4 text-center">
                                    <h4 class="text-sm text-gray-600">Bereavement</h4>
                                    <p class="text-2xl font-bold text-gray-600">3/3</p>
                                    <p class="text-xs text-gray-500">Days remaining</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">My Leave History</h3>
                                <div class="flex space-x-2">
                                    <select class="border rounded-md px-3 py-1 text-sm">
                                        <option>Last 3 months</option>
                                        <option>Last 6 months</option>
                                        <option>This year</option>
                                        <option>All records</option>
                                    </select>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Vacation</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">15-17 Jul 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Sick</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">22 May 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Personal</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10-11 Apr 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Vacation</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Pending: 10-15 Sep 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">6</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                                <button class="text-red-600 hover:text-red-900 ml-2">Cancel</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overtime Tab (Hidden by default) -->
            <div id="overtime-tab" class="tab-content hidden">

            
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6 form-card lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">New Overtime Request</h3>
                        <form id="overtime-form">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="ot-date">Date</label>
                                <input type="date" id="ot-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="ot-start">Start Time</label>
                                <input type="time" id="ot-start" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="ot-end">End Time</label>
                                <input type="time" id="ot-end" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="ot-type">OT Type</label>
                                <select id="ot-type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="regular">Regular OT</option>
                                    <option value="weekend">Weekend OT</option>
                                    <option value="holiday">Holiday OT</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="ot-reason">Reason</label>
                                <textarea id="ot-reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Submit Request
                            </button>
                        </form>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">My Overtime History</h3>
                                <div class="flex space-x-2">
                                    <select class="border rounded-md px-3 py-1 text-sm">
                                        <option>Last 3 months</option>
                                        <option>Last 6 months</option>
                                        <option>This year</option>
                                        <option>All records</option>
                                    </select>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">20 Aug 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">4 hrs</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Regular</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$120.00</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">12 Aug 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8 hrs</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Weekend</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$240.00</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">05 Jul 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 hrs</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Regular</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$60.00</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Pending: 09 Sep 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8 hrs</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Weekend</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                                <button class="text-red-600 hover:text-red-900 ml-2">Cancel</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Request Tab (Hidden by default) -->
            <div id="schedule-tab" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6 form-card lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">New Schedule Request</h3>
                        <form id="schedule-form">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="schedule-type">Request Type</label>
                                <select id="schedule-type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="shift-change">Shift Change</option>
                                    <option value="swap">Shift Swap</option>
                                    <option value="day-off">Additional Day Off</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="effective-date">Effective Date</label>
                                <input type="date" id="effective-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-4 schedule-options">
                                <div id="shift-change-fields">
                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new-shift">New Shift</label>
                                        <select id="new-shift" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="morning">Morning (8AM-4PM)</option>
                                            <option value="afternoon">Afternoon (4PM-12AM)</option>
                                            <option value="night">Night (12AM-8AM)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="schedule-reason">Reason</label>
                                        <textarea id="schedule-reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                                    </div>
                                </div>
                                <div id="swap-fields" class="hidden">
                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="swap-date">Swap Date</label>
                                        <input type="date" id="swap-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="swap-with">Swap With</label>
                                        <select id="swap-with" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="">Select employee</option>
                                            <option value="john">John Doe</option>
                                            <option value="lisa">Lisa Ray</option>
                                            <option value="michael">Michael Brown</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="swap-reason">Reason</label>
                                        <textarea id="swap-reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                                    </div>
                                </div>
                                <div id="dayoff-fields" class="hidden">
                                    <div class="mb-3">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="dayoff-date">Day Off Date</label>
                                        <input type="date" id="dayoff-date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="dayoff-reason">Reason</label>
                                        <textarea id="dayoff-reason" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Submit Request
                            </button>
                        </form>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">My Current Schedule</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Morning</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8:00 AM - 4:00 PM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Office</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Tuesday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Morning</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8:00 AM - 4:00 PM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Office</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Wednesday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Morning</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8:00 AM - 4:00 PM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Office</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Thursday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Morning</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8:00 AM - 4:00 PM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Office</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Friday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Morning</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8:00 AM - 4:00 PM</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Office</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Saturday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">OFF</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Sunday</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">OFF</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">My Schedule Requests</h3>
                                <div class="flex space-x-2">
                                    <select class="border rounded-md px-3 py-1 text-sm">
                                        <option>Last 3 months</option>
                                        <option>Last 6 months</option>
                                        <option>This year</option>
                                        <option>All records</option>
                                    </select>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effective Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Shift Change</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">To Afternoon (4PM-12AM)</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">25 Jul 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10 Sep 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-500 font-medium">Pending</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                                <button class="text-red-600 hover:text-red-900 ml-2">Cancel</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Shift Swap</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">With Robert Chen (15 Aug)</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05 Jul 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">15 Aug 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Day Off</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Personal business</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10 Jun 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">22 Jun 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-500 font-medium">Approved</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-blue-600 hover:text-blue-900">View</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Tab (Hidden by default) -->
            <div id="attendance-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Time In/Out</h3>
                    <div class="flex justify-center items-center">
                        <div class="text-center p-6 rounded-lg border border-gray-200 w-full max-w-md">
                            <div class="text-gray-600 mb-4">Today is <span id="current-date">September 5, 2023</span></div>
                            <div class="text-2xl font-bold mb-4" id="current-time">09:15 AM</div>
                            <div class="mb-6">
                                <div id="status-indicator" class="inline-block px-3 py-1 rounded-full bg-gray-200 text-gray-800 text-sm font-medium">Not Logged In</div>
                            </div>
                            <button id="time-in-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline mb-2">
                                Time In
                            </button>
                            <button id="time-out-btn" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline" disabled>
                                Time Out
                            </button>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Today's Attendance Logs</h3>
                        <div>
                            <div class="flex items-center">
                                <div class="mr-4">
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-1"></span>
                                    <span class="text-sm text-gray-600">On Time</span>
                                </div>
                                <div class="mr-4">
                                    <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 mr-1"></span>
                                    <span class="text-sm text-gray-600">Late</span>
                                </div>
                                <div>
                                    <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-1"></span>
                                    <span class="text-sm text-gray-600">Absent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/327d4822-0a3b-416d-8110-4dae483c91db.png" alt="John Doe profile photo - male employee with short hair and professional appearance" class="rounded-full border-2 border-white" />
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">John Doe</div>
                                                <div class="text-sm text-gray-500">HR Dept.</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:05 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05:30 PM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">9.25</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">On Time</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/f5fedf55-5ccc-4cc7-8762-77fc27a64386.png" alt="Lisa Ray profile photo - female employee with medium-length hair and glasses" class="rounded-full border-2 border-white" />
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Lisa Ray</div>
                                                <div class="text-sm text-gray-500">Finance Dept.</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:45 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">06:00 PM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">9.15</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Late</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/7ff6f221-e17b-4964-b619-04f95bde129d.png" alt="Michael Brown profile photo - male employee with short beard and professional appearance" class="rounded-full border-2 border-white" />
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Michael Brown</div>
                                                <div class="text-sm text-gray-500">IT Dept.</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:00 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">04:00 PM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">On Time</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/47fcf856-c43c-4e67-a398-a4a8359ee3ea.png" alt="Sarah Johnson profile photo - female employee with curly hair and professional appearance" class="rounded-full border-2 border-white" />
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                                <div class="text-sm text-gray-500">Marketing Dept.</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">0.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Absent</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">My Attendance History</h3>
                        <div class="flex space-x-2">
                            <select id="month-select" class="border rounded-md px-3 py-1 text-sm">
                                <option>September 2023</option>
                                <option>August 2023</option>
                                <option>July 2023</option>
                                <option>June 2023</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">01 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Friday</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:00 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05:00 PM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">9.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">On Time</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">02 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Saturday</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">0.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Weekend</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">03 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Sunday</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">0.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Weekend</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">04 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Monday</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:05 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">05:00 PM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">8.55</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">On Time</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">05 Sep 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Tuesday</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">08:15 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Working</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('collapsed');
            document.getElementById('company-name').classList.toggle('hidden');
            
            // Hide nav text when collapsed
            const navTexts = document.querySelectorAll('.nav-text');
            navTexts.forEach(text => {
                text.classList.toggle('hidden');
            });
        });

        // Tab switching
        const tabs = document.querySelectorAll('[data-tab]');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and tab contents
                tabs.forEach(t => t.parentElement.classList.remove('bg-blue-700'));
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked tab
                this.parentElement.classList.add('bg-blue-700');
                
                // Show corresponding tab content
                const tabId = this.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.remove('hidden');
            });
        });

        // Simulate time in/out functionality
        document.getElementById('time-in-btn').addEventListener('click', function() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = hours % 12 || 12;
            
            document.getElementById('status-indicator').textContent = 'Working';
            document.getElementById('status-indicator').classList.remove('bg-gray-200', 'text-gray-800');
            document.getElementById('status-indicator').classList.add('bg-blue-100', 'text-blue-800');
            
            document.getElementById('time-in-btn').disabled = true;
            document.getElementById('time-out-btn').disabled = false;
            
            // Simulate updating today's attendance log
            const timeCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(2)');
            if (timeCell && timeCell.textContent.trim() === '-') {
                timeCell.textContent = `${formattedHours}:${minutes} ${ampm}`;
                
                const statusCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(5)');
                if (statusCell) {
                    statusCell.innerHTML = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Working</span>`;
                }
            }
        });
        
        document.getElementById('time-out-btn').addEventListener('click', function() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const formattedHours = hours % 12 || 12;
            
            document.getElementById('status-indicator').textContent = 'Logged Out';
            document.getElementById('status-indicator').classList.remove('bg-blue-100', 'text-blue-800');
            document.getElementById('status-indicator').classList.add('bg-gray-200', 'text-gray-800');
            
            document.getElementById('time-in-btn').disabled = false;
            document.getElementById('time-out-btn').disabled = true;
            
            // Simulate updating today's attendance log
            const timeOutCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(3)');
            if (timeOutCell && timeOutCell.textContent.trim() === '-') {
                timeOutCell.textContent = `${formattedHours}:${minutes} ${ampm}`;
                
                // Calculate hours worked (simplified)
                const timeInCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(2)');
                if (timeInCell) {
                    const timeInText = timeInCell.textContent;
                    const [timeInHours, timeInMins] = timeInText.split(':').map(part => parseInt(part));
                    const isAM = timeInText.includes('AM');
                    let timeInTotal = (isAM ? timeInHours : timeInHours + 12) + timeInMins/60;
                    let timeOutTotal = (ampm === 'AM' ? formattedHours : formattedHours + 12) + minutes/60;
                    
                    if (ampm === 'AM' && !isAM) {
                        timeOutTotal += 24; // next day AM
                    }
                    
                    const hoursWorked = (timeOutTotal - timeInTotal).toFixed(2);
                    
                    const hoursCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(4)');
                    if (hoursCell) {
                        hoursCell.textContent = hoursWorked;
                    }
                    
                    const statusCell = document.querySelector('#attendance-tab tbody tr:nth-child(1) td:nth-child(5)');
                    if (statusCell) {
                        let statusClass = 'bg-green-100 text-green-800';
                        if (timeInTotal > 8.5) { // Late if after 8:30 AM
                            statusClass = 'bg-yellow-100 text-yellow-800';
                        }
                        statusCell.innerHTML = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">Logged</span>`;
                    }
                }
            }
        });

        // Update current time
        function updateCurrentTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
            
            let hours = now.getHours();
            let minutes = now.getMinutes().toString().padStart(2, '0');
            let seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            
            document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
        }
        
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime();

        // Schedule request type toggle
        document.getElementById('schedule-type').addEventListener('change', function() {
            const type = this.value;
            document.getElementById('shift-change-fields').classList.add('hidden');
            document.getElementById('swap-fields').classList.add('hidden');
            document.getElementById('dayoff-fields').classList.add('hidden');
            
            if (type === 'shift-change') {
                document.getElementById('shift-change-fields').classList.remove('hidden');
            } else if (type === 'swap') {
                document.getElementById('swap-fields').classList.remove('hidden');
            } else if (type === 'day-off') {
                document.getElementById('dayoff-fields').classList.remove('hidden');
            }
        });

        // Form submission handlers
        document.getElementById('leave-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Leave request submitted successfully!');
            this.reset();
        });
        
        document.getElementById('overtime-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Overtime request submitted successfully!');
            this.reset();
        });
        
        document.getElementById('schedule-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Schedule request submitted successfully!');
            this.reset();
        });
    </script>
</body>
</html>

