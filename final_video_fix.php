<!DOCTYPE html>
<html>
<head>
    <title>Final Video Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        .success { background: #d1fae5; border: 2px solid #10b981; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #fee2e2; border: 2px solid #ef4444; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { background: #dbeafe; border: 2px solid #3b82f6; padding: 15px; border-radius: 8px; margin: 15px 0; }
        button { background: #10b981; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #059669; }
        video { width: 100%; max-width: 600px; margin: 20px auto; display: block; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎥 Final Video Fix - Employee Guide 2026</h1>
    
    <?php
    include('Public/config/db.php');
    
    echo "<h2>Step 1: Check Current Database</h2>";
    
    // Find the Employee Guide announcement
    $stmt = $pdo->prepare("SELECT announcement_id, title, content, image FROM announcements WHERE title LIKE '%Employee Guide%' OR content LIKE '%Employee Guide%' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $announcement = $stmt->fetch();
    
    if ($announcement) {
        echo "<div class='info'>";
        echo "<strong>Found announcement:</strong> " . htmlspecialchars($announcement['title']) . "<br>";
        echo "<strong>ID:</strong> " . $announcement['announcement_id'] . "<br>";
        echo "<strong>Current attachments:</strong> " . htmlspecialchars($announcement['image']) . "<br>";
        echo "</div>";
        
        // Check which video files exist
        echo "<h2>Step 2: Check Available Video Files</h2>";
        $uploadDir = __DIR__ . '/Public/views/uploads/';
        $possibleFiles = [
            'employee_guide.mp4',
            'New_Harley_2026_Employee_Guide.mp4',
            'New Harley 2026 Employee Guide.mp4'
        ];
        
        $workingFile = null;
        foreach ($possibleFiles as $file) {
            $fullPath = $uploadDir . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                echo "<div class='success'>";
                echo "✅ <strong>Found:</strong> $file<br>";
                echo "<strong>Size:</strong> " . number_format($size) . " bytes (" . number_format($size / 1024 / 1024, 2) . " MB)<br>";
                echo "<strong>Path:</strong> $fullPath<br>";
                echo "</div>";
                if (!$workingFile) $workingFile = $file;
            } else {
                echo "<div class='error'>❌ <strong>Not found:</strong> $file</div>";
            }
        }
        
        if ($workingFile) {
            echo "<h2>Step 3: Update Database</h2>";
            
            if (isset($_POST['fix_now'])) {
                $newAttachment = json_encode([$workingFile]);
                $updateStmt = $pdo->prepare("UPDATE announcements SET image = ? WHERE announcement_id = ?");
                $result = $updateStmt->execute([$newAttachment, $announcement['announcement_id']]);
                
                if ($result) {
                    echo "<div class='success'>";
                    echo "<h3>✅ SUCCESS! Database Updated</h3>";
                    echo "<strong>New attachment value:</strong> " . htmlspecialchars($newAttachment) . "<br>";
                    echo "</div>";
                    
                    // Reload the announcement
                    $stmt->execute();
                    $announcement = $stmt->fetch();
                } else {
                    echo "<div class='error'>❌ Failed to update database</div>";
                }
            } else {
                echo "<div class='info'>";
                echo "<p><strong>Ready to update!</strong></p>";
                echo "<p>Will update announcement to use: <strong>$workingFile</strong></p>";
                echo "<form method='post'>";
                echo "<button type='submit' name='fix_now'>🔧 Fix Now - Update Database</button>";
                echo "</form>";
                echo "</div>";
            }
            
            echo "<h2>Step 4: Test Video Playback</h2>";
            
            // Test both path formats
            $isProduction = (strpos($_SERVER['HTTP_HOST'] ?? '', 'resourcestaffonline.com') !== false);
            if ($isProduction) {
                $testUrl = '/Public/views/uploads/' . $workingFile;
            } else {
                $testUrl = '/Timekeeping-system/Public/views/uploads/' . $workingFile;
            }
            
            echo "<div class='info'>";
            echo "<p><strong>Video URL:</strong> " . htmlspecialchars($testUrl) . "</p>";
            echo "<p><strong>Environment:</strong> " . ($isProduction ? "Production" : "Local Development") . "</p>";
            echo "</div>";
            
            echo "<video controls preload='metadata'>";
            echo "<source src='" . htmlspecialchars($testUrl) . "' type='video/mp4'>";
            echo "Your browser does not support the video tag.";
            echo "</video>";
            
            echo "<div class='info'>";
            echo "<p><strong>If the video plays above, the fix is complete!</strong></p>";
            echo "<p>Go back to your announcement page and refresh to see the working video.</p>";
            echo "</div>";
            
        } else {
            echo "<div class='error'>";
            echo "<h3>❌ No video files found!</h3>";
            echo "<p>Please copy the video file to: <code>" . $uploadDir . "</code></p>";
            echo "<p>Use one of these names: employee_guide.mp4</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ No Employee Guide announcement found in database</div>";
    }
    ?>
    
    <h2>Quick Actions</h2>
    <div class='info'>
        <p><a href='Public/views/announcement.php' style='color: #2563eb; text-decoration: underline;'>📢 Go to Admin Announcements</a></p>
        <p><a href='Public/module/announcementFeed.php' style='color: #2563eb; text-decoration: underline;'>👥 Go to Employee Feed</a></p>
    </div>
</div>

<script>
    // Add video error handling
    const videos = document.querySelectorAll('video');
    videos.forEach((video, index) => {
        video.addEventListener('error', function(e) {
            console.error('Video error:', {
                index: index,
                src: this.currentSrc,
                error: this.error ? {
                    code: this.error.code,
                    message: this.error.message
                } : 'No error object'
            });
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.innerHTML = '<strong>Video Error:</strong> Error code ' + (this.error ? this.error.code : 'unknown');
            video.parentNode.insertBefore(errorDiv, video.nextSibling);
        });
        
        video.addEventListener('loadeddata', function() {
            console.log('✅ Video loaded successfully');
        });
    });
</script>
</body>
</html>