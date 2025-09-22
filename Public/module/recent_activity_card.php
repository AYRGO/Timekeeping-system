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

<div class="bg-white rounded-2xl shadow-lg p-4 w-full lg:w-[32rem] xl:w-[36rem] 2xl:w-[40rem] border-0 ring-1 ring-gray-200/50 hover:shadow-xl hover:ring-blue-300/30 transition-all duration-500 backdrop-blur-sm">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <h3 class="text-xl font-bold text-gray-900 flex items-center">
            <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 p-2.5 rounded-xl mr-3 shadow-lg ring-1 ring-blue-500/20">
                <i class="fas fa-history text-white text-sm drop-shadow-sm"></i>
            </div>
            <span class="bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">Recent Activity</span>
        </h3>

        <!-- Date Filter -->
        <form method="GET" class="flex items-center gap-2 bg-gradient-to-r from-gray-50 to-gray-100/80 rounded-xl px-3 py-2 border-0 ring-1 ring-gray-200/70 hover:ring-blue-300/50 transition-all duration-300 shadow-sm hover:shadow-md">
            <label for="activityDate" class="text-xs text-gray-700 font-medium whitespace-nowrap flex items-center">
                <i class="fas fa-calendar-alt text-blue-500 mr-1.5 text-xs"></i>
                <span>Filter by date:</span>
            </label>
            <input type="date" id="activityDate" name="activityDate"
                   value="<?= htmlspecialchars($filterDate ?? '') ?>"
                   class="border-0 bg-white/90 rounded-lg px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:bg-white transition-all duration-200 shadow-sm w-32 font-medium">
            <button type="submit" class="bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 hover:from-blue-600 hover:via-blue-700 hover:to-indigo-700 text-white font-semibold px-3 py-1.5 rounded-lg text-xs transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105 active:scale-95 ring-1 ring-blue-500/20">
                <i class="fas fa-search text-xs mr-1"></i>Apply
            </button>
        </form>
    </div>

    <!-- Activity Table -->
    <div class="bg-gradient-to-br from-gray-50/50 to-gray-100/30 rounded-xl p-1 ring-1 ring-gray-200/50 shadow-inner">
        <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-sm ring-1 ring-white/50">
            <div class="max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent hover:scrollbar-thumb-gray-400">
                <table class="min-w-full divide-y divide-gray-100/80">
                    <thead class="bg-gradient-to-r from-gray-50/80 via-gray-100/50 to-gray-50/80 sticky top-0 z-10 backdrop-blur-sm">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wide w-28">
                                <i class="fas fa-calendar text-blue-500/70 mr-1.5"></i>Date
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wide flex-1 min-w-[160px]">
                                <i class="fas fa-tag text-purple-500/70 mr-1.5"></i>Request Type
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wide w-24">
                                <i class="fas fa-info-circle text-green-500/70 mr-1.5"></i>Status
                            </th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wide w-32">
                                <i class="fas fa-cog text-orange-500/70 mr-1.5"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/50 divide-y divide-gray-100/60 backdrop-blur-sm"><?php if (!empty($recentActivities)): ?>
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
        <tr class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/30 transition-all duration-300 group cursor-pointer backdrop-blur-sm">
            <td class="px-4 py-2.5 text-gray-600 font-medium w-28">
                <div class="flex items-center">
                    <div class="w-1.5 h-1.5 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-full mr-2.5 opacity-70 group-hover:opacity-100 transition-opacity duration-300 shadow-sm"></div>
                    <span class="text-xs font-semibold text-gray-700 group-hover:text-gray-900 transition-colors duration-300 whitespace-nowrap"><?= $created ?></span>
                </div>
            </td>
            <td class="px-4 py-2.5 flex-1 min-w-[160px]">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-6 w-6 mr-3 transform group-hover:scale-110 transition-transform duration-300">
                        <?php if ($type === 'Leave'): ?>
                            <div class="h-6 w-6 bg-gradient-to-br from-emerald-100 to-green-200 rounded-lg flex items-center justify-center ring-1 ring-emerald-200/50 shadow-sm">
                                <i class="fas fa-plane text-emerald-600 text-xs"></i>
                            </div>
                        <?php elseif ($type === 'Schedule'): ?>
                            <div class="h-6 w-6 bg-gradient-to-br from-purple-100 to-violet-200 rounded-lg flex items-center justify-center ring-1 ring-purple-200/50 shadow-sm">
                                <i class="fas fa-clock text-purple-600 text-xs"></i>
                            </div>
                        <?php elseif ($type === 'Time'): ?>
                            <div class="h-6 w-6 bg-gradient-to-br from-orange-100 to-amber-200 rounded-lg flex items-center justify-center ring-1 ring-orange-200/50 shadow-sm">
                                <i class="fas fa-edit text-orange-600 text-xs"></i>
                            </div>
                        <?php elseif ($type === 'Overtime'): ?>
                            <div class="h-6 w-6 bg-gradient-to-br from-indigo-100 to-blue-200 rounded-lg flex items-center justify-center ring-1 ring-indigo-200/50 shadow-sm">
                                <i class="fas fa-business-time text-indigo-600 text-xs"></i>
                            </div>
                        <?php else: ?>
                            <div class="h-6 w-6 bg-gradient-to-br from-gray-100 to-slate-200 rounded-lg flex items-center justify-center ring-1 ring-gray-200/50 shadow-sm">
                                <i class="fas fa-file text-gray-600 text-xs"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-bold text-gray-900 group-hover:text-gray-950 transition-colors duration-300 truncate">
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
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-2.5 w-24">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= $badgeColor ?> ring-1 ring-white/20 shadow-sm backdrop-blur-sm transform group-hover:scale-105 transition-all duration-300 whitespace-nowrap">
                    <?php if (strtolower($status) === 'approved'): ?>
                        <i class="fas fa-check-circle mr-1 text-emerald-600 drop-shadow-sm"></i>
                    <?php elseif (in_array(strtolower($status), ['rejected', 'declined', 'cancelled'])): ?>
                        <i class="fas fa-times-circle mr-1 text-red-600 drop-shadow-sm"></i>
                    <?php else: ?>
                        <i class="fas fa-clock mr-1 text-amber-600 drop-shadow-sm"></i>
                    <?php endif; ?>
                    <?= $status ?>
                </span>
            </td>
            <td class="px-4 py-2.5 w-32">
                <div class="flex items-center gap-2">
                    <?php
                    if ($type === 'Time' && !empty($activity['reason'])) {
                        ?>
                        <button onclick="alert('Reason: <?= htmlspecialchars_decode($activity['reason']) ?>')" 
                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-blue-700 bg-gradient-to-r from-blue-100/80 to-sky-100/60 hover:from-blue-200/90 hover:to-sky-200/70 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md ring-1 ring-blue-200/50 transform hover:scale-105 active:scale-95 backdrop-blur-sm whitespace-nowrap">
                            <i class="fas fa-eye mr-1 text-blue-600"></i>View
                        </button>
                    <?php } elseif ($type === 'Overtime' && !empty($activity['ot_reason'])) { ?>
                        <button onclick="alert('Reason: <?= htmlspecialchars_decode($activity['ot_reason']) ?>')" 
                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-blue-700 bg-gradient-to-r from-blue-100/80 to-sky-100/60 hover:from-blue-200/90 hover:to-sky-200/70 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md ring-1 ring-blue-200/50 transform hover:scale-105 active:scale-95 backdrop-blur-sm whitespace-nowrap">
                            <i class="fas fa-eye mr-1 text-blue-600"></i>View
                        </button>
                    <?php } else { ?>
                        <button onclick="alert(`<?= htmlspecialchars_decode($sentence) ?>`)" 
                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-blue-700 bg-gradient-to-r from-blue-100/80 to-sky-100/60 hover:from-blue-200/90 hover:to-sky-200/70 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md ring-1 ring-blue-200/50 transform hover:scale-105 active:scale-95 backdrop-blur-sm whitespace-nowrap">
                            <i class="fas fa-eye mr-1 text-blue-600"></i>View
                        </button>
                    <?php } ?>
                    
                    <?php
                    // Check if status is pending, from pending table, and we have request ID and table name for unsubmit
                    if (strtolower($status) === 'pending' && 
                        isset($activity['request_id']) && 
                        isset($activity['table_name']) &&
                        isset($activity['source_table']) &&
                        $activity['source_table'] === 'pending'): ?>
                        <button onclick="unsubmitRequest(<?= $activity['request_id'] ?>, '<?= $activity['table_name'] ?>')" 
                                class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-red-700 bg-gradient-to-r from-red-100/80 to-pink-100/60 hover:from-red-200/90 hover:to-pink-200/70 rounded-lg transition-all duration-300 shadow-sm hover:shadow-md ring-1 ring-red-200/50 transform hover:scale-105 active:scale-95 backdrop-blur-sm whitespace-nowrap">
                            <i class="fas fa-times mr-1 text-red-600"></i>Unsubmit
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-gray-100 to-slate-200 rounded-full flex items-center justify-center mb-3 ring-1 ring-gray-200/50 shadow-sm">
                                        <i class="fas fa-inbox text-gray-400 text-lg opacity-70"></i>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900 mb-1">No Recent Activity</h3>
                                    <p class="text-gray-500 text-xs opacity-80">Activities will appear here when you submit requests.</p>
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

<script>
function unsubmitRequest(requestId, tableName) {
    // Enhanced confirmation dialog with better styling
    const confirmed = confirm('⚠️ Are you sure you want to unsubmit this request?\n\nThis action cannot be undone and will permanently remove the request from the system.');
    
    if (!confirmed) {
        return;
    }
    
    // Show loading state with better visual feedback
    const buttons = document.querySelectorAll(`button[onclick*="${requestId}"][onclick*="${tableName}"]`);
    buttons.forEach(button => {
        if (button.textContent.trim().includes('Unsubmit')) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1 text-red-600"></i><span class="text-red-700">Removing...</span>';
            button.classList.add('opacity-75', 'cursor-not-allowed', 'animate-pulse');
            button.classList.remove('hover:scale-105', 'hover:from-red-200/90', 'hover:to-pink-200/70', 'active:scale-95');
        }
    });
    
    // Make AJAX request to unsubmit
    fetch('../unsubmit_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: requestId,
            table: tableName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message with better styling
            alert('✅ Request successfully unsubmitted!\n\nThe page will now refresh to show updated activity.');
            
            // Add a fade out effect before reload
            buttons.forEach(button => {
                if (button.textContent.includes('Removing')) {
                    button.closest('tr').style.transition = 'all 0.5s ease-out';
                    button.closest('tr').style.opacity = '0';
                    button.closest('tr').style.transform = 'translateX(100px)';
                }
            });
            
            setTimeout(() => location.reload(), 500);
        } else {
            // Show error message and restore button
            alert('❌ Error: ' + (data.message || 'Failed to unsubmit request'));
            buttons.forEach(button => {
                if (button.textContent.includes('Removing')) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-times mr-1 text-red-600"></i>Unsubmit';
                    button.classList.remove('opacity-75', 'cursor-not-allowed', 'animate-pulse');
                    button.classList.add('hover:scale-105', 'hover:from-red-200/90', 'hover:to-pink-200/70', 'active:scale-95');
                }
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('🔥 Network error occurred. Please check your connection and try again.');
        // Restore button
        buttons.forEach(button => {
            if (button.textContent.includes('Removing')) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-times mr-1 text-red-600"></i>Unsubmit';
                button.classList.remove('opacity-75', 'cursor-not-allowed', 'animate-pulse');
                button.classList.add('hover:scale-105', 'hover:from-red-200/90', 'hover:to-pink-200/70', 'active:scale-95');
            }
        });
    });
}

// Add smooth scroll animation and enhanced interactions
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.max-h-64');
    if (tableContainer) {
        tableContainer.style.scrollBehavior = 'smooth';
    }
    
    // Add subtle animations on page load
    const activityRows = document.querySelectorAll('tbody tr');
    activityRows.forEach((row, index) => {
        if (row.children.length > 1) { // Not the empty state row
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'all 0.4s ease-out';
            
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 100);
        }
    });
    
    // Enhanced hover effects for the entire card
    const card = document.querySelector('[class*="rounded-2xl shadow-lg"]');
    if (card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
});
</script>
