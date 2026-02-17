<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check which view to display (current requests or history)
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$isHistoryView = ($view === 'history');

$pageTitle = $isHistoryView ? 'Overtime Requests History' : 'Overtime Requests';

// Hardcoded schedule times (same as schedule_tracker.php)
$schedule_times = [
    3 => ['in' => '07:30:00', 'out' => '16:30:00'],
    4 => ['in' => '07:00:00', 'out' => '16:00:00'],
    5 => ['in' => '08:00:00', 'out' => '17:00:00'],
    6 => ['in' => '09:00:00', 'out' => '18:00:00'],
    7 => ['in' => '10:00:00', 'out' => '19:00:00'],
    8 => ['in' => '06:00:00', 'out' => '15:00:00'],
    9 => ['in' => '08:00:00', 'out' => '16:30:00'],
    10 => ['in' => '07:40:00', 'out' => '16:40:00'],
    11 => ['in' => '06:30:00', 'out' => '15:00:00'],
];

// Function to get current schedule for an employee on a specific date
function getCurrentScheduleForEmployee($employee_id, $log_date, $pdo, $schedule_times) {
    // Get employee's default schedule
    $stmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $default_schedule_id = $employee['official_sched'] ?? 4;
    
    // Check for active approved schedule changes on that date
    $stmt = $pdo->prepare("
        SELECT work_schedule_id 
        FROM post_schedule_change_requests 
        WHERE employee_id = ? AND status = 'Approved' 
        AND ? BETWEEN start_date AND end_date 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $log_date]);
    $activeRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $schedule_id = $activeRequest ? ($activeRequest['work_schedule_id'] ?? $default_schedule_id) : $default_schedule_id;
    
    // Get schedule times
    $sched_time_in_24h = $schedule_times[$schedule_id]['in'] ?? '07:00:00';
    $sched_time_out_24h = $schedule_times[$schedule_id]['out'] ?? '16:00:00';
    
    return [
        'schedule_id' => $schedule_id,
        'time_in' => date('g:i A', strtotime($sched_time_in_24h)),
        'time_out' => date('g:i A', strtotime($sched_time_out_24h))
    ];
}

// Fetch overtime requests with employee names and time logs
if ($isHistoryView) {
    // Fetch from post2_overtime_requests table (archived approved/declined requests)
    $stmt = $pdo->query("SELECT DISTINCT por.id, por.employee_id, por.time_log_id, por.time_in, por.time_out, por.ot_duration, por.ot_type, por.reason, por.status, por.attachment, por.created_at, por.approved_at, por.approved_by, e.fname, e.lname, tl.log_date FROM post2_overtime_requests por JOIN employees e ON por.employee_id = e.id LEFT JOIN time_logs tl ON por.time_log_id = tl.id ORDER BY por.created_at DESC");
} else {
    // Fetch from post_ot_requests table for pending requests only
    $stmt = $pdo->query("SELECT DISTINCT por.id, por.employee_id, por.time_log_id, por.time_in, por.time_out, por.ot_duration, por.ot_type, por.reason, por.status, por.attachment, por.created_at, por.approved_at, por.approved_by, e.fname, e.lname, tl.log_date FROM post_ot_requests por JOIN employees e ON por.employee_id = e.id LEFT JOIN time_logs tl ON por.time_log_id = tl.id WHERE por.status = 'Pending' ORDER BY por.created_at DESC");
}
$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add schedule information to each request
foreach ($overtime_requests as &$ot) {
    if ($ot['log_date']) {
        $ot['current_schedule'] = getCurrentScheduleForEmployee($ot['employee_id'], $ot['log_date'], $pdo, $schedule_times);
    } else {
        $ot['current_schedule'] = ['schedule_id' => 'N/A', 'time_in' => 'N/A', 'time_out' => 'N/A'];
    }
}

// Function to format duration in hours and minutes
function formatDurationPHP($hours) {
    $totalHours = floatval($hours ?: 0);
    $wholeHours = floor($totalHours);
    $minutes = round(($totalHours - $wholeHours) * 60);
    
    if ($wholeHours == 0 && $minutes == 0) {
        return '0.00 hrs (0h 0m)';
    }
    
    $decimalFormat = number_format($totalHours, 2) . ' hrs';
    $timeFormat = $wholeHours . 'h ' . $minutes . 'm';
    
    return $decimalFormat . ' <span class="text-gray-500">(' . $timeFormat . ')</span>';
}

// Function to get status badge
function getStatusBadge($status) {
    $status = strtolower($status ?? 'pending');
    $classes = match($status) {
        'approved' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'rejected', 'declined' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
    
    $icon = match($status) {
        'approved' => 'fas fa-check',
        'pending' => 'fas fa-clock',
        'rejected', 'declined' => 'fas fa-times',
        default => 'fas fa-question'
    };
    
    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}\">
                <i class=\"{$icon} mr-1\"></i>" . ucfirst($status) . "
            </span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Overtime Requests</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
</head>
<body class="bg-gray-100">

 <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

<div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Display messages -->
                <?php if (isset($_GET['message'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars(urldecode($_GET['message'])) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>


                        </div>
                        <div class="flex space-x-2">
                            <a href="?view=current" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= !$isHistoryView ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-clock mr-2"></i>Current Requests
                            </a>
                            <a href="?view=history" 
                               class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors <?= $isHistoryView ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                <i class="fas fa-history mr-2"></i>History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Search and Pagination Controls -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
                    <input type="text" id="searchInput" placeholder="Search by employee, date, status, OT type, reason..." class="w-full md:w-1/3 px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200" />
                    <div id="pagination" class="flex items-center space-x-2 mt-2 md:mt-0"></div>
                </div>
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200" id="otTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hrs Rendered</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?= $isHistoryView ? 'Processed' : 'Submitted' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="otTableBody">
                            <!-- Table rows will be rendered by JS -->
                        </tbody>
                    </table>
                </div>
                <script>
                // Version: 2026-01-16-v2 - Compact pagination
                let otRequests = <?php echo json_encode($overtime_requests); ?>;
                const isHistoryView = <?php echo json_encode($isHistoryView); ?>;
                const rowsPerPage = 10;
                let currentPage = 1;
                let filteredRequests = otRequests;
                
                // Sort by created_at descending (newest first) for history view, or by ID for current view
                if (isHistoryView) {
                    otRequests = otRequests.sort((a, b) => {
                        const dateA = new Date(a.created_at || 0);
                        const dateB = new Date(b.created_at || 0);
                        return dateB - dateA;
                    });
                } else {
                    otRequests = otRequests.sort((a, b) => parseInt(a.id) - parseInt(b.id));
                }
                
                function renderTable() {
                    const tbody = document.getElementById('otTableBody');
                    tbody.innerHTML = '';
                    const startIdx = (currentPage - 1) * rowsPerPage;
                    const endIdx = startIdx + rowsPerPage;
                    const pageData = filteredRequests.slice(startIdx, endIdx);
                    if (pageData.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="11" class="text-center text-sm py-8 text-gray-500"><i class='fas fa-clock text-4xl text-gray-300 mb-2'></i><div>No overtime requests found.</div></td></tr>`;
                        return;
                    }
                    pageData.forEach(ot => {
                        const statusBadge = getStatusBadgeJS(ot.status);
                        const otTypeBadge = getOtTypeBadgeJS(ot.ot_type);
                        const actionCell = renderActionCell(ot);
                        tbody.innerHTML += `
                        <tr class="hover:bg-gray-50" id="row-${ot.id}">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-medium text-sm">${ot.fname.charAt(0).toUpperCase() + ot.lname.charAt(0).toUpperCase()}</span>
                                    </div>
                                    <div><div class="font-medium text-gray-900">${escapeHtml(ot.fname + ' ' + ot.lname)}</div></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex flex-col">
                                    <span class="font-medium">${ot.log_date ? formatDate(ot.log_date) : 'N/A'}</span>
                                    <span class="text-xs text-gray-500">${ot.log_date ? formatDay(ot.log_date) : ''}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex flex-col space-y-1">
                                    <div class="flex items-center text-xs">
                                        <i class="fas fa-sign-in-alt text-green-600 mr-1"></i>
                                        <span class="text-gray-600">In:</span>
                                        <span class="font-medium ml-1 ${isValidTime(ot.time_in) ? 'text-green-700' : 'text-gray-400 italic'}">${isValidTime(ot.time_in) ? formatTime(ot.time_in) : 'None'}</span>
                                    </div>
                                    <div class="flex items-center text-xs">
                                        <i class="fas fa-sign-out-alt text-red-600 mr-1"></i>
                                        <span class="text-gray-600">Out:</span>
                                        <span class="font-medium ml-1 ${isValidTime(ot.time_out) ? 'text-red-700' : 'text-gray-400 italic'}">${isValidTime(ot.time_out) ? formatTime(ot.time_out) : 'None'}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex flex-col space-y-1">
                                    <div class="flex items-center text-xs">
                                        <i class="fas fa-play text-green-600 mr-1"></i>
                                        <span class="text-gray-600">Start:</span>
                                        <span class="font-medium ml-1 ${ot.current_schedule.time_out !== 'N/A' ? 'text-green-700' : 'text-gray-400 italic'}">${ot.current_schedule.time_out !== 'N/A' ? ot.current_schedule.time_out : 'None'}</span>
                                    </div>
                                    <div class="flex items-center text-xs">
                                        <i class="fas fa-stop text-red-600 mr-1"></i>
                                        <span class="text-gray-600">End:</span>
                                        <span class="font-medium ml-1 ${isValidTime(ot.time_out) ? 'text-red-700' : 'text-gray-400 italic'}">${isValidTime(ot.time_out) ? formatTime(ot.time_out) : 'None'}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><div class="flex flex-col items-center"><div class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium"><i class="fas fa-clock mr-1"></i>${formatDuration(ot.ot_duration)}</div></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${otTypeBadge}</td>
                            <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden"><div class="truncate hover:whitespace-normal" title="${escapeHtml(ot.reason)}">${escapeHtml(ot.reason)}</div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${ot.attachment ? `<a href="../uploads/overtime_attachments/${escapeHtml(ot.attachment)}" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800"><i class="fas fa-paperclip mr-1"></i>View</a>` : `<span class="text-gray-400 italic">None</span>`}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><div class="flex flex-col"><span>${formatDate(ot.created_at)}</span><span class="text-xs">${formatTime(ot.created_at)}</span></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${actionCell}</td>
                        </tr>`;
                    });
                }
                function escapeHtml(text) {
                    return text ? text.replace(/[&<>'"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[c]; }) : '';
                }
                function formatDate(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                }
                function formatDay(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-US', { weekday: 'long' });
                }
                function formatTime(dateStr) {
                    // If time-only string (e.g., '07:30:00'), format as time
                    if (/^\d{2}:\d{2}:\d{2}$/.test(dateStr)) {
                        const [h, m, s] = dateStr.split(':');
                        const d = new Date();
                        d.setHours(h, m, s, 0);
                        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    }
                    // Otherwise, try to parse as datetime
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return 'None';
                    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                }
                function isValidTime(dateStr) {
                    if (!dateStr) return false;
                    // Accept time-only strings like '07:30:00'
                    if (/^\d{2}:\d{2}:\d{2}$/.test(dateStr)) return true;
                    const d = new Date(dateStr);
                    return !isNaN(d.getTime());
                }
                function formatDuration(hours) {
                    const totalHours = parseFloat(hours || 0);
                    const wholeHours = Math.floor(totalHours);
                    const minutes = Math.round((totalHours - wholeHours) * 60);
                    
                    if (wholeHours === 0 && minutes === 0) {
                        return '0 min';
                    } else if (wholeHours === 0) {
                        return `${minutes} min`;
                    } else if (minutes === 0) {
                        return `${wholeHours} hr${wholeHours > 1 ? 's' : ''}`;
                    } else {
                        return `${wholeHours} hr${wholeHours > 1 ? 's' : ''} ${minutes} min`;
                    }
                }
                function getStatusBadgeJS(status) {
                    status = (status || 'pending').toLowerCase();
                    const classes = { 'approved': 'bg-green-100 text-green-800', 'pending': 'bg-yellow-100 text-yellow-800', 'rejected': 'bg-red-100 text-red-800', 'declined': 'bg-red-100 text-red-800', };
                    const icons = { 'approved': 'fas fa-check', 'pending': 'fas fa-clock', 'rejected': 'fas fa-times', 'declined': 'fas fa-times', };
                    const cls = classes[status] || 'bg-gray-100 text-gray-800';
                    const icon = icons[status] || 'fas fa-question';
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}"><i class="${icon} mr-1"></i>${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                }
                function getOtTypeBadgeJS(type) {
                    type = (type || '').toLowerCase();
                    return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${escapeHtml(type)}</span>`;
                }
                function renderActionCell(ot) {
                    if (!isHistoryView) {
                        if ((ot.status || '').toLowerCase() === 'pending') {
                            return `<div class="flex space-x-1"><form method="post" action="process_ot_action.php" class="inline-block"><input type="hidden" name="request_id" value="${ot.id}"><input type="hidden" name="action" value="approve"><button type="submit" onclick="return confirm('Are you sure you want to approve this overtime request?')" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 text-xs transition-colors"><i class="fas fa-check mr-1"></i>Approve</button></form><button type="button" onclick="openDeclineModal(${ot.id})" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 text-xs transition-colors"><i class="fas fa-times mr-1"></i>Decline</button></div>`;
                        } else {
                            return `<span class="text-gray-500 italic"><i class="fas fa-check-circle mr-1"></i>Done</span>`;
                        }
                    } else {
                        return '';
                    }
                }
                function renderPagination() {
                    const pagDiv = document.getElementById('pagination');
                    pagDiv.innerHTML = '';
                    const totalPages = Math.ceil(filteredRequests.length / rowsPerPage);
                    if (totalPages <= 1) return;
                    
                    // Previous button
                    const prevBtn = document.createElement('button');
                    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                    prevBtn.className = `px-3 py-1 rounded ${currentPage === 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'}`;
                    prevBtn.disabled = currentPage === 1;
                    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updateTable(); } };
                    pagDiv.appendChild(prevBtn);
                    
                    // Page indicator with jump input
                    const pageInfo = document.createElement('div');
                    pageInfo.className = 'flex items-center space-x-2';
                    pageInfo.innerHTML = `
                        <span class="text-sm text-gray-600">Page</span>
                        <input type="number" id="pageJump" value="${currentPage}" min="1" max="${totalPages}" 
                               class="w-16 px-2 py-1 border rounded text-center text-sm focus:ring focus:ring-blue-200">
                        <span class="text-sm text-gray-600">of ${totalPages}</span>
                    `;
                    pagDiv.appendChild(pageInfo);
                    
                    // Add event listener for page jump
                    setTimeout(() => {
                        const jumpInput = document.getElementById('pageJump');
                        jumpInput.addEventListener('change', function() {
                            let page = parseInt(this.value);
                            if (page >= 1 && page <= totalPages) {
                                currentPage = page;
                                updateTable();
                            } else {
                                this.value = currentPage;
                            }
                        });
                        jumpInput.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                this.blur();
                            }
                        });
                    }, 0);
                    
                    // Next button
                    const nextBtn = document.createElement('button');
                    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                    nextBtn.className = `px-3 py-1 rounded ${currentPage === totalPages ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'}`;
                    nextBtn.disabled = currentPage === totalPages;
                    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updateTable(); } };
                    pagDiv.appendChild(nextBtn);
                }
                function updateTable() {
                    renderTable();
                    renderPagination();
                }
                document.getElementById('searchInput').addEventListener('input', function(e) {
                    const val = e.target.value.toLowerCase();
                    filteredRequests = otRequests.filter(ot => {
                        return (
                            (ot.fname + ' ' + ot.lname).toLowerCase().includes(val) ||
                            (ot.log_date || '').toLowerCase().includes(val) ||
                            (ot.status || '').toLowerCase().includes(val) ||
                            (ot.ot_type || '').toLowerCase().includes(val) ||
                            (ot.reason || '').toLowerCase().includes(val)
                        );
                    });
                    currentPage = 1;
                    updateTable();
                });
                // Initial render
                updateTable();
                </script>

                <!-- Summary Cards -->
                <?php if (!empty($overtime_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'declined' => 0];
                    $totalHours = 0;
                    foreach ($overtime_requests as $req) {
                        $status = strtolower($req['status'] ?? 'pending');
                        if (isset($summary[$status])) {
                            $summary[$status]++;
                        }
                        
                        // Calculate total approved hours
                        if ($status === 'approved') {
                            $totalHours += $req['ot_duration'];
                        }
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-list text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="text-lg font-semibold text-gray-900"><?= count($overtime_requests) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['pending'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['approved'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="fas fa-business-time text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved Hours</p>
                                <p class="text-lg font-semibold text-gray-900"><?= formatDurationPHP($totalHours) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Overtime Request
            </h2>
            <form method="POST" action="process_ot_action.php">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="action" value="decline">

                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                          placeholder="Provide reason for declining..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeDeclineModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeclineModal(requestId) {
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('explanation').value = '';
            document.getElementById('declineModal').classList.remove('hidden');
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').classList.add('hidden');
        }
    </script>

</body>
</html>