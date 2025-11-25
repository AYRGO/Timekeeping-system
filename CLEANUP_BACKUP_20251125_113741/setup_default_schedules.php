<?php
// ============================================================================
// SETUP: Populate Weekly Default Schedules for All Employees
// ============================================================================
// This script creates weekly default schedules for employees based on their
// official_sched field in the employees table
// ============================================================================

require_once __DIR__ . '/Public/config/db.php';

echo "<h1>Setup Weekly Default Schedules</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f4f4f4;padding:10px;border:1px solid #ddd;}</style>";

// Step 1: Get all employees with their official schedule
$stmt = $pdo->query("
    SELECT id, fname, lname, official_sched, status
    FROM employees
    ORDER BY id
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Step 1: Employees Found</h2>";
echo "<p>Total employees: " . count($employees) . "</p>";

// Step 2: Check which employees already have default schedules
$stmt = $pdo->query("SELECT DISTINCT employee_id FROM employee_default_schedules");
$hasDefaults = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Step 2: Employees With Existing Default Schedules</h2>";
echo "<p>Employees with schedules: " . count($hasDefaults) . "</p>";

// Step 3: Create default schedules for employees without them
echo "<h2>Step 3: Creating Default Schedules</h2>";

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($employees as $emp) {
    $emp_id = $emp['id'];
    $name = $emp['fname'] . ' ' . $emp['lname'];
    $official_sched = $emp['official_sched'];
    $status = $emp['status'];
    
    // Skip if already has default schedule
    if (in_array($emp_id, $hasDefaults)) {
        echo "<p class='info'>⏭️ Skipping {$name} (ID: {$emp_id}) - Already has default schedule</p>";
        $skipped++;
        continue;
    }
    
    // Skip inactive employees
    if ($status !== 'Active') {
        echo "<p class='info'>⏭️ Skipping {$name} (ID: {$emp_id}) - Status: {$status}</p>";
        $skipped++;
        continue;
    }
    
    try {
        // If employee has an official_sched, use it for Mon-Fri
        if ($official_sched) {
            // Verify the schedule exists
            $schedStmt = $pdo->prepare("SELECT id, name FROM work_schedules WHERE id = ?");
            $schedStmt->execute([$official_sched]);
            $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
                echo "<p class='error'>❌ Error for {$name} (ID: {$emp_id}) - Schedule ID {$official_sched} not found</p>";
                $errors++;
                continue;
            }
            
            // Create Mon-Fri schedule, Sat-Sun rest days
            $effective_from = '2025-01-01'; // Start from beginning of year
            
            // Monday to Friday (days 1-5)
            for ($day = 1; $day <= 5; $day++) {
                $pdo->prepare("
                    INSERT INTO employee_default_schedules 
                    (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
                    VALUES (?, ?, ?, 0, ?)
                ")->execute([$emp_id, $day, $official_sched, $effective_from]);
            }
            
            // Sunday (0) and Saturday (6) as rest days
            $pdo->prepare("
                INSERT INTO employee_default_schedules 
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
                VALUES (?, 0, NULL, 1, ?)
            ")->execute([$emp_id, $effective_from]);
            
            $pdo->prepare("
                INSERT INTO employee_default_schedules 
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
                VALUES (?, 6, NULL, 1, ?)
            ")->execute([$emp_id, $effective_from]);
            
            echo "<p class='success'>✅ Created for {$name} (ID: {$emp_id}) - Schedule: {$schedule['name']} (Mon-Fri), Rest (Sat-Sun)</p>";
            $created++;
            
        } else {
            // No official schedule, create all days as rest days
            $effective_from = '2025-01-01';
            
            for ($day = 0; $day <= 6; $day++) {
                $pdo->prepare("
                    INSERT INTO employee_default_schedules 
                    (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)
                    VALUES (?, ?, NULL, 1, ?)
                ")->execute([$emp_id, $day, $effective_from]);
            }
            
            echo "<p class='success'>✅ Created for {$name} (ID: {$emp_id}) - All days marked as REST (no official schedule)</p>";
            $created++;
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error for {$name} (ID: {$emp_id}): " . $e->getMessage() . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>✅ Created: <strong>{$created}</strong></p>";
echo "<p>⏭️ Skipped: <strong>{$skipped}</strong></p>";
echo "<p>❌ Errors: <strong>{$errors}</strong></p>";

// Step 4: Run the SQL script to create cache table and populate it
if ($created > 0) {
    echo "<hr>";
    echo "<h2>Step 4: Next Steps</h2>";
    echo "<div class='info'>";
    echo "<h3>📋 Now run this SQL script to create and populate the cache:</h3>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin or your MySQL client</li>";
    echo "<li>Select your database: <strong>rss</strong></li>";
    echo "<li>Run the SQL file: <strong>create_daily_schedule_cache.sql</strong></li>";
    echo "<li>This will create the cache table and populate it with all schedules (past, present, future)</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><strong>Or run via command line:</strong></p>";
    echo "<pre>mysql -u root -p rss < create_daily_schedule_cache.sql</pre>";
    
    echo "<p><a href='check_schedule_data.php' style='display:inline-block;padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Check Schedule Data →</a></p>";
}

echo "<hr>";
echo "<h2>Verification Queries</h2>";
echo "<pre>";
echo "-- Check employee default schedules\n";
echo "SELECT e.id, e.fname, e.lname, eds.day_of_week, ws.name as schedule_name, eds.is_rest_day\n";
echo "FROM employees e\n";
echo "LEFT JOIN employee_default_schedules eds ON e.id = eds.employee_id\n";
echo "LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id\n";
echo "WHERE e.status = 'Active'\n";
echo "ORDER BY e.id, eds.day_of_week;\n";
echo "</pre>";
?>
