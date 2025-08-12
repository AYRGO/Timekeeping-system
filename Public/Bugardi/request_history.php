<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Handle delete request
if ($_POST['action'] ?? '' === 'delete' && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    
    try {
        // Delete from overtime_requests table
        $deleteStmt = $pdo->prepare("
            DELETE ot FROM overtime_requests ot
            JOIN employees e ON ot.employee_id = e.id
            WHERE ot.id = ? AND LOWER(TRIM(e.company)) = 'bugardi'
        ");
        
        if ($deleteStmt->execute([$requestId])) {
            $message = "Request #$requestId has been successfully deleted.";
        } else {
            $error = "Failed to delete the request.";
        }
    } catch (Exception $e) {
        $error = "Error deleting request: " . $e->getMessage();
    }
}

// Pagination settings
$recordsPerPage = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $recordsPerPage;

// Get total count for pagination
$countStmt = $pdo->query("
    SELECT COUNT(*) as total_count
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
");
$totalCountResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalCountResult['total_count'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch overtime requests for Bugardi with pagination
$stmt = $pdo->query("
    SELECT 
        o.id, o.date, o.start_time, o.end_time, o.reason, o.status, 
        o.attachment_ot, o.created_at, o.duration_hours, o.explanation,
        e.fname, e.lname, e.company
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
    ORDER BY o.created_at DESC
    LIMIT $recordsPerPage OFFSET $offset
");

$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total' => count($overtime_requests),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total_hours' => 0
];

foreach ($overtime_requests as $req) {
    $status = strtolower($req['status'] ?? 'pending');
    if ($status === 'pending') $stats['pending']++;
    elseif ($status === 'approved') {
        $stats['approved']++;
        $stats['total_hours'] += $req['duration_hours'];
    }
    elseif (in_array($status, ['rejected', 'declined'])) $stats['rejected']++;
}

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
    <link href="../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Bugardi - Request History</title>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>

            <main class="flex-1 p-6 overflow-y-auto">
                <!-- Display messages -->
                <?php if (isset($message)): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500 cursor-pointer" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500 cursor-pointer" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" onclick="this.parentElement.parentElement.style.display='none'"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="mb-6">
                    <div class="card-bugardi mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-white">Bugardi Request History</h1>
                                <p class="text-white mt-1 opacity-90">Complete history of all overtime requests</p>
                            </div>
                            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-4 py-2">
                                <div class="flex items-center text-white">
                                    <i class="fas fa-history mr-2"></i>
                                    <span class="font-semibold"><?= $stats['total'] ?> Total Requests</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-list text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="text-lg font-semibold text-gray-900"><?= $stats['total'] ?></p>
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
                                <p class="text-lg font-semibold text-gray-900"><?= $stats['pending'] ?></p>
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
                                <p class="text-lg font-semibold text-gray-900"><?= $stats['approved'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-bugardi-100 rounded-lg">
                                <i class="fas fa-business-time text-bugardi-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Approved Hours</p>
                                <p class="text-lg font-semibold text-gray-900"><?= number_format($stats['total_hours'], 1) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="overflow-x-auto bg-white shadow rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overtime Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                                <div class="w-8 h-8 bg-bugardi-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-bugardi-600 font-medium text-sm">
                                                        <?= strtoupper(substr($ot['fname'], 0, 1) . substr($ot['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname']) ?></div>
                                                    <div class="text-xs text-bugardi-600 font-medium"><?= htmlspecialchars($ot['company']) ?></div>
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
                                                <div class="bg-bugardi-100 text-bugardi-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    <i class="fas fa-clock mr-1"></i><?= number_format($ot['duration_hours'], 2) ?> hrs
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs text-sm text-gray-900 break-words overflow-hidden">
                                            <div class="truncate hover:whitespace-normal" title="<?= htmlspecialchars($ot['reason']) ?>">
                                                <?= htmlspecialchars($ot['reason']) ?>
                                            </div>
                                            <?php if (!empty($ot['explanation'])): ?>
                                                <div class="text-xs text-red-600 mt-1 italic">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    <?= htmlspecialchars($ot['explanation']) ?>
                                                </div>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex space-x-2">
                                                <?php if (!empty($ot['attachment_ot'])): ?>
                                                    <a href="../module/<?= htmlspecialchars($ot['attachment_ot']) ?>"
                                                       target="_blank"
                                                       class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs">
                                                        <i class="fas fa-paperclip mr-1"></i>View
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <button type="button"
                                                        onclick="confirmDelete(<?= $ot['id'] ?>, '<?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname'], ENT_QUOTES) ?>')"
                                                        class="inline-flex items-center text-red-600 hover:text-red-800 text-xs">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-sm py-8 text-gray-500">
                                        <i class="fas fa-history text-4xl text-gray-300 mb-2"></i>
                                        <div>No overtime requests found for Bugardi employees.</div>
                                        <div class="text-xs text-gray-400 mt-1">Request history will appear here</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Showing <?= min($offset + 1, $totalRecords) ?> to <?= min($offset + $recordsPerPage, $totalRecords) ?> of <?= $totalRecords ?> requests
                    </div>
                    
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>" 
                               class="px-3 py-2 border rounded-md text-sm <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" 
                               class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-500 hover:bg-gray-50">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Confirm Delete</h2>
                    <p class="text-sm text-gray-600">This action cannot be undone</p>
                </div>
            </div>
            
            <div class="mb-6">
                <p class="text-gray-700">Are you sure you want to delete this overtime request?</p>
                <div class="mt-2 p-3 bg-gray-50 rounded border-l-4 border-red-500">
                    <p class="text-sm text-gray-600">
                        <strong>Request ID:</strong> #<span id="deleteRequestId"></span><br>
                        <strong>Employee:</strong> <span id="deleteEmployeeName"></span>
                    </p>
                </div>
            </div>

            <form method="POST" id="deleteForm">
                <input type="hidden" name="request_id" id="modalDeleteRequestId">
                <input type="hidden" name="action" value="delete">

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-medium transition-colors">
                        <i class="fas fa-trash mr-1"></i>Delete Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmDelete(requestId, employeeName) {
            document.getElementById('deleteRequestId').textContent = requestId;
            document.getElementById('deleteEmployeeName').textContent = employeeName;
            document.getElementById('modalDeleteRequestId').value = requestId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>
