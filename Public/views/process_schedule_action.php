<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check if form is submitted and has necessary fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Allow approve, rejected, and decline actions
    if (!in_array($action, ['approve', 'rejected', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        // Set status based on action
        if ($action === 'approve') {
            $new_status = 'Approved';
        } elseif ($action === 'decline') {
            $new_status = 'Declined';
        } else {
            $new_status = 'Rejected';
        }

        try {
            $pdo->beginTransaction();

            // First, get the complete request data
            $stmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE id = :id");
            $stmt->execute(['id' => $request_id]);
            $request_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request_data) {
                throw new Exception("Request not found.");
            }

            // If action is decline, ensure explanation is provided
            if ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $request_data['explanation'] = $explanation;
            }

            // Update the status
            $request_data['status'] = $new_status;

            // Insert into post_schedule_change_requests table
            $stmt = $pdo->prepare("
                INSERT INTO post_schedule_change_requests 
                (employee_id, reason, status, start_date, end_date, work_schedule_id, 
                 current_work_schedule_id, attachment_scr, explanation, is_rest_day, created_at, notified) 
                VALUES 
                (:employee_id, :reason, :status, :start_date, :end_date, :work_schedule_id, 
                 :current_work_schedule_id, :attachment_scr, :explanation, :is_rest_day, :created_at, :notified)
            ");
            
            $stmt->execute([
                'employee_id' => $request_data['employee_id'],
                'reason' => $request_data['reason'],
                'status' => $request_data['status'],
                'start_date' => $request_data['start_date'],
                'end_date' => $request_data['end_date'],
                'work_schedule_id' => $request_data['work_schedule_id'],
                'current_work_schedule_id' => $request_data['current_work_schedule_id'],
                'attachment_scr' => $request_data['attachment_scr'],
                'explanation' => $request_data['explanation'] ?? null,
                'is_rest_day' => isset($request_data['is_rest_day']) ? intval($request_data['is_rest_day']) : (empty($request_data['work_schedule_id']) ? 1 : 0),
                'created_at' => $request_data['created_at'],
                'notified' => $request_data['notified'] ?? 0
            ]);

            // Delete from schedule_change_requests table
            $stmt = $pdo->prepare("DELETE FROM schedule_change_requests WHERE id = :id");
            $stmt->execute(['id' => $request_id]);

            // AUTO-UPDATE: Sync approved changes to employee_daily_schedules for calendar display
            if ($action === 'approve') {
                $post_request_id = $pdo->lastInsertId();
                
                // Determine rest day status
                $is_rest_day = empty($request_data['work_schedule_id']) ? 1 : 0;
                
                // Loop through date range and update employee_daily_schedules
                $current_date = new DateTime($request_data['start_date']);
                $end_date = new DateTime($request_data['end_date']);
                
                while ($current_date <= $end_date) {
                    $date_str = $current_date->format('Y-m-d');
                    
                    // Check if entry exists in employee_daily_schedules
                    $checkStmt = $pdo->prepare("
                        SELECT id FROM employee_daily_schedules 
                        WHERE employee_id = ? AND schedule_date = ?
                    ");
                    $checkStmt->execute([$request_data['employee_id'], $date_str]);
                    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($exists) {
                        // Update existing entry
                        $updateStmt = $pdo->prepare("
                            UPDATE employee_daily_schedules 
                            SET actual_schedule_id = ?, 
                                is_rest_day = ?,
                                has_override = 1,
                                override_status = 'approved',
                                override_reason = ?,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $updateStmt->execute([
                            $is_rest_day ? null : $request_data['work_schedule_id'],
                            $is_rest_day,
                            $request_data['reason'],
                            $exists['id']
                        ]);
                    } else {
                        // Insert new entry
                        $insertStmt = $pdo->prepare("
                            INSERT INTO employee_daily_schedules 
                            (employee_id, schedule_date, actual_schedule_id, is_rest_day, has_override, override_status, override_reason, created_at)
                            VALUES (?, ?, ?, ?, 1, 'approved', ?, NOW())
                        ");
                        $insertStmt->execute([
                            $request_data['employee_id'],
                            $date_str,
                            $is_rest_day ? null : $request_data['work_schedule_id'],
                            $is_rest_day,
                            $request_data['reason']
                        ]);
                    }
                    
                    // UPDATE CACHE: Update employee_daily_schedule_cache (used by calendar)
                    // Get schedule details if not rest day
                    $scheduleName = null;
                    $timeIn = null;
                    $timeOut = null;
                    
                    if (!$is_rest_day && $request_data['work_schedule_id']) {
                        $schedStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
                        $schedStmt->execute([$request_data['work_schedule_id']]);
                        $schedData = $schedStmt->fetch(PDO::FETCH_ASSOC);
                        if ($schedData) {
                            $scheduleName = $schedData['name'];
                            $timeIn = $schedData['time_in'];
                            $timeOut = $schedData['time_out'];
                        }
                    }
                    
                    // Check if cache entry exists
                    $cacheCheckStmt = $pdo->prepare("
                        SELECT id FROM employee_daily_schedule_cache 
                        WHERE employee_id = ? AND schedule_date = ?
                    ");
                    $cacheCheckStmt->execute([$request_data['employee_id'], $date_str]);
                    $cacheExists = $cacheCheckStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cacheExists) {
                        // Update cache
                        $cacheUpdateStmt = $pdo->prepare("
                            UPDATE employee_daily_schedule_cache 
                            SET work_schedule_id = ?,
                                is_rest_day = ?,
                                schedule_name = ?,
                                time_in = ?,
                                time_out = ?,
                                source = 'approved_request',
                                source_id = ?
                            WHERE id = ?
                        ");
                        $cacheUpdateStmt->execute([
                            $is_rest_day ? null : $request_data['work_schedule_id'],
                            $is_rest_day,
                            $scheduleName,
                            $timeIn,
                            $timeOut,
                            $post_request_id,
                            $cacheExists['id']
                        ]);
                    } else {
                        // Insert into cache
                        $cacheInsertStmt = $pdo->prepare("
                            INSERT INTO employee_daily_schedule_cache 
                            (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                             schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
                            VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'approved_request', ?, NOW())
                        ");
                        $cacheInsertStmt->execute([
                            $request_data['employee_id'],
                            $date_str,
                            $is_rest_day ? null : $request_data['work_schedule_id'],
                            $is_rest_day,
                            $scheduleName,
                            $timeIn,
                            $timeOut,
                            $post_request_id
                        ]);
                    }
                    
                    $current_date->modify('+1 day');
                }
            }

            // CLEANUP: If declined/rejected, remove any orphaned cache entries that reference this request
            if ($action !== 'approve') {
                // Delete cache entries that were created by this request (if it was previously approved)
                $cleanupStmt = $pdo->prepare("
                    DELETE FROM employee_daily_schedule_cache 
                    WHERE employee_id = ? 
                      AND schedule_date BETWEEN ? AND ? 
                      AND source = 'approved_request'
                      AND (source_id = ? OR source_id IS NULL OR source_id = 0)
                ");
                $cleanupStmt->execute([
                    $request_data['employee_id'],
                    $request_data['start_date'],
                    $request_data['end_date'],
                    $post_request_id
                ]);
            }

            $pdo->commit();

            // Set action_text for message
            if ($action === 'approve') {
                $action_text = 'approved';
            } elseif ($action === 'decline') {
                $action_text = 'declined';
            } else {
                $action_text = 'rejected';
            }
            header("Location: schedule_request.php?message=Request%20ID%20%23$request_id%20has%20been%20$action_text");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}

// If we reach here, there was an error
header("Location: schedule_request.php?error=" . urlencode($message));
exit;
?>
