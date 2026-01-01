<?php
include('Public/config/db.php');

echo "<h2>Fix Video Loading Issue</h2>";

// Find announcements with video attachments that reference missing files
$stmt = $pdo->query("SELECT announcement_id, title, content, image FROM announcements WHERE image IS NOT NULL AND image != '' ORDER BY created_at DESC");

$fixedCount = 0;

while ($row = $stmt->fetch()) {
    $attachments = json_decode($row['image'], true);
    if ($attachments) {
        $needsUpdate = false;
        $newAttachments = [];
        
        foreach ($attachments as $file) {
            $filePath = "Public/views/uploads/" . $file;
            
            // If file doesn't exist, try to find a replacement
            if (!file_exists($filePath)) {
                echo "<p>❌ Missing file: $file for announcement #{$row['announcement_id']}</p>";
                
                // Check if it's the Employee Guide video
                if (strpos($file, 'Employee') !== false || strpos($file, 'Guide') !== false) {
                    // Replace with our copied file
                    if (file_exists("Public/views/uploads/New_Harley_2026_Employee_Guide.mp4")) {
                        $newAttachments[] = "New_Harley_2026_Employee_Guide.mp4";
                        $needsUpdate = true;
                        echo "<p>✅ Will replace with: New_Harley_2026_Employee_Guide.mp4</p>";
                    }
                } else {
                    $newAttachments[] = $file; // Keep as is
                }
            } else {
                $newAttachments[] = $file; // Keep existing working files
            }
        }
        
        // Update database if needed
        if ($needsUpdate && !empty($newAttachments)) {
            $newJson = json_encode($newAttachments);
            $updateStmt = $pdo->prepare("UPDATE announcements SET image = ? WHERE announcement_id = ?");
            $updateStmt->execute([$newJson, $row['announcement_id']]);
            echo "<p>✅ Updated announcement #{$row['announcement_id']}</p>";
            $fixedCount++;
        }
    }
}

echo "<h3>Summary: Fixed $fixedCount announcements</h3>";

// Also create a test link
echo '<h3>Test Video:</h3>';
echo '<video controls width="400" height="225">';
echo '<source src="Public/views/uploads/New_Harley_2026_Employee_Guide.mp4" type="video/mp4">';
echo 'Your browser does not support the video tag.';
echo '</video>';
?>