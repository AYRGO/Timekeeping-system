<?php
include('../config/db.php'); // if not already included
date_default_timezone_set('Asia/Manila');

$employee_id = $_SESSION['employee']['id'] ?? null;
$current_year = date('Y');

$leave_balances = [];
$total_balance = 0;

// Define types to include in available leave count (excluding lwop, halfdays, etc.)
$included_types = ['sick', 'vacation', 'solo_parent', 'paternity', 'maternity', 'bereavement'];

if ($employee_id) {
    // Fetch leave balances
    $stmt = $pdo->prepare("SELECT leave_type, balance FROM leave_credits WHERE employee_id = ? AND year = ?");
    $stmt->execute([$employee_id, $current_year]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($credits as $credit) {
        $leave_balances[$credit['leave_type']] = $credit['balance'];
        if (in_array($credit['leave_type'], $included_types)) {
            $total_balance += (float) $credit['balance'];
        }
    }

    // Fetch approved future or current leaves
    $stmt = $pdo->prepare("SELECT leave_type, start_date, end_date FROM leave_requests WHERE employee_id = ? AND status = 'approved' ORDER BY start_date DESC");
    $stmt->execute([$employee_id]);
    $approved_leaves = array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), function ($leave) {
        return strtotime($leave['end_date']) >= strtotime(date('Y-m-d'));
    });
} else {
    $leave_balances = [];
    $approved_leaves = [];
    $total_balance = 0;
}

// Helper function
function format_date($date) {
    return date('M d, Y', strtotime($date));
}
?>

<!-- Available Leave Card -->
<div
    class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8"
    onclick="document.getElementById('approvedLeavesModal').classList.remove('hidden')"
>
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Available Leave</p>
            <h3 class="text-2xl font-bold mt-1"><?= number_format($total_balance, 2) ?> days</h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-umbrella-beach text-green-700 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full" style="width: <?= min(100, ($total_balance / 20) * 100) ?>%;"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1"><?= number_format($total_balance, 2) ?> of total leave credits remaining</p>
    </div>
</div>

<!-- Approved Leaves Modal -->
<div id="approvedLeavesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">
        <button class="absolute top-3 right-3 text-gray-500 hover:text-red-500"
                onclick="document.getElementById('approvedLeavesModal').classList.add('hidden')">
            <i class="fas fa-times text-lg"></i>
        </button>
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-umbrella-beach text-green-500 mr-2"></i>My Leave Summary
        </h3>

        <!-- Leave Balances -->
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-600 mb-1">Available Leave Credits (<?= $current_year ?>)</h4>
            <ul class="text-sm text-gray-700 grid grid-cols-2 gap-2">
                <?php foreach ($leave_balances as $type => $bal): ?>
                    <li><strong><?= ucwords(str_replace('_', ' ', $type)) ?>:</strong> <?= number_format($bal, 2) ?> day(s)</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Approved Leaves -->
        <div>
            <h4 class="text-sm font-semibold text-gray-600 mb-1">Approved Leaves</h4>
            <?php if (count($approved_leaves) > 0): ?>
                <table class="min-w-full text-sm mb-2">
                    <thead>
                        <tr>
                            <th class="text-left py-1 px-2">Type</th>
                            <th class="text-left py-1 px-2">Start</th>
                            <th class="text-left py-1 px-2">End</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_leaves as $leave): ?>
                            <tr>
                                <td class="py-1 px-2"><?= ucwords(str_replace('_', ' ', $leave['leave_type'])) ?></td>
                                <td class="py-1 px-2"><?= format_date($leave['start_date']) ?></td>
                                <td class="py-1 px-2"><?= format_date($leave['end_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500 text-sm">No approved leaves found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
