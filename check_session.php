<?php
// Debug session info
session_start();

echo "<h2>Session Debug Info</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "All Session Data:\n";
print_r($_SESSION);
echo "\n\n";

echo "Employee ID Detection:\n";
$employee_id = $_SESSION['employee']['id'] ?? $_SESSION['user_id'] ?? null;
echo "Detected employee_id: " . ($employee_id ?? 'NOT FOUND') . "\n";

echo "\nSession Keys Available:\n";
if (isset($_SESSION['employee']['id'])) {
    echo "✅ \$_SESSION['employee']['id'] = " . $_SESSION['employee']['id'] . "\n";
}
if (isset($_SESSION['user_id'])) {
    echo "✅ \$_SESSION['user_id'] = " . $_SESSION['user_id'] . "\n";
}
if (!isset($_SESSION['employee']['id']) && !isset($_SESSION['user_id'])) {
    echo "❌ No employee/user ID found in session\n";
    echo "Please login first\n";
}

echo "</pre>";

echo "<hr>";
echo "<p><a href='Public/views/employee-index.php'>Go to Dashboard</a></p>";
?>
