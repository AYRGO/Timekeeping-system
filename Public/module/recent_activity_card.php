<?php
// Prevent caching to ensure JSON fix is applied
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$filterDate = $_GET['activityDate'] ?? null;

// First, remove any potential duplicates based on message content and date
$unique_notifications = [];
$seen_activities = [];

foreach ($notifications as $notification) {
    $activity_key = md5($notification['message'] . $notification['created_at']);
    if (!in_array($activity_key, $seen_activities)) {
        $seen_activities[] = $activity_key;
        $unique_notifications[] = $notification;
    }
}

$filteredActivities = array_filter($unique_notifications, function ($activity) use ($filterDate) {
    if (!$filterDate) return true;
    return date('Y-m-d', strtotime($activity['created_at'])) === $filterDate;
});

$recentActivities = array_slice($filteredActivities, 0, 10);

// Helper function to get ACTUAL current schedule from calendar (matches schedule_content.php logic)
if (!function_exists('getActualCurrentScheduleFromCalendar')) {
    function getActualCurrentScheduleFromCalendar($pdo, $employee_id, $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        // PRIORITY 1: Check employee_daily_schedule_cache (what the calendar actually displays)
        try {
        $stmt = $pdo->prepare("
            SELECT work_schedule_id, is_rest_day, schedule_name, time_in, time_out
            FROM employee_daily_schedule_cache 
            WHERE employee_id = ? AND schedule_date = ? 
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $date]);
        $cachedSchedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cachedSchedule) {
            if ($cachedSchedule['is_rest_day']) {
                return ['is_rest_day' => true];
            }
            if ($cachedSchedule['time_in'] && $cachedSchedule['time_out']) {
                return [
                    'time_in' => date('g:i A', strtotime($cachedSchedule['time_in'])),
                    'time_out' => date('g:i A', strtotime($cachedSchedule['time_out'])),
                    'is_rest_day' => false
                ];
            }
        }
        
        // FALLBACK: Check employee_default_schedules (weekly default)
        $dayOfWeek = date('w', strtotime($date));
        $stmt = $pdo->prepare("
            SELECT eds.work_schedule_id, eds.is_rest_day, ws.time_in, ws.time_out
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ? AND eds.day_of_week = ?
            LIMIT 1
        ");
        $stmt->execute([$employee_id, $dayOfWeek]);
        $weeklySchedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($weeklySchedule) {
            if ($weeklySchedule['is_rest_day']) {
                return ['is_rest_day' => true];
            }
            if ($weeklySchedule['time_in'] && $weeklySchedule['time_out']) {
                return [
                    'time_in' => date('g:i A', strtotime($weeklySchedule['time_in'])),
                    'time_out' => date('g:i A', strtotime($weeklySchedule['time_out'])),
                    'is_rest_day' => false
                ];
            }
        }
        
        // PRIORITY 3: Fall back to employee's official schedule
        $empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
        $empStmt->execute([$employee_id]);
        $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
        $officialSchedId = $emp['official_sched'] ?? 4;
        
        $schedStmt = $pdo->prepare("SELECT time_in, time_out FROM work_schedules WHERE id = ?");
        $schedStmt->execute([$officialSchedId]);
        $sched = $schedStmt->fetch(PDO::FETCH_ASSOC);
        if ($sched) {
            return [
                'time_in' => date('g:i A', strtotime($sched['time_in'])),
                'time_out' => date('g:i A', strtotime($sched['time_out'])),
                'is_rest_day' => false
            ];
        }
    } catch (Exception $e) {
        // Fallback to empty
    }
    
    return ['time_in' => '—', 'time_out' => '—', 'is_rest_day' => false];
    }
}
?>

<!-- ✅ DO NOT TOUCH CONTAINER ABOVE THIS -->
<!-- 🔧 JSON FIX APPLIED: <?= date('Y-m-d H:i:s') ?> - JSON_HEX_TAG enabled for newline handling -->

<div class="bg-white rounded-xl shadow-sm p-6 w-full md:w-2/3 xl:w-1/2 2xl:w-5/12 border border-gray-200/80 transition-all duration-300 hover:shadow-lg">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-2xl font-semibold text-gray-800">
            Recent Activity
        </h3>

        <!-- Date Filter -->
        <form method="GET" class="flex items-center gap-2">
            <label for="activityDate" class="text-sm font-medium text-gray-600">Filter:</label>
            <input type="date" id="activityDate" name="activityDate"
                   value="<?= htmlspecialchars($filterDate ?? '') ?>"
                   class="border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm transition-all duration-200 w-40">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-all duration-300 shadow-sm hover:shadow-md transform active:scale-95">
                <i class="fas fa-search text-xs"></i>
            </button>
        </form>
    </div>

    <!-- Activity Table -->
    <div class="overflow-x-auto">
        <div class="border border-gray-200/80 rounded-lg">
            <div class="max-h-60 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-40">
                                Date Filed
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Request Details
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200"><?php if (!empty($recentActivities)): ?>
    <?php foreach ($recentActivities as $activity): ?>
        <?php
            $created = date('M j, Y', strtotime($activity['created_at']));
            $msg = strip_tags($activity['message']);

            // Detect type - check for schedule swap first, then monthly schedule, then overtime, then day off/rest day
            if (isset($activity['type']) && $activity['type'] === 'Schedule Swap Request') {
                $type = 'Schedule Swap';
            } elseif (isset($activity['type']) && $activity['type'] === 'Monthly Schedule Request') {
                $type = 'Monthly Schedule';
            } elseif (preg_match('/\b(overtime|OT|RDOT)\b/i', $msg)) {
                // Check for overtime first to prevent RDOT from being detected as "Day Off"
                $type = 'Overtime';
            } elseif (preg_match('/\b(day\s*off|rest\s*day|off\s*day)\b/i', $msg) && !preg_match('/\b(overtime|OT|RDOT)\b/i', $msg)) {
                // Only match day off if it's NOT an overtime request
                $type = 'Day Off';
            } else {
                preg_match('/\b(Leave|Schedule|Time)\b/i', $msg, $typeMatch);
                $type = $typeMatch[0] ?? 'Request';
            }

            // For Leave requests, fetch status directly from post_leave_requests table column 'status'
            if ($type === 'Leave' && isset($activity['leave_status'])) {
                $status = strtolower($activity['leave_status']);
                if ($status === 'approved') {
                    $status = 'Approved';
                } elseif ($status === 'declined' || $status === 'rejected') {
                    $status = 'Declined';
                } else {
                    $status = 'Pending';
                }
                
                // Get leave type for detailed feedback - check if leave_type exists in activity data
                $leaveType = $activity['leave_type'] ?? null;
                
                $leaveTypeDisplay = match($leaveType) {
                    'sick' => 'Sick Leave',
                    'vacation' => 'Vacation Leave',
                    'paternity' => 'Paternity Leave',
                    'maternity' => 'Maternity Leave',
                    'solo_parent' => 'Solo Parent Leave',
                    'halfday' => 'Half Day Leave',
                    'halfday_sick' => 'Half Day Sick Leave',
                    'lwop' => 'Leave Without Pay',
                    'bereavement' => 'Bereavement Leave',
                    default => 'Leave Request' // Fallback if leave_type is not found
                };
            }
            // For Time requests, fetch status and data directly from post_time_adjustment_requests table
            // IMPORTANT: For approved requests, all data (log_date, current_time_in, current_time_out, 
            // requested_time_in, requested_time_out, reason, etc.) should come from post_time_adjustment_requests table
            elseif ($type === 'Time' && isset($activity['time_adjust_status'])) {
                $status = strtolower($activity['time_adjust_status']);
                if ($status === 'approved') {
                    $status = 'Approved';
                } elseif ($status === 'declined' || $status === 'rejected') {
                    $status = 'Declined';
                } else {
                    $status = 'Pending';
                }
            }
            // For Overtime requests, fetch status directly from post_ot_requests table
            elseif ($type === 'Overtime' && isset($activity['ot_status'])) {
                $status = strtolower($activity['ot_status']);
                if ($status === 'approved') {
                    $status = 'Approved';
                } elseif ($status === 'declined' || $status === 'rejected') {
                    $status = 'Declined';
                } else {
                    $status = 'Pending';
                }
            }
            // For Schedule Swap requests
            elseif ($type === 'Schedule Swap' && isset($activity['status'])) {
                $status = ucfirst(strtolower($activity['status']));
            }
            // For Monthly Schedule requests
            elseif ($type === 'Monthly Schedule' && isset($activity['status'])) {
                $status = ucfirst(strtolower($activity['status']));
            }
            // For Schedule requests, use status from DB
            elseif ($type === 'Schedule' && isset($activity['status'])) {
                $status = ucfirst(strtolower($activity['status']));
            } else {
                preg_match('/\b(Approved|Rejected|Pending|Declined|Cancelled)\b/i', $msg, $statusMatch);
                $status = ucfirst(strtolower($statusMatch[0] ?? 'Pending'));
            }

            $badgeColor = match (strtolower($status)) {
                'approved' => 'bg-green-100 text-green-800',
                'rejected', 'declined', 'cancelled' => 'bg-red-100 text-red-800',
                'pending' => 'bg-yellow-100 text-yellow-800',
                default => 'bg-gray-100 text-gray-700',
            };

            // Build sentence-style summary
            $sentence = "On $created: ";
            if ($type === 'Schedule') {
                // Create a more descriptive message for schedule changes
                if (!empty($activity['current_time_in']) && !empty($activity['current_time_out']) && 
                    !empty($activity['requested_time_in']) && !empty($activity['requested_time_out'])) {
                    
                    $currentTimeIn = date('g:i A', strtotime($activity['current_time_in']));
                    $currentTimeOut = date('g:i A', strtotime($activity['current_time_out']));
                    $requestedTimeIn = date('g:i A', strtotime($activity['requested_time_in']));
                    $requestedTimeOut = date('g:i A', strtotime($activity['requested_time_out']));
                    
                    $sentence .= "Schedule change from {$currentTimeIn}-{$currentTimeOut} to {$requestedTimeIn}-{$requestedTimeOut} was $status.";
                } else {
                    $sentence .= "Schedule change was $status.";
                }
                
                // Add date range if available
                if (!empty($activity['start_date']) && !empty($activity['end_date'])) {
                    $startDate = date('M j, Y', strtotime($activity['start_date']));
                    $endDate = date('M j, Y', strtotime($activity['end_date']));
                    if ($startDate === $endDate) {
                            $sentence .= "\nEffective Date: $startDate"; // Keep card minimal: date ranges shown only in modal
                    } else {
                        $sentence .= "\nEffective Period: $startDate to $endDate";
                    }
                }
                    // Keep card minimal: date ranges shown only in modal
                
                if (in_array(strtolower($status), ['declined', 'cancelled', 'rejected']) && !empty($activity['explanation'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['explanation']);
                }
            } elseif ($type === 'Leave') {
                // Use leaveTypeDisplay if available, otherwise fall back to generic
                if (isset($leaveTypeDisplay) && $leaveTypeDisplay !== 'Leave Request') {
                    $sentence .= "$leaveTypeDisplay was $status.";
                } else {
                    $sentence .= "Leave request was $status.";
                }
                
                // Add leave dates if available
                if (!empty($activity['start_date']) && !empty($activity['end_date'])) {
                    $startDate = date('M j, Y', strtotime($activity['start_date']));
                    $endDate = date('M j, Y', strtotime($activity['end_date']));
                    if ($startDate === $endDate) {
                            $sentence .= "\nDate: $startDate"; // Keep card minimal: dates shown only in modal
                    } else {
                        $sentence .= "\nDates: $startDate to $endDate";
                    }
                }
                    // Keep card minimal: dates shown only in modal
                
                // Add detailed reason for declined requests
                if (in_array(strtolower($status), ['declined', 'cancelled', 'rejected'])) {
                    if (!empty($activity['explanation'])) {
                        $sentence .= "\nReason for decline: " . htmlspecialchars($activity['explanation']);
                    } else {
                        $sentence .= "\nReason for decline: Not specified by administrator.";
                    }
                }
                
                // Add employee reason if available
                if (!empty($activity['reason'])) {
                    $sentence .= "\nEmployee reason: " . htmlspecialchars($activity['reason']);
                }
            } elseif ($type === 'Time') {
                // NOTE: For approved time adjustments, this data should come from post_time_adjustment_requests table
                // including log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, reason
                $sentence .= "Time adjustment request was $status.";
                
                // Add time details if available (from post_time_adjustment_requests table)
                if (!empty($activity['log_date'])) {
                    $logDate = date('M j, Y', strtotime($activity['log_date']));
                    $sentence .= "\nTarget Date: $logDate";
                }
                
                // Format original times (from post_time_adjustment_requests.current_time_in/out)
                $currentTimeIn = !empty($activity['current_time_in']) ? date('g:i A', strtotime($activity['current_time_in'])) : '—';
                $currentTimeOut = !empty($activity['current_time_out']) ? date('g:i A', strtotime($activity['current_time_out'])) : '—';
                
                // Format adjusted times (from post_time_adjustment_requests.requested_time_in/out)
                $requestedTimeIn = !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '—';
                $requestedTimeOut = !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '—';
                
                $sentence .= "\nOriginal: $currentTimeIn - $currentTimeOut";
                $sentence .= "\nAdjusted: $requestedTimeIn - $requestedTimeOut";
                
                if (!empty($activity['reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['reason']);
                }
            } elseif ($type === 'Overtime') {
                $sentence .= "Overtime request was $status.";
                
                // Add OT date if available
                if (!empty($activity['ot_date'])) {
                    $otDate = date('M j, Y', strtotime($activity['ot_date']));
                    $sentence .= "\nOT Date: $otDate";
                } elseif (!empty($activity['log_date'])) {
                    $otDate = date('M j, Y', strtotime($activity['log_date']));
                    $sentence .= "\nOT Date: $otDate";
                }
                
                if (!empty($activity['ot_reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['ot_reason']);
                }
            } elseif ($type === 'Schedule Swap') {
                $sentence .= "Schedule swap request was $status.";
                
                // Add dates
                if (!empty($activity['source_date']) && !empty($activity['target_date'])) {
                    $sourceDateFormatted = date('M j, Y', strtotime($activity['source_date']));
                    $targetDateFormatted = date('M j, Y', strtotime($activity['target_date']));
                    $sentence .= "\nSwitch: $sourceDateFormatted ↔ $targetDateFormatted";
                }
                
                // Add schedule times if available
                if (!empty($activity['source_schedule_in']) && !empty($activity['target_schedule_in'])) {
                    $sentence .= "\nDate A: {$activity['source_schedule_in']} - {$activity['source_schedule_out']}";
                    $sentence .= "\nDate B: {$activity['target_schedule_in']} - {$activity['target_schedule_out']}";
                }
                
                if (!empty($activity['reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['reason']);
                }
                
                // Add admin explanation if declined
                if (in_array(strtolower($status), ['declined', 'cancelled', 'rejected']) && !empty($activity['explanation'])) {
                    $sentence .= "\nAdmin Notes: " . htmlspecialchars($activity['explanation']);
                }
            } elseif ($type === 'Monthly Schedule') {
                $sentence .= "Monthly schedule request was $status.";
                
                // Add month
                if (!empty($activity['month_name'])) {
                    $sentence .= "\nMonth: {$activity['month_name']}";
                }
                
                if (!empty($activity['reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['reason']);
                }
                
                // Add admin explanation if declined
                if (in_array(strtolower($status), ['declined', 'cancelled', 'rejected']) && !empty($activity['explanation'])) {
                    $sentence .= "\nAdmin Notes: " . htmlspecialchars($activity['explanation']);
                }
            } else {
                $sentence .= "$type request was $status.";
            }
        ?>
        <tr class="hover:bg-gray-50 transition-colors duration-200">
            <td class="px-4 py-3 text-sm font-medium text-gray-700 w-36">
                <?= $created ?>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <div class="text-sm font-bold text-gray-900">
                            <?php if ($type === 'Leave' && isset($leaveTypeDisplay) && $leaveTypeDisplay !== 'Leave Request'): ?>
                                <?= $leaveTypeDisplay ?>
                            <?php elseif ($type === 'Leave'): ?>
                                Leave Request
                            <?php elseif ($type === 'Schedule'): ?>
                                <?php
                                // Check if this is a rest day request (work_schedule_id is NULL or is_rest_day is 1)
                                $isRestDay = (empty($activity['work_schedule_id']) || ($activity['is_rest_day'] ?? 0) == 1);
                                echo $isRestDay ? 'Add a Day Off' : 'Schedule Change Request';
                                ?>
                            <?php elseif ($type === 'Time'): ?>
                                Time Adjustment Request
                            <?php elseif ($type === 'Schedule Swap'): ?>
                                Schedule Swap Request
                            <?php elseif ($type === 'Monthly Schedule'): ?>
                                Monthly Schedule Request
                            <?php else: ?>
                                <?= $type ?> Request
                            <?php endif; ?>
                    </div>
                    <!-- Minimal card: hide dates/effective; details are shown in the modal -->
                    
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 w-28">
                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $badgeColor ?> whitespace-nowrap">
                    <?= $status ?>
                </span>
            </td>
            <td class="px-4 py-3 w-28">
                <div class="flex items-center gap-2">
                    <?php
                    if ($type === 'Time') {
                        ?>
                        <button onclick="showActivityDetails('Time Adjustment', `<?= htmlspecialchars(json_encode([
                            'type' => 'Time Adjustment',
                            'status' => $status,
                            'date' => $created,
                            'log_date' => !empty($activity['log_date']) ? date('M j, Y', strtotime($activity['log_date'])) : '',
                            'current_time_in' => !empty($activity['current_time_in']) ? date('g:i A', strtotime($activity['current_time_in'])) : '',
                            'current_time_out' => !empty($activity['current_time_out']) ? date('g:i A', strtotime($activity['current_time_out'])) : '',
                            'requested_time_in' => !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '',
                            'requested_time_out' => !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '',
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'attachment' => $activity['attachment'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Overtime') { ?>
                        <button onclick="showActivityDetails('Overtime Request', `<?= htmlspecialchars(json_encode([
                            'type' => 'Overtime Request',
                            'status' => $status,
                            'date' => $created,
                            'ot_date' => !empty($activity['ot_date']) ? date('M j, Y', strtotime($activity['ot_date'])) : (!empty($activity['log_date']) ? date('M j, Y', strtotime($activity['log_date'])) : (!empty($activity['created_at']) ? date('M j, Y', strtotime($activity['created_at'])) : 'Not specified')),
                            'reason' => $activity['ot_reason'] ?? '',
                            'ot_type' => $activity['ot_type'] ?? 'Overtime',
                            'ot_duration' => isset($activity['ot_duration']) ? number_format((float)$activity['ot_duration'], 2) : '',
                            'max_ot_hours' => isset($activity['max_ot_hours']) ? number_format((float)$activity['max_ot_hours'], 2) : '',
                            'start_ot' => !empty($activity['start_ot']) ? date('g:i A', strtotime($activity['start_ot'])) : '',
                            'end_ot' => !empty($activity['end_ot']) ? date('g:i A', strtotime($activity['end_ot'])) : '',
                            'time_in' => !empty($activity['time_in']) ? date('g:i A', strtotime($activity['time_in'])) : '',
                            'time_out' => !empty($activity['time_out']) ? date('g:i A', strtotime($activity['time_out'])) : '',
                            'explanation' => $activity['explanation'] ?? '',
                            'attachment' => $activity['attachment'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Leave') { ?>
                        <button onclick="showActivityDetails('Leave Request', `<?= htmlspecialchars(json_encode([
                            'type' => isset($leaveTypeDisplay) ? $leaveTypeDisplay : 'Leave Request',
                            'status' => $status,
                            'date' => $created,
                            'start_date' => !empty($activity['start_date']) ? date('M j, Y', strtotime($activity['start_date'])) : '',
                            'end_date' => !empty($activity['end_date']) ? date('M j, Y', strtotime($activity['end_date'])) : '',
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'attachment_lr' => $activity['attachment_lr'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Schedule') { ?>
                        <?php
                            // Use the current schedule from the notification data (historical reference)
                            // Do NOT fetch live schedule - we want to preserve what it was at the time of request
                            $currentTimeIn = $activity['current_time_in'] ?? '';
                            $currentTimeOut = $activity['current_time_out'] ?? '';
                            
                            // Format if they're in 24-hour format
                            if ($currentTimeIn && strpos($currentTimeIn, ':') !== false && strpos($currentTimeIn, 'AM') === false && strpos($currentTimeIn, 'PM') === false) {
                                $currentTimeIn = date('g:i A', strtotime($currentTimeIn));
                            }
                            if ($currentTimeOut && strpos($currentTimeOut, ':') !== false && strpos($currentTimeOut, 'AM') === false && strpos($currentTimeOut, 'PM') === false) {
                                $currentTimeOut = date('g:i A', strtotime($currentTimeOut));
                            }
                        ?>
                        <button onclick="showActivityDetails('Schedule Change', `<?= htmlspecialchars(json_encode([
                            'type' => 'Schedule Change',
                            'status' => $status,
                            'date' => $created,
                            'current_time_in' => $currentTimeIn,
                            'current_time_out' => $currentTimeOut,
                            'requested_time_in' => !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '',
                            'requested_time_out' => !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '',
                            'start_date' => !empty($activity['start_date']) ? date('M j, Y', strtotime($activity['start_date'])) : '',
                            'end_date' => !empty($activity['end_date']) ? date('M j, Y', strtotime($activity['end_date'])) : '',
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'work_schedule_id' => $activity['work_schedule_id'] ?? '',
                            'attachment_scr' => $activity['attachment_scr'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Schedule Swap') { ?>
                        <button onclick="showActivityDetails('Schedule Swap Request', `<?= htmlspecialchars(json_encode([
                            'type' => 'Schedule Swap Request',
                            'status' => $status,
                            'date' => $created,
                            'source_date' => !empty($activity['source_date']) ? date('M j, Y', strtotime($activity['source_date'])) : '',
                            'target_date' => !empty($activity['target_date']) ? date('M j, Y', strtotime($activity['target_date'])) : '',
                            'source_schedule_in' => $activity['source_schedule_in'] ?? '',
                            'source_schedule_out' => $activity['source_schedule_out'] ?? '',
                            'target_schedule_in' => $activity['target_schedule_in'] ?? '',
                            'target_schedule_out' => $activity['target_schedule_out'] ?? '',
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'attachment_scr' => $activity['attachment_scr'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? '',
                            'processed_at' => !empty($activity['processed_at']) ? date('M j, Y g:i A', strtotime($activity['processed_at'])) : ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Monthly Schedule') { ?>
                        <button onclick="showActivityDetails('Monthly Schedule Request', `<?= htmlspecialchars(json_encode([
                            'type' => 'Monthly Schedule Request',
                            'status' => $status,
                            'date' => $created,
                            'month_name' => $activity['month_name'] ?? '',
                            'year' => $activity['year'] ?? '',
                            'month' => $activity['month'] ?? '',
                            'weekly_schedules' => $activity['weekly_schedules'] ?? [],
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'attachment_scr' => $activity['attachment_scr'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? '',
                            'processed_at' => !empty($activity['processed_at']) ? date('M j, Y g:i A', strtotime($activity['processed_at'])) : ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Day Off') { ?>
                        <?php
                            // Use the current schedule from the notification data (historical reference)
                            // For day off requests, we want to show what schedule they had before requesting day off
                            $currentTimeIn = $activity['current_time_in'] ?? '';
                            $currentTimeOut = $activity['current_time_out'] ?? '';
                            
                            // Format if they're in 24-hour format
                            if ($currentTimeIn && strpos($currentTimeIn, ':') !== false && strpos($currentTimeIn, 'AM') === false && strpos($currentTimeIn, 'PM') === false) {
                                $currentTimeIn = date('g:i A', strtotime($currentTimeIn));
                            }
                            if ($currentTimeOut && strpos($currentTimeOut, ':') !== false && strpos($currentTimeOut, 'AM') === false && strpos($currentTimeOut, 'PM') === false) {
                                $currentTimeOut = date('g:i A', strtotime($currentTimeOut));
                            }
                        ?>
                        <button onclick="showActivityDetails('Day Off Request', `<?= htmlspecialchars(json_encode([
                            'type' => 'Day Off Request',
                            'status' => $status,
                            'date' => $created,
                            'current_time_in' => $currentTimeIn,
                            'current_time_out' => $currentTimeOut,
                            'requested_time_in' => !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '',
                            'requested_time_out' => !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '',
                            'start_date' => !empty($activity['start_date']) ? date('M j, Y', strtotime($activity['start_date'])) : '',
                            'end_date' => !empty($activity['end_date']) ? date('M j, Y', strtotime($activity['end_date'])) : '',
                            'reason' => $activity['reason'] ?? '',
                            'explanation' => $activity['explanation'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'work_schedule_id' => $activity['work_schedule_id'] ?? '',
                            'is_rest_day' => $activity['is_rest_day'] ?? 1,
                            'attachment_scr' => $activity['attachment_scr'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } else { ?>
                        <?php
                            $fallbackPayload = [
                                'type' => $type . ' Request',
                                'status' => $status,
                                'date' => $created,
                                'reason' => $activity['reason'] ?? '',
                                'explanation' => $activity['explanation'] ?? '',
                            ];
                            if (!empty($activity['request_id'])) { $fallbackPayload['request_id'] = $activity['request_id']; }
                            if (!empty($activity['table_name'])) { $fallbackPayload['table_name'] = $activity['table_name']; }
                            if (!empty($activity['source_table'])) { $fallbackPayload['source_table'] = $activity['source_table']; }
                        ?>
                        <button onclick="showActivityDetails('<?= $type ?> Request', `<?= htmlspecialchars(json_encode($fallbackPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-1">No Recent Activity</h3>
                                    <p class="text-gray-500 text-sm">Your activities will appear here once you submit requests.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Activity Details Modal -->
<div id="activityModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-60 hidden backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 border border-gray-200/80 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50 rounded-t-xl">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-xl mr-4">
                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800" id="modalTitle">Activity Details</h2>
            </div>
            <button onclick="closeActivityModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-200 rounded-full">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-5" id="modalBody">
            <!-- Dynamic content will be inserted here -->
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl" id="modalFooter">
            <div id="unsubmitButtonContainer"></div>
            <button onclick="closeActivityModal()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-5 py-2 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md transform active:scale-95">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Make work schedules available to JavaScript
const workSchedules = <?= json_encode($work_schedules ?? []) ?>;

// Function to find schedule by ID  
function findScheduleById(scheduleId) {
    return workSchedules.find(ws => ws.id == scheduleId);
}

// Function to find schedule by time
function findScheduleByTime(timeIn, timeOut) {
    return workSchedules.find(ws => {
        const wsTimeInFormatted = new Date('1970-01-01 ' + ws.time_in).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
        const wsTimeOutFormatted = new Date('1970-01-01 ' + ws.time_out).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
        return wsTimeInFormatted === timeIn && wsTimeOutFormatted === timeOut;
    });
}

// Function to get schedule display name from ID
function getScheduleDisplayName(scheduleId) {
    const schedule = findScheduleById(scheduleId);
    if (schedule) {
        const timeInDisplay = formatTime12Hour(schedule.time_in);
        const timeOutDisplay = formatTime12Hour(schedule.time_out);
        return `${timeInDisplay} - ${timeOutDisplay}`;
    }
    return 'Unknown Schedule';
}

function showActivityDetails(title, dataJson) {
    try {
        console.log('🔍 showActivityDetails called:', {
            title: title,
            timestamp: new Date().toISOString(),
            jsonLength: dataJson ? dataJson.length : 0
        });
        console.log('📋 Raw JSON string:', dataJson);
        
        const data = JSON.parse(dataJson);
        console.log('📊 Parsed Data:', data);
        console.log('📎 Attachment in parsed data:', {
            attachment_scr: data.attachment_scr,
            attachment_lr: data.attachment_lr,
            attachment: data.attachment,
            hasAttachment: !!(data.attachment_scr && data.attachment_scr.trim()),
            hasAttachmentLr: !!(data.attachment_lr && data.attachment_lr.trim()),
            hasAttachmentGeneric: !!(data.attachment && data.attachment.trim())
        });
        
        const modal = document.getElementById('activityModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        // Set modal title - check if it's RDOT
        let displayTitle = data.type || title;
        if (data.type && data.type.includes('Overtime') && data.ot_type && (data.ot_type.toLowerCase().includes('rdot') || data.ot_type.toLowerCase().includes('rest'))) {
            displayTitle = 'RDOT Request';
        }
        modalTitle.textContent = displayTitle;
        
        // Build modal content based on request type
        let content = '';
        
        // Status badge
        const statusClass = getStatusBadgeClass(data.status);
        content += `
            <div class="flex items-start justify-between pb-4 border-b border-gray-200 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">${displayTitle}</h3>
                    <p class="text-sm text-gray-500">${data.date}</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold ${statusClass}">
                    ${getStatusIcon(data.status)} ${data.status}
                </span>
            </div>
        `;
        
        // Type-specific fields
        if (data.type.includes('Time Adjustment')) {
            if (data.log_date) {
                content += createInfoBlock('Adjustment Date', data.log_date, 'fa-calendar-day');
            }
            
            // Debug: Show all available data if time fields are missing
            console.log('Time Adjustment Data:', data);
            console.log('Requested Time In:', data.requested_time_in);
            console.log('Requested Time Out:', data.requested_time_out);
            console.log('Current Time In:', data.current_time_in);
            console.log('Current Time Out:', data.current_time_out);
            
            // Check if we have time data, if not show debugging info
            if (data.current_time_in || data.current_time_out || data.requested_time_in || data.requested_time_out) {
                // Check if request is pending and can be edited
                const isEditable = data.status && data.status.toLowerCase() === 'pending' && data.request_id;
                
                if (isEditable) {
                    // Compact editable time adjustment
                    content += `
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" id="time-adjustment-block-${data.request_id}">
                            <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                Time Changes
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Time In -->
                                <div class="bg-white p-3 rounded-lg border">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-xs font-medium text-blue-600">Time In</label>
                                        <span class="text-xs text-gray-500">Was: ${data.current_time_in || 'N/A'}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <select id="edit-time-in-hour-${data.request_id}" 
                                                class="w-12 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400" 
                                                disabled data-original="${extractHour(data.requested_time_in || data.current_time_in)}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'in'); checkForChanges(${data.request_id})">
                                            ${(() => {
                                                const timeValue = data.requested_time_in || data.current_time_in;
                                                console.log('Time In value being used for hour options:', timeValue);
                                                return generateHourOptions(timeValue);
                                            })()}
                                        </select>
                                        <span class="text-gray-400">:</span>
                                        <select id="edit-time-in-minute-${data.request_id}" 
                                                class="w-12 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400" 
                                                disabled data-original="${extractMinute(data.requested_time_in || data.current_time_in)}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'in'); checkForChanges(${data.request_id})">
                                            ${(() => {
                                                const timeValue = data.requested_time_in || data.current_time_in;
                                                console.log('Time In value being used for minute options:', timeValue);
                                                return generateMinuteOptions(timeValue);
                                            })()}
                                        </select>
                                        <select id="edit-time-in-ampm-${data.request_id}" 
                                                class="w-14 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400" 
                                                disabled data-original="${extractAMPM(data.requested_time_in || data.current_time_in)}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'in'); checkForChanges(${data.request_id})">
                                            ${(() => {
                                                const timeValue = data.requested_time_in || data.current_time_in;
                                                console.log('Time In value being used for AM/PM options:', timeValue);
                                                return generateAMPMOptions(timeValue);
                                            })()}
                                        </select>
                                    </div>
                                    <input type="hidden" id="edit-time-in-${data.request_id}" value="${data.requested_time_in || data.current_time_in || ''}" data-original="${data.requested_time_in || data.current_time_in || ''}">
                                </div>
                                
                                <!-- Time Out -->
                                <div class="bg-white p-3 rounded-lg border">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-xs font-medium text-green-600">Time Out</label>
                                        <span class="text-xs text-gray-500">Was: ${data.current_time_out || 'N/A'}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <select id="edit-time-out-hour-${data.request_id}" 
                                                class="w-12 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-green-400 focus:border-green-400" 
                                                disabled data-original="${extractHour(data.requested_time_out || data.current_time_out || '')}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'out'); checkForChanges(${data.request_id})">
                                            ${generateHourOptions(data.requested_time_out || data.current_time_out || '')}
                                        </select>
                                        <span class="text-gray-400">:</span>
                                        <select id="edit-time-out-minute-${data.request_id}" 
                                                class="w-12 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-green-400 focus:border-green-400" 
                                                disabled data-original="${extractMinute(data.requested_time_out || data.current_time_out || '')}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'out'); checkForChanges(${data.request_id})">
                                            ${generateMinuteOptions(data.requested_time_out || data.current_time_out || '')}
                                        </select>
                                        <select id="edit-time-out-ampm-${data.request_id}" 
                                                class="w-14 h-8 text-center border border-gray-300 bg-gray-100 rounded text-xs font-bold focus:ring-1 focus:ring-green-400 focus:border-green-400" 
                                                disabled data-original="${extractAMPM(data.requested_time_out || data.current_time_out || '')}" 
                                                onchange="updateTimeDisplay(${data.request_id}, 'out'); checkForChanges(${data.request_id})">
                                            ${generateAMPMOptions(data.requested_time_out || data.current_time_out || '')}
                                        </select>
                                    </div>
                                    <input type="hidden" id="edit-time-out-${data.request_id}" value="${data.requested_time_out || data.current_time_out || ''}" data-original="${data.requested_time_out || data.current_time_out || ''}">
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Compact static version for non-pending requests
                    content += `
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                Time Changes
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white p-3 rounded-lg border">
                                    <label class="text-xs font-medium text-gray-500 block mb-1">Original Time</label>
                                    <p class="text-sm font-semibold text-gray-800">${data.current_time_in || 'N/A'} - ${data.current_time_out || 'N/A'}</p>
                                </div>
                                <div class="bg-white p-3 rounded-lg border">
                                    <label class="text-xs font-medium text-green-600 block mb-1">Adjusted Time</label>
                                    <p class="text-sm font-semibold text-green-800">${data.requested_time_in || 'N/A'} - ${data.requested_time_out || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else {
                // Show debugging information when time data is missing
                content += `
                    <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                        <h4 class="text-sm font-bold text-red-700 mb-3">⚠️ Missing Time Data</h4>
                        <p class="text-sm text-red-600 mb-2">Expected data from post_time_adjustment_requests table:</p>
                        <ul class="text-xs text-red-600 list-disc list-inside space-y-1">
                            <li>log_date: ${data.log_date || 'Missing'}</li>
                            <li>current_time_in: ${data.current_time_in || 'Missing'}</li>
                            <li>current_time_out: ${data.current_time_out || 'Missing'}</li>
                            <li>requested_time_in: ${data.requested_time_in || 'Missing'}</li>
                            <li>requested_time_out: ${data.requested_time_out || 'Missing'}</li>
                            <li>reason: ${data.reason || 'Missing'}</li>
                        </ul>
                        <p class="text-xs text-red-600 mt-2 italic">Check if notification data is being fetched from the correct table.</p>
                        ${data.raw_data ? `<details class="mt-3"><summary class="text-xs cursor-pointer">Show Raw Data</summary><pre class="text-xs mt-2 bg-gray-100 p-2 rounded overflow-auto">${data.raw_data}</pre></details>` : ''}
                    </div>
                `;
            }
            
            // Show processing information for approved requests
            if (data.status.toLowerCase() === 'approved' && data.processed_at) {
                content += createInfoBlock('Processed Date', new Date(data.processed_at).toLocaleString(), 'fa-check-circle', 'text-sm text-green-700', 'bg-green-50 border-green-200');
            }
            
            // Attachment section for time adjustment requests
            content += createAttachmentSection(data.attachment, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Leave')) {
            if (data.start_date && data.end_date) {
                content += createInfoBlock('Leave Period', `${data.start_date} to ${data.end_date}`, 'fa-calendar-week');
            }
            
            // Attachment section for leave requests
            content += createAttachmentSection(data.attachment_lr, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Monthly Schedule')) {
            // Monthly Schedule request - minimal modern design
            console.log('📅 Monthly Schedule Data:', data);
            
            // Header with month badge
            content += `
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-calendar-week text-blue-600 mr-2"></i>
                            Monthly Schedule
                        </h4>
                        ${data.month_name ? `<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">${data.month_name}</span>` : ''}
                    </div>
                </div>
            `;
            
            // Weekly schedule - clean card layout
            if (data.weekly_schedules) {
                const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                const dayLabels = {
                    'monday': 'Mon',
                    'tuesday': 'Tue',
                    'wednesday': 'Wed',
                    'thursday': 'Thu',
                    'friday': 'Fri',
                    'saturday': 'Sat',
                    'sunday': 'Sun'
                };
                
                content += `
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
                        <div class="grid grid-cols-7 divide-x divide-gray-200">
                `;
                
                days.forEach(day => {
                    const schedule = data.weekly_schedules[day];
                    const dayLabel = dayLabels[day];
                    const isRestDay = schedule && schedule.is_rest_day;
                    const timeIn = schedule ? (schedule.time_in || '—') : '—';
                    const timeOut = schedule ? (schedule.time_out || '—') : '—';
                    
                    const isWeekend = day === 'saturday' || day === 'sunday';
                    const bgColor = isRestDay ? 'bg-gray-50' : (isWeekend ? 'bg-blue-50' : 'bg-white');
                    const textColor = isRestDay ? 'text-gray-400' : 'text-gray-700';
                    
                    content += `
                        <div class="${bgColor} p-3 text-center transition-all hover:shadow-sm">
                            <div class="text-xs font-semibold ${textColor} mb-2">${dayLabel}</div>
                            <div class="text-xs ${textColor} space-y-1">
                                ${isRestDay ? 
                                    '<div class="text-red-600 font-semibold">Rest Day</div>' : 
                                    '<div class="font-medium">' + timeIn + '</div><div class="text-gray-400">to</div><div class="font-medium">' + timeOut + '</div>'
                                }
                            </div>
                        </div>
                    `;
                });
                
                content += `
                        </div>
                    </div>
                `;
            }
            
            // Show processed date if available
            if (data.processed_at) {
                content += `
                    <div class="text-sm text-gray-500 mb-3">
                        <i class="fas fa-check-circle mr-1"></i>
                        Processed: ${data.processed_at}
                    </div>
                `;
            }

            // Attachment section for monthly schedule requests
            content += createAttachmentSection(data.attachment_scr, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Schedule Swap')) {
            // Schedule Swap request - minimal modern design
            console.log('📊 Schedule Swap Data:', data);
            
            // Header
            content += `
                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-exchange-alt text-purple-600 mr-2"></i>
                        Schedule Swap
                    </h4>
                    <p class="text-sm text-gray-500 mt-1">Swap schedules between two dates</p>
                </div>
            `;
            
            // Date swap - clean minimal design
            content += `
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
                    <div class="grid grid-cols-3 divide-x divide-gray-200">
                        <!-- Date A -->
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center mb-2">
                                <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">A</span>
                                <span class="text-xs text-gray-500 font-medium">From</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-800 mb-1">${data.source_date || 'N/A'}</div>
                            <div class="text-xs text-gray-600">${data.source_schedule_in || '—'} - ${data.source_schedule_out || '—'}</div>
                        </div>
                        
                        <!-- Swap Icon -->
                        <div class="p-4 flex items-center justify-center bg-gray-50">
                            <i class="fas fa-exchange-alt text-gray-400 text-xl"></i>
                        </div>
                        
                        <!-- Date B -->
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center mb-2">
                                <span class="w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-2">B</span>
                                <span class="text-xs text-gray-500 font-medium">To</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-800 mb-1">${data.target_date || 'N/A'}</div>
                            <div class="text-xs text-gray-600">${data.target_schedule_in || '—'} - ${data.target_schedule_out || '—'}</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Show processed date if available
            if (data.processed_at) {
                content += `
                    <div class="text-sm text-gray-500 mb-3">
                        <i class="fas fa-check-circle mr-1"></i>
                        Processed: ${data.processed_at}
                    </div>
                `;
            }

            // Attachment section for schedule swap requests
            content += createAttachmentSection(data.attachment_scr, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Schedule')) {
            // Full width horizontal container for schedule change
            content += `<div class="border border-gray-200 rounded-lg p-5 bg-gradient-to-r from-blue-50 to-green-50">`;
            
            if (data.current_time_in && data.current_time_out && data.requested_time_in && data.requested_time_out) {
                // Check if request is pending and can be edited
                const isEditable = data.status && data.status.toLowerCase() === 'pending' && data.request_id;
                
                // Header
                content += `
                    <div class="flex items-center mb-4">
                        <i class="fas fa-calendar-alt text-blue-600 text-lg mr-2"></i>
                        <h4 class="text-lg font-semibold text-gray-800">Schedule Change Details</h4>
                    </div>
                `;
                
                if (isEditable) {
                    content += `
                        <div id="schedule-change-block-${data.request_id}">
                            <!-- Horizontal Schedule Comparison -->
                            <div class="flex items-center justify-between gap-4 mb-4">
                                <div class="flex-1 bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                                        <h5 class="font-semibold text-blue-800 text-sm">Current Schedule</h5>
                                    </div>
                                    <p class="text-xl font-bold text-blue-900">
                                        ${data.current_time_in} - ${data.current_time_out}
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-center px-3">
                                    <i class="fas fa-arrow-right text-gray-400 text-2xl"></i>
                                </div>
                                
                                <div class="flex-1 bg-green-50 border-2 border-green-300 rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-arrow-right text-green-600 mr-2"></i>
                                        <h5 class="font-semibold text-green-800 text-sm">Requested Schedule</h5>
                                    </div>
                                    <select id="edit-schedule-${data.request_id}" 
                                            class="w-full p-2 border-2 border-gray-300 bg-gray-100 rounded-lg text-xl font-bold text-green-900 focus:ring-2 focus:ring-green-400 disabled:cursor-not-allowed" 
                                            disabled 
                                            onchange="updateScheduleDisplay('${data.request_id}')">
                                        ${generateScheduleOptions(data.requested_time_in, data.requested_time_out, data.work_schedule_id)}
                                    </select>
                                    <input type="hidden" id="edit-schedule-hidden-${data.request_id}" 
                                           value="${data.work_schedule_id || ''}" 
                                           data-original="${data.work_schedule_id || ''}">
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Non-editable version for approved/declined requests
                    // Use "Former Schedule" label for approved/declined to show historical context
                    const scheduleLabel = (data.status && (data.status.toLowerCase() === 'approved' || data.status.toLowerCase() === 'declined')) 
                        ? 'Former Schedule' 
                        : 'Current Schedule';
                    
                    content += `
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1 bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-clock text-blue-600 mr-2"></i>
                                    <h5 class="font-semibold text-blue-800 text-sm">${scheduleLabel}</h5>
                                </div>
                                <p class="text-xl font-bold text-blue-900">
                                    ${data.current_time_in} - ${data.current_time_out}
                                </p>
                            </div>
                            
                            <div class="flex items-center justify-center px-3">
                                <i class="fas fa-arrow-right text-gray-400 text-2xl"></i>
                            </div>
                            
                            <div class="flex-1 bg-green-50 border-2 border-green-300 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-arrow-right text-green-600 mr-2"></i>
                                    <h5 class="font-semibold text-green-800 text-sm">Requested Schedule</h5>
                                </div>
                                <p class="text-xl font-bold text-green-900">
                                    ${data.requested_time_in} - ${data.requested_time_out}
                                </p>
                            </div>
                        </div>
                    `;
                }
            }
            
            content += `</div>`; // Close main schedule container
            
            // Effective Period (editable for pending requests)
            if (data.start_date && data.end_date) {
                const isEditable = data.status && data.status.toLowerCase() === 'pending' && data.request_id;
                
                // Format dates properly - handle YYYY-MM-DD format from database
                let formattedStartDate = data.start_date;
                let formattedEndDate = data.end_date;
                
                console.log('Raw dates from database:', {
                    start_date: data.start_date,
                    end_date: data.end_date,
                    start_type: typeof data.start_date,
                    end_type: typeof data.end_date
                });
                
                try {
                    // Handle YYYY-MM-DD format specifically
                    if (typeof data.start_date === 'string' && data.start_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        const [year, month, day] = data.start_date.split('-');
                        if (parseInt(year) > 1900) {
                            const startDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                            formattedStartDate = startDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    } else {
                        // Try parsing as regular date
                        const startDate = new Date(data.start_date);
                        if (startDate.getTime() && startDate.getFullYear() > 1900) {
                            formattedStartDate = startDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    }
                    
                    // Handle end date the same way
                    if (typeof data.end_date === 'string' && data.end_date.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        const [year, month, day] = data.end_date.split('-');
                        if (parseInt(year) > 1900) {
                            const endDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                            formattedEndDate = endDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    } else {
                        // Try parsing as regular date
                        const endDate = new Date(data.end_date);
                        if (endDate.getTime() && endDate.getFullYear() > 1900) {
                            formattedEndDate = endDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    }
                } catch (e) {
                    console.error('Date formatting error:', e);
                    console.log('Problematic dates:', {start: data.start_date, end: data.end_date});
                }
                
                console.log('Formatted dates:', {
                    original_start: data.start_date,
                    original_end: data.end_date,
                    formatted_start: formattedStartDate,
                    formatted_end: formattedEndDate
                });
                
                const dateRange = `${formattedStartDate} to ${formattedEndDate}`;
                content += createInfoBlock('Effective Period', dateRange, 'fa-calendar-alt', 'font-semibold', 'bg-gray-50', isEditable, data.request_id, 'date_range');
            }

            // Attachment section for schedule change requests
            content += createAttachmentSection(data.attachment_scr, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Day Off')) {
            // Day Off request - show schedule change to OFF/Rest Day
            content += `<div class="border border-gray-200 rounded-lg p-5 bg-gradient-to-r from-gray-50 to-gray-100">`;
            
            // Header
            content += `
                <div class="flex items-center mb-4">
                    <i class="fas fa-bed text-gray-600 text-lg mr-2"></i>
                    <h4 class="text-lg font-semibold text-gray-800">Day Off Request Details</h4>
                </div>
            `;
            
            // Show current schedule to Day Off change
            // Use "Former Schedule" label for approved/declined to show historical context
            const dayOffScheduleLabel = (data.status && (data.status.toLowerCase() === 'approved' || data.status.toLowerCase() === 'declined')) 
                ? 'Former Schedule' 
                : 'Current Schedule';
            
            content += `
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div class="flex-1 bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-clock text-blue-600 mr-2"></i>
                            <h5 class="font-semibold text-blue-800 text-sm">${dayOffScheduleLabel}</h5>
                        </div>
                        <p class="text-xl font-bold text-blue-900">
                            ${data.current_time_in || data.requested_time_in || '—'} - ${data.current_time_out || data.requested_time_out || '—'}
                        </p>
                    </div>
                    
                    <div class="flex items-center justify-center px-3">
                        <i class="fas fa-arrow-right text-gray-400 text-2xl"></i>
                    </div>
                    
                    <div class="flex-1 bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-bed text-gray-600 mr-2"></i>
                            <h5 class="font-semibold text-gray-800 text-sm">Requested</h5>
                        </div>
                        <p class="text-xl font-bold text-gray-700">
                            OFF / Rest Day
                        </p>
                    </div>
                </div>
            `;
            
            content += `</div>`; // Close gradient container
            
            // Show date range if available
            if (data.start_date || data.end_date) {
                const formattedStartDate = data.start_date ? new Date(data.start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '';
                const formattedEndDate = data.end_date ? new Date(data.end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '';
                
                const dateRange = formattedStartDate === formattedEndDate ? formattedStartDate : `${formattedStartDate} to ${formattedEndDate}`;
                content += createInfoBlock('Effective Date(s)', dateRange, 'fa-calendar-alt', 'font-semibold', 'bg-gray-50');
            }

            // Attachment section for day off requests (uses same structure as schedule changes)
            content += createAttachmentSection(data.attachment_scr, data.request_id, data.table_name, data.status);
        } else if (data.type.includes('Overtime')) {
            // Always show OT Date section 
            console.log('OT Date Debug - Raw data:', data.ot_date); // Debug log
            let otDateDisplay = data.ot_date;
            
            // If no OT date, try to use the request date as fallback
            if (!otDateDisplay || otDateDisplay === '') {
                otDateDisplay = data.date || 'Not specified';
                console.log('Using fallback OT date:', otDateDisplay);
            }
            
            // Create prominent OT date display
            content += createInfoBlock('Overtime Date', otDateDisplay, 'fa-calendar-day', 'font-bold text-blue-800', 'bg-blue-50 border-blue-200');
            
            // Overtime details: Start, End, Duration, Type - prefer start_ot/end_ot over time_in/time_out
            const details = [];
            const startTime = data.start_ot || data.time_in;
            const endTime = data.end_ot || data.time_out;
            
            if (startTime || endTime) {
                details.push(`<div class=\"bg-gray-50 p-3 rounded-md\">` +
                    `<label class=\"block text-xs font-medium text-gray-500 mb-1\">OT Schedule</label>`+
                    `<div class=\"flex items-center justify-center space-x-3\">`+
                        `<span class=\"text-gray-800 font-semibold\">${startTime || '—'}</span>`+
                        `<i class=\"fas fa-arrow-right text-gray-400 text-xs\"></i>`+
                        `<span class=\"text-gray-800 font-semibold\">${endTime || '—'}</span>`+
                    `</div>`+
                `</div>`);
            }
            if (data.ot_duration) {
                // Check if request is pending and can be edited
                if (data.status && data.status.toLowerCase() === 'pending' && data.request_id) {
                    // Calculate maximum OT hours from Start OT and End OT
                    let maxOTHours = 'N/A';
                    if (startTime && endTime) {
                        // Convert times to 24-hour format for calculation
                        const startTime24 = convertTo24Hour(startTime);
                        const endTime24 = convertTo24Hour(endTime);
                        
                        if (startTime24 && endTime24) {
                            const [startHours, startMinutes] = startTime24.split(':').map(Number);
                            const [endHours, endMinutes] = endTime24.split(':').map(Number);
                            
                            const startTotalMinutes = startHours * 60 + startMinutes;
                            let endTotalMinutes = endHours * 60 + endMinutes;
                            
                            // Handle overnight shift (end time is next day)
                            if (endTotalMinutes <= startTotalMinutes) {
                                endTotalMinutes += 24 * 60; // Add 24 hours
                            }
                            
                            const totalMinutes = endTotalMinutes - startTotalMinutes;
                            maxOTHours = (totalMinutes / 60).toFixed(2);
                        }
                    }
                    
                    // Editable duration for pending requests
                    const durationHours = parseFloat(data.ot_duration) || 0;
                    const wholeHours = Math.floor(durationHours);
                    const minutes = Math.round((durationHours - wholeHours) * 60);
                    
                    details.push(`<div class=\"bg-blue-50 p-3 rounded-md\">`+
                        `<div class=\"flex items-center justify-between mb-3\">`+
                            `<label class=\"text-xs font-medium text-blue-600\">Duration (Editable)</label>`+
                            `<div class=\"text-right\">`+
                                `<div class=\"text-xs text-gray-500\">Max OT: ${maxOTHours !== 'N/A' ? formatOvertimeDuration(parseFloat(maxOTHours)) : 'N/A'}</div>`+
                                `<div class=\"text-xs text-blue-600\">Current: ${formatOvertimeDuration(durationHours)}</div>`+
                            `</div>`+
                        `</div>`+
                        `<div class=\"flex items-center space-x-3\">`+
                            `<div class=\"flex items-center space-x-1\">`+
                                `<input type=\"number\" id=\"edit-hours-${data.request_id}\" min=\"0\" max=\"24\" value=\"${wholeHours}\" `+
                                `data-max-hours=\"${maxOTHours}\" data-original=\"${wholeHours}\" disabled `+
                                `oninput=\"validateOTDuration('${data.request_id}'); checkForChanges('${data.request_id}')\" `+
                                `class=\"w-14 h-8 text-center border border-gray-300 bg-gray-100 rounded text-sm font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400\">`+
                                `<span class=\"text-xs text-gray-500 font-medium\">hrs</span>`+
                            `</div>`+
                            `<span class=\"text-gray-400 font-bold\">:</span>`+
                            `<div class=\"flex items-center space-x-1\">`+
                                `<input type=\"number\" id=\"edit-minutes-${data.request_id}\" min=\"0\" max=\"59\" step=\"15\" value=\"${minutes}\" `+
                                `data-original=\"${minutes}\" disabled `+
                                `oninput=\"validateOTDuration('${data.request_id}'); checkForChanges('${data.request_id}')\" `+
                                `class=\"w-14 h-8 text-center border border-gray-300 bg-gray-100 rounded text-sm font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400\">`+
                                `<span class=\"text-xs text-gray-500 font-medium\">min</span>`+
                            `</div>`+
                        `</div>`+
                        `<div class=\"text-xs mt-2\" id=\"duration-validation-${data.request_id}\" style=\"min-height: 16px;\"></div>`+
                    `</div>`);
                } else {
                    // Static duration display for non-pending requests
                    details.push(`<div class=\"bg-blue-50 p-3 rounded-md\">`+
                        `<label class=\"block text-xs font-medium text-blue-600 mb-1\">Duration</label>`+
                        `<p class=\"text-blue-800 font-semibold\">${data.ot_duration} hours</p>`+
                    `</div>`);
                }
            }
            if (details.length) {
                // Add OT type as a badge in the header if available
                let otTypeBadge = '';
                if (data.ot_type) {
                    otTypeBadge = `<span class=\"inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2\">${data.ot_type}</span>`;
                }
                
                content += `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-700">Overtime Details</h4>
                            ${otTypeBadge}
                        </div>
                        <div class="space-y-3">${details.join('')}</div>
                    </div>
                `;
            }
            
            // Attachment section for overtime requests
            console.log('🕰️ Overtime attachment debug:', {
                attachmentValue: data.attachment,
                requestId: data.request_id,
                tableName: data.table_name,
                status: data.status,
                allAttachmentFields: {
                    attachment: data.attachment,
                    attachment_scr: data.attachment_scr,
                    attachment_lr: data.attachment_lr,
                    attachment_ot: data.attachment_ot
                }
            });
            content += createAttachmentSection(data.attachment, data.request_id, data.table_name, data.status);
        }
        
        // Employee reason (editable for pending requests)
        if (data.reason || (data.status && data.status.toLowerCase() === 'pending')) {
            const reasonText = data.reason || 'No reason provided';
            const isEditable = data.status && data.status.toLowerCase() === 'pending' && data.request_id;
            content += createInfoBlock('Employee Reason', reasonText, 'fa-comment-dots', 'text-sm leading-relaxed', 'bg-gray-50', isEditable, data.request_id, 'reason');
        }
        
        // Admin explanation (for declined/rejected requests)
        if (data.explanation && ['declined', 'rejected', 'cancelled'].includes(data.status.toLowerCase())) {
            content += createInfoBlock('Administrator Response', data.explanation, 'fa-user-shield', 'text-sm leading-relaxed text-red-700', 'bg-red-50 border-red-200');
        }
        
        // Directly replace content (no preservation to prevent duplicate attachment blocks)
        modalBody.innerHTML = content;
        
        // Mark modal as successfully loaded and protect from replacement
        modalBody.setAttribute('data-loaded', 'true');
        modalBody.setAttribute('data-load-timestamp', new Date().toISOString());
        
        console.log('✅ Modal content loaded successfully with attachments');
        
        // Initialize time displays if this is a time adjustment
        if (data.type && data.type.includes('Time Adjustment') && data.request_id) {
            setTimeout(() => {
                initializeTimeDisplay(data.request_id);
            }, 100);
        }
        
        // Verify attachment sections are present and protect them
        setTimeout(() => {
            const attachmentSectionsLoaded = modalBody.querySelectorAll('[data-attachment-section]');
            console.log('🔍 Attachment sections after modal load:', attachmentSectionsLoaded.length);
            if (attachmentSectionsLoaded.length > 0) {
                console.log('✅ Attachment sections successfully loaded in modal');
                
                // PROTECT each attachment section from removal
                attachmentSectionsLoaded.forEach(section => {
                    const requestId = section.getAttribute('data-attachment-section');
                    if (requestId && window.protectAttachmentSection) {
                        window.protectAttachmentSection(requestId);
                    }
                });
                
                // Skip deduplication for now to prevent removal
                // dedupeAttachmentSections();
            }
        }, 50);
        
        // Add Edit and Cancel buttons if applicable (status is 'pending' and we have request data)
        // Exclude Monthly Schedule Request and Schedule Swap Request from edit functionality
        const unsubmitContainer = document.getElementById('unsubmitButtonContainer');
        unsubmitContainer.innerHTML = '';
        
        if (data.status && data.status.toLowerCase() === 'pending' && 
            data.request_id && data.table_name &&
            !data.type.includes('Monthly Schedule Request') && 
            !data.type.includes('Schedule Swap')) {
            unsubmitContainer.innerHTML = `
                <div class="flex gap-2">
                    <button id="edit-save-btn-${data.request_id}" onclick="toggleEditMode('${data.type}', ${data.request_id}, '${data.table_name}', '${data.source_table}')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md transform active:scale-95 flex items-center" 
                            data-mode="view">
                        <i class="fas fa-edit mr-2"></i>Edit Form
                    </button>
                    <button onclick="cancelRequestFromModal(${data.request_id}, '${data.table_name}', '${data.source_table}')" 
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md transform active:scale-95 flex items-center">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            `;
        }
        
        // Show modal with animation
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Add protection against accidental data loss
        const hasAttachment = !!(data.attachment_scr && data.attachment_scr.trim());
        modal.setAttribute('data-has-attachments', hasAttachment);
        modal.setAttribute('data-request-id', data.request_id || '');
        
        // Trigger animation
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
        
    } catch (error) {
        console.error('Error parsing activity data:', error);
        console.error('Raw JSON that failed to parse:', dataJson);
        console.error('Error details:', error.message);
        
        // Show more detailed error message
        alert(`Error displaying activity details:\n\nError: ${error.message}\n\nCheck browser console for more details.`);
        
        // Try to show modal with error info
        const modal = document.getElementById('activityModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        if (modal && modalContent && modalTitle && modalBody) {
            // Don't replace content if modal was already successfully loaded
            if (modalBody.getAttribute('data-loaded') === 'true') {
                console.log('🛡️ Preventing error handler from replacing successfully loaded modal content');
                return;
            }
            
            modalTitle.textContent = 'Error Loading Details';
            modalBody.innerHTML = `
                <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                    <h4 class="text-sm font-bold text-red-700 mb-3">⚠️ JSON Parse Error</h4>
                    <p class="text-sm text-red-600 mb-2">Failed to parse activity data:</p>
                    <pre class="text-xs text-red-600 bg-red-100 p-2 rounded overflow-auto max-h-32">${error.message}</pre>
                    <details class="mt-3">
                        <summary class="text-xs cursor-pointer text-red-700 font-bold">Show Raw Data</summary>
                        <pre class="text-xs mt-2 bg-gray-100 p-2 rounded overflow-auto max-h-40">${dataJson}</pre>
                    </details>
                </div>
            `;
            
            // Show modal anyway
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
    }
}

function createInfoBlock(label, value, icon, valueClass = 'font-semibold', containerClass = 'bg-gray-50', isEditable = false, requestId = null, fieldType = '') {
    if (isEditable && requestId) {
        // Create editable version for reason fields
        if (fieldType === 'reason') {
            return `
                <div class="border border-gray-200 rounded-lg p-4 ${containerClass}" id="reason-block-${requestId}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <label class="text-sm font-medium text-gray-600 flex items-center md:justify-end">
                            <i class="fas ${icon} mr-2 text-gray-400"></i>
                            ${label}
                        </label>
                        <div class="md:col-span-2">
                            <textarea id="edit-reason-${requestId}" 
                                      class="w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-lg text-sm resize-none disabled:cursor-not-allowed" 
                                      rows="3" 
                                      disabled 
                                      data-original="${value.replace(/"/g, '&quot;')}" 
                                      oninput="checkForChanges(${requestId})">${value}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }
        // Create editable version for date range fields
        else if (fieldType === 'date_range') {
            return `
                <div class="border border-gray-200 rounded-lg p-4 ${containerClass}" id="date-range-block-${requestId}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <label class="text-sm font-medium text-gray-600 flex items-center md:justify-end">
                            <i class="fas ${icon} mr-2 text-gray-400"></i>
                            ${label}
                        </label>
                        <div class="md:col-span-2">
                            <input type="text" id="edit-date-range-${requestId}" 
                                   class="w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-lg text-sm font-semibold disabled:cursor-not-allowed" 
                                   disabled 
                                   value="${value}" 
                                   data-original="${value}" 
                                   placeholder="Select date range"
                                   oninput="checkForChanges(${requestId})">
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    // Standard horizontal layout for non-editable fields
    return `
        <div class="border border-gray-200 rounded-lg p-4 ${containerClass}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <label class="text-sm font-medium text-gray-600 flex items-center md:justify-end">
                    <i class="fas ${icon} mr-2 text-gray-400"></i>
                    ${label}
                </label>
                <div class="md:col-span-2">
                    <p class="text-gray-800 ${valueClass}">${value}</p>
                </div>
            </div>
        </div>
    `;
}

function createAttachmentSection(attachmentScr, requestId, tableName, status) {
    console.log('🔧 createAttachmentSection called with:', {
        attachmentScr: attachmentScr,
        requestId: requestId,
        tableName: tableName,
        status: status,
        timestamp: new Date().toISOString(),
        attachmentType: typeof attachmentScr,
        attachmentValue: attachmentScr,
        isEmptyString: attachmentScr === '',
        isNull: attachmentScr === null,
        isUndefined: attachmentScr === undefined
    });
    
    const isPending = status && status.toLowerCase() === 'pending';
    const hasAttachment = attachmentScr && attachmentScr.trim() !== '';
    
    // Store metadata and setup protection
    window.lastKnownAttachmentData = {
        attachmentScr: attachmentScr,
        requestId: requestId,
        tableName: tableName,
        status: status
    };
    console.log('💾 Stored attachment data globally:', window.lastKnownAttachmentData);
    
    // Setup self-preservation mechanism
    window.protectAttachmentSection = function(requestId) {
        const section = document.querySelector(`[data-attachment-section="${requestId}"]`);
        if (section) {
            // Override the remove method to prevent deletion
            const originalRemove = section.remove;
            section.remove = function() {
                console.log('🛡️ BLOCKING removal of attachment section:', requestId);
                // Instead of removing, just hide temporarily then restore
                this.style.opacity = '0.5';
                setTimeout(() => {
                    this.style.opacity = '1';
                    console.log('🔄 Attachment section visibility restored');
                }, 100);
                return false;
            };
            
            // Mark as protected
            section.setAttribute('data-removal-protected', 'true');
            section.style.position = 'relative';
            section.style.zIndex = '1000';
            
            console.log('🛡️ Attachment section protected from removal');
        }
    };
    
    console.log('📎 Attachment evaluation:', {
        isPending: isPending,
        hasAttachment: hasAttachment,
        attachmentScrLength: attachmentScr ? attachmentScr.length : 0,
        attachmentScrValue: attachmentScr,
        trimmedValue: attachmentScr ? attachmentScr.trim() : 'null'
    });
    
    let attachmentContent = '';
    
    if (hasAttachment) {
        // Get file name from path
        const fileName = attachmentScr.split('/').pop() || attachmentScr.split('\\').pop() || attachmentScr;
        const fileExtension = fileName.split('.').pop()?.toLowerCase() || '';
        
        // Determine file icon
        let fileIcon = 'fa-file';
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(fileExtension)) {
            fileIcon = 'fa-file-image';
        } else if (fileExtension === 'pdf') {
            fileIcon = 'fa-file-pdf';
        } else if (['doc', 'docx'].includes(fileExtension)) {
            fileIcon = 'fa-file-word';
        } else if (['xls', 'xlsx'].includes(fileExtension)) {
            fileIcon = 'fa-file-excel';
        }
        
        attachmentContent = `
            <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg p-2.5 mt-3">
                <div class="flex items-center space-x-2 flex-1 min-w-0">
                    <i class="fas ${fileIcon} text-blue-600 text-sm"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-700 truncate font-medium">${fileName}</p>
                    </div>
                </div>
                <div class="flex gap-1 ml-2">
                    <button onclick="viewAttachment('${attachmentScr}', '${fileName}', event)" 
                            class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${isPending ? `
                    <button onclick="showAttachmentUpload(${requestId}, '${tableName}')" 
                            class="px-2 py-1 text-xs bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
    } else {
        attachmentContent = `
            <div class="text-center text-gray-400 border border-dashed border-gray-300 rounded-lg p-2 mt-3">
                <p class="text-xs">No attachment</p>
                ${isPending && requestId ? `
                <button onclick="showAttachmentUpload('${requestId}', '${tableName}')" 
                        class="mt-1 text-xs text-blue-600 hover:text-blue-700">
                    <i class="fas fa-upload mr-1"></i>Add
                </button>
                ` : ''}
            </div>
        `;
    }
    
    return `
        <div class="border-t border-gray-200 pt-3 mt-3" data-attachment-section="${requestId}" data-persist="true">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-600 flex items-center">
                    <i class="fas fa-paperclip text-gray-400 mr-1 text-xs"></i>
                    Attachment
                </span>
            </div>
            ${attachmentContent}
            
            <!-- File upload section (hidden by default) -->
            ${isPending && requestId ? `
            <div id="upload-section-${requestId}" class="hidden mt-2 p-2.5 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="mb-2">
                    <input type="file" id="attachment-input-${requestId}" 
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                           class="w-full text-xs border border-gray-300 rounded p-1.5 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="text-xs text-gray-400 mt-1">Max 10MB</p>
                </div>
                <div class="flex gap-1.5">
                    <button onclick="uploadAttachment('${requestId}', '${tableName}')" 
                            class="px-2.5 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                        <i class="fas fa-upload mr-1"></i>Upload
                    </button>
                    <button onclick="hideAttachmentUpload('${requestId}')" 
                            class="px-2.5 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

function editRequestFromModal(requestType, requestId, tableName, sourceTable) {
    // Determine the edit page based on request type
    let editUrl = '';
    
    if (requestType.includes('Time Adjustment')) {
        editUrl = `test.php?edit=${requestId}`;
    } else if (requestType.includes('Leave')) {
        editUrl = `employee_leave.php?edit=${requestId}`;
    } else if (requestType.includes('Schedule')) {
        editUrl = `schedule_change.php?edit=${requestId}`;
    } else if (requestType.includes('Overtime')) {
        editUrl = `overtime.php?edit=${requestId}`;
    } else {
        alert('Edit functionality not available for this request type.');
        return;
    }
    
    // Close modal and redirect to edit page
    closeActivityModal();
    window.location.href = editUrl;
}

function cancelRequestFromModal(requestId, tableName, sourceTable) {
    // Check if this is a leave request
    if (tableName === 'leave_requests' || tableName.includes('leave')) {
        if (!confirm('Are you sure you want to cancel this leave request?\n\nThis action cannot be undone.')) {
            return;
        }
        
        // Handle leave request cancellation specially
        cancelLeaveRequest(requestId, tableName, sourceTable);
    } else {
        if (!confirm('Are you sure you want to cancel this request?\n\nThis action cannot be undone and will permanently remove the request from the system.')) {
            return;
        }
        
        // Call the existing unsubmitRequest function with sourceTable for other request types
        unsubmitRequest(requestId, tableName, sourceTable);
    }
    
    // Close the modal after initiating cancel (it will only reload if successful)
    closeActivityModal();
}

function cancelLeaveRequest(requestId, tableName, sourceTable) {
    console.log(`🚀 Starting cancelLeaveRequest - ID: ${requestId}, Table: ${tableName}, Source: ${sourceTable}`);
    
    // Show loading state with better visual feedback
    const buttons = document.querySelectorAll(`button[onclick*="${requestId}"]`);
    console.log(`Found ${buttons.length} buttons for request ${requestId}`);
    
    buttons.forEach(button => {
        const buttonText = button.textContent.trim();
        console.log(`Button text: "${buttonText}"`);
        if (buttonText.includes('Cancel') || buttonText.includes('Unsubmit')) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling...';
            button.classList.add('opacity-75', 'cursor-not-allowed');
            console.log(`Button updated to loading state`);
        }
    });
    
    console.log(`📤 Sending request to cancel leave - ID: ${requestId}, Table: ${tableName}, Source: ${sourceTable}`);
    
    // Make AJAX request to cancel leave request
    const requestData = {
        id: requestId,
        table: tableName,
        source_table: sourceTable
    };
    console.log('📦 Request payload:', requestData);
    
    fetch('../controller/cancel_leave_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('📨 Response received:', response.status, response.statusText);
        // Check if response is ok first
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📋 Cancel leave request response:', data); // Debug logging
        
        if (data.success === true) {
            // Show success message
            alert('✅ Leave request successfully cancelled!');
            
            // Add a fade out effect before reload
            buttons.forEach(button => {
                button.style.opacity = '0.5';
            });
            
            // Force page reload to reflect database changes
            setTimeout(() => {
                window.location.reload(true); // Force reload from server
            }, 500);
        } else {
            // Show error message and restore button - DO NOT RELOAD
            console.error('Cancel failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to cancel leave request'));
            
            // Restore button state
            buttons.forEach(button => {
                if (button.textContent.includes('Cancelling')) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-times mr-1"></i>Cancel Request';
                    button.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            });
        }
    })
    .catch(error => {
        console.error('❌ Network/Parse Error Details:', {
            error: error,
            message: error.message,
            stack: error.stack,
            requestId: requestId,
            tableName: tableName,
            sourceTable: sourceTable
        });
        
        alert(`🔥 Network error occurred: ${error.message}\n\nPlease check your connection and try again.`);
        
        // Restore button state
        buttons.forEach(button => {
            if (button.textContent.includes('Cancelling')) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-times mr-1"></i>Cancel Request';
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                console.log('Button state restored');
            }
        });
    });
}

function closeActivityModal() {
    console.log('❌ closeActivityModal called:', {
        timestamp: new Date().toISOString(),
        stack: new Error().stack
    });
    
    const modal = document.getElementById('activityModal');
    const modalContent = document.getElementById('modalContent');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        console.log('🚪 Modal fully closed');
    }, 300);
}

function getStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
        case 'declined':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

function getStatusIcon(status) {
    return '';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('activityModal');
    if (event.target === modal) {
        closeActivityModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('activityModal');
        if (!modal.classList.contains('hidden')) {
            closeActivityModal();
        }
    }
});

function unsubmitRequest(requestId, tableName, sourceTable = null) {
    // Enhanced confirmation dialog with better styling
    const confirmed = confirm('⚠️ Are you sure you want to cancel this request?\n\nThis action cannot be undone and will permanently remove the request from the system.');
    
    if (!confirmed) {
        return;
    }
    
    // Show loading state with better visual feedback
    const buttons = document.querySelectorAll(`button[onclick*="${requestId}"][onclick*="${tableName}"]`);
    buttons.forEach(button => {
        if (button.textContent.trim().includes('Cancel') || button.textContent.trim().includes('Unsubmit')) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling...';
            button.classList.add('opacity-75', 'cursor-not-allowed');
        }
    });
    
    // Use sourceTable if provided, otherwise use tableName
    let tableToUse = tableName; // default to tableName
    
    if (sourceTable) {
        // Map source_table to actual table names
        if (sourceTable === 'pending') {
            // For pending requests, use the base table name
            tableToUse = tableName;
        } else if (sourceTable === 'post' || sourceTable === 'approved') {
            // For processed requests, add 'post_' prefix if not already there
            if (tableName && !tableName.startsWith('post_')) {
                tableToUse = 'post_' + tableName;
            } else {
                tableToUse = tableName;
            }
        } else {
            // For other cases, use tableName as is
            tableToUse = tableName;
        }
    }
    
    console.log(`Unsubmit mapping - Original table: ${tableName}, Source: ${sourceTable}, Final table: ${tableToUse}`);
    
    // Make AJAX request to unsubmit
    fetch('../unsubmit_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: requestId,
            table: tableToUse
        })
    })
    .then(response => {
        // Check if response is ok first
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Unsubmit response:', data); // Debug logging
        
        if (data.success === true) {
            // Show success message with better styling
            alert('✅ Request successfully cancelled! The page will now refresh.');
            
            // Add a fade out effect before reload
            buttons.forEach(button => {
                if (button.textContent.includes('Cancelling')) {
                    const row = button.closest('tr');
                    if (row) {
                        row.style.transition = 'all 0.5s ease-out';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(100px)';
                    }
                }
            });
            
            // Force page reload to reflect database changes
            setTimeout(() => {
                window.location.reload(true); // Force reload from server
            }, 500);
        } else {
            // Show error message and restore button - DO NOT RELOAD
            console.error('Cancel failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to cancel request'));
            
            // Restore button state
            buttons.forEach(button => {
                if (button.textContent.includes('Cancelling')) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-times mr-1.5"></i>Cancel';
                    button.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            });
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred. Please check your connection and try again.');
        
        // Restore button state
        buttons.forEach(button => {
            if (button.textContent.includes('Cancelling')) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-times mr-1.5"></i>Cancel';
                button.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    });
}

// Function to update time adjustment
function updateTimeAdjustment(requestId, tableName, timeInValue, timeOutValue) {
    if (!confirm(`Update time adjustment:\nTime In: ${timeInValue}\nTime Out: ${timeOutValue}?`)) {
        return;
    }
    
    // Show loading state
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const originalText = editSaveBtn ? editSaveBtn.innerHTML : '';
    if (editSaveBtn) {
        editSaveBtn.disabled = true;
        editSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    }
    
    const updateData = {
        request_id: requestId,
        table_name: tableName,
        requested_time_in: timeInValue,
        requested_time_out: timeOutValue
    };
    
    console.log('Updating time adjustment:', updateData);
    
    // Make AJAX request to update time adjustment
    fetch('../controller/update_time_adjustment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Time adjustment update response:', data);
        
        if (data.success) {
            alert('✅ Time adjustment updated successfully!');
            
            // Update original values to reflect the new saved state
            const timeInHidden = document.getElementById(`edit-time-in-${requestId}`);
            const timeOutHidden = document.getElementById(`edit-time-out-${requestId}`);
            
            if (timeInHidden) {
                timeInHidden.setAttribute('data-original', timeInValue);
            }
            if (timeOutHidden) {
                timeOutHidden.setAttribute('data-original', timeOutValue);
            }
            
            // Reset to view mode
            resetToViewMode(requestId);
            
            // Refresh the page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Time adjustment update failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to update time adjustment'));
            
            // Restore button state
            if (editSaveBtn) {
                editSaveBtn.disabled = false;
                editSaveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message);
        
        // Restore button state
        if (editSaveBtn) {
            editSaveBtn.disabled = false;
            editSaveBtn.innerHTML = originalText;
        }
    });
}

// Function to update schedule change request
function updateScheduleChange(requestId, tableName, newScheduleId, newDateRange = null) {
    // Get the schedule display name for confirmation
    const schedule = findScheduleById(newScheduleId);
    let scheduleDisplay = 'Unknown Schedule';
    if (schedule) {
        const timeInDisplay = formatTime12Hour(schedule.time_in);
        const timeOutDisplay = formatTime12Hour(schedule.time_out);
        scheduleDisplay = `${timeInDisplay} - ${timeOutDisplay}`;
    }
    
    if (!confirm(`Update schedule change to: "${scheduleDisplay}"${newDateRange ? ` for period: ${newDateRange}` : ''}?`)) {
        return;
    }
    
    // Show loading state
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const originalText = editSaveBtn ? editSaveBtn.innerHTML : '';
    if (editSaveBtn) {
        editSaveBtn.disabled = true;
        editSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    }
    
    const updateData = {
        request_id: requestId,
        table_name: tableName,
        new_schedule: scheduleDisplay,  // Send the schedule display format that controller expects
        work_schedule_id: newScheduleId,  // Also send the ID for reference
        new_date_range: newDateRange
    };
    
    console.log('Updating schedule change:', updateData);
    
    // Make AJAX request to update schedule change
    fetch('../controller/update_schedule_change.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Schedule change update response:', data);
        
        if (data.success) {
            alert('✅ Schedule change updated successfully!');
            
            // Update original values to reflect the new saved state
            const scheduleHidden = document.getElementById(`edit-schedule-hidden-${requestId}`);
            if (scheduleHidden) {
                scheduleHidden.setAttribute('data-original', newScheduleId);
                scheduleHidden.value = newScheduleId;
            }
            
            const dateRangeInput = document.getElementById(`edit-date-range-${requestId}`);
            if (dateRangeInput && newDateRange) {
                dateRangeInput.setAttribute('data-original', newDateRange);
                dateRangeInput.value = newDateRange;
            }
            
            // Update the display in the modal to show the new schedule
            const scheduleSelect = document.getElementById(`edit-schedule-${requestId}`);
            if (scheduleSelect) {
                scheduleSelect.value = newScheduleId;
                
                // Update the static display as well
                const staticDisplay = document.querySelector(`#schedule-change-block-${requestId} .text-gray-800`);
                if (staticDisplay && scheduleDisplay) {
                    staticDisplay.textContent = scheduleDisplay;
                }
            }
            
            console.log('Schedule updated in database:', data);
            
            // Reset to view mode
            resetToViewMode(requestId);
            
            // Refresh the page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Schedule change update failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to update schedule change'));
            
            // Restore button state
            if (editSaveBtn) {
                editSaveBtn.disabled = false;
                editSaveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message);
        
        // Restore button state
        if (editSaveBtn) {
            editSaveBtn.disabled = false;
            editSaveBtn.innerHTML = originalText;
        }
    });
}

// Function to update request reason
function updateRequestReason(requestId, tableName, newReason) {
    if (!confirm(`Update reason to: "${newReason}"?`)) {
        return;
    }
    
    // Show loading state
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const originalText = editSaveBtn ? editSaveBtn.innerHTML : '';
    if (editSaveBtn) {
        editSaveBtn.disabled = true;
        editSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    }
    
    // Determine the reason field name based on request type
    let reasonField = 'reason';
    if (tableName && tableName.includes('ot_requests')) {
        reasonField = 'ot_reason';
    }
    
    const updateData = {
        request_id: requestId,
        table_name: tableName,
        field_name: reasonField,
        new_value: newReason
    };
    
    console.log('Updating reason:', updateData);
    
    // Make AJAX request to update reason
    fetch('../controller/update_request_field.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Reason update response:', data);
        
        if (data.success) {
            alert('✅ Reason updated successfully!');
            
            // Update original value to reflect the new saved state
            const reasonTextarea = document.getElementById(`edit-reason-${requestId}`);
            if (reasonTextarea) {
                reasonTextarea.setAttribute('data-original', newReason);
            }
            
            // Reset to view mode
            resetToViewMode(requestId);
            
            // Refresh the page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Reason update failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to update reason'));
            
            // Restore button state
            if (editSaveBtn) {
                editSaveBtn.disabled = false;
                editSaveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message);
        
        // Restore button state
        if (editSaveBtn) {
            editSaveBtn.disabled = false;
            editSaveBtn.innerHTML = originalText;
        }
    });
}

// Function to update overtime duration
function updateOvertimeDuration(requestId, tableName, callback = null) {
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const validationDiv = document.getElementById(`duration-validation-${requestId}`);
    
    if (!hoursInput || !minutesInput) {
        alert('Error: Duration inputs not found');
        return;
    }
    
    const hours = parseInt(hoursInput.value) || 0;
    const minutes = parseInt(minutesInput.value) || 0;
    const maxAllowedHours = parseFloat(hoursInput.getAttribute('data-max-hours')) || 24;
    
    // Allow exact minute values without rounding
    // Ensure minutes stay within valid range (0-59)
    if (minutes > 59) {
        minutesInput.value = minutes - 60;
        hoursInput.value = hours + 1;
    }
    
    // Calculate total hours as decimal
    const totalHours = hours + (parseInt(minutesInput.value) / 60);
    
    if (totalHours === 0) {
        alert('Please select a valid duration (minimum 1 minute)');
        return;
    }
    
    // Validate against maximum allowed hours (based on OT window)
    if (totalHours > maxAllowedHours) {
        alert(`Duration cannot exceed ${formatOvertimeDuration(maxAllowedHours)} (based on Start OT to End OT window)`);
        // Reset to maximum allowed
        const maxWholeHours = Math.floor(maxAllowedHours);
        const maxMinutes = Math.round((maxAllowedHours - maxWholeHours) * 60);
        hoursInput.value = maxWholeHours;
        minutesInput.value = maxMinutes;
        return;
    }
    
    // Show confirmation
    const durationText = formatOvertimeDuration(totalHours);
    if (!confirm(`Update overtime duration to ${durationText}?`)) {
        return;
    }
    
    // Show loading state on the main edit/save button
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const originalText = editSaveBtn ? editSaveBtn.innerHTML : '';
    if (editSaveBtn) {
        editSaveBtn.disabled = true;
        editSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    }
    
    // Debug: Log the data being sent
    const updateData = {
        request_id: requestId,
        table_name: tableName,
        ot_duration: totalHours.toFixed(2)
    };
    console.log('Sending update data:', updateData);
    
    // Make AJAX request to update duration (using debug version)
    fetch('../controller/update_overtime_duration_debug.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            alert('✅ Overtime duration updated successfully!');
            
            // Update original values to reflect the new saved state
            hoursInput.setAttribute('data-original', Math.floor(totalHours));
            minutesInput.setAttribute('data-original', Math.round((totalHours - Math.floor(totalHours)) * 60));
            
            // Update the validation message to show success
            if (validationDiv) {
                validationDiv.innerHTML = `<span class="text-green-600 text-xs">✅ Updated to ${formatOvertimeDuration(totalHours)}</span>`;
            }
            
            // If there's a callback, execute it instead of resetting to view mode
            if (callback && typeof callback === 'function') {
                callback();
            } else {
                // Reset to view mode
                resetToViewMode(requestId);
                
                // Refresh the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            console.error('Update failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to update duration') + 
                  '\n\nDebug info: ' + JSON.stringify(data, null, 2));
            
            // Restore button state
            if (editSaveBtn) {
                editSaveBtn.disabled = false;
                editSaveBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message + 
              '\n\nPlease check browser console for details and try again.');
        
        // Restore button state
        if (editSaveBtn) {
            editSaveBtn.disabled = false;
            editSaveBtn.innerHTML = originalText;
        }
    });
}

// Function to convert 12-hour time format to 24-hour format
function convertTo24Hour(time12h) {
    if (!time12h) return '';
    
    const [time, modifier] = time12h.split(/\s/);
    let [hours, minutes] = time.split(':');
    
    if (hours === '12') {
        hours = '00';
    }
    
    if (modifier && modifier.toUpperCase() === 'PM') {
        hours = parseInt(hours, 10) + 12;
    }
    
    return `${hours}:${minutes}:00`;
}

// Helper functions for clock UI
function extractHour(time12h) {
    console.log('extractHour called with:', time12h);
    if (!time12h || time12h === '') {
        console.log('No time provided, returning empty for user choice');
        return '';
    }
    const [time] = time12h.split(/\s/);
    if (!time) {
        console.log('No time part found, returning empty');
        return '';
    }
    const [hours] = time.split(':');
    const result = hours || '08';
    console.log('Extracted hour:', result);
    return result;
}

function extractMinute(time12h) {
    console.log('extractMinute called with:', time12h);
    if (!time12h || time12h === '') return '';
    const [time] = time12h.split(/\s/);
    if (!time) return '';
    const [, minutes] = time.split(':');
    const result = minutes || '00';
    console.log('Extracted minute:', result);
    return result;
}

function extractAMPM(time12h) {
    console.log('extractAMPM called with:', time12h);
    if (!time12h || time12h === '') return '';
    const parts = time12h.split(/\s/);
    const result = parts[1] ? parts[1].toUpperCase() : 'AM';
    console.log('Extracted AM/PM:', result);
    return result;
}

function generateHourOptions(selectedTime) {
    console.log('generateHourOptions called with selectedTime:', selectedTime);
    const currentHour = extractHour(selectedTime);
    console.log('generateHourOptions - extracted currentHour:', currentHour);
    let options = '';
    
    // If no time selected, add placeholder
    if (!currentHour || currentHour === '') {
        options += '<option value="" selected disabled>--</option>';
    }
    
    for (let i = 1; i <= 12; i++) {
        const hour = i.toString().padStart(2, '0');
        // Normalize both values for comparison - remove leading zeros
        const normalizedHour = parseInt(hour, 10).toString();
        const normalizedCurrentHour = parseInt(currentHour, 10).toString();
        const selected = normalizedHour === normalizedCurrentHour ? 'selected' : '';
        if (selected) {
            console.log('Hour', hour, 'is selected because', normalizedHour, 'matches', normalizedCurrentHour);
        }
        options += `<option value="${hour}" ${selected}>${hour}</option>`;
    }
    console.log('Generated hour options:', options);
    return options;
}

function generateMinuteOptions(selectedTime) {
    const currentMinute = extractMinute(selectedTime);
    console.log('generateMinuteOptions - extracted currentMinute:', currentMinute);
    let options = '';
    
    // If no time selected, add placeholder
    if (!currentMinute || currentMinute === '') {
        options += '<option value="" selected disabled>--</option>';
    }
    
    // Generate all minute options (00-59) to allow exact time selection
    for (let i = 0; i < 60; i++) {
        const minute = i.toString().padStart(2, '0');
        const selected = minute === currentMinute ? 'selected' : '';
        if (selected) {
            console.log('Minute', minute, 'is selected (exact match for', currentMinute, ')');
        }
        options += `<option value="${minute}" ${selected}>${minute}</option>`;
    }
    return options;
}

function generateAMPMOptions(selectedTime) {
    const currentAMPM = extractAMPM(selectedTime);
    let options = '';
    
    // If no time selected, add placeholder
    if (!currentAMPM || currentAMPM === '') {
        options += '<option value="" selected disabled>--</option>';
    }
    
    options += `
        <option value="AM" ${currentAMPM === 'AM' ? 'selected' : ''}>AM</option>
        <option value="PM" ${currentAMPM === 'PM' ? 'selected' : ''}>PM</option>
    `;
    
    return options;
}

// Generate schedule options for schedule change requests
function generateScheduleOptions(requestedTimeIn, requestedTimeOut, currentScheduleId = null) {
    console.log('generateScheduleOptions called with:', requestedTimeIn, requestedTimeOut, 'currentScheduleId:', currentScheduleId);
    
    // Use ALL work schedules from database (no filtering by ID)
    const schedules = [...workSchedules]; // Create a copy to avoid modifying original
    
    // Sort schedules by time_in ascending (matching schedule_change_form.php)
    schedules.sort((a, b) => {
        const timeA = new Date('1970-01-01 ' + a.time_in);
        const timeB = new Date('1970-01-01 ' + b.time_in);
        return timeA - timeB;
    });
    
    // Convert requested times to compare format
    const requestedSchedule = `${requestedTimeIn} - ${requestedTimeOut}`;
    console.log('Looking for schedule match:', requestedSchedule);
    console.log('Available schedules:', schedules.length);
    
    let options = '<option value="" disabled>Choose work hours</option>';
    let foundMatch = false;
    
    schedules.forEach(schedule => {
        // Skip schedules with invalid time data
        if (!schedule.time_in || !schedule.time_out) {
            console.warn('⚠️ Skipping schedule with missing time data:', schedule);
            return; // Skip this iteration
        }
        
        // Convert 24-hour to 12-hour format for display (matching schedule_change_form.php format)
        const timeInDisplay = formatTime12Hour(schedule.time_in);
        const timeOutDisplay = formatTime12Hour(schedule.time_out);
        
        // Skip if formatting failed
        if (timeInDisplay === 'N/A' || timeOutDisplay === 'N/A') {
            console.warn('⚠️ Skipping schedule with invalid time format:', schedule);
            return; // Skip this iteration
        }
        
        const scheduleDisplay = `${timeInDisplay} - ${timeOutDisplay}`;
        
        // Check if this matches the requested schedule OR if this is the current schedule ID
        let isSelected = false;
        if (currentScheduleId && schedule.id == currentScheduleId) {
            isSelected = true;
            foundMatch = true;
            console.log('✅ Matched by schedule ID:', schedule.id, scheduleDisplay);
        } else if (scheduleDisplay === requestedSchedule) {
            isSelected = true;
            foundMatch = true;
            console.log('✅ Matched by time display:', scheduleDisplay);
        }
        
        const selectedAttr = isSelected ? 'selected' : '';
        
        options += `<option value="${schedule.id}" data-schedule="${scheduleDisplay}" ${selectedAttr}>${scheduleDisplay}</option>`;
    });
    
    // Debug: Log if requested schedule was not found
    if (!foundMatch && currentScheduleId) {
        console.warn('⚠️ Requested schedule not found in available schedules!');
        console.warn('Looking for: ID=' + currentScheduleId + ', Time=' + requestedSchedule);
        console.warn('Available schedule IDs:', schedules.map(s => s.id));
    }
    
    return options;
}

// Helper function to convert 24-hour time to 12-hour format
function formatTime12Hour(time24) {
    // Handle null, undefined, or empty values
    if (!time24 || time24 === null || time24 === undefined || time24.trim() === '') {
        console.warn('⚠️ formatTime12Hour received invalid time:', time24);
        return 'N/A';
    }
    
    try {
        const [hours, minutes] = time24.split(':');
        
        // Validate that we have valid hours and minutes
        if (!hours || !minutes) {
            console.warn('⚠️ Invalid time format:', time24);
            return 'N/A';
        }
        
        const hour12 = parseInt(hours);
        const ampm = hour12 >= 12 ? 'PM' : 'AM';
        const displayHour = hour12 === 0 ? 12 : (hour12 > 12 ? hour12 - 12 : hour12);
        return `${displayHour}:${minutes} ${ampm}`;
    } catch (error) {
        console.error('❌ Error formatting time:', time24, error);
        return 'N/A';
    }
}

function updateTimeDisplay(requestId, timeType) {
    const hourSelect = document.getElementById(`edit-time-${timeType}-hour-${requestId}`);
    const minuteSelect = document.getElementById(`edit-time-${timeType}-minute-${requestId}`);
    const ampmSelect = document.getElementById(`edit-time-${timeType}-ampm-${requestId}`);
    const hiddenInput = document.getElementById(`edit-time-${timeType}-${requestId}`);
    
    if (hourSelect && minuteSelect && ampmSelect && hiddenInput) {
        const hour = hourSelect.value;
        const minute = minuteSelect.value;
        const ampm = ampmSelect.value;
        
        const timeString = `${hour}:${minute} ${ampm}`;
        hiddenInput.value = timeString;
        
        console.log(`Updated ${timeType} time for request ${requestId}: ${timeString}`);
    }
}

// Initialize time display after modal opens
function initializeTimeDisplay(requestId) {
    updateTimeDisplay(requestId, 'in');
    updateTimeDisplay(requestId, 'out');
}

// Function to update schedule display when dropdown changes
function updateScheduleDisplay(requestId) {
    const scheduleSelect = document.getElementById(`edit-schedule-${requestId}`);
    const hiddenInput = document.getElementById(`edit-schedule-hidden-${requestId}`);
    
    if (scheduleSelect && hiddenInput) {
        // Store the actual schedule ID value
        const scheduleId = scheduleSelect.value;
        hiddenInput.value = scheduleId;
        
        // Get the display text for logging
        const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
        const scheduleDisplay = selectedOption.getAttribute('data-schedule') || selectedOption.textContent;
        
        console.log(`Updated schedule for request ${requestId}: ID=${scheduleId}, Display=${scheduleDisplay}`);
        
        // Check for changes to enable save button
        checkForChanges(requestId);
    }
}

// Function to validate OT duration in real-time
function validateOTDuration(requestId) {
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const validationDiv = document.getElementById(`duration-validation-${requestId}`);
    
    if (!hoursInput || !minutesInput || !validationDiv) return;
    
    const hours = parseInt(hoursInput.value) || 0;
    const minutes = parseInt(minutesInput.value) || 0;
    const maxAllowedHours = parseFloat(hoursInput.getAttribute('data-max-hours')) || 24;
    
    // Calculate total hours as decimal
    const totalHours = hours + (minutes / 60);
    
    if (totalHours > maxAllowedHours) {
        validationDiv.innerHTML = `<span class="text-red-500 text-xs">⚠️ Exceeds limit by ${formatOvertimeDuration(totalHours - maxAllowedHours)}</span>`;
        hoursInput.classList.add('border-red-300', 'bg-red-50');
        minutesInput.classList.add('border-red-300', 'bg-red-50');
        hoursInput.classList.remove('border-green-300', 'bg-green-50');
        minutesInput.classList.remove('border-green-300', 'bg-green-50');
    } else if (totalHours > 0) {
        validationDiv.innerHTML = `<span class="text-green-600 text-xs">✓ ${formatOvertimeDuration(totalHours)} (${formatOvertimeDuration(maxAllowedHours - totalHours)} remaining)</span>`;
        hoursInput.classList.remove('border-red-300', 'bg-red-50');
        minutesInput.classList.remove('border-red-300', 'bg-red-50');
        hoursInput.classList.add('border-green-300', 'bg-green-50');
        minutesInput.classList.add('border-green-300', 'bg-green-50');
    } else {
        validationDiv.innerHTML = `<span class="text-gray-500 text-xs">Enter duration (exact minutes)</span>`;
        hoursInput.classList.remove('border-red-300', 'border-green-300', 'bg-red-50', 'bg-green-50');
        minutesInput.classList.remove('border-red-300', 'border-green-300', 'bg-red-50', 'bg-green-50');
    }
}

// Function to format overtime duration for display
function formatOvertimeDuration(hours) {
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
        return wholeHours + (wholeHours > 1 ? ' hrs' : ' hr') + ' ' + minutes + ' min';
    }
}

// Function to toggle between edit and save mode
function toggleEditMode(requestType, requestId, tableName, sourceTable) {
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const currentMode = editSaveBtn.getAttribute('data-mode');
    
    if (currentMode === 'view') {
        // Enable edit mode
        enableEditMode(requestId);
        editSaveBtn.setAttribute('data-mode', 'edit');
        editSaveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Changes';
        editSaveBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        editSaveBtn.classList.add('bg-green-600', 'hover:bg-green-700');
    } else {
        // Save changes and return to view mode
        saveAllChanges(requestType, requestId, tableName, sourceTable);
    }
}

// Function to enable edit mode for all editable fields
function enableEditMode(requestId) {
    // Enable overtime duration inputs if they exist
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    
    if (hoursInput && minutesInput) {
        // Enable inputs
        hoursInput.disabled = false;
        minutesInput.disabled = false;
        
        // Update styling to show inputs are editable
        hoursInput.classList.remove('border-gray-300', 'bg-gray-100');
        hoursInput.classList.add('border-blue-300', 'bg-white');
        minutesInput.classList.remove('border-gray-300', 'bg-gray-100');
        minutesInput.classList.add('border-blue-300', 'bg-white');
        
        // Update label colors
        const nextElements = hoursInput.parentElement.querySelectorAll('span');
        nextElements.forEach(span => {
            if (span.textContent === 'hrs' || span.textContent === 'min') {
                span.classList.remove('text-gray-500');
                span.classList.add('text-blue-600');
            }
        });
        
        // Update the separator colon
        const colonElement = hoursInput.parentElement.parentElement.querySelector('span');
        if (colonElement && colonElement.textContent === ':') {
            colonElement.classList.remove('text-gray-400');
            colonElement.classList.add('text-blue-400');
        }
        
        // Initialize validation
        validateOTDuration(requestId);
    }
    
    // Enable reason textarea if it exists
    const reasonTextarea = document.getElementById(`edit-reason-${requestId}`);
    if (reasonTextarea) {
        reasonTextarea.disabled = false;
        reasonTextarea.classList.remove('border-gray-300', 'bg-gray-100');
        reasonTextarea.classList.add('border-blue-300', 'bg-white', 'focus:ring-2', 'focus:ring-blue-400');
        
        // Update the container styling to show it's editable
        const reasonBlock = document.getElementById(`reason-block-${requestId}`);
        if (reasonBlock) {
            reasonBlock.classList.remove('bg-gray-50');
            reasonBlock.classList.add('bg-blue-50', 'border-blue-200');
        }
    }
    
    // Enable time adjustment clock UI if it exists
    const timeAdjustmentBlock = document.getElementById(`time-adjustment-block-${requestId}`);
    if (timeAdjustmentBlock) {
        // Enable time in selects
        const timeInHour = document.getElementById(`edit-time-in-hour-${requestId}`);
        const timeInMinute = document.getElementById(`edit-time-in-minute-${requestId}`);
        const timeInAMPM = document.getElementById(`edit-time-in-ampm-${requestId}`);
        
        if (timeInHour && timeInMinute && timeInAMPM) {
            timeInHour.disabled = false;
            timeInMinute.disabled = false;
            timeInAMPM.disabled = false;
            
            // Update styling to show they're editable
            timeInHour.classList.remove('border-gray-300', 'bg-gray-100');
            timeInHour.classList.add('border-blue-300', 'bg-white');
            timeInMinute.classList.remove('border-gray-300', 'bg-gray-100');
            timeInMinute.classList.add('border-blue-300', 'bg-white');
            timeInAMPM.classList.remove('border-gray-300', 'bg-gray-100');
            timeInAMPM.classList.add('border-blue-300', 'bg-white');
        }
        
        // Enable time out selects
        const timeOutHour = document.getElementById(`edit-time-out-hour-${requestId}`);
        const timeOutMinute = document.getElementById(`edit-time-out-minute-${requestId}`);
        const timeOutAMPM = document.getElementById(`edit-time-out-ampm-${requestId}`);
        
        if (timeOutHour && timeOutMinute && timeOutAMPM) {
            timeOutHour.disabled = false;
            timeOutMinute.disabled = false;
            timeOutAMPM.disabled = false;
            
            // Update styling to show they're editable
            timeOutHour.classList.remove('border-gray-300', 'bg-gray-100');
            timeOutHour.classList.add('border-green-300', 'bg-white');
            timeOutMinute.classList.remove('border-gray-300', 'bg-gray-100');
            timeOutMinute.classList.add('border-green-300', 'bg-white');
            timeOutAMPM.classList.remove('border-gray-300', 'bg-gray-100');
            timeOutAMPM.classList.add('border-green-300', 'bg-white');
        }
        
        // Update container styling
        timeAdjustmentBlock.classList.add('border-blue-200');
    }
    
    // Enable schedule change UI if it exists
    const scheduleChangeBlock = document.getElementById(`schedule-change-block-${requestId}`);
    if (scheduleChangeBlock) {
        const scheduleSelect = document.getElementById(`edit-schedule-${requestId}`);
        
        if (scheduleSelect) {
            scheduleSelect.disabled = false;
            
            // Update styling to show it's editable
            scheduleSelect.classList.remove('border-gray-300', 'bg-gray-100');
            scheduleSelect.classList.add('border-green-300', 'bg-white');
        }
        
        // Update container styling
        scheduleChangeBlock.classList.add('border-blue-200');
    }
    
    // Enable date range input if it exists
    const dateRangeInput = document.getElementById(`edit-date-range-${requestId}`);
    if (dateRangeInput) {
        dateRangeInput.disabled = false;
        dateRangeInput.classList.remove('border-gray-300', 'bg-gray-100');
        dateRangeInput.classList.add('border-blue-300', 'bg-white', 'focus:ring-2', 'focus:ring-blue-400');
        
        // Update the container styling to show it's editable
        const dateRangeBlock = document.getElementById(`date-range-block-${requestId}`);
        if (dateRangeBlock) {
            dateRangeBlock.classList.remove('bg-gray-50');
            dateRangeBlock.classList.add('bg-blue-50', 'border-blue-200');
        }
    }
    
    // Add any other field types here (e.g., leave dates, etc.)
    // This is where you'd enable editing for other request types
}

// Function to save all changes made in edit mode
function saveAllChanges(requestType, requestId, tableName, sourceTable) {
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const reasonTextarea = document.getElementById(`edit-reason-${requestId}`);
    const timeInHidden = document.getElementById(`edit-time-in-${requestId}`);
    const timeOutHidden = document.getElementById(`edit-time-out-${requestId}`);
    
    let hasOvertimeChanges = false;
    let hasReasonChanges = false;
    let hasTimeAdjustmentChanges = false;
    let hasScheduleChanges = false;
    
    // Check overtime duration changes
    if (hoursInput && minutesInput && (!hoursInput.disabled)) {
        const originalHours = parseInt(hoursInput.getAttribute('data-original')) || 0;
        const originalMinutes = parseInt(minutesInput.getAttribute('data-original')) || 0;
        const currentHours = parseInt(hoursInput.value) || 0;
        const currentMinutes = parseInt(minutesInput.value) || 0;
        
        hasOvertimeChanges = (originalHours !== currentHours || originalMinutes !== currentMinutes);
    }
    
    // Check reason changes
    if (reasonTextarea && (!reasonTextarea.disabled)) {
        const originalReason = reasonTextarea.getAttribute('data-original') || '';
        const currentReason = reasonTextarea.value.trim() || '';
        
        hasReasonChanges = (originalReason !== currentReason);
    }
    
    // Check time adjustment changes
    if (timeInHidden && timeOutHidden) {
        const originalTimeIn = timeInHidden.getAttribute('data-original') || '';
        const originalTimeOut = timeOutHidden.getAttribute('data-original') || '';
        const currentTimeIn = timeInHidden.value || '';
        const currentTimeOut = timeOutHidden.value || '';
        
        hasTimeAdjustmentChanges = (originalTimeIn !== currentTimeIn) || (originalTimeOut !== currentTimeOut);
    }
    
    // Check schedule changes
    const scheduleHidden = document.getElementById(`edit-schedule-hidden-${requestId}`);
    const dateRangeInput = document.getElementById(`edit-date-range-${requestId}`);
    if (scheduleHidden || dateRangeInput) {
        let scheduleChanged = false;
        let dateRangeChanged = false;
        
        if (scheduleHidden) {
            const originalSchedule = scheduleHidden.getAttribute('data-original') || '';
            const currentSchedule = scheduleHidden.value || '';
            scheduleChanged = (originalSchedule !== currentSchedule);
        }
        
        if (dateRangeInput) {
            const originalDateRange = dateRangeInput.getAttribute('data-original') || '';
            const currentDateRange = dateRangeInput.value || '';
            dateRangeChanged = (originalDateRange !== currentDateRange);
        }
        
        hasScheduleChanges = scheduleChanged || dateRangeChanged;
    }
    
    // Handle multiple changes sequentially
    if (hasOvertimeChanges && hasReasonChanges) {
        // Save overtime duration first, then reason
        updateOvertimeDuration(requestId, tableName, () => {
            updateRequestReason(requestId, tableName, reasonTextarea.value.trim());
        });
        return;
    }
    
    if (hasTimeAdjustmentChanges && hasReasonChanges) {
        // Save time adjustment first, then reason
        updateTimeAdjustment(requestId, tableName, timeInHidden.value, timeOutHidden.value);
        // Note: Reason update will need to be handled after time adjustment completes
        return;
    }
    
    // Handle single field changes
    if (hasOvertimeChanges) {
        updateOvertimeDuration(requestId, tableName);
        return;
    }
    
    if (hasTimeAdjustmentChanges) {
        updateTimeAdjustment(requestId, tableName, timeInHidden.value, timeOutHidden.value);
        return;
    }
    
    if (hasReasonChanges) {
        updateRequestReason(requestId, tableName, reasonTextarea.value.trim());
        return;
    }
    
    if (hasScheduleChanges) {
        const newScheduleId = scheduleHidden ? scheduleHidden.value : null;
        const newDateRange = dateRangeInput ? dateRangeInput.value : null;
        updateScheduleChange(requestId, tableName, newScheduleId, newDateRange);
        return;
    }
    
    // If no changes were made, just return to view mode
    resetToViewMode(requestId);
}

// Function to reset back to view mode
function resetToViewMode(requestId) {
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const reasonTextarea = document.getElementById(`edit-reason-${requestId}`);
    
    if (editSaveBtn) {
        editSaveBtn.setAttribute('data-mode', 'view');
        editSaveBtn.innerHTML = '<i class="fas fa-edit mr-2"></i>Edit Form';
        editSaveBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
        editSaveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
    }
    
    if (hoursInput && minutesInput) {
        // Disable inputs
        hoursInput.disabled = true;
        minutesInput.disabled = true;
        
        // Reset styling to disabled state
        hoursInput.classList.remove('border-blue-300', 'bg-white', 'border-red-300', 'bg-red-50', 'border-green-300', 'bg-green-50');
        hoursInput.classList.add('border-gray-300', 'bg-gray-100');
        minutesInput.classList.remove('border-blue-300', 'bg-white', 'border-red-300', 'bg-red-50', 'border-green-300', 'bg-green-50');
        minutesInput.classList.add('border-gray-300', 'bg-gray-100');
        
        // Reset label colors
        const nextElements = hoursInput.parentElement.querySelectorAll('span');
        nextElements.forEach(span => {
            if (span.textContent === 'hrs' || span.textContent === 'min') {
                span.classList.remove('text-blue-600');
                span.classList.add('text-gray-500');
            }
        });
        
        // Reset the separator colon
        const colonElement = hoursInput.parentElement.parentElement.querySelector('span');
        if (colonElement && colonElement.textContent === ':') {
            colonElement.classList.remove('text-blue-400');
            colonElement.classList.add('text-gray-400');
        }
        
        // Clear validation message
        const validationDiv = document.getElementById(`duration-validation-${requestId}`);
        if (validationDiv) {
            validationDiv.innerHTML = '';
        }
    }
    
    // Reset reason textarea
    if (reasonTextarea) {
        reasonTextarea.disabled = true;
        reasonTextarea.classList.remove('border-blue-300', 'bg-white', 'focus:ring-2', 'focus:ring-blue-400');
        reasonTextarea.classList.add('border-gray-300', 'bg-gray-100');
        
        // Reset the container styling
        const reasonBlock = document.getElementById(`reason-block-${requestId}`);
        if (reasonBlock) {
            reasonBlock.classList.remove('bg-blue-50', 'border-blue-200');
            reasonBlock.classList.add('bg-gray-50');
        }
    }
    
    // Reset time adjustment clock UI
    const timeAdjustmentBlock = document.getElementById(`time-adjustment-block-${requestId}`);
    if (timeAdjustmentBlock) {
        // Reset time in selects
        const timeInHour = document.getElementById(`edit-time-in-hour-${requestId}`);
        const timeInMinute = document.getElementById(`edit-time-in-minute-${requestId}`);
        const timeInAMPM = document.getElementById(`edit-time-in-ampm-${requestId}`);
        
        if (timeInHour && timeInMinute && timeInAMPM) {
            timeInHour.disabled = true;
            timeInMinute.disabled = true;
            timeInAMPM.disabled = true;
            
            // Reset styling to disabled state
            timeInHour.classList.remove('border-blue-300', 'bg-white');
            timeInHour.classList.add('border-gray-300', 'bg-gray-100');
            timeInMinute.classList.remove('border-blue-300', 'bg-white');
            timeInMinute.classList.add('border-gray-300', 'bg-gray-100');
            timeInAMPM.classList.remove('border-blue-300', 'bg-white');
            timeInAMPM.classList.add('border-gray-300', 'bg-gray-100');
        }
        
        // Reset time out selects
        const timeOutHour = document.getElementById(`edit-time-out-hour-${requestId}`);
        const timeOutMinute = document.getElementById(`edit-time-out-minute-${requestId}`);
        const timeOutAMPM = document.getElementById(`edit-time-out-ampm-${requestId}`);
        
        if (timeOutHour && timeOutMinute && timeOutAMPM) {
            timeOutHour.disabled = true;
            timeOutMinute.disabled = true;
            timeOutAMPM.disabled = true;
            
            // Reset styling to disabled state
            timeOutHour.classList.remove('border-green-300', 'bg-white');
            timeOutHour.classList.add('border-gray-300', 'bg-gray-100');
            timeOutMinute.classList.remove('border-green-300', 'bg-white');
            timeOutMinute.classList.add('border-gray-300', 'bg-gray-100');
            timeOutAMPM.classList.remove('border-green-300', 'bg-white');
            timeOutAMPM.classList.add('border-gray-300', 'bg-gray-100');
        }
        
        // Reset container styling
        timeAdjustmentBlock.classList.remove('border-blue-200');
    }
    
    // Reset schedule change UI
    const scheduleChangeBlock = document.getElementById(`schedule-change-block-${requestId}`);
    if (scheduleChangeBlock) {
        const scheduleSelect = document.getElementById(`edit-schedule-${requestId}`);
        
        if (scheduleSelect) {
            scheduleSelect.disabled = true;
            
            // Reset styling to disabled state
            scheduleSelect.classList.remove('border-green-300', 'bg-white');
            scheduleSelect.classList.add('border-gray-300', 'bg-gray-100');
        }
        
        // Reset container styling
        scheduleChangeBlock.classList.remove('border-blue-200');
    }
    
    // Reset date range input
    const dateRangeInput = document.getElementById(`edit-date-range-${requestId}`);
    if (dateRangeInput) {
        dateRangeInput.disabled = true;
        dateRangeInput.classList.remove('border-blue-300', 'bg-white', 'focus:ring-2', 'focus:ring-blue-400');
        dateRangeInput.classList.add('border-gray-300', 'bg-gray-100');
        
        // Reset the container styling
        const dateRangeBlock = document.getElementById(`date-range-block-${requestId}`);
        if (dateRangeBlock) {
            dateRangeBlock.classList.remove('bg-blue-50', 'border-blue-200');
            dateRangeBlock.classList.add('bg-gray-50');
        }
    }
}

// Function to check if values have changed and enable/disable save button
function checkForChanges(requestId) {
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const reasonTextarea = document.getElementById(`edit-reason-${requestId}`);
    const editSaveBtn = document.getElementById(`edit-save-btn-${requestId}`);
    
    if (!editSaveBtn) return;
    
    let hasChanged = false;
    
    // Check overtime duration changes
    if (hoursInput && minutesInput) {
        const originalHours = parseInt(hoursInput.getAttribute('data-original')) || 0;
        const originalMinutes = parseInt(minutesInput.getAttribute('data-original')) || 0;
        const currentHours = parseInt(hoursInput.value) || 0;
        const currentMinutes = parseInt(minutesInput.value) || 0;
        
        hasChanged = hasChanged || (originalHours !== currentHours) || (originalMinutes !== currentMinutes);
    }
    
    // Check reason changes
    if (reasonTextarea) {
        const originalReason = reasonTextarea.getAttribute('data-original') || '';
        const currentReason = reasonTextarea.value.trim() || '';
        
        hasChanged = hasChanged || (originalReason !== currentReason);
    }
    
    // Check time adjustment changes
    const timeInHidden = document.getElementById(`edit-time-in-${requestId}`);
    const timeOutHidden = document.getElementById(`edit-time-out-${requestId}`);
    
    if (timeInHidden) {
        const originalTimeIn = timeInHidden.getAttribute('data-original') || '';
        const currentTimeIn = timeInHidden.value || '';
        
        hasChanged = hasChanged || (originalTimeIn !== currentTimeIn);
    }
    
    if (timeOutHidden) {
        const originalTimeOut = timeOutHidden.getAttribute('data-original') || '';
        const currentTimeOut = timeOutHidden.value || '';
        
        hasChanged = hasChanged || (originalTimeOut !== currentTimeOut);
    }
    
    // Check schedule change
    const scheduleHidden = document.getElementById(`edit-schedule-hidden-${requestId}`);
    if (scheduleHidden) {
        const originalSchedule = scheduleHidden.getAttribute('data-original') || '';
        const currentSchedule = scheduleHidden.value || '';
        
        hasChanged = hasChanged || (originalSchedule !== currentSchedule);
    }
    
    // Check date range changes
    const dateRangeInput = document.getElementById(`edit-date-range-${requestId}`);
    if (dateRangeInput) {
        const originalDateRange = dateRangeInput.getAttribute('data-original') || '';
        const currentDateRange = dateRangeInput.value || '';
        
        hasChanged = hasChanged || (originalDateRange !== currentDateRange);
    }
    
    if (hasChanged) {
        // Show save button as ready to save changes
        editSaveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Changes';
        editSaveBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
        editSaveBtn.classList.add('bg-orange-600', 'hover:bg-orange-700');
    } else {
        // Show save button in normal edit state
        editSaveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Changes';
        editSaveBtn.classList.remove('bg-orange-600', 'hover:bg-orange-700');
        editSaveBtn.classList.add('bg-green-600', 'hover:bg-green-700');
    }
}

// Add smooth scroll animation and enhanced interactions
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced hover effects for the entire card
    const card = document.querySelector('.bg-white.rounded-xl.shadow-sm');
    if (card) {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)';
            this.style.transform = 'translateY(0)';
        });
    }
    
    // Enhanced focus handler with smart restoration and duplication prevention
    window.addEventListener('focus', function() {
        const modal = document.getElementById('activityModal');
        if (modal && !modal.classList.contains('hidden')) {
            console.log('🔒 Window focus regained');
            
            // Only restore if we're sure no sections exist and we have data
            setTimeout(() => {
                const attachmentSections = modal.querySelectorAll('[data-attachment-section]');
                console.log('📊 Focus check - existing sections:', attachmentSections.length);
                
                if (attachmentSections.length === 0 && window.lastKnownAttachmentData) {
                    console.log('🔧 Restoring disappeared attachment section on focus');
                    const modalBody = modal.querySelector('.modal-body');
                    if (modalBody && !modalBody.querySelector('[data-attachment-section]')) {
                        const data = window.lastKnownAttachmentData;
                        const attachmentHtml = createAttachmentSection(data.attachmentScr, data.requestId, data.tableName, data.status);
                        modalBody.insertAdjacentHTML('beforeend', attachmentHtml);
                        console.log('✅ Attachment section restored on focus');
                    }
                } else if (attachmentSections.length > 1) {
                    console.log('🧹 Multiple attachment sections detected, deduplicating');
                    dedupeAttachmentSections();
                }
            }, 150);
        }
    });
    
    // Set up MutationObserver to monitor attempts to remove protected sections
    const modal = document.getElementById('activityModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.removedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE && node.hasAttribute && node.hasAttribute('data-attachment-section')) {
                            const sectionId = node.getAttribute('data-attachment-section');
                            console.error('🚨 PROTECTED ATTACHMENT SECTION WAS REMOVED DESPITE PROTECTION:', sectionId);
                            console.trace('Removal stack trace:');
                            
                            // This should not happen if protection is working
                            // Restore as a last resort
                            if (window.lastKnownAttachmentData && window.lastKnownAttachmentData.requestId == sectionId) {
                                setTimeout(() => {
                                    const modalBody = modal.querySelector('.modal-body');
                                    if (modalBody && !modalBody.querySelector(`[data-attachment-section="${sectionId}"]`)) {
                                        const data = window.lastKnownAttachmentData;
                                        const attachmentHtml = createAttachmentSection(data.attachmentScr, data.requestId, data.tableName, data.status);
                                        modalBody.insertAdjacentHTML('beforeend', attachmentHtml);
                                        console.log('🆘 Emergency restoration of protected section');
                                    }
                                }, 10);
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(modal, {
            childList: true,
            subtree: true
        });
        
        console.log('🔍 MutationObserver set up (monitoring only)');
    }
    
    // Add global error handler to catch any JavaScript errors
    window.addEventListener('error', function(event) {
        const modal = document.getElementById('activityModal');
        if (modal && !modal.classList.contains('hidden')) {
            console.error('🚨 JavaScript error detected while modal is open:', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error,
                timestamp: new Date().toISOString()
            });
            
            // Check if this error might have affected attachment sections
            const attachmentSections = modal.querySelectorAll('[data-attachment-section]');
            console.log('📎 Attachment sections after error:', attachmentSections.length);
        }
    });
});

// Utility: remove duplicate attachment sections (keep protected ones first, then first occurrence per requestId)
function dedupeAttachmentSections() {
    const sections = document.querySelectorAll('[data-attachment-section]');
    if (!sections.length) return;
    
    const sectionsByRequest = {};
    
    // Group sections by request ID
    sections.forEach(section => {
        const id = section.getAttribute('data-attachment-section');
        if (!sectionsByRequest[id]) {
            sectionsByRequest[id] = [];
        }
        sectionsByRequest[id].push(section);
    });
    
    // For each request ID, keep only one section (prefer protected ones)
    Object.keys(sectionsByRequest).forEach(requestId => {
        const sectionsForRequest = sectionsByRequest[requestId];
        if (sectionsForRequest.length > 1) {
            console.log('🧹 Deduplicating', sectionsForRequest.length, 'sections for request', requestId);
            
            // Find the best section to keep (protected first, then first one)
            const protectedSection = sectionsForRequest.find(s => s.getAttribute('data-protected') === 'true');
            const sectionToKeep = protectedSection || sectionsForRequest[0];
            
            // Remove all others
            sectionsForRequest.forEach(section => {
                if (section !== sectionToKeep) {
                    console.log('🗑️ Removing duplicate section for request', requestId);
                    section.remove();
                }
            });
        }
    });
}

// Attachment related functions
function viewAttachment(attachmentPath, fileName, event) {
    // Prevent event bubbling that might close modal or trigger other actions
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
    }
    
    console.log('👀 viewAttachment called with:', {
        attachmentPath: attachmentPath,
        fileName: fileName,
        timestamp: new Date().toISOString(),
        eventType: event ? event.type : 'no-event'
    });
    
    // Check if attachment section exists BEFORE opening
    const attachmentSections = document.querySelectorAll('[data-attachment-section]');
    console.log('📎 Attachment sections BEFORE view:', attachmentSections.length);
    attachmentSections.forEach((section, index) => {
        console.log(`  Section ${index}:`, section.getAttribute('data-attachment-section'), 'Visible:', !section.classList.contains('hidden'));
    });
    
    if (!attachmentPath) {
        alert('No attachment to view.');
        return;
    }
    
    // Use the secure attachment viewer
    const attachmentUrl = `../controller/view_attachment.php?file=${encodeURIComponent(attachmentPath)}`;
    console.log('🔗 Opening URL:', attachmentUrl);
    
    // Track successful opening
    console.log('🚀 About to open new window...');
    
    try {
        const newWindow = window.open(attachmentUrl, '_blank');
        if (newWindow) {
            console.log('✅ New window opened successfully');
            // Ensure modal stays open and data persists
            newWindow.focus();
            
            // LOCK DOWN ATTACHMENT SECTIONS TO PREVENT REMOVAL
            const lockDownAttachments = () => {
                const attachmentSectionsAfter = document.querySelectorAll('[data-attachment-section]');
                console.log('📎 Attachment sections AFTER view:', attachmentSectionsAfter.length);
                
                // Apply maximum protection to each section
                attachmentSectionsAfter.forEach(section => {
                    const requestId = section.getAttribute('data-attachment-section');
                    console.log('🔒 Applying maximum protection to section:', requestId);
                    
                    // Apply removal protection
                    if (window.protectAttachmentSection) {
                        window.protectAttachmentSection(requestId);
                    }
                    
                    // Additional DOM protection
                    section.setAttribute('data-protected', 'true');
                    section.setAttribute('data-removal-blocked', 'true');
                    section.style.position = 'relative';
                    section.style.zIndex = '1001';
                    
                    // Make it unremovable by overriding parent methods too
                    if (section.parentNode) {
                        const originalRemoveChild = section.parentNode.removeChild;
                        section.parentNode.removeChild = function(child) {
                            if (child === section) {
                                console.log('🛡️ BLOCKED removeChild attempt on protected attachment section');
                                return section; // Return the section instead of removing it
                            }
                            return originalRemoveChild.call(this, child);
                        };
                    }
                });
                
                // If no sections exist but we have data, restore
                if (attachmentSectionsAfter.length === 0 && window.lastKnownAttachmentData) {
                    console.log('🆘 Emergency restoration - no sections found');
                    const modalBody = document.getElementById('modalBody');
                    if (modalBody) {
                        const data = window.lastKnownAttachmentData;
                        const attachmentHtml = createAttachmentSection(data.attachmentScr, data.requestId, data.tableName, data.status);
                        modalBody.insertAdjacentHTML('beforeend', attachmentHtml);
                        console.log('✅ Emergency attachment section restored');
                    }
                }
            };
            
            // Apply protection immediately and repeatedly
            lockDownAttachments();
            setTimeout(lockDownAttachments, 10);
            setTimeout(lockDownAttachments, 50);
            setTimeout(lockDownAttachments, 200);
            
        } else {
            console.log('❌ Failed to open new window (popup blocked?)');
            alert('Please allow popups for this site to view attachments.');
        }
    } catch (error) {
        console.error('💥 Error opening attachment window:', error);
        alert('Error opening attachment: ' + error.message);
    }
    
    // Explicitly return false to prevent any form submission or page navigation
    return false;
}

function deleteAttachment(requestId, tableName, attachmentPath) {
    if (!confirm(`Are you sure you want to delete this attachment?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    // Show loading state
    const deleteBtn = event.target;
    const originalText = deleteBtn.innerHTML;
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';
    
    // Determine attachment column name based on table
    const attachmentColumns = {
        'leave_requests': 'attachment_lr',
        'post_leave_requests': 'attachment_lr',
        'time_adjustment_requests': 'attachment',
        'post_time_adjustment_requests': 'attachment',
        'overtime_requests': 'attachment_ot',
        'post_ot_requests': 'attachment',
        'schedule_change_requests': 'attachment_scr',
        'post_schedule_change_requests': 'attachment_scr'
    };
    
    const attachmentColumn = attachmentColumns[tableName] || 'attachment';
    
    const deleteData = {
        request_id: requestId,
        table_name: tableName,
        attachment_column: attachmentColumn
    };
    
    console.log('Deleting attachment:', deleteData);
    
    fetch('../controller/delete_attachment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(deleteData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Delete attachment response:', data);
        
        if (data.success) {
            alert('✅ Attachment deleted successfully!');
            
            // Refresh the page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Delete attachment failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to delete attachment'));
            
            // Restore button state
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message);
        
        // Restore button state
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalText;
    });
}

function showAttachmentUpload(requestId, tableName) {
    const uploadSection = document.getElementById(`upload-section-${requestId}`);
    if (uploadSection) {
        uploadSection.classList.remove('hidden');
    }
}

function hideAttachmentUpload(requestId) {
    const uploadSection = document.getElementById(`upload-section-${requestId}`);
    const fileInput = document.getElementById(`attachment-input-${requestId}`);
    
    if (uploadSection) {
        uploadSection.classList.add('hidden');
    }
    
    if (fileInput) {
        fileInput.value = '';
    }
}

function uploadAttachment(requestId, tableName) {
    const fileInput = document.getElementById(`attachment-input-${requestId}`);
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a file to upload.');
        return;
    }
    
    // Validate file size (10MB limit)
    const maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if (file.size > maxSize) {
        alert('File size must be less than 10MB.');
        return;
    }
    
    // Validate file type
    const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
    const fileExtension = file.name.split('.').pop()?.toLowerCase();
    if (!allowedTypes.includes(fileExtension)) {
        alert('Invalid file type. Please upload PDF, JPG, PNG, DOC, DOCX, XLS, or XLSX files only.');
        return;
    }
    
    // Show loading state
    const uploadBtn = event.target;
    const originalText = uploadBtn.innerHTML;
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Uploading...';
    
    // Determine attachment column name based on table
    const attachmentColumns = {
        'leave_requests': 'attachment_lr',
        'post_leave_requests': 'attachment_lr',
        'time_adjustment_requests': 'attachment',
        'post_time_adjustment_requests': 'attachment',
        'overtime_requests': 'attachment_ot',
        'post_ot_requests': 'attachment',
        'schedule_change_requests': 'attachment_scr',
        'post_schedule_change_requests': 'attachment_scr'
    };
    
    const attachmentColumn = attachmentColumns[tableName] || 'attachment';
    
    // Create FormData for file upload
    const formData = new FormData();
    formData.append('attachment', file);
    formData.append('request_id', requestId);
    formData.append('table_name', tableName);
    formData.append('attachment_column', attachmentColumn);
    
    console.log('Uploading attachment for request:', requestId, 'table:', tableName, 'column:', attachmentColumn, 'file:', file.name);
    
    // Use generic upload controller for all request types
    fetch('../controller/upload_attachment.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'  // Include cookies for session
    })
    .then(response => {
        console.log('Upload response status:', response.status);
        console.log('Upload response headers:', response.headers);
        
        // First get the response as text to see what we're dealing with
        return response.text().then(text => {
            console.log('Raw server response:', text);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: Server error - ${text.substring(0, 300)}`);
            }
            
            // Try to parse as JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was not JSON, got:', text.substring(0, 500));
                throw new Error('Server returned invalid JSON response. Check server logs.');
            }
        });
    })
    .then(data => {
        console.log('Upload attachment response:', data);
        
        if (data.success) {
            alert('✅ Attachment replaced successfully!');
            
            // Hide upload section and clear input
            hideAttachmentUpload(requestId);
            
            // Update the attachment section in-place with new file
            const existingSection = document.querySelector(`[data-attachment-section="${requestId}"]`);
            const newFilename = data.filename || data.new_filename; // Handle both possible response formats
            if (existingSection && newFilename) {
                // Update the stored attachment data
                if (window.lastKnownAttachmentData) {
                    window.lastKnownAttachmentData.attachmentScr = newFilename;
                }
                
                // Create new attachment section HTML
                const newAttachmentHtml = createAttachmentSection(newFilename, requestId, tableName, 'pending');
                
                // Replace the existing section
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = newAttachmentHtml;
                const newSection = tempDiv.firstElementChild;
                
                existingSection.parentNode.replaceChild(newSection, existingSection);
                console.log('✅ Attachment section updated with new file:', data.new_filename);
            } else {
                // Fallback: reload page if we can't update in-place
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        } else {
            console.error('Upload attachment failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to upload attachment'));
            
            // Restore button state
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message);
        
        // Restore button state
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = originalText;
    });
}
</script>
