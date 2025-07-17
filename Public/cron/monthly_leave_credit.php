<?php 
// File: monthly_leave_credit.php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Step 1: Fetch all active employees
$employees = $pdo->query("SELECT id FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Define leave types with their monthly increments and annual limits
$leaveTypes = [
    'sick' => ['monthly_increment' => 0.42, 'max_balance' => 15],
    'vacation' => ['monthly_increment' => 1.25, 'max_balance' => 15, 'carry_over' => 5],
    'paternity' => ['fixed' => 7],
    'maternity' => ['fixed' => 105],
    'solo_parent' => ['fixed' => 7],
    'bereavement' => ['fixed' => 3],
    'halfday' => ['fixed' => 0],
    'halfday_sick' => ['fixed' => 0],
    'lwop' => ['fixed' => 0],
];

// Step 3: Loop through each employee and leave type
foreach ($employees as $emp) {
    $employee_id = $emp['id'];

    foreach ($leaveTypes as $type => $rules) {
        // Check if leave_credits row exists
        $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employee_id, $type, $currentYear]);
        $row = $stmt->fetch();

        if ($row) {
            // Existing row
            $currentBalance = is_numeric($row['balance']) ? floatval($row['balance']) : 0.0;
            $currentCarryOver = ($type === 'vacation' && isset($row['carry_over'])) ? floatval($row['carry_over']) : 0.0;

            if (isset($rules['monthly_increment'])) {
                $monthlyIncrement = $rules['monthly_increment'];

                // Calculate how much can go to balance
                $spaceLeft = $rules['max_balance'] - $currentBalance;
                $toBalance = min($monthlyIncrement, $spaceLeft);
                $remaining = $monthlyIncrement - $toBalance;

                // Apply carry over if vacation and space is available
                $toCarryOver = 0;
                if ($type === 'vacation' && $remaining > 0) {
                    $carrySpace = $rules['carry_over'] - $currentCarryOver;
                    $toCarryOver = min($remaining, $carrySpace);
                }

                $newBalance = $currentBalance + $toBalance;
                $newCarryOver = ($type === 'vacation') ? $currentCarryOver + $toCarryOver : null;

                // Update the record
                $update = $pdo->prepare("
                    UPDATE leave_credits
                    SET balance = ?, monthly_increment = ?, carry_over = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $update->execute([$newBalance, $monthlyIncrement, $newCarryOver, $row['id']]);
            }

        } else {
            // New row: Insert
            $balance = 0;
            $monthlyIncrement = null;
            $carryOver = null;

            if (isset($rules['monthly_increment'])) {
                $balance = $rules['monthly_increment'];
                $monthlyIncrement = $rules['monthly_increment'];
                if ($type === 'vacation') {
                    $carryOver = 0;
                }
            } elseif (isset($rules['fixed'])) {
                $balance = $rules['fixed'];
            }

            $insert = $pdo->prepare("
                INSERT INTO leave_credits (employee_id, leave_type, balance, monthly_increment, carry_over, year, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $insert->execute([
                $employee_id,
                $type,
                $balance,
                $monthlyIncrement,
                $carryOver,
                $currentYear
            ]);
        }
    }
}

echo "✅ Leave credits updated successfully for " . count($employees) . " employees.\n";
