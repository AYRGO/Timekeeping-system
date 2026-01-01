<?php
include('Public/config/db.php');

echo "<h2>Fix Employee Guide Video - Final Solution</h2>";

// Find the announcement with the broken video
$stmt = $pdo->prepare("SELECT announcement_id, title, content, image FROM announcements WHERE title LIKE '%Employee Guide%' ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$announcement = $stmt->fetch();

if ($announcement) {
    echo "<p><strong>Found announcement:</strong> " . $announcement['title'] . " (ID: " . $announcement['announcement_id'] . ")</p>";
    
    // Update with the working video file
    $newAttachment = json_encode(["employee_guide.mp4"]);
    
    $updateStmt = $pdo->prepare("UPDATE announcements SET image = ? WHERE announcement_id = ?");
    $result = $updateStmt->execute([$newAttachment, $announcement['announcement_id']]);
    
    if ($result) {
        echo "<p>✅ <strong>Successfully updated the video reference!</strong></p>";
    } else {
        echo "<p>❌ Failed to update database</p>";
    }
    
    echo "<h3>Test the video now:</h3>";
    echo '<video controls width="600" height="400" preload="metadata" style="border: 2px solid #4CAF50;">';
    echo '<source src="/Timekeeping-system/Public/views/uploads/employee_guide.mp4" type="video/mp4">';
    echo 'Your browser does not support the video tag.';
    echo '</video>';
    
    echo "<h3>File Information:</h3>";
    $videoPath = $_SERVER['DOCUMENT_ROOT'] . '/Timekeeping-system/Public/views/uploads/employee_guide.mp4';
    if (file_exists($videoPath)) {
        $size = filesize($videoPath);
        echo "<p>✅ File exists</p>";
        echo "<p>Size: " . number_format($size) . " bytes (" . number_format($size / 1024 / 1024, 2) . " MB)</p>";
        echo "<p>MIME Type: " . mime_content_type($videoPath) . "</p>";
    } else {
        echo "<p>❌ File not found at: $videoPath</p>";
    }
    
} else {
    echo "<p>❌ No Employee Guide announcement found</p>";
}

// Also create a completely new announcement as backup
echo "<hr><h3>Alternative: Create New Announcement</h3>";
echo '<form method="post" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">';
echo '<input type="hidden" name="create_new" value="1">';
echo '<p><strong>Create a fresh Employee Guide announcement with working video?</strong></p>';
echo '<button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px;">Create New Announcement</button>';
echo '</form>';

if (isset($_POST['create_new'])) {
    $title = "Harley Employee Guide 2026";
    $content = "Hey everyone! Please check out this video for the new Harley Employee Guide 2026 — it highlights all the key updates and shows the before-and-after changes you need to know.";
    $attachments = json_encode(["employee_guide.mp4"]);
    $createdAt = date('Y-m-d H:i:s');
    
    $insertStmt = $pdo->prepare("INSERT INTO announcements (title, content, admin_name, created_at, image, deleted) VALUES (?, ?, ?, ?, ?, 0)");
    $result = $insertStmt->execute([$title, $content, 'Admin', $createdAt, $attachments]);
    
    if ($result) {
        echo "<p>✅ <strong>New announcement created successfully!</strong></p>";
        echo "<p>You can now delete the old broken announcement if needed.</p>";
    } else {
        echo "<p>❌ Failed to create new announcement</p>";
    }
}
?>