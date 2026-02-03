<?php
// File: update_leave_credits.php
include('../config/db.php'); // Adjust path as needed
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Leave Types with Yearly Allotments FOR PROBATIONARY EMPLOYEES ONLY
// Regular employees should use monthly accrual (process_monthly_accrual.php)
$yearlyCredits = [
    'sick'         => 5,
    'vacation'     => 15,
    'paternity'    => 7,
    'maternity'    => 105,
    'solo_parent'  => 6,
    'bereavement'  => 3,
    'halfday'      => 0,    // Deducted from vacation, so no separate credit
    'halfday_sick' => 0,    // Deducted from sick
    'lwop'         => null, // Leave Without Pay — no credits given
];

// Get ONLY PROBATIONARY active employees
// Regular employees should not get full year credits at once
$employees = $pdo->query("SELECT id, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Probationary'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as $emp) {
    $employee_id = $emp['id'];

    foreach ($yearlyCredits as $leave_type => $yearly_value) {
        if (is_null($yearly_value)) {
            // LWOP or other leave types not tracked via credits
            continue;
        }

        // Check if record already exists
        $stmt = $pdo->prepare("SELECT id FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employee_id, $leave_type, $currentYear]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Insert leave credit for the year
            $insert = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, ?, ?, ?)");
            $insert->execute([$employee_id, $leave_type, $yearly_value, $currentYear]);
        }
    }
} 

echo "✅ Leave credits initialized/updated successfully for $currentYear.";
?>
