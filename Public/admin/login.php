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
        header("Location: ../views/employee_list.php");
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-md p-8 sm:p-10 space-y-6">

    <!-- Header -->
    <div class="text-center">
      <h1 class="text-2xl sm:text-3xl font-semibold text-gray-800">Admin Login</h1>
      <p class="text-sm text-gray-500 mt-1">Enter your credentials to continue</p>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
      <p class="text-red-600 text-center font-medium"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" class="space-y-5">

      <!-- Username -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input 
          type="text" 
          name="username" 
          required 
          class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
        />
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input 
          type="password" 
          name="password" 
          required 
          class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400"
        />
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        class="w-full bg-sky-500 hover:bg-sky-600 text-white font-medium py-2.5 rounded-md transition duration-200 shadow"
      >
        Login
      </button>
    </form>

    <!-- Back Link
    <p class="text-center text-sm text-gray-500">
      <a href="../index.php" class="hover:underline hover:text-sky-600 transition">← Back to homepage</a>
    </p> -->

  </div>

</body>
</html>
