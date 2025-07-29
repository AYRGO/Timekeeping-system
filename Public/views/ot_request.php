<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check which view to display (current requests or history)
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$isHistoryView = ($view === 'history');

$pageTitle = $isHistoryView ? 'Overtime Requests History' : 'Overtime Requests';

// Fetch overtime requests with employee names and time logs
if ($isHistoryView) {
    // Fetch from post_overtime_requests table (history)
    $stmt = $pdo->query("
        SELECT 
            por.id, por.date, por.start_time, por.end_time, por.reason, por.status, 
            por.attachment_ot, por.created_at, por.duration_hours, por.explanation,
            por.time_in, por.time_out, por.employee_id,
            e.fname, e.lname
        FROM post_overtime_requests por
        JOIN employees e ON por.employee_id = e.id
        ORDER BY por.created_at DESC
    ");
} else {
    // Fetch from overtime_requests table (current requests) - only pending
    $stmt = $pdo->query("
        SELECT 
            o.id, o.date, o.start_time, o.end_time, o.reason, o.status, 
            o.attachment_ot, o.created_at,
            TIMESTAMPDIFF(MINUTE, o.start_time, o.end_time) / 60 AS duration_hours,
            e.fname, e.lname,
            t.time_in AS log_time_in, t.time_out AS log_time_out
        FROM overtime_requests o
        JOIN employees e ON o.employee_id = e.id
        LEFT JOIN time_logs t ON o.employee_id = t.employee_id AND o.date = t.log_date
        WHERE o.status = 'pending'
        ORDER BY o.created_at DESC
    ");
}

$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                            <h1 class="text-2xl font-bold text-gray-900"><?= $pageTitle ?></h1>
                            <p class="text-gray-600">
                                <?= $isHistoryView ? 'View all processed overtime requests' : 'Manage employee overtime requests' ?>
                            </p>
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

                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regular Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    <?= $isHistoryView ? 'Processed' : 'Submitted' ?>
                                </th>
                                <?php if (!$isHistoryView): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($overtime_requests)): ?>
                                <?php foreach ($overtime_requests as $ot): ?>
                                    <tr class="hover:bg-gray-50" id="row-<?= $ot['id'] ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= htmlspecialchars($ot['id']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        <?= strtoupper(substr($ot['fname'], 0, 1) . substr($ot['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?= date('M d, Y', strtotime($ot['date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($ot['date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <div class="flex items-center text-xs text-green-600 mb-1">
                                                    <i class="fas fa-sign-in-alt mr-1"></i>In:
                                                </div>
                                                <span class="font-medium text-green-700">
                                                    <?php 
                                                    $time_in = $isHistoryView ? ($ot['time_in'] ?? null) : ($ot['log_time_in'] ?? null);
                                                    echo $time_in ? date('g:i A', strtotime($time_in)) : '<span class="text-gray-400 italic">None</span>';
                                                    ?>
                                                </span>
                                                <div class="flex items-center text-xs text-red-600 mb-1 mt-2">
                                                    <i class="fas fa-sign-out-alt mr-1"></i>Out:
                                                </div>
                                                <span class="font-medium text-red-700">
                                                    <?php 
                                                    $time_out = $isHistoryView ? ($ot['time_out'] ?? null) : ($ot['log_time_out'] ?? null);
                                                    echo $time_out ? date('g:i A', strtotime($time_out)) : '<span class="text-gray-400 italic">None</span>';
                                                    ?>
                                                </span>
                                            </div>
                                        </td>
                                  <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <div class="flex items-center text-xs text-green-600 mb-1">
                                                    <i class="fas fa-play mr-1"></i>Start:
                                                </div>
                                                <span class="font-medium text-green-700"><?= date('g:i A', strtotime($ot['start_time'])) ?></span>
                                                <div class="flex items-center text-xs text-red-600 mb-1 mt-2">
                                                    <i class="fas fa-stop mr-1"></i>End:
                                                </div>
                                                <span class="font-medium text-red-700"><?= date('g:i A', strtotime($ot['end_time'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col items-center">
                                                <div class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    <i class="fas fa-clock mr-1"></i><?= number_format($ot['duration_hours'], 2) ?> hrs
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
                                            <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($ot['reason']) ?>">
                                                <?= htmlspecialchars($ot['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if (!empty($ot['attachment_ot'])): ?>
                                                <a href="../uploads/overtime_attachments/<?= htmlspecialchars($ot['attachment_ot']) ?>"
                                                   target="_blank"
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?= getStatusBadge($ot['status'] ?? 'pending') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span><?= date('M d, Y', strtotime($ot['created_at'])) ?></span>
                                                <span class="text-xs"><?= date('g:i A', strtotime($ot['created_at'])) ?></span>
                                            </div>
                                        </td>
                                        <?php if (!$isHistoryView): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex space-x-1">
                                                <?php if (strtolower($ot['status']) === 'pending'): ?>
                                                    <form method="post" action="process_ot_action.php" class="inline-block">
                                                        <input type="hidden" name="request_id" value="<?= $ot['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" 
                                                                onclick="return confirm('Are you sure you want to approve this overtime request?')"
                                                                class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700 text-xs transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineModal(<?= $ot['id'] ?>)"
                                                            class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 text-xs transition-colors">
                                                        <i class="fas fa-times mr-1"></i>Decline
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-500 italic">
                                                        <i class="fas fa-check-circle mr-1"></i>Done
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $isHistoryView ? '10' : '11' ?>" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-clock text-4xl text-gray-300 mb-2"></i>
                                        <div><?= $isHistoryView ? 'No processed overtime requests found.' : 'No overtime requests found.' ?></div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

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
                            $totalHours += $req['duration_hours'];
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
                                <p class="text-lg font-semibold text-gray-900"><?= number_format($totalHours, 1) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-6">
                    <a href="../views/employee_list.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Employee List
                    </a>
                </div>
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