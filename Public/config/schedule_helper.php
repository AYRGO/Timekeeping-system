<?php
/**
 * Schedule Helper Functions
 * 
 * This file provides helper functions to work with work schedules
 * from the database, eliminating the need for hardcoded schedule arrays.
 */

/**
 * Get all schedules from database as an associative array
 * Format: [id => ['in' => 'HH:MM:SS', 'out' => 'HH:MM:SS', 'name' => 'Name']]
 * 
 * @param PDO $pdo Database connection
 * @param string $format '24h' for 24-hour format, '12h' for AM/PM format
 * @return array
 */
function getAllSchedules($pdo, $format = '24h') {
    $stmt = $pdo->prepare("SELECT id, name, time_in, time_out FROM work_schedules ORDER BY id ASC");
    $stmt->execute();
    $schedules = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $timeIn = $row['time_in'];
        $timeOut = $row['time_out'];
        
        // Convert to 12-hour format if requested
        if ($format === '12h') {
            $timeIn = date('h:i A', strtotime($timeIn));
            $timeOut = date('h:i A', strtotime($timeOut));
        }
        
        $schedules[$row['id']] = [
            'in' => $timeIn,
            'out' => $timeOut,
            'name' => $row['name']
        ];
    }
    
    return $schedules;
}

/**
 * Get schedule by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $scheduleId Schedule ID
 * @param string $format '24h' for 24-hour format, '12h' for AM/PM format
 * @return array|null
 */
function getScheduleById($pdo, $scheduleId, $format = '24h') {
    $stmt = $pdo->prepare("SELECT id, name, time_in, time_out FROM work_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        return null;
    }
    
    $timeIn = $row['time_in'];
    $timeOut = $row['time_out'];
    
    // Convert to 12-hour format if requested
    if ($format === '12h') {
        $timeIn = date('h:i A', strtotime($timeIn));
        $timeOut = date('h:i A', strtotime($timeOut));
    }
    
    return [
        'id' => $row['id'],
        'in' => $timeIn,
        'out' => $timeOut,
        'name' => $row['name']
    ];
}

/**
 * Get schedule times in both formats
 * 
 * @param PDO $pdo Database connection
 * @param int $scheduleId Schedule ID
 * @return array ['24h' => [...], '12h' => [...]]
 */
function getScheduleTimes($pdo, $scheduleId) {
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        return [
            '24h' => ['in' => '07:00:00', 'out' => '16:00:00'],
            '12h' => ['in' => '07:00 AM', 'out' => '04:00 PM']
        ];
    }
    
    return [
        '24h' => [
            'in' => $row['time_in'],
            'out' => $row['time_out']
        ],
        '12h' => [
            'in' => date('h:i A', strtotime($row['time_in'])),
            'out' => date('h:i A', strtotime($row['time_out']))
        ]
    ];
}

/**
 * Calculate working hours for a schedule
 * 
 * @param PDO $pdo Database connection
 * @param int $scheduleId Schedule ID
 * @return float Hours as decimal (e.g., 8.5 for 8 hours 30 minutes)
 */
function getScheduleDuration($pdo, $scheduleId) {
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        return 8.0; // Default 8 hours
    }
    
    $timeIn = new DateTime($row['time_in']);
    $timeOut = new DateTime($row['time_out']);
    
    // Handle overnight shifts
    if ($timeOut < $timeIn) {
        $timeOut->modify('+1 day');
    }
    
    $interval = $timeIn->diff($timeOut);
    $hours = $interval->h + ($interval->i / 60);
    
    return round($hours, 2);
}

/**
 * Check if a schedule exists
 * 
 * @param PDO $pdo Database connection
 * @param int $scheduleId Schedule ID
 * @return bool
 */
function scheduleExists($pdo, $scheduleId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM work_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get count of employees using a schedule
 * 
 * @param PDO $pdo Database connection
 * @param int $scheduleId Schedule ID
 * @return int
 */
function getScheduleUsageCount($pdo, $scheduleId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE official_sched = ?");
    $stmt->execute([$scheduleId]);
    return (int)$stmt->fetchColumn();
}
