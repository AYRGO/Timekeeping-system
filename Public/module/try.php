<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

// Force session regeneration
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// Check if user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = ''; // success or error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $message = "Invalid CSRF token.";
        $message_type = 'error';
    } else {
        $content = trim($_POST['content'] ?? '');
        $today = date('Y-m-d');

        try {
            // Check for existing request today
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM test_sql WHERE employee_id = ? AND created_date = ?");
            $stmt->execute([$employee_id, $today]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $message = "You have already submitted a request today.";
                $message_type = 'error';
            } else {
                // Insert request
                $stmt = $pdo->prepare("INSERT INTO test_sql (employee_id, content, created_date) VALUES (?, ?, ?)");
                $stmt->execute([$employee_id, $content, $today]);

                $message = "Request submitted successfully.";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Form</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .box { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h2>Submit Daily Request</h2>

    <?php if ($message): ?>
        <div class="box <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="content">Request Content:</label><br>
        <textarea name="content" id="content" rows="4" cols="50" required></textarea><br><br>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit">Submit Request</button>
    </form>
</body>
</html>
