<?php
session_start();

echo "<h2>Admin Session Check</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";

echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "\n";
echo "Employee ID: " . ($_SESSION['employee']['id'] ?? 'NOT SET') . "\n";
echo "View Mode: " . ($_SESSION['view_mode'] ?? 'NOT SET') . "\n";

echo "\n\nFull Session Data:\n";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['admin_id'])) {
    echo "<p style='color: green;'>✅ Admin is logged in</p>";
} else {
    echo "<p style='color: red;'>❌ Admin is NOT logged in</p>";
    echo "<p>You need to login as admin or switch to admin view mode</p>";
}
?>
