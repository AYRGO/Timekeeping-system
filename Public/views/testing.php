<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS HRIS | Employee Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .avatar {
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Profile Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <div class="w-32 h-32 rounded-full avatar overflow-hidden">
                        <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/e69e5987-0593-4a53-a928-39db07786cef.png" alt="Employee portrait of John Doe, a 30-year-old man with short brown hair and business attire" class="w-full h-full object-cover" onerror="this.src='https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/b65931a0-3845-41f0-8abb-71d693ef98da.png'">
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">John Doe</h1>
                                <p class="text-gray-600">Software Developer</p>
                            </div>
                            <div class="flex gap-2">
                                <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                    <i class="fas fa-edit mr-2"></i>Edit Profile
                                </button>
                                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                                    <i class="fas fa-print mr-2"></i>Print
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Employee ID</p>
                                <p class="font-medium">EMP-00123</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Department</p>
                                <p class="font-medium">IT</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Employment Status</p>
                                <p class="font-medium">Regular</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Date Hired</p>
                                <p class="font-medium">Jan 15, 2020</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="flex overflow-x-auto border-b border-gray-200 mb-6">
                <button onclick="openTab(event, 'profile')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition" id="default-tab">
                    <i class="fas fa-user mr-2"></i>Profile
                </button>
                <button onclick="openTab(event, 'checklist')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-tasks mr-2"></i>201 Checklist
                </button>
                <button onclick="openTab(event, 'leave-credits')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-calendar-alt mr-2"></i>Leave Credits
                </button>
                <button onclick="openTab(event, 'leave-requests')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-plane mr-2"></i>Leave Requests
                </button>
                <button onclick="openTab(event, 'schedule-changes')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-clock mr-2"></i>Schedule Changes
                </button>
                <button onclick="openTab(event, 'overtime-requests')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-business-time mr-2"></i>Overtime
                </button>
                <button onclick="openTab(event, 'time-adjustments')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-history mr-2"></i>Time Adjustments
                </button>
            </div>
            
            <!-- Tab Contents -->
            <div id="profile" class="tab-content active">
                <!-- Profile Information -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Personal Information</h2>
                        <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition">Edit</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Full Name</p>
                            <p class="font-medium">John Michael Doe</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date of Birth</p>
                            <p class="font-medium">May 15, 1990</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Gender</p>
                            <p class="font-medium">Male</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Marital Status</p>
                            <p class="font-medium">Single</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nationality</p>
                            <p class="font-medium">American</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Contact Number</p>
                            <p class="font-medium">+1 (555) 123-4567</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium">john.doe@company.com</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Address</p>
                            <p class="font-medium">123 Main St, Anytown, USA</p>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Emergency Contact</h2>
                        <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition">Edit</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium">Jane Doe</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Relationship</p>
                            <p class="font-medium">Mother</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Contact Number</p>
                            <p class="font-medium">+1 (555) 987-6543</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Address</p>
                            <p class="font-medium">123 Main St, Anytown, USA</p>
                        </div>
                    </div>
                </div>
                
                <!-- Employment Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Employment Details</h2>
                        <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition">Edit</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Position</p>
                            <p class="font-medium">Senior Software Developer</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Department</p>
                            <p class="font-medium">Information Technology</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Manager</p>
                            <p class="font-medium">Sarah Johnson</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Employment Type</p>
                            <p class="font-medium">Full-time</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Salary</p>
                            <p class="font-medium">$90,000/annually</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Working Schedule</p>
                            <p class="font-medium">Mon-Fri, 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 201 Checklist Tab -->
            <div id="checklist" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">201 Checklist</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Add Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed On</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Employee Handbook Review</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 22, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 20, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Company Policies Quiz</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 29, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 27, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Benefits Enrollment</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Feb 5, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Equipment Setup</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">In Progress</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Feb 12, 2020</td>
                                    <td class="px-6 py-4 whitespace-nowrap">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Leave Credits Tab -->
            <div id="leave-credits" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Leave Credits</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Add Credit
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <h3 class="font-medium text-green-800 mb-2">Vacation Leave</h3>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-3xl font-bold text-green-600">12.0</p>
                                    <p class="text-sm text-green-500">days remaining</p>
                                </div>
                                <div>
                                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                        <i class="fas fa-umbrella-beach text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <h3 class="font-medium text-blue-800 mb-2">Sick Leave</h3>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-3xl font-bold text-blue-600">5.5</p>
                                    <p class="text-sm text-blue-500">days remaining</p>
                                </div>
                                <div>
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-procedures text-blue-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <h3 class="font-medium text-purple-800 mb-2">Emergency Leave</h3>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-3xl font-bold text-purple-600">3.0</p>
                                    <p class="text-sm text-purple-500">days remaining</p>
                                </div>
                                <div>
                                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                        <i class="fas fa-exclamation-triangle text-purple-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credits Added</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiration Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Vacation Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">10.0</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Dec 31, 2023</td>
                                    <td class="px-6 py-4">Annual allocation</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Sick Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">7.0</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jan 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Dec 31, 2023</td>
                                    <td class="px-6 py-4">Annual allocation</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Vacation Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">2.0</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jun 1, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Dec 31, 2023</td>
                                    <td class="px-6 py-4">Performance bonus</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Leave Requests Tab -->
            <div id="leave-requests" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Leave Requests</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>New Request
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approver</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Vacation Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jul 15, 2023 - Jul 18, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">4 days</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Sick Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sep 5, 2023 - Sep 8, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">4 days</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Emergency Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Nov 2, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">1 day</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Processing</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Vacation Leave</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Mar 10, 2023 - Mar 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">6 days</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Schedule Change Requests Tab -->
            <div id="schedule-changes" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Schedule Change Requests</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>New Request
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Schedule</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Temporary Change</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jul 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Jul 25, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">10:00 AM - 7:00 PM</td>
                                    <td class="px-6 py-4">Family commitments</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Permanent Change</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sep 1, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Ongoing</td>
                                    <td class="px-6 py-4 whitespace-nowrap">8:00 AM - 5:00 PM</td>
                                    <td class="px-6 py-4">Preference for earlier schedule</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Temporary Change</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Nov 1, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Nov 5, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Work from home</td>
                                    <td class="px-6 py-4">Home renovation</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Processing</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Overtime Requests Tab -->
            <div id="overtime-requests" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Overtime Requests</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>New Request
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approver</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Jun 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">2.5</td>
                                    <td class="px-6 py-4">Project deadline</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Aug 25, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">3.0</td>
                                    <td class="px-6 py-4">System maintenance</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Oct 15, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">4.0</td>
                                    <td class="px-6 py-4">Product launch</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Sarah Johnson</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Time Adjustment Requests Tab -->
            <div id="time-adjustments" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Time Adjustment Requests</h2>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>New Request
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Jun 10, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">8:45 AM (from 9:05 AM)</td>
                                    <td class="px-6 py-4 whitespace-nowrap">6:15 PM (from 6:05 PM)</td>
                                    <td class="px-6 py-4">Timesheet correction</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Aug 5, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">9:15 AM (from 9:30 AM)</td>
                                    <td class="px-6 py-4 whitespace-nowrap">6:30 PM (from 6:45 PM)</td>
                                    <td class="px-6 py-4">System issue in morning</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-6 hover:text-red-800">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Nov 20, 2023</td>
                                    <td class="px-6 py-4 whitespace-nowrap">9:00 AM (from 10:15 AM)</td>
                                    <td class="px-6 py-4 whitespace-nowrap">6:00 PM (unchanged)</td>
                                    <td class="px-6 py-4">Doctor's appointment</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-900">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tab buttons
            var tabButtons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("border-blue-500", "text-blue-600");
                tabButtons[i].classList.add("border-transparent");
            }
            
            // Show the current tab and add active class to button
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("border-blue-500", "text-blue-600");
            evt.currentTarget.classList.remove("border-transparent");
        }
        
        // Set the first tab as active by default
        document.getElementById("default-tab").click();
    </script>
</body>
</html>

