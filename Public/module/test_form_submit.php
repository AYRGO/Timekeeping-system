<?php
// Test if POST data is being received
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Form Submission Test</h2>";
echo "<h3>POST Data Received:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>FILES Data Received:</h3>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo "<h3>Test Checklist:</h3>";
echo "<ul>";
if (isset($_POST['submit_schedule_swap'])) {
    echo "<li style='color: green;'>✅ submit_schedule_swap field found</li>";
} else {
    echo "<li style='color: red;'>❌ submit_schedule_swap field NOT found</li>";
}

if (isset($_POST['source_date'])) {
    echo "<li style='color: green;'>✅ source_date: " . htmlspecialchars($_POST['source_date']) . "</li>";
} else {
    echo "<li style='color: red;'>❌ source_date NOT found</li>";
}

if (isset($_POST['target_date'])) {
    echo "<li style='color: green;'>✅ target_date: " . htmlspecialchars($_POST['target_date']) . "</li>";
} else {
    echo "<li style='color: red;'>❌ target_date NOT found</li>";
}

if (isset($_POST['reason'])) {
    echo "<li style='color: green;'>✅ reason: " . htmlspecialchars($_POST['reason']) . "</li>";
} else {
    echo "<li style='color: red;'>❌ reason NOT found</li>";
}

if (isset($_FILES['attachment_scr'])) {
    echo "<li style='color: green;'>✅ attachment_scr file found</li>";
    echo "<ul>";
    echo "<li>Name: " . htmlspecialchars($_FILES['attachment_scr']['name']) . "</li>";
    echo "<li>Size: " . $_FILES['attachment_scr']['size'] . " bytes</li>";
    echo "<li>Error: " . $_FILES['attachment_scr']['error'] . "</li>";
    echo "</ul>";
} else {
    echo "<li style='color: red;'>❌ attachment_scr file NOT found</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h3>Server Info:</h3>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
?>
