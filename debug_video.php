<?php
include('Public/config/db.php');

// Get the specific announcement with the employee guide video
$stmt = $pdo->prepare("SELECT announcement_id, title, content, image FROM announcements WHERE title LIKE '%Employee Guide%' ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$announcement = $stmt->fetch();

if ($announcement) {
    echo "<h2>Employee Guide Announcement Details:</h2>";
    echo "<strong>ID:</strong> " . $announcement['announcement_id'] . "<br>";
    echo "<strong>Title:</strong> " . $announcement['title'] . "<br>";
    echo "<strong>Attachments JSON:</strong> " . $announcement['image'] . "<br><br>";
    
    if ($announcement['image']) {
        $attachments = json_decode($announcement['image'], true);
        if ($attachments) {
            echo "<h3>Processing Each Attachment:</h3>";
            foreach ($attachments as $index => $fileInfo) {
                echo "<strong>Attachment #" . ($index + 1) . ":</strong><br>";
                echo "Raw data: " . json_encode($fileInfo) . "<br>";
                
                // Process the file info (same logic as in announcement.php)
                if (is_string($fileInfo)) {
                    $filePath = $fileInfo;
                    $originalName = basename($fileInfo);
                } else {
                    $filePath = $fileInfo['path'] ?? $fileInfo['filename'] ?? $fileInfo;
                    $originalName = $fileInfo['original'] ?? basename($filePath);
                }
                
                $cleanFileName = basename($filePath);
                $isProduction = false; // We're on local
                
                if ($isProduction) {
                    $fileUrl = '/Public/views/uploads/' . $cleanFileName;
                } else {
                    $fileUrl = '/Timekeeping-system/Public/views/uploads/' . $cleanFileName;
                }
                
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileUrl;
                
                echo "File path: $filePath<br>";
                echo "Clean filename: $cleanFileName<br>";
                echo "File URL: $fileUrl<br>";
                echo "Full server path: $fullPath<br>";
                echo "File exists: " . (file_exists($fullPath) ? "YES" : "NO") . "<br>";
                
                if (file_exists($fullPath)) {
                    echo "File size: " . number_format(filesize($fullPath)) . " bytes<br>";
                    echo "File type: " . mime_content_type($fullPath) . "<br>";
                }
                
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'mpeg', '3gp']);
                
                echo "Extension: $ext<br>";
                echo "Is video: " . ($isVideo ? "YES" : "NO") . "<br>";
                echo "<br>";
                
                // If it's a video, create a test player
                if ($isVideo) {
                    echo "<h4>Test Video Player:</h4>";
                    echo '<video controls width="400" height="225" style="background: black;">';
                    echo '<source src="' . htmlspecialchars($fileUrl) . '" type="video/' . ($ext === 'mov' ? 'quicktime' : ($ext === 'avi' ? 'x-msvideo' : $ext)) . '">';
                    echo 'Your browser does not support the video tag.';
                    echo '</video><br><br>';
                }
            }
        }
    }
} else {
    echo "No Employee Guide announcement found.";
}
?>