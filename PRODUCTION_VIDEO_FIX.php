<!DOCTYPE html>
<html>
<head>
    <title>Production Video Fix Instructions</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }
        .step { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
        .command { background: #1e293b; color: #10b981; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
        h1 { color: #1e40af; }
        h2 { color: #3b82f6; margin-top: 30px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>

<h1>🔧 Production Video Fix Guide</h1>
<p><strong>For:</strong> harley.resourcestaffonline.com</p>

<div class="warning">
    <strong>⚠️ Current Issue:</strong><br>
    The video file "New Harley 2026 Employee Guide.mp4" exists on your LOCAL computer but NOT on your PRODUCTION server. 
    The announcement is trying to load a video that doesn't exist on Hostinger.
</div>

<h2>Step 1: Upload Video to Production Server</h2>

<div class="step">
    <strong>Option A: Using cPanel File Manager</strong>
    <ol>
        <li>Login to your Hostinger cPanel: <code>https://hpanel.hostinger.com</code></li>
        <li>Go to <strong>File Manager</strong></li>
        <li>Navigate to: <code>public_html/Public/views/uploads/</code></li>
        <li>Click <strong>Upload</strong></li>
        <li>Upload the file from: <code>C:\Users\resty\Downloads\New Harley 2026 Employee Guide.mp4</code></li>
        <li>Rename the uploaded file to: <code>employee_guide.mp4</code></li>
    </ol>
</div>

<div class="step">
    <strong>Option B: Using FTP (FileZilla)</strong>
    <ol>
        <li>Open FileZilla and connect to your Hostinger server</li>
        <li>Navigate to: <code>/public_html/Public/views/uploads/</code></li>
        <li>Upload: <code>C:\Users\resty\Downloads\New Harley 2026 Employee Guide.mp4</code></li>
        <li>Rename to: <code>employee_guide.mp4</code></li>
    </ol>
</div>

<h2>Step 2: Update Database on Production</h2>

<div class="step">
    <strong>Access phpMyAdmin on Production</strong>
    <ol>
        <li>Login to Hostinger cPanel</li>
        <li>Go to <strong>phpMyAdmin</strong></li>
        <li>Select database: <code>u816220874_calendartype</code></li>
        <li>Click <strong>SQL</strong> tab</li>
        <li>Run this query:</li>
    </ol>
    
    <div class="command">
UPDATE `announcements` 
SET `image` = '["employee_guide.mp4"]' 
WHERE `title` LIKE '%Employee Guide%' 
ORDER BY `created_at` DESC 
LIMIT 1;
    </div>
</div>

<h2>Step 3: Verify the Video File</h2>

<div class="step">
    <strong>Test the video URL directly:</strong>
    <ol>
        <li>After uploading, visit this URL in your browser:</li>
        <li><code>https://harley.resourcestaffonline.com/Public/views/uploads/employee_guide.mp4</code></li>
        <li>The video should start downloading or playing</li>
        <li>If you get a 404 error, the file wasn't uploaded to the correct location</li>
    </ol>
</div>

<h2>Step 4: Check Permissions</h2>

<div class="step">
    <strong>Ensure proper file permissions:</strong>
    <ol>
        <li>In cPanel File Manager, right-click on <code>employee_guide.mp4</code></li>
        <li>Select <strong>Change Permissions</strong></li>
        <li>Set permissions to: <code>644</code> (or check: Owner-Read+Write, Group-Read, Public-Read)</li>
        <li>Click <strong>Change Permissions</strong></li>
    </ol>
</div>

<div class="success">
    <strong>✅ After completing these steps:</strong><br>
    1. Visit: https://harley.resourcestaffonline.com/Public/views/announcement.php<br>
    2. The video should now load and play correctly<br>
    3. Employee view at announcementFeed.php should also work
</div>

<h2>Quick Checklist</h2>

<div class="step">
    <input type="checkbox"> Video file uploaded to: <code>/public_html/Public/views/uploads/employee_guide.mp4</code><br>
    <input type="checkbox"> File renamed to: <code>employee_guide.mp4</code> (simple name, no spaces)<br>
    <input type="checkbox"> Database updated with SQL query<br>
    <input type="checkbox"> File permissions set to 644<br>
    <input type="checkbox"> Tested video URL directly in browser<br>
    <input type="checkbox"> Checked announcement page - video plays
</div>

<h2>Alternative: Create New Announcement with Video</h2>

<div class="step">
    <strong>If updating the database is difficult:</strong>
    <ol>
        <li>After uploading the video file to production</li>
        <li>Login to your admin panel</li>
        <li>Create a NEW announcement</li>
        <li>Upload the video through the announcement form</li>
        <li>Delete the old broken announcement</li>
    </ol>
</div>

<hr style="margin: 40px 0;">

<h2>Current File Paths (Reference)</h2>
<div class="step">
    <strong>Local (Development):</strong><br>
    <code>C:\xampp\htdocs\Timekeeping-system\Public\views\uploads\employee_guide.mp4</code>
    
    <br><br>
    
    <strong>Production (Hostinger):</strong><br>
    Server Path: <code>/home/u816220874/public_html/Public/views/uploads/employee_guide.mp4</code><br>
    Web URL: <code>https://harley.resourcestaffonline.com/Public/views/uploads/employee_guide.mp4</code>
</div>

<?php
// Show current local file info
$localVideo = __DIR__ . '/Public/views/uploads/employee_guide.mp4';
if (file_exists($localVideo)) {
    $size = filesize($localVideo);
    echo "<div class='success'>";
    echo "<strong>✅ Local File Status:</strong><br>";
    echo "File exists on your local machine<br>";
    echo "Size: " . number_format($size) . " bytes (" . number_format($size / 1024 / 1024, 2) . " MB)<br>";
    echo "This file needs to be uploaded to production server";
    echo "</div>";
}
?>

</body>
</html>