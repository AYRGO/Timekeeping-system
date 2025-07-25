<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

$pageTitle = 'Leave Requests';

$sql = "
    SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.attachment_lr,
           e.fname, e.lname
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    ORDER BY lr.start_date DESC
";
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
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Leave Requests</h1>
                    <p class="text-gray-600">Manage employee leave requests</p>
                </div>

                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($leave_requests)): ?>
                                <?php foreach ($leave_requests as $lr): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= htmlspecialchars($lr['id']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        <?= strtoupper(substr($lr['fname'], 0, 1) . substr($lr['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($lr['fname'] . ' ' . $lr['lname']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= getLeaveTypeBadge($lr['leave_type'] ?? 'unknown') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?= date('M d, Y', strtotime($lr['start_date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($lr['start_date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium"><?= date('M d, Y', strtotime($lr['end_date'])) ?></span>
                                                <span class="text-xs text-gray-500"><?= date('l', strtotime($lr['end_date'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
                                            <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($lr['reason']) ?>">
                                                <?= htmlspecialchars($lr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?= getStatusBadge($lr['status'] ?? 'pending') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if (!empty($lr['attachment_lr'])): ?>
                                                <a href="../uploads/leave_attachments/<?= htmlspecialchars($lr['attachment_lr']) ?>"
                                                   target="_blank"
                                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php if ($lr['status'] === 'pending'): ?>
                                                <div class="flex space-x-2">
                                                    <form method="POST" action="process_leave_action.php" class="inline-block">
                                                        <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit"
                                                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm transition-colors">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            onclick="openDeclineModal(<?= $lr['id'] ?>)"
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
                                    <td colspan="9" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-2"></i>
                                        <div>No leave requests found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

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

    <script>
        function openDeclineModal(leaveId) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('explanation').value = '';
            document.getElementById('declineModal').classList.remove('hidden');
        }

        function closeDeclineModal() {
            document.getElementById('declineModal').classList.add('hidden');
        }
    </script>

</body>
</html>