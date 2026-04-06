<?php
/**
 * Setup Leave Credit Tracking Table
 * Run this once on production to create the leave_credits_history table
 * 
 * Usage: Access via browser on production server
 * Add ?confirm=yes to actually create the table
 */

require_once __DIR__ . '/src/config/database.php';

echo "<pre>";
echo "=======================================================\n";
echo "LEAVE CREDIT TRACKING SETUP\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to database\n\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

// Check if table already exists
$tableExists = false;
try {
    $result = $pdo->query("SHOW TABLES LIKE 'leave_credits_history'");
    $tableExists = $result->rowCount() > 0;
} catch (PDOException $e) {
    // Ignore
}

if ($tableExists) {
    echo "📋 Table 'leave_credits_history' already exists!\n\n";
    
    // Show current row count
    $count = $pdo->query("SELECT COUNT(*) FROM leave_credits_history")->fetchColumn();
    echo "   Current records: $count\n\n";
    
    // Show table structure
    echo "Table Structure:\n";
    echo str_repeat("-", 60) . "\n";
    $columns = $pdo->query("DESCRIBE leave_credits_history")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        printf("   %-25s %s\n", $col['Field'], $col['Type']);
    }
    echo "\n";
    
    // Show recent history
    echo "Recent History (last 10 records):\n";
    echo str_repeat("-", 60) . "\n";
    $recent = $pdo->query("
        SELECT h.*, e.fname, e.lname 
        FROM leave_credits_history h 
        LEFT JOIN employees e ON h.employee_id = e.id 
        ORDER BY h.changed_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent)) {
        echo "   No records yet - tracking will begin with the next leave credit update.\n";
    } else {
        foreach ($recent as $r) {
            $name = trim(($r['fname'] ?? '') . ' ' . ($r['lname'] ?? ''));
            $change = $r['balance_change'] >= 0 ? '+' . $r['balance_change'] : $r['balance_change'];
            echo sprintf("   %s | %s | %s | %s | %s\n", 
                $r['changed_at'],
                substr($name ?: 'Employee #' . $r['employee_id'], 0, 20),
                $r['leave_type'],
                $change,
                $r['change_type']
            );
        }
    }
    
    echo "\n✅ Setup complete! The tracking feature is ready to use.\n";
    echo "</pre>";
    exit;
}

// Table doesn't exist - show what will be created
echo "📋 Table 'leave_credits_history' does NOT exist.\n\n";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "This script will create the leave_credits_history table for tracking.\n\n";
    echo "Table columns:\n";
    echo "   - history_id (primary key)\n";
    echo "   - leave_credit_id\n";
    echo "   - employee_id\n";
    echo "   - leave_type\n";
    echo "   - old_balance, new_balance, balance_change\n";
    echo "   - old_monthly_increment, new_monthly_increment\n";
    echo "   - change_type (ACCRUAL, ADMIN_INCREASE, ADMIN_DECREASE, etc.)\n";
    echo "   - change_reason\n";
    echo "   - changed_by (admin username)\n";
    echo "   - changed_at (timestamp)\n";
    echo "   - year\n\n";
    echo "⚠️  To create the table, add ?confirm=yes to the URL\n";
    echo "</pre>";
    exit;
}

// Create the table
echo "Creating table...\n\n";

$sql = "
CREATE TABLE IF NOT EXISTS `leave_credits_history` (
  `history_id` INT(11) NOT NULL AUTO_INCREMENT,
  `leave_credit_id` INT(11) DEFAULT NULL,
  `employee_id` INT(11) NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `old_balance` DECIMAL(5,2) DEFAULT NULL,
  `new_balance` DECIMAL(5,2) DEFAULT NULL,
  `balance_change` DECIMAL(5,2) DEFAULT NULL,
  `old_monthly_increment` DECIMAL(5,2) DEFAULT NULL,
  `new_monthly_increment` DECIMAL(5,2) DEFAULT NULL,
  `old_last_processed_month` INT(11) DEFAULT NULL,
  `new_last_processed_month` INT(11) DEFAULT NULL,
  `change_type` VARCHAR(50) DEFAULT NULL COMMENT 'ACCRUAL, DEDUCTION, ADMIN_INCREASE, ADMIN_DECREASE, LEAVE_USED, CARRY_OVER, FORFEITURE, ADJUSTMENT',
  `change_reason` TEXT DEFAULT NULL,
  `changed_by` VARCHAR(100) DEFAULT NULL COMMENT 'System or admin username',
  `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `year` INT(11) NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type` (`leave_type`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_leave_credit_id` (`leave_credit_id`),
  KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

try {
    $pdo->exec($sql);
    echo "✅ Table 'leave_credits_history' created successfully!\n\n";
    
    // Verify
    $columns = $pdo->query("DESCRIBE leave_credits_history")->fetchAll(PDO::FETCH_ASSOC);
    echo "Table Structure:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($columns as $col) {
        printf("   %-25s %s\n", $col['Field'], $col['Type']);
    }
    
    echo "\n";
    echo "=======================================================\n";
    echo "✅ SETUP COMPLETE!\n";
    echo "=======================================================\n\n";
    echo "The leave credit tracking feature is now active.\n";
    echo "All changes to leave credits will be logged automatically.\n\n";
    echo "To view history:\n";
    echo "   1. Go to Admin → Employees → Edit Employee\n";
    echo "   2. Click 'Leave Credits' tab\n";
    echo "   3. Click 'View History' button\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
