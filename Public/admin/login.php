<?php
session_start();
include('../config/db.php');

// Redirect if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: ../views/employee_list.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && $admin['admin_password'] === $password) {
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['admin_username']
        ];
        header("Location: ../views/employee_list.php"); // redirect to admin panel
        exit;
    } else {
        $error = "Invalid admin username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0fe0fc] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-sm sm:max-w-md md:max-w-lg bg-white rounded-2xl shadow-xl border-t-8 border-[#00ffff] p-6 sm:p-8 space-y-6">

    <div class="text-center">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#0fe0fc]">Admin Login</h1>
    </div>

    <?php if (!empty($error)): ?>
      <p class="text-red-600 text-center font-medium"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- Username -->
      <div>
        <label class="block text-[#B45309] font-semibold mb-1">Username:</label>
        <input 
          type="text" 
          name="username" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0fe0fc]"
        />
      </div>

      <!-- Password -->
      <div>
        <label class="block text-[#B45309] font-semibold mb-1">Password:</label>
        <input 
          type="password" 
          name="password" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0fe0fc]"
        />
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        class="w-full bg-[#0fe0fc] hover:bg-[#D97706] text-white font-semibold py-3 rounded-xl transition duration-200 text-lg"
      >
        Login
      </button>
    </form>

    <p class="text-center text-sm text-[#B45309]">
      <a href="../index.php" class="underline hover:text-[#F59E0B]">
        ← Back to homepage
      </a>
    </p>
    
  </div>

</body>
</html>
