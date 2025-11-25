<?php
require 'Public/config/db.php';

// Copy the exact function from generate_payroll_report.php
function getScheduleForDate($pdo, $employee_id, $date) {
    $stmt = $pdo->prepare("
        SELECT 
            schedule_date,
            employee_id,
            work_schedule_id,
            is_rest_day,
            is_holiday,
            schedule_name,
            time_in,
            time_out,
            holiday_name,
            source
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $cache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cache) {
        return [
            'is_rest_day' => $cache['is_rest_day'],
            'is_holiday' => $cache['is_holiday'],
            'schedule_name' => $cache['schedule_name'],
            'time_in' => $cache['time_in'],
            'time_out' => $cache['time_out'],
            'holiday_name' => $cache['holiday_name'] ?? null,
            'source' => $cache['source']
        ];
    }
    
    $dayOfWeek = date('w', strtotime($date));
    $weeklyStmt = $pdo->prepare("
        SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
        FROM employee_default_schedules edd
        LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
        WHERE edd.employee_id = ? 
          AND edd.day_of_week = ? 
          AND edd.effective_from <= ? 
          AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
        LIMIT 1
    ");
    $weeklyStmt->execute([$employee_id, $dayOfWeek, $date, $date]);
    $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weekly) {
        if ($weekly['is_rest_day']) {
            return [
                'is_rest_day' => 1,
                'is_holiday' => 0,
                'schedule_name' => 'OFF',
                'time_in' => null,
                'time_out' => null,
                'source' => 'weekly_default'
            ];
        } elseif ($weekly['work_schedule_id']) {
            return [
                'is_rest_day' => 0,
                'is_holiday' => 0,
                'schedule_name' => $weekly['name'],
                'time_in' => $weekly['time_in'],
                'time_out' => $weekly['time_out'],
                'source' => 'weekly_default'
            ];
        }
    }
    
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return [
            'is_rest_day' => 1,
            'is_holiday' => 0,
            'schedule_name' => 'OFF',
            'time_in' => null,
            'time_out' => null,
            'source' => 'weekend'
        ];
    }
    
    return [
        'is_rest_day' => 0,
        'is_holiday' => 0,
        'schedule_name' => 'Day shift',
        'time_in' => '07:00:00',
        'time_out' => '16:00:00',
        'source' => 'default'
    ];
}

function getWeeklyScheduleSummary($pdo, $employee_id, $startDate) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $dayAbbrev = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    $weekSchedule = [];
    $checkDate = new DateTime($startDate);
    
    $dayOfWeek = $checkDate->format('w');
    if ($dayOfWeek != 1) {
        $daysToSubtract = ($dayOfWeek == 0) ? 6 : ($dayOfWeek - 1);
        $checkDate->modify("-{$daysToSubtract} days");
    }
    
    echo "Starting week from: " . $checkDate->format('Y-m-d') . "\n\n";
    
    for ($i = 0; $i < 7; $i++) {
        $date = $checkDate->format('Y-m-d');
        $schedInfo = getScheduleForDate($pdo, $employee_id, $date);
        
        $dow = $checkDate->format('w');
        $weekSchedule[$dow] = $schedInfo;
        
        echo "{$dayAbbrev[$dow]} {$date}: ";
        if ($schedInfo['is_rest_day']) {
            echo "OFF";
        } else {
            echo "{$schedInfo['time_in']}-{$schedInfo['time_out']}";
        }
        echo " (source: {$schedInfo['source']})\n";
        
        $checkDate->modify('+1 day');
    }
    
    // Group consecutive days with same schedule
    $groups = [];
    $currentGroup = null;
    $order = [1, 2, 3, 4, 5, 6, 0];
    
    foreach ($order as $dow) {
        $sched = $weekSchedule[$dow];
        
        if ($sched['is_rest_day']) {
            $schedKey = 'OFF';
        } elseif ($sched['is_holiday']) {
            $schedKey = 'HOLIDAY';
        } else {
            $schedKey = $sched['time_in'] . '-' . $sched['time_out'];
        }
        
        if ($currentGroup === null || $currentGroup['key'] !== $schedKey) {
            if ($currentGroup !== null) {
                $groups[] = $currentGroup;
            }
            $currentGroup = [
                'key' => $schedKey,
                'start_dow' => $dow,
                'end_dow' => $dow,
                'schedule' => $sched
            ];
        } else {
            $currentGroup['end_dow'] = $dow;
        }
    }
    
    if ($currentGroup !== null) {
        $groups[] = $currentGroup;
    }
    
    $parts = [];
    foreach ($groups as $group) {
        $startDow = $group['start_dow'];
        $endDow = $group['end_dow'];
        $sched = $group['schedule'];
        
        if ($startDow === $endDow) {
            $dayRange = $dayAbbrev[$startDow];
        } else {
            $dayRange = $dayAbbrev[$startDow] . '-' . $dayAbbrev[$endDow];
        }
        
        if ($sched['is_rest_day']) {
            $parts[] = $dayRange . '; OFF';
        } elseif ($sched['is_holiday']) {
            $parts[] = $dayRange . '; HOLIDAY';
        } else {
            $timeIn = date('ga', strtotime($sched['time_in']));
            $timeOut = date('ga', strtotime($sched['time_out']));
            $parts[] = $dayRange . '; ' . $timeIn . '-' . $timeOut;
        }
    }
    
    return implode(' | ', $parts);
}

echo "=== TESTING getWeeklyScheduleSummary for Employee 1006 ===\n\n";
echo "Start date: 2025-10-01\n\n";

$result = getWeeklyScheduleSummary($pdo, 1006, '2025-10-01');

echo "\n=== RESULT ===\n";
echo $result . "\n";
