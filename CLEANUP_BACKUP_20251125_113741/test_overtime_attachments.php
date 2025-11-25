<?php
// Test file to verify overtime attachment functionality
require_once 'Public/config/database.php';

// Test 1: Check if overtime_requests table has attachment_ot column
echo "<h2>Test 1: Check overtime_requests table structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE overtime_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasAttachmentOt = false;
    
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        if ($col['Field'] === 'attachment_ot') {
            $hasAttachmentOt = true;
        }
    }
    echo "</table>";
    
    echo $hasAttachmentOt ? "<p style='color:green'>✓ attachment_ot column exists</p>" : "<p style='color:red'>✗ attachment_ot column missing</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test 2: Check if post_ot_requests table has attachment column
echo "<h2>Test 2: Check post_ot_requests table structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE post_ot_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasAttachment = false;
    
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        if ($col['Field'] === 'attachment') {
            $hasAttachment = true;
        }
    }
    echo "</table>";
    
    echo $hasAttachment ? "<p style='color:green'>✓ attachment column exists</p>" : "<p style='color:red'>✗ attachment column missing</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test 3: Check if post2_overtime_requests table has attachment column
echo "<h2>Test 3: Check post2_overtime_requests table structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE post2_overtime_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasAttachment = false;
    
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        if ($col['Field'] === 'attachment') {
            $hasAttachment = true;
        }
    }
    echo "</table>";
    
    echo $hasAttachment ? "<p style='color:green'>✓ attachment column exists</p>" : "<p style='color:red'>✗ attachment column missing</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Test 4: Check if uploads/overtime_attachments directory exists
echo "<h2>Test 4: Check uploads/overtime_attachments directory</h2>";
$otDir = "uploads/overtime_attachments";
if (is_dir($otDir)) {
    echo "<p style='color:green'>✓ Directory exists: {$otDir}</p>";
    
    // Check if it's writable
    if (is_writable($otDir)) {
        echo "<p style='color:green'>✓ Directory is writable</p>";
    } else {
        echo "<p style='color:orange'>⚠ Directory exists but may not be writable</p>";
    }
    
    // List any existing files
    $files = scandir($otDir);
    $attachmentFiles = array_filter($files, function($file) {
        return $file !== '.' && $file !== '..' && strpos($file, 'ot_') === 0;
    });
    
    if (count($attachmentFiles) > 0) {
        echo "<p>Found " . count($attachmentFiles) . " overtime attachment files:</p><ul>";
        foreach ($attachmentFiles as $file) {
            echo "<li>{$file}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No overtime attachment files found (this is normal if no overtime attachments have been uploaded)</p>";
    }
    
} else {
    echo "<p style='color:red'>✗ Directory does not exist: {$otDir}</p>";
    echo "<p>Attempting to create directory...</p>";
    
    if (mkdir($otDir, 0755, true)) {
        echo "<p style='color:green'>✓ Directory created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create directory</p>";
    }
}

// Test 5: Sample overtime requests with attachments
echo "<h2>Test 5: Sample overtime requests with attachments</h2>";
try {
    // Check pending overtime requests with attachments
    $stmt = $pdo->query("SELECT id, employee_id, date, reason, attachment_ot FROM overtime_requests WHERE attachment_ot IS NOT NULL AND attachment_ot != '' LIMIT 5");
    $pendingOT = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pendingOT) > 0) {
        echo "<p style='color:green'>✓ Found " . count($pendingOT) . " pending overtime requests with attachments:</p>";
        echo "<table border='1'><tr><th>ID</th><th>Employee</th><th>Date</th><th>Attachment</th></tr>";
        foreach ($pendingOT as $ot) {
            echo "<tr><td>{$ot['id']}</td><td>{$ot['employee_id']}</td><td>{$ot['date']}</td><td>{$ot['attachment_ot']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No pending overtime requests with attachments found</p>";
    }
    
    // Check processed overtime requests with attachments
    $stmt = $pdo->query("SELECT id, employee_id, ot_type, attachment FROM post_ot_requests WHERE attachment IS NOT NULL AND attachment != '' LIMIT 5");
    $processedOT = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($processedOT) > 0) {
        echo "<p style='color:green'>✓ Found " . count($processedOT) . " processed overtime requests with attachments:</p>";
        echo "<table border='1'><tr><th>ID</th><th>Employee</th><th>OT Type</th><th>Attachment</th></tr>";
        foreach ($processedOT as $ot) {
            echo "<tr><td>{$ot['id']}</td><td>{$ot['employee_id']}</td><td>{$ot['ot_type']}</td><td>{$ot['attachment']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No processed overtime requests with attachments found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The overtime attachment system should be working if:</p>";
echo "<ul>";
echo "<li>✓ Database tables have the correct attachment columns</li>";
echo "<li>✓ uploads/overtime_attachments directory exists and is writable</li>";
echo "<li>✓ Controllers (upload_attachment.php, view_attachment.php) support overtime attachments</li>";
echo "<li>✓ Frontend (recent_activity_card.php) calls createAttachmentSection for overtime requests</li>";
echo "<li>✓ Backend (notification_modal.php) includes attachment fields in queries</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong> Test by opening an overtime request in the Recent Activity modal to see if the Supporting Documents section appears.</p>";
?>