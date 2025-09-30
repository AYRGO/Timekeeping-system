<?php
// Test attachment functionality
require_once 'config/db.php';

echo "<h2>Testing Attachment Functionality</h2>";

try {
    // Check for requests with attachments
    $stmt = $pdo->prepare("SELECT id, employee_id, ot_date, attachment FROM post_ot_requests WHERE attachment IS NOT NULL AND attachment != '' LIMIT 5");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Requests with attachments:</h3>";
    if (empty($requests)) {
        echo "<p>No requests with attachments found.</p>";
        
        // Check all requests structure
        $allStmt = $pdo->prepare("SELECT id, employee_id, ot_date, attachment FROM post_ot_requests LIMIT 3");
        $allStmt->execute();
        $allRequests = $allStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample requests (showing attachment column):</h3>";
        echo "<pre>";
        print_r($allRequests);
        echo "</pre>";
        
    } else {
        echo "<pre>";
        print_r($requests);
        echo "</pre>";
        
        // Check if files exist
        echo "<h3>File existence check:</h3>";
        foreach ($requests as $request) {
            $filePath = 'uploads/' . $request['attachment'];
            $exists = file_exists($filePath);
            echo "<p>Request {$request['id']}: {$request['attachment']} - " . ($exists ? "EXISTS" : "NOT FOUND") . "</p>";
        }
    }
    
    // Check uploads directory
    $uploadsDir = 'uploads/';
    if (is_dir($uploadsDir)) {
        echo "<h3>Files in uploads directory:</h3>";
        $files = scandir($uploadsDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "<p>$file</p>";
            }
        }
    } else {
        echo "<p>Uploads directory not found or not accessible.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>