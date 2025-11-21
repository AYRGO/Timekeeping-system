<?php
session_start();
error_log("=== PROCESS SWITCH ACTION CALLED ===");
error_log("POST data: " . json_encode($_POST));
error_log("Session admin_id: " . ($_SESSION['admin_id'] ?? 'NOT SET'));
error_log("Session employee: " . json_encode($_SESSION['employee'] ?? 'NOT SET'));
error_log("Session view_mode: " . ($_SESSION['view_mode'] ?? 'NOT SET'));

include('../config/db.php');

// Check if admin is logged in (either admin_id or internal employee in admin view mode)
$is_admin = isset($_SESSION['admin_id']) || 
            (isset($_SESSION['employee']['role']) && $_SESSION['employee']['role'] === 'internal' && $_SESSION['view_mode'] === 'admin');

error_log("Is admin check result: " . ($is_admin ? 'TRUE' : 'FALSE'));

if (!$is_admin) {
    error_log("NOT ADMIN - Redirecting to admin_homepage.php");
    header('Location: admin_homepage.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("=== POST REQUEST DETECTED ===");
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $explanation = $_POST['explanation'] ?? null;
    
    error_log("Request ID from POST: " . ($request_id ?? 'NULL'));
    error_log("Action from POST: " . ($action ?? 'NULL'));
    
    // Get admin ID (either from admin_id or employee id)
    $admin_id = $_SESSION['admin_id'] ?? $_SESSION['employee']['id'] ?? null;
    error_log("Admin ID resolved to: " . ($admin_id ?? 'NULL'));

    if (!$request_id || !$action) {
        error_log("ERROR: Missing request_id or action - Redirecting");
        header('Location: schedule_request.php?view=switch&error=' . urlencode('Invalid request.'));
        exit();
    }

    error_log("=== ATTEMPTING TO FETCH REQUEST FROM DATABASE ===");
    
    try {
        // Get the switch request details
        $stmt = $pdo->prepare("
            SELECT employee_id, source_date, target_date, status 
            FROM schedule_switch_requests 
            WHERE id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Database query result: " . json_encode($request));

        if (!$request) {
            error_log("ERROR: Request not found in database");
            header('Location: schedule_request.php?view=switch&error=' . urlencode('Request not found.'));
            exit();
        }

        error_log("Request status from DB: " . $request['status']);
        
        if (strtolower($request['status']) !== 'pending') {
            error_log("ERROR: Request status is not pending, it is: " . $request['status']);
            header('Location: schedule_request.php?view=switch&error=' . urlencode('Request has already been processed.'));
            exit();
        }

        $employee_id = $request['employee_id'];
        $source_date = $request['source_date'];
        $target_date = $request['target_date'];

        if ($action === 'approve') {
            // Log approval attempt
            error_log("=== SWITCH APPROVAL START ===");
            error_log("Request ID: $request_id");
            error_log("Employee ID: $employee_id");
            error_log("Source Date: $source_date");
            error_log("Target Date: $target_date");
            
            // Get schedules for both dates
            $stmt = $pdo->prepare("
                SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
                FROM employee_daily_schedule_cache
                WHERE employee_id = ? AND schedule_date = ?
            ");
            
            // Get source date schedule
            $stmt->execute([$employee_id, $source_date]);
            $sourceSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Source schedule: " . json_encode($sourceSchedule));
            
            // Get target date schedule
            $stmt->execute([$employee_id, $target_date]);
            $targetSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Target schedule: " . json_encode($targetSchedule));

            if (!$sourceSchedule || !$targetSchedule) {
                error_log("ERROR: Schedule not found - Source: " . ($sourceSchedule ? 'found' : 'NOT FOUND') . ", Target: " . ($targetSchedule ? 'found' : 'NOT FOUND'));
                header('Location: schedule_request.php?view=switch&error=' . urlencode('One or both schedules not found in cache.'));
                exit();
            }

            // Begin transaction to swap schedules atomically
            $pdo->beginTransaction();

            try {
                // Update source date with target schedule
                $updateStmt = $pdo->prepare("
                    UPDATE employee_daily_schedule_cache 
                    SET work_schedule_id = ?, 
                        is_rest_day = ?, 
                        schedule_name = ?, 
                        time_in = ?, 
                        time_out = ?
                    WHERE employee_id = ? AND schedule_date = ?
                ");
                $result1 = $updateStmt->execute([
                    $targetSchedule['work_schedule_id'],
                    $targetSchedule['is_rest_day'],
                    $targetSchedule['schedule_name'],
                    $targetSchedule['time_in'],
                    $targetSchedule['time_out'],
                    $employee_id,
                    $source_date
                ]);
                error_log("Updated source date ($source_date): " . ($result1 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $updateStmt->rowCount());

                // Update target date with source schedule
                $result2 = $updateStmt->execute([
                    $sourceSchedule['work_schedule_id'],
                    $sourceSchedule['is_rest_day'],
                    $sourceSchedule['schedule_name'],
                    $sourceSchedule['time_in'],
                    $sourceSchedule['time_out'],
                    $employee_id,
                    $target_date
                ]);
                error_log("Updated target date ($target_date): " . ($result2 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $updateStmt->rowCount());

                // Update request status to approved
                $statusStmt = $pdo->prepare("
                    UPDATE schedule_switch_requests 
                    SET status = 'Approved', 
                        processed_at = NOW(), 
                        processed_by = ?,
                        admin_notes = 'Schedules successfully swapped'
                    WHERE id = ?
                ");
                $result3 = $statusStmt->execute([$admin_id, $request_id]);
                error_log("Updated request status: " . ($result3 ? "SUCCESS" : "FAILED") . " - Rows affected: " . $statusStmt->rowCount());

                $pdo->commit();
                error_log("=== SWITCH APPROVAL COMPLETE - TRANSACTION COMMITTED ===");

                header('Location: schedule_request.php?view=switch&message=' . urlencode('Schedule switch approved and applied successfully!'));
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("ERROR during switch: " . $e->getMessage());
                error_log("=== SWITCH APPROVAL FAILED - TRANSACTION ROLLED BACK ===");
                header('Location: schedule_request.php?view=switch&error=' . urlencode('Error swapping schedules: ' . $e->getMessage()));
                exit();
            }

        } elseif ($action === 'decline') {
            if (!$explanation) {
                header('Location: schedule_request.php?view=switch&error=' . urlencode('Explanation is required for declining.'));
                exit();
            }

            // Update request status to rejected
            $stmt = $pdo->prepare("
                UPDATE schedule_switch_requests 
                SET status = 'Rejected', 
                    processed_at = NOW(), 
                    processed_by = ?,
                    admin_notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $explanation, $request_id]);

            header('Location: schedule_request.php?view=switch&message=' . urlencode('Schedule switch request declined.'));
            exit();

        } else {
            header('Location: schedule_request.php?view=switch&error=' . urlencode('Invalid action.'));
            exit();
        }

    } catch (PDOException $e) {
        header('Location: schedule_request.php?view=switch&error=' . urlencode('Database error: ' . $e->getMessage()));
        exit();
    }

} else {
    header('Location: schedule_request.php?view=switch');
    exit();
}
?>
