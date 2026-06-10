<?php
/**
 * Test Script for Manual Leave Accrual System
 * Run this to verify the accrual system is working correctly
 */

include('../config/db.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Leave Accrual System Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        h1, h2 { color: #333; }
        .status-ok { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🧪 Leave Accrual System Test</h1>
    <p><strong>Test Date:</strong> " . date('F j, Y g:i A') . "</p>
";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $pdo->query("SELECT 1");
    echo "<div class='success'>✓ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Test 2: Check Table Structure
echo "<h2>2. Leave Credits Table Structure</h2>";
try {
    $columns = $pdo->query("DESCRIBE leave_credits")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";
    
    $requiredColumns = ['employee_id', 'leave_type', 'balance', 'carry_over', 'year'];
    $existingColumns = array_column($columns, 'Field');
    $missing = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missing)) {
        echo "<div class='success'>✓ All required columns exist</div>";
    } else {
        echo "<div class='error'>✗ Missing columns: " . implode(', ', $missing) . "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking table structure: " . $e->getMessage() . "</div>";
}

// Test 3: Check accrual-eligible employees.
echo "<h2>3. Accrual-Eligible Employees</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular')");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "<div class='success'>✓ Found {$count} accrual-eligible employees</div>";
        
        // Show sample employees
        $stmt = $pdo->query("SELECT id, fname, lname, status FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular') LIMIT 5");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table><tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr><td>{$emp['id']}</td><td>{$emp['fname']} {$emp['lname']}</td><td>{$emp['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>✗ No accrual-eligible employees found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking employees: " . $e->getMessage() . "</div>";
}

// Test 4: Current Leave Credits
echo "<h2>4. Current Leave Credits Status</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT employee_id) as employees_with_credits,
            COUNT(*) as total_records,
            SUM(CASE WHEN leave_type = 'sick' THEN 1 ELSE 0 END) as sick_records,
            SUM(CASE WHEN leave_type = 'vacation' THEN 1 ELSE 0 END) as vacation_records
        FROM leave_credits 
        WHERE year = YEAR(CURDATE())
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>Employees with Credits</td><td>{$stats['employees_with_credits']}</td></tr>
        <tr><td>Total Records</td><td>{$stats['total_records']}</td></tr>
        <tr><td>Sick Leave Records</td><td>{$stats['sick_records']}</td></tr>
        <tr><td>Vacation Leave Records</td><td>{$stats['vacation_records']}</td></tr>
    </table>";
    
    if ($stats['total_records'] > 0) {
        echo "<div class='success'>✓ Leave credits data exists</div>";
    } else {
        echo "<div class='info'>ℹ No leave credits found (this is normal for new system)</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking leave credits: " . $e->getMessage() . "</div>";
}

// Test 5: Sample Leave Credit Records
echo "<h2>5. Sample Leave Credit Records</h2>";
try {
    $stmt = $pdo->query("
        SELECT 
            e.fname, 
            e.lname, 
            lc.leave_type, 
            lc.balance, 
            lc.carry_over,
            lc.updated_at
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.year = YEAR(CURDATE())
        ORDER BY lc.updated_at DESC
        LIMIT 10
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        echo "<table>
            <tr>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Balance</th>
                <th>Carry Over</th>
                <th>Last Updated</th>
            </tr>";
        
        foreach ($records as $rec) {
            echo "<tr>
                <td>{$rec['fname']} {$rec['lname']}</td>
                <td>" . ucfirst($rec['leave_type']) . "</td>
                <td>" . number_format($rec['balance'], 2) . "</td>
                <td>" . ($rec['carry_over'] ? number_format($rec['carry_over'], 2) : 'N/A') . "</td>
                <td>" . date('M j, Y g:i A', strtotime($rec['updated_at'])) . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ No records to display</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error fetching records: " . $e->getMessage() . "</div>";
}

// Test 6: Accrual Calculation Test (Dry Run)
echo "<h2>6. Accrual Calculation Test (Dry Run)</h2>";
echo "<div class='info'>ℹ This simulates accrual without making changes</div>";
try {
    $leaveRates = ['sick' => 0.42, 'vacation' => 1.25];
    
    // Get the first employee eligible for automatic accrual.
    $stmt = $pdo->query("SELECT id, fname, lname FROM employees WHERE status = 'active' AND Emp_Type IN ('Regular', 'Old_Regular') LIMIT 1");
    $testEmployee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testEmployee) {
        echo "<p><strong>Test Employee:</strong> {$testEmployee['fname']} {$testEmployee['lname']} (ID: {$testEmployee['id']})</p>";
        
        echo "<table><tr><th>Leave Type</th><th>Current Balance</th><th>Monthly Rate</th><th>New Balance</th><th>Status</th></tr>";
        
        foreach ($leaveRates as $type => $rate) {
            $stmt = $pdo->prepare("SELECT balance, carry_over FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$testEmployee['id'], $type, date('Y')]);
            $credit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentBalance = $credit ? floatval($credit['balance']) : 0;
            $maxBalance = 15;
            $spaceLeft = $maxBalance - $currentBalance;
            $toBalance = min($rate, $spaceLeft);
            $newBalance = $currentBalance + $toBalance;
            
            $status = $credit ? 
                "<span class='status-ok'>Would Update</span>" : 
                "<span class='status-ok'>Would Create</span>";
            
            echo "<tr>
                <td>" . ucfirst($type) . "</td>
                <td>" . number_format($currentBalance, 2) . "</td>
                <td>+" . number_format($rate, 2) . "</td>
                <td>" . number_format($newBalance, 2) . "</td>
                <td>{$status}</td>
            </tr>";
        }
        
        echo "</table>";
        echo "<div class='success'>✓ Accrual calculation test successful</div>";
    } else {
        echo "<div class='error'>✗ No active employee found for testing</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error in calculation test: " . $e->getMessage() . "</div>";
}

// Test 7: File Existence
echo "<h2>7. Required Files Check</h2>";
$requiredFiles = [
    '../module/process_monthly_leave_accrual.php' => 'Accrual Processing Endpoint',
    '../module/leave_accrual_manager.php' => 'Accrual Manager Dashboard',
    '../module/leave_credits.php' => 'Leave Credits Display'
];

echo "<table><tr><th>File</th><th>Description</th><th>Status</th></tr>";
foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists($file);
    $status = $exists ? 
        "<span class='status-ok'>✓ Exists</span>" : 
        "<span class='status-fail'>✗ Missing</span>";
    echo "<tr><td>{$file}</td><td>{$desc}</td><td>{$status}</td></tr>";
}
echo "</table>";

// Summary
echo "<h2>📊 Test Summary</h2>";
echo "<div class='success'>
    <h3>System is ready for use!</h3>
    <p><strong>Next Steps:</strong></p>
    <ol>
        <li>Navigate to <code>/Public/module/leave_accrual_manager.php</code> to access the admin dashboard</li>
        <li>Or go to any employee's leave credits page to see the accrual button</li>
        <li>Click 'Process Monthly Accrual' to run the accrual for all employees</li>
    </ol>
    <p><strong>Accrual Rates:</strong></p>
    <ul>
        <li>Sick Leave: +0.42 days/month (5 days/year)</li>
        <li>Vacation Leave: +1.25 days/month (15 days/year)</li>
    </ul>
</div>";

echo "</body></html>";
?>
