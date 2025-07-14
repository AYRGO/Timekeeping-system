<?php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Define yearly leave credits
$leaveTypes = [
    'sick'          => 5,
    'vacation'      => 15,
    'paternity'     => 7,
    'maternity'     => 105,
    'solo_parent'   => 6,
    'halfday'       => 0,       // deducts from vacation
    'halfday_sick'  => 0,       // deducts from sick
    'lwop'          => null,    // no balance
    'bereavement'   => 3
];

// Fetch all active employees
$employees = $pdo->query("SELECT id FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as $emp) {
    $employee_id = $emp['id'];

    foreach ($leaveTypes as $type => $defaultBalance) {
        // Check if leave credit already exists for this year
        $stmt = $pdo->prepare("SELECT id FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employee_id, $type, $currentYear]);
        $exists = $stmt->fetch();

        // If not exists, insert
        if (!$exists) {
            $insert = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, ?, ?, ?)");
            $insert->execute([$employee_id, $type, $defaultBalance, $currentYear]);
        }
    }
}

echo "✅ Leave credits initialized/updated successfully for {$currentYear}.";
?>
