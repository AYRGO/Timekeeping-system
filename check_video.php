<?php
include('Public/config/db.php');

// Get the announcement with the video issue
$stmt = $pdo->query("SELECT announcement_id, title, content, image, created_at FROM announcements WHERE content LIKE '%Employee Guide%' OR title LIKE '%Employee Guide%' ORDER BY created_at DESC LIMIT 5");

echo "<h2>Announcement with Video Issue:</h2>";
while ($row = $stmt->fetch()) {
    echo "<strong>ID:</strong> " . $row['announcement_id'] . "<br>";
    echo "<strong>Title:</strong> " . $row['title'] . "<br>";
    echo "<strong>Content:</strong> " . substr($row['content'], 0, 200) . "...<br>";
    echo "<strong>Attachments:</strong> " . $row['image'] . "<br>";
    echo "<strong>Created:</strong> " . $row['created_at'] . "<br>";
    echo "<hr>";
    
    // Check if attachments exist
    if ($row['image']) {
        $attachments = json_decode($row['image'], true);
        if ($attachments) {
            foreach ($attachments as $file) {
                $filePath = "Public/views/uploads/" . $file;
                $fullPath = __DIR__ . "/" . $filePath;
                echo "<strong>File:</strong> " . $file . "<br>";
                echo "<strong>Path:</strong> " . $filePath . "<br>";
                echo "<strong>Exists:</strong> " . (file_exists($fullPath) ? "YES" : "NO") . "<br>";
                if (file_exists($fullPath)) {
                    echo "<strong>Size:</strong> " . number_format(filesize($fullPath)) . " bytes<br>";
                }
                echo "<br>";
            }
        }
    }
}
?>