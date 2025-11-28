<?php
// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Secure session settings
session_set_cookie_params([
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

include('../config/db.php');
include('../config/csrf_helper.php');

// Initialize CSRF protection
init_csrf_protection();

// Check if user is already logged in - redirect them away from login page
if (isset($_SESSION['employee']['id'])) {
    header("Location: ../module/time_log_create.php");
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Server-side mobile block removed - Allow all devices
// function isMobileDevice() {
//     $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
//     return preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua);
// }

// $screenWidth = $_COOKIE['screen_width'] ?? 1200;
// if (isMobileDevice() || $screenWidth < 1024) {
//     die("<h2 style='text-align:center; padding-top:60px;'>Access denied on mobile devices.<br>Please use a desktop or laptop computer.</h2>");
// }

// Brute-force protection
$max_attempts = 5;
$lockout_time = 300; // 5 minutes
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Enhanced CSRF check with centralized validation
    $csrf_validation = validate_csrf_token(true);
    if (!$csrf_validation['valid']) {
        error_log("SECURITY: CSRF validation failed in employee login - " . $csrf_validation['error'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
        $error = "Security validation failed. Please refresh the page and try again.";
    } else {
        // Only process login if CSRF token is valid
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

    $attempt = $_SESSION['login_attempts'][$username] ?? ['count' => 0, 'time' => 0];
    if ($attempt['count'] >= $max_attempts && (time() - $attempt['time']) < $lockout_time) {
        die("Too many failed login attempts. Try again after 5 minutes.");
    }

    // Query DB
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Check password - handle both hashed and plain text passwords
    $password_valid = false;
    if ($user) {
        // First try password_verify for hashed passwords (new method)
        if (password_verify($password, $user['password'])) {
            $password_valid = true;
        } 
        // If that fails, try direct comparison for plain text passwords (legacy method)
        else if ($user['password'] === $password) {
            $password_valid = true;
            
            // Optional: Update to hashed password for security
            // This will automatically upgrade plain text passwords to hashed ones
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user['id']]);
        }
    }

    if ($user && $password_valid) {
        // Success
        unset($_SESSION['login_attempts'][$username]);
        session_regenerate_id(true);

        // Default role is "employee"
        $role = $user['role'] ?? 'employee';

        $_SESSION['employee'] = [
            'id'       => $user['id'],
            'fname'    => $user['fname'],
            'lname'    => $user['lname'],
            'position' => $user['position'],
            'role'     => $role
        ];

        // Set default view mode to employee
        $_SESSION['view_mode'] = 'employee';

        header("Location: ../module/time_log_create.php");
        exit;
    } else {
        // Failed login
        $_SESSION['login_attempts'][$username] = [
            'count' => $attempt['count'] + 1,
            'time'  => time()
        ];
        $error = "Invalid username or password.";
    }
    } // Close CSRF validation else block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="../asset/RSS-logo-colour.png" />
  <title>Employee Login</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Block mobile using screen width & user agent -->
  <script>
    document.cookie = "screen_width=" + window.innerWidth + "; path=/";

    function isProbablyMobile() {
      const ua = navigator.userAgent;
      const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      const isSmall = window.innerWidth <= 1024;
      const isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
      return isMobileUA || isTouch || isSmall;
    }

    if (isProbablyMobile()) {
      document.documentElement.innerHTML = `
        <div style="text-align:center;padding-top:60px;font-family:sans-serif;">
          <h2>Access denied on mobile devices.</h2>
          <p>Please use a desktop or laptop computer.</p>
        </div>`;
      throw new Error("Blocked mobile device");
    }
  </script>

  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    html::before {
      content: "JavaScript is required and mobile access is restricted.";
      display: none;
    }
  </style>

  <noscript>
    <style>
      body { display: none !important; }
      html::before {
        content: "JavaScript is required and mobile access is restricted.";
        display: block;
        text-align: center;
        padding-top: 60px;
        font-family: sans-serif;
      }
    </style>
  </noscript>
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
      <?= csrf_token_field() ?>>

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
  <!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68636c761c010c190e038444/1iv25vcme';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
</body>
</html>