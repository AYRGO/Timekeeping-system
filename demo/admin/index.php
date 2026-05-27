<?php
date_default_timezone_set('Asia/Manila');

session_start();

require_once __DIR__ . '/../../Public/config/db.php';
require_once __DIR__ . '/../../Public/config/demo_helper.php';

try {
    $employee = demo_prepare_admin_account($pdo);
    demo_login_admin($employee);

    header('Location: ../../Public/views/admin_homepage.php?demo=admin');
    exit;
} catch (Throwable $e) {
    error_log('Admin demo login failed: ' . $e->getMessage());
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Demo Unavailable</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">
    <main class="max-w-md w-full bg-white border border-gray-200 rounded-xl shadow p-8 text-center">
        <h1 class="text-2xl font-semibold text-gray-900 mb-3">Admin demo is temporarily unavailable</h1>
        <p class="text-gray-600">The protected demo admin account could not be prepared right now.</p>
        <a href="../" class="inline-flex mt-6 px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700">Open Employee Demo</a>
    </main>
</body>
</html>
