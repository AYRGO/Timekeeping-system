<!DOCTYPE html>
<html>
<head>
    <title>Video Test</title>
</head>
<body>
    <h2>Direct Video Test</h2>
    <video controls width="800" height="450">
        <source src="Public/views/uploads/New_Harley_2026_Employee_Guide.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <h3>File Information:</h3>
    <?php
    $videoFile = "Public/views/uploads/New_Harley_2026_Employee_Guide.mp4";
    if (file_exists($videoFile)) {
        echo "<p>✅ File exists!</p>";
        echo "<p>Size: " . number_format(filesize($videoFile)) . " bytes</p>";
        echo "<p>Path: " . realpath($videoFile) . "</p>";
        
        // Check file permissions
        echo "<p>Readable: " . (is_readable($videoFile) ? "Yes" : "No") . "</p>";
        
        // Check MIME type
        echo "<p>MIME type: " . mime_content_type($videoFile) . "</p>";
    } else {
        echo "<p>❌ File does not exist at: $videoFile</p>";
    }
    ?>
</body>
</html>