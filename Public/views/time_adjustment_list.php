<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Set page title for header
$pageTitle = 'Time Adjustment Requests';

date_default_timezone_set('Asia/Manila'); // or your preferred timezone

// Fetch time adjustment requests
$stmt = $pdo->query("
    SELECT tar.*, e.fname, e.lname
    FROM time_adjustment_requests tar
    JOIN employees e ON tar.employee_id = e.id
    ORDER BY tar.submitted_at DESC
");
$adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title><?= $pageTitle ?></title>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- Include sidebar -->
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <!-- Include header -->
        <?php include('header.php'); ?>

        <main class="flex-1 p-6 overflow-y-auto">
            <!-- Main Container - Full Width -->
            <div class="w-full">
                <!-- Page Header -->
                <div class="mb-8">
                

                <!-- Time Adjustment Table Container - Full Width -->


                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Log Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attachment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($adjustments)): ?>
                                    <?php foreach ($adjustments as $adj): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $adj['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($adj['fname'] . ' ' . $adj['lname']) ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M j, Y', strtotime($adj['log_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <div class="flex items-center">
                                                        <span class="text-xs text-gray-500 w-8">In:</span>
                                                        <span class="font-medium"><?= $adj['current_time_in'] ? date('g:i A', strtotime($adj['current_time_in'])) : '—' ?></span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-xs text-gray-500 w-8">Out:</span>
                                                        <span class="font-medium"><?= $adj['current_time_out'] ? date('g:i A', strtotime($adj['current_time_out'])) : '—' ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="space-y-1">
                                                    <div class="flex items-center">
                                                        <span class="text-xs text-gray-500 w-8">In:</span>
                                                        <span class="font-medium text-blue-600"><?= $adj['requested_time_in'] ? date('g:i A', strtotime($adj['requested_time_in'])) : '—' ?></span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-xs text-gray-500 w-8">Out:</span>
                                                        <span class="font-medium text-blue-600"><?= $adj['requested_time_out'] ? date('g:i A', strtotime($adj['requested_time_out'])) : '—' ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 max-w-xs text-sm text-gray-900">
                                                <div class="truncate hover:whitespace-normal cursor-pointer" title="<?= htmlspecialchars($adj['reason']) ?>">
                                                    <?= htmlspecialchars(substr($adj['reason'], 0, 50)) ?><?= strlen($adj['reason']) > 50 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if (!empty($adj['attachment'])): ?>
                                                    <a href="../Public/uploads/time_adjustments/<?= urlencode(htmlspecialchars($adj['attachment'])) ?>" 
                                                       target="_blank" 
                                                       class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                        </svg>
                                                        View
                                                    </a>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                                        No File
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= 
                                                    strtolower($adj['status']) === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                    (strtolower($adj['status']) === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') 
                                                ?>">
                                                    <?= htmlspecialchars(ucfirst($adj['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M j, Y', strtotime($adj['submitted_at'])) ?>
                                                <br>
                                                <span class="text-xs"><?= date('g:i A', strtotime($adj['submitted_at'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if (strtolower($adj['status']) === 'pending'): ?>
                                                    <div class="flex space-x-2">
                                                        <form method="POST" action="process_time_adjustment.php" class="inline-block">
                                                            <input type="hidden" name="request_id" value="<?= $adj['id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" 
                                                                    class="bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700 text-xs font-medium transition-colors flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                </svg>
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <button type="button"
                                                                onclick="openDeclineModal(<?= $adj['id'] ?>)"
                                                                class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 text-xs font-medium transition-colors flex items-center">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                            Decline
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic text-xs">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-12">
                                            <div class="text-gray-400">
                                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                                <p class="text-lg font-medium">No time adjustment requests found</p>
                                                <p class="text-sm">Requests will appear here when employees submit them</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Back to Dashboard Link -->
            </div> <!-- End Main Container -->
        </main>
    </div>
</div>

<!-- Decline Modal -->
<div id="declineModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Decline Time Adjustment</h2>
            <button onclick="closeDeclineModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" action="process_time_adjustment.php">
            <input type="hidden" name="request_id" id="modalRequestId">
            <input type="hidden" name="action" value="decline">

            <div class="mb-4">
                <label for="explanation" class="block text-sm font-medium text-gray-700 mb-2">Reason for Decline:</label>
                <textarea name="explanation" id="explanation" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                          placeholder="Please provide a clear reason for declining this request..." required></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeclineModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm font-medium">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                    Decline Request
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

    // Close modal when clicking outside
    document.getElementById('declineModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeclineModal();
        }
    });
</script>

</body>
</html>