<?php
// Secure session configuration BEFORE session_start
session_set_cookie_params([
    'secure' => true,       // Only over HTTPS
    'httponly' => true,     // Inaccessible via JavaScript
    'samesite' => 'Strict'  // Prevent cross-site access
]);

session_start();
include('../config/db.php');

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize error message
$error = '';

// Brute-force configuration
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Brute-force check
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $attempt = $_SESSION['login_attempts'][$username] ?? ['count' => 0, 'time' => 0];
    if ($attempt['count'] >= $max_attempts && (time() - $attempt['time']) < $lockout_time) {
        die("Too many failed login attempts. Try again after 5 minutes.");
    }

    // Database query
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Password check (plaintext — upgrade later!)
    if ($user && $user['password'] === $password) {
        // Success: Reset login attempts
        unset($_SESSION['login_attempts'][$username]);

        // Session hardening
        session_regenerate_id(true);

        $_SESSION['employee'] = [
            'id' => $user['id'],
            'fname' => $user['fname'],
            'lname' => $user['lname'],
            'position' => $user['position']
        ];

        header("Location: ../module/time_log_create.php");
        exit;
    } else {
        // Failed login tracking
        $_SESSION['login_attempts'][$username] = [
            'count' => $attempt['count'] + 1,
            'time' => time()
        ];

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
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input 
          type="text" 
          name="username" 
          required 
          class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input 
          type="password" 
          name="password" 
          required 
          class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
        />
      </div>

      <button 
        type="submit" 
        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2.5 rounded-md transition duration-200 shadow"
      >
        Login
      </button>
    </form>

  </div>

</body>
</html>
