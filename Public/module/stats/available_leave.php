<?php

// Fetch approved leaves for the logged-in user
$employee_id = $_SESSION['employee']['id'] ?? null;
$approved_leaves = [];
if ($employee_id) {
    $stmt = $pdo->prepare("SELECT leave_type, start_date, end_date FROM leave_requests WHERE employee_id = ? AND status = 'approved' ORDER BY start_date DESC");
    $stmt->execute([$employee_id]);
    $approved_leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$approved_leaves = array_filter($approved_leaves, function ($leave) {
    $endDate = strtotime($leave['end_date']);
    $today = strtotime(date('Y-m-d'));
    return $endDate >= $today;
});
?>

<div
    class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8"
    onclick="document.getElementById('approvedLeavesModal').classList.remove('hidden')"
>
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Available Leave</p>
            <h3 class="text-2xl font-bold mt-1">- days</h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-umbrella-beach text-green-700 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 60%;"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1">- of annual leave remaining</p>
    </div>
</div>

<!-- Approved Leaves Modal -->
<div id="approvedLeavesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
        <button class="absolute top-3 right-3 text-gray-500 hover:text-red-500"
                onclick="document.getElementById('approvedLeavesModal').classList.add('hidden')">
            <i class="fas fa-times text-lg"></i>
        </button>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-umbrella-beach text-green-500 mr-2"></i>Approved Leaves
        </h3>
        <?php if (count($approved_leaves) > 0): ?>
            <table class="min-w-full text-sm mb-2">
                <thead>
                    <tr>
                        <th class="text-left py-1 px-2">Type</th>
                        <th class="text-left py-1 px-2">Start Date</th>
                        <th class="text-left py-1 px-2">End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved_leaves as $leave): ?>
                        <tr>
                            <td class="py-1 px-2"><?= htmlspecialchars($leave['leave_type']) ?></td>
                            <td class="py-1 px-2"><?= date('M d, Y', strtotime($leave['start_date'])) ?></td>
                            <td class="py-1 px-2"><?= date('M d, Y', strtotime($leave['end_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-gray-500">No approved leaves found.</div>
        <?php endif; ?>
    </div>
</div>
