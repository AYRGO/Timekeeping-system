<?php
/**
 * URL Shortener for WhatsApp links
 * Creates short, clickable links for WhatsApp
 */

require_once '../../../config/db.php';

// Create short_links table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS short_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        short_code VARCHAR(10) UNIQUE,
        original_url TEXT,
        request_id INT,
        action VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL
    )");
} catch (Exception $e) {
    // Table might already exist
}

/**
 * Create a short link for WhatsApp actions
 */
function createShortLink($originalUrl, $requestId, $action) {
    global $pdo;
    
    // Generate short code
    $shortCode = substr(md5($originalUrl . time()), 0, 6);
    
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO short_links (short_code, original_url, request_id, action) VALUES (?, ?, ?, ?)");
    $stmt->execute([$shortCode, $originalUrl, $requestId, $action]);
    
    return "http://localhost/Timekeeping-system/Public/Bugardi/quick-approval-system/whatsapp-notifications/s.php?c=" . $shortCode;
}

// Handle redirect
if (isset($_GET['c'])) {
    $shortCode = $_GET['c'];
    
    // Get original URL
    $stmt = $pdo->prepare("SELECT original_url, used_at FROM short_links WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($link) {
        // Mark as used
        if (!$link['used_at']) {
            $stmt = $pdo->prepare("UPDATE short_links SET used_at = NOW() WHERE short_code = ?");
            $stmt->execute([$shortCode]);
        }
        
        // Redirect to original URL
        header("Location: " . $link['original_url']);
        exit;
    } else {
        http_response_code(404);
        die("Link not found or expired");
    }
}
?>
