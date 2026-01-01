<?php
/**
 * Fix Video Database References
 * This updates the announcement to point to an existing video file
 */

include('Public/config/db.php');

echo "<h1>🔧 Fix Video Database</h1>";
echo "<style>body{font-family:Arial;max-width:600px;margin:20px auto;padding:20px;} .ok{color:green;font-weight:bold;} .error{color:red;}</style>";

// The correct filename that EXISTS on the server
$correctFilename = 'Employee_Guide_2026.mp4';
$correctJson = json_encode([
    [
        'original' => 'New Harley 2026 Employee Guide.mp4',
        'stored' => 'uploads/' . $correctFilename
    ]
]);

echo "<p>Will update to use: <code>$correctFilename</code></p>";
echo "<p>JSON: <code>" . htmlspecialchars($correctJson) . "</code></p>";

// Check if form submitted
if (isset($_POST['fix'])) {
    $announcementId = (int)$_POST['announcement_id'];
    
    $stmt = $pdo->prepare("UPDATE announcements SET image = ? WHERE announcement_id = ?");
    $result = $stmt->execute([$correctJson, $announcementId]);
    
    if ($result) {
        echo "<p class='ok'>✅ Successfully updated announcement #$announcementId!</p>";
        echo "<p><a href='Public/views/announcement.php'>Go check the announcement page →</a></p>";
    } else {
        echo "<p class='error'>❌ Failed to update</p>";
    }
}

// Show announcements that need fixing
echo "<h2>Announcements with Video Issues:</h2>";

$stmt = $pdo->query("SELECT announcement_id, title, image FROM announcements WHERE image LIKE '%.mp4%' ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($announcements as $a) {
    $files = json_decode($a['image'], true);
    $needsFix = false;
    
    if ($files) {
        foreach ($files as $f) {
            $storedFile = is_array($f) ? ($f['stored'] ?? '') : $f;
            $filename = basename($storedFile);
            $serverPath = $_SERVER['DOCUMENT_ROOT'] . '/Public/views/uploads/' . $filename;
            
            if (!file_exists($serverPath)) {
                $needsFix = true;
            }
        }
    }
    
    echo "<div style='border:1px solid " . ($needsFix ? 'red' : 'green') . ";padding:15px;margin:10px 0;border-radius:5px;'>";
    echo "<p><strong>ID:</strong> " . $a['announcement_id'] . "</p>";
    echo "<p><strong>Title:</strong> " . htmlspecialchars($a['title']) . "</p>";
    echo "<p><strong>Current:</strong> <code>" . htmlspecialchars($a['image']) . "</code></p>";
    
    if ($needsFix) {
        echo "<p class='error'>❌ File missing - needs fix</p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='announcement_id' value='" . $a['announcement_id'] . "'>";
        echo "<button type='submit' name='fix' style='background:green;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>Fix This Announcement</button>";
        echo "</form>";
    } else {
        echo "<p class='ok'>✅ File exists</p>";
    }
    
    echo "</div>";
}
?>
