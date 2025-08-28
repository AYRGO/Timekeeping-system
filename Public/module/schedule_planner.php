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

// Get current week or selected week
$currentWeek = isset($_GET['week']) ? $_GET['week'] : date('Y-W');
list($year, $week) = explode('-', $currentWeek);

// Get start and end dates for the week
$dto = new DateTime();
$dto->setISODate($year, $week);
$startDate = $dto->format('Y-m-d');
$dto->modify('+6 days');
$endDate = $dto->format('Y-m-d');

// Get all employees
$employeeStmt = $pdo->query("
    SELECT id, fname, lname, position, official_sched, company
    FROM employees 
    ORDER BY fname, lname
");
$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available work schedules
$scheduleStmt = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules ORDER BY time_in");
$schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing schedule assignments for this week
$existingStmt = $pdo->prepare("
    SELECT employee_id, day_of_week, work_schedule_id, effective_date
    FROM employee_schedules 
    WHERE effective_date BETWEEN ? AND ?
");
$existingStmt->execute([$startDate, $endDate]);
$existingSchedules = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Delete existing schedules for this week
        $deleteStmt = $pdo->prepare("
            DELETE FROM employee_schedules 
            WHERE effective_date BETWEEN ? AND ?
        ");
        $deleteStmt->execute([$startDate, $endDate]);
        
        // Insert new schedules
        $insertStmt = $pdo->prepare("
            INSERT INTO employee_schedules (employee_id, work_schedule_id, day_of_week, effective_date) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($_POST['schedules'] as $employeeId => $days) {
            foreach ($days as $day => $scheduleId) {
                if (!empty($scheduleId)) {
                    $dayDate = date('Y-m-d', strtotime($startDate . ' +' . (array_search($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])) . ' days'));
                    $insertStmt->execute([$employeeId, $scheduleId, $day, $dayDate]);
                }
            }
        }
        
        $pdo->commit();
        $successMessage = "Schedules updated successfully!";
        
        // Refresh existing schedules
        $existingStmt->execute([$startDate, $endDate]);
        $existingSchedules = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMessage = "Error updating schedules: " . $e->getMessage();
    }
}

// Helper function to get schedule for employee and day
function getScheduleForEmployeeAndDay($employeeId, $day, $existingSchedules) {
    foreach ($existingSchedules as $schedule) {
        if ($schedule['employee_id'] == $employeeId && $schedule['day_of_week'] == $day) {
            return $schedule['work_schedule_id'];
        }
    }
    return null;
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Schedule Planner</title>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">Schedule Planner</h1>
                        <p class="text-blue-100 mt-2">Assign work schedules to employees for the week</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Week Navigation -->
                        <div class="flex items-center space-x-2">
                            <a href="?week=<?= date('Y-W', strtotime($startDate . ' -1 week')) ?>" 
                               class="bg-blue-500 bg-opacity-30 backdrop-blur-sm rounded-lg px-3 py-2 text-white hover:bg-opacity-50 transition-all">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <span class="bg-blue-500 bg-opacity-30 backdrop-blur-sm rounded-lg px-4 py-2 text-white font-semibold">
                                <?= date('M j', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>
                            </span>
                            <a href="?week=<?= date('Y-W', strtotime($startDate . ' +1 week')) ?>" 
                               class="bg-blue-500 bg-opacity-30 backdrop-blur-sm rounded-lg px-3 py-2 text-white hover:bg-opacity-50 transition-all">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <a href="?week=<?= date('Y-W') ?>" 
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-all">
                            Current Week
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto p-6">
            <!-- Messages -->
            <?php if (isset($successMessage)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= $successMessage ?>
                </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= $errorMessage ?>
                </div>
            <?php endif; ?>

            <!-- Schedule Form -->
            <form method="POST" class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider min-w-[200px]">
                                    Employee
                                </th>
                                <?php foreach ($daysOfWeek as $day): ?>
                                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase tracking-wider min-w-[150px]">
                                        <?= $day ?>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <?= date('M j', strtotime($startDate . ' +' . (array_search($day, $daysOfWeek)) . ' days')) ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($employees as $employee): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                    <span class="text-white font-semibold text-sm">
                                                        <?= strtoupper(substr($employee['fname'], 0, 1) . substr($employee['lname'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= $employee['fname'] . ' ' . $employee['lname'] ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= $employee['position'] ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?= $employee['company'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php foreach ($daysOfWeek as $day): ?>
                                        <td class="px-4 py-3 text-center">
                                            <select name="schedules[<?= $employee['id'] ?>][<?= $day ?>]" 
                                                    class="w-full p-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">No Schedule</option>
                                                <?php foreach ($schedules as $schedule): ?>
                                                    <?php 
                                                    $selected = getScheduleForEmployeeAndDay($employee['id'], $day, $existingSchedules) == $schedule['id'] ? 'selected' : '';
                                                    $scheduleLabel = $schedule['name'] ?: "{$schedule['time_in']} - {$schedule['time_out']}";
                                                    ?>
                                                    <option value="<?= $schedule['id'] ?>" <?= $selected ?>>
                                                        <?= $scheduleLabel ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Form Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Select schedules for each employee and day of the week
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" onclick="resetForm()" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Reset
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i>
                                Save Schedules
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Schedule Legend -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule Legend</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($schedules as $schedule): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <div class="w-4 h-4 bg-blue-500 rounded mr-3"></div>
                            <div>
                                <div class="font-medium text-gray-900">
                                    <?= $schedule['name'] ?: "Schedule {$schedule['id']}" ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= date('g:i A', strtotime($schedule['time_in'])) ?> - <?= date('g:i A', strtotime($schedule['time_out'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetForm() {
            if (confirm('Are you sure you want to reset all schedule selections?')) {
                document.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });
            }
        }

        // Auto-save warning
        window.addEventListener('beforeunload', function(e) {
            const form = document.querySelector('form');
            if (form && form.dataset.changed) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Mark form as changed
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                document.querySelector('form').dataset.changed = 'true';
            });
        });
    </script>
</body>
</html>
