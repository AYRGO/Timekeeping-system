<?php
// Simple debug script to test attachment paths
session_start();

if (!isset($_SESSION['employee']['id'])) {
    echo "Please log in first.";
    exit;
}

echo "<h2>Attachment Path Debug</h2>";

// Test the specific file you mentioned
$testFile = 'scr_68df46052432b.png';
$testPaths = [
    '../uploads/' . $testFile,
    '../uploads/schedule_attachments/' . $testFile,
    'uploads/' . $testFile,
    'uploads/schedule_attachments/' . $testFile
];

echo "<h3>Testing file: {$testFile}</h3>";

foreach ($testPaths as $path) {
    $exists = file_exists($path);
    $realPath = $exists ? realpath($path) : 'N/A';
    echo "<p><strong>{$path}</strong>: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . " (Real path: {$realPath})</p>";
}

echo "<hr>";
echo "<h3>Available files in uploads directory:</h3>";
if (is_dir('../uploads/')) {
    $files = scandir('../uploads/');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>{$file}</li>";
        }
    }
    echo "</ul>";
}

echo "<h3>Available files in uploads/schedule_attachments directory:</h3>";
if (is_dir('../uploads/schedule_attachments/')) {
    $files = scandir('../uploads/schedule_attachments/');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>{$file}</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>schedule_attachments directory does not exist</p>";
}

echo "<hr>";
echo "<h3>Test Links:</h3>";
echo "<p><a href='view_attachment.php?file={$testFile}' target='_blank'>View {$testFile} (direct)</a></p>";
echo "<p><a href='view_attachment.php?file=schedule_attachments/{$testFile}' target='_blank'>View {$testFile} (with path)</a></p>";
?>