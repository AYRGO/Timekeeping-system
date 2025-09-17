<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

$pageTitle = 'Leave Request List';
// Check which view to display (current requests or history)
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$isHistoryView = ($view === 'history');

$pageTitle = $isHistoryView ? 'Leave Requests History' : 'Leave Requests';

// Fetch leave requests with employee names and attachments 

if ($isHistoryView) {
    // Check if processed_at column exists
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM post_leave_requests LIKE 'processed_at'");
        $hasProcessedAt = $checkColumn->rowCount() > 0;
    } catch (Exception $e) {
        $hasProcessedAt = false;
    }
    
    if ($hasProcessedAt) {
        // Use processed_at column if it exists
        $sql = "
            SELECT id, leave_type, start_date, end_date, reason, status, attachment_lr, created_at, processed_at, explanation, employee_id, fname, lname
            FROM (
                SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.attachment_lr,
                       lr.created_at, lr.created_at AS processed_at, '' AS explanation, lr.employee_id,
                       e.fname, e.lname
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                WHERE lr.status != 'pending'
                UNION ALL
                SELECT plr.id, plr.leave_type, plr.start_date, plr.end_date, plr.reason, plr.status, plr.attachment_lr,
                       plr.created_at, COALESCE(plr.processed_at, plr.created_at) AS processed_at, plr.explanation, plr.employee_id,
                       e.fname, e.lname
                FROM post_leave_requests plr
                JOIN employees e ON plr.employee_id = e.id
            ) AS all_requests
            ORDER BY processed_at DESC
        ";
    } else {
        // Fallback to created_at if processed_at doesn't exist
        $sql = "
            SELECT id, leave_type, start_date, end_date, reason, status, attachment_lr, created_at, created_at AS processed_at, explanation, employee_id, fname, lname
            FROM (
                SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.attachment_lr,
                       lr.created_at, '' AS explanation, lr.employee_id,
                       e.fname, e.lname
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                WHERE lr.status != 'pending'
                UNION ALL
                SELECT plr.id, plr.leave_type, plr.start_date, plr.end_date, plr.reason, plr.status, plr.attachment_lr,
                       plr.created_at, plr.explanation, plr.employee_id,
                       e.fname, e.lname
                FROM post_leave_requests plr
                JOIN employees e ON plr.employee_id = e.id
            ) AS all_requests
            ORDER BY created_at DESC
        ";
    }
} else {
    // Fetch from leave_requests table (current requests) - only pending
    $sql = "
        SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.attachment_lr,
               lr.created_at, lr.employee_id,
               e.fname, e.lname
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE lr.status = 'pending'
        ORDER BY lr.start_date DESC
    ";
}
$stmt = $pdo->prepare($sql);
$stmt->execute();
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Function to get leave type badge
function getLeaveTypeBadge($type) {
    $type = strtolower($type ?? 'unknown');
    $classes = match($type) {
        'sick_leave' => 'bg-red-100 text-red-800',
        'vacation_leave' => 'bg-blue-100 text-blue-800',
        'emergency_leave' => 'bg-orange-100 text-orange-800',
        'maternity_leave' => 'bg-pink-100 text-pink-800',
        'paternity_leave' => 'bg-indigo-100 text-indigo-800',
        default => 'bg-gray-100 text-gray-800'
    };
    
    $displayName = ucfirst(str_replace('_', ' ', $type));
    
    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}\">
                {$displayName}
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
    <title>Leave Requests</title>
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
                    <div class="flex flex-col md:flex-row gap-2 md:w-2/3">
                        <input type="text" id="searchInput" placeholder="Search by employee, leave type, status..." class="flex-1 px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200" />
                        <select id="sortSelect" class="px-4 py-2 border rounded-lg focus:ring focus:ring-blue-200 bg-white">
                            <?php if ($isHistoryView): ?>
                                <option value="processed_at_desc">Processed Date (Newest)</option>
                                <option value="processed_at_asc">Processed Date (Oldest)</option>
                            <?php else: ?>
                                <option value="created_at_desc">Submitted Date (Newest)</option>
                                <option value="created_at_asc">Submitted Date (Oldest)</option>
                            <?php endif; ?>
                            <option value="employee_asc">Employee A-Z</option>
                            <option value="employee_desc">Employee Z-A</option>
                            <option value="start_date_desc">Start Date (Latest)</option>
                            <option value="start_date_asc">Start Date (Earliest)</option>
                            <option value="status">Status</option>
                        </select>
                    </div>
                    <div id="pagination" class="flex items-center space-x-2 mt-2 md:mt-0"></div>
                </div>

                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200" id="leaveTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leave Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?= $isHistoryView ? 'Processed' : 'Submitted' ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="leaveTableBody">
                            <!-- Table rows will be rendered by JS -->
                        </tbody>
                    </table>
                </div>

                <script>
                // Prepare leave requests data for JS
                let leaveRequests = <?php echo json_encode($leave_requests); ?>;
                const isHistoryView = <?php echo json_encode($isHistoryView); ?>;
                
                // Sort by processed_at descending (newest first) for history view, or by ID for current view
                if (isHistoryView) {
                    leaveRequests = leaveRequests.sort((a, b) => {
                        const dateA = new Date(a.processed_at || a.created_at || 0);
                        const dateB = new Date(b.processed_at || b.created_at || 0);
                        return dateB - dateA;
                    });
                } else {
                    leaveRequests = leaveRequests.sort((a, b) => parseInt(a.id) - parseInt(b.id));
                }
                const rowsPerPage = 10;
                let currentPage = 1;
                let filteredRequests = leaveRequests;

                function renderTable() {
                    try {
                        const tbody = document.getElementById('leaveTableBody');
                        if (!tbody) {
                            console.error('Table body element not found');
                            return;
                        }
                        
                        tbody.innerHTML = '';
                        const startIdx = (currentPage - 1) * rowsPerPage;
                        const endIdx = startIdx + rowsPerPage;
                        const pageData = filteredRequests.slice(startIdx, endIdx);
                        
                        if (pageData.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="10" class="text-center text-sm py-8 text-gray-500">
                                <i class='fas fa-calendar-times text-4xl text-gray-300 mb-2'></i>
                                <div>No leave requests found.</div>
                            </td></tr>`;
                            return;
                        }
                    pageData.forEach(lr => {
                        const statusBadge = getStatusBadgeJS(lr.status);
                        const leaveTypeBadge = getLeaveTypeBadgeJS(lr.leave_type);
                        const actionCell = isHistoryView ? renderHistoryAction(lr) : renderCurrentAction(lr);
                        tbody.innerHTML += `
                        <tr class="hover:bg-gray-50" id="leave-row-${lr.id}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${lr.id}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-medium text-sm">
                                            ${lr.fname.charAt(0).toUpperCase() + lr.lname.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">${escapeHtml(lr.fname + ' ' + lr.lname)}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${leaveTypeBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex flex-col">
                                    <span class="font-medium">${formatDate(lr.start_date)}</span>
                                    <span class="text-xs text-gray-500">${formatDay(lr.start_date)}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex flex-col">
                                    <span class="font-medium">${formatDate(lr.end_date)}</span>
                                    <span class="text-xs text-gray-500">${formatDay(lr.end_date)}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
                                <div class="truncate hover:whitespace-normal" title="${escapeHtml(lr.reason)}">
                                    ${escapeHtml(lr.reason)}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                ${lr.attachment_lr ? `<a href="../uploads/leave_attachments/${escapeHtml(lr.attachment_lr)}" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800"><i class="fas fa-paperclip mr-1"></i>View</a>` : `<span class="text-gray-400 italic">None</span>`}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-col">
                                    <span>${formatDate(lr.created_at || lr.start_date)}</span>
                                    <span class="text-xs">${formatTime(lr.created_at || lr.start_date)}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${actionCell}</td>
                        </tr>`;
                    });
                    } catch (error) {
                        console.error('Error rendering table:', error);
                        const tbody = document.getElementById('leaveTableBody');
                        if (tbody) {
                            tbody.innerHTML = `<tr><td colspan="10" class="text-center text-sm py-8 text-red-500">
                                <i class='fas fa-exclamation-triangle text-4xl text-red-300 mb-2'></i>
                                <div>Error loading data. Please refresh the page.</div>
                            </td></tr>`;
                        }
                    }
                }

                function escapeHtml(text) {
                    return text ? text.replace(/[&<>'"]/g, function (c) {
                        return {'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[c];
                    }) : '';
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
                    const d = new Date(dateStr);
                    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                }
                function getStatusBadgeJS(status) {
                    status = (status || 'pending').toLowerCase();
                    const classes = {
                        'approved': 'bg-green-100 text-green-800',
                        'pending': 'bg-yellow-100 text-yellow-800',
                        'rejected': 'bg-red-100 text-red-800',
                        'declined': 'bg-red-100 text-red-800',
                    };
                    const icons = {
                        'approved': 'fas fa-check',
                        'pending': 'fas fa-clock',
                        'rejected': 'fas fa-times',
                        'declined': 'fas fa-times',
                    };
                    const cls = classes[status] || 'bg-gray-100 text-gray-800';
                    const icon = icons[status] || 'fas fa-question';
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}"><i class="${icon} mr-1"></i>${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                }
                function getLeaveTypeBadgeJS(type) {
                    type = (type || 'unknown').toLowerCase();
                    const classes = {
                        'sick_leave': 'bg-red-100 text-red-800',
                        'vacation_leave': 'bg-blue-100 text-blue-800',
                        'emergency_leave': 'bg-orange-100 text-orange-800',
                        'maternity_leave': 'bg-pink-100 text-pink-800',
                        'paternity_leave': 'bg-indigo-100 text-indigo-800',
                    };
                    const cls = classes[type] || 'bg-gray-100 text-gray-800';
                    const displayName = type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cls}">${displayName}</span>`;
                }
                function renderCurrentAction(lr) {
                    if (lr.status === 'pending') {
                        return `<div class="flex space-x-2">
                            <button type="button" onclick="approveLeave(${lr.id})" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors" id="approve-btn-${lr.id}"><i class="fas fa-check mr-1"></i>Approve</button>
                            <button type="button" onclick="openDeclineModal(${lr.id})" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm transition-colors"><i class="fas fa-times mr-1"></i>Decline</button>
                        </div>`;
                    } else {
                        return `<span class="text-gray-500 italic"><i class="fas fa-check-circle mr-1"></i>Done</span>`;
                    }
                }
                function renderHistoryAction(lr) {
                    if (lr.status === 'approved') {
                        const today = new Date();
                        const startDate = new Date(lr.start_date);
                        const canCancel = startDate > today;
                        if (canCancel) {
                            return `<button type="button" onclick="openCancelModal(${lr.id}, '${escapeHtml(lr.fname + ' ' + lr.lname)}', '${formatDate(lr.start_date)} - ${formatDate(lr.end_date)}')" class="bg-orange-600 text-white px-3 py-1 rounded hover:bg-orange-700 text-sm transition-colors"><i class="fas fa-ban mr-1"></i>Cancel</button>`;
                        } else {
                            return `<span class="text-gray-500 italic text-xs"><i class="fas fa-clock mr-1"></i>Cannot cancel<br>(Leave started)</span>`;
                        }
                    } else {
                        return `<span class="text-gray-500 italic"><i class="fas fa-check-circle mr-1"></i>Processed</span>`;
                    }
                }

                function renderPagination() {
                    const pagDiv = document.getElementById('pagination');
                    pagDiv.innerHTML = '';
                    const totalPages = Math.ceil(filteredRequests.length / rowsPerPage);
                    if (totalPages <= 1) return;
                    const prevBtn = document.createElement('button');
                    prevBtn.textContent = 'Prev';
                    prevBtn.className = 'px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 text-gray-700';
                    prevBtn.disabled = currentPage === 1;
                    prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; updateTable(); } };
                    pagDiv.appendChild(prevBtn);
                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.textContent = i;
                        btn.className = `px-3 py-1 rounded ${i === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`;
                        btn.onclick = () => { currentPage = i; updateTable(); };
                        pagDiv.appendChild(btn);
                    }
                    const nextBtn = document.createElement('button');
                    nextBtn.textContent = 'Next';
                    nextBtn.className = 'px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 text-gray-700';
                    nextBtn.disabled = currentPage === totalPages;
                    nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; updateTable(); } };
                    pagDiv.appendChild(nextBtn);
                }

                function sortRequests(requests, sortBy) {
                    return requests.sort((a, b) => {
                        switch(sortBy) {
                            case 'created_at_desc':
                                return new Date(b.created_at) - new Date(a.created_at);
                            case 'created_at_asc':
                                return new Date(a.created_at) - new Date(b.created_at);
                            case 'processed_at_desc':
                                const dateB = new Date(b.processed_at || b.created_at || 0);
                                const dateA = new Date(a.processed_at || a.created_at || 0);
                                return dateB - dateA;
                            case 'processed_at_asc':
                                const dateA2 = new Date(a.processed_at || a.created_at || 0);
                                const dateB2 = new Date(b.processed_at || b.created_at || 0);
                                return dateA2 - dateB2;
                            case 'employee_asc':
                                return (a.fname + ' ' + a.lname).localeCompare(b.fname + ' ' + b.lname);
                            case 'employee_desc':
                                return (b.fname + ' ' + b.lname).localeCompare(a.fname + ' ' + a.lname);
                            case 'start_date_desc':
                                return new Date(b.start_date) - new Date(a.start_date);
                            case 'start_date_asc':
                                return new Date(a.start_date) - new Date(b.start_date);
                            case 'status':
                                return (a.status || '').localeCompare(b.status || '');
                            default:
                                return 0;
                        }
                    });
                }

                function updateTable() {
                    renderTable();
                    renderPagination();
                }

                document.getElementById('searchInput').addEventListener('input', function(e) {
                    const val = e.target.value.toLowerCase();
                    filteredRequests = leaveRequests.filter(lr => {
                        return (
                            (lr.fname + ' ' + lr.lname).toLowerCase().includes(val) ||
                            (lr.leave_type || '').toLowerCase().includes(val) ||
                            (lr.status || '').toLowerCase().includes(val) ||
                            (lr.reason || '').toLowerCase().includes(val)
                        );
                    });
                    // Apply current sorting
                    const sortBy = document.getElementById('sortSelect').value;
                    filteredRequests = sortRequests(filteredRequests, sortBy);
                    currentPage = 1;
                    updateTable();
                });

                document.getElementById('sortSelect').addEventListener('change', function(e) {
                    const sortBy = e.target.value;
                    filteredRequests = sortRequests(filteredRequests, sortBy);
                    currentPage = 1;
                    updateTable();
                });

                // Initial render with default sorting
                const defaultSort = isHistoryView ? 'processed_at_desc' : 'created_at_desc';
                document.getElementById('sortSelect').value = defaultSort;
                filteredRequests = sortRequests(filteredRequests, defaultSort);
                updateTable();
                </script>

                <!-- Summary Cards -->
                <?php if (!empty($leave_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'declined' => 0];
                    $totalDays = 0;
                    foreach ($leave_requests as $req) {
                        $status = strtolower($req['status'] ?? 'pending');
                        if (isset($summary[$status])) {
                            $summary[$status]++;
                        }
                        
                        // Calculate total approved days
                        if ($status === 'approved') {
                            $start = new DateTime($req['start_date']);
                            $end = new DateTime($req['end_date']);
                            $totalDays += $start->diff($end)->days + 1;
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
                                <p class="text-lg font-semibold text-gray-900"><?= count($leave_requests) ?></p>
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
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-calendar-day text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved Days</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $totalDays ?></p>
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
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Leave Request
            </h2>
            <form method="POST" action="process_leave_action.php">
                <input type="hidden" name="leave_id" id="modalLeaveId">
                <input type="hidden" name="action" value="decline">

                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                          placeholder="Provide explanation for declining..." required></textarea>

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

    <!-- Cancel Modal -->
    <div id="cancelModal" class="fixed inset-0 items-center justify-center bg-black bg-opacity-50 hidden z-50" style="display: none;">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-ban text-orange-600 mr-2"></i>Cancel Leave Request
            </h2>
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Employee: <span id="cancelEmployeeName" class="font-medium"></span></p>
                <p class="text-sm text-gray-600 mb-4">Leave Period: <span id="cancelLeavePeriod" class="font-medium"></span></p>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-orange-600"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-orange-800">Warning</h3>
                            <div class="mt-2 text-sm text-orange-700">
                                <p>Cancelling this leave request will restore the leave credits that were deducted. This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <form method="POST" action="process_leave_action.php">
                <input type="hidden" name="leave_id" id="modalCancelLeaveId">
                <input type="hidden" name="action" value="cancel">

                <label for="cancel_reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for cancellation:</label>
                <textarea name="cancel_reason" id="cancel_reason" rows="3"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-orange-200"
                          placeholder="Provide reason for cancelling..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeCancelModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                            onclick="return confirm('Are you sure you want to cancel this leave request? Leave credits will be restored.')"
                            class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">
                        <i class="fas fa-ban mr-1"></i>Cancel Leave
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Message -->
    <div id="toast" class="fixed top-5 right-5 z-50 hidden px-4 py-2 rounded shadow text-white font-semibold"></div>

    <script>
        function openDeclineModal(leaveId) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('explanation').value = '';
            document.getElementById('declineModal').classList.remove('hidden');
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').classList.add('hidden');
        }

        function openCancelModal(leaveId, employeeName, leavePeriod) {
            document.getElementById('modalCancelLeaveId').value = leaveId;
            document.getElementById('cancelEmployeeName').textContent = employeeName;
            document.getElementById('cancelLeavePeriod').textContent = leavePeriod;
            document.getElementById('cancel_reason').value = '';
            
            const modal = document.getElementById('cancelModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        function approveLeave(leaveId) {
            if (!confirm('Are you sure you want to approve this leave request?')) return;
            const btn = document.getElementById('approve-btn-' + leaveId);
            btn.disabled = true;
            btn.classList.add('opacity-50');

            fetch('process_leave_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'leave_id=' + encodeURIComponent(leaveId) + '&action=approve'
            })
            .then(response => response.text())
            .then(html => {
                // Try to extract message from returned HTML
                let msg = 'Leave approved successfully!';
                try {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const p = doc.querySelector('p');
                    if (p) msg = p.textContent;
                } catch {}
                showToast(msg, true);

                // Update row status and remove action buttons
                const row = document.getElementById('leave-row-' + leaveId);
                if (row) {
                    // Find status cell and update
                    const statusCell = row.querySelector('td:nth-child(7)');
                    if (statusCell) {
                        statusCell.innerHTML = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>Approved
                        </span>`;
                    }
                    // Remove action buttons
                    const actionCell = row.querySelector('td:nth-last-child(1)');
                    if (actionCell) {
                        actionCell.innerHTML = `<span class="text-gray-500 italic">
                            <i class="fas fa-check-circle mr-1"></i>Done
                        </span>`;
                    }
                }
            })
            .catch(() => {
                showToast('Error approving leave request.', false);
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            });
        }

        function showToast(message, success) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'fixed top-5 right-5 z-50 px-4 py-2 rounded shadow text-white font-semibold ' +
                (success ? 'bg-green-600' : 'bg-red-600');
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }
    </script>

</body>
</html>