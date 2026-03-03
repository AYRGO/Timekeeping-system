<?php
/**
 * Cron job to automatically move approved/declined overtime requests to archive table
 * Run this script every 5 minutes or as needed via cron:
 * */5 * * * * /usr/bin/php /path/to/your/project/Public/module/cron_move_ot_requests.php
 */

// Set working directory
chdir(__DIR__);

include('../config/db.php');
date_default_timezone_set('Asia/Manila');

function moveCompletedOTRequests($pdo) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get all approved, declined, or rejected requests from post_ot_requests
        $selectStmt = $pdo->prepare("
            SELECT * FROM post_ot_requests 
            WHERE LOWER(status) IN ('approved', 'declined', 'rejected')
        ");
        $selectStmt->execute();
        $completedRequests = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($completedRequests)) {
            // Insert completed requests into post2_overtime_requests
            $insertStmt = $pdo->prepare("
                INSERT INTO post2_overtime_requests 
                (id, employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, attachment, reason, status, created_at, approved_at, approved_by, notified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $movedCount = 0;
            foreach ($completedRequests as $request) {
                $insertStmt->execute([
                    $request['id'],
                    $request['employee_id'],
                    $request['time_log_id'],
                    $request['time_in'],
                    $request['time_out'],
                    $request['ot_duration'],
                    $request['ot_type'],
                    $request['attachment'],
                    $request['reason'],
                    $request['status'],
                    $request['created_at'],
                    $request['approved_at'],
                    $request['approved_by'],
                    $request['notified']
                ]);
                $movedCount++;
            }
            
            // Delete moved requests from original table
            $deleteStmt = $pdo->prepare("
                DELETE FROM post_ot_requests 
                WHERE LOWER(status) IN ('approved', 'declined', 'rejected')
            ");
            $deleteStmt->execute();
            
            $pdo->commit();
            
            $timestamp = date('Y-m-d H:i:s');
            echo "[$timestamp] Successfully moved {$movedCount} completed OT requests to archive table\n";
            error_log("Cron: Successfully moved {$movedCount} completed OT requests to archive table");
            
            return $movedCount;
        }
        
        $pdo->commit();
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] No completed OT requests to move\n";
        return 0;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $timestamp = date('Y-m-d H:i:s');
        $errorMsg = "Error moving completed OT requests: " . $e->getMessage();
        echo "[$timestamp] $errorMsg\n";
        error_log("Cron: $errorMsg");
        return false;
    }
}

// Run the migration
$result = moveCompletedOTRequests($pdo);

if ($result !== false) {
    echo "Cron job completed successfully\n";
} else {
    echo "Cron job failed\n";
    exit(1);
}
?>