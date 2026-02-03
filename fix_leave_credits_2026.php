<?php
/**
 * FIX LEAVE CREDITS 2026 - Correct the 15 VL Issue
 * 
 * PROBLEM: On January 31, 2026, all Regular employees received 15 VL instead of monthly accrual
 * CAUSE: An initialization script (generate_leave_credits.php or update_leave_credits.php) was run
 * 
 * SOLUTION: Reset vacation leave to proper monthly accrual amount based on months worked
 * 
 * BACKUP RECOMMENDATION: Before running this, backup your database!
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

echo "===========================================\n";
echo "FIX LEAVE CREDITS 2026 - CORRECTION SCRIPT\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

// Safety check - require manual confirmation
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "⚠️ SAFETY CHECK: This script will modify leave credit balances.\n\n";
    echo "📋 WHAT THIS SCRIPT DOES:\n";
    echo "   1. Finds all Regular employees with 15 VL in 2026\n";
    echo "   2. Calculates correct VL based on monthly accrual (1.25/month)\n";
    echo "   3. Updates balances to correct amounts\n\n";
    echo "🎯 CALCULATION METHOD:\n";
    echo "   - January 2026 = 1 month worked = 1.25 VL\n";
    echo "   - February 2026 = 2 months worked = 2.50 VL\n";
    echo "   - Current month (Feb) = 2.50 VL (since we're early in Feb)\n\n";
    echo "⚠️ TO RUN THIS SCRIPT: Add ?confirm=yes to the URL\n";
    echo "   Example: https://harley.resourcestaffonline.com/fix_leave_credits_2026.php?confirm=yes\n\n";
    echo "💾 STRONGLY RECOMMENDED: Backup your database first!\n";
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Calculate correct VL balance
    // Monthly accrual happens at END of month, so:
    // - January 31, 2026: First accrual = 1.25 VL
    // - February accrual won't happen until Feb 28/29
    // Since we're early February, they should only have January's accrual: 1.25 VL
    $monthsAccrued = 1; // Only January accrual has happened
    $correctBalance = 1.25;
    
    echo "📊 CORRECTION DETAILS:\n";
    echo "   Current date: " . date('F j, Y') . "\n";
    echo "   Months accrued so far: $monthsAccrued (January only)\n";
    echo "   Correct VL balance: $correctBalance days\n";
    echo "   (Calculation: 1 month × 1.25 days/month = 1.25 VL)\n";
    echo "   Note: February accrual happens at end of February\n\n";
    
    // Get all Regular employees with incorrect VL in 2026 (balance > 1.25 means they got the wrong 15 VL)
    $stmt = $pdo->query("
        SELECT 
            lc.id as leave_credit_id,
            lc.employee_id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            lc.balance as current_balance,
            lc.carry_over,
            lc.updated_at
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.year = 2026
        AND e.Emp_Type = 'Regular'
        AND e.status = 'active'
        AND lc.balance > 1.25
        AND lc.updated_at = '2026-01-31 23:47:12'
    ");
    
    $affectedEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAffected = count($affectedEmployees);
    
    if ($totalAffected === 0) {
        echo "✅ No employees need correction. All balances are correct.\n";
        $pdo->rollBack();
        exit;
    }
    
    echo "🔍 FOUND $totalAffected EMPLOYEES TO CORRECT:\n";
    echo str_repeat("-", 95) . "\n";
    printf("%-5s %-30s %-12s %-12s %-10s %-12s\n", "ID", "Name", "Current VL", "VL Used", "Correct VL", "Final VL");
    printf("%-5s %-30s %-12s %-12s %-10s %-12s\n", str_repeat("-", 5), str_repeat("-", 30), str_repeat("-", 12), str_repeat("-", 12), str_repeat("-", 10), str_repeat("-", 12));
    
    $updateStmt = $pdo->prepare("
        UPDATE leave_credits 
        SET balance = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $correctedCount = 0;
    $errors = [];
    
    foreach ($affectedEmployees as $emp) {
        try {
            // Calculate VL already used: 15 (incorrect amount given) - current balance
            $vlUsed = 15 - floatval($emp['current_balance']);
            $vlUsed = max(0, $vlUsed); // Can't be negative
            
            // Final balance = correct accrual - VL used
            $finalBalance = $correctBalance - $vlUsed;
            $finalBalance = max(0, $finalBalance); // Can't be negative
            
            // Update the balance
            $updateStmt->execute([$finalBalance, $emp['leave_credit_id']]);
            
            printf("%-5s %-30s %-12s %-12s %-10s %-12s\n", 
                $emp['employee_id'],
                substr($emp['full_name'], 0, 28),
                $emp['current_balance'],
                number_format($vlUsed, 2),
                $correctBalance,
                number_format($finalBalance, 2)
            );
            
            $correctedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Employee {$emp['full_name']} (ID: {$emp['employee_id']}): " . $e->getMessage();
        }
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    if (!empty($errors)) {
        echo "⚠️ ERRORS ENCOUNTERED:\n";
        foreach ($errors as $error) {
            echo "   • $error\n";
        }
        echo "\n";
    }
    
    echo "📊 CORRECTION SUMMARY:\n";
    echo "   Total affected employees: $totalAffected\n";
    echo "   Successfully corrected: $correctedCount\n";
    echo "   Errors: " . count($errors) . "\n\n";
    
    if ($correctedCount > 0) {
        echo "✅ COMMIT CHANGES? This will permanently update the database.\n";
        echo "   Wrong VL given: 15.00 VL → Correct VL: $correctBalance VL (minus any used)\n\n";
        
        // Commit the transaction
        $pdo->commit();
        
        echo "✅ CHANGES COMMITTED SUCCESSFULLY!\n";
        echo "   $correctedCount employee leave balances have been corrected.\n\n";
        
        // Log this action
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO system_logs (log_type, log_message, created_at) 
                VALUES ('leave_credits_fix', ?, NOW())
            ");
            $logMessage = "Fixed 2026 VL balances: $correctedCount employees corrected from 15.00 to $correctBalance";
            $logStmt->execute([$logMessage]);
        } catch (Exception $e) {
            // Logging failed, but the main operation succeeded
            echo "   Note: Logging to system_logs failed (table may not exist)\n";
        }
        
    } else {
        $pdo->rollBack();
        echo "❌ NO CHANGES MADE - Rolling back transaction.\n";
    }
    
    echo "\n===========================================\n";
    echo "CORRECTION COMPLETE\n";
    echo "===========================================\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "\nTransaction rolled back. No changes were made.\n";
}
?>
