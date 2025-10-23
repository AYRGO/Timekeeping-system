<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;

// Removed: Fetching employee's default schedule and hardcoded schedule_times array
// Now using employee_daily_schedule_cache for all schedule lookups

// Fetch all logs for July 1, 2025 onwards - Updated to include status and log_out_date columns
$allLogsStmt = $pdo->prepare("
    SELECT 
        t.log_date, t.time_in, t.time_out, t.log_out_date, t.status,
        r.requested_time_in, r.requested_time_out, r.status AS request_status
    FROM time_logs t
    LEFT JOIN (
        SELECT r1.* 
        FROM post_time_adjustment_requests r1 
        INNER JOIN (
            SELECT employee_id, log_date, MAX(id) AS latest_id 
            FROM post_time_adjustment_requests 
            WHERE status = 'approved' 
            GROUP BY employee_id, log_date
        ) r2 ON r1.id = r2.latest_id
    ) r ON t.employee_id = r.employee_id AND t.log_date = r.log_date
    WHERE t.employee_id = ? AND t.log_date >= '2025-07-01'
    ORDER BY t.log_date DESC
");
$allLogsStmt->execute([$employee_id]);
$logs = $allLogsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch overtime requests for the same period
$otRequestsStmt = $pdo->prepare("
    SELECT 
        DATE(tl.log_date) as log_date,
        COALESCE(por.status, or_table.status) as ot_status
    FROM time_logs tl
    LEFT JOIN overtime_requests or_table ON tl.employee_id = or_table.employee_id AND DATE(tl.log_date) = DATE(or_table.created_at)
    LEFT JOIN post_ot_requests por ON tl.employee_id = por.employee_id AND DATE(tl.log_date) = DATE(por.created_at)
    WHERE tl.employee_id = ? AND tl.log_date >= '2025-07-01'
");
$otRequestsStmt->execute([$employee_id]);
$otRequests = $otRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved leave requests for the same period
$leaveRequestsStmt = $pdo->prepare("
    SELECT start_date, end_date, leave_type
    FROM post_leave_requests 
    WHERE employee_id = ? AND status = 'approved'
    AND end_date >= '2025-07-01'
");
$leaveRequestsStmt->execute([$employee_id]);
$approvedLeaves = $leaveRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Create OT status map
$otStatusMap = [];
foreach ($otRequests as $otRequest) {
    $otStatusMap[$otRequest['log_date']] = $otRequest['ot_status'];
}

// Function to check if a date is within approved leave period
function isOnApprovedLeave($checkDate, $approvedLeaves) {
    foreach ($approvedLeaves as $leave) {
        if ($checkDate >= $leave['start_date'] && $checkDate <= $leave['end_date']) {
            return $leave['leave_type'];
        }
    }
    return false;
}

// Create date-indexed map
$logMap = [];
foreach ($logs as $log) {
    $logMap[$log['log_date']] = $log;
}

// Build complete date range (all dates from July 1 to today)
$start = new DateTime('2025-07-01');
$end = new DateTime();
$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($start, $interval, $end);

// Convert to array and reverse (most recent first)
$allDates = array_reverse(iterator_to_array($dateRange));

// Search functionality
$searchDate = $_GET['search'] ?? '';
$filteredDates = $allDates;

if (!empty($searchDate)) {
    $filteredDates = array_filter($allDates, function($dateObj) use ($searchDate) {
        $logDate = $dateObj->format('Y-m-d');
        $formattedDate = $dateObj->format('d M Y');
        $dayName = $dateObj->format('l');
        
        // Search in date, formatted date, or day name
        return (
            stripos($logDate, $searchDate) !== false ||
            stripos($formattedDate, $searchDate) !== false ||
            stripos($dayName, $searchDate) !== false
        );
    });
}

// Pagination setup
$itemsPerPage = 5;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalItems = count($filteredDates);
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$offset = ($currentPage - 1) * $itemsPerPage;

// Get current page items
$currentPageDates = array_slice($filteredDates, $offset, $itemsPerPage);
?>

<div class="bg-white rounded-lg shadow p-6 mt-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">My Attendance History</h3>
        </div>
        
        <!-- Search Form -->
        <form method="GET" class="flex items-center gap-2">
            <div class="relative">
                <input 
                    type="text" 
                    name="search"
                    id="searchInput"
                    placeholder="Search by date, day..." 
                    value="<?= htmlspecialchars($searchDate) ?>"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
            
            <!-- Search Button -->
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
            
            <!-- Clear Button -->
            <?php if (!empty($searchDate)): ?>
                <a href="?" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors" title="Clear search">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
            
            <!-- Preserve page number if needed -->
            <?php if (isset($_GET['page']) && !empty($searchDate)): ?>
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
            <?php endif; ?>
        </form>
    </div>

    <!-- Results Info -->
    <div class="mb-4 text-sm text-gray-600">
        <?php if (!empty($searchDate)): ?>
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-blue-500"></i>
                <span>Showing <?= count($filteredDates) ?> result(s) for "<strong><?= htmlspecialchars($searchDate) ?></strong>"</span>
            </div>
        <?php else: ?>
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar text-green-500"></i>
                <span>Showing <?= count($currentPageDates) ?> of <?= $totalItems ?> records</span>
            </div>
        <?php endif; ?>
        
        <?php // Removed status counting and display logic ?>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($currentPageDates)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-search text-4xl text-gray-300"></i>
                                <?php if (!empty($searchDate)): ?>
                                    <p>No attendance records found for "<strong><?= htmlspecialchars($searchDate) ?></strong>"</p>
                                    <p class="text-sm">Try searching with different keywords</p>
                                <?php else: ?>
                                    <p>No attendance records found</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($currentPageDates as $dateObj): ?>
                        <?php
                            $logDate = $dateObj->format('Y-m-d');
                            $dayName = $dateObj->format('l');
                            $formattedDate = $dateObj->format('d M Y');

                            $log = $logMap[$logDate] ?? null;

                            // Fetch schedule using the same logic as schedule_content.php calendar
                            // Query the pre-computed cache table
                            $cacheStmt = $pdo->prepare("
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
                            $cacheStmt->execute([$employee_id, $logDate]);
                            $scheduleCache = $cacheStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Initialize default values
                            $isRestDay = false;
                            $schedule_in_24h = '07:00:00';
                            $schedule_out_24h = '16:00:00';
                            
                            // If found in cache, use cache data
                            if ($scheduleCache) {
                                $isRestDay = ($scheduleCache['is_rest_day'] == 1);
                                
                                if ($scheduleCache['work_schedule_id'] && $scheduleCache['time_in'] && $scheduleCache['time_out']) {
                                    $schedule_in_24h = $scheduleCache['time_in'];
                                    $schedule_out_24h = $scheduleCache['time_out'];
                                }
                            } else {
                                // Fallback: Check employee_default_schedules (same as schedule_content.php)
                                $dayOfWeek = date('w', strtotime($logDate));
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
                                $weeklyStmt->execute([$employee_id, $dayOfWeek, $logDate, $logDate]);
                                $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($weekly) {
                                    $isRestDay = ($weekly['is_rest_day'] == 1);
                                    if ($weekly['work_schedule_id'] && $weekly['time_in'] && $weekly['time_out']) {
                                        $schedule_in_24h = $weekly['time_in'];
                                        $schedule_out_24h = $weekly['time_out'];
                                    }
                                } else {
                                    // Final fallback: Check if weekend
                                    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                                        $isRestDay = true;
                                    }
                                }
                            }
                            
                            $schedule_in = date('h:i A', strtotime($schedule_in_24h));
                            $schedule_out = date('h:i A', strtotime($schedule_out_24h));

                            // Handle times - Updated to consider status and log_out_date
                            $isApproved = isset($log) && strtolower($log['request_status'] ?? '') === 'approved';
                            if ($isApproved) {
                                $timeIn = $log['requested_time_in'] ?? $log['time_in'] ?? null;
                                $timeOut = !empty($log['requested_time_out']) ? $log['requested_time_out'] : ($log['time_out'] ?? null);
                                $logOutDate = $log['log_out_date'] ?? null;
                            } else {
                                $timeIn = $log['time_in'] ?? null;
                                $timeOut = $log['time_out'] ?? null;
                                $logOutDate = $log['log_out_date'] ?? null;
                            }
                            
                            // Check if this is a cross-midnight shift
                            $isCrossMidnight = $logOutDate && $logOutDate !== $logDate;

                            // Check if this is an auto-marked incomplete shift
                            $isAutoIncomplete = $log && ($log['status'] === 'incomplete' || $log['time_out'] === 'INC');
                            
                            // Check for night shift
                            $isNightShift = $timeIn && strtotime($timeIn) > strtotime('18:00:00');

                            $timeInDisplay = $timeIn ? date('h:i A', strtotime($timeIn)) : '-';
                            
                            // Handle time out display - Updated for incomplete logic and cross-midnight
                            if ($isAutoIncomplete) {
                                $timeOutDisplay = '<span class="text-red-600 font-bold">INC</span>';
                            } elseif ($timeOut && $timeOut !== 'INC') {
                                $timeOutDisplay = date('h:i A', strtotime($timeOut));
                            } else {
                                $timeOutDisplay = '-';
                            }

                            // Calculate hours worked - Updated to handle incomplete shifts and cross-midnight
                            $hoursWorked = '-';
                            if ($timeIn && $timeOut && $timeOut !== 'INC' && !$isAutoIncomplete) {
                                // Use log_out_date for accurate cross-midnight calculation
                                if ($isCrossMidnight) {
                                    $start = new DateTime($logDate . ' ' . $timeIn);
                                    $end = new DateTime($logOutDate . ' ' . $timeOut);
                                } else {
                                    $start = new DateTime($logDate . ' ' . $timeIn);
                                    $end = new DateTime($logDate . ' ' . $timeOut);
                                    
                                    // Handle overnight calculation (fallback logic)
                                    if ($end < $start) {
                                        $end->add(new DateInterval('P1D'));
                                    }
                                }
                                
                                $diff = $start->diff($end);
                                $totalHours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
                                
                                // Only deduct 1 hour for lunch break if total hours is 8 or above
                                if ($totalHours >= 8) {
                                    $totalHours -= 1; // Deduct 1 hour for lunch break
                                }
                                
                                if ($totalHours < 0) $totalHours = 0;
                                
                                // If more than 16 hours, show blank (likely an error)
                                if ($totalHours > 16) {
                                    $hoursWorked = '-';
                                } else {
                                    $hoursWorked = number_format($totalHours, 2);
                                }
                                

                            }

                            // Check if this date is on approved leave first
                            $leaveType = isOnApprovedLeave($logDate, $approvedLeaves);
                            
                            // Calculate late and undertime minutes
                            $lateMinutes = 0;
                            $undertimeMinutes = 0;
                            
                            if ($timeIn && !$leaveType) {
                                $actualTimeIn = date('H:i:s', strtotime($timeIn));
                                $scheduledTimeIn = $schedule_in_24h;
                                $graceTimeIn = date('H:i:s', strtotime($scheduledTimeIn . ' +15 minutes'));
                                
                                // Calculate late minutes (if arrived after grace period)
                                if ($actualTimeIn > $graceTimeIn) {
                                    $lateSeconds = strtotime($actualTimeIn) - strtotime($graceTimeIn);
                                    $lateMinutes = round($lateSeconds / 60);
                                }
                            }
                            
            if ($timeOut && $timeOut !== 'INC' && !$leaveType && !$isAutoIncomplete) {
                $actualTimeOut = date('H:i:s', strtotime($timeOut));
                $scheduledTimeOut = $schedule_out_24h;
                
                // Calculate undertime minutes (if left before scheduled time out)
                if (!$isCrossMidnight && $actualTimeOut < $scheduledTimeOut) {
                    $undertimeSeconds = strtotime($scheduledTimeOut) - strtotime($actualTimeOut);
                    $undertimeMinutes = round($undertimeSeconds / 60);
                }
            }                            // Updated status calculation
                            $status = '-';
                            $badgeClass = 'bg-gray-100 text-gray-800';

                            if ($leaveType) {
                                // Employee is on approved leave
                                $status = 'On Leave';
                                $badgeClass = 'bg-purple-100 text-purple-800';
                            } elseif ($isRestDay && !$timeIn) {
                                // Rest day/Off day with no time in (not working)
                                $status = 'Off';
                                $badgeClass = 'bg-gray-100 text-gray-600';
                            } elseif ($isRestDay && $timeIn) {
                                // Rest day but employee worked (rest day work)
                                if (!$timeOut || $timeOut === 'INC' || $isAutoIncomplete) {
                                    $status = 'Rest Day Work (INC)';
                                    $badgeClass = 'bg-blue-100 text-blue-800';
                                } else {
                                    $status = 'Rest Day Work';
                                    $badgeClass = 'bg-blue-100 text-blue-800';
                                }
                            } elseif ($isAutoIncomplete || !$timeOut || $timeOut === 'INC') {
                                // Missing time out or auto-marked incomplete
                                $status = 'Incomplete';
                                $badgeClass = 'bg-red-100 text-red-800';
                            } elseif ($timeIn && $timeOut && $timeOut !== 'INC') {
                                // Both time in and time out are present
                                $actualTimeIn = date('H:i:s', strtotime($timeIn));
                                $actualTimeOut = date('H:i:s', strtotime($timeOut));
                                
                                // Calculate grace period (15 minutes after scheduled time in)
                                $scheduledTimeIn = $schedule_in_24h;
                                $graceTimeIn = date('H:i:s', strtotime($scheduledTimeIn . ' +15 minutes'));
                                $scheduledTimeOut = $schedule_out_24h;
                                
                                // Check if late (arrived after grace period)
                                $isLate = $actualTimeIn > $graceTimeIn;
                                
                // Check if undertime (left early before scheduled time out)
                $isUndertime = false;
                
                if ($isCrossMidnight) {
                    // For cross-midnight shifts, we need to handle the time comparison differently
                    // The scheduled out time might be on the next day
                    $isUndertime = false; // For cross-midnight, consider complete unless obviously early
                } else {
                    // Regular shift - check if left before scheduled time out
                    $isUndertime = $actualTimeOut < $scheduledTimeOut;
                }                                // Determine final status - prioritize Late over Undertime
                                if ($isLate) {
                                    $status = $lateMinutes > 0 ? "Late ({$lateMinutes}mins)" : 'Late';
                                    $badgeClass = 'bg-orange-100 text-orange-800';
                                } elseif ($isUndertime) {
                                    $status = $undertimeMinutes > 0 ? "Undertime ({$undertimeMinutes}mins)" : 'Undertime';
                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    $status = 'Complete';
                                    $badgeClass = 'bg-green-100 text-green-800';
                                }
                            } else {
                                // No time in record and not on leave
                                $status = 'Incomplete';
                                $badgeClass = 'bg-red-100 text-red-800';
                            }

                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?php if ($isCrossMidnight): ?>
                                    <?= date('j', strtotime($logDate)) ?> - <?= date('j M Y', strtotime($logOutDate)) ?>
                                <?php else: ?>
                                    <?= $formattedDate ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $dayName ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= $timeInDisplay ?>
                                <?php if ($isNightShift): ?>
                                    <div class="text-xs text-blue-600 mt-1">
                                        <i class="fas fa-moon mr-1"></i>Night Shift
                                    </div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-400 mt-1">
                                    Sched: <?= $schedule_in ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php if ($isAutoIncomplete): ?>
                                    <?= $timeOutDisplay ?>
                                <?php elseif ($timeOut && $timeOut !== 'INC'): ?>
                                    <?= $timeOutDisplay ?>
                                <?php elseif ($timeIn): ?>
                                    <?php 
                                    // Updated logic for showing time out status
                                    $isToday = ($logDate === date('Y-m-d'));
                                    
                                    if ($isToday) {
                                        if ($isNightShift) {
                                            // For night shifts today, check progress
                                            $currentTime = date('H:i:s');
                                            $scheduledOut = $schedule_out_24h;
                                            
                                            if ($currentTime < $scheduledOut) {
                                                echo '<span class="text-blue-600 font-medium">In Progress</span>';
                                            } else {
                                                $timeSinceScheduled = strtotime($currentTime) - strtotime($scheduledOut);
                                                if ($timeSinceScheduled > (12 * 3600)) { // 12 hours
                                                    echo '<span class="text-red-600 font-medium">Will Auto-Mark</span>';
                                                } else {
                                                    echo '<span class="text-orange-600 font-medium">Overdue</span>';
                                                }
                                            }
                                        } else {
                                            // Regular shift today
                                            echo '<span class="text-blue-600 font-medium">In Progress</span>';
                                        }
                                    } else {
                                        // Past dates show missing
                                        echo '<span class="text-orange-600 font-medium">Missing</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                                <div class="text-xs text-gray-400 mt-1">
                                    Sched: <?= $schedule_out ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php if ($isAutoIncomplete): ?>
                                    <span class="text-red-600 font-medium">-</span>
                                <?php else: ?>
                                    <?= $hoursWorked ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                                <?php if ($isApproved): ?>
                                    <div class="text-xs text-green-600 mt-1">
                                        <i class="fas fa-check mr-1"></i>Adjusted
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
            <div class="text-sm text-gray-700">
                Showing <?= $offset + 1 ?> to <?= min($offset + $itemsPerPage, $totalItems) ?> of <?= $totalItems ?> results
            </div>
            
            <nav class="flex items-center space-x-1">
                <!-- Previous Button -->
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?><?= !empty($searchDate) ? '&search=' . urlencode($searchDate) : '' ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                    <a href="?page=1<?= !empty($searchDate) ? '&search=' . urlencode($searchDate) : '' ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="px-3 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-md">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($searchDate) ? '&search=' . urlencode($searchDate) : '' ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $totalPages ?><?= !empty($searchDate) ? '&search=' . urlencode($searchDate) : '' ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700"><?= $totalPages ?></a>
                <?php endif; ?>

                <!-- Next Button -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= !empty($searchDate) ? '&search=' . urlencode($searchDate) : '' ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    
    // Focus on search input for better UX
    searchInput.addEventListener('focus', function() {
        this.select();
    });
    
    // Optional: Add some visual feedback when typing
    searchInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            this.style.borderColor = '#3B82F6';
        } else {
            this.style.borderColor = '#D1D5DB';
        }
    });
});
</script>