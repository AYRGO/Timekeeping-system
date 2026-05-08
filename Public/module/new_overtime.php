<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

// Helper: fix night-shift time_out that is stored on the same date as time_in
// For night shifts (e.g. 7PM-3AM), time_out may be stored as the SAME calendar date
// as time_in (e.g. 2026-02-08 03:01:00 instead of 2026-02-09 03:01:00).
// This causes a negative diff, making duration appear as ~15hrs instead of ~8hrs.
function fixNightShiftTimeOut(DateTime $timeIn, DateTime $timeOut): DateTime {
    if ($timeOut < $timeIn) {
        // time_out is earlier than time_in on the same date — advance it by 1 day
        $timeOut->modify('+1 day');
    }
    return $timeOut;
}

// Function to format duration in hours and minutes
function formatDurationPHP($hours) {
    $totalHours = floatval($hours ?: 0);
    $wholeHours = floor($totalHours);
    $minutes = round(($totalHours - $wholeHours) * 60);
    
    if ($wholeHours == 0 && $minutes == 0) {
        return '0.00 hrs (0h 0m)';
    }
    
    $decimalFormat = number_format($totalHours, 2) . ' hrs';
    $timeFormat = $wholeHours . 'h ' . $minutes . 'm';
    
    return $decimalFormat . ' <span class="text-gray-500">(' . $timeFormat . ')</span>';
}

if (!isset($_SESSION['regenerated']) && !headers_sent()) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

require_once 'time_logs_helper.php';

// Include schedule tracker to get current schedule information
include_once 'stats/schedule_tracker.php';

// Function to move approved/declined overtime requests to archive table
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
            error_log("Successfully moved {$movedCount} completed OT requests to archive table");
            return $movedCount;
        }
        
        $pdo->commit();
        return 0;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error moving completed OT requests: " . $e->getMessage());
        return false;
    }
}

// Auto-move completed requests on page load
moveCompletedOTRequests($pdo);

// Updated overtime eligibility function based on active schedule

// Overtime eligibility based on simple rule: 30+ minutes past scheduled end time
function isOvertimeEligibleBySchedule($time_in, $time_out, $log_date, $employee_id, $pdo, $ot_type = null) {
  if (empty($time_in) || empty($time_out)) {
    return false;
  }
  
  // Special handling for Restday OT - minimum 30 minutes (0.5 hours)
  if ($ot_type === 'Restday OT') {
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    $interval = $timeIn->diff($timeOut);
    $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    $totalHours = $totalMinutes / 60;
    $workHours = $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours; // Only minus 1hr lunch if 8+ hours
    
    return $workHours >= 0.5; // 30+ minutes for Restday OT
  }
  
  $scheduleInfo = getScheduleForDate($employee_id, $log_date, $pdo);
  $schedule_out = $scheduleInfo['time_out'];
  
  // Convert to DateTime objects
  $schedule_out_dt = new DateTime($log_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  $actual_out_dt = new DateTime($time_out);
  
  // If actual times are on different dates, adjust schedule time accordingly
  $actual_date = $actual_out_dt->format('Y-m-d');
  if ($actual_date !== $log_date) {
    $schedule_out_dt = new DateTime($actual_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  }

  // Check if worked past scheduled end time
  if ($actual_out_dt <= $schedule_out_dt) {
    return false; // No overtime if didn't work past scheduled end
  }

  // Calculate minutes worked past scheduled end time
  $past_end_interval = $schedule_out_dt->diff($actual_out_dt);
  $minutes_past_end = ($past_end_interval->days * 24 * 60) + ($past_end_interval->h * 60) + $past_end_interval->i;

  // Must work 30+ minutes past scheduled end time to qualify for OT
  return $minutes_past_end >= 30;
}

// Calculate overtime hours: time worked past scheduled end time (NO lunch deduction for OT hours)
function calculateOvertimeHoursBySchedule($time_in, $time_out, $log_date, $employee_id, $pdo, $ot_type = null) {
  if (empty($time_in) || empty($time_out)) {
    return 0;
  }
  
  //  Special handling for Restday OT - return total work hours (apply lunch deduction if 8+ hours)
  if ($ot_type === 'Restday OT') {
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    $timeOut = fixNightShiftTimeOut($timeIn, $timeOut); // fix same-date night shifts
    $interval = $timeIn->diff($timeOut);
    $totalMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    $totalHours = $totalMinutes / 60;
    if ($totalHours >= 8) {
      $totalHours = max(0, $totalHours - 1);
    }
    
    return round($totalHours, 2);
  }
  
  $scheduleInfo = getScheduleForDate($employee_id, $log_date, $pdo);
  $schedule_out = $scheduleInfo['time_out'];
  
  // Convert to DateTime objects
  $schedule_out_dt = new DateTime($log_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  $actual_out_dt = new DateTime($time_out);
  
  // If actual times are on different dates, adjust schedule time accordingly
  $actual_date = $actual_out_dt->format('Y-m-d');
  if ($actual_date !== $log_date) {
    $schedule_out_dt = new DateTime($actual_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  }

  // Check if worked past scheduled end time
  if ($actual_out_dt <= $schedule_out_dt) {
    return 0; // No overtime if didn't work past scheduled end
  }

  // Calculate minutes worked past scheduled end time
  // NOTE: This is purely OT time (past scheduled end), so NO lunch deduction should apply
  $past_end_interval = $schedule_out_dt->diff($actual_out_dt);
  $ot_minutes = ($past_end_interval->days * 24 * 60) + ($past_end_interval->h * 60) + $past_end_interval->i;

  // Convert to hours and round to 2 decimals (NO LUNCH DEDUCTION)
  return round($ot_minutes / 60, 2);
}

// Function to get start OT and end OT times with exact overtime details
function getOvertimeTimesAndHours($time_in, $time_out, $log_date, $employee_id, $pdo, $ot_type = null) {
  if (empty($time_in) || empty($time_out)) {
    return [
      'start_ot' => '',
      'end_ot' => '',
      'max_ot_hours' => 0,
      'exact_ot_minutes' => 0,
      'eligible' => false
    ];
  }
  
  // Auto-detect rest day from schedule if ot_type not provided
  if ($ot_type === null) {
    $scheduleCheck = getScheduleForDate($employee_id, $log_date, $pdo);
    if ($scheduleCheck['is_rest_day'] == 1) {
      $ot_type = 'Restday OT';
    }
  }
  
  // For Restday OT, start OT is time in and end OT is time out
  if ($ot_type === 'Restday OT') {
    $actual_in_dt = new DateTime($time_in);
    $actual_out_dt = new DateTime($time_out);
    $actual_out_dt = fixNightShiftTimeOut($actual_in_dt, $actual_out_dt); // fix same-date night shifts
    
    // Calculate total minutes using timestamp difference (handles midnight crossing)
    $diffSeconds = $actual_out_dt->getTimestamp() - $actual_in_dt->getTimestamp();
    $totalMinutes = $diffSeconds / 60; // positive after fixNightShiftTimeOut
    $totalHours = $totalMinutes / 60;
    $netHours = $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours;
    
    return [
      'start_ot' => $actual_in_dt->format('h:i A'),
      'end_ot' => $actual_out_dt->format('h:i A'),
      'max_ot_hours' => round($netHours, 2),
      'exact_ot_minutes' => round($netHours * 60),
      'eligible' => $totalHours >= 8,
      'is_restday' => true
    ];
  }
  
  // For regular OT, get schedule information
  $scheduleInfo = getScheduleForDate($employee_id, $log_date, $pdo);
  $schedule_out = $scheduleInfo['time_out'];
  
  // Convert to DateTime objects
  $schedule_out_dt = new DateTime($log_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  $actual_out_dt   = new DateTime($time_out);

  // If actual time-out is on a different date, move schedule_out to match
  $actual_date = $actual_out_dt->format('Y-m-d');
  if ($actual_date !== $log_date) {
    $schedule_out_dt = new DateTime($actual_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  }

  // Calculate overtime minutes
  $ot_minutes = 0;
  if ($actual_out_dt > $schedule_out_dt) {
    $past_end_interval = $schedule_out_dt->diff($actual_out_dt);
    $ot_minutes = ($past_end_interval->days * 24 * 60) + ($past_end_interval->h * 60) + $past_end_interval->i;
  }
  
  $ot_hours = round($ot_minutes / 60, 2);
  $eligible = $ot_minutes >= 30; // Must work 30+ minutes past scheduled end
  
  return [
    'start_ot' => $schedule_out_dt->format('h:i A'), // End of scheduled time = Start of OT
    'end_ot' => $actual_out_dt->format('h:i A'),     // Actual time out = End of OT
    'max_ot_hours' => $ot_hours,
    'exact_ot_minutes' => $ot_minutes,
    'eligible' => $eligible,
    'is_restday' => false
  ];
}

// Helper function to get detailed OT calculation breakdown - SIMPLIFIED VERSION
function getOvertimeCalculationDetails($time_in, $time_out, $log_date, $employee_id, $pdo, $ot_type = null) {
  if (empty($time_in) || empty($time_out)) {
    return [
      'eligible' => false,
      'reason' => 'Missing time in or time out data',
      'details' => []
    ];
  }
  
  // Auto-detect rest day from active schedule if ot_type not provided
  if ($ot_type === null) {
    $scheduleCheck = getScheduleForDate($employee_id, $log_date, $pdo);
    if ($scheduleCheck['is_rest_day'] == 1) {
      $ot_type = 'Restday OT';
    }
  }
  
  // Special handling for Restday OT
  if ($ot_type === 'Restday OT') {
    $actual_in_dt = new DateTime($time_in);
    $actual_out_dt = new DateTime($time_out);
    $actual_out_dt = fixNightShiftTimeOut($actual_in_dt, $actual_out_dt); // fix same-date night shifts
    
    // Calculate actual hours worked (apply lunch deduction if 8+ hours)
    $actual_interval = $actual_in_dt->diff($actual_out_dt);
    $actual_minutes = ($actual_interval->days * 24 * 60) + ($actual_interval->h * 60) + $actual_interval->i;
    $actual_hours = round($actual_minutes / 60, 2);
    $net_hours = $actual_hours >= 8 ? max(0, $actual_hours - 1) : $actual_hours;
    $minimum_hours = 0.5; // 30 minutes
    
    $eligible = $actual_hours >= $minimum_hours; // 30+ minutes for Restday OT
    
    $details = [
      'schedule' => [
        'in' => 'N/A (Rest Day)',
        'out' => 'N/A (Rest Day)',
        'hours' => 0,
        'hours_minus_lunch' => 0
      ],
      'actual' => [
        'in' => $actual_in_dt->format('H:i:s'),
        'out' => $actual_out_dt->format('H:i:s'),
        'hours' => $actual_hours,
        'hours_minus_lunch' => $net_hours
      ],
      'overtime_minutes' => $net_hours * 60,
      'overtime_hours' => $net_hours,
      'net_overtime_minutes' => $net_hours * 60,
      'net_overtime_hours' => $net_hours,
      'minimum_required' => $minimum_hours, // hours for Restday OT
      'eligible' => $eligible,
      'lunch_deducted' => $actual_hours >= 8,
      'is_restday_ot' => true
    ];
    
    $reason = $eligible 
      ? "Eligible for {$net_hours} hours of Restday OT (worked {$actual_hours}h total)"
      : "Need at least 0.5 hours (30 minutes) of work for Restday OT (worked only {$actual_hours}h)";    
    
    return [
      'eligible' => $eligible,
      'reason' => $reason,
      'details' => $details
    ];
  }
  
  $scheduleInfo = getScheduleForDate($employee_id, $log_date, $pdo);
  $schedule_in = $scheduleInfo['time_in'];
  $schedule_out = $scheduleInfo['time_out'];
  
  // Convert to DateTime objects
  $schedule_in_dt  = new DateTime($log_date . ' ' . date('H:i:s', strtotime($schedule_in)));
  $schedule_out_dt = new DateTime($log_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  $actual_in_dt    = new DateTime($time_in);
  $actual_out_dt   = new DateTime($time_out);

  // If actual time-out is on a different date, move schedule times to match
  $actual_date = $actual_out_dt->format('Y-m-d');
  if ($actual_date !== $log_date) {
    $schedule_in_dt  = new DateTime($actual_date . ' ' . date('H:i:s', strtotime($schedule_in)));
    $schedule_out_dt = new DateTime($actual_date . ' ' . date('H:i:s', strtotime($schedule_out)));
  }

  // Calculate scheduled hours (minus 1hr lunch)
  $scheduled_interval = $schedule_in_dt->diff($schedule_out_dt);
  $scheduled_minutes = ($scheduled_interval->days * 24 * 60) + ($scheduled_interval->h * 60) + $scheduled_interval->i;
  $scheduled_hours_with_lunch = round($scheduled_minutes / 60, 2);
  $scheduled_hours = max(0, $scheduled_hours_with_lunch - 1); // Minus 1hr lunch
  
  // Calculate actual hours worked (minus 1hr lunch)
  $actual_interval = $actual_in_dt->diff($actual_out_dt);
  $actual_minutes = ($actual_interval->days * 24 * 60) + ($actual_interval->h * 60) + $actual_interval->i;
  $actual_hours_with_lunch = round($actual_minutes / 60, 2);
  $actual_hours = max(0, $actual_hours_with_lunch - 1); // Minus 1hr lunch
  
  // Calculate overtime: time worked past scheduled end time
  // NOTE: OT hours should NOT have lunch deducted because lunch is only for the regular shift
  $ot_minutes = 0;
  if ($actual_out_dt > $schedule_out_dt) {
    $past_end_interval = $schedule_out_dt->diff($actual_out_dt);
    $ot_minutes = ($past_end_interval->days * 24 * 60) + ($past_end_interval->h * 60) + $past_end_interval->i;
  }
  
  $ot_hours = round($ot_minutes / 60, 2);
  $eligible = $ot_minutes >= 30; // Must work 30+ minutes past scheduled end
  
  $details = [
    'schedule' => [
      'in' => $schedule_in,
      'out' => $schedule_out,
      'hours' => $scheduled_hours_with_lunch,
      'hours_minus_lunch' => $scheduled_hours
    ],
    'actual' => [
      'in' => $actual_in_dt->format('H:i:s'),
      'out' => $actual_out_dt->format('H:i:s'),
      'hours' => $actual_hours_with_lunch,
      'hours_minus_lunch' => $net_hours
    ],
    'overtime_minutes' => $ot_minutes,
    'overtime_hours' => $ot_hours,
    'net_overtime_minutes' => $ot_minutes,
    'net_overtime_hours' => $ot_hours,
    'minimum_required' => 30, // minutes past scheduled end
    'eligible' => $eligible,
    'lunch_deducted' => true
  ];
  
  // Determine reason
  $reason = '';
  if (!$eligible) {
    if ($ot_minutes <= 0) {
      $reason = "Did not work past scheduled end time ({$schedule_out})";
    } elseif ($ot_minutes < 30) {
      $reason = "Worked only {$ot_minutes} minutes past scheduled end (need 30+ minutes)";
    }
  } else {
    $reason = "Eligible for {$ot_hours} hours of overtime (worked {$ot_minutes} min past scheduled end)";
  }
  
  return [
    'eligible' => $eligible,
    'reason' => $reason,
    'details' => $details
  ];
}

// Function to get schedule for a specific date (historical accuracy)
function getScheduleForDate($employee_id, $date, $pdo) {
    // PRIORITY 1: Check employee_daily_schedule_cache for pre-computed schedule
    $stmt = $pdo->prepare("
        SELECT 
            work_schedule_id,
            schedule_name,
            time_in,
            time_out,
            is_rest_day,
            is_holiday,
            source
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $cache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cache && $cache['time_in'] && $cache['time_out']) {
        // Found in cache with valid times
        $schedule_in = $cache['time_in'];
        $schedule_out = $cache['time_out'];
        $schedule_id = $cache['work_schedule_id'] ?? null;
    } else {
        // FALLBACK: Check employee_default_schedules + work_schedules
        $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday
        $stmt = $pdo->prepare("
            SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
            FROM employee_default_schedules edd
            LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
            WHERE edd.employee_id = ? 
              AND edd.day_of_week = ? 
              AND edd.effective_from <= ? 
              AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $dayOfWeek, $date, $date]);
        $weekly = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($weekly && $weekly['time_in'] && $weekly['time_out']) {
            $schedule_in = $weekly['time_in'];
            $schedule_out = $weekly['time_out'];
            $schedule_id = $weekly['work_schedule_id'];
        } else {
            // Final fallback: Use employee's official schedule
            $stmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            $schedule_id = $employee['official_sched'] ?? 4;
            
            // Get times from work_schedules table
            $stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
            $stmt->execute([$schedule_id]);
            $ws = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $schedule_in = $ws['time_in'] ?? '07:00:00';
            $schedule_out = $ws['time_out'] ?? '16:00:00';
        }
    }
    
    // Format times for display
    $formatted_in = date('h:i A', strtotime($schedule_in));
    $formatted_out = date('h:i A', strtotime($schedule_out));
    
    // Determine status for display
    $today = date('Y-m-d');
    $isToday = ($date === $today);
    $isPast = ($date < $today);
    
    $status_text = '';
    $status_color = 'text-blue-600';
    
    if ($cache) {
        // Using cached schedule data
        if ($cache['source'] === 'approved_request') {
            $status_text = "Changed Schedule (ID: {$schedule_id})";
            $status_color = 'text-green-600';
        } elseif ($cache['source'] === 'admin_override') {
            $status_text = "Admin Override (ID: {$schedule_id})";
            $status_color = 'text-purple-600';
        } else {
            $status_text = "";
            $status_color = 'text-blue-600';
        }
        $was_changed = in_array($cache['source'], ['approved_request', 'admin_override']);
    } else {
        // Using fallback data
        $status_text = "";
        $status_color = 'text-blue-600';
        $was_changed = false;
    }
    
    return [
        'time_in' => $formatted_in,
        'time_out' => $formatted_out,
        'schedule_id' => $schedule_id,
        'status_text' => $status_text,
        'status_color' => $status_color,
        'was_changed' => $was_changed,
        'is_default' => !$was_changed,
        'is_rest_day' => $cache['is_rest_day'] ?? ($weekly['is_rest_day'] ?? 0)
    ];
}

// Get employee's time logs with pagination
$employee_id = $_SESSION['employee']['id'] ?? 1; // Use the correct session key

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? max(5, min(50, intval($_GET['per_page']))) : 10; // Allow 5-50 records per page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM time_logs tl 
              WHERE tl.employee_id = ? AND tl.time_out IS NOT NULL";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->bindValue(1, $employee_id, PDO::PARAM_INT);
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated time logs
$sql = "SELECT tl.*, DATE_FORMAT(tl.time_in, '%Y-%m-%d %H:%i') as formatted_time_in, 
               DATE_FORMAT(tl.time_out, '%Y-%m-%d %H:%i') as formatted_time_out
        FROM time_logs tl 
        WHERE tl.employee_id = ? AND tl.time_out IS NOT NULL 
        ORDER BY tl.log_date DESC 
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $employee_id, PDO::PARAM_INT);
$stmt->bindValue(2, $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overtime request history (from both active and archived tables)
$history_sql = "
    (SELECT ot.*, tl.log_date, tl.time_in, tl.time_out,
           DATE_FORMAT(ot.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at,
           DATE_FORMAT(tl.log_date, '%M %d, %Y') as formatted_log_date,
           DATE_FORMAT(tl.time_in, '%h:%i %p') as formatted_time_in,
           DATE_FORMAT(tl.time_out, '%h:%i %p') as formatted_time_out,
           ot.status as request_status, 'active' as source_table
    FROM post_ot_requests ot 
    LEFT JOIN time_logs tl ON ot.time_log_id = tl.id 
    WHERE ot.employee_id = ?)
    UNION ALL
    (SELECT ot.*, tl.log_date, tl.time_in, tl.time_out,
           DATE_FORMAT(ot.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at,
           DATE_FORMAT(tl.log_date, '%M %d, %Y') as formatted_log_date,
           DATE_FORMAT(tl.time_in, '%h:%i %p') as formatted_time_in,
           DATE_FORMAT(tl.time_out, '%h:%i %p') as formatted_time_out,
           ot.status as request_status, 'archived' as source_table
    FROM post2_overtime_requests ot 
    LEFT JOIN time_logs tl ON ot.time_log_id = tl.id 
    WHERE ot.employee_id = ?)
    ORDER BY created_at DESC LIMIT 20";
$history_stmt = $pdo->prepare($history_sql);
$history_stmt->bindValue(1, $employee_id, PDO::PARAM_INT);
$history_stmt->bindValue(2, $employee_id, PDO::PARAM_INT);
$history_stmt->execute();
$overtime_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get default schedule from session (set by schedule_tracker.php)
$default_sched = $_SESSION['current_schedule'] ?? [
    'time_in' => '07:00 AM',
    'time_out' => '04:00 PM'
];
$default_time_in = $default_sched['time_in'];
$default_time_out = $default_sched['time_out'];

?>
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse-glow {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.animate-slide-in-right {
    animation: slideInRight 0.4s ease-out;
}

.hover-scale {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-scale:hover {
    transform: scale(1.02) translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.tab-active {
    background: white !important;
    color: #2563eb !important;
    border-color: #2563eb !important;
}

.tab-inactive {
    background: transparent !important;
    color: #6b7280 !important;
    border-color: transparent !important;
    transition: all 0.2s ease;
}

.tab-inactive:hover {
    color: #111827 !important;
    border-color: #d1d5db !important;
}

.status-approved {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.status-declined {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde68a;
}

/* Enhanced table styling */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

/* Smooth backdrop blur for modern browsers */
@supports (backdrop-filter: blur(10px)) {
    .backdrop-blur-sm {
        backdrop-filter: blur(10px);
    }
}

/* Enhanced responsive table */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table-responsive th,
    .table-responsive td {
        padding: 0.75rem 0.5rem;
    }
}

/* Custom scrollbar for better UX */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(90deg, #059669, #047857);
}

/* Clean time input styling */
#ot_time_input {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.05em;
}

#ot_time_input:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.15);
}

#ot_time_input::placeholder {
    color: #9ca3af;
    font-weight: 500;
}

/* Button-based Time Picker Styling */
#hours-up, #hours-down, #minutes-up, #minutes-down {
    transition: all 0.2s ease;
    font-size: 10px;
}

#hours-up:hover:not(:disabled), #hours-down:hover:not(:disabled), 
#minutes-up:hover:not(:disabled), #minutes-down:hover:not(:disabled) {
    background-color: #059669 !important;
    color: white !important;
    transform: scale(1.05);
}

#hours-up:disabled, #hours-down:disabled, 
#minutes-up:disabled, #minutes-down:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

#selected-hours, #selected-minutes {
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.02em;
}

/* Time picker container animations */
.time-picker-container:hover {
    transform: translateY(-1px);
}

/* Button active states */
#hours-up:active:not(:disabled), #hours-down:active:not(:disabled),
#minutes-up:active:not(:disabled), #minutes-down:active:not(:disabled) {
    transform: scale(0.95);
    background-color: #047857 !important;
}

/* Prevent any overflow issues */
.modal-content {
    max-height: 90vh;
    overflow-y: auto;
}

/* Ensure containers are properly contained */
#overtimeModal .relative {
    overflow: visible;
}

/* Smooth transitions for time display */
#selected-hours, #selected-minutes {
    transition: all 0.2s ease;
}

/* Focus states for accessibility */
#hours-up:focus, #hours-down:focus, #minutes-up:focus, #minutes-down:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.5);
}

/* Enhanced form input animations */
input[type="number"]:focus,
select:focus,
textarea:focus {
    transform: translateY(-1px);
    animation: subtle-glow 2s ease-in-out infinite alternate;
}

@keyframes subtle-glow {
    0% { box-shadow: 0 4px 20px rgba(16, 185, 129, 0.15); }
    100% { box-shadow: 0 6px 25px rgba(16, 185, 129, 0.25); }
}

/* Enhanced gradient backgrounds */
.gradient-bg {
    background: linear-gradient(135deg, 
        rgba(16, 185, 129, 0.05) 0%, 
        rgba(5, 150, 105, 0.08) 50%, 
        rgba(6, 95, 70, 0.05) 100%);
}

/* Modern glassmorphism effect */
.glass-effect {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Micro-interactions for better feedback */
.micro-bounce:active {
    animation: micro-bounce 0.2s ease-in-out;
}

@keyframes micro-bounce {
    0% { transform: scale(1); }
    50% { transform: scale(0.98); }
    100% { transform: scale(1); }
}

/* Enhanced button hover effects */
button:hover {
    filter: brightness(1.05);
}

/* Enhanced modal animations */
.modal-enter {
    animation: modal-slide-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modal-slide-in {
    0% {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>

<div id="overtimeView" class="hidden animate-fade-in-up overtime-management-container">
  <div class="w-full">
    
    <!-- Modern Header Section - Monday.com/Sprout Style -->
    <div class="bg-white border-b border-gray-200">
      <div class="px-8 py-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <!-- Left: Icon + Title -->
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="fas fa-clock text-white text-lg"></i>
            </div>
            <div>
              <h1 class="text-xl font-semibold text-gray-900">Overtime Management</h1>
              <p class="text-sm text-gray-500">Submit and track your overtime requests</p>
            </div>
          </div>
          
          <!-- Right: Stats Overview -->
          <div class="flex items-center gap-8">
            <div class="text-center">
              <div class="text-2xl font-semibold text-gray-900"><?= count($time_logs) ?></div>
              <div class="text-xs text-gray-500 whitespace-nowrap">Recent Logs</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-semibold text-green-600">
                <?= count(array_filter($time_logs, function($log) use ($employee_id, $pdo) { 
                  if (empty($log['time_in']) || empty($log['time_out'])) return false;
                  $isEligible = isOvertimeEligibleBySchedule($log['time_in'], $log['time_out'], $log['log_date'], $employee_id, $pdo);
                  $otHours = calculateOvertimeHoursBySchedule($log['time_in'], $log['time_out'], $log['log_date'], $employee_id, $pdo);
                  return $isEligible && $otHours >= 0.5;
                })) ?>
              </div>
              <div class="text-xs text-gray-500 whitespace-nowrap">OT Eligible</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-semibold text-gray-900"><?= count($overtime_history) ?></div>
              <div class="text-xs text-gray-500 whitespace-nowrap">Total Requests</div>
            </div>
            <div class="text-center">
              <div class="text-sm font-medium text-gray-900 whitespace-nowrap"><?= htmlspecialchars($default_time_in . ' - ' . $default_time_out) ?></div>
              <div class="text-xs text-gray-500 whitespace-nowrap">Today's Schedule</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Tab Navigation with Action Buttons -->
    <div class="bg-white border-b border-gray-200 px-8">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button id="submitTab" onclick="switchTab('submit')" 
                  class="px-4 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600 transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>Submit Request
          </button>
          <button id="historyTab" onclick="switchTab('history')" 
                  class="px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-gray-900 hover:border-gray-300 transition-all duration-200">
            <i class="fas fa-history mr-2"></i>Request History
          </button>
        </div>
      </div>
    </div>

    <!-- Submit Request Tab Content -->
    <div id="submitContent" class="bg-white">
      
      <!-- Table Section -->
      <div class="px-8 py-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Recent Time Logs</h3>
            <p class="text-sm text-gray-500 mt-1">Review your work logs and submit overtime requests</p>
          </div>
          
          <!-- Legend -->
          <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 bg-green-500 rounded-full"></span>
              <span class="text-gray-600">OT Eligible</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 bg-blue-500 rounded-full"></span>
              <span class="text-gray-600">Regular</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 bg-red-500 rounded-full"></span>
              <span class="text-gray-600">Expired</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 bg-gray-400 rounded-full"></span>
              <span class="text-gray-600">No Data</span>
            </div>
          </div>
        </div>

        <!-- Records Per Page Selector -->
        <div class="bg-gray-50 border border-gray-200 rounded-t-lg px-4 py-3 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            <span class="font-medium text-gray-900"><?= $total_records ?></span> entries
          </div>
          <div class="flex items-center gap-2">
            <label for="per_page" class="text-sm text-gray-600">Show</label>
            <select id="per_page" onchange="changePerPage(this.value)" 
                    class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="5" <?= $records_per_page == 5 ? 'selected' : '' ?>>5</option>
              <option value="10" <?= $records_per_page == 10 ? 'selected' : '' ?>>10</option>
              <option value="20" <?= $records_per_page == 20 ? 'selected' : '' ?>>20</option>
              <option value="50" <?= $records_per_page == 50 ? 'selected' : '' ?>>50</option>
            </select>
          </div>
        </div>

        <!-- Modern Table -->
        <div class="border border-gray-200 rounded-b-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="timeLogsTable">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date & Day
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Active Schedule
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time In
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time Out
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Work Duration
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    OT Hours
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100" id="timeLogsBody">
                <?php foreach ($time_logs as $index => $log): 
                  $hasDate = !empty($log['log_date']);
                  $hasLog = !empty($log['time_in']) && !empty($log['time_out']);
                  if ($hasLog) {
                    // Get schedule info for this date FIRST (needed for accurate hours calculation)
                    $scheduleForCalc = getScheduleForDate($employee_id, $log['log_date'], $pdo);
                    $scheduleInTime = $scheduleForCalc['time_in'] ?? null;
                    // Convert schedule time to 24h format if it's in 12h format
                    if ($scheduleInTime && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $scheduleInTime)) {
                        $scheduleInTime = date('H:i:s', strtotime($scheduleInTime));
                    }
                    $isRestDayCalc = ($scheduleForCalc['is_rest_day'] ?? 0) == 1;
                    
                    // Use the corrected detailed calculation for eligibility
                    $detailedCalc = getOvertimeCalculationDetails($log['time_in'], $log['time_out'], $log['log_date'], $employee_id, $pdo);
                    $isOTEligible = $detailedCalc['eligible'];
                    $overtimeHours = $detailedCalc['details']['net_overtime_hours'];
                    
                    // Get Start OT and End OT times with detailed breakdown
                    $otDetails = getOvertimeTimesAndHours($log['time_in'], $log['time_out'], $log['log_date'], $employee_id, $pdo);
                    
                    // Debug logging for button logic
                    error_log("BUTTON DEBUG for {$log['log_date']}: Eligible={$isOTEligible}, OT Hours={$overtimeHours}, Reason=" . $detailedCalc['reason']);
                    
                    // Fallback to old calculation if detailed calc fails
                    if ($overtimeHours <= 0) {
                        $isOTEligible = false;
                        $overtimeHours = 0;
                    }
                    
                    // Calculate actual hours worked - pass schedule info to prevent early arrivals from inflating hours
                    // For Rest Days, don't adjust by schedule (all hours count as OT)
                    $actualHours = $isRestDayCalc 
                        ? calculateActualHoursWorked($log['time_in'], $log['time_out']) 
                        : calculateActualHoursWorked($log['time_in'], $log['time_out'], $scheduleInTime, $log['log_date']);
                    $requestStatus = hasExistingOTRequest($log['id']); // Returns status or false
                    $hasRequest = ($requestStatus !== false); // True if any request exists
                    $timeIn = new DateTime($log['time_in']);
                    $timeOut = new DateTime($log['time_out']);
                    $interval = $timeIn->diff($timeOut);
                    $totalHours = $interval->h + ($interval->i / 60);

                    $logDate = new DateTime($log['log_date']);
                    $now = new DateTime();
                    $daysPassed = $logDate->diff($now)->days;
                    $isOlderThan30Days = $daysPassed > 30; // Changed from hardcoded August 16 to 30 days
                    $rowStatus = $isOlderThan30Days ? 'noteligible' : ($isOTEligible ? ($hasRequest ? 'submitted' : 'eligible') : 'regular');
                  }
                ?>
                <tr class="hover:bg-emerald-50/30 transition-all duration-200 animate-slide-in-right group
                  <?php 
                    if (!$hasLog) {
                      echo 'bg-gray-50 border-l-4 border-l-gray-300';
                    } elseif ($isOlderThan30Days) {
                      echo 'bg-red-50/30 border-l-4 border-l-red-300';
                    } elseif ($isOTEligible) {
                      echo 'bg-emerald-50/40 border-l-4 border-l-emerald-400 shadow-sm';
                    } else {
                      echo 'border-l-4 border-l-blue-300';
                    }
                  ?>"
                  style="animation-delay: <?= $index * 0.05 ?>s;"
                  data-date="<?= $hasDate ? date('M d, Y', strtotime($log['log_date'])) : '' ?>"
                  data-log-date="<?= $hasDate ? $log['log_date'] : '' ?>"
                  data-status="<?= $hasLog ? $rowStatus : 'rdot' ?>"
                  data-log-id="<?= $hasLog ? $log['id'] : '' ?>"
                  data-time-in="<?= $hasLog ? $log['time_in'] : '' ?>"
                  data-time-out="<?= $hasLog ? $log['time_out'] : '' ?>"
                  data-ot-hours="<?= $hasLog ? $overtimeHours : '0' ?>"
                  data-start-ot="<?= $hasLog && isset($otDetails['start_ot']) ? $otDetails['start_ot'] : '' ?>"
                  data-end-ot="<?= $hasLog && isset($otDetails['end_ot']) ? $otDetails['end_ot'] : '' ?>"
                  data-max-ot-hours="<?= $hasLog && isset($otDetails['max_ot_hours']) ? $otDetails['max_ot_hours'] : '0' ?>"
                  <?php 
                  // Add scheduled start time and rest day flag for Restday OT calculation
                  if ($hasDate) {
                    $scheduleForRestday = getScheduleForDate($employee_id, $log['log_date'], $pdo);
                    $scheduledStartTime = $scheduleForRestday['time_in'] ?? '07:00 AM';
                    $isRestDay = $scheduleForRestday['is_rest_day'] ?? 0;
                    echo 'data-scheduled-start="' . htmlspecialchars($scheduledStartTime) . '" ';
                    echo 'data-is-rest-day="' . ($isRestDay ? '1' : '0') . '"';
                  }
                  ?>>
                  
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 mr-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                          <i class="fas fa-calendar text-blue-600 text-lg"></i>
                        </div>
                      </div>
                      <div>
                        <div class="text-lg font-bold text-gray-900">
                          <?= $hasDate ? date('M d, Y', strtotime($log['log_date'])) : '<span class="italic text-gray-400">No Date</span>' ?>
                        </div>
                        <div class="text-sm font-medium text-gray-500">
                          <?= $hasDate ? date('l', strtotime($log['log_date'])) : '' ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  
                  <td class="px-8 py-6">
                    <div class="space-y-2">
                      <?php 
                      // Get the correct schedule for this specific date
                      if ($hasDate) {
                        $dateSchedule = getScheduleForDate($employee_id, $log['log_date'], $pdo);
                        
                        // Check if it's a rest day or holiday
                        if ($dateSchedule['is_rest_day'] == 1) {
                          $scheduleTime = 'Rest Day';
                          $statusText = $dateSchedule['status_text'];
                          $statusColor = $dateSchedule['status_color'];
                        } elseif (!empty($dateSchedule['is_holiday'])) {
                          $scheduleTime = 'Holiday';
                          $statusText = $dateSchedule['status_text'];
                          $statusColor = $dateSchedule['status_color'];
                        } else {
                          $scheduleTime = $dateSchedule['time_in'] . ' - ' . $dateSchedule['time_out'];
                          $statusText = $dateSchedule['status_text'];
                          $statusColor = $dateSchedule['status_color']; // Use the color returned by the function
                        }
                      } else {
                        $scheduleTime = '—';
                        $statusText = '—';
                        $statusColor = 'text-gray-400';
                      }
                      ?>
                      <div class="text-lg font-bold text-gray-900">
                        <?= htmlspecialchars($scheduleTime) ?>
                      </div>
                      <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100">
                        <span class="<?= $statusColor ?>"><?= htmlspecialchars($statusText) ?></span>
                      </div>
                    </div>
                  </td>
                  
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 mr-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                          <i class="fas fa-sign-in-alt text-green-600"></i>
                        </div>
                      </div>
                      <span class="text-lg font-bold text-gray-900">
                        <?= $hasLog ? date('h:i A', strtotime($log['time_in'])) : '<span class="text-gray-400">—</span>' ?>
                      </span>
                    </div>
                  </td>
                  
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 mr-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                          <i class="fas fa-sign-out-alt text-orange-600"></i>
                        </div>
                      </div>
                      <span class="text-lg font-bold text-gray-900">
                        <?= $hasLog ? date('h:i A', strtotime($log['time_out'])) : '<span class="text-gray-400">—</span>' ?>
                      </span>
                    </div>
                  </td>
                  
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 mr-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                          <i class="fas fa-clock text-purple-600"></i>
                        </div>
                      </div>
                      <div>
                        <?php if ($hasLog): ?>
                          <div class="text-lg font-bold text-gray-900">
                            <?= formatDurationPHP($actualHours) ?>
                          </div>
                        <?php else: ?>
                          <div class="text-lg font-bold text-gray-400">—</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  
                  <!-- OT Hours Column -->
                  <td class="px-8 py-6 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 mr-3">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                          <i class="fas fa-hourglass-half text-amber-600"></i>
                        </div>
                      </div>
                      <div>
                        <?php if ($hasLog && isset($otDetails['max_ot_hours']) && $otDetails['max_ot_hours'] > 0): ?>
                          <div class="text-lg font-bold text-emerald-600">
                            <?= formatDurationPHP($otDetails['max_ot_hours']) ?>
                          </div>
                          <?php if ($otDetails['eligible']): ?>
                            <div class="text-sm text-emerald-600 font-medium">
                              <i class="fas fa-check-circle mr-1"></i>Eligible
                            </div>
                          <?php else: ?>
                            <div class="text-sm text-red-500 font-medium">
                              <i class="fas fa-exclamation-triangle mr-1"></i>
                              <?= isset($otDetails['is_restday']) && $otDetails['is_restday'] ? 'Need 8+ hrs' : 'Need 30+ min' ?>
                            </div>
                          <?php endif; ?>
                        <?php else: ?>
                          <div class="text-lg font-bold text-gray-400">0h</div>
                          <div class="text-xs text-gray-400">No overtime</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  
                  <!-- Action Column -->
                  <td class="px-8 py-6">
                    <?php if (!$hasLog): ?>
                      <div class="flex items-center justify-center w-full">
                        <span class="inline-flex items-center px-4 py-3 bg-gray-100 text-gray-500 rounded-xl text-sm font-medium border border-gray-200">
                          <i class="fas fa-ban mr-2"></i>
                          Not Available
                        </span>
                      </div>
                    <?php elseif ($isOlderThan30Days): ?>
                      <div class="flex items-center justify-center w-full">
                        <span class="inline-flex items-center px-4 py-3 bg-red-100 text-red-600 rounded-xl text-sm font-medium border border-red-200">
                          <i class="fas fa-clock mr-2"></i>
                          Request Expired
                        </span>
                      </div>
                    <?php elseif ($requestStatus === 'approved'): ?>
                      <div class="flex items-center justify-center w-full">
                        <span class="inline-flex items-center px-4 py-3 bg-green-100 text-green-700 rounded-xl text-sm font-medium border border-green-200">
                          <i class="fas fa-check-circle mr-2"></i>
                          Approved
                        </span>
                      </div>
                    <?php elseif ($requestStatus === 'pending'): ?>
                      <div class="flex items-center justify-center w-full">
                        <span class="inline-flex items-center px-4 py-3 bg-yellow-100 text-yellow-700 rounded-xl text-sm font-medium border border-yellow-200">
                          <i class="fas fa-clock mr-2"></i>
                          Request Submitted
                        </span>
                      </div>
                    <?php elseif ($requestStatus === 'declined' || !$hasRequest): ?>
                      <?php 
                      // Check if OT is actually eligible (30+ minutes for Regular OT, 8+ hours for Restday OT)
                      $canFileOT = $isOTEligible && isset($otDetails['max_ot_hours']) && $otDetails['max_ot_hours'] >= 0.5;
                      $isRestDayOT = isset($otDetails['is_restday']) && $otDetails['is_restday'];
                      
                      if ($canFileOT): 
                      ?>
                      <button onclick="openOvertimeModal(this)"
                              data-log-date="<?= $hasDate ? $log['log_date'] : '' ?>"
                              data-time-log-id="<?= $hasLog ? $log['id'] : '' ?>"
                              class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl transition-all duration-200 transform hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform duration-200"></i>
                        OT Request
                      </button>
                      <?php else: ?>
                      <div class="flex items-center justify-center w-full">
                        <span class="inline-flex items-center px-4 py-3 bg-gray-100 text-gray-500 rounded-xl text-sm font-medium border border-gray-200" title="<?= $isRestDayOT ? 'Need 8+ hours worked for Restday OT' : 'Need 30+ minutes past scheduled time for Regular OT' ?>">
                          <i class="fas fa-ban mr-2"></i>
                          Not Eligible
                        </span>
                      </div>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination Controls -->
          <?php if ($total_pages > 1): ?>
          <div class="bg-white px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <!-- Page Info -->
              <div class="text-sm text-gray-700">
                Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                <span class="font-medium"><?= min($offset + $records_per_page, $total_records) ?></span> of 
                <span class="font-medium"><?= $total_records ?></span> results
              </div>
              
                             <!-- Pagination Navigation -->
               <div class="flex items-center space-x-2">
                 <?php if ($total_pages > 10): ?>
                   <!-- Go to Page Input for Large Page Counts -->
                   <div class="flex items-center space-x-2 mr-4">
                     <label class="text-xs text-gray-600">Go to:</label>
                     <input type="number" id="goToPage" min="1" max="<?= $total_pages ?>" 
                            class="w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                            placeholder="<?= $current_page ?>">
                     <button onclick="goToPage()" 
                             class="px-2 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                       Go
                     </button>
                   </div>
                 <?php endif; ?>
                <!-- Previous Page -->
                <?php if ($current_page > 1): ?>
                  <a href="?page=<?= $current_page - 1 ?>&per_page=<?= $records_per_page ?>" 
                     class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                    <i class="fas fa-chevron-left mr-1"></i>
                    Previous
                  </a>
                <?php else: ?>
                  <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                    <i class="fas fa-chevron-left mr-1"></i>
                    Previous
                  </span>
                <?php endif; ?>
                
                                 <!-- Page Numbers -->
                 <div class="flex items-center space-x-1">
                   <?php
                   // Smart pagination logic - only show ellipsis when there are many pages
                   $show_ellipsis = $total_pages > 7;
                   
                   if ($show_ellipsis): ?>
                     <?php
                     // Show first page if we're not near the beginning
                     if ($current_page > 3): ?>
                       <a href="?page=1&per_page=<?= $records_per_page ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                         1
                       </a>
                       <?php if ($current_page > 4): ?>
                         <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400">...</span>
                       <?php endif; ?>
                     <?php endif; ?>
                     
                     <?php
                     // Show pages around current page (but limit to reasonable range)
                     $start_page = max(1, $current_page - 1);
                     $end_page = min($total_pages, $current_page + 1);
                     
                     for ($i = $start_page; $i <= $end_page; $i++): ?>
                       <?php if ($i == $current_page): ?>
                         <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-gray-600 border border-gray-600 rounded-lg">
                           <?= $i ?>
                         </span>
                       <?php else: ?>
                         <a href="?page=<?= $i ?>&per_page=<?= $records_per_page ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                           <?= $i ?>
                         </a>
                       <?php endif; ?>
                     <?php endfor; ?>
                     
                     <?php
                     // Show last page if we're not near the end
                     if ($current_page < $total_pages - 2): ?>
                       <?php if ($current_page < $total_pages - 3): ?>
                         <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-400">...</span>
                       <?php endif; ?>
                       <a href="?page=<?= $total_pages ?>&per_page=<?= $records_per_page ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                         <?= $total_pages ?>
                       </a>
                     <?php endif; ?>
                   <?php else: ?>
                     <?php
                     // For small numbers of pages, show all pages without ellipsis
                     for ($i = 1; $i <= $total_pages; $i++): ?>
                       <?php if ($i == $current_page): ?>
                         <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-gray-600 border border-gray-600 rounded-lg">
                           <?= $i ?>
                         </span>
                       <?php else: ?>
                         <a href="?page=<?= $i ?>&per_page=<?= $records_per_page ?>" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                           <?= $i ?>
                         </a>
                       <?php endif; ?>
                     <?php endfor; ?>
                   <?php endif; ?>
                 </div>
                
                <!-- Next Page -->
                <?php if ($current_page < $total_pages): ?>
                  <a href="?page=<?= $current_page + 1 ?>&per_page=<?= $records_per_page ?>" 
                     class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                    Next
                    <i class="fas fa-chevron-right ml-1"></i>
                  </a>
                <?php else: ?>
                  <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                    Next
                    <i class="fas fa-chevron-right ml-1"></i>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Request History Tab Content - Full Width -->
    <div id="historyContent" class="bg-white rounded-2xl shadow-xl border border-gray-200 hidden overflow-hidden">
      <div class="p-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-8 gap-4">
          <div class="flex-1">
            <h3 class="text-2xl font-bold text-gray-900 mb-2 flex items-center">
              <div class="p-3 bg-blue-100 rounded-xl mr-4">
                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
              </div>
              Overtime Request History
            </h3>
            <p class="text-gray-600 text-lg">
              Track the status and progress of all your overtime requests
            </p>
          </div>
          
          <!-- Quick Stats for History -->
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Request Summary</h4>
            <div class="grid grid-cols-3 gap-4 text-xs">
              <div class="text-center">
                <div class="text-lg font-bold text-green-600">
                  <?= count(array_filter($overtime_history, fn($r) => strtolower($r['request_status'] ?? $r['status']) === 'approved')) ?>
                </div>
                <div class="text-gray-600">Approved</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-yellow-600">
                  <?= count(array_filter($overtime_history, fn($r) => strtolower($r['request_status'] ?? $r['status']) === 'pending')) ?>
                </div>
                <div class="text-gray-600">Pending</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-red-600">
                  <?= count(array_filter($overtime_history, fn($r) => in_array(strtolower($r['request_status'] ?? $r['status']), ['declined', 'rejected']))) ?>
                </div>
                <div class="text-gray-600">Declined</div>
              </div>
            </div>
          </div>
        </div>

        <?php if (empty($overtime_history)): ?>
          <!-- No History Message -->
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
              <i class="fas fa-inbox text-2xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Requests Yet</h3>
            <p class="text-gray-600 mb-4">
              You haven't submitted any overtime requests yet.
            </p>
            <button onclick="switchTab('submit')" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
              <i class="fas fa-plus mr-2"></i>
              Submit Your First Request
            </button>
          </div>
        <?php else: ?>
          <!-- Enhanced History Table -->
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-lg">
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                  <tr>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                        Date & Time Period
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-calendar-check mr-2 text-indigo-500"></i>
                        Active Schedule
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-comment-alt mr-2 text-purple-500"></i>
                        Request Details
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-tag mr-2 text-green-500"></i>
                        Type & Duration
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-info-circle mr-2 text-orange-500"></i>
                        Status
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-clock mr-2 text-gray-500"></i>
                        Submitted
                      </div>
                    </th>
                    <th class="px-8 py-5 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-cog mr-2 text-gray-500"></i>
                        Actions
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($overtime_history as $index => $request): 
                    $statusClass = '';
                    $statusIcon = '';
                    $statusText = '';
                    
                    // Use request_status to ensure we're getting the correct status
                    $currentStatus = strtolower($request['request_status'] ?? $request['status'] ?? 'pending');
                    
                    switch($currentStatus) {
                      case 'approved':
                        $statusClass = 'status-approved';
                        $statusIcon = 'fas fa-check-circle';
                        $statusText = 'Approved';
                        break;
                      case 'declined':
                      case 'rejected':
                        $statusClass = 'status-declined';
                        $statusIcon = 'fas fa-times-circle';
                        $statusText = 'Declined';
                        break;
                      default:
                        $statusClass = 'status-pending';
                        $statusIcon = 'fas fa-clock';
                        $statusText = 'Pending';
                    }
                  ?>
                  <tr class="hover:bg-blue-50/30 transition-all duration-200 animate-slide-in-right group border-l-4 
                    <?php 
                      switch($currentStatus) {
                        case 'approved':
                          echo 'border-l-green-400 bg-green-50/20';
                          break;
                        case 'declined':
                        case 'rejected':
                          echo 'border-l-red-400 bg-red-50/20';
                          break;
                        default:
                          echo 'border-l-yellow-400 bg-yellow-50/20';
                      }
                    ?>"
                      style="animation-delay: <?= $index * 0.05 ?>s;">
                    
                    <td class="px-8 py-6">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 mr-4">
                          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-calendar text-blue-600 text-lg"></i>
                          </div>
                        </div>
                        <div>
                          <div class="text-lg font-bold text-gray-900">
                            <?= $request['formatted_log_date'] ?? 'N/A' ?>
                          </div>
                          <div class="text-sm text-gray-500 flex items-center mt-1">
                            <div class="flex items-center mr-4">
                              <i class="fas fa-sign-in-alt mr-1 text-green-500"></i>
                              <span class="font-medium"><?= $request['formatted_time_in'] ?? 'N/A' ?></span>
                            </div>
                            <div class="flex items-center">
                              <i class="fas fa-sign-out-alt mr-1 text-orange-500"></i>
                              <span class="font-medium"><?= $request['formatted_time_out'] ?? 'N/A' ?></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-8 py-6">
                      <div class="space-y-2">
                        <?php 
                        // Get the correct schedule for this specific date from the history
                        if (!empty($request['log_date'])) {
                          $historySchedule = getScheduleForDate($employee_id, $request['log_date'], $pdo);
                          
                          // Check if it's a rest day or holiday
                          if ($historySchedule['is_rest_day'] == 1) {
                            $historyScheduleTime = 'Rest Day';
                            $historyStatusText = $historySchedule['status_text'];
                            $historyStatusColor = $historySchedule['status_color'];
                          } elseif (!empty($historySchedule['is_holiday'])) {
                            $historyScheduleTime = 'Holiday';
                            $historyStatusText = $historySchedule['status_text'];
                            $historyStatusColor = $historySchedule['status_color'];
                          } else {
                            $historyScheduleTime = $historySchedule['time_in'] . ' - ' . $historySchedule['time_out'];
                            $historyStatusText = $historySchedule['status_text'];
                            $historyStatusColor = $historySchedule['status_color']; // Use the color returned by the function
                          }
                        } else {
                          $historyScheduleTime = '—';
                          $historyStatusText = 'N/A';
                          $historyStatusColor = 'text-gray-400';
                        }
                        ?>
                        <div class="text-lg font-bold text-gray-900">
                          <?= htmlspecialchars($historyScheduleTime) ?>
                        </div>
                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100">
                          <span class="<?= $historyStatusColor ?>"><?= htmlspecialchars($historyStatusText) ?></span>
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-8 py-6">
                      <div class="space-y-2">
                        <div class="text-base font-medium text-gray-900 max-w-sm">
                          <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <p class="text-sm leading-relaxed" title="<?= htmlspecialchars($request['reason']) ?>">
                              <?= htmlspecialchars(substr($request['reason'], 0, 120)) . (strlen($request['reason']) > 120 ? '...' : '') ?>
                            </p>
                          </div>
                        </div>
                        <?php if (!empty($request['admin_comment'])): ?>
                          <div class="text-xs bg-blue-50 border border-blue-200 rounded-lg p-2">
                            <span class="font-medium text-blue-700">Admin Note:</span>
                            <p class="text-blue-600 italic mt-1">
                              "<?= htmlspecialchars(substr($request['admin_comment'], 0, 80)) . (strlen($request['admin_comment']) > 80 ? '...' : '') ?>"
                            </p>
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>
                    
                    <td class="px-8 py-6">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 mr-3">
                          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tag text-green-600"></i>
                          </div>
                        </div>
                        <div>
                          <div class="text-base font-bold text-gray-900">
                            <?= htmlspecialchars($request['ot_type']) ?>
                          </div>
                          <div class="text-sm text-gray-500 flex items-center mt-1">
                            <i class="fas fa-hourglass-half mr-1 text-purple-500"></i>
                            <span class="font-medium"><?= formatDurationPHP($request['ot_duration']) ?></span>
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-8 py-6">
                      <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold border <?= $statusClass ?> shadow-sm">
                        <i class="<?= $statusIcon ?> mr-2"></i>
                        <?= $statusText ?>
                      </span>
                    </td>
                    
                    <td class="px-8 py-6">
                      <div class="text-sm">
                        <div class="font-medium text-gray-900">
                          <?= $request['formatted_created_at'] ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          <?php 
                            $submittedDate = new DateTime($request['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($submittedDate);
                            if ($diff->days > 0) {
                              echo $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                            } elseif ($diff->h > 0) {
                              echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                            } else {
                              echo $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                            }
                          ?>
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-8 py-6">
                      <div class="flex flex-col space-y-2">
                        <button onclick="viewOTDetails(<?= $request['id'] ?>)" 
                                class="inline-flex items-center px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-xl text-sm font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-300">
                          <i class="fas fa-eye mr-2"></i>
                          View Details
                        </button>
                        <?php if (!empty($request['attachment'])): ?>
                          <a href="../uploads/overtime_attachments/<?= htmlspecialchars($request['attachment']) ?>" target="_blank"
                             class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            <i class="fas fa-paperclip mr-2"></i>
                            Attachment
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Overtime Request Modal -->
<div id="overtimeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
  <!-- Background overlay -->
  <div class="fixed inset-0 transition-opacity bg-black/40 backdrop-blur-sm" onclick="closeOvertimeModal()"></div>

  <!-- Modal content -->
  <div class="inline-block w-full max-w-4xl px-0 pt-0 pb-0 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle border border-gray-200 animate-fade-in-up">
            
            <!-- Modal Header -->
      <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Submit Overtime Request</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Fill out the details for your request</p>
                        </div>
                    </div>
          <button onclick="closeOvertimeModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-4 max-h-[calc(100vh-180px)] overflow-y-auto">
                <form id="overtimeForm" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" id="selected_time_log_id" name="time_log_id">
                    <input type="hidden" id="selected_time_in" name="time_in">
                    <input type="hidden" id="selected_time_out" name="time_out">
                    <input type="hidden" id="max_ot_hours" name="max_ot_hours">
                    <input type="hidden" id="start_ot_time" name="start_ot_time">
                    <input type="hidden" id="end_ot_time" name="end_ot_time">
                    
                    <!-- Information Grid -->
          <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">
                            Request Information
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700">Selectable OT Hours</label>
                                <div class="relative">
                                    <input type="hidden" id="overtime_hours" name="overtime_hours" value="">
                                    
                                    <div class="w-full px-3 py-2.5 border border-gray-300 rounded-md bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
                                        <div class="flex items-center justify-center space-x-3">
                                            <div class="flex flex-col items-center">
                                                <label class="text-xs text-gray-500 mb-1">Hrs</label>
                                                <input type="number" 
                                                       id="hours-input" 
                                                       min="0" 
                                                       max="99"
                                                       value="0"
                                                       class="w-12 h-7 text-center border border-gray-300 rounded text-sm font-medium text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            </div>
                                            
                                            <div class="text-lg font-medium text-gray-400 mt-4">:</div>
                                            
                                            <div class="flex flex-col items-center">
                                                <label class="text-xs text-gray-500 mb-1">Min</label>
                                                <input type="number" 
                                                       id="minutes-input" 
                                                       min="0" 
                                                       max="59"
                                                       step="1"
                                                       value="0"
                                                       class="w-12 h-7 text-center border border-gray-300 rounded text-sm font-medium text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="ot-range-info" class="text-xs text-gray-600"></div>
                                <div id="ot-validation-info" class="text-xs text-green-600"></div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700">Max OT Available</label>
                                <input type="text" id="display_max_ot_hours" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md bg-gray-50 font-medium text-center text-gray-900 focus:outline-none text-sm"
                                       readonly>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700">Start OT</label>
                                <input type="text" id="display_start_ot" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md bg-white focus:outline-none text-sm font-medium text-gray-900"
                                       readonly>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700">End OT</label>
                                <input type="text" id="display_end_ot" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md bg-white focus:outline-none text-sm font-medium text-gray-900"
                                       readonly>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-medium text-gray-700">Date</label>
                                <input type="text" id="selected_date" name="selected_date" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md bg-white focus:outline-none text-sm font-medium text-gray-900"
                                       readonly>
                            </div>

                            <!-- Hidden Time In and Time Out fields for form submission -->
                            <div class="hidden">
                                <input type="text" id="display_time_in" readonly>
                                <input type="text" id="display_time_out" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- OT Type and Attachment -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">
                                Overtime Type <span class="text-red-500">*</span>
                            </label>
                            <select name="ot_type" id="ot_type" required
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-sm font-medium text-gray-900">
                                <option value="">Select OT Type</option>
                                <option value="Regular OT">Regular OT</option>
                                <option value="Special Holiday OT">Special Holiday OT</option>
                                <option value="Regular Holiday OT">Regular Holiday OT</option>
                                <option value="Restday OT">Restday OT</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700">
                                Supporting Document <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   name="attachment" 
                                   id="attachment" 
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   required
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 text-sm bg-white">
                            <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG (Max 5MB)</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <label class="block text-xs font-medium text-gray-700">
                            Reason for Overtime <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="4" required
                                  class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none placeholder:text-gray-400 text-sm bg-white"
                                  placeholder="Provide detailed explanation for OT work (indicate if with or without break)"></textarea>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-5 border-t border-gray-200">
                        <button type="button" onclick="closeOvertimeModal()" 
                                class="flex-1 px-4 py-2.5 border border-gray-300 rounded-md text-gray-700 font-medium bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors text-sm">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to format duration in hours and minutes (JavaScript version)
function formatDuration(hours) {
    const totalHours = parseFloat(hours || 0);
    const wholeHours = Math.floor(totalHours);
    const minutes = Math.round((totalHours - wholeHours) * 60);
    
    if (wholeHours === 0 && minutes === 0) {
        return '0 min';
    } else if (wholeHours === 0) {
        return minutes + ' min';
    } else if (minutes === 0) {
        return wholeHours + (wholeHours > 1 ? ' hrs' : ' hr');
    } else {
        return wholeHours + (wholeHours > 1 ? ' hrs ' : ' hr ') + minutes + ' min';
    }
}

// Utility: compute Restday OT hours from Start OT to End OT
function computeRDOTHrsFromStartToEnd() {
    const startOTStr = document.getElementById('start_ot_time')?.value || '';
    const endOTStr = document.getElementById('end_ot_time')?.value || '';
    
    console.log('computeRDOTHrsFromStartToEnd - Start OT:', startOTStr, 'End OT:', endOTStr);
    
    if (!startOTStr || !endOTStr) {
        console.warn('Start OT or End OT not available for Restday OT calculation');
        return 0;
    }

    // Try a few parsing strategies to be resilient to formats
    const parseDateTime = (s) => {
        if (!s) return null;
        // 1) Time with AM/PM format (most common in UI)
        if (/\d{1,2}:\d{2}\s?(AM|PM)/i.test(s)) {
            const d = new Date('2000-01-01 ' + s);
            if (!isNaN(d.getTime())) return d;
        }
        // 2) ISO-like: YYYY-MM-DD HH:MM[:SS]
        const isoLike = new Date(s.replace(' ', 'T'));
        if (!isNaN(isoLike.getTime())) return isoLike;
        // 3) If only time provided, pair with arbitrary date
        if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(s)) {
            const d = new Date('2000-01-01T' + s.padStart(5, '0'));
            if (!isNaN(d.getTime())) return d;
        }
        // 3) Manual parse fallback
        const m = s.match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{1,2}):(\d{2})(?::(\d{2}))?/);
        if (m) {
            const [_, Y, M, D, h, i, sec] = m;
            const d = new Date(Number(Y), Number(M) - 1, Number(D), Number(h), Number(i), Number(sec || 0));
            if (!isNaN(d.getTime())) return d;
        }
        return null;
    };

    try {
        const scheduledStart = parseDateTime(scheduledStartTime);
        const startOT = parseDateTime(startOTStr);
        const endOT = parseDateTime(endOTStr);
        
        if (!startOT || !endOT) {
            console.warn('Could not parse Start OT or End OT times');
            return 0;
        }

        let diffMs = endOT.getTime() - startOT.getTime();
        if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000;
        const diffHours = diffMs / (1000 * 60 * 60);
        
        // For Restday OT, apply lunch deduction only when 8+ hours
        const workHours = diffHours >= 8 ? Math.max(0, diffHours - 1) : diffHours;
        return Number.isFinite(workHours) ? workHours : 0;
    } catch (e) {
        console.error('computeRDOTHrsFromStartToEnd error:', e);
        return 0;
    }
}

// Utility: compute regular total work hours from time in to time out (for fallback)
function computeRDOTHrsFromHiddenFields() {
    const timeInStr = document.getElementById('selected_time_in')?.value || '';
    const timeOutStr = document.getElementById('selected_time_out')?.value || '';
    if (!timeInStr || !timeOutStr) {
        return 0;
    }

    // Try a few parsing strategies to be resilient to formats
    const parseDateTime = (s) => {
        if (!s) return null;
        // 1) ISO-like: YYYY-MM-DD HH:MM[:SS]
        const isoLike = new Date(s.replace(' ', 'T'));
        if (!isNaN(isoLike.getTime())) return isoLike;
        // 2) If only time provided, pair with arbitrary date
        if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(s)) {
            const d = new Date('2000-01-01T' + s.padStart(5, '0'));
            if (!isNaN(d.getTime())) return d;
        }
        // 3) Manual parse fallback
        const m = s.match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{1,2}):(\d{2})(?::(\d{2}))?/);
        if (m) {
            const [_, Y, M, D, h, i, sec] = m;
            const d = new Date(Number(Y), Number(M) - 1, Number(D), Number(h), Number(i), Number(sec || 0));
            if (!isNaN(d.getTime())) return d;
        }
        return null;
    };

    try {
        const inDate = parseDateTime(timeInStr);
        const outDate = parseDateTime(timeOutStr);
        if (!inDate || !outDate) return 0;
        let diffMs = outDate.getTime() - inDate.getTime();
        // Night-shift: time_out is earlier than time_in on the same date — add one day
        if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000;
        const diffHours = diffMs / (1000 * 60 * 60);
        const workHours = diffHours >= 8 ? Math.max(0, diffHours - 1) : diffHours;
        return Number.isFinite(workHours) ? workHours : 0;
    } catch (e) {
        console.error('computeRDOTHrsFromHiddenFields error:', e);
        return 0;
    }
}
// Function to initialize simple input-based time picker
function initializeTimePicker(maxHours) {
    const hoursInput = document.getElementById('hours-input');
    const minutesInput = document.getElementById('minutes-input');
    const hiddenInput = document.getElementById('overtime_hours');
    const rangeInfo = document.getElementById('ot-range-info');
    const validationInfo = document.getElementById('ot-validation-info');
    
    if (!hoursInput || !minutesInput) return;
    
    if (maxHours <= 0) {
        hoursInput.disabled = true;
        minutesInput.disabled = true;
        rangeInfo.textContent = 'No overtime available for this time log';
        validationInfo.textContent = 'Please select a different time log.';
        validationInfo.className = 'mt-0.5 text-xs text-red-500 truncate';
        return;
    }
    
    // Check if this is Restday OT to show lunch deduction info
    const otTypeSelect = document.getElementById('ot_type');
    const isRestdayOT = otTypeSelect && otTypeSelect.value === 'Restday OT';
    
    if (isRestdayOT) {
        rangeInfo.innerHTML = `Maximum available: ${formatDuration(maxHours)} <span class="text-orange-600 font-semibold">(1hr lunch deduction applied)</span>`;
    } else {
        rangeInfo.textContent = `Maximum available: ${formatDuration(maxHours)}`;
    }
    
    function updateHiddenInput() {
        const hours = parseInt(hoursInput.value) || 0;
        const minutes = parseInt(minutesInput.value) || 0;
        
        // Calculate total hours as decimal
        const totalHours = hours + (minutes / 60);
        
        if (totalHours === 0) {
            hiddenInput.value = '';
            validationInfo.textContent = 'Select approved OT hours';
            validationInfo.className = 'mt-0.5 text-xs text-gray-500 truncate';
            toggleSubmitButton(false, 'Select OT hours');
        } else if (totalHours > maxHours) {
            hiddenInput.value = totalHours.toFixed(2);
            validationInfo.textContent = 'Exceeds available OT time';
            validationInfo.className = 'mt-0.5 text-xs text-red-500 truncate';
            toggleSubmitButton(false, 'Exceeds available OT');
        } else {
            hiddenInput.value = totalHours.toFixed(2);
            
            // Show selected hours
            validationInfo.textContent = `Selected: ${formatDuration(totalHours)}`;
            validationInfo.className = 'mt-0.5 text-xs text-emerald-600 truncate';
            toggleSubmitButton(true);
        }
    }
    
    // Helper to enable/disable submit button
    function toggleSubmitButton(enabled, reason = '') {
        const submitBtn = document.querySelector('#overtimeForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = !enabled;
            if (enabled) {
                submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed', 'opacity-60');
                submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                submitBtn.title = '';
            } else {
                submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed', 'opacity-60');
                submitBtn.title = reason || 'Cannot submit';
            }
        }
    }
    
    // Input event listeners
    hoursInput.addEventListener('input', function() {
        let value = parseInt(this.value);
        if (value < 0) this.value = 0;
        if (value > 99) this.value = 99;
        updateHiddenInput();
    });
    
    minutesInput.addEventListener('input', function() {
        let value = parseInt(this.value);
        if (value < 0) this.value = 0;
        if (value > 59) this.value = 59;
        updateHiddenInput();
    });
    
    // Initialize
    updateHiddenInput();
}

// Updated function (renamed for clarity)
function populateOvertimeHoursDropdown(maxHours) {
    // Now calls the new time picker function
    initializeTimePicker(maxHours);
}

// Helper function to format minutes to HH:MM:SS format
function formatMinutesToTime(totalMinutes) {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours}:${minutes.toString().padStart(2, '0')}:00`;
}

// Open overtime modal with data
function openOvertimeModal(button) {
    console.log('openOvertimeModal called with button:', button);
    
    const row = button.closest('tr');
    if (!row) {
        console.error('No table row found for button');
        return;
    }
    
    const timeLogId = row.dataset.logId;
    const timeIn = row.dataset.timeIn;
    const timeOut = row.dataset.timeOut;
    const otHours = parseFloat(row.dataset.otHours);
    const date = row.dataset.date;
    const startOT = row.dataset.startOt || '';
    const endOT = row.dataset.endOt || '';
    const maxOTHours = parseFloat(row.dataset.maxOtHours) || 0;
    const scheduledStart = row.dataset.scheduledStart || '';
    const isRestDay = row.dataset.isRestDay === '1';
    
    console.log('Modal Data:', { timeLogId, timeIn, timeOut, otHours, date, startOT, endOT, maxOTHours }); // Debug log
    
    // Populate basic form fields
    document.getElementById('selected_time_log_id').value = timeLogId || '';
    document.getElementById('selected_time_in').value = timeIn || '';
    document.getElementById('selected_time_out').value = timeOut || '';
    document.getElementById('selected_date').value = date || '';
    
    // Store max OT hours
    document.getElementById('max_ot_hours').value = maxOTHours.toFixed(2);
    document.getElementById('display_max_ot_hours').value = formatDuration(maxOTHours);
    
    // Populate Start OT and End OT fields
    document.getElementById('start_ot_time').value = startOT;
    document.getElementById('end_ot_time').value = endOT;
    document.getElementById('display_start_ot').value = startOT;
    document.getElementById('display_end_ot').value = endOT;
    
    // Populate selectable overtime hours dropdown
    populateOvertimeHoursDropdown(maxOTHours);
    
    // Format and display times - only if timeIn and timeOut exist
    if (timeIn && timeOut) {
        try {
            const timeInFormatted = new Date('2000-01-01 ' + timeIn).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const timeOutFormatted = new Date('2000-01-01 ' + timeOut).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            document.getElementById('display_time_in').value = timeInFormatted;
            document.getElementById('display_time_out').value = timeOutFormatted;
        } catch (error) {
            console.error('Time formatting error:', error);
            document.getElementById('display_time_in').value = timeIn;
            document.getElementById('display_time_out').value = timeOut;
        }
    }
    
    // Set default OT type based on rest day status
    const otTypeSelect = document.getElementById('ot_type');
    const restdayOption = otTypeSelect.querySelector('option[value="Restday OT"]');
    
    if (isRestDay) {
        // Rest day: Enable Restday OT option and set as default
        if (restdayOption) {
            restdayOption.disabled = false;
            restdayOption.textContent = 'Restday OT';
        }
        otTypeSelect.value = 'Restday OT';
    } else {
        // Not a rest day: Disable Restday OT option and set to Regular OT
        if (restdayOption) {
            restdayOption.disabled = true;
            restdayOption.textContent = 'Restday OT (Only available on rest days)';
        }
        otTypeSelect.value = 'Regular OT';
    }
    
    // Store regular OT hours and original start OT for restoration when switching types
    const overtimeForm = document.getElementById('overtimeForm');
    if (overtimeForm) {
        overtimeForm.dataset.regularOtHours = (otHours || 0).toFixed(2);
        overtimeForm.dataset.originalStartOt = startOT || '';
        overtimeForm.dataset.scheduledStartTime = scheduledStart || '';
        overtimeForm.dataset.isRestDay = isRestDay ? '1' : '0';
    }
    
    // Clear previous values
    document.getElementById('reason').value = '';
    document.getElementById('attachment').value = '';
    
    // Set modal title based on rest day status
    const modalTitle = document.querySelector('#overtimeModal h3');
    if (modalTitle) {
        if (isRestDay) {
            modalTitle.textContent = 'Submit Rest Day Overtime Request';
        } else {
            modalTitle.textContent = 'Submit Overtime Request';
        }
    }
    
    // Show modal
    document.getElementById('overtimeModal').classList.remove('hidden');
    
    // Focus on OT type field
    setTimeout(() => {
        document.getElementById('ot_type').focus();
    }, 100);

    // Enable all fields for normal OT
    document.getElementById('ot_type').removeAttribute('disabled');
    document.getElementById('selected_time_in').removeAttribute('readonly');
    document.getElementById('selected_time_out').removeAttribute('readonly');
    document.getElementById('selected_date').setAttribute('readonly', 'readonly');
    document.getElementById('overtime_hours').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_in').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_out').setAttribute('readonly', 'readonly');
    document.getElementById('reason').removeAttribute('readonly');
    document.getElementById('attachment').removeAttribute('disabled');
}

// Open RDOT modal with data
function openRDOTModal(button) {
    const row = button.closest('tr');
    const timeLogId = row.dataset.logId;
    const timeIn = row.dataset.timeIn;
    const timeOut = row.dataset.timeOut;
    const date = row.dataset.date;
    const startOT = row.dataset.startOt || timeIn; // For RDOT, start OT is usually time in
    const endOT = row.dataset.endOt || timeOut;    // For RDOT, end OT is time out
    
    console.log('RDOT Modal Data:', { timeLogId, timeIn, timeOut, date, startOT, endOT });
    
    // Calculate work hours for RDOT
    let workHours = 0;
    let isEligible = false;
    if (timeIn && timeOut) {
        // Parse as full timestamps to preserve date span
        const timeInDate = new Date(timeIn.replace(' ', 'T'));
        const timeOutDate = new Date(timeOut.replace(' ', 'T'));
        let diffMs = timeOutDate.getTime() - timeInDate.getTime();
        // Night-shift: time_out stored on same date as time_in but is actually next day
        if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000;
        const diffHours = diffMs / (1000 * 60 * 60);
        const rawHours = diffHours;
        const netHours = rawHours >= 8 ? Math.max(0, rawHours - 1) : rawHours;
        workHours = netHours;
        isEligible = rawHours >= 8; // Restday OT needs 8+ hours
    }
    
    // Populate form fields
    document.getElementById('selected_time_log_id').value = timeLogId || '';
    document.getElementById('selected_time_in').value = timeIn || '';
    document.getElementById('selected_time_out').value = timeOut || '';
    document.getElementById('selected_date').value = date || '';
    
    // Store max OT hours for RDOT
    document.getElementById('max_ot_hours').value = workHours.toFixed(2);
    document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
    
    // Populate Start OT and End OT fields for RDOT
    document.getElementById('start_ot_time').value = startOT;
    document.getElementById('end_ot_time').value = endOT;
    document.getElementById('display_start_ot').value = startOT;
    document.getElementById('display_end_ot').value = endOT;
    
    // Populate selectable overtime hours dropdown
    populateOvertimeHoursDropdown(workHours);
    
    // Format and display times
    if (timeIn && timeOut) {
        try {
            const timeInFormatted = new Date('2000-01-01 ' + timeIn).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const timeOutFormatted = new Date('2000-01-01 ' + timeOut).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            document.getElementById('display_time_in').value = timeInFormatted;
            document.getElementById('display_time_out').value = timeOutFormatted;
        } catch (error) {
            console.error('Time formatting error:', error);
            document.getElementById('display_time_in').value = timeIn;
            document.getElementById('display_time_out').value = timeOut;
        }
    }
    
    // Set RDOT as default type
    document.getElementById('ot_type').value = 'Restday OT';
    
    // Clear previous values
    document.getElementById('reason').value = '';
    document.getElementById('attachment').value = '';
    
    // Update modal title for RDOT
    const modalTitle = document.querySelector('#overtimeModal h3');
    if (modalTitle) {
        modalTitle.textContent = 'Submit Rest Day Overtime Request';
    }
    
    // Show modal
    document.getElementById('overtimeModal').classList.remove('hidden');
    
    // Focus on reason field since OT type is pre-selected
    setTimeout(() => {
        document.getElementById('reason').focus();
    }, 100);

    // Enable all fields for RDOT
    document.getElementById('ot_type').removeAttribute('disabled');
    document.getElementById('selected_time_in').removeAttribute('readonly');
    document.getElementById('selected_time_out').removeAttribute('readonly');
    document.getElementById('selected_date').setAttribute('readonly', 'readonly');
    document.getElementById('display_max_ot_hours').setAttribute('readonly', 'readonly');
    document.getElementById('display_start_ot').setAttribute('readonly', 'readonly');
    document.getElementById('display_end_ot').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_in').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_out').setAttribute('readonly', 'readonly');
    document.getElementById('reason').removeAttribute('readonly');
    document.getElementById('attachment').removeAttribute('disabled');
}

// Add missing close function for modal
function closeOvertimeModal() {
    document.getElementById('overtimeModal').classList.add('hidden');
    
    // Reset modal title
    const modalTitle = document.querySelector('#overtimeModal h3');
    if (modalTitle) {
        modalTitle.textContent = 'Submit Overtime Request';
    }
}

// Tab switching functionality
function switchTab(tab) {
    const submitTab = document.getElementById('submitTab');
    const historyTab = document.getElementById('historyTab');
    const submitContent = document.getElementById('submitContent');
    const historyContent = document.getElementById('historyContent');
    
    if (tab === 'submit') {
        submitTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-active';
        historyTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-inactive';
        submitContent.classList.remove('hidden');
        historyContent.classList.add('hidden');
    } else {
        submitTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-inactive';
        historyTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-active';
        submitContent.classList.add('hidden');
        historyContent.classList.remove('hidden');
    }
}

// Replace incomplete or broken call with working fetch for OT details
function viewOTDetails(requestId) {
    fetch('get_overtime_details.php?id=' + requestId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let details = `Overtime Request Details:\n\n`;
                details += `Date: ${data.request.log_date}\n`;
                details += `Type: ${data.request.ot_type}\n`;
                details += `Hours: ${data.request.overtime_hours}\n`;
                details += `Status: ${data.request.status}\n`;
                details += `Reason: ${data.request.reason}\n`;
                if (data.request.reviewed_comment) {
                    details += `Review Comment: ${data.request.reviewed_comment}\n`;
                }
                alert(details);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching details');
        });
}

// Function to change records per page
function changePerPage(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', value);
    urlParams.delete('page'); // Reset to first page when changing per page
    
    // Update URL without reloading
    const newUrl = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState({}, '', newUrl);
    
    // Load the new page content via AJAX
    loadPageContent(1, value);
}

// Function to go to specific page
function goToPage() {
    const pageInput = document.getElementById('goToPage');
    const page = parseInt(pageInput.value);
    const maxPage = parseInt(pageInput.max);
    
    if (isNaN(page) || page < 1 || page > maxPage) {
        showNotification('Please enter a valid page number between 1 and ' + maxPage, 'error');
        pageInput.focus();
        return;
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    
    // Update URL without reloading
    const newUrl = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState({}, '', newUrl);
    
    // Load the new page content via AJAX
    loadPageContent(page, urlParams.get('per_page') || 10);
}

// Function to navigate to a specific page
function navigateToPage(page, perPage = null) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    if (perPage !== null) {
        urlParams.set('per_page', perPage);
    }
    
    // Update URL without reloading
    const newUrl = window.location.pathname + '?' + urlParams.toString();
    window.history.pushState({}, '', newUrl);
    
    // Load the new page content via AJAX
    loadPageContent(page, perPage || urlParams.get('per_page') || 10);
}

// Function to load page content via AJAX
function loadPageContent(page, perPage) {
    // Show loading state
    const tableBody = document.querySelector('#timeLogsTable tbody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600 mx-auto"></div><p class="mt-2 text-gray-500">Loading...</p></td></tr>';
    }
    
    // Build the AJAX URL
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('ajax', '1'); // Flag to indicate AJAX request
    
    // Fetch the new page content
    fetch(url.toString())
        .then(response => response.text())
        .then(html => {
            // Parse the HTML and extract the table content and pagination
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update the table body
            const newTableBody = doc.querySelector('#timeLogsTable tbody');
            if (newTableBody && tableBody) {
                tableBody.innerHTML = newTableBody.innerHTML;
            }
            
            // Update the pagination section
            const newPagination = doc.querySelector('.bg-white.px-6.py-4.border-t.border-gray-200');
            const currentPagination = document.querySelector('.bg-white.px-6.py-4.border-t.border-gray-200');
            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }
            
            // Update the records per page selector
            const newPerPageSelector = doc.querySelector('#recordsPerPage');
            const currentPerPageSelector = document.getElementById('recordsPerPage');
            if (newPerPageSelector && currentPerPageSelector) {
                currentPerPageSelector.value = newPerPageSelector.value;
            }
            
            // Update the go to page input
            const newGoToPageInput = doc.querySelector('#goToPage');
            const currentGoToPageInput = document.getElementById('goToPage');
            if (newGoToPageInput && currentGoToPageInput) {
                currentGoToPageInput.placeholder = newGoToPageInput.placeholder;
                currentGoToPageInput.max = newGoToPageInput.max;
            }
            
            // Re-attach event listeners to new pagination elements
            attachPaginationEventListeners();
            
            // Show success notification
            showNotification(`Page ${page} loaded successfully`, 'success');
        })
        .catch(error => {
            console.error('Error loading page:', error);
            showNotification('Error loading page content. Please refresh the page.', 'error');
            
            // Restore original content on error
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-500">Error loading content. Please refresh the page.</td></tr>';
            }
        });
}

// Function to attach event listeners to pagination elements
function attachPaginationEventListeners() {
    // Add click event listeners to all pagination links
    const paginationLinks = document.querySelectorAll('.bg-white.px-6.py-4.border-t.border-gray-200 a[href*="page="]');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const href = this.getAttribute('href');
            const urlParams = new URLSearchParams(href);
            const page = urlParams.get('page');
            const perPage = urlParams.get('per_page');
            
            if (page) {
                navigateToPage(parseInt(page), perPage ? parseInt(perPage) : null);
            }
        });
    });
    
    // Add event listener for go to page input
    const goToPageInput = document.getElementById('goToPage');
    if (goToPageInput) {
        goToPageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToPage();
            }
        });
    }
}

// Enhanced form submission handler - submit to dedicated processor
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('overtimeForm');
    if (!form) {
        console.error('Overtime form not found!');
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Form submission started...');
        
        // Wait a moment to ensure any pending input is captured
        setTimeout(() => {
            submitOvertimeForm(this);
        }, 100);
    });
    
    // Add event listener for OT type change to recalculate eligibility
    const otTypeSelect = document.getElementById('ot_type');
    if (otTypeSelect) {
        otTypeSelect.addEventListener('change', function() {
            console.log('OT Type changed to:', this.value);
            const type = this.value;
            const hoursInput = document.getElementById('overtime_hours');
            const formEl = document.getElementById('overtimeForm');
            if (!hoursInput) return;
            
            if (type === 'Restday OT') {
                const workHours = computeRDOTHrsFromStartToEnd();
                hoursInput.value = workHours.toFixed(2);
                
                // Update MAX OT AVAILABLE display
                document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                
                // For Restday OT, set start OT to the selected time in
                const timeInValue = document.getElementById('selected_time_in')?.value || '';
                if (timeInValue) {
                    try {
                        // Format the time in to display format
                        const timeInFormatted = new Date('2000-01-01 ' + timeInValue).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                        
                        // Update both the hidden field and display field for start OT
                        document.getElementById('start_ot_time').value = timeInFormatted;
                        document.getElementById('display_start_ot').value = timeInFormatted;
                        
                        console.log('Updated Start OT for Restday OT:', timeInFormatted);
                    } catch (error) {
                        console.error('Error formatting time for Restday OT:', error);
                        // Fallback to original time format
                        document.getElementById('start_ot_time').value = timeInValue;
                        document.getElementById('display_start_ot').value = timeInValue;
                    }
                }
            } else {
                // Restore regular OT hours and start OT time
                const fallback = formEl?.dataset?.regularOtHours;
                if (fallback !== undefined && fallback !== null) {
                    hoursInput.value = fallback;
                }
                
                // Restore original start OT time for regular OT (from data attributes)
                const originalStartOT = formEl?.dataset?.originalStartOt || '';
                if (originalStartOT) {
                    document.getElementById('start_ot_time').value = originalStartOT;
                    document.getElementById('display_start_ot').value = originalStartOT;
                }
            }
            // Trigger an input event to ensure any UI bindings react
            try { hoursInput.dispatchEvent(new Event('input', { bubbles: true })); } catch(e) {}
        });
        // If modal opens with RDOT already selected, compute immediately
        if (otTypeSelect.value === 'Restday OT') {
            const hoursInput = document.getElementById('overtime_hours');
            if (hoursInput) {
                const workHours = computeRDOTHrsFromStartToEnd();
                hoursInput.value = workHours.toFixed(2);
                
                // Update MAX OT AVAILABLE display
                document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                
                // Also set start OT to time in for Restday OT
                const timeInValue = document.getElementById('selected_time_in')?.value || '';
                if (timeInValue) {
                    try {
                        const timeInFormatted = new Date('2000-01-01 ' + timeInValue).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                        document.getElementById('start_ot_time').value = timeInFormatted;
                        document.getElementById('display_start_ot').value = timeInFormatted;
                    } catch (error) {
                        document.getElementById('start_ot_time').value = timeInValue;
                        document.getElementById('display_start_ot').value = timeInValue;
                    }
                }
                
                try { hoursInput.dispatchEvent(new Event('input', { bubbles: true })); } catch(e) {}
            }
        }
    }
    
    // Add event listener for Start OT time changes (only for Restday OT)
    const startOTInput = document.getElementById('start_ot_time');
    if (startOTInput) {
        startOTInput.addEventListener('change', function() {
            const otTypeSelect = document.getElementById('ot_type');
            if (otTypeSelect && otTypeSelect.value === 'Restday OT') {
                console.log('Start OT changed for Restday OT:', this.value);
                const hoursInput = document.getElementById('overtime_hours');
                if (hoursInput) {
                    const workHours = computeRDOTHrsFromStartToEnd();
                    hoursInput.value = workHours.toFixed(2);
                    console.log('Updated RDOT hours to:', workHours.toFixed(2));
                    
                    // Update MAX OT AVAILABLE display
                    document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                    
                    // Trigger input event to update any UI bindings
                    try { 
                        hoursInput.dispatchEvent(new Event('input', { bubbles: true })); 
                        // Also update the time picker if it exists
                        const maxOTHours = parseFloat(hoursInput.value) || 0;
                        initializeTimePicker(maxOTHours);
                    } catch(e) {
                        console.warn('Error updating hours input:', e);
                    }
                }
            }
        });
    }
    
    // Add event listener for End OT time changes (only for Restday OT)
    const endOTInput = document.getElementById('end_ot_time');
    if (endOTInput) {
        endOTInput.addEventListener('change', function() {
            const otTypeSelect = document.getElementById('ot_type');
            if (otTypeSelect && otTypeSelect.value === 'Restday OT') {
                console.log('End OT changed for Restday OT:', this.value);
                const hoursInput = document.getElementById('overtime_hours');
                if (hoursInput) {
                    const workHours = computeRDOTHrsFromStartToEnd();
                    hoursInput.value = workHours.toFixed(2);
                    console.log('Updated RDOT hours to:', workHours.toFixed(2));
                    
                    // Update MAX OT AVAILABLE display
                    document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                    
                    // Trigger input event to update any UI bindings
                    try { 
                        hoursInput.dispatchEvent(new Event('input', { bubbles: true })); 
                        // Also update the time picker if it exists
                        const maxOTHours = parseFloat(hoursInput.value) || 0;
                        initializeTimePicker(maxOTHours);
                    } catch(e) {
                        console.warn('Error updating hours input:', e);
                    }
                }
            }
        });
    }
    
    // Add event listeners for display Start OT and End OT fields as well
    const displayStartOT = document.getElementById('display_start_ot');
    const displayEndOT = document.getElementById('display_end_ot');
    
    if (displayStartOT) {
        displayStartOT.addEventListener('change', function() {
            const otTypeSelect = document.getElementById('ot_type');
            if (otTypeSelect && otTypeSelect.value === 'Restday OT') {
                // Sync with hidden field
                document.getElementById('start_ot_time').value = this.value;
                
                // Recalculate hours
                const hoursInput = document.getElementById('overtime_hours');
                if (hoursInput) {
                    const workHours = computeRDOTHrsFromStartToEnd();
                    hoursInput.value = workHours.toFixed(2);
                    console.log('Updated RDOT hours from display Start OT to:', workHours.toFixed(2));
                    
                    // Update MAX OT AVAILABLE display
                    document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                    
                    try { 
                        hoursInput.dispatchEvent(new Event('input', { bubbles: true })); 
                        const maxOTHours = parseFloat(hoursInput.value) || 0;
                        initializeTimePicker(maxOTHours);
                    } catch(e) {}
                }
            }
        });
    }
    
    if (displayEndOT) {
        displayEndOT.addEventListener('change', function() {
            const otTypeSelect = document.getElementById('ot_type');
            if (otTypeSelect && otTypeSelect.value === 'Restday OT') {
                // Sync with hidden field
                document.getElementById('end_ot_time').value = this.value;
                
                // Recalculate hours
                const hoursInput = document.getElementById('overtime_hours');
                if (hoursInput) {
                    const workHours = computeRDOTHrsFromStartToEnd();
                    hoursInput.value = workHours.toFixed(2);
                    console.log('Updated RDOT hours from display End OT to:', workHours.toFixed(2));
                    
                    // Update MAX OT AVAILABLE display
                    document.getElementById('display_max_ot_hours').value = formatDuration(workHours);
                    
                    try { 
                        hoursInput.dispatchEvent(new Event('input', { bubbles: true })); 
                        const maxOTHours = parseFloat(hoursInput.value) || 0;
                        initializeTimePicker(maxOTHours);
                    } catch(e) {}
                }
            }
        });
    }
});

function submitOvertimeForm(form) {
    // Get form elements more reliably with explicit checks
    const timeLogId = form.querySelector('#selected_time_log_id')?.value || '';
    const otType = form.querySelector('#ot_type')?.value || '';
    const reasonElement = form.querySelector('#reason');
    const overtimeHours = form.querySelector('#overtime_hours')?.value || '';
    const timeIn = form.querySelector('#selected_time_in')?.value || '';
    const timeOut = form.querySelector('#selected_time_out')?.value || '';
    const attachment = form.querySelector('#attachment')?.files[0];
    
    // Check if this is a rest day (from form dataset)
    const overtimeForm = document.getElementById('overtimeForm');
    const isRestDay = overtimeForm?.dataset.isRestDay === '1';
    
    // Validate RDOT can only be filed on rest days
    if (otType === 'Restday OT' && !isRestDay) {
        alert('⚠️ Restday OT can only be filed on days marked as "Rest Day" in your schedule.\\n\\nThis date is not a rest day. Please select "Regular OT" instead.');
        form.querySelector('#ot_type')?.focus();
        return;
    }
    
    // Debug all form elements
    console.log('All form inputs:', {
        timeLogElement: form.querySelector('#selected_time_log_id'),
        otTypeElement: form.querySelector('#ot_type'),
        reasonElement: reasonElement,
        hoursElement: form.querySelector('#overtime_hours'),
        timeInElement: form.querySelector('#selected_time_in'),
        timeOutElement: form.querySelector('#selected_time_out'),
        attachmentElement: form.querySelector('#attachment')
    });
    
    // Check if reason element exists and get its value
    let reason = '';
    if (reasonElement) {
        reason = reasonElement.value.trim();
        console.log('Reason element found:', {
            element: reasonElement,
            value: reasonElement.value,
            trimmed: reason,
            focused: document.activeElement === reasonElement,
            placeholder: reasonElement.placeholder,
            required: reasonElement.required
        });
    } else {
        console.error('Reason element not found!');
        alert('Error: Reason input field not found. Please refresh the page and try again.');
        return;
    }
    
    // Additional debugging for reason field
    const reasonById = document.getElementById('reason');
    const reasonByName = document.getElementsByName('reason')[0];
    const allTextareas = document.querySelectorAll('textarea');
    
    console.log('Comprehensive reason field debugging:', {
        byId: reasonById ? { value: reasonById.value, id: reasonById.id } : 'NOT FOUND',
        byName: reasonByName ? { value: reasonByName.value, name: reasonByName.name } : 'NOT FOUND',
        allTextareas: Array.from(allTextareas).map(ta => ({ 
            id: ta.id, 
            name: ta.name, 
            value: ta.value,
            placeholder: ta.placeholder 
        })),
        modalVisible: !document.getElementById('overtimeModal').classList.contains('hidden')
    });
    
    // Validation with detailed error messages
    if (!timeLogId) {
        alert('Error: Time log ID is missing. Please close the modal and try again.');
        return;
    }
    
    if (!otType) {
        alert('Error: Please select an overtime type.');
        form.querySelector('#ot_type')?.focus();
        return;
    }
    
    if (!reason || reason.length === 0) {
        alert('Error: Please provide a reason for overtime.\n\nCurrent status:\n- Field found: ' + (reasonElement ? 'Yes' : 'No') + '\n- Field value: "' + reason + '"\n- Field length: ' + reason.length);
        if (reasonElement) {
            reasonElement.focus();
            reasonElement.style.border = '2px solid red';
            // Try to force focus and highlight
            reasonElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }
    
    if (!attachment) {
        alert('Error: Please upload a supporting document.');
        form.querySelector('#attachment')?.focus();
        return;
    }
    
    // Validate that requested OT hours don't exceed available OT time
    const maxOtHours = parseFloat(form.querySelector('#max_ot_hours')?.value) || 0;
    const requestedHours = parseFloat(overtimeHours) || 0;
    
    // For Regular OT, check if requested hours exceed max available
    if (otType !== 'Restday OT' && requestedHours > maxOtHours) {
        const maxMinutes = Math.round(maxOtHours * 60);
        alert('⚠️ Cannot submit OT request.\n\nYou have requested more overtime hours than available.\n\n' +
              '• Maximum available: ' + maxMinutes + ' minutes (' + maxOtHours.toFixed(2) + ' hrs)\n' +
              '• You requested: ' + Math.round(requestedHours * 60) + ' minutes (' + requestedHours.toFixed(2) + ' hrs)\n\n' +
              'Please adjust your OT hours to be within the available time.');
        return;
    }
    
    // No minimum for Restday OT; keep regular OT validation
    if (otType !== 'Restday OT') {
        // Regular OT validation - needs to be past scheduled time
        if (!overtimeHours || parseFloat(overtimeHours) <= 0) {
            alert('Error: Invalid overtime hours: ' + overtimeHours);
            return;
        }
    }
    
    // Create FormData manually with additional validation
    const formData = new FormData();
    formData.append('time_log_id', timeLogId);
    formData.append('ot_type', otType);
    formData.append('reason', reason);
    
    const finalOvertimeHours = parseFloat(overtimeHours) || 0;
    formData.append('overtime_hours', finalOvertimeHours.toFixed(2));
    formData.append('time_in', timeIn);
    formData.append('time_out', timeOut);
    formData.append('attachment', attachment);
    
    // Final validation of FormData
    console.log('FormData validation:');
    let hasReason = false;
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: "${value}"`);
        if (key === 'reason') {
            hasReason = true;
            if (!value || value.trim().length === 0) {
                alert('Error: Reason field is empty in FormData. Please try typing your reason again.');
                return;
            }
        }
    }
    
    if (!hasReason) {
        alert('Error: Reason field not found in form data. Please refresh and try again.');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
    submitBtn.disabled = true;
    
    // Submit to dedicated processing file
    fetch('process_overtime_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            alert(data.message);
            closeOvertimeModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        // Remove error styling
        if (reasonElement) {
            reasonElement.style.border = '';
        }
    });
}

// Notification system
function showNotification(message, type = 'info') {
    // Create notification container if it doesn't exist
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-emerald-500' : 
                    type === 'error' ? 'bg-red-500' : 
                    type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    notification.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                               type === 'error' ? 'fa-times-circle' : 
                               type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} mr-3"></i>
                <span class="font-medium">${message}</span>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Initialize pagination event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing pagination...');
    
    // Attach pagination event listeners
    attachPaginationEventListeners();
    
    // Add event listener for go to page input
    const goToPageInput = document.getElementById('goToPage');
    if (goToPageInput) {
        goToPageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToPage();
            }
        });
    }
    
    console.log('Pagination initialization complete');
});

// Function to validate overtime hours in real-time
function validateOvertimeHours() {
    const otType = document.getElementById('ot_type').value;
    const hoursInput = document.getElementById('overtime_hours');
    const submitBtn = document.querySelector('#overtimeForm button[type="submit"]');
    
    if (!hoursInput || !submitBtn) return;
    
    const hours = parseFloat(hoursInput.value) || 0;
    let isValid = true;
    let message = '';
    
    if (otType === 'Restday OT') {
        // RDOT allows any hours > 0
        if (hours <= 0) {
            isValid = false;
            message = 'Rest day overtime hours must be greater than 0';
        }
    } else {
        // Regular OT must exceed 8 hours
        if (hours < 8) {
            isValid = false;
            message = 'Regular overtime must exceed 8 hours to be eligible';
        }
    }
    
    // Update submit button state
    submitBtn.disabled = !isValid;
    
    // Show/hide validation message
    let validationMsg = document.getElementById('otValidationMsg');
    if (!validationMsg) {
        validationMsg = document.createElement('div');
        validationMsg.id = 'otValidationMsg';
        validationMsg.className = 'text-sm mt-2';
        hoursInput.parentNode.appendChild(validationMsg);
    }
    
    if (!isValid) {
        validationMsg.className = 'text-sm mt-2 text-red-600';
        validationMsg.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>${message}`;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        validationMsg.className = 'text-sm mt-2 text-green-600';
        validationMsg.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Hours are valid for ${otType}`;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}
</script>

<style>
/* Monday.com Full-Width Overtime Management Style */

/* Remove margins and ensure full width - ONLY for overtime view */
.overtime-management-container {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
}

/* Full viewport usage */
.overtime-management-container > .w-full {
    width: 100% !important;
    padding: 0 !important;
}

/* Clean border-based design */
.overtime-management-container .border-gray-200 {
    border-color: #e5e7eb;
}

/* Smooth transitions */
.overtime-management-container * {
    transition-property: background-color, border-color, color, box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Table hover effects */
.overtime-management-container table tbody tr:hover {
    background-color: #f9fafb;
}

/* Button hover states */
.overtime-management-container button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Card hover effects */
.overtime-management-container .bg-white:hover {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* Clean rounded corners */
.overtime-management-container .rounded-2xl {
    border-radius: 0.75rem;
}

.overtime-management-container .rounded-xl {
    border-radius: 0.5rem;
}

.overtime-management-container .rounded-lg {
    border-radius: 0.375rem;
}

/* Enhanced focus states */
.overtime-management-container input:focus,
.overtime-management-container select:focus,
.overtime-management-container textarea:focus {
    ring-width: 2px;
    ring-color: rgb(16, 185, 129);
    border-color: rgb(16, 185, 129);
}
</style>