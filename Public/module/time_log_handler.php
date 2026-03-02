<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Include CSRF helper and database
include_once('../config/csrf_helper.php');
include_once('../config/db.php');

// Initialize CSRF protection with extended timeout matching the time log page
init_csrf_protection(28800); // 8 hours timeout to match time_log_create.php

// Make sure user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "<script>alert('User not logged in.'); location.href='../employee/login.php';</script>";
    exit;
}

// Verify CSRF token using the correct function
$csrf_validation = validate_csrf_token(true);

if (!$csrf_validation['valid']) {
    error_log("CSRF validation failed for employee $employee_id: " . $csrf_validation['error']);
    echo "<script>alert('Invalid security token. Please refresh the page and try again.'); history.back();</script>";
    exit;
}

// Get form data
$action = $_POST['action'] ?? '';
$original_log_date = $_POST['original_log_date'] ?? date('Y-m-d');
$is_overnight = $_POST['is_overnight'] ?? '0';
$is_incomplete = $_POST['is_incomplete'] ?? '0';
$has_incomplete_previous = $_POST['has_incomplete_previous'] ?? '0';
$today = date('Y-m-d');

// --- FIX: Eliminate 1-second clock delay causing wrong minute recording ---
// The Harley system clock can be ~1 second behind Philippine Standard Time.
// When an employee sees 3:00:00 PM and clicks Time Out, the server may record
// 2:59:59 PM, which shows as 2:59 PM instead of 3:00 PM.
//
// Strategy: Round the server time to the nearest minute.
// e.g., 14:59:31 -> 15:00:00, 14:59:29 -> 14:59:00, 15:00:01 -> 15:00:00
// Also accept client_time from the browser (server-synced) as a cross-check.
$raw_server_time = date('H:i:s');
$server_seconds = (int)date('s');

// Round to nearest minute
if ($server_seconds >= 30) {
    // Round up: add remaining seconds to get to next minute
    $rounded_timestamp = strtotime($raw_server_time) + (60 - $server_seconds);
} else {
    // Round down: subtract current seconds
    $rounded_timestamp = strtotime($raw_server_time) - $server_seconds;
}
$current_time = date('H:i:s', $rounded_timestamp);

// If client sent a time (server-synced from browser), use it as cross-check
$client_time = $_POST['client_time'] ?? '';
if (!empty($client_time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $client_time)) {
    $client_ts = strtotime($client_time);
    $server_ts = strtotime($raw_server_time);
    // Only trust client time if within 5 seconds of server time (prevents tampering)
    if (abs($client_ts - $server_ts) <= 5) {
        // Round client time to nearest minute too
        $client_seconds = (int)date('s', $client_ts);
        if ($client_seconds >= 30) {
            $rounded_client = $client_ts + (60 - $client_seconds);
        } else {
            $rounded_client = $client_ts - $client_seconds;
        }
        $current_time = date('H:i:s', $rounded_client);
    }
}

error_log("TIME LOG: Raw server=$raw_server_time, Client=$client_time, Recorded=$current_time (employee $employee_id)");

try {
    $pdo->beginTransaction();

    if (isset($_POST['time_in'])) {
        // Handle Time In (including reset for incomplete shifts)
        
        // FIRST: Check if user has incomplete previous shift - block time-in if so
        if ($has_incomplete_previous === '1') {
            throw new Exception("You cannot log in for today until you complete your previous shift. Please use the 'Complete Previous Shift' button first.");
        }
        
        // SECOND: If this is an incomplete shift reset, completely clean up ALL records for this employee
        if ($is_incomplete === '1') {
            // Delete ALL incomplete records for this employee
            $cleanupAllIncompleteStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND status = 'incomplete'");
            $cleanupAllIncompleteStmt->execute([$employee_id]);
            
            // Also delete any records from the original incomplete date to ensure clean slate
            $cleanupOriginalDateStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date = ? AND status = 'incomplete'");
            $cleanupOriginalDateStmt->execute([$employee_id, $original_log_date]);
            
            error_log("RESET: Deleted all incomplete records for employee $employee_id from date $original_log_date");
        }
        
        // Auto-mark and clean up any OTHER shifts that should be incomplete (older than 14 hours)
        $autoMarkIncompleteStmt = $pdo->prepare("
            UPDATE time_logs 
            SET time_out = NULL, status = 'incomplete' 
            WHERE employee_id = ? 
            AND time_out IS NULL 
            AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) >= 14
            AND status != 'incomplete'
        ");
        $autoMarkIncompleteStmt->execute([$employee_id]);
        
        if ($autoMarkIncompleteStmt->rowCount() > 0) {
            // Delete these newly marked incomplete ones too
            $deleteNewlyIncomplete = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND status = 'incomplete'");
            $deleteNewlyIncomplete->execute([$employee_id]);
            error_log("Auto-marked and deleted " . $autoMarkIncompleteStmt->rowCount() . " old incomplete shifts for employee $employee_id");
        }
        
        // Check for 8-hour restriction from previous shift completion (only if table exists)
        $tableExistsStmt = $pdo->prepare("SHOW TABLES LIKE 'shift_completions'");
        $tableExistsStmt->execute();
        $tableExists = $tableExistsStmt->rowCount() > 0;
        
        if ($tableExists) {
            try {
                $restrictionStmt = $pdo->prepare("SELECT completed_at FROM shift_completions WHERE employee_id = ? ORDER BY completed_at DESC LIMIT 1");
                $restrictionStmt->execute([$employee_id]);
                $lastCompletion = $restrictionStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lastCompletion) {
                    $completionTime = new DateTime($lastCompletion['completed_at']);
                    $now = new DateTime();
                    $hoursSinceCompletion = ($now->getTimestamp() - $completionTime->getTimestamp()) / 3600;
                    
                    if ($hoursSinceCompletion < 8) {
                        $hoursRemaining = 8 - $hoursSinceCompletion;
                        throw new Exception("You must wait " . number_format($hoursRemaining, 1) . " more hour(s) before starting a new shift.");
                    }
                }
            } catch (PDOException $e) {
                // Error querying table - skip restriction check
                error_log("Error querying shift_completions table: " . $e->getMessage());
            }
        } else {
            // Table doesn't exist - skip restriction check
            error_log("shift_completions table does not exist - skipping 8-hour restriction check");
        }
        
        // Check if there's already an active shift (AFTER cleanup) - but skip this check if we're resetting
        if ($is_incomplete !== '1') {
            // Check for any open shift (not incomplete)
            $checkActiveStmt = $pdo->prepare("SELECT id, log_date, time_in FROM time_logs WHERE employee_id = ? AND time_out IS NULL AND status != 'incomplete'");
            $checkActiveStmt->execute([$employee_id]);
            $activeShift = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activeShift) {
                throw new Exception("You already have an active shift from " . date('M j, Y', strtotime($activeShift['log_date'])) . " at " . date('h:i A', strtotime($activeShift['time_in'])) . ". Please complete your time out first.");
            }
        }
        
        // Check if there's already a completed log for today (but allow reset)
        if ($is_incomplete !== '1') {
            $todayCheckStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_out IS NOT NULL AND status = 'completed'");
            $todayCheckStmt->execute([$employee_id, $today]);
            
            if ($todayCheckStmt->fetch()) {
                throw new Exception("You have already completed a shift for today.");
            }
        }
        
        // NOW: Create a completely NEW record with TODAY's date and current time
        $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out, status) VALUES (?, ?, ?, NULL, 'active')");
        $stmt->execute([$employee_id, $today, $current_time]);
        
        $newRecordId = $pdo->lastInsertId();
        error_log("Created new time log record ID: $newRecordId for employee $employee_id on $today at $current_time");
        
        $message = $is_incomplete === '1' ? 
            "New shift started successfully at " . date('h:i A', strtotime($current_time)) . " (Previous incomplete shift reset)" :
            "Time In logged successfully at " . date('h:i A', strtotime($current_time));
        
    } elseif (isset($_POST['time_out'])) {
        // Handle Time Out
        
        if ($has_incomplete_previous === '1') {
            // Handle completing previous incomplete shift
            
            // Find the open shift from the original date (yesterday)
            // Include all non-incomplete status to handle rest day shifts
            $findStmt = $pdo->prepare("SELECT id, time_in, log_date FROM time_logs 
                WHERE employee_id = ? AND time_out IS NULL AND log_date = ? AND status != 'incomplete'");
            $findStmt->execute([$employee_id, $original_log_date]);
            $openShift = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$openShift) {
                throw new Exception("No open shift found from " . date('F j, Y', strtotime($original_log_date)) . " to complete.");
            }
            
            // Update the existing record with time out and log_out_date (today since completing previous shift today)
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ?, log_out_date = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$current_time, $today, $openShift['id']]);
            
            $formatted_date = date('F j, Y', strtotime($original_log_date));
            $formatted_out_date = date('F j, Y', strtotime($today));
            
            // Show both dates if they're different (cross-midnight completion)
            if ($original_log_date !== $today) {
                $message = "Previous shift from $formatted_date completed successfully on $formatted_out_date at " . date('h:i A', strtotime($current_time));
            } else {
                $message = "Previous shift from $formatted_date completed successfully at " . date('h:i A', strtotime($current_time));
            }
            
        } elseif ($is_overnight === '1') {
            // Handle overnight shift completion
            
            // Find the open overnight shift
            // Include all non-incomplete status to handle rest day shifts
            $findStmt = $pdo->prepare("SELECT id, time_in, log_date FROM time_logs 
                WHERE employee_id = ? AND time_out IS NULL AND log_date = ? AND status != 'incomplete'");
            $findStmt->execute([$employee_id, $original_log_date]);
            $openShift = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$openShift) {
                throw new Exception("No open overnight shift found to complete.");
            }
            
            // Update the existing record with time out and log_out_date (today since completing overnight shift today)
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ?, log_out_date = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$current_time, $today, $openShift['id']]);
            
            $formatted_date = date('F j, Y', strtotime($original_log_date));
            $formatted_out_date = date('F j, Y', strtotime($today));
            
            // Show both dates for overnight shift completion
            if ($original_log_date !== $today) {
                $message = "Overnight shift from $formatted_date completed successfully on $formatted_out_date at " . date('h:i A', strtotime($current_time));
            } else {
                $message = "Overnight shift completed successfully at " . date('h:i A', strtotime($current_time));
            }
            
        } else {
            // Handle regular time out
            
            // Find today's time in record
            // Include all non-incomplete status to handle rest day shifts
            $findStmt = $pdo->prepare("SELECT id, time_in, log_date FROM time_logs 
                WHERE employee_id = ? AND log_date = ? AND time_out IS NULL AND status != 'incomplete'");
            $findStmt->execute([$employee_id, $today]);
            $todayRecord = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$todayRecord) {
                throw new Exception("No active time in record found for today.");
            }
            
            // Determine if this is a cross-midnight shift
            $time_in_hour = (int)date('H', strtotime($todayRecord['time_in']));
            $time_out_hour = (int)date('H', strtotime($current_time));
            $log_out_date = $today; // Default to same day
            
            // Check for cross-midnight scenario (night shift that extends to next day)
            // If time_in is in evening/night (after 6 PM) and time_out is in early morning (before 6 AM)
            if ($time_in_hour >= 18 && $time_out_hour < 6) {
                // This appears to be a cross-midnight shift, but employee is clocking out same day
                // Keep log_out_date as today but this indicates a very long shift or error
                $log_out_date = $today;
            }
            // For normal shifts or shifts that don't cross midnight, use same day
            
            // Update with time out and log_out_date
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ?, log_out_date = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$current_time, $log_out_date, $todayRecord['id']]);
            
            $message = "Time Out logged successfully at " . date('h:i A', strtotime($current_time));
        }
        
    } else {
        throw new Exception("Invalid action specified.");
    }
    
    $pdo->commit();
    
    // Regenerate CSRF token after successful operation
    regenerate_csrf_token();
    
    // Success - For incomplete shift resets, force fresh page load with cache busting
    if ($is_incomplete === '1') {
        echo "<script>
            alert('$message');
            window.location.href = 'time_log_create.php?reset=" . time() . "';
        </script>";
    } else {
        echo "<script>
            alert('$message');
            window.location.href = document.referrer || 'time_log_create.php';
        </script>";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Error - redirect back with error message
    echo "<script>
        alert('Error: " . addslashes($e->getMessage()) . "');
        history.back();
    </script>";
}
?>
