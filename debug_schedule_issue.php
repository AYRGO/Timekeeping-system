<?php
require_once 'Public/config/config.php';
require_once 'Public/config/session_handler.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Schedule Issue</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #333; color: white; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>🔍 Schedule Issue Debugger</h1>
    
    <?php
    try {
        $employee_id = $_SESSION['employee']['id'] ?? null;
        
        if (!$employee_id) {
            echo "<div class='box error'>❌ Not logged in</div>";
            exit;
        }
        
        echo "<div class='box'><strong>Logged in as Employee ID: {$employee_id}</strong></div>";
        
        // 1. Check if columns exist
        echo "<div class='box'>";
        echo "<h2>1️⃣ Database Structure Check</h2>";
        
        $checkColumn = $pdo->query("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'post_schedule_change_requests' 
            AND COLUMN_NAME IN ('processed_to_calendar', 'processed_at')
        ")->fetchAll();
        
        if (count($checkColumn) >= 2) {
            echo "<div class='success'>✅ Tracking columns exist</div>";
        } else {
            echo "<div class='error'>❌ Tracking columns missing! Need to add them.</div>";
        }
        echo "</div>";
        
        // 2. Check approved requests
        echo "<div class='box'>";
        echo "<h2>2️⃣ Approved Schedule Change Requests</h2>";
        
        $stmt = $pdo->prepare("
            SELECT pscr.*, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM post_schedule_change_requests pscr
            LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
            WHERE pscr.employee_id = ?
            AND LOWER(pscr.status) = 'approved'
            ORDER BY pscr.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$employee_id]);
        $approved = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($approved) > 0) {
            echo "<p><strong>Found " . count($approved) . " approved request(s):</strong></p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Status</th><th>Start Date</th><th>End Date</th><th>New Schedule</th><th>Processed?</th><th>Processed At</th></tr>";
            foreach ($approved as $req) {
                $processed = $req['processed_to_calendar'] ?? 0;
                $processedClass = $processed ? 'success' : 'warning';
                echo "<tr class='{$processedClass}'>";
                echo "<td>{$req['id']}</td>";
                echo "<td>{$req['status']}</td>";
                echo "<td>{$req['start_date']}</td>";
                echo "<td>{$req['end_date']}</td>";
                echo "<td>{$req['schedule_name']} ({$req['time_in']} - {$req['time_out']})</td>";
                echo "<td>" . ($processed ? '✅ Yes' : '❌ No') . "</td>";
                echo "<td>" . ($req['processed_at'] ?? 'Not yet') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='warning'>⚠️ No approved requests found</div>";
        }
        echo "</div>";
        
        // 3. Check employee_daily_schedules entries
        echo "<div class='box'>";
        echo "<h2>3️⃣ Calendar Entries (employee_daily_schedules)</h2>";
        
        $calStmt = $pdo->prepare("
            SELECT eds.*, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM employee_daily_schedules eds
            LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id
            WHERE eds.employee_id = ?
            AND eds.schedule_date BETWEEN '2025-10-27' AND '2025-10-31'
            ORDER BY eds.schedule_date ASC
        ");
        $calStmt->execute([$employee_id]);
        $calEntries = $calStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($calEntries) > 0) {
            echo "<p><strong>Found " . count($calEntries) . " calendar entry/entries for Oct 27-31:</strong></p>";
            echo "<table>";
            echo "<tr><th>Date</th><th>Schedule ID</th><th>Schedule Name</th><th>Time</th><th>Rest Day?</th><th>Source</th><th>Request ID</th></tr>";
            foreach ($calEntries as $entry) {
                echo "<tr>";
                echo "<td>{$entry['schedule_date']}</td>";
                echo "<td>{$entry['actual_schedule_id']}</td>";
                echo "<td>" . ($entry['schedule_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($entry['time_in'] ? "{$entry['time_in']} - {$entry['time_out']}" : 'N/A') . "</td>";
                echo "<td>" . ($entry['is_rest_day'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($entry['schedule_source'] ?? 'N/A') . "</td>";
                echo "<td>" . ($entry['request_id'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ NO calendar entries found for Oct 27-31! This is the problem!</div>";
            echo "<p>The approved request needs to be processed to create these entries.</p>";
        }
        echo "</div>";
        
        // 4. Check employee_default_schedules (fallback)
        echo "<div class='box'>";
        echo "<h2>4️⃣ Weekly Default Schedules (Fallback)</h2>";
        
        $defStmt = $pdo->prepare("
            SELECT eds.*, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ?
            ORDER BY eds.day_of_week ASC
        ");
        $defStmt->execute([$employee_id]);
        $defaults = $defStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($defaults) > 0) {
            echo "<p><strong>Your default weekly schedule:</strong></p>";
            echo "<table>";
            echo "<tr><th>Day</th><th>Schedule</th><th>Time</th><th>Rest Day?</th></tr>";
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($defaults as $def) {
                echo "<tr>";
                echo "<td>{$days[$def['day_of_week']]}</td>";
                echo "<td>" . ($def['schedule_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($def['time_in'] ? "{$def['time_in']} - {$def['time_out']}" : 'N/A') . "</td>";
                echo "<td>" . ($def['is_rest_day'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<div class='warning'>⚠️ If no daily overrides exist, the calendar shows THIS schedule</div>";
        } else {
            echo "<div>No default weekly schedule set</div>";
        }
        echo "</div>";
        
        // 5. What schedule ID 19 actually is
        echo "<div class='box'>";
        echo "<h2>5️⃣ What is Schedule ID 19?</h2>";
        
        $schedStmt = $pdo->prepare("SELECT * FROM work_schedules WHERE id = 19");
        $schedStmt->execute();
        $sched19 = $schedStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sched19) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Time In</th><th>Time Out</th></tr>";
            echo "<tr>";
            echo "<td>{$sched19['id']}</td>";
            echo "<td>{$sched19['name']}</td>";
            echo "<td>{$sched19['time_in']}</td>";
            echo "<td>{$sched19['time_out']}</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Schedule ID 19 not found!</div>";
        }
        echo "</div>";
        
        // SOLUTION
        echo "<div class='box' style='background: #fff3cd; border: 2px solid #ffc107;'>";
        echo "<h2>💡 SOLUTION</h2>";
        echo "<p><strong>Go to: <a href='process_schedules.php' style='color: blue; font-size: 18px;'>process_schedules.php</a></strong></p>";
        echo "<p>Click the 'Process These Requests Now' button to create the calendar entries.</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='box error'>❌ Error: " . $e->getMessage() . "</div>";
    }
    ?>
</body>
</html>
