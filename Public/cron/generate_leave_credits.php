<?php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Define yearly leave credits FOR PROBATIONARY EMPLOYEES ONLY
// Regular employees should use monthly accrual (process_monthly_accrual.php)
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

// Fetch ONLY PROBATIONARY active employees
// Regular employees should not get full year credits at once
$employees = $pdo->query("SELECT id, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Probationary'")->fetchAll(PDO::FETCH_ASSOC);

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
