<?php
include('../config/db.php');

echo "<h2>Database Diagnostic - What's Stored?</h2>";

// Get the announcement
$stmt = $pdo->query("SELECT announcement_id, title, content, image FROM announcements WHERE title LIKE '%Employee Guide%' ORDER BY created_at DESC LIMIT 1");
$announcement = $stmt->fetch();

if ($announcement) {
    echo "<strong>Announcement ID:</strong> " . $announcement['announcement_id'] . "<br>";
    echo "<strong>Title:</strong> " . $announcement['title'] . "<br><br>";
    
    echo "<strong>Raw 'image' field from database:</strong><br>";
    echo "<pre>" . htmlspecialchars($announcement['image']) . "</pre><br>";
    
    echo "<strong>Decoded JSON:</strong><br>";
    $files = json_decode($announcement['image'], true);
    echo "<pre>" . print_r($files, true) . "</pre><br>";
    
    echo "<h3>Files in uploads folder:</h3>";
    $uploadDir = __DIR__ . '/../views/uploads/';
    $actualFiles = glob($uploadDir . '*.mp4');
    foreach ($actualFiles as $file) {
        echo "- " . basename($file) . "<br>";
    }
    
    echo "<br><h3>What the code will look for:</h3>";
    if (is_array($files)) {
        foreach ($files as $fileInfo) {
            $filePath = is_array($fileInfo) ? ($fileInfo['stored'] ?? $fileInfo['path'] ?? $fileInfo['filename'] ?? $fileInfo) : $fileInfo;
            $cleanFileName = basename($filePath);
            echo "Filename: <code>" . htmlspecialchars($cleanFileName) . "</code><br>";
            
            $fullPath = $uploadDir . $cleanFileName;
            echo "Looking at: " . htmlspecialchars($fullPath) . "<br>";
            echo "Exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "<br><br>";
        }
    }
    
    echo "<hr><h3>Fix Options:</h3>";
    
    // Show available MP4 files
    if (!empty($actualFiles)) {
        echo "<p>Found these MP4 files in uploads:</p>";
        foreach ($actualFiles as $file) {
            $filename = basename($file);
            echo "<form method='post' style='margin: 10px 0;'>";
            echo "<input type='hidden' name='fix_filename' value='" . htmlspecialchars($filename) . "'>";
            echo "<button type='submit' style='background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>";
            echo "Use this file: " . htmlspecialchars($filename);
            echo "</button>";
            echo "</form>";
        }
    }
} else {
    echo "❌ No announcement found with 'Employee Guide' in title";
}

// Handle fix
if (isset($_POST['fix_filename'])) {
    $newFilename = $_POST['fix_filename'];
    $newJson = json_encode([$newFilename]);
    
    $updateStmt = $pdo->prepare("UPDATE announcements SET image = ? WHERE announcement_id = ?");
    $result = $updateStmt->execute([$newJson, $announcement['announcement_id']]);
    
    if ($result) {
        echo "<div style='background: #d1fae5; padding: 15px; margin: 20px 0; border-radius: 8px;'>";
        echo "✅ <strong>Database Updated!</strong><br>";
        echo "Announcement now points to: " . htmlspecialchars($newFilename) . "<br>";
        echo "<a href='../views/announcement.php' style='color: #059669; text-decoration: underline;'>View Announcements</a>";
        echo "</div>";
    }
}
?>