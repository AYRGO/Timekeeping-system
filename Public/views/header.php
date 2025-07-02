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

            <!-- Avatar dropdown -->
            <div class="relative">
                <button id="avatarBtn" class="flex items-center space-x-2 focus:outline-none">
                    <img src="https://harley.resourcestaffonline.com/Public/asset/RSS-logo-colour.png" alt="Avatar" class="h-9 w-9 rounded-full border-2 border-white object-cover" />
                </button>

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
