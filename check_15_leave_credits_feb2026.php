<?php
/**
 * Check for Employees with 15 SL/VL - February 28, 2026
 * 
 * POLICY REMINDER:
 * - Probationary: Get full credits upfront (15 VL)
 * - Regular: Monthly accrual (1.25 VL/month) = 2.50 VL by end of Feb
 * - SL: Max 5 days for any employee (one-time grant upon regularization)
 * 
 * ISSUE: Some employees still show 15 SL or 15 VL when they shouldn't
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

echo "<pre>";
echo "===========================================\n";
echo "CHECK EMPLOYEES WITH 15 SL/VL - FEB 28, 2026\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // ================================
    // 1. Check employees with SL > 5 (MAJOR ISSUE - max should be 5)
    // ================================
    echo "🚨 EMPLOYEES WITH SICK LEAVE (SL) > 5 - INCORRECT!\n";
    echo "   (Maximum SL should be 5 days per policy)\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            e.status,
            lc.balance as sl_balance,
            lc.carry_over,
            lc.updated_at,
            lc.year
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'sick' 
        AND lc.year = 2026
        AND lc.balance > 5
        AND e.status = 'active'
        ORDER BY e.Emp_Type, e.fname, e.lname
    ");
    $resultsSL = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($resultsSL)) {
        echo "✅ No employees with SL > 5 found.\n";
    } else {
        printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
            "ID", "Name", "Emp_Type", "SL", "Carry Over", "Updated At");
        printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
            str_repeat("-", 5), str_repeat("-", 35), str_repeat("-", 15), 
            str_repeat("-", 10), str_repeat("-", 10), str_repeat("-", 22));
        
        foreach ($resultsSL as $row) {
            printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
                $row['id'],
                substr($row['full_name'], 0, 33),
                $row['Emp_Type'] ?? 'NULL',
                $row['sl_balance'],
                $row['carry_over'] ?? 'N/A',
                $row['updated_at'] ?? 'NULL'
            );
        }
        echo "\n⚠️  Total employees with SL > 5: " . count($resultsSL) . "\n";
    }
    
    // ================================
    // 2. Check REGULAR employees with 15 VL (INCORRECT)
    // ================================
    echo "\n\n🚨 REGULAR EMPLOYEES WITH 15 VL - INCORRECT!\n";
    echo "   (Regular should have ~2.50 VL by Feb 28: 1.25 x 2 months)\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            e.status,
            lc.balance as vl_balance,
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
    $resultsVLRegular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($resultsVLRegular)) {
        echo "✅ No Regular employees with 15 VL found.\n";
    } else {
        printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
            "ID", "Name", "Emp_Type", "VL", "Carry Over", "Updated At");
        printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
            str_repeat("-", 5), str_repeat("-", 35), str_repeat("-", 15), 
            str_repeat("-", 10), str_repeat("-", 10), str_repeat("-", 22));
        
        foreach ($resultsVLRegular as $row) {
            printf("%-5s %-35s %-15s %-10s %-10s %-22s\n", 
                $row['id'],
                substr($row['full_name'], 0, 33),
                $row['Emp_Type'],
                $row['vl_balance'],
                $row['carry_over'] ?? 'N/A',
                $row['updated_at'] ?? 'NULL'
            );
        }
        echo "\n⚠️  Total Regular employees with 15 VL: " . count($resultsVLRegular) . "\n";
    }
    
    // ================================
    // 3. Check Probationary employees with 15 VL (This is expected/correct)
    // ================================
    echo "\n\n✅ PROBATIONARY EMPLOYEES WITH 15 VL - EXPECTED (Correct):\n";
    echo str_repeat("-", 100) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            e.status,
            lc.balance as vl_balance,
            lc.carry_over,
            lc.updated_at
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.year = 2026
        AND e.Emp_Type = 'Probationary'
        AND lc.balance = 15
        AND e.status = 'active'
        ORDER BY e.fname, e.lname
    ");
    $resultsVLProba = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($resultsVLProba)) {
        echo "No Probationary employees with 15 VL found.\n";
    } else {
        printf("%-5s %-35s %-15s %-10s %-22s\n", 
            "ID", "Name", "Emp_Type", "VL", "Updated At");
        printf("%-5s %-35s %-15s %-10s %-22s\n", 
            str_repeat("-", 5), str_repeat("-", 35), str_repeat("-", 15), 
            str_repeat("-", 10), str_repeat("-", 22));
        
        foreach ($resultsVLProba as $row) {
            printf("%-5s %-35s %-15s %-10s %-22s\n", 
                $row['id'],
                substr($row['full_name'], 0, 33),
                $row['Emp_Type'],
                $row['vl_balance'],
                $row['updated_at'] ?? 'NULL'
            );
        }
        echo "\n✅ Total Probationary with 15 VL: " . count($resultsVLProba) . " (This is expected)\n";
    }
    
    // ================================
    // 4. Summary Statistics
    // ================================
    echo "\n\n📊 LEAVE CREDITS SUMMARY FOR 2026:\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            lc.leave_type,
            e.Emp_Type,
            COUNT(*) as total,
            MIN(lc.balance) as min_balance,
            MAX(lc.balance) as max_balance,
            ROUND(AVG(lc.balance), 2) as avg_balance,
            SUM(CASE WHEN lc.balance = 15 THEN 1 ELSE 0 END) as count_15
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.year = 2026
        AND e.status = 'active'
        GROUP BY lc.leave_type, e.Emp_Type
        ORDER BY lc.leave_type, e.Emp_Type
    ");
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printf("%-12s %-15s %-8s %-8s %-8s %-10s %-12s\n", 
        "Leave Type", "Emp_Type", "Count", "Min", "Max", "Avg", "Count=15");
    printf("%-12s %-15s %-8s %-8s %-8s %-10s %-12s\n", 
        str_repeat("-", 12), str_repeat("-", 15), str_repeat("-", 8), 
        str_repeat("-", 8), str_repeat("-", 8), str_repeat("-", 10), str_repeat("-", 12));
    
    foreach ($summary as $row) {
        printf("%-12s %-15s %-8s %-8s %-8s %-10s %-12s\n", 
            $row['leave_type'],
            $row['Emp_Type'] ?? 'NULL',
            $row['total'],
            $row['min_balance'],
            $row['max_balance'],
            $row['avg_balance'],
            $row['count_15']
        );
    }
    
    // ================================
    // Issues Count
    // ================================
    $totalIssues = count($resultsSL) + count($resultsVLRegular);
    
    echo "\n\n===========================================\n";
    echo "🔍 ISSUES FOUND: $totalIssues employees need correction\n";
    echo "   - Employees with SL > 5: " . count($resultsSL) . "\n";
    echo "   - Regular employees with 15 VL: " . count($resultsVLRegular) . "\n";
    echo "===========================================\n";
    
    if ($totalIssues > 0) {
        echo "\n💡 To fix these issues, run:\n";
        echo "   fix_15_leave_credits_feb2026.php?confirm=yes\n";
    }
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
