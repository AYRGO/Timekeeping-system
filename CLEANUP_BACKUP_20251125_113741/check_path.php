<?php
echo "<h2>🔍 Path Information</h2>";
echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Script Path:</strong> " . __FILE__ . "<br>";
echo "<strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "<br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";

// Try to detect the username from the path
$currentPath = getcwd();
if (preg_match('/\/home\/([^\/]+)\//', $currentPath, $matches)) {
    echo "<strong style='color: green;'>Detected Username:</strong> " . $matches[1] . "<br>";
} else {
    echo "<strong style='color: red;'>Could not detect username from path</strong><br>";
}

echo "<hr>";
echo "<small>Delete this file after checking!</small>";
?>