<?php
/**
 * CACHE SCHEDULE VERIFICATION TOOL
 * 
 * This script checks what schedules are actually stored in employee_daily_schedule_cache
 * for test employees 1003-1007 for October 2025
 */

require 'Public/config/db.php';

echo "<h1>Employee Schedule Cache Verification</h1>";
echo "<p>Checking cache for employees 1003-1007 for October 1-15, 2025</p>";
echo "<hr>";

$employees = [1003, 1004, 1005, 1006, 1007];

foreach ($employees as $emp_id) {
    echo "<h2>Employee ID: $emp_id</h2>";
    
    // Check if employee exists
    $empCheck = $pdo->prepare("SELECT id, fname, lname FROM employees WHERE id = ?");
    $empCheck->execute([$emp_id]);
    $employee = $empCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo "<p style='color: red;'>❌ Employee not found in database!</p>";
        continue;
    }
    
    echo "<p>✅ Employee: {$employee['fname']} {$employee['lname']}</p>";
    
    // Check cache entries for October 1-15, 2025
    $cacheCheck = $pdo->prepare("
        SELECT 
            schedule_date,
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
        WHERE employee_id = ?
        AND schedule_date BETWEEN '2025-10-01' AND '2025-10-15'
        ORDER BY schedule_date ASC
    ");
    $cacheCheck->execute([$emp_id]);
    $cacheEntries = $cacheCheck->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cacheEntries)) {
        echo "<p style='color: orange;'>⚠️ NO CACHE ENTRIES FOUND for October 1-15, 2025</p>";
        echo "<p>This means the payroll report will fall back to employee_default_schedules</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($cacheEntries) . " cache entries</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>Date</th>
                <th>Day</th>
                <th>Schedule</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Rest Day?</th>
                <th>Holiday?</th>
                <th>Source</th>
              </tr>";
        
        foreach ($cacheEntries as $entry) {
            $dayName = date('D', strtotime($entry['schedule_date']));
            $dateDisplay = date('M d', strtotime($entry['schedule_date']));
            
            $scheduleDisplay = '';
            if ($entry['is_holiday']) {
                $scheduleDisplay = "<span style='color: orange;'>HOLIDAY: {$entry['holiday_name']}</span>";
            } elseif ($entry['is_rest_day']) {
                $scheduleDisplay = "<span style='color: gray;'>REST DAY / OFF</span>";
            } elseif ($entry['schedule_name']) {
                $scheduleDisplay = htmlspecialchars($entry['schedule_name']);
            } else {
                $scheduleDisplay = "<span style='color: red;'>No Schedule</span>";
            }
            
            $timeIn = $entry['time_in'] ? date('g:i A', strtotime($entry['time_in'])) : '-';
            $timeOut = $entry['time_out'] ? date('g:i A', strtotime($entry['time_out'])) : '-';
            
            $sourceColor = '';
            switch($entry['source']) {
                case 'admin_override': $sourceColor = 'purple'; break;
                case 'approved_request': $sourceColor = 'green'; break;
                case 'weekly_default': $sourceColor = 'blue'; break;
                case 'holiday': $sourceColor = 'orange'; break;
                default: $sourceColor = 'gray';
            }
            
            echo "<tr>
                    <td>{$dateDisplay}</td>
                    <td><strong>{$dayName}</strong></td>
                    <td>{$scheduleDisplay}</td>
                    <td>{$timeIn}</td>
                    <td>{$timeOut}</td>
                    <td>" . ($entry['is_rest_day'] ? '✅' : '❌') . "</td>
                    <td>" . ($entry['is_holiday'] ? '✅' : '❌') . "</td>
                    <td><span style='color: {$sourceColor}; font-weight: bold;'>{$entry['source']}</span></td>
                  </tr>";
        }
        echo "</table>";
    }
    
    // Check employee_default_schedules as fallback
    echo "<h3>Default Weekly Schedule (Fallback)</h3>";
    $defaultCheck = $pdo->prepare("
        SELECT 
            edd.day_of_week,
            edd.work_schedule_id,
            edd.is_rest_day,
            ws.name as schedule_name,
            ws.time_in,
            ws.time_out,
            edd.effective_from,
            edd.effective_until
        FROM employee_default_schedules edd
        LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
        WHERE edd.employee_id = ?
        ORDER BY edd.day_of_week ASC
    ");
    $defaultCheck->execute([$emp_id]);
    $defaults = $defaultCheck->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($defaults)) {
        echo "<p style='color: red;'>❌ NO DEFAULT SCHEDULE SET!</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>Day of Week</th>
                <th>Schedule</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Rest Day?</th>
                <th>Effective</th>
              </tr>";
        
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($defaults as $def) {
            $dayName = $dayNames[$def['day_of_week']];
            $schedName = $def['is_rest_day'] ? 'REST DAY' : ($def['schedule_name'] ?? 'No Schedule');
            $timeIn = $def['time_in'] ? date('g:i A', strtotime($def['time_in'])) : '-';
            $timeOut = $def['time_out'] ? date('g:i A', strtotime($def['time_out'])) : '-';
            $effective = $def['effective_from'] . ($def['effective_until'] ? ' to ' . $def['effective_until'] : ' (ongoing)');
            
            echo "<tr>
                    <td><strong>{$dayName}</strong></td>
                    <td>{$schedName}</td>
                    <td>{$timeIn}</td>
                    <td>{$timeOut}</td>
                    <td>" . ($def['is_rest_day'] ? '✅' : '❌') . "</td>
                    <td style='font-size: 0.8em;'>{$effective}</td>
                  </tr>";
        }
        echo "</table>";
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>What this means:</strong></p>";
echo "<ul>";
echo "<li>If cache entries exist for October 1-15, the payroll report will use those</li>";
echo "<li>If cache is empty, it falls back to employee_default_schedules</li>";
echo "<li>Admin overrides (purple) take priority over everything</li>";
echo "<li>Approved requests (green) take priority over defaults</li>";
echo "<li>Weekly defaults (blue) are the standard schedule</li>";
echo "</ul>";

echo "<h3>Actions:</h3>";
echo "<p>If the cache is empty or wrong:</p>";
echo "<ol>";
echo "<li>Run <a href='run_cache_setup.php'>run_cache_setup.php</a> to rebuild the entire cache</li>";
echo "<li>Or check if approved schedule requests need processing</li>";
echo "<li>Or verify admin overrides are correctly applied</li>";
echo "</ol>";
?>
