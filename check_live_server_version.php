<?php
/**
 * Check if the fixed scripts are deployed on live server
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Live Server Version Check</h2>";
echo "<p>Checking if critical fixes are deployed...</p><hr>";

$filesToCheck = [
    'Public/cron/generate_leave_credits.php',
    'Public/cron/update_leave_credits.php',
    'Public/cron/initialize_leave_credits.php',
    'Public/cron/process_monthly_accrual.php'
];

foreach ($filesToCheck as $file) {
    echo "<h3>$file</h3>";
    
    if (!file_exists($file)) {
        echo "<p style='color:red;'>❌ FILE NOT FOUND</p>";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if the file has the Emp_Type filter
    if (strpos($content, "Emp_Type = 'Probationary'") !== false) {
        echo "<p style='color:green;'>✅ HAS Probationary FILTER - Fixed version deployed!</p>";
    } elseif (strpos($content, "Emp_Type = 'Regular'") !== false) {
        echo "<p style='color:green;'>✅ HAS Regular FILTER - Fixed version deployed!</p>";
    } else {
        echo "<p style='color:red;'>❌ NO Emp_Type FILTER - Old version still deployed!</p>";
        echo "<pre>First 500 chars:\n" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
    }
    
    echo "<hr>";
}

echo "<h3>Last Modified Times:</h3>";
foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        echo "<p><strong>$file</strong>: " . date('Y-m-d H:i:s', filemtime($file)) . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
