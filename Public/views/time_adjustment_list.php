<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');
include('header.php');

// Fetch time adjustment requests
$stmt = $pdo->query("
    SELECT tar.*, e.fname, e.lname
    FROM time_adjustment_requests tar
    JOIN employees e ON tar.employee_id = e.id
    ORDER BY tar.submitted_at DESC
");
$adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto mt-10 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Time Adjustment Requests</h1>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Log Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($adjustments)): ?>
                    <?php foreach ($adjustments as $adj): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $adj['id'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($adj['fname'] . ' ' . $adj['lname']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($adj['log_date']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $adj['current_time_in'] ? date('g:i A', strtotime($adj['current_time_in'])) : '—' ?>
                                <br>
                                <?= $adj['current_time_out'] ? date('g:i A', strtotime($adj['current_time_out'])) : '—' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $adj['requested_time_in'] ? date('g:i A', strtotime($adj['requested_time_in'])) : '—' ?>
                                <br>
                                <?= $adj['requested_time_out'] ? date('g:i A', strtotime($adj['requested_time_out'])) : '—' ?>
                            </td>
                            <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
    <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($adj['reason']) ?>">
        <?= htmlspecialchars($adj['reason']) ?>
    </div>
</td>

                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($adj['attachment'])): ?>
                                   <a href="../Public/uploads/time_adjustments/<?= urlencode(htmlspecialchars($adj['attachment'])) ?>" 
   target="_blank" 
   class="text-blue-600 hover:underline">
   View
</a>

                                <?php else: ?>
                                    <span class="text-gray-400 italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= ucfirst($adj['status']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('F j, Y g:i A', strtotime($adj['submitted_at'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if (strtolower($adj['status']) === 'pending'): ?>
                                    <div class="flex justify-between items-center">
                                        <form method="POST" action="process_time_adjustment.php" class="inline-block">
                                            <input type="hidden" name="request_id" value="<?= $adj['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Approve</button>
                                        </form>

                                        <button type="button"
                                                onclick="openDeclineModal(<?= $adj['id'] ?>)"
                                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm ml-2">
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
                        <td colspan="10" class="text-center text-sm py-4 text-gray-500">No time adjustment requests found.</td>
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
        <h2 class="text-xl font-bold mb-4 text-gray-800">Decline Time Adjustment</h2>
        <form method="POST" action="process_time_adjustment.php">
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
