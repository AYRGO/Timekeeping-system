<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Include CSRF helper and database
include_once('../config/csrf_helper.php');
include_once('../config/db.php');

// Initialize CSRF protection
init_csrf_protection();

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
$current_time = date('H:i:s');
$today = date('Y-m-d');

try {
    $pdo->beginTransaction();

    if (isset($_POST['time_in'])) {
        // Handle Time In (including reset for incomplete shifts)
        
        // FIRST: If this is an incomplete shift reset, completely clean up ALL records for this employee
        if ($is_incomplete === '1') {
            // Delete ALL incomplete records for this employee
            $cleanupAllIncompleteStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND status = 'incomplete'");
            $cleanupAllIncompleteStmt->execute([$employee_id]);
            
            // Also delete any records from the original incomplete date to ensure clean slate
            $cleanupOriginalDateStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_out = 'INC'");
            $cleanupOriginalDateStmt->execute([$employee_id, $original_log_date]);
            
            error_log("RESET: Deleted all incomplete records for employee $employee_id from date $original_log_date");
        }
        
        // Auto-mark and clean up any OTHER shifts that should be incomplete (older than 12 hours)
        $autoMarkIncompleteStmt = $pdo->prepare("
            UPDATE time_logs 
            SET time_out = 'INC', status = 'incomplete' 
            WHERE employee_id = ? 
            AND time_out IS NULL 
            AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) >= 12
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
            $checkActiveStmt = $pdo->prepare("SELECT id, log_date, time_in FROM time_logs WHERE employee_id = ? AND time_out IS NULL AND status = 'active'");
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
        
        if ($is_overnight === '1') {
            // Handle overnight shift completion
            
            // Find the open overnight shift
            $findStmt = $pdo->prepare("SELECT id, time_in, log_date FROM time_logs 
                WHERE employee_id = ? AND time_out IS NULL AND log_date = ? AND (status IS NULL OR status = 'active')");
            $findStmt->execute([$employee_id, $original_log_date]);
            $openShift = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$openShift) {
                throw new Exception("No open overnight shift found to complete.");
            }
            
            // Update the existing record with time out
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$current_time, $openShift['id']]);
            
            $message = "Overnight shift completed successfully at " . date('h:i A', strtotime($current_time));
            
        } else {
            // Handle regular time out
            
            // Find today's time in record
            $findStmt = $pdo->prepare("SELECT id, time_in FROM time_logs 
                WHERE employee_id = ? AND log_date = ? AND time_out IS NULL AND (status IS NULL OR status = 'active')");
            $findStmt->execute([$employee_id, $today]);
            $todayRecord = $findStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$todayRecord) {
                throw new Exception("No active time in record found for today.");
            }
            
            // Update with time out
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ?, status = 'completed' WHERE id = ?");
            $updateStmt->execute([$current_time, $todayRecord['id']]);
            
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
