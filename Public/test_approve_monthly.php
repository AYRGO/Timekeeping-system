<?php
// Test script to simulate approving a monthly schedule request
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['employee']['id'] = 1009; // Set admin session

// Simulate POST data
$_POST['request_id'] = 7;
$_POST['action'] = 'approve';

echo "<h2>Simulating Monthly Schedule Approval for Request #7</h2>";
echo "<p>This will test if the email gets sent properly...</p>";
echo "<hr>";

// Include the actual processor
include('views/process_monthly_schedule_action.php');
