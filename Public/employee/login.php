<?php
session_start();
include('../config/db.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) {
        $_SESSION['employee'] = [
            'id' => $user['id'],
            'fname' => $user['fname'],
            'lname' => $user['lname'],
            'position' => $user['position']
        ];
        header("Location: ../module/time_log_create.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <!-- Paper Form Container -->
  <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-gray-200 p-8 sm:p-10 space-y-6">

    <!-- Header -->
    <div class="text-center">
      <h1 class="text-2xl sm:text-3xl font-semibold text-gray-800">Employee Login</h1>
      <p class="text-sm text-gray-500 mt-1">Access your employee dashboard</p>
    </div>

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
          class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
        />
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input 
          type="password" 
          name="password" 
          required 
          class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
        />
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2.5 rounded-md transition duration-200 shadow"
      >
        Login
      </button>
    </form>

    <!-- Footer Link
    <p class="text-center text-sm text-gray-500">
      <a href="../index.php" class="hover:underline hover:text-blue-600 transition">← Back to homepage</a>
    </p> -->

  </div>

</body>
</html>
