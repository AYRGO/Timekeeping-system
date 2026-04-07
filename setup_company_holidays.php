<?php
/**
 * Create company_holidays table and populate with Philippine holidays
 */

require_once 'Public/config/db.php';

if (!isset($pdo)) {
    die("Database connection not available.");
}

echo "<pre>";
echo "========================================\n";
echo "Setting up company_holidays table...\n";
echo "========================================\n\n";

// Create company_holidays table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS company_holidays (
            id INT(11) NOT NULL AUTO_INCREMENT,
            holiday_date DATE NOT NULL,
            holiday_name VARCHAR(255) NOT NULL,
            holiday_type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',
            is_recurring TINYINT(1) DEFAULT 0 COMMENT 'If 1, applies every year',
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_holiday_date (holiday_date),
            INDEX idx_holiday_date (holiday_date),
            INDEX idx_holiday_type (holiday_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        COMMENT='Company holiday calendar'
    ");
    echo "✓ Created company_holidays table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "✓ company_holidays table already exists\n";
    } else {
        echo "❌ Error creating table: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Insert company holidays for 2025-2026 (Philippines + Australia)
echo "\nAdding company holidays (Philippines & Australia)...\n";

$holidays = [
    // 2026 Australian Public Holidays
    ['2026-01-01', 'New Year\'s Day', 'regular', 1],
    ['2026-01-26', 'Australia Day', 'regular', 1],
    ['2026-04-03', 'Good Friday', 'regular', 0],
    ['2026-04-04', 'Easter Saturday', 'regular', 0],
    ['2026-04-05', 'Easter Sunday', 'special_non_working', 0],
    ['2026-04-06', 'Easter Monday', 'regular', 0],
    ['2026-04-25', 'Anzac Day', 'regular', 1],
    ['2026-06-08', 'King\'s Birthday', 'regular', 1],
    ['2026-10-05', 'Labour Day', 'regular', 1],
    ['2026-12-25', 'Christmas Day', 'regular', 1],
    ['2026-12-26', 'Boxing Day', 'regular', 1],
    ['2026-12-28', 'Additional public holiday for Boxing Day', 'regular', 0],
    
    // 2026 Philippine Public Holidays
    ['2026-04-02', 'Maundy Thursday', 'regular', 0],
    ['2026-04-09', 'Araw ng Kagitingan (Day of Valor)', 'regular', 1],
    ['2026-05-01', 'Labor Day', 'regular', 1],
    ['2026-06-12', 'Independence Day', 'regular', 1],
    ['2026-08-25', 'Ninoy Aquino Day', 'special_non_working', 1],
    ['2026-08-31', 'National Heroes Day', 'regular', 0],
    ['2026-11-01', 'All Saints\' Day', 'special_non_working', 1],
    ['2026-11-30', 'Bonifacio Day', 'regular', 1],
    ['2026-12-30', 'Rizal Day', 'regular', 1],
    ['2026-12-24', 'Christmas Eve', 'special_non_working', 1],
    ['2026-12-31', 'New Year\'s Eve', 'special_non_working', 1],
    
    // 2025 Australian Public Holidays
    ['2025-01-01', 'New Year\'s Day', 'regular', 1],
    ['2025-01-26', 'Australia Day', 'regular', 1],
    ['2025-04-17', 'Maundy Thursday', 'regular', 0],
    ['2025-04-18', 'Good Friday', 'regular', 0],
    ['2025-04-19', 'Easter Saturday', 'regular', 0],
    ['2025-04-20', 'Easter Sunday', 'special_non_working', 0],
    ['2025-04-21', 'Easter Monday', 'regular', 0],
    ['2025-04-25', 'Anzac Day', 'regular', 1],
    ['2025-06-09', 'King\'s Birthday', 'regular', 1],
    ['2025-10-06', 'Labour Day', 'regular', 1],
    ['2025-12-25', 'Christmas Day', 'regular', 1],
    ['2025-12-26', 'Boxing Day', 'regular', 1],
    
    // 2025 Philippine Public Holidays
    ['2025-04-09', 'Araw ng Kagitingan (Day of Valor)', 'regular', 1],
    ['2025-05-01', 'Labor Day', 'regular', 1],
    ['2025-06-12', 'Independence Day', 'regular', 1],
    ['2025-08-25', 'Ninoy Aquino Day', 'special_non_working', 1],
    ['2025-08-31', 'National Heroes Day', 'regular', 0],
    ['2025-11-01', 'All Saints\' Day', 'special_non_working', 1],
    ['2025-11-30', 'Bonifacio Day', 'regular', 1],
    ['2025-12-30', 'Rizal Day', 'regular', 1],
    ['2025-12-24', 'Christmas Eve', 'special_non_working', 1],
    ['2025-12-31', 'New Year\'s Eve', 'special_non_working', 1],
];

$insertStmt = $pdo->prepare("
    INSERT INTO company_holidays (holiday_date, holiday_name, holiday_type, is_recurring)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        holiday_name = VALUES(holiday_name),
        holiday_type = VALUES(holiday_type),
        is_recurring = VALUES(is_recurring)
");

$inserted = 0;
foreach ($holidays as $holiday) {
    try {
        $insertStmt->execute($holiday);
        $inserted++;
    } catch (PDOException $e) {
        // Skip duplicates silently
    }
}

echo "✓ Added/updated $inserted holidays\n";

echo "\n========================================\n";
echo "✓ Setup complete!\n";
echo "========================================\n";
echo "</pre>";

?>
