<?php
/**
 * FIX 15 LEAVE CREDITS - February 28, 2026
 * 
 * This script corrects two issues:
 * 1. EMPLOYEES WITH SL > 5 - Should be max 5 (policy violation)
 * 2. REGULAR EMPLOYEES WITH 15 VL - Should be ~2.50 (monthly accrual)
 * 
 * POLICY:
 * - SL: Max 5 days for all employees (one-time grant upon regularization)
 * - VL Regular: 1.25/month accrual = 2.50 by Feb 28
 * - VL Probationary: Full 15 days upfront (correct, no change needed)
 * 
 * ⚠️ BACKUP YOUR DATABASE BEFORE RUNNING!
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

$isConfirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

echo "<pre>";
echo "===========================================\n";
echo "FIX 15 LEAVE CREDITS - FEBRUARY 28, 2026\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Mode: " . ($isConfirmed ? "EXECUTE FIX" : "DRY RUN (Preview)") . "\n";
echo "===========================================\n\n";

if (!$isConfirmed) {
    echo "⚠️  DRY RUN MODE - No changes will be made.\n";
    echo "    To execute fixes, add ?confirm=yes to URL\n\n";
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Calculate expected VL for Regular employees as of Feb 28, 2026
    // Jan accrual (1.25) + Feb accrual (1.25) = 2.50
    $correctVLBalance = 2.50;
    $correctSLBalance = 5.00; // Max SL is 5 days
    
    $fixResults = [
        'sl_fixed' => 0,
        'vl_fixed' => 0,
        'errors' => []
    ];
    
    if ($isConfirmed) {
        $pdo->beginTransaction();
    }
    
    // ========================================
    // FIX #1: Employees with SL > 5 (should be max 5)
    // ========================================
    echo "🔧 FIX #1: EMPLOYEES WITH SL > 5 → MAX 5 SL\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            lc.id as credit_id,
            lc.employee_id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            lc.balance as current_sl,
            lc.updated_at
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'sick' 
        AND lc.year = 2026
        AND lc.balance > 5
        AND e.status = 'active'
        ORDER BY e.fname, e.lname
    ");
    $slToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($slToFix)) {
        echo "✅ No employees with SL > 5 found. Nothing to fix.\n";
    } else {
        printf("%-5s %-35s %-15s %-12s %-12s\n", 
            "ID", "Name", "Emp_Type", "Current SL", "New SL");
        printf("%-5s %-35s %-15s %-12s %-12s\n", 
            str_repeat("-", 5), str_repeat("-", 35), str_repeat("-", 15), 
            str_repeat("-", 12), str_repeat("-", 12));
        
        $updateSL = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        
        foreach ($slToFix as $emp) {
            $currentSL = floatval($emp['current_sl']);
            
            // If current SL > 5, we need to cap it at 5
            // But account for any SL that may have been used beyond 5
            // (e.g., if they had 15 and used 11, they now have 4 - don't touch)
            $newSL = min($currentSL, $correctSLBalance);
            
            printf("%-5s %-35s %-15s %-10s %-10s\n", 
                $emp['employee_id'],
                substr($emp['full_name'], 0, 33),
                $emp['Emp_Type'] ?? 'NULL',
                number_format($currentSL, 2),
                number_format($newSL, 2)
            );
            
            if ($isConfirmed) {
                try {
                    $updateSL->execute([$newSL, $emp['credit_id']]);
                    $fixResults['sl_fixed']++;
                } catch (Exception $e) {
                    $fixResults['errors'][] = "SL - {$emp['full_name']}: " . $e->getMessage();
                }
            }
        }
        echo "\n   Total identified: " . count($slToFix) . " employees\n";
    }
    
    // ========================================
    // FIX #2: REGULAR employees with 15 VL (should be ~2.50)
    // ========================================
    echo "\n\n🔧 FIX #2: REGULAR EMPLOYEES WITH 15 VL → 2.50 VL\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            lc.id as credit_id,
            lc.employee_id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            lc.balance as current_vl,
            lc.carry_over,
            lc.updated_at
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.year = 2026
        AND e.Emp_Type = 'Regular'
        AND lc.balance = 15
        AND e.status = 'active'
        ORDER BY e.fname, e.lname
    ");
    $vlToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($vlToFix)) {
        echo "✅ No Regular employees with 15 VL found. Nothing to fix.\n";
    } else {
        printf("%-5s %-35s %-15s %-10s %-10s %-10s\n", 
            "ID", "Name", "Emp_Type", "Old VL", "VL Used", "New VL");
        printf("%-5s %-35s %-15s %-10s %-10s %-10s\n", 
            str_repeat("-", 5), str_repeat("-", 35), str_repeat("-", 15), 
            str_repeat("-", 10), str_repeat("-", 10), str_repeat("-", 10));
        
        $updateVL = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        
        foreach ($vlToFix as $emp) {
            // Calculate how much VL was used (if any)
            $vlUsed = 15 - floatval($emp['current_vl']);
            $vlUsed = max(0, $vlUsed);
            
            // New balance = 2.50 minus whatever was already used
            $newVL = $correctVLBalance - $vlUsed;
            $newVL = max(0, $newVL); // Can't go negative
            
            // If carry_over exists, add it
            $carryOver = floatval($emp['carry_over'] ?? 0);
            $newVL += $carryOver;
            $newVL = min($newVL, 15); // Cap at 15 max
            
            printf("%-5s %-35s %-15s %-10s %-10s %-10s\n", 
                $emp['employee_id'],
                substr($emp['full_name'], 0, 33),
                $emp['Emp_Type'],
                $emp['current_vl'],
                number_format($vlUsed, 2),
                number_format($newVL, 2)
            );
            
            if ($isConfirmed) {
                try {
                    $updateVL->execute([$newVL, $emp['credit_id']]);
                    $fixResults['vl_fixed']++;
                } catch (Exception $e) {
                    $fixResults['errors'][] = "VL - {$emp['full_name']}: " . $e->getMessage();
                }
            }
        }
        echo "\n   Total identified: " . count($vlToFix) . " employees\n";
    }
    
    // ========================================
    // Summary & Commit
    // ========================================
    echo "\n\n===========================================\n";
    
    if ($isConfirmed) {
        if (!empty($fixResults['errors'])) {
            echo "⚠️  ERRORS ENCOUNTERED:\n";
            foreach ($fixResults['errors'] as $error) {
                echo "   • $error\n";
            }
            echo "\n";
            
            // Rollback on errors
            $pdo->rollBack();
            echo "❌ CHANGES ROLLED BACK due to errors.\n";
        } else {
            $pdo->commit();
            echo "✅ FIX APPLIED SUCCESSFULLY!\n";
            echo "   • SL fixed: {$fixResults['sl_fixed']} employees (capped at 5)\n";
            echo "   • VL fixed: {$fixResults['vl_fixed']} employees (15 → 2.50)\n";
        }
    } else {
        echo "📋 DRY RUN SUMMARY:\n";
        echo "   • Employees with SL > 5 to fix: " . count($slToFix) . "\n";
        echo "   • Regular employees with 15 VL to fix: " . count($vlToFix) . "\n";
        echo "\n⚠️  To apply these fixes, run:\n";
        echo "   fix_15_leave_credits_feb2026.php?confirm=yes\n";
    }
    echo "===========================================\n";
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
    if ($isConfirmed && isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Changes rolled back.\n";
    }
}
echo "</pre>";
?>
