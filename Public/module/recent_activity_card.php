<?php
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
?>

<!-- ✅ DO NOT TOUCH CONTAINER ABOVE THIS -->

<div class="bg-white rounded-2xl shadow-lg p-6 w-full lg:w-1/2 border border-gray-200">
    <!-- Header -->
 <form method="GET" class="flex flex-col md:flex-row md:items-start md:justify-between mb-6 gap-4 flex-wrap">
    <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
        <i class="fas fa-history text-blue-500 bg-blue-100 p-2 rounded-full mr-3"></i>
        Recent Activity
    </h3>

    <!-- Date Filter -->
    <div class="flex items-center gap-2 flex-wrap md:flex-nowrap">
        <label for="activityDate" class="text-sm text-gray-600 font-medium">Filter by date:</label>
        <input type="date" id="activityDate" name="activityDate"
                               value="<?= htmlspecialchars($filterDate ?? '') ?>"
               class="border rounded-md px-3 py-1 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200 max-w-[160px] w-full">
        <button type="submit" class="text-sm bg-blue-500 hover:bg-blue-600 text-white font-medium px-3 py-1 rounded">
            Apply
        </button>
    </div>
</form>


    <!-- Scrollable Table -->
    <div class="overflow-x-auto">
        <div class="max-h-60 overflow-y-auto rounded-md border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
<?php if (!empty($recentActivities)): ?>
    <?php foreach ($recentActivities as $activity): ?>
        <?php
            $created = date('M j, Y', strtotime($activity['created_at']));
            $msg = strip_tags($activity['message']);

            // Detect type
            preg_match('/\b(Leave|Schedule|Time|Overtime)\b/i', $msg, $typeMatch);
            $type = $typeMatch[0] ?? 'Request';

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
            // For Time requests, fetch status directly from post_time_adjustment_requests table
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
                        $sentence .= "\nEffective Date: $startDate";
                    } else {
                        $sentence .= "\nEffective Period: $startDate to $endDate";
                    }
                }
                
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
                        $sentence .= "\nDate: $startDate";
                    } else {
                        $sentence .= "\nDates: $startDate to $endDate";
                    }
                }
                
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
                $sentence .= "Time adjustment request was $status.";
                
                // Add time details if available
                if (!empty($activity['log_date'])) {
                    $logDate = date('M j, Y', strtotime($activity['log_date']));
                    $sentence .= "\nDate: $logDate";
                }
                
                // Format current times
                $currentTimeIn = !empty($activity['current_time_in']) ? date('g:i A', strtotime($activity['current_time_in'])) : '—';
                $currentTimeOut = !empty($activity['current_time_out']) ? date('g:i A', strtotime($activity['current_time_out'])) : '—';
                
                // Format requested times  
                $requestedTimeIn = !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '—';
                $requestedTimeOut = !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '—';
                
                $sentence .= "\nCurrent: $currentTimeIn - $currentTimeOut";
                $sentence .= "\nRequested: $requestedTimeIn - $requestedTimeOut";
                
                if (!empty($activity['reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['reason']);
                }
            } elseif ($type === 'Overtime') {
                $sentence .= "Overtime request was $status.";
                if (!empty($activity['ot_reason'])) {
                    $sentence .= "\nReason: " . htmlspecialchars($activity['ot_reason']);
                }
            } else {
                $sentence .= "$type request was $status.";
            }
        ?>
        <tr>
            <td class="px-4 py-3 text-gray-500"><?= $created ?></td>
            <td class="px-4 py-3 font-medium text-gray-900">
                <?php if ($type === 'Leave' && isset($leaveTypeDisplay) && $leaveTypeDisplay !== 'Leave Request'): ?>
                    <?= $leaveTypeDisplay ?>
                <?php elseif ($type === 'Leave'): ?>
                    Leave Request
                <?php elseif ($type === 'Schedule'): ?>
                    Schedule Change
                <?php elseif ($type === 'Time'): ?>
                    Time Adjustment
                <?php else: ?>
                    <?= $type ?> Request
                <?php endif; ?>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex px-2 text-xs font-semibold rounded-full <?= $badgeColor ?>">
                    <?= $status ?>
                </span>
                <!-- Reason display removed as requested -->
            </td>
            <td class="px-4 py-3 text-green-600 hover:text-green-900">
                <?php
                if ($type === 'Time' && !empty($activity['reason'])) {
                    ?>
                    <button onclick="alert('Reason: <?= htmlspecialchars_decode($activity['reason']) ?>')">View</button>
                <?php } elseif ($type === 'Overtime' && !empty($activity['ot_reason'])) { ?>
                    <button onclick="alert('Reason: <?= htmlspecialchars_decode($activity['ot_reason']) ?>')">View</button>
                <?php } else { ?>
                    <button onclick="alert(`<?= htmlspecialchars_decode($sentence) ?>`)">View</button>
                <?php } ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No recent activity found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
