<?php
/**
 * Check Hostinger Leave Credits - Diagnostic Script v2
 * Fixed: Removed last_processed_month column reference
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

echo "<pre>";
echo "===========================================\n";
echo "HOSTINGER DATABASE LEAVE CREDITS CHECK\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to Hostinger database successfully!\n\n";
    
    // 1. Count employees with 15 VL balance by Emp_Type and year
    echo "📊 EMPLOYEES WITH 15 VL BALANCE BY TYPE AND YEAR:\n";
    echo str_repeat("-", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.Emp_Type,
            lc.year,
            COUNT(*) as count,
            MIN(lc.updated_at) as first_update,
            MAX(lc.updated_at) as last_update
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.balance = 15
        GROUP BY e.Emp_Type, lc.year
        ORDER BY lc.year DESC, e.Emp_Type
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "No employees with exactly 15 VL balance found.\n";
    } else {
        printf("%-15s %-6s %-8s %-22s %-22s\n", "Emp_Type", "Year", "Count", "First Update", "Last Update");
        printf("%-15s %-6s %-8s %-22s %-22s\n", str_repeat("-", 15), str_repeat("-", 6), str_repeat("-", 8), str_repeat("-", 22), str_repeat("-", 22));
        foreach ($results as $row) {
            printf("%-15s %-6s %-8s %-22s %-22s\n", 
                $row['Emp_Type'] ?? 'NULL',
                $row['year'],
                $row['count'],
                $row['first_update'] ?? 'NULL',
                $row['last_update'] ?? 'NULL'
            );
        }
    }
    
    // 2. List specific Regular employees with 15 VL in 2026
    echo "\n\n📋 REGULAR EMPLOYEES WITH 15 VL IN 2026:\n";
    echo str_repeat("-", 90) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            e.status,
            lc.balance,
            lc.carry_over,
            lc.updated_at,
            lc.year
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.year = 2026
        AND e.Emp_Type = 'Regular'
        AND lc.balance = 15
        ORDER BY e.fname, e.lname
        LIMIT 50
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "No Regular employees with 15 VL in 2026 found.\n";
    } else {
        printf("%-5s %-30s %-12s %-8s %-12s %-22s\n", "ID", "Name", "Emp_Type", "Balance", "Carry Over", "Updated At");
        printf("%-5s %-30s %-12s %-8s %-12s %-22s\n", str_repeat("-", 5), str_repeat("-", 30), str_repeat("-", 12), str_repeat("-", 8), str_repeat("-", 12), str_repeat("-", 22));
        foreach ($results as $row) {
            printf("%-5s %-30s %-12s %-8s %-12s %-22s\n", 
                $row['id'],
                substr($row['full_name'], 0, 28),
                $row['Emp_Type'],
                $row['balance'],
                $row['carry_over'] ?? 'NULL',
                $row['updated_at'] ?? 'NULL'
            );
        }
        echo "\nTotal: " . count($results) . " employees shown (max 50)\n";
    }
    
    // 3. Check all vacation leave credits for 2026 (summary)
    echo "\n\n📊 ALL VACATION LEAVE CREDITS SUMMARY FOR 2026:\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            e.Emp_Type,
            e.status,
            COUNT(*) as total,
            ROUND(AVG(lc.balance), 2) as avg_balance,
            MIN(lc.balance) as min_balance,
            MAX(lc.balance) as max_balance
        FROM leave_credits lc
        JOIN employees e ON lc.employee_id = e.id
        WHERE lc.leave_type = 'vacation' 
        AND lc.year = 2026
        GROUP BY e.Emp_Type, e.status
        ORDER BY e.Emp_Type, e.status
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "No vacation leave records for 2026 found.\n";
    } else {
        printf("%-15s %-10s %-8s %-12s %-10s %-10s\n", "Emp_Type", "Status", "Count", "Avg Balance", "Min", "Max");
        printf("%-15s %-10s %-8s %-12s %-10s %-10s\n", str_repeat("-", 15), str_repeat("-", 10), str_repeat("-", 8), str_repeat("-", 12), str_repeat("-", 10), str_repeat("-", 10));
        foreach ($results as $row) {
            printf("%-15s %-10s %-8s %-12s %-10s %-10s\n", 
                $row['Emp_Type'] ?? 'NULL',
                $row['status'],
                $row['total'],
                $row['avg_balance'],
                $row['min_balance'],
                $row['max_balance']
            );
        }
    }
    
    // 4. Check when leave credits were last modified
    echo "\n\n⏰ RECENT LEAVE CREDIT UPDATES (Last 30 days):\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            DATE(lc.updated_at) as update_date,
            COUNT(*) as records_updated,
            COUNT(DISTINCT lc.employee_id) as unique_employees,
            GROUP_CONCAT(DISTINCT lc.leave_type) as leave_types
        FROM leave_credits lc
        WHERE lc.updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(lc.updated_at)
        ORDER BY update_date DESC
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "No leave credit updates in the last 30 days.\n";
    } else {
        printf("%-12s %-18s %-18s %-30s\n", "Date", "Records Updated", "Unique Employees", "Leave Types");
        printf("%-12s %-18s %-18s %-30s\n", str_repeat("-", 12), str_repeat("-", 18), str_repeat("-", 18), str_repeat("-", 30));
        foreach ($results as $row) {
            printf("%-12s %-18s %-18s %-30s\n", 
                $row['update_date'],
                $row['records_updated'],
                $row['unique_employees'],
                $row['leave_types']
            );
        }
    }
    
    echo "\n\n===========================================\n";
    echo "CHECK COMPLETE\n";
    echo "===========================================\n";
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
