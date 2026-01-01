<!DOCTYPE html>
<html>
<head>
    <title>Video Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        video { border: 2px solid #ddd; margin: 10px 0; }
        .test-section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Video Loading Debug Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Direct File Path</h2>
        <video controls width="600" height="400" preload="metadata">
            <source src="/Timekeeping-system/Public/views/uploads/New_Harley_2026_Employee_Guide.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Alternative Path</h2>
        <video controls width="600" height="400" preload="metadata">
            <source src="Public/views/uploads/New_Harley_2026_Employee_Guide.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    
    <div class="test-section">
        <h2>Test 3: File Information</h2>
        <?php
        $videoFiles = [
            '/Timekeeping-system/Public/views/uploads/New_Harley_2026_Employee_Guide.mp4',
            'Public/views/uploads/New_Harley_2026_Employee_Guide.mp4'
        ];
        
        foreach ($videoFiles as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            echo "<strong>Path:</strong> $path<br>";
            echo "<strong>Full Path:</strong> $fullPath<br>";
            echo "<strong>Exists:</strong> " . (file_exists($fullPath) ? "YES" : "NO") . "<br>";
            
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                echo "<strong>Size:</strong> " . number_format($size) . " bytes (" . number_format($size / 1024 / 1024, 2) . " MB)<br>";
                echo "<strong>MIME:</strong> " . mime_content_type($fullPath) . "<br>";
                echo "<strong>Readable:</strong> " . (is_readable($fullPath) ? "YES" : "NO") . "<br>";
            }
            echo "<hr>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 4: Alternative Video Files</h2>
        <?php
        // Check if there are other video files we can use as backup
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Timekeeping-system/Public/views/uploads/';
        $alternativeFiles = [
            'New_Harley_2026_Employee_Guide.mp4',
            // Check original downloads
        ];
        
        // Also check if we have the original file
        $downloadsPath = 'C:\\Users\\resty\\Downloads\\New Harley 2026 Employee Guide.mp4';
        if (file_exists($downloadsPath)) {
            echo "<p>Original file exists in Downloads: " . number_format(filesize($downloadsPath)) . " bytes</p>";
            
            // Try to re-copy with better encoding
            $newPath = $uploadDir . 'Employee_Guide_2026.mp4';
            if (!file_exists($newPath)) {
                copy($downloadsPath, $newPath);
                echo "<p>Copied to: Employee_Guide_2026.mp4</p>";
            }
        }
        ?>
        
        <!-- Test with fresh copy -->
        <video controls width="600" height="400" preload="metadata">
            <source src="/Timekeeping-system/Public/views/uploads/Employee_Guide_2026.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    
    <script>
        // Add JavaScript error handling
        document.querySelectorAll('video').forEach((video, index) => {
            video.addEventListener('error', function(e) {
                console.error('Video ' + (index + 1) + ' error:', e);
                console.error('Error code:', this.error ? this.error.code : 'No error code');
                console.error('Error message:', this.error ? this.error.message : 'No error message');
            });
            
            video.addEventListener('loadstart', function() {
                console.log('Video ' + (index + 1) + ' started loading');
            });
            
            video.addEventListener('loadeddata', function() {
                console.log('Video ' + (index + 1) + ' loaded data');
            });
            
            video.addEventListener('canplay', function() {
                console.log('Video ' + (index + 1) + ' can play');
            });
        });
        
        // Check if files are accessible via fetch
        fetch('/Timekeeping-system/Public/views/uploads/New_Harley_2026_Employee_Guide.mp4', {method: 'HEAD'})
            .then(response => {
                console.log('File fetch response:', response.status, response.statusText);
                console.log('Content-Type:', response.headers.get('Content-Type'));
                console.log('Content-Length:', response.headers.get('Content-Length'));
            })
            .catch(error => {
                console.error('File fetch error:', error);
            });
    </script>
</body>
</html>