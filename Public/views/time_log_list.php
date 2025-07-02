<?php
include('../config/db.php');

// Fetch all time logs joined with employee names
$stmt = $pdo->query("
    SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
    FROM time_logs t
    JOIN employees e ON t.employee_id = e.id
    ORDER BY t.log_date DESC, t.time_in ASC
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('header.php');
?>

<div class="max-w-7xl mx-auto mt-10 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Time Logs</h1>
        <a href="../module/time_log_create.php"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            + Add Time Log
        </a>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time In</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Out</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $log['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($log['fname'] . ' ' . $log['lname']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($log['log_date']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($log['time_in']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($log['time_out']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="employee_list.php" class="text-blue-600 hover:underline text-sm">← Back to Employee List</a>
    </div>
</div>

<?php include('footer.php'); ?>
