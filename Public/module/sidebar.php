<!-- Sidebar -->
       <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col fixed md:relative z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out fixed h-full">

            <!-- Logo -->
            <div class="p-6 flex justify-center">
                <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-32">
            </div>

            <hr class="border-t border-gray-300 w-full mb-4">

            <!-- Navigation -->
            <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                <a href="#" onclick="showSection('dashboardView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-tachometer-alt text-lg"></i>
                    <span class="text-base">Home</span>
                </a>
                <a href="#" onclick="showSection('newsFeedView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-newspaper text-lg"></i>
                    <span class="text-base">News Feed</span>
                </a>
                <a href="#" onclick="showSection('scheduleView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-calendar-alt text-lg"></i>
                    <span class="text-base">Request Change Schedule</span>
                </a>
                <a href="#" onclick="showSection('overtimeView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-clock text-lg"></i>
                    <span class="text-base">Overtime</span>
                </a>
    
              <div class="space-y-1">
    <button onclick="toggleLeaveMenu()" class="flex items-center justify-between w-full p-2 rounded-lg hover:bg-green-100 text-gray-700">
        <span class="flex items-center space-x-3">
            <i class="fas fa-plane-departure text-lg"></i>
            <span class="text-base">Leave</span>
        </span>
        <i class="fas fa-chevron-down text-sm transition-transform" id="leaveMenuIcon"></i>
    </button>
    <div id="leaveSubmenu" class="pl-10 hidden space-y-1">
        <a href="#" onclick="showSection('requestView');" class="block p-2 rounded hover:bg-green-100 text-gray-700">
            Request Leave
        </a>
        <a href="#" onclick="showSection('leaveCreditsView');" class="block p-2 rounded hover:bg-green-100 text-gray-700">
            Leave Credits
        </a>
    </div>
</div>

            </nav>

           <hr class="border-t border-gray-300 w-full mt-4 mb-1">


            <!-- User Info -->
            <div class="px-4 py-3 flex items-center">
                <?php if ($profile_picture): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border border-gray-300 mr-3">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-green-600 flex items-center justify-center text-white font-bold text-xl border border-gray-300 mr-3">
                        <?= strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="flex flex-col">
                    <span class="font-semibold text-sm text-gray-800"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
                    <span class="text-xs text-gray-500"><?= htmlspecialchars($position) ?></span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Fixed Header -->
            <header class="fixed top-0 left-0 md:left-64 w-full md:w-[calc(100%-16rem)] bg-white shadow z-50 flex items-center justify-between px-4 md:px-8 py-4">
    <div class="flex items-center space-x-4">
        <!-- Hamburger button -->
    <!-- Hamburger only on mobile -->
   <button id="hamburgerBtn" class="md:hidden text-gray-600 mr-2">
  <i class="fas fa-bars text-xl"></i>
</button>


        <h1 class="text-2xl font-semibold text-gray-800">Employee Dashboard</h1>
    </div>

 <div class="flex items-center space-x-6">
      <button class="relative text-gray-600 hover:text-gray-800 focus:outline-none notification-button" onclick="toggleModal()">
        <i class="fas fa-bell text-xl"></i>
        <span class="absolute -top-1 -right-1 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
      </button>