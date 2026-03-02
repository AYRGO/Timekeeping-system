<?php
/**
 * CSRF Token Refresh Endpoint
 * Called via AJAX to keep the CSRF token fresh for employees
 * who keep the time logging page open for extended periods.
 */
session_start();
date_default_timezone_set('Asia/Manila');
include_once('../config/csrf_helper.php');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verify the request is from an authenticated employee
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Generate a fresh CSRF token with extended timeout (8 hours)
$token = generate_csrf_token(28800);

echo json_encode([
    'success' => true,
    'csrf_token' => $token
]);
