<?php
/**
 * Cache Cleanup Tool
 * 
 * This page finds and fixes orphaned cache entries where:
 * - source='approved_request' but source_id doesn't exist in post_schedule_change_requests
 * - Rebuilds those entries using the employee's default weekly schedule
 */

session_start();
include('../config/db.php');

$cleaned = 0;
$rebuilt = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup'])) {
    try {
        // Find all orphaned cache entries
        $orphanedQuery = "
            SELECT c.id, c.employee_id, c.schedule_date, c.source_id, c.work_schedule_id,
                   e.fname, e.lname
            FROM employee_daily_schedule_cache c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.source = 'approved_request'
              AND (c.source_id IS NULL 
                   OR c.source_id = 0
                   OR NOT EXISTS (
                       SELECT 1 FROM post_schedule_change_requests p 
                       WHERE p.id = c.source_id
                   ))
            ORDER BY c.employee_id, c.schedule_date
        ";
        
        $stmt = $pdo->query($orphanedQuery);
        $orphanedEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orphanedEntries as $entry) {
            $employee_id = $entry['employee_id'];
            $schedule_date = $entry['schedule_date'];
            $cache_id = $entry['id'];
            
            // Delete orphaned entry
            $deleteStmt = $pdo->prepare("DELETE FROM employee_daily_schedule_cache WHERE id = ?");
            $deleteStmt->execute([$cache_id]);
            $cleaned++;
            
            // Get day of week for this date
            $dayOfWeek = date('w', strtotime($schedule_date)); // 0 (Sun) to 6 (Sat)
            
            // Get default schedule for this day
            $defaultStmt = $pdo->prepare("
                SELECT eds.work_schedule_id, eds.is_rest_day, ws.name, ws.time_in, ws.time_out
                FROM employee_default_schedules eds
                LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
                WHERE eds.employee_id = ? AND eds.day_of_week = ?
            ");
            $defaultStmt->execute([$employee_id, $dayOfWeek]);
            $defaultSched = $defaultStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($defaultSched) {
                // Insert new cache entry with default schedule
                $insertStmt = $pdo->prepare("
                    INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                     schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
                    VALUES (?, ?, ?, ?, 0, ?, ?, ?, NULL, 'weekly_default', NULL, NOW())
                ");
                $insertStmt->execute([
                    $employee_id,
                    $schedule_date,
                    $defaultSched['work_schedule_id'],
                    $defaultSched['is_rest_day'],
                    $defaultSched['name'],
                    $defaultSched['time_in'],
                    $defaultSched['time_out']
                ]);
                $rebuilt++;
            } else {
                $errors[] = "No default schedule found for {$entry['fname']} {$entry['lname']} on " . date('D, M d, Y', strtotime($schedule_date));
            }
        }
        
        $success_message = "Cleanup complete! Removed $cleaned orphaned entries and rebuilt $rebuilt with default schedules.";
        
    } catch (Exception $e) {
        $errors[] = "Error during cleanup: " . $e->getMessage();
    }
}

// Get count of orphaned entries
$countStmt = $pdo->query("
    SELECT COUNT(*) as orphaned_count
    FROM employee_daily_schedule_cache c
    WHERE c.source = 'approved_request'
      AND (c.source_id IS NULL 
           OR c.source_id = 0
           OR NOT EXISTS (
               SELECT 1 FROM post_schedule_change_requests p 
               WHERE p.id = c.source_id
           ))
");
$orphanedCount = $countStmt->fetch(PDO::FETCH_ASSOC)['orphaned_count'];

// Get details of orphaned entries
$detailsStmt = $pdo->query("
    SELECT c.schedule_date, c.employee_id, c.source_id, c.work_schedule_id,
           e.fname, e.lname, ws.name as schedule_name, ws.time_in, ws.time_out
    FROM employee_daily_schedule_cache c
    JOIN employees e ON c.employee_id = e.id
    LEFT JOIN work_schedules ws ON c.work_schedule_id = ws.id
    WHERE c.source = 'approved_request'
      AND (c.source_id IS NULL 
           OR c.source_id = 0
           OR NOT EXISTS (
               SELECT 1 FROM post_schedule_change_requests p 
               WHERE p.id = c.source_id
           ))
    ORDER BY c.schedule_date DESC, e.lname
    LIMIT 50
");
$orphanedDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Cleanup Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include('sidebar.php'); ?>
    
    <div class="flex-1 flex flex-col">
        <?php 
        $pageTitle = "Cache Cleanup Tool";
        include('header.php'); 
        ?>
        
        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-broom text-3xl text-blue-600 mr-4"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Schedule Cache Cleanup</h1>
                            <p class="text-gray-600">Fix orphaned cache entries from deleted requests</p>
                        </div>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            <i class="fas fa-check-circle mr-2"></i><?= $success_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                            <div>
                                <p class="text-sm text-blue-800">
                                    <strong>Found <?= $orphanedCount ?> orphaned cache <?= $orphanedCount === 1 ? 'entry' : 'entries' ?></strong>
                                </p>
                                <p class="text-xs text-blue-700 mt-1">
                                    These entries reference deleted schedule change requests and need to be rebuilt with default schedules.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($orphanedCount > 0): ?>
                        <form method="post" onsubmit="return confirm('Clean up <?= $orphanedCount ?> orphaned cache entries?');">
                            <button type="submit" name="cleanup" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                <i class="fas fa-magic mr-2"></i>Clean Up Now
                            </button>
                        </form>
                        
                        <!-- Details Table -->
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Orphaned Entries:</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Current Cache</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Request ID</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($orphanedDetails as $detail): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($detail['fname'] . ' ' . $detail['lname']) ?></td>
                                                <td class="px-4 py-2 text-sm"><?= date('M d, Y', strtotime($detail['schedule_date'])) ?></td>
                                                <td class="px-4 py-2 text-sm">
                                                    <?php if ($detail['schedule_name']): ?>
                                                        <?= htmlspecialchars($detail['schedule_name']) ?>: 
                                                        <?= date('g:i A', strtotime($detail['time_in'])) ?> - <?= date('g:i A', strtotime($detail['time_out'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2 text-sm">
                                                    <span class="text-red-600">#<?= $detail['source_id'] ?? 'NULL' ?> (deleted)</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                            <p class="text-lg font-semibold">All clean!</p>
                            <p class="text-sm">No orphaned cache entries found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>
