<?php
// Verify Emp_Type column exists and check employee distribution
require_once __DIR__ . '/Public/config/db.php';

echo "=== EMPLOYEE TYPE VERIFICATION ===\n\n";

try {
    // Check if Emp_Type column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'Emp_Type'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✅ Emp_Type column EXISTS\n";
        echo "   Type: {$column['Type']}\n";
        echo "   Null: {$column['Null']}\n";
        echo "   Default: {$column['Default']}\n\n";
        
        // Get employee distribution
        $stmt = $pdo->query("
            SELECT 
                Emp_Type,
                COUNT(*) as count,
                GROUP_CONCAT(CONCAT(fname, ' ', lname) SEPARATOR ', ') as names
            FROM employees 
            WHERE status = 'active'
            GROUP BY Emp_Type
        ");
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "=== ACTIVE EMPLOYEE DISTRIBUTION ===\n";
        foreach ($distribution as $row) {
            $empType = $row['Emp_Type'] ?: 'NULL/Not Set';
            echo "\n{$empType}: {$row['count']} employee(s)\n";
            if ($row['count'] <= 10) {
                echo "   Names: {$row['names']}\n";
            }
        }
        
        // Show who will get accruals
        $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active' AND Emp_Type = 'Regular'");
        $regularCount = $stmt->fetchColumn();
        
        echo "\n=== ACCRUAL ELIGIBILITY ===\n";
        echo "✅ Will receive accruals: {$regularCount} Regular employee(s)\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active' AND (Emp_Type = 'Probationary' OR Emp_Type IS NULL)");
        $probationaryCount = $stmt->fetchColumn();
        echo "❌ Will NOT receive accruals: {$probationaryCount} Probationary/Unset employee(s)\n";
        
    } else {
        echo "❌ Emp_Type column DOES NOT EXIST!\n";
        echo "\nRun this SQL command to create it:\n\n";
        echo "ALTER TABLE `employees` \n";
        echo "ADD COLUMN `Emp_Type` ENUM('Regular', 'Probationary') NOT NULL DEFAULT 'Probationary' \n";
        echo "AFTER `status`;\n";
    }
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END OF VERIFICATION ===\n";
?>
