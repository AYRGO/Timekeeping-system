<?php
/**
 * Test Schedule Processing System
 * Quick test to verify the automatic schedule processing works
 */

require_once 'config/config.php';

echo "<!DOCTYPE html><html><head><title>Test Schedule Processing</title>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;background:#e7f6e7;padding:10px;margin:10px 0;border-radius:5px;}";
echo ".error{color:red;background:#ffe7e7;padding:10px;margin:10px 0;border-radius:5px;}";
echo ".info{color:blue;background:#e7f0ff;padding:10px;margin:10px 0;border-radius:5px;}";
echo "table{width:100%;border-collapse:collapse;background:white;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#4CAF50;color:white;}</style></head><body>";

echo "<h1>🧪 Schedule Processing System Test</h1>";

try {
    // Check if columns exist
    echo "<div class='info'><h3>Step 1: Checking Database Structure</h3>";
    
    $checkColumn = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'post_schedule_change_requests' 
        AND COLUMN_NAME = 'processed_to_calendar'
    ")->fetch();
    
    if (!$checkColumn) {
        echo "<p>⚠️ Adding tracking columns to post_schedule_change_requests...</p>";
        $pdo->exec("
            ALTER TABLE post_schedule_change_requests 
            ADD COLUMN processed_to_calendar TINYINT(1) DEFAULT 0 AFTER status,
            ADD COLUMN processed_at DATETIME NULL AFTER processed_to_calendar
        ");
        echo "<p class='success'>✅ Columns added successfully!</p>";
    } else {
        echo "<p class='success'>✅ Tracking columns exist</p>";
    }
    echo "</div>";
    
    // Check for approved requests
    echo "<div class='info'><h3>Step 2: Checking for Approved Requests</h3>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN processed_to_calendar = 1 THEN 1 ELSE 0 END) as processed,
               SUM(CASE WHEN processed_to_calendar = 0 OR processed_to_calendar IS NULL THEN 1 ELSE 0 END) as pending
        FROM post_schedule_change_requests 
        WHERE status = 'approved'
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>📊 Total Approved: <strong>{$stats['total']}</strong></p>";
    echo "<p>✅ Already Processed: <strong>{$stats['processed']}</strong></p>";
    echo "<p>⏳ Pending Processing: <strong>{$stats['pending']}</strong></p>";
    echo "</div>";
    
    // Show pending requests
    if ($stats['pending'] > 0) {
        echo "<div class='info'><h3>Step 3: Pending Requests Details</h3>";
        $pendingStmt = $pdo->query("
            SELECT pscr.id, pscr.employee_id, e.first_name, e.last_name, 
                   pscr.work_schedule_id, pscr.start_date, pscr.end_date,
                   ws.time_in, ws.time_out, ws.name as schedule_name
            FROM post_schedule_change_requests pscr
            JOIN employees e ON pscr.employee_id = e.id
            LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
            WHERE pscr.status = 'approved' 
            AND (pscr.processed_to_calendar = 0 OR pscr.processed_to_calendar IS NULL)
            ORDER BY pscr.created_at DESC
            LIMIT 10
        ");
        $pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Employee</th><th>Schedule</th><th>Date Range</th><th>Days</th></tr>";
        foreach ($pending as $req) {
            $start = new DateTime($req['start_date']);
            $end = new DateTime($req['end_date']);
            $days = $start->diff($end)->days + 1;
            
            $scheduleName = $req['schedule_name'] ?: date('g:i A', strtotime($req['time_in'])) . ' - ' . date('g:i A', strtotime($req['time_out']));
            
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['first_name']} {$req['last_name']}</td>";
            echo "<td>{$scheduleName}</td>";
            echo "<td>{$req['start_date']} to {$req['end_date']}</td>";
            echo "<td>{$days} days</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Offer to process
        echo "<div class='success'>";
        echo "<h3>Step 4: Process These Requests</h3>";
        echo "<p>Click below to process all pending approved requests:</p>";
        echo "<form method='post' action=''>";
        echo "<button type='submit' name='process' style='padding:15px 30px;background:#4CAF50;color:white;border:none;border-radius:5px;font-size:16px;cursor:pointer;'>🚀 Process Now</button>";
        echo "</form>";
        echo "</div>";
    }
    
    // Process if requested
    if (isset($_POST['process'])) {
        echo "<div class='info'><h3>🚀 Processing Requests...</h3>";
        
        $processed = 0;
        $errors = [];
        
        $requests = $pdo->query("
            SELECT id, employee_id, work_schedule_id, start_date, end_date
            FROM post_schedule_change_requests 
            WHERE status = 'approved' 
            AND (processed_to_calendar = 0 OR processed_to_calendar IS NULL)
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($requests as $request) {
            try {
                $pdo->beginTransaction();
                
                $start = new DateTime($request['start_date']);
                $end = new DateTime($request['end_date']);
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
                
                $daysProcessed = 0;
                foreach ($dateRange as $date) {
                    $currentDate = $date->format('Y-m-d');
                    
                    $checkStmt = $pdo->prepare("
                        SELECT id FROM employee_daily_schedules 
                        WHERE employee_id = ? AND schedule_date = ?
                    ");
                    $checkStmt->execute([$request['employee_id'], $currentDate]);
                    $existing = $checkStmt->fetch();
                    
                    if ($existing) {
                        $updateStmt = $pdo->prepare("
                            UPDATE employee_daily_schedules 
                            SET actual_schedule_id = ?, is_rest_day = 0,
                                schedule_source = 'approved_request', request_id = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$request['work_schedule_id'], $request['id'], $existing['id']]);
                    } else {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO employee_daily_schedules 
                            (employee_id, schedule_date, actual_schedule_id, is_rest_day, 
                             schedule_source, request_id, created_at)
                            VALUES (?, ?, ?, 0, 'approved_request', ?, NOW())
                        ");
                        $insertStmt->execute([$request['employee_id'], $currentDate, $request['work_schedule_id'], $request['id']]);
                    }
                    $daysProcessed++;
                }
                
                $markStmt = $pdo->prepare("
                    UPDATE post_schedule_change_requests 
                    SET processed_to_calendar = 1, processed_at = NOW()
                    WHERE id = ?
                ");
                $markStmt->execute([$request['id']]);
                
                $pdo->commit();
                $processed++;
                
                echo "<p class='success'>✅ Processed Request ID {$request['id']} - Created {$daysProcessed} daily schedule entries</p>";
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Request ID {$request['id']}: " . $e->getMessage();
                echo "<p class='error'>❌ Error processing Request ID {$request['id']}: {$e->getMessage()}</p>";
            }
        }
        
        echo "<h3 class='success'>✅ Processing Complete!</h3>";
        echo "<p>Processed: <strong>{$processed}</strong> request(s)</p>";
        if (count($errors) > 0) {
            echo "<p>Errors: <strong>" . count($errors) . "</strong></p>";
        }
        echo "<p><a href='test_schedule_processing.php' style='color:#4CAF50;text-decoration:none;'>🔄 Refresh Page</a></p>";
        echo "</div>";
    }
    
    // Show sample calendar entry
    echo "<div class='info'><h3>Step 5: Check Calendar Entries</h3>";
    $calendarStmt = $pdo->query("
        SELECT eds.*, e.first_name, e.last_name, ws.time_in, ws.time_out, ws.name as schedule_name
        FROM employee_daily_schedules eds
        JOIN employees e ON eds.employee_id = e.id
        LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id
        WHERE eds.schedule_source = 'approved_request'
        ORDER BY eds.created_at DESC
        LIMIT 10
    ");
    $calendarEntries = $calendarStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($calendarEntries) > 0) {
        echo "<p class='success'>✅ Found " . count($calendarEntries) . " calendar entries from approved requests:</p>";
        echo "<table>";
        echo "<tr><th>Employee</th><th>Date</th><th>Schedule</th><th>Created</th></tr>";
        foreach ($calendarEntries as $entry) {
            $scheduleName = $entry['schedule_name'] ?: date('g:i A', strtotime($entry['time_in'])) . ' - ' . date('g:i A', strtotime($entry['time_out']));
            echo "<tr>";
            echo "<td>{$entry['first_name']} {$entry['last_name']}</td>";
            echo "<td>{$entry['schedule_date']}</td>";
            echo "<td>{$scheduleName}</td>";
            echo "<td>" . date('M j, Y g:i A', strtotime($entry['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No calendar entries found yet. Process some requests first!</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Error</h3><p>{$e->getMessage()}</p></div>";
}

echo "</body></html>";
?>
