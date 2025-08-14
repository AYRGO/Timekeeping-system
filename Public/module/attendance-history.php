<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;

// Fetch employee's default schedule from employees table
$empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
$empStmt->execute([$employee_id]);
$employee = $empStmt->fetch(PDO::FETCH_ASSOC);
$default_schedule_id = $employee['official_sched'] ?? 4;

// Fetch all approved schedule changes from post_schedule_change_requests
$scheduleChangesStmt = $pdo->prepare("
    SELECT work_schedule_id, start_date, end_date 
    FROM post_schedule_change_requests 
    WHERE employee_id = ? AND status = 'Approved' 
    ORDER BY created_at DESC
");
$scheduleChangesStmt->execute([$employee_id]);
$approvedScheduleChanges = $scheduleChangesStmt->fetchAll(PDO::FETCH_ASSOC);

// Store latest approved schedule change in session for future use
$scheduleRequestStmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE employee_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
$scheduleRequestStmt->execute([$employee_id]);
$latestScheduleRequest = $scheduleRequestStmt->fetch(PDO::FETCH_ASSOC);

if ($latestScheduleRequest) {
    $_SESSION['schedule_request'] = [
        'start_date' => $latestScheduleRequest['start_date'],
        'end_date' => $latestScheduleRequest['end_date'],
        'work_schedule_id' => $latestScheduleRequest['work_schedule_id'],
        'status' => $latestScheduleRequest['status']
    ];
}

$schedule_times = [
        1 => ['in' => '06:30:00', 'out' => '15:30:00'],
        2 => ['in' => '08:00:00', 'out' => '19:00:00'],
        3 => ['in' => '07:30:00', 'out' => '16:30:00'],
        4 => ['in' => '07:00:00', 'out' => '16:00:00'],
        5 => ['in' => '08:00:00', 'out' => '17:00:00'],
        6 => ['in' => '09:00:00', 'out' => '18:00:00'],
        7 => ['in' => '10:00:00', 'out' => '19:00:00'],
        8 => ['in' => '06:00:00', 'out' => '15:00:00'],
        9 => ['in' => '08:00:00', 'out' => '16:30:00'],
        10 => ['in' => '07:40:00', 'out' => '16:40:00'],
        11 => ['in' => '06:30:00', 'out' => '15:00:00'],
        12 => ['in' => '06:30:00', 'out' => '17:30:00'],
        13 => ['in' => '07:00:00', 'out' => '18:00:00'],
        14 => ['in' => '06:00:00', 'out' => '17:00:00'],
        15 => ['in' => '06:00:00', 'out' => '16:00:00'],
        16 => ['in' => '08:30:00', 'out' => '16:30:00'],
        17 => ['in' => '06:00:00', 'out' => '12:00:00'],
        18 => ['in' => '06:00:00', 'out' => '14:30:00'],
];

// Fetch all logs for July 1, 2025 onwards
$allLogsStmt = $pdo->prepare("
    SELECT 
        t.log_date, t.time_in, t.time_out, 
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

// Create OT status map
$otStatusMap = [];
foreach ($otRequests as $otRequest) {
    $otStatusMap[$otRequest['log_date']] = $otRequest['ot_status'];
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
        <h3 class="text-lg font-semibold text-gray-800">My Attendance History</h3>
        
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OT Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($currentPageDates)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
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

                            // Updated schedule logic - check post_schedule_change_requests
                            $schedule_id_to_use = $default_schedule_id;
                            
                            // Check if there's an approved schedule change for this date
                            foreach ($approvedScheduleChanges as $scheduleChange) {
                                if ($logDate >= $scheduleChange['start_date'] && $logDate <= $scheduleChange['end_date']) {
                                    $schedule_id_to_use = $scheduleChange['work_schedule_id'];
                                    break; // Use the first matching (most recent) schedule change
                                }
                            }

                            if (isset($schedule_times[$schedule_id_to_use])) {
                                $schedule_in_24h = $schedule_times[$schedule_id_to_use]['in'];
                                $schedule_out_24h = $schedule_times[$schedule_id_to_use]['out'];
                                $schedule_in = date('h:i A', strtotime($schedule_in_24h));
                                $schedule_out = date('h:i A', strtotime($schedule_out_24h));
                            } else {
                                $schedule_in_24h = '07:00:00';
                                $schedule_out_24h = '16:00:00';
                                $schedule_in = '07:00 AM';
                                $schedule_out = '04:00 PM';
                            }

                            // Handle times
                            $isApproved = isset($log) && strtolower($log['request_status'] ?? '') === 'approved';
                            $timeIn = $isApproved ? ($log['requested_time_in'] ?? null) : ($log['time_in'] ?? null);
                            $timeOut = $isApproved ? ($log['requested_time_out'] ?? null) : ($log['time_out'] ?? null);

                            $timeInDisplay = $timeIn ? date('h:i A', strtotime($timeIn)) : '-';
                            $timeOutDisplay = $timeOut ? date('h:i A', strtotime($timeOut)) : '-';

                            $hoursWorked = '-';
                            if ($timeIn && $timeOut) {
                                $start = new DateTime($timeIn);
                                $end = new DateTime($timeOut);
                                $diff = $start->diff($end);
                                $hours = $diff->h + ($diff->i / 60);
                                $hoursWorked = number_format($hours, 2);
                            }

                            // Status calculation based on actual schedule (official or approved change)
                            $status = '-';
                            $badgeClass = 'bg-gray-100 text-gray-800';

                            if ($timeIn) {
                                // Use 24-hour format for accurate comparison
                                $actualTimeIn = date('H:i:s', strtotime($timeIn));
                                $actualTimeOut = $timeOut ? date('H:i:s', strtotime($timeOut)) : null;
                                
                                // Calculate grace period (15 minutes after scheduled time in)
                                $scheduledTimeIn = $schedule_in_24h;
                                $graceTimeIn = date('H:i:s', strtotime($scheduledTimeIn . ' +15 minutes'));
                                $scheduledTimeOut = $schedule_out_24h;

                                // Check if on time (within 15-minute grace period)
                                if ($actualTimeIn <= $graceTimeIn) {
                                    $status = 'On Time';
                                    $badgeClass = 'bg-green-100 text-green-800';
                                } else {
                                    $status = 'Late';
                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                }

                                // Check for early departure
                                if ($actualTimeOut && $actualTimeOut < $scheduledTimeOut) {
                                    $status = 'Left Early';
                                    $badgeClass = 'bg-red-100 text-red-800';
                                }
                            }

                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $formattedDate ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $dayName ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= $timeInDisplay ?>
                                <div class="text-xs text-gray-400 mt-1">
                                    Sched: <?= $schedule_in ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= $timeOutDisplay ?>
                                <div class="text-xs text-gray-400 mt-1">
                                    Sched: <?= $schedule_out ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $hoursWorked ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                $otStatus = $otStatusMap[$logDate] ?? null;
                                if ($otStatus): 
                                    $otBadgeClass = '';
                                    switch (strtolower($otStatus)) {
                                        case 'pending':
                                            $otBadgeClass = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'approved':
                                            $otBadgeClass = 'bg-green-100 text-green-800';
                                            break;
                                        case 'rejected':
                                            $otBadgeClass = 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            $otBadgeClass = 'bg-gray-100 text-gray-800';
                                    }
                                ?>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $otBadgeClass ?>">
                                        <?= ucfirst($otStatus) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">-</span>
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