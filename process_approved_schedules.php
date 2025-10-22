<?php
/**
 * Simple Direct Processor - Just click this page to process approved schedules
 */

require_once 'Public/config/config.php';
require_once 'Public/config/session_handler.php';

header('Content-Type: text/html; charset=utf-8');

$employee_id = $_SESSION['employee']['id'] ?? null;

if (!$employee_id) {
    die("❌ Not logged in! Please log in first.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Process Approved Schedules</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #17a2b8; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; border: none; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: bold; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        .result { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #343a40; color: white; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Process Approved Schedule Changes</h1>
        
        <div class="info">
            <strong>👤 Employee ID:</strong> <?= $employee_id ?><br>
            <strong>📅 Today:</strong> <?= date('F j, Y') ?>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<div class='result'>";
            echo "<h2>⚙️ Processing...</h2>";
            
            try {
                // Get approved requests
                $stmt = $pdo->prepare("
                    SELECT pscr.*, ws.name as schedule_name, ws.time_in, ws.time_out
                    FROM post_schedule_change_requests pscr
                    LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
                    WHERE pscr.employee_id = ?
                    AND LOWER(pscr.status) = 'approved'
                    ORDER BY pscr.created_at DESC
                ");
                $stmt->execute([$employee_id]);
                $approved = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($approved) === 0) {
                    echo "<div class='info'>ℹ️ No approved schedule change requests found.</div>";
                } else {
                    echo "<p><strong>Found " . count($approved) . " approved request(s):</strong></p>";
                    
                    echo "<table>";
                    echo "<tr><th>Request ID</th><th>Period</th><th>New Schedule</th><th>Status</th></tr>";
                    
                    $totalProcessed = 0;
                    $totalDays = 0;
                    
                    foreach ($approved as $request) {
                        echo "<tr>";
                        echo "<td><strong>#{$request['id']}</strong></td>";
                        echo "<td>{$request['start_date']} to {$request['end_date']}</td>";
                        echo "<td>{$request['schedule_name']}<br><small>" . date('g:i A', strtotime($request['time_in'])) . " - " . date('g:i A', strtotime($request['time_out'])) . "</small></td>";
                        
                        try {
                            $pdo->beginTransaction();
                            
                            $start = new DateTime($request['start_date']);
                            $end = new DateTime($request['end_date']);
                            $interval = new DateInterval('P1D');
                            $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
                            
                            $daysProcessed = 0;
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
                                    // Update
                                    $updateStmt = $pdo->prepare("
                                        UPDATE employee_daily_schedules 
                                        SET actual_schedule_id = ?, is_rest_day = 0, updated_at = NOW()
                                        WHERE id = ?
                                    ");
                                    $updateStmt->execute([$request['work_schedule_id'], $existing['id']]);
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
                                    $insertStmt->execute([$request['employee_id'], $currentDate, $request['work_schedule_id']]);
                                }
                                $daysProcessed++;
                            }
                            
                            $pdo->commit();
                            $totalProcessed++;
                            $totalDays += $daysProcessed;
                            
                            echo "<td><span class='badge badge-success'>✅ Processed {$daysProcessed} days</span></td>";
                            
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            echo "<td><span class='badge' style='background:#dc3545;color:white;'>❌ Error: {$e->getMessage()}</span></td>";
                        }
                        
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                    
                    echo "<div class='success'>";
                    echo "<h3>✅ Processing Complete!</h3>";
                    echo "<p><strong>Summary:</strong></p>";
                    echo "<ul>";
                    echo "<li>Processed <strong>{$totalProcessed}</strong> request(s)</li>";
                    echo "<li>Created/Updated <strong>{$totalDays}</strong> calendar entries</li>";
                    echo "</ul>";
                    echo "<p><a href='Public/module/time_log_create.php#scheduleView'><button type='button'>📅 View Calendar</button></a></p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<h3>❌ Error</h3>";
                echo "<p>{$e->getMessage()}</p>";
                echo "</div>";
            }
            
            echo "</div>";
        } else {
            // Show form
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM post_schedule_change_requests 
                    WHERE employee_id = ?
                    AND LOWER(status) = 'approved'
                ");
                $stmt->execute([$employee_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'];
                
                if ($count > 0) {
                    echo "<div class='info'>";
                    echo "<h3>📋 Ready to Process</h3>";
                    echo "<p>You have <strong>{$count}</strong> approved schedule change request(s) waiting to be processed.</p>";
                    echo "<p>Click the button below to create calendar entries for these approved changes.</p>";
                    echo "</div>";
                    
                    echo "<form method='post'>";
                    echo "<button type='submit'>🚀 Process Approved Schedules Now</button>";
                    echo "</form>";
                } else {
                    echo "<div class='info'>";
                    echo "<h3>✅ All Caught Up!</h3>";
                    echo "<p>No pending approved schedule changes to process.</p>";
                    echo "<p><a href='Public/module/time_log_create.php#scheduleView'><button type='button'>📅 View Calendar</button></a></p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'><p>Error: {$e->getMessage()}</p></div>";
            }
        }
        ?>
    </div>
</body>
</html>
