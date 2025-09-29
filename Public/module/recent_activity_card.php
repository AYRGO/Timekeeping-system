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
                                Date
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
                                Schedule Change Request
                            <?php elseif ($type === 'Time'): ?>
                                Time Adjustment Request
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
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Overtime') { ?>
                        <button onclick="showActivityDetails('Overtime Request', `<?= htmlspecialchars(json_encode([
                            'type' => 'Overtime Request',
                            'status' => $status,
                            'date' => $created,
                            'ot_date' => !empty($activity['ot_date']) ? date('M j, Y', strtotime($activity['ot_date'])) : (!empty($activity['log_date']) ? date('M j, Y', strtotime($activity['log_date'])) : ''),
                            'reason' => $activity['ot_reason'] ?? '',
                            'ot_type' => $activity['ot_type'] ?? 'Overtime',
                            'ot_duration' => isset($activity['ot_duration']) ? number_format((float)$activity['ot_duration'], 2) : '',
                            'max_ot_hours' => isset($activity['max_ot_hours']) ? number_format((float)$activity['max_ot_hours'], 2) : '',
                            'start_ot' => !empty($activity['start_ot']) ? date('g:i A', strtotime($activity['start_ot'])) : '',
                            'end_ot' => !empty($activity['end_ot']) ? date('g:i A', strtotime($activity['end_ot'])) : '',
                            'time_in' => !empty($activity['time_in']) ? date('g:i A', strtotime($activity['time_in'])) : '',
                            'time_out' => !empty($activity['time_out']) ? date('g:i A', strtotime($activity['time_out'])) : '',
                            'explanation' => $activity['explanation'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>`)" 
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
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>`)" 
                                class="inline-flex px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-md transition-all duration-200 shadow-sm ring-1 ring-blue-200/50">
                            View Details
                        </button>
                    <?php } elseif ($type === 'Schedule') { ?>
                        <button onclick="showActivityDetails('Schedule Change', `<?= htmlspecialchars(json_encode([
                            'type' => 'Schedule Change',
                            'status' => $status,
                            'date' => $created,
                            'current_time_in' => !empty($activity['current_time_in']) ? date('g:i A', strtotime($activity['current_time_in'])) : '',
                            'current_time_out' => !empty($activity['current_time_out']) ? date('g:i A', strtotime($activity['current_time_out'])) : '',
                            'requested_time_in' => !empty($activity['requested_time_in']) ? date('g:i A', strtotime($activity['requested_time_in'])) : '',
                            'requested_time_out' => !empty($activity['requested_time_out']) ? date('g:i A', strtotime($activity['requested_time_out'])) : '',
                            'start_date' => !empty($activity['start_date']) ? date('M j, Y', strtotime($activity['start_date'])) : '',
                            'end_date' => !empty($activity['end_date']) ? date('M j, Y', strtotime($activity['end_date'])) : '',
                            'explanation' => $activity['explanation'] ?? '',
                            'request_id' => $activity['request_id'] ?? '',
                            'table_name' => $activity['table_name'] ?? '',
                            'source_table' => $activity['source_table'] ?? ''
                        ], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>`)" 
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
                        <button onclick="showActivityDetails('<?= $type ?> Request', `<?= htmlspecialchars(json_encode($fallbackPayload, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>`)" 
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
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 border border-gray-200/80 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
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
function showActivityDetails(title, dataJson) {
    try {
        console.log('Raw JSON string:', dataJson); // Debug: show raw JSON string
        const data = JSON.parse(dataJson);
        console.log('Parsed Data:', data); // Debug: show parsed data
        
        const modal = document.getElementById('activityModal');
        const modalContent = document.getElementById('modalContent');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        // Set modal title
        modalTitle.textContent = data.type || title;
        
        // Build modal content based on request type
        let content = '';
        
        // Status badge
        const statusClass = getStatusBadgeClass(data.status);
        content += `
            <div class="flex items-start justify-between pb-4 border-b border-gray-200 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">${data.type}</h3>
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
                content += createInfoBlock('Target Date', data.log_date, 'fa-calendar-day');
            }
            
            // Debug: Show all available data if time fields are missing
            console.log('Time Adjustment Data:', data);
            
            // Check if we have time data, if not show debugging info
            if (data.current_time_in || data.current_time_out || data.requested_time_in || data.requested_time_out) {
                content += `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Time Changes</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-md">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Original Time</label>
                                <p class="text-gray-800 font-semibold">${data.current_time_in || 'Not Available'} - ${data.current_time_out || 'Not Available'}</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-md">
                                <label class="block text-xs font-medium text-green-600 mb-1">Adjusted Time</label>
                                <p class="text-green-800 font-semibold">${data.requested_time_in || 'Not Available'} - ${data.requested_time_out || 'Not Available'}</p>
                            </div>
                        </div>
                    </div>
                `;
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
        } else if (data.type.includes('Leave')) {
            if (data.start_date && data.end_date) {
                content += createInfoBlock('Leave Period', `${data.start_date} to ${data.end_date}`, 'fa-calendar-week');
            }
        } else if (data.type.includes('Schedule')) {
            if (data.current_time_in && data.current_time_out && data.requested_time_in && data.requested_time_out) {
                content += `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Schedule Changes</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-md">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Current</label>
                                <p class="text-gray-800 font-semibold">${data.current_time_in} - ${data.current_time_out}</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-md">
                                <label class="block text-xs font-medium text-blue-600 mb-1">Requested</label>
                                <p class="text-blue-800 font-semibold">${data.requested_time_in} - ${data.requested_time_out}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            if (data.start_date && data.end_date) {
                content += createInfoBlock('Effective Period', `${data.start_date} to ${data.end_date}`, 'fa-calendar-alt');
            }
        } else if (data.type.includes('Overtime')) {
            // Add OT Date if available
            if (data.ot_date) {
                content += createInfoBlock('Overtime Date', data.ot_date, 'fa-calendar-day');
            }
            
            // Overtime details: Start, End, Duration, Type - prefer start_ot/end_ot over time_in/time_out
            const details = [];
            const startTime = data.start_ot || data.time_in;
            const endTime = data.end_ot || data.time_out;
            
            if (startTime || endTime) {
                details.push(`<div class=\"grid grid-cols-1 sm:grid-cols-2 gap-4\">` +
                    `<div class=\"bg-gray-50 p-3 rounded-md\">`+
                    `<label class=\"block text-xs font-medium text-gray-500 mb-1\">Start OT</label>`+
                    `<p class=\"text-gray-800 font-semibold\">${startTime || '—'}</p>`+
                    `</div>`+
                    `<div class=\"bg-gray-50 p-3 rounded-md\">`+
                    `<label class=\"block text-xs font-medium text-gray-500 mb-1\">End OT</label>`+
                    `<p class=\"text-gray-800 font-semibold\">${endTime || '—'}</p>`+
                    `</div>`+
                `</div>`);
            }
            if (data.ot_duration) {
                // Check if request is pending and can be edited
                if (data.status && data.status.toLowerCase() === 'pending' && data.request_id) {
                    // Editable duration for pending requests
                    const durationHours = parseFloat(data.ot_duration) || 0;
                    const wholeHours = Math.floor(durationHours);
                    const minutes = Math.round((durationHours - wholeHours) * 60);
                    
                    details.push(`<div class=\"bg-blue-50 p-3 rounded-md\">`+
                        `<label class=\"block text-xs font-medium text-blue-600 mb-2\">Duration (Editable)</label>`+
                        `<div class=\"flex items-center space-x-2 mb-2\">`+
                            `<div class=\"flex flex-col items-center\">`+
                                `<label class=\"text-xs font-medium text-blue-600 mb-1\">Hrs</label>`+
                                `<input type=\"number\" id=\"edit-hours-${data.request_id}\" min=\"0\" max=\"24\" value=\"${wholeHours}\" `+
                                `class=\"w-12 h-8 text-center border border-blue-300 rounded text-sm font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400\">`+
                            `</div>`+
                            `<div class=\"text-blue-400 font-bold mt-4\">:</div>`+
                            `<div class=\"flex flex-col items-center\">`+
                                `<label class=\"text-xs font-medium text-blue-600 mb-1\">Min</label>`+
                                `<input type=\"number\" id=\"edit-minutes-${data.request_id}\" min=\"0\" max=\"59\" step=\"15\" value=\"${minutes}\" `+
                                `class=\"w-12 h-8 text-center border border-blue-300 rounded text-sm font-bold focus:ring-1 focus:ring-blue-400 focus:border-blue-400\">`+
                            `</div>`+
                        `</div>`+
                        `<div class=\"text-xs text-blue-600 mb-2\" id=\"duration-display-${data.request_id}\">Current: ${data.ot_duration} hours</div>`+
                        `<div class=\"text-xs text-gray-500\" id=\"duration-validation-${data.request_id}\">Max available: ${data.max_ot_hours || 'N/A'}</div>`+
                        `<button type=\"button\" onclick=\"updateOvertimeDuration('${data.request_id}', '${data.table_name}')\" `+
                        `class=\"mt-2 px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors\">Update Duration</button>`+
                    `</div>`);
                } else {
                    // Static duration display for non-pending requests
                    details.push(`<div class=\"bg-blue-50 p-3 rounded-md\">`+
                        `<label class=\"block text-xs font-medium text-blue-600 mb-1\">Duration</label>`+
                        `<p class=\"text-blue-800 font-semibold\">${data.ot_duration} hours</p>`+
                    `</div>`);
                }
            }
            if (data.ot_type) {
                details.push(`<div class=\"bg-green-50 p-3 rounded-md\">`+
                    `<label class=\"block text-xs font-medium text-green-600 mb-1\">Type</label>`+
                    `<p class=\"text-green-800 font-semibold\">${data.ot_type}</p>`+
                `</div>`);
            }
            if (details.length) {
                content += `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Overtime Details</h4>
                        <div class="space-y-3">${details.join('')}</div>
                    </div>
                `;
            }
        }
        
        // Employee reason
        if (data.reason) {
            content += createInfoBlock('Employee Reason', data.reason, 'fa-comment-dots', 'text-sm leading-relaxed');
        }
        
        // Admin explanation (for declined/rejected requests)
        if (data.explanation && ['declined', 'rejected', 'cancelled'].includes(data.status.toLowerCase())) {
            content += createInfoBlock('Administrator Response', data.explanation, 'fa-user-shield', 'text-sm leading-relaxed text-red-700', 'bg-red-50 border-red-200');
        }
        
        modalBody.innerHTML = content;
        
        // Add Edit and Cancel buttons if applicable (status is 'pending' and we have request data)
        const unsubmitContainer = document.getElementById('unsubmitButtonContainer');
        unsubmitContainer.innerHTML = '';
        
        if (data.status && data.status.toLowerCase() === 'pending' && 
            data.request_id && data.table_name) {
            unsubmitContainer.innerHTML = `
                <div class="flex gap-2">
                    <button onclick="editRequestFromModal('${data.type}', ${data.request_id}, '${data.table_name}', '${data.source_table}')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md transform active:scale-95 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Edit
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

function createInfoBlock(label, value, icon, valueClass = 'font-semibold', containerClass = 'bg-gray-50') {
    return `
        <div class="border border-gray-200 rounded-lg p-4 ${containerClass}">
            <label class="block text-sm font-medium text-gray-600 mb-2 flex items-center">
                <i class="fas ${icon} mr-2 text-gray-400"></i>
                ${label}
            </label>
            <p class="text-gray-800 ${valueClass}">${value}</p>
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
    if (!confirm('Are you sure you want to cancel this request?\n\nThis action cannot be undone and will permanently remove the request from the system.')) {
        return;
    }
    
    // Call the existing unsubmitRequest function with sourceTable
    unsubmitRequest(requestId, tableName, sourceTable);
    
    // Close the modal after initiating cancel (it will only reload if successful)
    closeActivityModal();
}

function closeActivityModal() {
    const modal = document.getElementById('activityModal');
    const modalContent = document.getElementById('modalContent');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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

// Function to update overtime duration
function updateOvertimeDuration(requestId, tableName) {
    const hoursInput = document.getElementById(`edit-hours-${requestId}`);
    const minutesInput = document.getElementById(`edit-minutes-${requestId}`);
    const validationDiv = document.getElementById(`duration-validation-${requestId}`);
    const displayDiv = document.getElementById(`duration-display-${requestId}`);
    
    if (!hoursInput || !minutesInput) {
        alert('Error: Duration inputs not found');
        return;
    }
    
    const hours = parseInt(hoursInput.value) || 0;
    const minutes = parseInt(minutesInput.value) || 0;
    const maxAllowedHours = parseFloat(hoursInput.getAttribute('data-max-hours')) || 24;
    
    // Round minutes to nearest 15-minute increment
    const roundedMinutes = Math.round(minutes / 15) * 15;
    if (roundedMinutes !== minutes) {
        minutesInput.value = roundedMinutes > 59 ? 0 : roundedMinutes;
        if (roundedMinutes > 59) {
            hoursInput.value = hours + 1;
        }
    }
    
    // Calculate total hours as decimal
    const totalHours = hours + (parseInt(minutesInput.value) / 60);
    
    if (totalHours === 0) {
        alert('Please select a valid duration (minimum 15 minutes)');
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
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Updating...';
    
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
            displayDiv.innerHTML = `Current: ${totalHours.toFixed(2)} hours`;
            
            // Refresh the page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            console.error('Update failed:', data);
            alert('❌ Error: ' + (data.message || 'Failed to update duration') + 
                  '\n\nDebug info: ' + JSON.stringify(data, null, 2));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Network/Parse Error:', error);
        alert('🔥 Network error occurred: ' + error.message + 
              '\n\nPlease check browser console for details and try again.');
        button.disabled = false;
        button.innerHTML = originalText;
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
        validationDiv.innerHTML = `<span class="text-red-500">⚠️ Exceeds OT window limit: ${formatOvertimeDuration(maxAllowedHours)}</span>`;
        hoursInput.classList.add('border-red-300');
        minutesInput.classList.add('border-red-300');
    } else if (totalHours > 0) {
        validationDiv.innerHTML = `<span class="text-green-600">✓ Valid duration: ${formatOvertimeDuration(totalHours)}</span>`;
        hoursInput.classList.remove('border-red-300');
        minutesInput.classList.remove('border-red-300');
        hoursInput.classList.add('border-green-300');
        minutesInput.classList.add('border-green-300');
    } else {
        validationDiv.innerHTML = `Max allowed: ${formatOvertimeDuration(maxAllowedHours)} hrs (based on OT window)`;
        hoursInput.classList.remove('border-red-300', 'border-green-300');
        minutesInput.classList.remove('border-red-300', 'border-green-300');
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
});
</script>
