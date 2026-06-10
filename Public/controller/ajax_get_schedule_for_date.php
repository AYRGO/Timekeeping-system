<?php
// ajax_get_schedule_for_date.php
// Fetch employee's current schedule for a specific date

session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

// Get parameters
$date = $_POST['date'] ?? '';
// Try multiple session keys for employee_id
$employee_id = $_POST['employee_id'] ?? $_SESSION['employee']['id'] ?? $_SESSION['user_id'] ?? null;

// Check if we have an employee_id
if (!$employee_id) {
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated - no employee_id found in session',
        'debug' => [
            'session_employee_id' => $_SESSION['employee']['id'] ?? 'not set',
            'session_user_id' => $_SESSION['user_id'] ?? 'not set',
            'post_employee_id' => $_POST['employee_id'] ?? 'not set',
            'session_id' => session_id(),
            'session_data' => array_keys($_SESSION)
        ]
    ]);
    exit;
}

if (empty($date)) {
    echo json_encode(['success' => false, 'error' => 'Date is required']);
    exit;
}

try {
    // Query the cache first (fastest and most accurate)
    $stmt = $pdo->prepare("
        SELECT 
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
        // Format the response
        $response = [
            'success' => true,
            'has_schedule' => true,
            'is_rest_day' => (bool)$cache['is_rest_day'],
            'is_holiday' => (bool)$cache['is_holiday'],
            'source' => $cache['source']
        ];
        
        if ($cache['is_holiday']) {
            $response['display'] = '🎉 Holiday: ' . $cache['holiday_name'];
        } elseif ($cache['is_rest_day']) {
            $response['display'] = '🛌 Rest Day / Day Off';
        } elseif ($cache['work_schedule_id']) {
            $time_in = date('g:i A', strtotime($cache['time_in']));
            $time_out = date('g:i A', strtotime($cache['time_out']));
            
            if (!empty($cache['schedule_name'])) {
                $response['display'] = $cache['schedule_name'] . ' (' . $time_in . ' - ' . $time_out . ')';
            } else {
                $response['display'] = $time_in . ' - ' . $time_out;
            }
            
            $response['schedule_id'] = $cache['work_schedule_id'];
            $response['schedule_name'] = $cache['schedule_name'];
            $response['time_in'] = $cache['time_in'];
            $response['time_out'] = $cache['time_out'];
        } else {
            $response['display'] = 'No scheduled work';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Fallback: Check employee_default_schedules
    $dayOfWeek = date('w', strtotime($date));
    $stmt = $pdo->prepare("
        SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
        FROM employee_default_schedules edd
        LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
        WHERE edd.employee_id = ?
          AND edd.day_of_week = ?
          AND edd.effective_from <= ?
          AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
        ORDER BY edd.effective_from DESC, edd.id DESC
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $dayOfWeek, $date, $date]);
    $default = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($default) {
        $response = [
            'success' => true,
            'has_schedule' => true,
            'is_rest_day' => (bool)$default['is_rest_day'],
            'is_holiday' => false,
            'source' => 'weekly_default'
        ];
        
        if ($default['is_rest_day']) {
            $response['display'] = '🛌 Rest Day / Day Off';
        } elseif ($default['work_schedule_id']) {
            $time_in = date('g:i A', strtotime($default['time_in']));
            $time_out = date('g:i A', strtotime($default['time_out']));
            
            if (!empty($default['name'])) {
                $response['display'] = $default['name'] . ' (' . $time_in . ' - ' . $time_out . ')';
            } else {
                $response['display'] = $time_in . ' - ' . $time_out;
            }
            
            $response['schedule_id'] = $default['work_schedule_id'];
            $response['schedule_name'] = $default['name'];
            $response['time_in'] = $default['time_in'];
            $response['time_out'] = $default['time_out'];
        } else {
            $response['display'] = 'No scheduled work';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Final fallback: No schedule found
    echo json_encode([
        'success' => true,
        'has_schedule' => false,
        'is_rest_day' => ($dayOfWeek == 0 || $dayOfWeek == 6), // Weekend
        'is_holiday' => false,
        'display' => ($dayOfWeek == 0 || $dayOfWeek == 6) ? 'Weekend' : 'No schedule set',
        'source' => 'none'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
