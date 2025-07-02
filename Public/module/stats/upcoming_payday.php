<?php
$stmt = $pdo->query("
    SELECT start_time, end_time
    FROM overtime_requests
    WHERE status = 'Approved'
    ORDER BY start_time DESC
    LIMIT 1
");
$latestOT = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="card bg-white rounded-lg p-6 shadow-sm">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Latest Approved OT</p>
            <?php if ($latestOT): ?>
                <h3 class="text-2xl font-bold mt-1">
                    <?= date('M j, Y g:i A', strtotime($latestOT['start_time'])) ?>
                    <span class="text-gray-400">→</span>
                    <?= date('M j, Y g:i A', strtotime($latestOT['end_time'])) ?>
                </h3>
            <?php else: ?>
                <h3 class="text-2xl font-bold mt-1 text-gray-400">No Approved OT</h3>
            <?php endif; ?>
        </div>
        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
            <i class="fas fa-clock text-yellow-600 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <p class="text-sm text-gray-600">
            <?= $latestOT ? 'Most recent approved overtime logged.' : 'No OT approved yet.' ?>
        </p>
    </div>
</div>
