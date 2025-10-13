<?php
// File: run_scheduler.php
// Web trigger for monthly leave accrual scheduler

// Security: Only allow from specific IP or with password
$allowed_password = 'your_secret_password_here'; // Change this!

if (!isset($_GET['password']) || $_GET['password'] !== $allowed_password) {
    die('Access denied');
}

echo "<h2>🗓️ Monthly Leave Accrual Scheduler</h2>";
echo "<p>Starting scheduler...</p>";
echo "<pre>";

// Include and run the scheduler
include_once 'monthly_leave_accrual_scheduler.php';

echo "</pre>";
echo "<p>✅ Scheduler completed!</p>";
?>