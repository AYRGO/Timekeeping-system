<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');
include('header.php');

// Fetch schedule change requests with employee names and attachments
$stmt = $pdo->query("
    SELECT sr.id, sr.reason, sr.status, sr.start_date, sr.end_date, sr.created_at,
           sr.work_schedule_id, sr.attachment_scr, sr.explanation,
           e.fname, e.lname,
           ws.time_in, ws.time_out
    FROM schedule_change_requests sr
    JOIN employees e ON sr.employee_id = e.id
    LEFT JOIN work_schedules ws ON sr.work_schedule_id = ws.id
    ORDER BY sr.created_at DESC
");
$schedule_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto mt-10 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Schedule Change Requests</h1>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
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
                        switch ((int)$sr['work_schedule_id']) {
                            case 4: $shift = '7:00 AM – 4:00 PM'; break;
                            case 5: $shift = '8:00 AM – 5:00 PM'; break;
                            case 6: $shift = '9:00 AM – 6:00 PM'; break;
                            case 7: $shift = '10:00 AM – 7:00 PM'; break;
                            case 8: $shift = '6:00 AM – 3:00 PM'; break;
                            default:
                                $shift = $sr['time_in'] && $sr['time_out']
                                    ? date('g:i A', strtotime($sr['time_in'])) . ' – ' . date('g:i A', strtotime($sr['time_out']))
                                    : 'N/A';
                        }
                        ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($sr['id']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($sr['fname'] . ' ' . $sr['lname']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $shift ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($sr['start_date']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($sr['end_date']) ?></td>
                            <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
    <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($sr['reason']) ?>">
        <?= htmlspecialchars($sr['reason']) ?>
    </div>
</td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($sr['attachment_scr'])): ?>
                                    <a href="../uploads/schedule_attachments/<?= htmlspecialchars($sr['attachment_scr']) ?>" target="_blank" class="text-blue-600 hover:underline">View</a>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars(ucfirst($sr['status'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('F j, Y g:i A', strtotime($sr['created_at'])) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if (strtolower($sr['status']) === 'pending'): ?>
                                    <div class="flex justify-between items-center">
                                        <!-- Approve Button (Left) -->
                                        <form method="post" action="process_schedule_action.php" class="inline-block">
                                            <input type="hidden" name="request_id" value="<?= $sr['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">Approve</button>
                                        </form>

                                        <!-- Decline Button (Right) -->
                                        <button type="button"
                                                onclick="openDeclineModal(<?= $sr['id'] ?>)"
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
                        <td colspan="10" class="text-center text-sm py-4 text-gray-500">No schedule change requests found.</td>
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
        <h2 class="text-xl font-bold mb-4 text-gray-800">Decline Schedule Request</h2>
        <form method="POST" action="process_schedule_action.php">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" value="decline">

            <label for="explanation" class="block text-sm font-medium text-gray-700 mb-1">Explanation:</label>
            <textarea name="explanation" id="explanation" rows="4"
                      class="w-full border rounded-md px-3 py-2 text-sm focus:ring focus:ring-red-200"
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
