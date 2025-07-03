<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');
include('header.php');

// Fetch overtime requests with employee names
$stmt = $pdo->query("
    SELECT o.id, o.date, o.start_time, o.end_time, o.reason, o.status, o.attachment_ot, o.created_at,
           TIMESTAMPDIFF(MINUTE, o.start_time, o.end_time) / 60 AS duration_hours,
           e.fname, e.lname
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    ORDER BY o.created_at DESC
");
$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto mt-10 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Overtime Requests</h1>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration (hrs)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($overtime_requests)): ?>
                    <?php foreach ($overtime_requests as $ot): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ot['id']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ot['date']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= date('g:i A', strtotime($ot['start_time'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= date('g:i A', strtotime($ot['end_time'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
    <?= number_format($ot['duration_hours'], 2) ?>
</td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ot['reason'] ?? '—') ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($ot['attachment_ot'])): ?>
                                    <a href="../uploads/overtime_attachments/<?= htmlspecialchars($ot['attachment_ot']) ?>" target="_blank" class="text-blue-600 hover:underline">View</a>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars(ucfirst($ot['status'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('F j, Y g:i A', strtotime($ot['created_at'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if (strtolower($ot['status']) === 'pending'): ?>
                                    <div class="flex items-center space-x-2">
                                        <form method="post" action="process_ot_action.php" class="inline-block">
                                            <input type="hidden" name="request_id" value="<?= $ot['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Approve</button>
                                        </form>

                                        <button type="button"
                                                onclick="openDeclineModal(<?= $ot['id'] ?>)"
                                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">
                                            Decline
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500 italic">Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-sm py-4 text-gray-500">No overtime requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="admin_homepage.php" class="text-blue-600 hover:underline text-sm">← Back to Dashboard</a>
    </div>
</div>

<!-- Decline Modal -->
<div id="declineModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Decline Overtime Request</h2>
        <form method="POST" action="process_ot_action.php">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" value="decline">

            <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
            <textarea name="explanation" id="explanation" rows="4"
                      class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
                      placeholder="Provide reason for declining..." required></textarea>

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
    function openDeclineModal(requestId) {
        document.getElementById('modalRequestId').value = requestId;
        document.getElementById('explanation').value = '';
        document.getElementById('declineModal').classList.remove('hidden');
    }

    function closeDeclineModal() {
        document.getElementById('declineModal').classList.add('hidden');
    }
</script>

<?php include('footer.php'); ?>
