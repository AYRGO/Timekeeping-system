<?php
/**
 * Admin Manual Leave Credit Adjustment
 * Allows admins to manually encode/adjust Vacation Leave (VL) credits
 * for newly regularized employees (pro-rated balance from probation period)
 */

session_start();
include('../config/db.php');

// Only admins can access
if (!isset($_SESSION['employee']) || $_SESSION['employee']['role'] !== 'internal') {
    die("Unauthorized - Admin only");
}

// Process manual adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_credit'])) {
    $employee_id = (int)$_POST['employee_id'];
    $leave_type = $_POST['leave_type'];
    $adjustment_amount = floatval($_POST['adjustment_amount']);
    $adjustment_type = $_POST['adjustment_type']; // 'add' or 'set'
    $reason = $_POST['reason'] ?? '';
    $currentYear = (int)date('Y');
    
    if ($employee_id && $leave_type && $adjustment_amount >= 0) {
        try {
            // Check if record exists
            $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$employee_id, $leave_type, $currentYear]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($adjustment_type === 'add') {
                    $newBalance = floatval($existing['balance']) + $adjustment_amount;
                } else {
                    $newBalance = $adjustment_amount;
                }
                
                // Cap at maximum
                $maxBalance = 15.00;
                if ($newBalance > $maxBalance) {
                    $newBalance = $maxBalance;
                }
                
                $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newBalance, $existing['id']]);
                
                $message = "Successfully updated {$leave_type} balance to {$newBalance} days";
                
            } else {
                // Create new record
                $monthlyIncrement = ($leave_type === 'vacation') ? 1.25 : 0;
                $carryOver = ($leave_type === 'vacation') ? 0 : null;
                
                $stmt = $pdo->prepare("
                    INSERT INTO leave_credits 
                    (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$employee_id, $leave_type, $adjustment_amount, $carryOver, $currentYear, $monthlyIncrement]);
                
                $message = "Successfully created {$leave_type} record with {$adjustment_amount} days";
            }
            
            // Log the adjustment (optional - if you have an audit log table)
            $admin_id = $_SESSION['employee']['id'];
            $admin_name = $_SESSION['employee']['fname'] . ' ' . $_SESSION['employee']['lname'];
            
            echo "<script>alert('{$message}\\nReason: {$reason}'); window.location.reload();</script>";
            
        } catch (Exception $e) {
            echo "<script>alert('Error: {$e->getMessage()}');</script>";
        }
    }
}

// Fetch all Regular employees for the dropdown
$stmt = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name, position, Emp_Type FROM employees WHERE status = 'Active' ORDER BY fname, lname");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If an employee is selected, fetch their current credits
$selectedEmployee = null;
$currentCredits = [];
if (isset($_GET['employee_id'])) {
    $emp_id = (int)$_GET['employee_id'];
    $stmt = $pdo->prepare("SELECT id, CONCAT(fname, ' ', lname) as full_name, position, Emp_Type FROM employees WHERE id = ?");
    $stmt->execute([$emp_id]);
    $selectedEmployee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedEmployee) {
        $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? ORDER BY leave_type");
        $stmt->execute([$emp_id, date('Y')]);
        $currentCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Leave Credit Adjustment - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-user-edit text-blue-600 mr-2"></i>
                        Manual Leave Credit Adjustment
                    </h1>
                    <p class="text-gray-600">Manually encode or adjust leave credits for employees</p>
                </div>
                <a href="../views/admin_homepage.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Admin
                </a>
            </div>
        </div>

        <!-- Employee Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-user-check text-green-600 mr-2"></i>
                Select Employee
            </h2>
            <form method="GET" class="flex gap-4">
                <select name="employee_id" class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Choose Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($selectedEmployee && $selectedEmployee['id'] == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['full_name']) ?> - <?= htmlspecialchars($emp['position']) ?> 
                            (<?= htmlspecialchars($emp['Emp_Type'] ?? 'Probationary') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                    <i class="fas fa-search mr-2"></i>View Credits
                </button>
            </form>
        </div>

        <?php if ($selectedEmployee): ?>
            <!-- Current Credits Display -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-list text-purple-600 mr-2"></i>
                    Current Leave Credits for <?= htmlspecialchars($selectedEmployee['full_name']) ?>
                </h2>
                <p class="text-sm text-gray-600 mb-4">
                    <strong>Position:</strong> <?= htmlspecialchars($selectedEmployee['position']) ?> |
                    <strong>Status:</strong> 
                    <span class="<?= $selectedEmployee['Emp_Type'] === 'Regular' ? 'text-blue-600 font-semibold' : 'text-orange-600' ?>">
                        <?= htmlspecialchars($selectedEmployee['Emp_Type'] ?? 'Probationary') ?>
                    </span>
                </p>

                <?php if (!empty($currentCredits)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($currentCredits as $credit): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-700 mb-2 capitalize">
                                    <?= str_replace('_', ' ', $credit['leave_type']) ?> Leave
                                </h3>
                                <p class="text-2xl font-bold text-blue-600">
                                    <?= number_format($credit['balance'], 2) ?> <span class="text-sm text-gray-500">days</span>
                                </p>
                                <?php if ($credit['leave_type'] === 'vacation' && $credit['carry_over']): ?>
                                    <p class="text-xs text-gray-500 mt-1">Carry-over: <?= number_format($credit['carry_over'], 2) ?> days</p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-2">
                                    Last updated: <?= date('M d, Y', strtotime($credit['updated_at'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-yellow-800">No leave credits found for this employee</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Adjustment Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-edit text-orange-600 mr-2"></i>
                    Adjust Leave Credits
                </h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Policy Reminder (Effective Jan 1, 2026)
                    </h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>✅ <strong>Sick Leave (SL):</strong> Automatically granted 5 days upon regularization (no manual entry needed)</li>
                        <li>✅ <strong>Vacation Leave (VL):</strong> Accrues monthly (1.25 days/month) for Regular employees</li>
                        <li>📝 <strong>Pro-rated VL:</strong> Manually encode any VL earned during probation period here</li>
                    </ul>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="adjust_credit" value="1">
                    <input type="hidden" name="employee_id" value="<?= $selectedEmployee['id'] ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                            <select name="leave_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">-- Select Type --</option>
                                <option value="vacation">Vacation Leave (VL)</option>
                                <option value="sick">Sick Leave (SL)</option>
                                <option value="paternity">Paternity Leave</option>
                                <option value="maternity">Maternity Leave</option>
                                <option value="solo_parent">Solo Parent Leave</option>
                                <option value="bereavement">Bereavement Leave</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                            <select name="adjustment_type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="add">Add to existing balance</option>
                                <option value="set">Set exact balance</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (days)</label>
                            <input type="number" step="0.01" min="0" max="15" name="adjustment_amount" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="e.g., 2.50" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Adjustment</label>
                            <input type="text" name="reason" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="e.g., Pro-rated VL from probation" required>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-md transition font-semibold">
                            <i class="fas fa-check mr-2"></i>Apply Adjustment
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
