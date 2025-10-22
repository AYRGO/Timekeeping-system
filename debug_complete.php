<?php
require_once 'Public/config/config.php';
require_once 'Public/config/session_handler.php';

header('Content-Type: text/html; charset=utf-8');

$employee_id = $_SESSION['employee']['id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Step by Step</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; line-height: 1.6; }
        .box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #333; color: white; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .step { font-size: 24px; font-weight: bold; color: #007bff; margin-top: 20px; }
        button { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>🔍 Complete Debug - Why Calendar Not Updating</h1>
    
    <?php
    if (!$employee_id) {
        echo "<div class='error'>❌ Not logged in! Employee ID: NULL</div>";
        exit;
    }
    
    echo "<div class='info'>✅ Logged in as Employee ID: <strong>{$employee_id}</strong></div>";
    
    try {
        echo "<div class='step'>STEP 1: Does employee_daily_schedules table exist?</div>";
        echo "<div class='box'>";
        
        $tableExists = $pdo->query("SHOW TABLES LIKE 'employee_daily_schedules'")->fetch();
        
        if (!$tableExists) {
            echo "<div class='error'>";
            echo "<h3>❌ CRITICAL PROBLEM FOUND!</h3>";
            echo "<p>The table <code>employee_daily_schedules</code> DOES NOT EXIST in your database!</p>";
            echo "<p>This is why the calendar system cannot work. The processing scripts try to insert data into a table that doesn't exist.</p>";
            echo "<h4>SOLUTION:</h4>";
            echo "<p>We need to create this table first. Click the button below:</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='create_table'>Create employee_daily_schedules Table Now</button>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
            
            if (isset($_POST['create_table'])) {
                echo "<div class='box'>";
                echo "<h3>Creating table...</h3>";
                
                try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS `employee_daily_schedules` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `employee_id` int(11) NOT NULL,
                          `schedule_date` date NOT NULL,
                          `actual_schedule_id` int(11) DEFAULT NULL,
                          `is_rest_day` tinyint(1) DEFAULT 0,
                          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                          `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `unique_employee_date` (`employee_id`, `schedule_date`),
                          KEY `idx_employee` (`employee_id`),
                          KEY `idx_date` (`schedule_date`),
                          KEY `idx_schedule` (`actual_schedule_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ");
                    
                    echo "<div class='success'>";
                    echo "<h3>✅ SUCCESS!</h3>";
                    echo "<p>Table <code>employee_daily_schedules</code> created successfully!</p>";
                    echo "<p><a href='?'>↻ Refresh this page</a> to continue debugging.</p>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Error creating table: " . $e->getMessage() . "</div>";
                }
                echo "</div>";
            }
            
            exit;
        }
        
        echo "<div class='success'>✅ Table exists!</div>";
        
        // Show table structure
        $columns = $pdo->query("DESCRIBE employee_daily_schedules")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table structure:</strong></p>";
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><code>{$col['Field']}</code></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // STEP 2
        echo "<div class='step'>STEP 2: Check approved requests in post_schedule_change_requests</div>";
        echo "<div class='box'>";
        
        $stmt = $pdo->prepare("
            SELECT id, employee_id, work_schedule_id, start_date, end_date, status, created_at
            FROM post_schedule_change_requests 
            WHERE employee_id = ?
            AND LOWER(status) = 'approved'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$employee_id]);
        $approved = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($approved) > 0) {
            echo "<div class='success'>✅ Found " . count($approved) . " approved request(s)</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Status</th><th>Start Date</th><th>End Date</th><th>New Schedule ID</th><th>Created</th></tr>";
            foreach ($approved as $req) {
                $highlight = ($req['id'] == 20) ? 'style="background: #ffeb3b;"' : '';
                echo "<tr {$highlight}>";
                echo "<td><strong>{$req['id']}</strong></td>";
                echo "<td>{$req['status']}</td>";
                echo "<td>{$req['start_date']}</td>";
                echo "<td>{$req['end_date']}</td>";
                echo "<td><strong>{$req['work_schedule_id']}</strong></td>";
                echo "<td>{$req['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ No approved requests found!</div>";
        }
        echo "</div>";
        
        // STEP 3
        echo "<div class='step'>STEP 3: Check what's in employee_daily_schedules (Oct 27-31)</div>";
        echo "<div class='box'>";
        
        $stmt = $pdo->prepare("
            SELECT eds.*, ws.time_in, ws.time_out, ws.name as schedule_name
            FROM employee_daily_schedules eds
            LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id
            WHERE eds.employee_id = ?
            AND eds.schedule_date BETWEEN '2025-10-27' AND '2025-10-31'
            ORDER BY eds.schedule_date ASC
        ");
        $stmt->execute([$employee_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($entries) > 0) {
            echo "<div class='success'>✅ Found " . count($entries) . " calendar entries</div>";
            echo "<table>";
            echo "<tr><th>Date</th><th>Schedule ID</th><th>Schedule Name</th><th>Time</th><th>Rest Day?</th><th>Created</th></tr>";
            foreach ($entries as $entry) {
                echo "<tr>";
                echo "<td><strong>{$entry['schedule_date']}</strong></td>";
                echo "<td>{$entry['actual_schedule_id']}</td>";
                echo "<td>{$entry['schedule_name']}</td>";
                echo "<td>" . ($entry['time_in'] ? date('g:i A', strtotime($entry['time_in'])) . " - " . date('g:i A', strtotime($entry['time_out'])) : 'N/A') . "</td>";
                echo "<td>" . ($entry['is_rest_day'] ? 'Yes' : 'No') . "</td>";
                echo "<td>{$entry['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<div class='info'>ℹ️ The calendar shows these schedules.</div>";
        } else {
            echo "<div class='error'>";
            echo "<h3>❌ PROBLEM: No calendar entries for Oct 27-31!</h3>";
            echo "<p>This is why you see the default schedule. The approved request has NOT been processed yet.</p>";
            echo "<p><strong>SOLUTION:</strong> Click the button below to process approved requests:</p>";
            echo "<form method='post'>";
            echo "<button type='submit' name='process_now'>Process Approved Requests Now</button>";
            echo "</form>";
            echo "</div>";
        }
        echo "</div>";
        
        // Process if requested
        if (isset($_POST['process_now'])) {
            echo "<div class='box' style='background: #e3f2fd;'>";
            echo "<h2>🚀 Processing Approved Requests...</h2>";
            
            $stmt = $pdo->prepare("
                SELECT id, employee_id, work_schedule_id, start_date, end_date
                FROM post_schedule_change_requests 
                WHERE employee_id = ?
                AND LOWER(status) = 'approved'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$employee_id]);
            $toProcess = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($toProcess as $request) {
                echo "<p><strong>Processing Request ID {$request['id']}</strong></p>";
                
                try {
                    $pdo->beginTransaction();
                    
                    $start = new DateTime($request['start_date']);
                    $end = new DateTime($request['end_date']);
                    $interval = new DateInterval('P1D');
                    $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
                    
                    $count = 0;
                    foreach ($dateRange as $date) {
                        $currentDate = $date->format('Y-m-d');
                        
                        // Check if exists
                        $checkStmt = $pdo->prepare("
                            SELECT id FROM employee_daily_schedules 
                            WHERE employee_id = ? AND schedule_date = ?
                        ");
                        $checkStmt->execute([$request['employee_id'], $currentDate]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            $updateStmt = $pdo->prepare("
                                UPDATE employee_daily_schedules 
                                SET actual_schedule_id = ?, is_rest_day = 0, updated_at = NOW()
                                WHERE id = ?
                            ");
                            $updateStmt->execute([$request['work_schedule_id'], $existing['id']]);
                            echo "  ✏️ Updated {$currentDate} → Schedule ID {$request['work_schedule_id']}<br>";
                        } else {
                            $insertStmt = $pdo->prepare("
                                INSERT INTO employee_daily_schedules 
                                (employee_id, schedule_date, actual_schedule_id, is_rest_day,
                                 is_holiday, is_company_holiday, has_override, has_time_log,
                                 scheduled_hours, actual_hours, overtime_hours, undertime_hours, late_minutes,
                                 attendance_status, time_log_status, display_priority,
                                 created_at, updated_at)
                                VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0.00, 0.00, 0.00, 0.00, 0,
                                        'scheduled', 'no_log', 1, NOW(), NOW())
                            ");
                            $insertStmt->execute([$request['employee_id'], $currentDate, $request['work_schedule_id']]);
                            echo "  ➕ Inserted {$currentDate} → Schedule ID {$request['work_schedule_id']}<br>";
                        }
                        $count++;
                    }
                    
                    $pdo->commit();
                    echo "<div class='success'>✅ Processed {$count} days successfully!</div>";
                    
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
                }
            }
            
            echo "<p><a href='?'><button>↻ Refresh to See Results</button></a></p>";
            echo "<p><a href='Public/module/time_log_create.php#scheduleView'><button>📅 Go to Calendar</button></a></p>";
            echo "</div>";
        }
        
        // STEP 4
        echo "<div class='step'>STEP 4: What does work_schedule_id 19 look like?</div>";
        echo "<div class='box'>";
        
        $stmt = $pdo->prepare("SELECT * FROM work_schedules WHERE id = 19");
        $stmt->execute();
        $sched = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sched) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Time In</th><th>Time Out</th></tr>";
            echo "<tr>";
            echo "<td>{$sched['id']}</td>";
            echo "<td>{$sched['name']}</td>";
            echo "<td><strong>" . date('g:i A', strtotime($sched['time_in'])) . "</strong></td>";
            echo "<td><strong>" . date('g:i A', strtotime($sched['time_out'])) . "</strong></td>";
            echo "</tr>";
            echo "</table>";
            echo "<div class='info'>ℹ️ This is the schedule that should appear on Oct 27-31 after processing.</div>";
        } else {
            echo "<div class='error'>❌ Schedule ID 19 not found!</div>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'><h3>❌ Fatal Error</h3><p>" . $e->getMessage() . "</p></div>";
    }
    ?>
</body>
</html>
