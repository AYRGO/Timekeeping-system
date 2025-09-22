<?php
// Simple test to check if unsubmit_request.php is working
session_start();

// Mock data for testing (remove this after testing)
$_SESSION['user_id'] = 1; // Replace with actual user ID for testing
$_POST['request_type'] = 'leave';
$_POST['request_id'] = '999'; // Use a non-existent ID for testing

// Include the unsubmit script
include 'unsubmit_request.php';
?>