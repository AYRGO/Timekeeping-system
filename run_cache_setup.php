<?php
// ============================================================================
// RUN SQL: Create and Populate Daily Schedule Cache
// ============================================================================
require_once __DIR__ . '/Public/config/db.php';

echo "<h1>Create Daily Schedule Cache System</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f4f4f4;padding:10px;border:1px solid #ddd;overflow:auto;max-height:400px;}</style>";

// Read the SQL file
$sqlFile = __DIR__ . '/create_daily_schedule_cache.sql';

if (!file_exists($sqlFile)) {
    echo "<p class='error'>❌ SQL file not found: {$sqlFile}</p>";
    exit;
}

echo "<p class='info'>📁 Reading SQL file...</p>";
$sql = file_get_contents($sqlFile);

echo "<p class='info'>🔧 Executing SQL statements...</p>";

try {
    // Split SQL into individual statements
    // Remove comments and split by delimiter changes
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    $inDelimiterChange = false;
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        // Check for DELIMITER change
        if (stripos($line, 'DELIMITER') === 0) {
            if ($currentStatement) {
                $statements[] = $currentStatement;
                $currentStatement = '';
            }
            $parts = explode(' ', $line);
            if (isset($parts[1])) {
                $delimiter = trim($parts[1]);
            }
            continue;
        }
        
        // Add line to current statement
        $currentStatement .= ' ' . $line;
        
        // Check if statement is complete
        if (substr($line, -strlen($delimiter)) === $delimiter) {
            // Remove delimiter from end
            $currentStatement = substr($currentStatement, 0, -strlen($delimiter));
            $currentStatement = trim($currentStatement);
            
            if (!empty($currentStatement)) {
                $statements[] = $currentStatement;
            }
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty($currentStatement)) {
        $statements[] = trim($currentStatement);
    }
    
    echo "<p class='success'>✅ Found " . count($statements) . " SQL statements</p>";
    
    // Execute each statement
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for major operations
            if (stripos($statement, 'CREATE TABLE') !== false) {
                echo "<p class='success'>✅ Created table</p>";
            } elseif (stripos($statement, 'CREATE PROCEDURE') !== false) {
                preg_match('/CREATE PROCEDURE\s+(\w+)/i', $statement, $matches);
                $procName = $matches[1] ?? 'unknown';
                echo "<p class='success'>✅ Created procedure: {$procName}</p>";
            } elseif (stripos($statement, 'CREATE TRIGGER') !== false) {
                preg_match('/CREATE TRIGGER\s+(\w+)/i', $statement, $matches);
                $trigName = $matches[1] ?? 'unknown';
                echo "<p class='success'>✅ Created trigger: {$trigName}</p>";
            } elseif (stripos($statement, 'CREATE EVENT') !== false) {
                echo "<p class='success'>✅ Created maintenance event</p>";
            } elseif (stripos($statement, 'CALL populate_all_employees') !== false) {
                echo "<p class='info'>🔄 Populating cache... (this may take a minute)</p>";
                flush();
                ob_flush();
            }
            
        } catch (PDOException $e) {
            // Some errors are expected (like "procedure already exists")
            if (stripos($e->getMessage(), 'already exists') !== false) {
                echo "<p class='info'>ℹ️ Object already exists, continuing...</p>";
            } else {
                echo "<p class='error'>❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "</p>";
                $errors++;
            }
        }
    }
    
    echo "<hr>";
    echo "<h2>Execution Summary</h2>";
    echo "<p>✅ Executed: <strong>{$executed}</strong> statements</p>";
    echo "<p>❌ Errors: <strong>{$errors}</strong></p>";
    
    if ($errors === 0) {
        echo "<hr>";
        echo "<h2 class='success'>🎉 Success! Daily Schedule Cache System Created</h2>";
        
        // Verify cache was populated
        $stmt = $pdo->query("SELECT COUNT(*) as count, COUNT(DISTINCT employee_id) as employees, MIN(schedule_date) as earliest, MAX(schedule_date) as latest FROM employee_daily_schedule_cache");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats['count'] > 0) {
            echo "<div class='success'>";
            echo "<h3>📊 Cache Statistics</h3>";
            echo "<ul>";
            echo "<li><strong>Total Schedule Records:</strong> " . number_format($stats['count']) . "</li>";
            echo "<li><strong>Employees Cached:</strong> {$stats['employees']}</li>";
            echo "<li><strong>Date Range:</strong> {$stats['earliest']} to {$stats['latest']}</li>";
            echo "</ul>";
            echo "</div>";
            
            // Show sample data
            echo "<h3>📅 Sample Schedule Data</h3>";
            $sample = $pdo->query("
                SELECT 
                    e.fname, e.lname,
                    c.schedule_date,
                    DAYNAME(c.schedule_date) as day_name,
                    c.schedule_name,
                    TIME_FORMAT(c.time_in, '%h:%i %p') as time_in,
                    TIME_FORMAT(c.time_out, '%h:%i %p') as time_out,
                    c.is_rest_day,
                    c.is_holiday,
                    c.holiday_name,
                    c.source
                FROM employee_daily_schedule_cache c
                JOIN employees e ON c.employee_id = e.id
                WHERE c.schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY c.schedule_date, e.lname
                LIMIT 20
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            if ($sample) {
                echo "<table style='border-collapse:collapse;width:100%;'>";
                echo "<tr style='background:#4CAF50;color:white;'><th style='padding:8px;border:1px solid #ddd;'>Employee</th><th style='padding:8px;border:1px solid #ddd;'>Date</th><th style='padding:8px;border:1px solid #ddd;'>Day</th><th style='padding:8px;border:1px solid #ddd;'>Schedule</th><th style='padding:8px;border:1px solid #ddd;'>Time</th><th style='padding:8px;border:1px solid #ddd;'>Type</th><th style='padding:8px;border:1px solid #ddd;'>Source</th></tr>";
                foreach ($sample as $row) {
                    $displaySchedule = $row['is_holiday'] ? "HOLIDAY: {$row['holiday_name']}" : 
                                      ($row['is_rest_day'] ? "REST DAY" : $row['schedule_name']);
                    $displayTime = (!$row['is_rest_day'] && !$row['is_holiday']) ? "{$row['time_in']} - {$row['time_out']}" : "-";
                    $type = $row['is_holiday'] ? 'Holiday' : ($row['is_rest_day'] ? 'Rest' : 'Work');
                    
                    echo "<tr>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$row['fname']} {$row['lname']}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$row['schedule_date']}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$row['day_name']}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$displaySchedule}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$displayTime}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$type}</td>";
                    echo "<td style='padding:8px;border:1px solid #ddd;'>{$row['source']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            echo "<hr>";
            echo "<h2>✅ Next Steps</h2>";
            echo "<ol>";
            echo "<li>The cache is now populated with schedules from 6 months ago to 12 months in the future</li>";
            echo "<li>The system will automatically update when schedules change (via triggers)</li>";
            echo "<li>A maintenance event runs daily to keep future dates populated</li>";
            echo "<li>Test the calendars - they should now show schedules!</li>";
            echo "</ol>";
            
            echo "<p><a href='check_schedule_data.php' style='display:inline-block;padding:10px 20px;background:#2196F3;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>📊 Check Schedule Data</a>";
            echo "<a href='employee-edit.php?id=1#current-schedule' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>📅 View Calendar</a></p>";
        } else {
            echo "<p class='error'>⚠️ Cache table created but no data was populated. Check if employees have default schedules set.</p>";
            echo "<p><a href='setup_default_schedules.php'>Run Default Schedule Setup →</a></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fatal error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
