<?php
// Process approved monthly schedule requests
// This applies the approved weekly schedule as the employee's default from the approval start date onward.
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentEmployeeId = (int)$_SESSION['employee']['id'];
session_write_close();

try {
    // Process only the logged-in employee's approved requests.
    $stmt = $pdo->prepare("
        SELECT * FROM month_weekly_schedule 
        WHERE employee_id = ? AND status = 'approved' AND processed_at IS NULL
        ORDER BY created_at ASC
    ");
    $stmt->execute([$currentEmployeeId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $processedCount = 0;
    $errors = [];

    $workScheduleMap = [];
    foreach ($pdo->query("SELECT id, name, time_in, time_out FROM work_schedules")->fetchAll(PDO::FETCH_ASSOC) as $scheduleRow) {
        $workScheduleMap[(int)$scheduleRow['id']] = $scheduleRow;
    }
    
    foreach ($requests as $request) {
        try {
            $employee_id = $request['employee_id'];
            $year = $request['year'];
            $month = $request['month'];
            
            // Get current date
            $today = date('Y-m-d');
            $todayTimestamp = strtotime($today);
            
            // Calculate date range: FROM TODAY (or start of month if future) TO END OF MONTH
            $firstDayOfMonth = date('Y-m-d', strtotime("$year-$month-01"));
            $lastDayOfMonth = date('Y-m-t', strtotime("$year-$month-01"));
            
            // Determine start date: today if in current month, otherwise first day of month
            if (strtotime($firstDayOfMonth) > $todayTimestamp) {
                // Future month - start from first day
                $startDate = $firstDayOfMonth;
            } elseif (strtotime($lastDayOfMonth) < $todayTimestamp) {
                // Past month - skip it
                continue;
            } else {
                // Current month - start from today
                $startDate = $today;
            }
            
            $endDate = $lastDayOfMonth;
            
            // Build weekly schedule mapping
            $weeklySchedule = [
                0 => [ // Sunday
                    'schedule_id' => $request['sunday_schedule_id'],
                    'is_rest_day' => $request['sunday_is_rest_day']
                ],
                1 => [ // Monday
                    'schedule_id' => $request['monday_schedule_id'],
                    'is_rest_day' => $request['monday_is_rest_day']
                ],
                2 => [ // Tuesday
                    'schedule_id' => $request['tuesday_schedule_id'],
                    'is_rest_day' => $request['tuesday_is_rest_day']
                ],
                3 => [ // Wednesday
                    'schedule_id' => $request['wednesday_schedule_id'],
                    'is_rest_day' => $request['wednesday_is_rest_day']
                ],
                4 => [ // Thursday
                    'schedule_id' => $request['thursday_schedule_id'],
                    'is_rest_day' => $request['thursday_is_rest_day']
                ],
                5 => [ // Friday
                    'schedule_id' => $request['friday_schedule_id'],
                    'is_rest_day' => $request['friday_is_rest_day']
                ],
                6 => [ // Saturday
                    'schedule_id' => $request['saturday_schedule_id'],
                    'is_rest_day' => $request['saturday_is_rest_day']
                ]
            ];

            $pdo->beginTransaction();

            $nextScheduleStmt = $pdo->prepare("
                SELECT MIN(effective_from)
                FROM employee_default_schedules
                WHERE employee_id = ?
                  AND effective_from > ?
            ");
            $nextScheduleStmt->execute([$employee_id, $startDate]);
            $nextEffectiveFrom = $nextScheduleStmt->fetchColumn();
            $newEffectiveUntil = $nextEffectiveFrom
                ? date('Y-m-d', strtotime($nextEffectiveFrom . ' -1 day'))
                : null;

            $previousEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
            $endPreviousStmt = $pdo->prepare("
                UPDATE employee_default_schedules
                SET effective_until = ?
                WHERE employee_id = ?
                  AND effective_from < ?
                  AND (effective_until IS NULL OR effective_until >= ?)
            ");
            $endPreviousStmt->execute([$previousEndDate, $employee_id, $startDate, $startDate]);

            $deleteSameStartStmt = $pdo->prepare("
                DELETE FROM employee_default_schedules
                WHERE employee_id = ?
                  AND effective_from = ?
            ");
            $deleteSameStartStmt->execute([$employee_id, $startDate]);

            $insertDefaultStmt = $pdo->prepare("
                INSERT INTO employee_default_schedules
                (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, effective_until, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            foreach ($weeklySchedule as $dayOfWeek => $daySchedule) {
                $insertDefaultStmt->execute([
                    $employee_id,
                    $dayOfWeek,
                    $daySchedule['schedule_id'],
                    $daySchedule['is_rest_day'],
                    $startDate,
                    $newEffectiveUntil
                ]);
            }

            $clearFutureDefaultsStmt = $pdo->prepare("
                DELETE FROM employee_daily_schedule_cache
                WHERE employee_id = ?
                  AND schedule_date >= ?
                  AND (
                      source IN ('default', 'weekly_default', 'approved_monthly_request')
                      OR source LIKE '%|weekly_default'
                  )
            ");
            $clearFutureDefaultsStmt->execute([$employee_id, $startDate]);
            
            // Loop through each date from startDate to endDate
            $currentDate = strtotime($startDate);
            $endDateTimestamp = strtotime($endDate);
            
            while ($currentDate <= $endDateTimestamp) {
                $dateStr = date('Y-m-d', $currentDate);
                $dayOfWeek = (int)date('w', $currentDate); // 0=Sunday, 6=Saturday
                
                // Get schedule for this day of week
                $daySchedule = $weeklySchedule[$dayOfWeek];
                
                // Only process if a schedule was set for this day
                if ($daySchedule['schedule_id'] || $daySchedule['is_rest_day']) {
                    // Insert or update in employee_daily_schedule_cache
                    $insertStmt = $pdo->prepare("
                        INSERT INTO employee_daily_schedule_cache (
                            employee_id, schedule_date, work_schedule_id, is_rest_day, 
                            is_holiday, schedule_name, time_in, time_out, 
                            holiday_name, source
                        ) VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'approved_monthly_request')
                        ON DUPLICATE KEY UPDATE
                            work_schedule_id = VALUES(work_schedule_id),
                            is_rest_day = VALUES(is_rest_day),
                            schedule_name = VALUES(schedule_name),
                            time_in = VALUES(time_in),
                            time_out = VALUES(time_out),
                            source = 'approved_monthly_request'
                    ");
                    
                    if ($daySchedule['is_rest_day']) {
                        // Rest day
                        $insertStmt->execute([
                            $employee_id,
                            $dateStr,
                            null,
                            1,
                            'Rest Day',
                            null,
                            null
                        ]);
                    } else {
                        // Work schedule
                        $schedInfo = $workScheduleMap[(int)$daySchedule['schedule_id']] ?? null;
                        
                        if ($schedInfo) {
                            $insertStmt->execute([
                                $employee_id,
                                $dateStr,
                                $daySchedule['schedule_id'],
                                0,
                                $schedInfo['name'],
                                $schedInfo['time_in'],
                                $schedInfo['time_out']
                            ]);
                        }
                    }
                }
                
                // Move to next day
                $currentDate = strtotime('+1 day', $currentDate);
            }
            
            // Mark request as processed
            $updateStmt = $pdo->prepare("
                UPDATE month_weekly_schedule 
                SET processed_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$request['id']]);

            $pdo->commit();
            
            $processedCount++;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Error processing request ID {$request['id']}: " . $e->getMessage();
            error_log("Monthly schedule processing error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'processed' => $processedCount,
        'total' => count($requests),
        'message' => "Processed $processedCount monthly schedule request(s)",
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in monthly schedule processor: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
