<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = intval($_POST['leave_id']);
    $action = $_POST['action'];

    if (!in_array($action, ['approve', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', explanation = NULL WHERE id = :id");
            $stmt->execute(['id' => $leave_id]);
            $message = "Leave request #$leave_id has been approved.";
        } elseif ($action === 'decline') {
            $explanation = trim($_POST['explanation'] ?? '');
            if (empty($explanation)) {
                $message = "Explanation is required for declining.";
            } else {
                $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', explanation = :explanation WHERE id = :id");
                $stmt->execute(['explanation' => $explanation, 'id' => $leave_id]);
                $message = "Leave request #$leave_id has been rejected with explanation.";
            }
        }
    }
} else {
    $message = "Invalid form submission.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Action Result</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto mt-20 bg-white p-6 rounded shadow text-center">
        <h1 class="text-xl font-bold mb-4 text-gray-800">Action Result</h1>
        <p class="text-gray-700 mb-6"><?= htmlspecialchars($message) ?></p>
        <a href="leave_request_list.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
            ← Back to Leave Requests
        </a>
    </div>
</body>
</html>
