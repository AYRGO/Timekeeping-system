<?php
// Read filter date from GET
$filterDate = $_GET['activityDate'] ?? null;

// Filter and slice notifications
$filteredActivities = array_filter($notifications, function ($activity) use ($filterDate) {
    if (!$filterDate) return true;
    return date('Y-m-d', strtotime($activity['created_at'])) === $filterDate;
});

$recentActivities = array_slice($filteredActivities, 0, 10); // Limit to 10
?>

<div class="bg-white rounded-2xl shadow-lg p-6 w-full lg:w-1/2 border border-gray-200">
    <!-- Header -->
    <form method="GET" class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-history text-blue-500 bg-blue-100 p-2 rounded-full mr-3"></i>
            Recent Activity
        </h3>

        <!-- Date Filter -->
        <div class="flex items-center gap-2">
            <label for="activityDate" class="text-sm text-gray-600 font-medium">Filter by date:</label>
            <input type="date" id="activityDate" name="activityDate"
                   value="<?= htmlspecialchars($filterDate) ?>"
                   class="border rounded-md px-3 py-1 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-200">
            <button type="submit" class="text-sm bg-blue-500 hover:bg-blue-600 text-white font-medium px-3 py-1 rounded">
                Apply
            </button>

        </div>
    </form>

    <!-- Scrollable Activity Table -->
    <div class="overflow-x-auto">
        <div class="max-h-60 overflow-y-auto rounded-md border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php
                                $created = date('M j, Y', strtotime($activity['created_at']));
                                $msg = strip_tags($activity['message']);
                                preg_match('/\b(Leave|Schedule|Time|Overtime)\b/i', $msg, $typeMatch);
                                preg_match('/\b(Approved|Rejected|Pending|Declined)\b/i', $msg, $statusMatch);
                                $type = $typeMatch[0] ?? 'Request';
                                $status = ucfirst(strtolower($statusMatch[0] ?? 'Pending'));
                                $badgeColor = match (strtolower($status)) {
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected', 'declined' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-gray-500"><?= $created ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= $type ?> Request</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 text-xs font-semibold rounded-full <?= $badgeColor ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-green-600 hover:text-green-900">
                                    <button onclick="alert(`<?= htmlspecialchars_decode($msg) ?>`)">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No recent activity found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
