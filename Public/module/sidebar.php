<!-- Sidebar -->
       <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col fixed md:relative z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out h-screen">

            <!-- Logo -->
            <div class="p-6 pt-8 flex justify-center">
                <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-28 h-auto">
            </div>

            <hr class="border-t border-gray-300 w-full mb-4">

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-2 space-y-2 overflow-y-auto overflow-x-visible">
                <a href="#" onclick="showSection('dashboardView');" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-green-50 text-gray-700 transition-colors" data-section="dashboardView">
                    <i class="fas fa-tachometer-alt text-lg"></i>
                    <span class="text-base font-medium">Home</span>
                </a>
                <a href="#" onclick="showSection('newsFeedView');" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-green-50 text-gray-700 transition-colors" data-section="newsFeedView">
                    <i class="fas fa-newspaper text-lg"></i>
                    <span class="text-base font-medium">News Feed</span>
                </a>
                <a href="#" onclick="showSection('scheduleView');" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-green-50 text-gray-700 transition-colors" data-section="scheduleView">
                    <i class="fas fa-calendar-check text-lg"></i>
                    <span class="text-base font-medium">Schedule Management</span>
                </a>
                <a href="#" onclick="showSection('overtimeView');" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-green-50 text-gray-700 transition-colors" data-section="overtimeView">
                    <i class="fas fa-clock text-lg"></i>
                    <span class="text-base font-medium">Overtime</span>
                </a>
              <div class="space-y-1">
    <button onclick="toggleLeaveMenu()" class="nav-link flex items-center justify-between w-full p-3 rounded-lg hover:bg-green-50 text-gray-700 transition-colors" data-section="leaveMenu">
        <span class="flex items-center space-x-3">
            <i class="fas fa-plane-departure text-lg"></i>
            <span class="text-base font-medium">Leave</span>
        </span>
        <i class="fas fa-chevron-down text-sm transition-transform" id="leaveMenuIcon"></i>
    </button>
    <div id="leaveSubmenu" class="pl-10 hidden space-y-1">
        <a href="#" onclick="showSection('requestView');" class="nav-link block p-2 rounded-lg hover:bg-green-50 text-gray-700 text-sm transition-colors" data-section="requestView">
            Request Leave
        </a>
        <a href="#" onclick="showSection('leaveCreditsView');" class="nav-link block p-2 rounded-lg hover:bg-green-50 text-gray-700 text-sm transition-colors" data-section="leaveCreditsView">
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