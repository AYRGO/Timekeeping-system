<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS HRIS - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a5632;
            --primary-light: #2e7d32;
            --accent-gold: #d4af37;
            --sidebar-width: 17rem;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            @apply bg-gray-100;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            @apply fixed h-screen bg-[var(--primary-dark)] text-white transition-all duration-300;
        }
        
        .content-area {
            margin-left: var(--sidebar-width);
            @apply transition-all duration-300;
        }
        
        .nav-link.active {
            @apply bg-[var(--primary-light)] border-l-4 border-[var(--accent-gold)];
        }
        
        .nav-link:hover:not(.active) {
            @apply bg-[var(--primary-light)] bg-opacity-50;
        }
        
        .alert-count {
            @apply absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .content-area {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar shadow-lg">
        <div class="p-4 flex items-center justify-between border-b border-[var(--accent-gold)] border-opacity-30">
            <div class="flex items-center">
                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/418e54b6-423c-494e-9187-e97f9d83124f.png" alt="RSS HRIS Logo - Green hexagonal badge with golden RSS letters" class="mr-3 rounded">
                <h1 class="text-xl font-bold">RSS HRIS</h1>
            </div>
            <span class="text-xs px-2 py-1 bg-[var(--accent-gold)] rounded text-[var(--primary-dark)] font-bold">ADMIN</span>
        </div>
        
        <div class="py-4 px-2">
            <div class="px-4 py-2 text-sm text-gray-300 uppercase tracking-wider">Navigation</div>
            
            <nav>
                <a href="#" class="nav-link active relative flex items-center px-4 py-3">
                    <i class="fas fa-tachometer-alt mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-users mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Employees Directory</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-calendar-minus mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Pending Leave Requests</span>
                    <span class="alert-count">5</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-calendar-edit mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Pending Schedule Changes</span>
                    <span class="alert-count">3</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-clock mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Pending OT Requests</span>
                    <span class="alert-count">7</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-history mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Pending Time Adjustments</span>
                    <span class="alert-count">2</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-clipboard-list mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Review Logs</span>
                </a>
                
                <a href="#" class="nav-link relative flex items-center px-4 py-3">
                    <i class="fas fa-bullhorn mr-3 w-6 text-center text-[var(--accent-gold)]"></i>
                    <span>Announcement</span>
                </a>
            </nav>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 p-4 text-center text-xs text-gray-400">
            <p class="mb-1">RSS HRIS v2.4.1</p>
            <p class="text-[var(--accent-gold)]">© 2023 All Rights Reserved</p>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <!-- Header -->
        <header class="bg-white shadow-sm py-4 px-6 flex items-center justify-between sticky top-0 z-10">
            <div>
                <button id="sidebarToggle" class="md:hidden text-[var(--primary-dark)]">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-[var(--primary-dark)] ml-2 md:ml-0">Admin Dashboard</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="text-gray-600 hover:text-[var(--primary-dark)]">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="alert-count">12</span>
                    </button>
                </div>
                
                <div class="flex items-center">
                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/9c4b2c19-feaa-4e03-81f1-a87bf884e8c9.png" alt="Admin Profile Picture - Professional headshot of admin user" class="w-10 h-10 rounded-full border-2 border-[var(--accent-gold)]">
                    <div class="ml-3">
                        <p class="font-medium text-sm">Administrator</p>
                        <p class="text-xs text-gray-500">Super Admin</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6">
            <!-- Dashboard Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[var(--primary-dark)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">New Employees</p>
                            <p class="text-2xl font-bold">24</p>
                        </div>
                        <div class="p-3 rounded-full bg-green-100 text-[var(--primary-dark)]">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-green-600 font-medium">
                        <i class="fas fa-arrow-up mr-1"></i> 12.5% from last month
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[var(--primary-dark)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Approvals</p>
                            <p class="text-2xl font-bold">17</p>
                        </div>
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-yellow-600 font-medium">
                        <i class="fas fa-exclamation-circle mr-1"></i> 3 high priority
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[var(--primary-dark)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Active Employees</p>
                            <p class="text-2xl font-bold">287</p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-blue-600 font-medium">
                        <i class="fas fa-info-circle mr-1"></i> 92% active
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-[var(--primary-dark)]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Announcements</p>
                            <p class="text-2xl font-bold">3</p>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-[var(--primary-dark)] font-medium">
                        <i class="fas fa-eye mr-1"></i> 2 unread
                    </div>
                </div>
            </div>

            <!-- Section: Recent Activities -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-semibold text-[var(--primary-dark)] flex items-center">
                        <i class="fas fa-history text-[var(--accent-gold)] mr-2"></i>
                        Recent Activities
                    </h2>
                    <button class="text-xs text-[var(--primary-dark)] hover:underline">View All</button>
                </div>
                <div class="divide-y">
                    <div class="p-4 flex items-start hover:bg-gray-50">
                        <div class="p-2 rounded-full bg-green-100 text-[var(--primary-dark)] mr-3">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-sm"><span class="font-medium">John Smith</span> has been approved for <span class="text-[var(--primary-dark)]">Leave Request</span></p>
                            <p class="text-xs text-gray-500 mt-1">2 minutes ago</p>
                        </div>
                    </div>
                    <div class="p-4 flex items-start hover:bg-gray-50">
                        <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <div>
                            <p class="text-sm"><span class="font-medium">Sarah Johnson</span> submitted a <span class="text-[var(--primary-dark)]">Timesheet Adjustment</span></p>
                            <p class="text-xs text-gray-500 mt-1">15 minutes ago</p>
                        </div>
                    </div>
                    <div class="p-4 flex items-start hover:bg-gray-50">
                        <div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mr-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <p class="text-sm"><span class="font-medium">Michael Lee</span> has <span class="text-[var(--primary-dark)]">OVERTIME PENDING</span> for approval</p>
                            <p class="text-xs text-gray-500 mt-1">1 hour ago</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Employees Directory Section -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-semibold text-[var(--primary-dark)] flex items-center">
                        <i class="fas fa-users text-[var(--accent-gold)] mr-2"></i>
                        Employees Directory (Sample)
                    </h2>
                    <div>
                        <button class="text-xs bg-[var(--primary-dark)] text-white px-3 py-1 rounded hover:bg-[var(--primary-light)] transition mr-2">
                            <i class="fas fa-plus mr-1"></i> Add Employee
                        </button>
                        <button class="text-xs border border-gray-300 text-gray-600 px-3 py-1 rounded hover:bg-gray-50 transition">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/00340564-405f-4639-912a-47cb0af19d20.png" alt="Employee John Smith - Professional headshot of a man with brown hair and blue shirt" class="w-10 h-10 rounded-full mr-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">John Smith</div>
                                            <div class="text-sm text-gray-500">EMP-1001</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Engineering</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Senior Developer</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button class="text-[var(--primary-dark)] hover:text-[var(--primary-light)] mx-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-800 mx-1">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-800 mx-1">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/1ff24f45-096d-4e4f-aaba-643383deb56a.png" alt="Employee Sarah Johnson - Asian woman with glasses smiling professionally" class="w-10 h-10 rounded-full mr-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                            <div class="text-sm text-gray-500">EMP-1002</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Human Resources</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">HR Manager</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button class="text-[var(--primary-dark)] hover:text-[var(--primary-light)] mx-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-800 mx-1">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-800 mx-1">
                                        <i class="fas fa-trash-alt"></i>
                                 
