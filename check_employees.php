<?php
require_once 'Public/config/db.php';

// Get Bugardi employees
$stmt = $pdo->query("SELECT id, fname, lname, position, company FROM employees WHERE LOWER(TRIM(company)) = 'bugardi' ORDER BY fname, lname");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Bugardi Employees:\n";
foreach ($employees as $emp) {
    echo "ID: {$emp['id']}, Name: {$emp['fname']} {$emp['lname']}, Position: {$emp['position']}\n";
}
echo "\nTotal Bugardi employees: " . count($employees) . "\n";

// Check existing overtime requests for Bugardi employees
$stmt = $pdo->query("
    SELECT COUNT(*) as count
    FROM overtime_requests ot
    JOIN employees e ON ot.employee_id = e.id
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
");
$otCount = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nExisting overtime requests for Bugardi: " . $otCount['count'] . "\n";
?>
