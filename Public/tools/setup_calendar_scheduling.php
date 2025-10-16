<?php
/**
 * Calendar Scheduling System Setup Script
 * Run this file once to create all necessary database tables
 */

require_once(__DIR__ . '/../config/db.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Calendar Scheduling System - Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f4f4f4; padding: 15px; overflow-x: auto; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h1>📅 Calendar Scheduling System - Database Setup</h1>";

try {
    echo "<div class='info'><strong>Starting database setup...</strong></div>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../../Database/create_calendar_scheduling_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;\s*$/m', $sql)
        )
    );
    
    $executed = 0;
    $errors = [];
    
    echo "<h2>Executing SQL Statements:</h2>";
    
    foreach ($statements as $statement) {
        // Skip comments and empty statements
        if (empty($statement) || str_starts_with($statement, '--') || str_starts_with($statement, '/*')) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Extract table/view name for display
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Created table: <strong>{$matches[1]}</strong></div>";
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Created view: <strong>{$matches[1]}</strong></div>";
            } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Inserted data into: <strong>{$matches[1]}</strong></div>";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Altered table: <strong>{$matches[1]}</strong></div>";
            } else {
                echo "<div class='success'>✓ Executed statement</div>";
            }
        } catch (PDOException $e) {
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo "<h2>📊 Setup Summary:</h2>";
    echo "<div class='info'>";
    echo "<strong>Total statements executed: $executed</strong><br>";
    echo "<strong>Errors encountered: " . count($errors) . "</strong>";
    echo "</div>";
    
    if (count($errors) > 0) {
        echo "<h2>⚠️ Errors:</h2>";
        foreach ($errors as $error) {
            echo "<div class='error'>";
            echo "<strong>Statement:</strong><br><pre>" . htmlspecialchars($error['statement']) . "</pre>";
            echo "<strong>Error:</strong> " . htmlspecialchars($error['error']);
            echo "</div>";
        }
    }
    
    // Verify tables were created
    echo "<h2>✅ Verification - Tables Created:</h2>";
    $tables = [
        'employee_daily_schedules',
        'employee_default_schedules',
        'company_holidays',
        'schedule_override_history',
        'rotating_schedule_patterns',
        'rotating_schedule_pattern_days',
        'employee_rotating_schedules'
    ];
    
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<div class='success'>✓ <strong>$table</strong> - $count records</div>";
            } else {
                echo "<div class='error'>✗ <strong>$table</strong> - NOT FOUND</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>✗ <strong>$table</strong> - Error: {$e->getMessage()}</div>";
        }
    }
    
    echo "<h2>📖 Next Steps:</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li><strong>Setup Default Schedules:</strong> Navigate to employee management to set up default weekly schedules</li>";
    echo "<li><strong>Configure Holidays:</strong> The system has been pre-populated with Philippine holidays for 2025</li>";
    echo "<li><strong>Test the Calendar:</strong> Visit the employee schedule calendar to verify functionality</li>";
    echo "<li><strong>Apply Schedule Requests:</strong> Approved schedule change requests can now be automatically applied to the calendar</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>📚 Database Structure:</h2>";
    echo "<div class='info'>";
    echo "<strong>Main Tables:</strong><br>";
    echo "<ul>";
    echo "<li><code>employee_daily_schedules</code> - Daily schedule overrides (highest priority)</li>";
    echo "<li><code>employee_default_schedules</code> - Default weekly patterns (Mon-Sun)</li>";
    echo "<li><code>company_holidays</code> - Holiday calendar</li>";
    echo "<li><code>schedule_override_history</code> - Audit trail of changes</li>";
    echo "</ul>";
    echo "<strong>Rotating Schedules:</strong><br>";
    echo "<ul>";
    echo "<li><code>rotating_schedule_patterns</code> - Pattern definitions</li>";
    echo "<li><code>rotating_schedule_pattern_days</code> - Pattern details</li>";
    echo "<li><code>employee_rotating_schedules</code> - Employee assignments</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🔧 Using the System:</h2>";
    echo "<div class='info'><pre>";
    echo htmlspecialchars("
// Example: Get employee schedule for a date
require_once('config/CalendarScheduler.php');
\$scheduler = new CalendarScheduler(\$pdo);
\$schedule = \$scheduler->getEmployeeScheduleForDate(\$employee_id, '2025-10-20');

// Example: Set a daily override
\$scheduler->setDailySchedule(\$employee_id, '2025-10-20', \$work_schedule_id, 'override');

// Example: Apply approved schedule change request
\$scheduler->applyScheduleChangeRequest(\$request_id, \$admin_id);

// Example: Get monthly view
\$monthly = \$scheduler->getMonthlySchedule(\$employee_id, 2025, 10);
    ");
    echo "</pre></div>";
    
    echo "<a href='../views/employee-edit.php?id=1' class='btn'>View Employee Schedule</a>";
    echo "<a href='../admin/admin_homepage.php' class='btn'>Go to Admin Dashboard</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><strong>File:</strong> " . htmlspecialchars($e->getFile());
    echo "<br><strong>Line:</strong> " . $e->getLine();
    echo "</div>";
}

echo "</body></html>";
?>
