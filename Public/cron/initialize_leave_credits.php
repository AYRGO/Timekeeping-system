<?php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Monthly increment formula (could be updated based on admin input)
$leaveTypes = [
    'sick'          => 5 / 12,
    'vacation'      => 15 / 12,
    'paternity'     => 7 / 12,
    'maternity'     => 105 / 12,
    'solo_parent'   => 6 / 12,
    'halfday'       => 0,
    'halfday_sick'  => 0,
    'lwop'          => 0,
    'bereavement'   => 3 / 12,
];

// Get ONLY PROBATIONARY active employees
// Regular employees should use process_monthly_accrual.php for gradual monthly accrual
$employees = $pdo->query("SELECT id, Emp_Type FROM employees WHERE status = 'active' AND Emp_Type = 'Probationary'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as $emp) {
    $employee_id = $emp['id'];

    foreach ($leaveTypes as $type => $monthlyInc) {
        // Check if credit for this year and leave type exists
        $stmt = $pdo->prepare("SELECT id FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employee_id, $type, $currentYear]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $carryOver = ($type === 'vacation') ? 5 : 0;

            $insert = $pdo->prepare("
                INSERT INTO leave_credits 
                (employee_id, leave_type, balance, monthly_increment, carry_over, year) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $employee_id,
                $type,
                null,              // Set balance to NULL initially
                $monthlyInc,    
                $carryOver,
                $currentYear
            ]);
        }
    }
}

echo "✅ Leave credits initialized for {$currentYear}.";
?>
