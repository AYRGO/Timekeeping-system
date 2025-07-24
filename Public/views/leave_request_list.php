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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Leave Requests</title>
</head>
<body class="bg-gray-100">

 <div class="flex h-screen">
        <?php include('sidebar.php'); ?>


<div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>


            <main class="flex-1 p-6 overflow-y-auto">
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
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lr['id']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lr['fname'] . ' ' . $lr['lname']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lr['leave_type'] ?? '—') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lr['start_date'] ?? '—') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lr['end_date'] ?? '—') ?></td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
                                            <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($lr['reason']) ?>">
                                                <?= htmlspecialchars($lr['reason']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars(ucfirst($lr['status'] ?? 'Pending')) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php if (!empty($lr['attachment_lr'])): ?>
                                                <a href="../uploads/leave_attachments/<?= htmlspecialchars($lr['attachment_lr']) ?>"
                                                   target="_blank"
                                                   class="text-blue-600 hover:underline">View</a>
                                            <?php else: ?>
                                                <span class="text-gray-400 italic">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php if ($lr['status'] === 'pending'): ?>
                                                <div class="flex flex-col space-y-2">
                                                    <div class="flex space-x-2">
                                                        <form method="POST" action="process_leave_action.php" class="flex-1">
                                                            <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit"
                                                                    class="bg-green-600 text-white w-full px-3 py-1 rounded hover:bg-green-700 text-sm">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <button type="button"
                                                                onclick="openDeclineModal(<?= $lr['id'] ?>)"
                                                                class="bg-red-600 text-white w-full px-3 py-1 rounded hover:bg-red-700 text-sm flex-1">
                                                            Decline
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic"></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-sm py-4 text-gray-500">No leave requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <a href="../views/employee_list.php" class="text-blue-600 hover:underline text-sm">← Back to Employee List</a>
                </div>
            </main>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Decline Leave Request</h2>
            <form method="POST" action="process_leave_action.php">
                <input type="hidden" name="leave_id" id="modalLeaveId">
                <input type="hidden" name="action" value="decline">

                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
                <textarea name="explanation" id="explanation" rows="4"
                          class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-blue-200"
                          placeholder="Provide explanation for declining..." required></textarea>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="button" onclick="closeDeclineModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">Cancel</button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">Submit</button>
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
