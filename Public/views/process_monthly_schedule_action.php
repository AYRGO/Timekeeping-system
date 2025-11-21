<?php
// process_monthly_schedule_action.php
// Handle approve/decline actions for monthly schedule requests

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Invalid request method"));
    exit;
}

$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$request_id || !$action) {
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Missing required parameters"));
    exit;
}

try {
    if ($action === 'approve') {
        // Get the approved request FIRST to determine dates
        $requestStmt = $pdo->prepare("SELECT * FROM month_weekly_schedule WHERE id = ?");
        $requestStmt->execute([$request_id]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Request not found"));
            exit;
        }
        
        $employee_id = $request['employee_id'];
        $year = $request['year'];
        $month = $request['month'];
        
        // Calculate date range
        $today = date('Y-m-d');
        $todayTimestamp = strtotime($today);
        $firstDayOfMonth = date('Y-m-d', strtotime("$year-$month-01"));
        $lastDayOfMonth = date('Y-m-t', strtotime("$year-$month-01"));
        
        // Determine start date: For FUTURE months, start from day 1. For CURRENT month, start from today.
        if (strtotime($firstDayOfMonth) > $todayTimestamp) {
            // Future month (e.g., December when we're in November)
            $startDate = $firstDayOfMonth;
            error_log("MONTHLY APPROVAL: Future month detected. Start date: $startDate");
        } elseif (strtotime($lastDayOfMonth) >= $todayTimestamp) {
            // Current month - start from today
            $startDate = $today;
            error_log("MONTHLY APPROVAL: Current month detected. Start date: $startDate");
        } else {
            // Past month - cannot approve
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Cannot approve schedule for past months"));
            exit;
        }
        
        error_log("MONTHLY APPROVAL: Processing request ID $request_id for employee $employee_id, month $year-$month");
        error_log("MONTHLY APPROVAL: Date range: $startDate to $lastDayOfMonth");
        
        // STEP 1: UPDATE employee_default_schedules (Weekly Pattern)
        // End the current weekly schedule by setting effective_until
        $endPreviousStmt = $pdo->prepare("
            UPDATE employee_default_schedules 
            SET effective_until = ?
            WHERE employee_id = ? 
              AND (effective_until IS NULL OR effective_until >= ?)
        ");
        $previousEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $endPreviousStmt->execute([$previousEndDate, $employee_id, $startDate]);
        error_log("MONTHLY APPROVAL: Ended previous schedules with effective_until = $previousEndDate");
        
        // Insert new weekly schedule pattern (7 days)
        $weeklySchedule = [
            0 => ['schedule_id' => $request['sunday_schedule_id'], 'is_rest_day' => $request['sunday_is_rest_day']],
            1 => ['schedule_id' => $request['monday_schedule_id'], 'is_rest_day' => $request['monday_is_rest_day']],
            2 => ['schedule_id' => $request['tuesday_schedule_id'], 'is_rest_day' => $request['tuesday_is_rest_day']],
            3 => ['schedule_id' => $request['wednesday_schedule_id'], 'is_rest_day' => $request['wednesday_is_rest_day']],
            4 => ['schedule_id' => $request['thursday_schedule_id'], 'is_rest_day' => $request['thursday_is_rest_day']],
            5 => ['schedule_id' => $request['friday_schedule_id'], 'is_rest_day' => $request['friday_is_rest_day']],
            6 => ['schedule_id' => $request['saturday_schedule_id'], 'is_rest_day' => $request['saturday_is_rest_day']]
        ];
        
        $insertedCount = 0;
        foreach ($weeklySchedule as $dayOfWeek => $daySchedule) {
            $insertStmt = $pdo->prepare("
                INSERT INTO employee_default_schedules 
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->execute([
                $employee_id,
                $dayOfWeek,
                $daySchedule['schedule_id'],
                $daySchedule['is_rest_day'],
                $startDate,
                $lastDayOfMonth  // Set to end of month so it only applies for this month
            ]);
            $insertedCount++;
            error_log("MONTHLY APPROVAL: Inserted schedule for day $dayOfWeek (schedule_id: {$daySchedule['schedule_id']}, is_rest: {$daySchedule['is_rest_day']})");
        }
        error_log("MONTHLY APPROVAL: Inserted $insertedCount weekly schedule rows");
        
        // STEP 2: REBUILD employee_daily_schedule_cache for the affected date range
        // Clear existing cache entries for this date range
        $deleteStmt = $pdo->prepare("
            DELETE FROM employee_daily_schedule_cache 
            WHERE employee_id = ? 
              AND schedule_date BETWEEN ? AND ?
        ");
        $deleteStmt->execute([$employee_id, $startDate, $lastDayOfMonth]);
        $deletedRows = $deleteStmt->rowCount();
        error_log("MONTHLY APPROVAL: Deleted $deletedRows cache rows");
        
        // Rebuild cache with new schedule
        $currentDate = strtotime($startDate);
        $endDateTimestamp = strtotime($lastDayOfMonth);
        $cacheInsertCount = 0;
        
        while ($currentDate <= $endDateTimestamp) {
            $dateStr = date('Y-m-d', $currentDate);
            $dayOfWeek = (int)date('w', $currentDate);
            $daySchedule = $weeklySchedule[$dayOfWeek];
            
            if ($daySchedule['is_rest_day']) {
                // Insert rest day
                $pdo->prepare("
                    INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                     schedule_name, time_in, time_out, holiday_name, source, created_at, updated_at)
                    VALUES (?, ?, NULL, 1, 0, NULL, NULL, NULL, NULL, 'approved_monthly_request', NOW(), NOW())
                ")->execute([$employee_id, $dateStr]);
                $cacheInsertCount++;
            } else {
                // Insert work schedule
                $schedStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
                $schedStmt->execute([$daySchedule['schedule_id']]);
                $schedInfo = $schedStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($schedInfo) {
                    $pdo->prepare("
                        INSERT INTO employee_daily_schedule_cache 
                        (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                         schedule_name, time_in, time_out, holiday_name, source, created_at, updated_at)
                        VALUES (?, ?, ?, 0, 0, ?, ?, ?, NULL, 'approved_monthly_request', NOW(), NOW())
                    ")->execute([
                        $employee_id, $dateStr, $daySchedule['schedule_id'],
                        $schedInfo['name'], $schedInfo['time_in'], $schedInfo['time_out']
                    ]);
                    $cacheInsertCount++;
                }
            }
            
            $currentDate = strtotime('+1 day', $currentDate);
        }
        error_log("MONTHLY APPROVAL: Inserted $cacheInsertCount cache rows");
        
        // Update request status to approved and mark as processed
        $updateStmt = $pdo->prepare("
            UPDATE month_weekly_schedule 
            SET status = 'approved', 
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        $admin_id = $_SESSION['user_id'] ?? $_SESSION['employee']['id'] ?? null;
        $updateStmt->execute([$admin_id, $request_id]);
        error_log("MONTHLY APPROVAL: Request marked as approved and processed");
        
        header("Location: schedule_request.php?view=monthly&message=" . urlencode("Monthly schedule approved! Weekly pattern updated for $year-$month."));
        
    } else if ($action === 'decline') {
        $explanation = $_POST['explanation'] ?? '';
        
        if (empty($explanation)) {
            header("Location: schedule_request.php?view=monthly&error=" . urlencode("Explanation is required for declining"));
            exit;
        }
        
        // Update status to rejected
        $stmt = $pdo->prepare("
            UPDATE month_weekly_schedule 
            SET status = 'rejected',
                admin_notes = ?,
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");
        
        $admin_id = $_SESSION['user_id'] ?? $_SESSION['employee']['id'] ?? null;
        $stmt->execute([$explanation, $admin_id, $request_id]);
        
        header("Location: schedule_request.php?view=monthly&message=" . urlencode("Monthly schedule request declined"));
        
    } else {
        header("Location: schedule_request.php?view=monthly&error=" . urlencode("Invalid action"));
    }
    
} catch (PDOException $e) {
    error_log("Monthly schedule action error: " . $e->getMessage());
    header("Location: schedule_request.php?view=monthly&error=" . urlencode("Database error: " . $e->getMessage()));
}

exit;
