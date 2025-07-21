<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS HRIS - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom scrollbar for sidebar */
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4f46e5 #1e1b4b;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #1e1b4b;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #4f46e5;
            border-radius: 20px;
        }
        
        /* Animation for sidebar toggle */
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        @keyframes slideOut {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }
        .sidebar-mobile {
            animation: slideIn 0.3s forwards;
        }
        .sidebar-mobile-out {
            animation: slideOut 0.3s forwards;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Main Container -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Backdrop (Mobile) -->
        <div id="sidebarBackdrop" class="fixed inset-0 z-20 bg-black bg-opacity-50 hidden"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 h-full bg-indigo-900 border-r border-indigo-800">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 px-4 border-b border-indigo-800">
                    <div class="flex items-center">
                        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/1fa55b9b-1ed1-4c4a-8991-d3c4adc65719.png" alt="RSS HRIS logo with deep purple background and white text" class="h-8 w-8 rounded-md">
                        <span class="ml-2 text-xl font-bold text-white">RSS</span>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex flex-col flex-grow px-2 py-4 overflow-y-auto sidebar-scroll">
                    <!-- Dashboard Section -->
                    <div class="px-2">
                        <h3 class="text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">Admin Panel</h3>
                        <ul>
                            <li class="mb-1">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-800 rounded-lg group" onclick="loadContent('dashboard')">
                                    <i class="fas fa-home mr-3 text-indigo-300"></i>
                                    Dashboard
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-indigo-600 rounded-full">8 New</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Management Section -->
                    <div class="px-2 mt-4">
                        <h3 class="text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">Management</h3>
                        <ul>
                            <li class="mb-1" id="employeesNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('employees')">
                                    <i class="fas fa-users mr-3 text-indigo-300"></i>
                                    Employees Directory 
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Approvals Section -->
                    <div class="px-2 mt-4">
                        <h3 class="text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">Pending Approvals</h3>
                        <ul>
                            <li class="mb-1" id="leaveRequestsNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('leaveRequests')">
                                    <i class="fas fa-calendar-times mr-3 text-indigo-300"></i>
                                    Leave Requests
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-rose-500 rounded-full">12</span>
                                </a>
                            </li>
                            <li class="mb-1" id="scheduleChangesNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('scheduleChanges')">
                                    <i class="fas fa-clock mr-3 text-indigo-300"></i>
                                    Schedule Changes
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-amber-500 rounded-full">7</span>
                                </a>
                            </li>
                            <li class="mb-1" id="otRequestsNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('otRequests')">
                                    <i class="fas fa-business-time mr-3 text-indigo-300"></i>
                                    OT Requests
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-blue-500 rounded-full">5</span>
                                </a>
                            </li>
                            <li class="mb-1" id="timeAdjustmentsNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('timeAdjustments')">
                                    <i class="fas fa-hourglass-half mr-3 text-indigo-300"></i>
                                    Time Adjustments
                                    <span class="ml-auto px-2 py-0.5 text-xs font-medium bg-emerald-500 rounded-full">3</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- System Section -->
                    <div class="px-2 mt-4">
                        <h3 class="text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-2">System</h3>
                        <ul>
                            <li class="mb-1" id="logsNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('logs')">
                                    <i class="fas fa-clipboard-list mr-3 text-indigo-300"></i>
                                    Review Logs
                                </a>
                            </li>
                            <li class="mb-1" id="announcementsNav">
                                <a href="#" class="flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white hover:bg-indigo-800 rounded-lg group" onclick="loadContent('announcements')">
                                    <i class="fas fa-bullhorn mr-3 text-indigo-300"></i>
                                    Announcements
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- User Info (Bottom) -->
                <div class="flex items-center p-4 border-t border-indigo-800">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ead4cc6a-4567-4687-9c51-717b3b2145a5.png" alt="Admin profile photo with formal business attire and professional appearance" class="h-9 w-9 rounded-full">
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">Admin User</p>
                        <p class="text-xs font-medium text-indigo-300 group-hover:text-indigo-200">superadmin@rsshris.com</p>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Header -->
            <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200">
                <!-- Mobile Menu Button -->
                <button id="sidebarToggle" class="md:hidden text-gray-500 hover:text-gray-600 focus:outline-none" aria-label="Toggle sidebar">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Search Bar -->
                <div class="relative flex-1 max-w-md mx-4 hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search...">
                </div>
                
                <!-- Right Side -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="p-1 text-gray-400 hover:text-gray-500 focus:outline-none relative" id="notificationsButton">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                        </button>
                        <!-- Notification Dropdown -->
                        <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg overflow-hidden z-10">
                            <div class="p-4 border-b border-gray-200 bg-indigo-600 text-white">
                                <h3 class="text-sm font-medium">Notifications (4)</h3>
                            </div>
                            <div class="max-h-60 overflow-y-auto">
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/13dc0065-a610-4813-a975-085bc6599257.png" alt="Employee profile requesting time off" class="h-9 w-9 rounded-full">
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-800">John Smith requested time off</p>
                                            <p class="text-xs text-gray-500">Just now</p>
                                        </div>
                                    </div>
                                </a>
                                <!-- More notifications would go here -->
                            </div>
                            <div class="px-4 py-2 bg-gray-50 text-center">
                                <a href="#" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">View all notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button class="flex items-center focus:outline-none" id="profileButton">
                            <span class="hidden md:inline-block text-sm font-medium text-gray-700 mr-2">Admin User</span>
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/c71db7ca-65a5-4f0d-b845-2a06ac6504ef.png" alt="Admin profile photo thumbnail with professional appearance" class="h-8 w-8 rounded-full">
                        </button>
                        <!-- Profile Dropdown Content -->
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div id="contentArea">
                    <!-- Default Dashboard Content -->
                    <div id="dashboardContent" class="content-section">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                            <p class="text-gray-600">Welcome back, Admin. Here's what's happening today.</p>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            <!-- Pending Approvals -->
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-indigo-100 text-indigo-600">
                                        <i class="fas fa-clock text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900">27</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Employees -->
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-green-100 text-green-600">
                                        <i class="fas fa-users text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">Total Employees</p>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900">143</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Today's Leave -->
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600">
                                        <i class="fas fa-calendar-times text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">On Leave Today</p>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900">8</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- OT Requests -->
                            <div class="bg-white rounded-lg shadow p-6">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                                        <i class="fas fa-business-time text-lg"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-500">OT Requests</p>
                                        <p class="mt-1 text-2xl font-semibold text-gray-900">5</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity Section -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                            </div>
                            <div class="divide-y divide-gray-200">
                                <div class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/3beb94d1-9e93-4097-a6e3-0924a972af91.png" alt="Employee profile completing a request" class="h-9 w-9 rounded-full">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Maria Garcia</div>
                                            <div class="text-sm text-gray-500">Submitted a leave request for July 5-7</div>
                                        </div>
                                        <div class="ml-auto text-sm text-gray-500">Just now</div>
                                    </div>
                                </div>
                                <div class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/84b3c8f7-4758-42d7-9a99-90a379a14d4e.png" alt="IT administrator making system changes" class="h-9 w-9 rounded-full">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">IT Department</div>
                                            <div class="text-sm text-gray-500">System maintenance scheduled for tomorrow 2-4 AM</div>
                                        </div>
                                        <div class="ml-auto text-sm text-gray-500">30 mins ago</div>
                                    </div>
                                </div>
                                <div class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/f4ac4e5c-a2c2-481b-be2c-7ed0409d5590.png" alt="Employee starting training session" class="h-9 w-9 rounded-full">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Robert Johnson</div>
                                            <div class="text-sm text-gray-500">Completed mandatory compliance training</div>
                                        </div>
                                        <div class="ml-auto text-sm text-gray-500">2 hours ago</div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-6 py-4 bg-gray-50 text-right">
                                <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    View all activity
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employees Content -->
                    <div id="employeesContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Employee Management</h1>
                            <p class="text-gray-600">Manage all employee records and information</p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                                    <div class="mb-4 sm:mb-0">
                                        <input type="text" placeholder="Search employees..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <button class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <i class="fas fa-plus mr-2"></i>New Employee
                                    </button>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/5f448aa8-50cb-45de-b73a-f57b4ab0acc2.png" alt="Employee Sarah Johnson's profile photo with professional attire" class="h-10 w-10 rounded-full">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                                        <div class="text-sm text-gray-500">ID: EMP-1001</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">HR Manager</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">Human Resources</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                                <a href="#" class="text-gray-600 hover:text-gray-900">View</a>
                                            </td>
                                        </tr>
                                        <!-- More employee rows would go here -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-200">
                                <div class="flex-1 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">143</span> employees
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">2</a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">3</a>
                                            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other content sections would follow the same pattern -->
                    <div id="leaveRequestsContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Pending Leave Requests</h1>
                            <p class="text-gray-600">12 pending requests awaiting your approval</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                    
                    <div id="scheduleChangesContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Pending Schedule Changes</h1>
                            <p class="text-gray-600">7 pending schedule modification requests</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                    
                    <div id="otRequestsContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Pending OT Requests</h1>
                            <p class="text-gray-600">5 overtime requests to review</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                    
                    <div id="timeAdjustmentsContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Pending Time Adjustments</h1>
                            <p class="text-gray-600">3 time entry corrections to approve</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                    
                    <div id="logsContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">System Logs</h1>
                            <p class="text-gray-600">View and monitor system activity</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                    
                    <div id="announcementsContent" class="content-section hidden">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-800">Announcements</h1>
                            <p class="text-gray-600">Broadcast messages to your organization</p>
                        </div>
                        <!-- Content would be loaded here -->
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebarToggle = document.getElementById('sidebarToggle');
        let isMobileSidebarOpen = false;
        
        sidebarToggle.addEventListener('click', () => {
            if (!isMobileSidebarOpen) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('fixed', 'z-30', 'sidebar-mobile');
                sidebarBackdrop.classList.remove('hidden');
                isMobileSidebarOpen = true;
            } else {
                sidebar.classList.remove('sidebar-mobile');
                sidebar.classList.add('sidebar-mobile-out');
                setTimeout(() => {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('fixed', 'z-30', 'sidebar-mobile-out');
                    sidebarBackdrop.classList.add('hidden');
                    isMobileSidebarOpen = false;
                }, 300);
            }
        });
        
        sidebarBackdrop.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-mobile');
            sidebar.classList.add('sidebar-mobile-out');
            setTimeout(() => {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('fixed', 'z-30', 'sidebar-mobile-out');
                sidebarBackdrop.classList.add('hidden');
                isMobileSidebarOpen = false;
            }, 300);
        });
        
        // Dropdown toggles
        const notificationsButton = document.getElementById('notificationsButton');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const profileButton = document.getElementById('profileButton');
        const profileDropdown = document.getElementById('profileDropdown');
        
        notificationsButton.addEventListener('click', () => {
            notificationsDropdown.classList.toggle('hidden');
            profileDropdown.classList.add('hidden');
        });
        
        profileButton.addEventListener('click', () => {
            profileDropdown.classList.toggle('hidden');
            notificationsDropdown.classList.add('hidden');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!notificationsButton.contains(e.target) && !notificationsDropdown.contains(e.target)) {
                notificationsDropdown.classList.add('hidden');
            }
            if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
        
        // Navigation and content loading
        function loadContent(section) {
            // Hide all content sections
            document.querySelectorAll('.content-section').forEach(el => {
                el.classList.add('hidden');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.content-section').forEach(el => {
                el.classList.remove('bg-indigo-800');
                el.querySelector('a').classList.remove('text-white');
                el.querySelector('a').classList.add('text-indigo-200');
            });
            
            // Add active class to clicked nav item
            const navItem = document.getElementById(section + 'Nav');
            navItem.classList.add('bg-indigo-800');
            navItem.querySelector('a').classList.add('text-white');
            navItem.querySelector('a').classList.remove('text-indigo-200');
            
            // Show the selected content section
            document.getElementById(section + 'Content').classList.remove('hidden');
            
            // Close mobile sidebar if open
            if (isMobileSidebarOpen) {
                sidebar.classList.remove('sidebar-mobile');
                sidebar.classList.add('sidebar-mobile-out');
                setTimeout(() => {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('fixed', 'z-30', 'sidebar-mobile-out');
                    sidebarBackdrop.classList.add('hidden');
                    isMobileSidebarOpen = false;
                }, 300);
            }
        }
        
        // Set dashboard as default active
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('dashboardNav').classList.add('bg-indigo-800');
            document.querySelector('#dashboardNav a').classList.add('text-white');
            document.querySelector('#dashboardNav a').classList.remove('text-indigo-200');
        });
    </script>
</body>
</html>

