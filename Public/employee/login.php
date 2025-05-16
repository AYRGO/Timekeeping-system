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
<body class="bg-[#A3F7B5] min-h-screen flex items-center justify-center px-4 py-8 sm:py-12">

  <div class="w-full max-w-sm sm:max-w-md md:max-w-lg bg-white rounded-2xl shadow-xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">

    <div class="text-center">
      <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95]">Employee Login</h1>
    </div>

    <?php if (!empty($error)): ?>
      <p class="text-red-600 text-center font-medium"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-5 text-base sm:text-lg">

      <!-- Username -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Username:</label>
        <input 
          type="text" 
          name="username" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Password -->
      <div>
        <label class="block text-[#2F9C95] font-semibold mb-1">Password:</label>
        <input 
          type="password" 
          name="password" 
          required 
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#40C9A2]"
        />
      </div>

      <!-- Submit Button -->
      <button 
        type="submit" 
        class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200 text-lg"
      >
        Login
      </button>
    </form>

    <!-- Optional Footer Link -->
    <p class="text-center text-sm text-[#2F9C95]">
      <a href="../index.php" class="underline hover:text-[#40C9A2]">
        ← Back to homepage
      </a>
    </p>
    
  </div>

</body>
</html>
