<?php
/**
 * Video Debug Script - Check why videos aren't playing
 * Access this on production: https://harley.resourcestaffonline.com/video_check.php
 */

include('Public/config/db.php');

echo "<h1>🎬 Video Loading Debug</h1>";
echo "<style>body{font-family:Arial;max-width:900px;margin:20px auto;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;} pre{background:#f5f5f5;padding:10px;overflow-x:auto;}</style>";

// Check server info
echo "<h2>1. Server Environment</h2>";
echo "<p>HTTP_HOST: <strong>" . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</strong></p>";
$isProduction = (strpos($_SERVER['HTTP_HOST'] ?? '', 'resourcestaffonline.com') !== false);
echo "<p>Is Production: <strong>" . ($isProduction ? 'YES' : 'NO (Local)') . "</strong></p>";
echo "<p>Document Root: <strong>" . $_SERVER['DOCUMENT_ROOT'] . "</strong></p>";

// Get announcement with video
echo "<h2>2. Database - Video Announcement</h2>";
$stmt = $pdo->query("SELECT announcement_id, title, image FROM announcements WHERE title LIKE '%Employee Guide%' OR image LIKE '%.mp4%' ORDER BY created_at DESC LIMIT 3");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($announcements)) {
    echo "<p class='error'>❌ No video announcements found!</p>";
} else {
    foreach ($announcements as $a) {
        echo "<div style='border:1px solid #ddd;padding:15px;margin:10px 0;'>";
        echo "<p><strong>Announcement ID:</strong> " . $a['announcement_id'] . "</p>";
        echo "<p><strong>Title:</strong> " . htmlspecialchars($a['title']) . "</p>";
        echo "<p><strong>Image/Attachments (raw):</strong></p>";
        echo "<pre>" . htmlspecialchars($a['image']) . "</pre>";
        
        // Parse the attachments
        $files = json_decode($a['image'], true);
        if (!$files) {
            $files = [$a['image']];
        }
        
        echo "<h3>Files Analysis:</h3>";
        foreach ($files as $idx => $fileInfo) {
            echo "<div style='background:#f9f9f9;padding:10px;margin:5px 0;'>";
            echo "<p><strong>File #" . ($idx + 1) . ":</strong></p>";
            
            // Get file path
            $filePath = is_array($fileInfo) ? ($fileInfo['stored'] ?? $fileInfo['path'] ?? $fileInfo['filename'] ?? json_encode($fileInfo)) : $fileInfo;
            $originalName = is_array($fileInfo) ? ($fileInfo['original'] ?? basename($filePath)) : basename($filePath);
            
            echo "<p>Original name: <code>" . htmlspecialchars($originalName) . "</code></p>";
            echo "<p>Stored path: <code>" . htmlspecialchars($filePath) . "</code></p>";
            
            // Clean filename
            $cleanFileName = basename($filePath);
            echo "<p>Clean filename: <code>" . htmlspecialchars($cleanFileName) . "</code></p>";
            
            // Build URL
            if ($isProduction) {
                $fileUrl = '/Public/views/uploads/' . $cleanFileName;
            } else {
                $fileUrl = '/Timekeeping-system/Public/views/uploads/' . $cleanFileName;
            }
            
            // URL encode
            $pathParts = explode('/', $fileUrl);
            $pathParts[count($pathParts) - 1] = rawurlencode($pathParts[count($pathParts) - 1]);
            $encodedFileUrl = implode('/', $pathParts);
            
            echo "<p>Generated URL: <code>" . htmlspecialchars($fileUrl) . "</code></p>";
            echo "<p>Encoded URL: <code>" . htmlspecialchars($encodedFileUrl) . "</code></p>";
            
            // Check if file exists on server
            $serverPath = $_SERVER['DOCUMENT_ROOT'] . $fileUrl;
            echo "<p>Server path: <code>" . htmlspecialchars($serverPath) . "</code></p>";
            
            if (file_exists($serverPath)) {
                $size = filesize($serverPath);
                $mime = mime_content_type($serverPath);
                echo "<p class='ok'>✅ FILE EXISTS!</p>";
                echo "<p>Size: " . number_format($size) . " bytes (" . number_format($size/1024/1024, 2) . " MB)</p>";
                echo "<p>MIME Type: " . $mime . "</p>";
                echo "<p>Readable: " . (is_readable($serverPath) ? "<span class='ok'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
                
                // Get file permissions
                $perms = substr(sprintf('%o', fileperms($serverPath)), -4);
                echo "<p>Permissions: " . $perms . "</p>";
            } else {
                echo "<p class='error'>❌ FILE DOES NOT EXIST at: $serverPath</p>";
                
                // Try to find the file
                echo "<h4>Searching for video files...</h4>";
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . ($isProduction ? '/Public/views/uploads/' : '/Timekeeping-system/Public/views/uploads/');
                if (is_dir($uploadDir)) {
                    $allFiles = scandir($uploadDir);
                    $videoFiles = array_filter($allFiles, function($f) {
                        return preg_match('/\.(mp4|webm|mov|avi)$/i', $f);
                    });
                    if (!empty($videoFiles)) {
                        echo "<p class='warn'>Found these video files in uploads:</p><ul>";
                        foreach ($videoFiles as $vf) {
                            echo "<li><code>" . htmlspecialchars($vf) . "</code></li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p class='error'>No video files found in uploads directory!</p>";
                    }
                } else {
                    echo "<p class='error'>Upload directory doesn't exist: $uploadDir</p>";
                }
            }
            
            // Extension check
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (empty($ext)) {
                $ext = strtolower(pathinfo($cleanFileName, PATHINFO_EXTENSION));
            }
            echo "<p>Extension: <code>" . $ext . "</code></p>";
            echo "<p>Is Video: " . (in_array($ext, ['mp4', 'webm', 'ogg', 'mov']) ? "<span class='ok'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
            
            echo "</div>";
        }
        echo "</div>";
    }
}

// Check uploads directory
echo "<h2>3. Uploads Directory</h2>";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . ($isProduction ? '/Public/views/uploads/' : '/Timekeeping-system/Public/views/uploads/');
echo "<p>Path: <code>" . $uploadDir . "</code></p>";

if (is_dir($uploadDir)) {
    echo "<p class='ok'>✅ Directory exists</p>";
    echo "<p>Writable: " . (is_writable($uploadDir) ? "<span class='ok'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    // List video files
    $files = scandir($uploadDir);
    $videoFiles = array_filter($files, function($f) {
        return preg_match('/\.(mp4|webm|mov|avi|ogg)$/i', $f);
    });
    
    echo "<h3>Video files in directory:</h3>";
    if (!empty($videoFiles)) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Filename</th><th>Size</th><th>MIME</th></tr>";
        foreach ($videoFiles as $vf) {
            $fullPath = $uploadDir . $vf;
            $size = filesize($fullPath);
            $mime = mime_content_type($fullPath);
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($vf) . "</code></td>";
            echo "<td>" . number_format($size/1024/1024, 2) . " MB</td>";
            echo "<td>" . $mime . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No video files found!</p>";
    }
} else {
    echo "<p class='error'>❌ Directory does not exist!</p>";
}

// Test video player
echo "<h2>4. Test Video Player</h2>";
if (!empty($videoFiles)) {
    $testVideo = reset($videoFiles);
    $testUrl = ($isProduction ? '/Public/views/uploads/' : '/Timekeeping-system/Public/views/uploads/') . rawurlencode($testVideo);
    
    echo "<p>Testing with: <code>" . htmlspecialchars($testVideo) . "</code></p>";
    echo "<p>URL: <code>" . htmlspecialchars($testUrl) . "</code></p>";
    
    echo '<video id="testVideo" controls width="600" height="400" style="background:black;border:2px solid green;" preload="auto" playsinline>';
    echo '<source src="' . $testUrl . '" type="video/mp4">';
    echo 'Your browser does not support the video tag.';
    echo '</video>';
    
    echo '<div id="videoStatus" style="margin-top:10px;padding:10px;background:#f5f5f5;"></div>';
    
    echo '<script>
    const video = document.getElementById("testVideo");
    const status = document.getElementById("videoStatus");
    
    video.addEventListener("loadstart", () => status.innerHTML += "<p>Loading started...</p>");
    video.addEventListener("loadedmetadata", () => status.innerHTML += "<p class=\"ok\">✅ Metadata loaded! Duration: " + video.duration + "s</p>");
    video.addEventListener("loadeddata", () => status.innerHTML += "<p class=\"ok\">✅ Data loaded!</p>");
    video.addEventListener("canplay", () => status.innerHTML += "<p class=\"ok\">✅ Can play!</p>");
    video.addEventListener("error", (e) => {
        status.innerHTML += "<p class=\"error\">❌ Error: " + (video.error ? video.error.message : "Unknown error") + "</p>";
        status.innerHTML += "<p class=\"error\">Error code: " + (video.error ? video.error.code : "N/A") + "</p>";
    });
    
    // Also try fetch to check if file is accessible
    fetch("' . $testUrl . '", {method: "HEAD"})
        .then(r => {
            status.innerHTML += "<p>Fetch status: " + r.status + " " + r.statusText + "</p>";
            status.innerHTML += "<p>Content-Type: " + r.headers.get("Content-Type") + "</p>";
            status.innerHTML += "<p>Content-Length: " + r.headers.get("Content-Length") + "</p>";
        })
        .catch(e => status.innerHTML += "<p class=\"error\">Fetch error: " + e.message + "</p>");
    </script>';
}

// Direct link test
echo "<h2>5. Direct Link Test</h2>";
if (!empty($videoFiles)) {
    $testVideo = reset($videoFiles);
    $directUrl = ($isProduction ? 'https://harley.resourcestaffonline.com/Public/views/uploads/' : 'http://localhost/Timekeeping-system/Public/views/uploads/') . rawurlencode($testVideo);
    echo "<p>Try opening this URL directly in a new tab:</p>";
    echo "<p><a href='" . $directUrl . "' target='_blank'>" . htmlspecialchars($directUrl) . "</a></p>";
}

// Check .htaccess
echo "<h2>6. Server Configuration</h2>";
$htaccessPath = $uploadDir . '.htaccess';
if (file_exists($htaccessPath)) {
    echo "<p class='ok'>✅ .htaccess exists in uploads</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
} else {
    echo "<p class='warn'>⚠️ No .htaccess in uploads directory</p>";
}
?>
