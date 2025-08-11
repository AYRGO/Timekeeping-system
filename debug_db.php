<?php
include('Public/config/db.php');

echo "=== DATABASE DEBUG INFO ===\n";

// Check time_logs table structure
echo "\nTime Logs Table Structure:\n";
$stmt = $pdo->query('DESCRIBE time_logs');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

// Check sample time logs
echo "\nSample Time Logs (first 5):\n";
$stmt = $pdo->query('SELECT tl.*, e.fname, e.lname, e.company FROM time_logs tl JOIN employees e ON tl.employee_id = e.id LIMIT 5');
while ($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Employee: ' . $row['fname'] . ' ' . $row['lname'] . ', Company: ' . $row['company'] . ', Date: ' . $row['log_date'] . "\n";
}

// Check Bugardi employees
echo "\nBugardi Employees:\n";
$stmt = $pdo->query('SELECT id, fname, lname, company FROM employees WHERE LOWER(company) = "bugardi"');
while ($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Name: ' . $row['fname'] . ' ' . $row['lname'] . ', Company: ' . $row['company'] . "\n";
}

// Check time logs for Bugardi employees specifically
echo "\nTime Logs for Bugardi Employees:\n";
$stmt = $pdo->query('SELECT COUNT(*) as count FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE LOWER(e.company) = "bugardi"');
$result = $stmt->fetch();
echo 'Total Bugardi time logs (old query): ' . $result['count'] . "\n";

$stmt = $pdo->query('SELECT COUNT(*) as count FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE LOWER(TRIM(e.company)) = "bugardi"');
$result = $stmt->fetch();
echo 'Total Bugardi time logs (with TRIM): ' . $result['count'] . "\n";

echo "\nActual Bugardi Time Logs:\n";
$stmt = $pdo->query('SELECT tl.id, tl.log_date, tl.time_in, tl.time_out, e.fname, e.lname FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE LOWER(TRIM(e.company)) = "bugardi" ORDER BY tl.log_date DESC LIMIT 10');
while ($row = $stmt->fetch()) {
    echo 'Date: ' . $row['log_date'] . ', Employee: ' . $row['fname'] . ' ' . $row['lname'] . ', Time In: ' . $row['time_in'] . ', Time Out: ' . $row['time_out'] . "\n";
}

// Check for any time logs where employee_id matches our Bugardi employees
echo "\nDirect check - Time logs for Bugardi employee IDs:\n";
$stmt = $pdo->query('SELECT tl.id, tl.employee_id, tl.log_date, tl.time_in, tl.time_out FROM time_logs tl WHERE tl.employee_id IN (52, 58, 62, 65, 67) ORDER BY tl.log_date DESC');
while ($row = $stmt->fetch()) {
    echo 'Employee ID: ' . $row['employee_id'] . ', Date: ' . $row['log_date'] . ', Time In: ' . $row['time_in'] . ', Time Out: ' . $row['time_out'] . "\n";
}

// Check the actual company field content
echo "\nBugardi employees with exact company field content:\n";
$stmt = $pdo->query('SELECT id, fname, lname, CONCAT("*", company, "*") as company_debug FROM employees WHERE id IN (52, 58, 62, 65, 67)');
while ($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Name: ' . $row['fname'] . ' ' . $row['lname'] . ', Company: ' . $row['company_debug'] . "\n";
}

// Check company names variations
echo "\nAll unique company names:\n";
$stmt = $pdo->query('SELECT DISTINCT company FROM employees ORDER BY company');
while ($row = $stmt->fetch()) {
    echo '"' . $row['company'] . '"' . "\n";
}
?>
