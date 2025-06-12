<?php
// set_screen_width.php
$redirect = $_GET['redirect'] ?? 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Initializing...</title>
  <script>
    document.cookie = "screen_width=" + window.innerWidth + "; path=/";
    window.location.href = "<?= htmlspecialchars($redirect) ?>";
  </script>
  <noscript>
    <h2 style="text-align:center;padding-top:60px;font-family:sans-serif;">
      JavaScript is required. Please enable JavaScript and reload the page.
    </h2>
  </noscript>
</head>
<body></body>
</html>
