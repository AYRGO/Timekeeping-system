<?php
session_start();
include('../config/db.php');

// Check if user is admin
if (!isset($_SESSION['employee']) || $_SESSION['employee']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$pageTitle = "Leave Accrual Management";

// Get current month info
$currentMonth = date('F Y');
$lastDayOfMonth = date('t');
$currentDay = date('j');
$isLastWeek = ($currentDay >= ($lastDayOfMonth - 7));

// Get last accrual date
$stmt = $pdo->query("SELECT MAX(updated_at) as last_accrual FROM leave_credits WHERE YEAR(updated_at) = YEAR(CURDATE())");
$lastAccrual = $stmt->fetchColumn();
$lastAccrualFormatted = $lastAccrual ? date('F j, Y g:i A', strtotime($lastAccrual)) : 'Never';

// Get total active employees
$stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
$totalEmployees = $stmt->fetchColumn();

// Get employees with leave credits
$stmt = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM leave_credits WHERE year = YEAR(CURDATE())");
$employeesWithCredits = $stmt->fetchColumn();

// Recent accrual activity
$stmt = $pdo->query("
    SELECT 
        e.fname, 
        e.lname, 
        lc.leave_type, 
        lc.balance, 
        lc.updated_at
    FROM leave_credits lc
    JOIN employees e ON lc.employee_id = e.id
    WHERE YEAR(lc.updated_at) = YEAR(CURDATE())
    ORDER BY lc.updated_at DESC
    LIMIT 10
");
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="../assets/css/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include('../views/sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('../views/header.php'); ?>
        
        <main class="flex-1 p-6 overflow-y-auto">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-slate-800 flex items-center">
                    <div class="bg-emerald-100 p-3 rounded-xl mr-4">
                        <i class="fas fa-calendar-plus text-emerald-600 text-2xl"></i>
                    </div>
                    Leave Accrual Management
                </h1>
                <p class="text-slate-600 mt-2 ml-16">Manage and process monthly leave credits for all employees</p>
            </div>

            <!-- Alert Banner -->
            <?php if ($isLastWeek): ?>
            <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <div class="bg-amber-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                    </div>
                    <div>
                        <h3 class="text-amber-800 font-semibold">End of Month Approaching</h3>
                        <p class="text-amber-700 text-sm">It's the last week of <?= date('F') ?>. Consider processing monthly leave accrual soon.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Current Month -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between mb-2">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-calendar text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $currentMonth ?></h3>
                    <p class="text-slate-500 text-sm mt-1">Current Period</p>
                </div>

                <!-- Total Employees -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500">
                    <div class="flex items-center justify-between mb-2">
                        <div class="bg-emerald-100 p-3 rounded-lg">
                            <i class="fas fa-users text-emerald-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $totalEmployees ?></h3>
                    <p class="text-slate-500 text-sm mt-1">Active Employees</p>
                </div>

                <!-- Employees with Credits -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between mb-2">
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $employeesWithCredits ?></h3>
                    <p class="text-slate-500 text-sm mt-1">With Leave Credits</p>
                </div>

                <!-- Last Accrual -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between mb-2">
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800"><?= $lastAccrualFormatted ?></h3>
                    <p class="text-slate-500 text-sm mt-1">Last Processed</p>
                </div>
            </div>

            <!-- Main Action Card -->
            <div class="bg-gradient-to-br from-white to-emerald-50 rounded-xl shadow-lg p-8 mb-6 border border-emerald-100">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-slate-800 mb-3 flex items-center">
                            <i class="fas fa-calendar-plus text-emerald-600 mr-3"></i>
                            Process Monthly Leave Accrual
                        </h2>
                        <p class="text-slate-600 mb-4">
                            This will add monthly leave credits to all active employees:
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <div class="bg-white rounded-lg p-4 border border-emerald-200 shadow-sm">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-thermometer-half text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">Sick Leave</p>
                                        <p class="text-2xl font-bold text-blue-600">+0.42 <span class="text-sm text-slate-500">days</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-emerald-200 shadow-sm">
                                <div class="flex items-center">
                                    <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-umbrella-beach text-emerald-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800">Vacation Leave</p>
                                        <p class="text-2xl font-bold text-emerald-600">+1.25 <span class="text-sm text-slate-500">days</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-amber-600 mt-1 mr-2"></i>
                                <p class="text-sm text-amber-800">
                                    <strong>Note:</strong> Maximum balance is 15 days. Vacation leave can carry over up to 5 days.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3">
                        <button 
                            onclick="processMonthlyAccrual()" 
                            id="accrualButton"
                            class="group bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-8 py-4 rounded-xl font-bold shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 flex items-center whitespace-nowrap">
                            <i class="fas fa-play-circle text-2xl mr-3 group-hover:scale-110 transition-transform"></i>
                            <span>Process Now</span>
                        </button>
                        <p class="text-xs text-slate-500 text-center">For <?= $currentMonth ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 border-b border-slate-200 px-6 py-4">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center">
                        <i class="fas fa-history text-blue-600 mr-3"></i>
                        Recent Accrual Activity
                    </h3>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentActivity)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Leave Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($recentActivity as $activity): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <?= htmlspecialchars($activity['fname'] . ' ' . $activity['lname']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                            <?= $activity['leave_type'] === 'sick' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                            <?= ucfirst($activity['leave_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">
                                        <?= number_format($activity['balance'], 2) ?> days
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        <?= date('M j, Y g:i A', strtotime($activity['updated_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-12">
                        <div class="bg-slate-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-slate-400 text-2xl"></i>
                        </div>
                        <p class="text-slate-500">No recent activity</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
function processMonthlyAccrual() {
    const button = document.getElementById('accrualButton');
    const originalText = button.innerHTML;
    
    if (!confirm('Process monthly leave accrual for all active employees?\n\n This will add:\n• 1.25 days to Vacation Leave\n• 0.42 days to Sick Leave\n\nContinue?')) {
        return;
    }
    
    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Processing...';
    
    // Make AJAX request
    fetch('process_monthly_leave_accrual.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.innerHTML = originalText;
        
        if (data.success) {
            showAccrualResults(data);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = originalText;
        alert('Error processing accrual: ' + error.message);
    });
}

function showAccrualResults(data) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.onclick = (e) => {
        if (e.target === modal) {
            modal.remove();
            location.reload();
        }
    };
    
    let detailsHTML = '';
    if (data.details && data.details.length > 0) {
        detailsHTML = '<div class="mt-4 max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-slate-100 sticky top-0"><tr><th class="px-3 py-2 text-left">Employee</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-right">Previous</th><th class="px-3 py-2 text-right">Added</th><th class="px-3 py-2 text-right">New Balance</th></tr></thead><tbody>';
        
        data.details.forEach(emp => {
            emp.updates.forEach(update => {
                detailsHTML += `<tr class="border-b border-slate-100">
                    <td class="px-3 py-2">${emp.name}</td>
                    <td class="px-3 py-2">${update.type}</td>
                    <td class="px-3 py-2 text-right">${update.previous}</td>
                    <td class="px-3 py-2 text-right text-emerald-600">+${update.added}</td>
                    <td class="px-3 py-2 text-right font-semibold">${update.new}</td>
                </tr>`;
            });
        });
        
        detailsHTML += '</tbody></table></div>';
    }
    
    let errorsHTML = '';
    if (data.errors && data.errors.length > 0) {
        errorsHTML = '<div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4"><h4 class="font-semibold text-red-800 mb-2">Errors:</h4><ul class="list-disc list-inside text-sm text-red-700">';
        data.errors.forEach(error => {
            errorsHTML += `<li>${error}</li>`;
        });
        errorsHTML += '</ul></div>';
    }
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 p-3 rounded-lg mr-3">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Accrual Processing Complete</h3>
                            <p class="text-emerald-100 text-sm">${data.message}</p>
                        </div>
                    </div>
                    <button onclick="this.closest('.fixed').remove(); location.reload();" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 100px)">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-800">${data.summary.total_employees}</div>
                        <div class="text-xs text-blue-600 mt-1">Total Employees</div>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-emerald-800">${data.summary.processed_employees}</div>
                        <div class="text-xs text-emerald-600 mt-1">Processed</div>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-800">${data.summary.total_records_updated}</div>
                        <div class="text-xs text-purple-600 mt-1">Records Updated</div>
                    </div>
                    <div class="bg-${data.summary.errors_count > 0 ? 'red' : 'slate'}-50 border border-${data.summary.errors_count > 0 ? 'red' : 'slate'}-200 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-${data.summary.errors_count > 0 ? 'red' : 'slate'}-800">${data.summary.errors_count}</div>
                        <div class="text-xs text-${data.summary.errors_count > 0 ? 'red' : 'slate'}-600 mt-1">Errors</div>
                    </div>
                </div>
                
                ${detailsHTML}
                ${errorsHTML}
                
                <div class="mt-6 flex justify-end gap-3 sticky bottom-0 bg-white pt-4 border-t">
                    <button onclick="this.closest('.fixed').remove(); location.reload();" class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-6 py-3 rounded-xl font-semibold shadow-md transition-all duration-300 hover:shadow-lg">
                        <i class="fas fa-check mr-2"></i>Done
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}
</script>

</body>
</html>
