<?php
/**
 * Direct Schedule Processor - Run this page to manually process approved schedules
 */

require_once 'Public/config/config.php';
require_once 'Public/config/session_handler.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Process Schedule Changes</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; }
        .processing { background: #ffc107; }
    </style>
</head>
<body>
    <h1>🔄 Schedule Change Processor</h1>
    
    <?php
    try {
        // Check and add columns if needed
        echo "<div class='box'>";
        echo "<h2>Step 1: Database Setup</h2>";
        
        $checkColumn = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'post_schedule_change_requests' 
            AND COLUMN_NAME = 'processed_to_calendar'
        ")->fetch();
        
        if (!$checkColumn) {
            echo "<p>Adding tracking columns...</p>";
            $pdo->exec("
                ALTER TABLE post_schedule_change_requests 
                ADD COLUMN processed_to_calendar TINYINT(1) DEFAULT 0 AFTER status,
                ADD COLUMN processed_at DATETIME NULL AFTER processed_to_calendar
            ");
            echo "<div class='success'>✅ Columns added!</div>";
        } else {
            echo "<div class='success'>✅ Database ready</div>";
        }
        echo "</div>";
        
        // Check for approved requests
        echo "<div class='box'>";
        echo "<h2>Step 2: Approved Requests</h2>";
        
        $stmt = $pdo->prepare("
            SELECT pscr.*, e.first_name, e.last_name, ws.time_in, ws.time_out
            FROM post_schedule_change_requests pscr
            JOIN employees e ON pscr.employee_id = e.id
            LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
            WHERE LOWER(pscr.status) = 'approved'
            AND (pscr.processed_to_calendar = 0 OR pscr.processed_to_calendar IS NULL)
            ORDER BY pscr.created_at DESC
        ");
        $stmt->execute();
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Found " . count($pending) . " pending request(s)</strong></p>";
        
        if (count($pending) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Employee</th><th>Start Date</th><th>End Date</th><th>Schedule</th></tr>";
            foreach ($pending as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>{$req['first_name']} {$req['last_name']}</td>";
                echo "<td>{$req['start_date']}</td>";
                echo "<td>{$req['end_date']}</td>";
                echo "<td>" . date('g:i A', strtotime($req['time_in'])) . " - " . date('g:i A', strtotime($req['time_out'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if (!isset($_POST['process'])) {
                echo "<form method='post'>";
                echo "<button type='submit' name='process'>🚀 Process These Requests Now</button>";
                echo "</form>";
            }
        } else {
            echo "<div class='info'>ℹ️ No pending approved requests to process</div>";
        }
        echo "</div>";
        
        // Process if requested
        if (isset($_POST['process'])) {
            echo "<div class='box'>";
            echo "<h2>Step 3: Processing...</h2>";
            
            $processed = 0;
            $totalDays = 0;
            
            foreach ($pending as $request) {
                try {
                    $pdo->beginTransaction();
                    
                    echo "<div class='processing' style='padding:10px;margin:10px 0;border-radius:5px;'>";
                    echo "<strong>Processing Request ID {$request['id']}</strong><br>";
                    
                    $start = new DateTime($request['start_date']);
                    $end = new DateTime($request['end_date']);
                    $interval = new DateInterval('P1D');
                    $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
                    
                    $days = 0;
                    foreach ($dateRange as $date) {
                        $currentDate = $date->format('Y-m-d');
                        
                        // Check if entry exists
                        $checkStmt = $pdo->prepare("
                            SELECT id FROM employee_daily_schedules 
                            WHERE employee_id = ? AND schedule_date = ?
                        ");
                        $checkStmt->execute([$request['employee_id'], $currentDate]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            // Update
                            $updateStmt = $pdo->prepare("
                                UPDATE employee_daily_schedules 
                                SET actual_schedule_id = ?,
                                    is_rest_day = 0,
                                    schedule_source = 'approved_request',
                                    request_id = ?,
                                    updated_at = NOW()
                                WHERE id = ?
                            ");
                            $updateStmt->execute([
                                $request['work_schedule_id'],
                                $request['id'],
                                $existing['id']
                            ]);
                            echo "  Updated: {$currentDate}<br>";
                        } else {
                            // Insert
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
                            $insertStmt->execute([
                                $request['employee_id'],
                                $currentDate,
                                $request['work_schedule_id']
                            ]);
                            echo "  Inserted: {$currentDate}<br>";
                        }
                        $days++;
                    }
                    
                    // Mark as processed
                    $markStmt = $pdo->prepare("
                        UPDATE post_schedule_change_requests 
                        SET processed_to_calendar = 1, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $markStmt->execute([$request['id']]);
                    
                    $pdo->commit();
                    $processed++;
                    $totalDays += $days;
                    
                    echo "<strong>✅ Success - Created {$days} calendar entries</strong>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    echo "<div class='error'>❌ Error: {$e->getMessage()}</div>";
                }
            }
            
            echo "<div class='success'>";
            echo "<h3>✅ Processing Complete!</h3>";
            echo "<p>Processed {$processed} request(s) and created {$totalDays} calendar entries</p>";
            echo "<p><a href='?'>↻ Refresh</a> | <a href='Public/module/time_log_create.php#scheduleView'>View Calendar</a></p>";
            echo "</div>";
            echo "</div>";
        }
        
        // Show recent calendar entries
        echo "<div class='box'>";
        echo "<h2>Recent Calendar Entries</h2>";
        $calendarStmt = $pdo->query("
            SELECT eds.*, e.first_name, e.last_name, ws.time_in, ws.time_out
            FROM employee_daily_schedules eds
            JOIN employees e ON eds.employee_id = e.id
            LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id
            ORDER BY eds.created_at DESC
            LIMIT 20
        ");
        $entries = $calendarStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($entries) > 0) {
            echo "<p>Showing last " . count($entries) . " entries from approved requests:</p>";
            echo "<table>";
            echo "<tr><th>Employee</th><th>Date</th><th>Schedule</th><th>Created</th></tr>";
            foreach ($entries as $entry) {
                echo "<tr>";
                echo "<td>{$entry['first_name']} {$entry['last_name']}</td>";
                echo "<td>{$entry['schedule_date']}</td>";
                echo "<td>" . date('g:i A', strtotime($entry['time_in'])) . " - " . date('g:i A', strtotime($entry['time_out'])) . "</td>";
                echo "<td>" . date('M j, Y g:i A', strtotime($entry['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>No calendar entries yet. Process some requests first!</div>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'><h3>❌ Error</h3><p>{$e->getMessage()}</p></div>";
    }
    ?>
</body>
</html>
