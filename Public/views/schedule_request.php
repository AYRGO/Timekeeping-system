<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

$pageTitle = 'Schedule Change Requests';
// Fetch schedule change requests with employee names, attachments, and current schedule
$stmt = $pdo->query("
    SELECT sr.id, sr.reason, sr.status, sr.start_date, sr.end_date, sr.created_at,
           sr.work_schedule_id, sr.current_work_schedule_id, sr.attachment_scr, sr.explanation,
           e.fname, e.lname,
           ws.time_in, ws.time_out
    FROM schedule_change_requests sr
    JOIN employees e ON sr.employee_id = e.id
    LEFT JOIN work_schedules ws ON sr.work_schedule_id = ws.id
    ORDER BY sr.created_at DESC
");
$schedule_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get schedule time display
function getScheduleTime($schedule_id) {
    switch ((int)$schedule_id) {
        case 3: return '7:30 AM – 4:30 PM';
        case 4: return '7:00 AM – 4:00 PM';
        case 5: return '8:00 AM – 5:00 PM';
        case 6: return '9:00 AM – 6:00 PM';
        case 7: return '10:00 AM – 7:00 PM';
        case 8: return '6:00 AM – 3:00 PM';
        case 9: return '8:00 AM – 4:30 PM';
        case 10: return '7:40 AM – 4:40 PM';
        default: return 'N/A';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Schedule Change Requests</title>
</head>
<body class="bg-gray-100">

 <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

<div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Schedule Change Requests</h1>
                    <p class="text-gray-600">Manage employee schedule change requests</p>
                </div>

                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Schedule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($schedule_requests)): ?>
                                <?php foreach ($schedule_requests as $sr): ?>
                                    <?php
                                    // Get requested schedule time
                                    $requested_shift = getScheduleTime($sr['work_schedule_id']);
                                    if ($requested_shift === 'N/A' && $sr['time_in'] && $sr['time_out']) {
                                        $requested_shift = date('g:i A', strtotime($sr['time_in'])) . ' – ' . date('g:i A', strtotime($sr['time_out']));
                                    }
                                    
                                    // Get current schedule time
                                    $current_shift = getScheduleTime($sr['current_work_schedule_id']);
                                    
                                    // Calculate period duration
                                    $start = new DateTime($sr['start_date']);
                                    $end = new DateTime($sr['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= htmlspecialchars($sr['id']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        <?= strtoupper(substr($sr['fname'], 0, 1) . substr($sr['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($sr['fname'] . ' ' . $sr['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (!empty($sr['current_work_schedule_id'])): ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-red-600">Schedule <?= $sr['current_work_schedule_id'] ?>:</span>
                                                    <span class="text-sm"><?= $current_shift ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">Not recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-green-600">Schedule <?= $sr['work_schedule_id'] ?>:</span>
                                                <span class="text-sm"><?= $requested_shift ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium">Start: <?= date('M d, Y', strtotime($sr['start_date'])) ?></span>
                                                <span class="font-medium">End: <?= date('M d, Y', strtotime($sr['end_date'])) ?></span>
                                                <span class="text-xs text-gray-500 mt-1">(<?= $days ?> day<?= $days > 1 ? 's' : '' ?>)</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900">
                                            <div class="truncate hover:whitespace-normal cursor-help" title="<?= htmlspecialchars($sr['reason']) ?>">
                                                <?= htmlspecialchars($sr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if (!empty($sr['attachment_scr'])): ?>
                                                <a href="../uploads/schedule_attachments/<?= htmlspecialchars($sr['attachment_scr']) ?>" target="_blank" 
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php
                                            $status = strtolower($sr['status']);
                                            $statusClass = match($status) {
                                                'approved' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'rejected', 'declined' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucfirst($sr['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span><?= date('M d, Y', strtotime($sr['created_at'])) ?></span>
                                                <span class="text-xs"><?= date('g:i A', strtotime($sr['created_at'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if (strtolower($sr['status']) === 'pending'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="post" action="process_schedule_action.php" class="inline-block">
                                                        <input type="hidden" name="request_id" value="<?= $sr['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineModal(<?= $sr['id'] ?>)"
                                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm transition-colors">
                                                        <i class="fas fa-times mr-1"></i>Decline
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">
                                                    <i class="fas fa-check-circle mr-1"></i>Done
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-2"></i>
                                        <div>No schedule change requests found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Cards -->
                <?php if (!empty($schedule_requests)): ?>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $summary = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'declined' => 0];
                    foreach ($schedule_requests as $req) {
                        $status = strtolower($req['status']);
                        if (isset($summary[$status])) {
                            $summary[$status]++;
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
                                <p class="text-lg font-semibold text-gray-900"><?= count($schedule_requests) ?></p>
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
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-times text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Rejected</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $summary['rejected'] + $summary['declined'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-6">
                    <a href="admin_homepage.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-times-circle text-red-600 mr-2"></i>Decline Schedule Request
            </h2>
            <form method="POST" action="process_schedule_action.php">
                <input type="hidden" name="request_id" id="modalRequestId">
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