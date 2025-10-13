<?php
// Debug script to check leave accrual system
include(__DIR__ . '/../config/db.php');
date_default_timezone_set('Asia/Manila');

echo "🔍 LEAVE ACCRUAL DEBUG SCRIPT\n";
echo str_repeat("=", 50) . "\n";

try {
    // Check database connection
    echo "✅ Database connection successful\n";
    
    // Check employees table
    $employees = $pdo->query("SELECT id, CONCAT(fname, ' ', lname) as full_name FROM employees WHERE status = 'active' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "👥 Found " . count($employees) . " active employees (showing first 5):\n";
    foreach ($employees as $emp) {
        echo "   - ID: {$emp['id']}, Name: {$emp['full_name']}\n";
    }
    
    // Check leave_credits table structure
    echo "\n📊 Leave Credits Table Structure:\n";
    $columns = $pdo->query("DESCRIBE leave_credits")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Check existing leave credits
    echo "\n💳 Current Leave Credits (first 10 records):\n";
    $credits = $pdo->query("SELECT * FROM leave_credits WHERE year = " . date('Y') . " ORDER BY updated_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($credits)) {
        echo "   ⚠️ No leave credit records found for " . date('Y') . "\n";
    } else {
        foreach ($credits as $credit) {
            echo sprintf("   - Employee %d: %s = %.2f days (Updated: %s)\n", 
                $credit['employee_id'], 
                $credit['leave_type'], 
                $credit['balance'], 
                $credit['updated_at']
            );
        }
    }
    
    // Test processing one employee
    echo "\n🧪 Testing accrual for first employee...\n";
    if (!empty($employees)) {
        $testEmployeeId = $employees[0]['id'];
        $testEmployeeName = $employees[0]['full_name'];
        
        echo "Testing with Employee ID: {$testEmployeeId} ({$testEmployeeName})\n";
        
        foreach (['sick', 'vacation'] as $leaveType) {
            // Check existing record
            $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
            $stmt->execute([$testEmployeeId, $leaveType, date('Y')]);
            $record = $stmt->fetch();
            
            if ($record) {
                $oldBalance = floatval($record['balance']);
                echo "   Current {$leaveType} balance: {$oldBalance}\n";
                
                // Update the record
                $increment = ($leaveType === 'sick') ? 0.42 : 1.25;
                $newBalance = $oldBalance + $increment;
                
                $updateStmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, updated_at = NOW() WHERE id = ?");
                $success = $updateStmt->execute([$newBalance, $record['id']]);
                
                if ($success) {
                    echo "   ✅ Updated {$leaveType} balance: {$oldBalance} → {$newBalance}\n";
                } else {
                    echo "   ❌ Failed to update {$leaveType} balance\n";
                }
            } else {
                echo "   ⚠️ No {$leaveType} record found, creating new one...\n";
                
                $increment = ($leaveType === 'sick') ? 0.42 : 1.25;
                $carryOver = ($leaveType === 'vacation') ? 0 : null;
                
                $insertStmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $success = $insertStmt->execute([$testEmployeeId, $leaveType, $increment, $carryOver, date('Y'), $increment]);
                
                if ($success) {
                    echo "   ✅ Created {$leaveType} record with balance: {$increment}\n";
                } else {
                    echo "   ❌ Failed to create {$leaveType} record\n";
                }
            }
        }
    }
    
    echo "\n🔍 Final check - Updated records:\n";
    $finalCredits = $pdo->query("SELECT * FROM leave_credits WHERE year = " . date('Y') . " AND employee_id = {$testEmployeeId} ORDER BY leave_type")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalCredits as $credit) {
        echo sprintf("   - %s: %.2f days (Updated: %s)\n", 
            $credit['leave_type'], 
            $credit['balance'], 
            $credit['updated_at']
        );
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n✅ Debug complete!\n";
?>