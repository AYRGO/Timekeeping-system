<?php
/**
 * Redirect from old scott_ot_approval.php to new quick approval system
 */

// Get the token and type from the URL
$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'temporary';

// Build the new URL
$newUrl = "quick-approval-system/approval-pages/quick_ot_approval.php";

// Add parameters if they exist
$params = [];
if ($token) $params[] = "token=" . urlencode($token);
if ($type) $params[] = "type=" . urlencode($type);

if (!empty($params)) {
    $newUrl .= "?" . implode("&", $params);
}

// Redirect to the new location
header("Location: " . $newUrl);
exit();
?>
