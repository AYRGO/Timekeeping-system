<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>RSS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, rgb(16, 185, 72) 0%, rgb(5, 101, 211) 100%);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body class="bg-white">

<!-- Navigation -->
<nav class="gradient-bg shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo / Brand -->
            <h1 class="text-white text-2xl font-bold">RSS Admin</h1>

            <!-- Navigation links -->
            <div class="hidden md:flex space-x-4">
                <?php
                $current = basename($_SERVER['PHP_SELF']);
                $navItems = [
                    'admin_homepage.php' => 'Dashboard',
                    'employee_list.php' => 'Employees',
                    'leave_request_list.php' => 'Leave Requests',
                    'schedule_request.php' => 'Schedule Requests',
                ];
                foreach ($navItems as $file => $label): ?>
                    <a href="<?= $file ?>"
                       class="px-4 py-2 rounded-md text-sm font-medium text-white <?= $current === $file ? 'bg-green-800' : 'hover:bg-green-700' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Logout icon with confirmation modal -->
            <div class="relative">
                <button id="logoutBtn" title="Logout" class="flex items-center space-x-2 focus:outline-none">
                    <!-- Heroicons outline logout icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white hover:text-red-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
                    </svg>
                </button>
            </div>

            <!-- Logout Confirmation Modal -->
            <div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
                    <h2 class="text-lg font-semibold mb-4">Confirm Logout</h2>
                    <p class="mb-6">Are you sure you want to logout?</p>
                    <div class="flex justify-end space-x-3">
                        <button id="cancelLogout" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                        <a href="../admin/logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Logout</a>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const logoutBtn = document.getElementById('logoutBtn');
                    const logoutModal = document.getElementById('logoutModal');
                    const cancelLogout = document.getElementById('cancelLogout');

                    logoutBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        logoutModal.classList.remove('hidden');
                    });

                    cancelLogout.addEventListener('click', () => {
                        logoutModal.classList.add('hidden');
                    });

                    // Optional: close modal on outside click
                    logoutModal.addEventListener('click', (e) => {
                        if (e.target === logoutModal) {
                            logoutModal.classList.add('hidden');
                        }
                    });
                });
            </script>

                <!-- Dropdown menu -->
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">

                    <div class="border-t my-1"></div>
                    <a href="../admin/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Toggle dropdown on avatar click
    document.addEventListener('DOMContentLoaded', () => {
        const avatarBtn = document.getElementById('avatarBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');

        avatarBtn.addEventListener('click', () => {
            dropdownMenu.classList.toggle('hidden');
        });

        // Optional: hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!avatarBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });
    });
</script>

</body>
</html>
