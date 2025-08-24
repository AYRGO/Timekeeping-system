<?php
// Run this script to sync all approved time adjustments to time_logs
date_default_timezone_set('Asia/Manila');
require_once('../config/db.php');

try {
    $stmt = $pdo->query("SELECT employee_id, log_date, requested_time_in, requested_time_out FROM post_time_adjustment_requests WHERE status = 'approved'");
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($adjustments as $adj) {
        // Only update if requested_time_in or requested_time_out is not null
        if ($adj['requested_time_in'] || $adj['requested_time_out']) {
            $update = $pdo->prepare("UPDATE time_logs SET time_in = ?, time_out = ? WHERE employee_id = ? AND log_date = ?");
            $update->execute([
                $adj['requested_time_in'],
                $adj['requested_time_out'],
                $adj['employee_id'],
                $adj['log_date']
            ]);
            if ($update->rowCount() > 0) $updated++;
        }
    }
    echo "Sync complete. Updated $updated time_logs records.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
