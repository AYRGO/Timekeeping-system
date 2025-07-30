<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check which view to display (current requests or history)
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$isHistoryView = ($view === 'history');

// Set page title for header
$pageTitle = $isHistoryView ? 'Time Adjustment Requests History' : 'Time Adjustments';

date_default_timezone_set('Asia/Manila'); // or your preferred timezone

// Fetch time adjustment requests
if ($isHistoryView) {
    // Fetch from post_time_adjustment_requests table (history)
    $stmt = $pdo->query("
        SELECT ptar.id, ptar.employee_id, ptar.log_date, ptar.current_time_in, ptar.current_time_out,
               ptar.requested_time_in, ptar.requested_time_out, ptar.reason, ptar.status,
               ptar.submitted_at, ptar.attachment, ptar.created_at,
               e.fname, e.lname
        FROM post_time_adjustment_requests ptar
        JOIN employees e ON ptar.employee_id = e.id
        ORDER BY ptar.created_at DESC
    ");
} else {
    // Fetch from time_adjustment_requests table (current requests) - only pending
    $stmt = $pdo->query("
        SELECT tar.*, e.fname, e.lname
        FROM time_adjustment_requests tar
        JOIN employees e ON tar.employee_id = e.id
        WHERE tar.status = 'pending'
        ORDER BY tar.submitted_at DESC
    ");
}
$adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <!-- Display messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success']) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error']) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="mb-8">
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

                <!-- Time Adjustment Table Container - Full Width -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?= $isHistoryView ? 'Processed' : 'Submitted' ?>
                                    </th>
                                    <?php if (!$isHistoryView): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($adjustments)): ?>
                                    <?php foreach ($adjustments as $adj): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $adj['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                        <span class="text-blue-600 font-medium text-sm">
                                                            <?= strtoupper(substr($adj['fname'], 0, 1) . substr($adj['lname'], 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($adj['fname'] . ' ' . $adj['lname']) ?>
                                                    </div>
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
                                                <?php 
                                                $date_field = $isHistoryView ? ($adj['created_at'] ?? $adj['submitted_at']) : $adj['submitted_at'];
                                                ?>
                                                <?= date('M j, Y', strtotime($date_field)) ?>
                                                <br>
                                                <span class="text-xs"><?= date('g:i A', strtotime($date_field)) ?></span>
                                            </td>
                                            <?php if (!$isHistoryView): ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if (strtolower($adj['status']) === 'pending'): ?>
                                                    <div class="flex space-x-2">
                                                        <form method="POST" action="process_time_adjustment.php" class="inline-block">
                                                            <input type="hidden" name="request_id" value="<?= $adj['id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" 
                                                                    onclick="return confirm('Are you sure you want to approve this time adjustment request?')"
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
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= $isHistoryView ? '9' : '10' ?>" class="text-center py-12">
                                            <div class="text-gray-400">
                                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                                <p class="text-lg font-medium"><?= $isHistoryView ? 'No processed time adjustment requests found' : 'No time adjustment requests found' ?></p>
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