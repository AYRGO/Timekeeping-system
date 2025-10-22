<?php
// ============================================================================
// SIMPLIFIED SCHEDULE FUNCTIONS USING CACHE
// ============================================================================
// These functions use the pre-computed employee_daily_schedule_cache table
// for fast and simple schedule lookups
// ============================================================================

/**
 * Get schedule for a specific employee and date - CACHE VERSION
 * This is MUCH simpler and faster than the priority-based lookup
 */
function getScheduleFromCache($pdo, $employee_id, $date) {
    $stmt = $pdo->prepare("
        SELECT 
            schedule_date as date,
            employee_id,
            work_schedule_id,
            is_rest_day,
            is_holiday,
            schedule_name,
            time_in,
            time_out,
            holiday_name,
            source,
            source_id
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Map to expected format
        return [
            'date' => $result['date'],
            'employee_id' => $result['employee_id'],
            'actual_schedule' => $result['work_schedule_id'] ? [
                'id' => $result['work_schedule_id'],
                'name' => $result['schedule_name'],
                'time_in' => $result['time_in'],
                'time_out' => $result['time_out']
            ] : null,
            'is_rest_day' => $result['is_rest_day'],
            'is_holiday' => $result['is_holiday'],
            'holiday' => $result['is_holiday'] ? ['holiday_name' => $result['holiday_name']] : null,
            'source' => $result['source'],
            'schedule_color' => getScheduleColor($result['source'], $result['is_rest_day'], $result['is_holiday'])
        ];
    }
    
    // Fallback: no data in cache, return empty cell
    return [
        'date' => $date,
        'employee_id' => $employee_id,
        'actual_schedule' => null,
        'is_rest_day' => 1,
        'is_holiday' => 0,
        'holiday' => null,
        'source' => 'none',
        'schedule_color' => '#e2e8f0'
    ];
}

/**
 * Get color based on source
 */
function getScheduleColor($source, $is_rest_day, $is_holiday) {
    if ($is_holiday) {
        return '#f59e0b'; // Amber for holidays
    }
    
    switch ($source) {
        case 'approved_request':
            return '#10b981'; // Green for approved change requests (amber in UI)
        case 'admin_override':
            return '#8b5cf6'; // Purple for admin overrides
        case 'weekly_default':
            return $is_rest_day ? '#e2e8f0' : '#3b82f6'; // Light gray or blue
        case 'weekend':
            return '#e2e8f0'; // Light gray
        default:
            return '#9ca3af'; // Gray for no data
    }
}

/**
 * Get schedule for multiple dates at once (for calendar view)
 */
function getScheduleRangeFromCache($pdo, $employee_id, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT 
            schedule_date,
            work_schedule_id,
            is_rest_day,
            is_holiday,
            schedule_name,
            time_in,
            time_out,
            holiday_name,
            source
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? 
          AND schedule_date BETWEEN ? AND ?
        ORDER BY schedule_date
    ");
    $stmt->execute([$employee_id, $start_date, $end_date]);
    
    $schedules = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schedules[$row['schedule_date']] = [
            'actual_schedule' => $row['work_schedule_id'] ? [
                'id' => $row['work_schedule_id'],
                'name' => $row['schedule_name'],
                'time_in' => $row['time_in'],
                'time_out' => $row['time_out']
            ] : null,
            'is_rest_day' => $row['is_rest_day'],
            'is_holiday' => $row['is_holiday'],
            'holiday' => $row['is_holiday'] ? ['holiday_name' => $row['holiday_name']] : null,
            'source' => $row['source'],
            'schedule_color' => getScheduleColor($row['source'], $row['is_rest_day'], $row['is_holiday'])
        ];
    }
    
    return $schedules;
}

/**
 * Check if cache needs refresh for employee
 */
function ensureCacheExists($pdo, $employee_id, $start_date, $end_date) {
    // Check if we have data
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM employee_daily_schedule_cache
        WHERE employee_id = ?
          AND schedule_date BETWEEN ? AND ?
    ");
    $stmt->execute([$employee_id, $start_date, $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $daysExpected = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
    
    // If less than 50% of days have data, refresh cache
    if ($result['count'] < ($daysExpected * 0.5)) {
        try {
            $pdo->query("CALL populate_schedule_cache($employee_id, '$start_date', '$end_date')");
            return true;
        } catch (Exception $e) {
            error_log("Cache refresh failed: " . $e->getMessage());
            return false;
        }
    }
    
    return true;
}

// ============================================================================
// USAGE EXAMPLES
// ============================================================================
/*

// Example 1: Get today's schedule for employee
$schedule = getScheduleFromCache($pdo, $employee_id, date('Y-m-d'));
if ($schedule['is_rest_day']) {
    echo "Rest Day";
} else {
    echo $schedule['actual_schedule']['name'];
    echo " " . $schedule['actual_schedule']['time_in'] . " - " . $schedule['actual_schedule']['time_out'];
}

// Example 2: Get month of schedules
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
ensureCacheExists($pdo, $employee_id, $month_start, $month_end); // Make sure cache exists
$schedules = getScheduleRangeFromCache($pdo, $employee_id, $month_start, $month_end);

foreach ($schedules as $date => $schedule) {
    echo "$date: ";
    if ($schedule['is_holiday']) {
        echo "HOLIDAY - " . $schedule['holiday']['holiday_name'];
    } elseif ($schedule['is_rest_day']) {
        echo "REST DAY";
    } else {
        echo $schedule['actual_schedule']['name'];
    }
    echo "\n";
}

// Example 3: Simple calendar query
SELECT 
    schedule_date,
    DAYNAME(schedule_date) as day,
    CASE 
        WHEN is_holiday = 1 THEN CONCAT('HOLIDAY: ', holiday_name)
        WHEN is_rest_day = 1 THEN 'OFF'
        ELSE CONCAT(schedule_name, ' (', TIME_FORMAT(time_in, '%h:%i %p'), ' - ', TIME_FORMAT(time_out, '%h:%i %p'), ')')
    END as schedule_display
FROM employee_daily_schedule_cache
WHERE employee_id = 1
  AND schedule_date BETWEEN '2025-10-01' AND '2025-10-31'
ORDER BY schedule_date;

*/
?>
