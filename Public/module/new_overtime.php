<?php

include('../config/db.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

require_once 'time_logs_helper.php';

// Get employee's last 5 time logs that are OT eligible (no null data)
$employee_id = $_SESSION['employee']['id'] ?? 1; // Use the correct session key
$sql = "SELECT tl.*, DATE_FORMAT(tl.time_in, '%Y-%m-%d %H:%i') as formatted_time_in, 
               DATE_FORMAT(tl.time_out, '%Y-%m-%d %H:%i') as formatted_time_out
        FROM time_logs tl 
        WHERE tl.employee_id = ? AND tl.time_out IS NOT NULL 
        ORDER BY tl.log_date DESC LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([$employee_id]);
$time_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overtime request history
$history_sql = "SELECT ot.*, tl.log_date, tl.time_in, tl.time_out,
                       DATE_FORMAT(ot.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at,
                       DATE_FORMAT(ot.approved_at, '%M %d, %Y at %h:%i %p') as formatted_reviewed_at,
                       DATE_FORMAT(tl.log_date, '%M %d, %Y') as formatted_log_date,
                       DATE_FORMAT(tl.time_in, '%h:%i %p') as formatted_time_in,
                       DATE_FORMAT(tl.time_out, '%h:%i %p') as formatted_time_out
                FROM post_ot_requests ot 
                LEFT JOIN time_logs tl ON ot.time_log_id = tl.id 
                WHERE ot.employee_id = ? 
                ORDER BY ot.created_at DESC LIMIT 20";
$history_stmt = $pdo->prepare($history_sql);
$history_stmt->execute([$employee_id]);
$overtime_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get default schedule from session (set by schedule_tracker.php)
$default_sched = $_SESSION['current_schedule'] ?? [
    'time_in' => '07:00 AM',
    'time_out' => '04:00 PM'
];
$default_time_in = $default_sched['time_in'];
$default_time_out = $default_sched['time_out'];

?>
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out;
}

.animate-slide-in-right {
    animation: slideInRight 0.3s ease-out;
}

.hover-scale {
    transition: all 0.2s ease;
}

.hover-scale:hover {
    transform: scale(1.01);
}

.tab-active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-color: #10b981;
}

.tab-inactive {
    background: white;
    color: #6b7280;
    border-color: #e5e7eb;
}

.tab-inactive:hover {
    background: #f9fafb;
    color: #374151;
}

.status-approved {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.status-declined {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.status-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}
</style>

<div id="overtimeView" class="mt-12 hidden animate-fade-in-up">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Compact Header Section -->
    <div class="bg-gradient-to-r from-emerald-600 to-green-600 rounded-xl shadow-lg mb-6">
      <div class="p-6">
        <div class="flex flex-col lg:flex-row items-center justify-between">
          <div class="text-center lg:text-left mb-4 lg:mb-0">
            <div class="flex items-center justify-center lg:justify-start mb-3">
              <div class="p-2 bg-white/20 rounded-lg">
                <i class="fas fa-clock text-2xl text-white"></i>
              </div>
            </div>
            <h1 class="text-2xl lg:text-3xl font-bold text-white mb-1">
              Overtime Management
            </h1>
            <p class="text-emerald-100">
              Submit and track your overtime requests
            </p>
            
            <!-- Compact Stats -->
            <div class="flex gap-3 mt-3">
              <div class="bg-white/10 rounded-lg px-3 py-1.5">
                <div class="text-lg font-bold text-white"><?= count($time_logs) ?></div>
                <div class="text-xs text-emerald-100">Recent Logs</div>
              </div>
              <div class="bg-white/10 rounded-lg px-3 py-1.5">
                <div class="text-lg font-bold text-white">
                  <?= count(array_filter($time_logs, function($log) { 
                    return !empty($log['time_in']) && !empty($log['time_out']) && isOvertimeEligible($log['time_in'], $log['time_out']); 
                  })) ?>
                </div>
                <div class="text-xs text-emerald-100">OT Eligible</div>
              </div>
              <div class="bg-white/10 rounded-lg px-3 py-1.5">
                <div class="text-lg font-bold text-white"><?= count($overtime_history) ?></div>
                <div class="text-xs text-emerald-100">Total Requests</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 mb-6">
      <div class="p-6 border-b border-gray-200">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
          <button id="submitTab" onclick="switchTab('submit')" 
                  class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-active">
            <i class="fas fa-plus mr-2"></i>Submit Request
          </button>
          <button id="historyTab" onclick="switchTab('history')" 
                  class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-inactive">
            <i class="fas fa-history mr-2"></i>Request History
          </button>
        </div>
      </div>
    </div>

    <!-- Submit Request Tab Content -->
    <div id="submitContent" class="bg-white rounded-2xl shadow-lg border border-gray-200">
      
      <!-- Table Section -->
      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-xl font-bold text-gray-900 mb-1 flex items-center">
              <div class="p-2 bg-emerald-100 rounded-lg mr-3">
                <i class="fas fa-history text-emerald-600"></i>
              </div>
              Recent Time Logs
            </h3>
            <p class="text-gray-600">
              Last 5 days • Click "Request OT" for eligible entries
            </p>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200">
          <div class="overflow-x-auto">
            <table class="min-w-full bg-white" id="timeLogsTable">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center">
                      <i class="fas fa-calendar-alt mr-2 text-emerald-500"></i>
                      Date
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center">
                      <i class="fas fa-sign-in-alt mr-2 text-blue-500"></i>
                      Time In
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center">
                      <i class="fas fa-sign-out-alt mr-2 text-orange-500"></i>
                      Time Out
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center">
                      <i class="fas fa-clock mr-2 text-purple-500"></i>
                      Actual Work
                    </div>
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100" id="timeLogsBody">
                <?php foreach ($time_logs as $index => $log): 
                  $hasDate = !empty($log['log_date']);
                  $hasLog = !empty($log['time_in']) && !empty($log['time_out']);
                  $isRdot = false;
                  if ($hasLog) {
                    $isOTEligible = isOvertimeEligible($log['time_in'], $log['time_out']);
                    $overtimeHours = calculateOvertimeHours($log['time_in'], $log['time_out']);
                    $actualHours = calculateActualHoursWorked($log['time_in'], $log['time_out']);
                    $hasRequest = hasExistingOTRequest($log['id']);
                    $timeIn = new DateTime($log['time_in']);
                    $timeOut = new DateTime($log['time_out']);
                    $interval = $timeIn->diff($timeOut);
                    $totalHours = $interval->h + ($interval->i / 60);

                    $logDate = new DateTime($log['log_date']);
                    $now = new DateTime();
                    $daysPassed = $logDate->diff($now)->days;
                    $isOlderThan5Days = $daysPassed > 5;
                    $rowStatus = $isOlderThan5Days ? 'noteligible' : ($isOTEligible ? ($hasRequest ? 'submitted' : 'eligible') : 'regular');
                  }
                ?>
                <tr class="hover:bg-emerald-50 transition-colors duration-200 animate-slide-in-right
                  <?= $hasLog ? ($isOlderThan5Days ? 'bg-gray-50 border-l-4 border-l-gray-300' : ($isOTEligible ? 'bg-emerald-50/50 border-l-4 border-l-emerald-400' : '')) : 'bg-gray-50 border-l-4 border-l-gray-200' ?>"
                  style="animation-delay: <?= $index * 0.05 ?>s;"
                  data-date="<?= $hasDate ? date('M d, Y', strtotime($log['log_date'])) : '' ?>"
                  data-status="<?= $hasLog ? $rowStatus : 'rdot' ?>"
                  data-log-id="<?= $hasLog ? $log['id'] : '' ?>"
                  data-time-in="<?= $hasLog ? $log['time_in'] : '' ?>"
                  data-time-out="<?= $hasLog ? $log['time_out'] : '' ?>"
                  data-ot-hours="<?= $hasLog ? $overtimeHours : '0' ?>"
                  data-is-rdot="<?= $isRdot ? '1' : '0' ?>">
                  
                  <td class="px-6 py-4">
                    <div>
                      <div class="text-base font-semibold text-gray-900">
                        <?= $hasDate ? date('M d, Y', strtotime($log['log_date'])) : '<span class="italic text-gray-400">N/A</span>' ?>
                      </div>
                      <div class="text-sm text-gray-500">
                        <?= $hasDate ? date('l', strtotime($log['log_date'])) : '' ?>
                      </div>
                    </div>
                  </td>
                  
                  <td class="px-6 py-4">
                    <span class="text-base font-semibold text-gray-900">
                      <?= $hasLog ? date('h:i A', strtotime($log['time_in'])) : '—' ?>
                    </span>
                  </td>
                  
                  <td class="px-6 py-4">
                    <span class="text-base font-semibold text-gray-900">
                      <?= $hasLog ? date('h:i A', strtotime($log['time_out'])) : '—' ?>
                    </span>
                  </td>
                  
                  <td class="px-6 py-4">
                    <div>
                      <div class="text-base font-semibold text-gray-900">
                        <?= $hasLog ? number_format($actualHours, 2) . 'h' : '—' ?>
                      </div>
                    </div>
                  </td>
                  
                  <td class="px-6 py-4">
                    <?php if (!$hasLog): ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-500">
                        <i class="fas fa-ban mr-2"></i>
                        No Data
                      </span>
                    <?php elseif ($isOlderThan5Days): ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-700">
                        <i class="fas fa-clock mr-2"></i>
                        Expired
                      </span>
                    <?php elseif ($isOTEligible): ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-700">
                        <i class="fas fa-star mr-2"></i>
                        OT Eligible (<?= number_format($overtimeHours, 2) ?>h)
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                        <i class="fas fa-check mr-2"></i>
                        Regular
                      </span>
                    <?php endif; ?>
                  </td>
                  
                  <td class="px-6 py-4">
                    <?php if (!$hasLog): ?>
                      <span class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium">
                        <i class="fas fa-ban mr-2"></i>
                        Not Available
                      </span>
                    <?php elseif ($isOlderThan5Days): ?>
                      <span class="inline-flex items-center px-3 py-2 bg-red-100 text-red-600 rounded-lg text-sm font-medium">
                        <i class="fas fa-clock mr-2"></i>
                        Expired
                      </span>
                    <?php elseif ($isOTEligible && !$hasRequest): ?>
                      <button onclick="openOvertimeModal(this)" 
                              class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-all duration-200 hover-scale focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <i class="fas fa-plus mr-2"></i>
                        Request OT
                      </button>
                    <?php elseif ($hasRequest): ?>
                      <span class="inline-flex items-center px-3 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                        <i class="fas fa-check-circle mr-2"></i>
                        Submitted
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400 text-sm">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
          <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
            <i class="fas fa-search text-2xl text-emerald-600"></i>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">No Records Found</h3>
          <p class="text-gray-600 mb-4">
            No matching time logs found. Try adjusting your search criteria.
          </p>
          <button class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-refresh mr-2"></i>
            Refresh Page
          </button>
        </div>
      </div>
    </div>

    <!-- Request History Tab Content -->
    <div id="historyContent" class="bg-white rounded-2xl shadow-lg border border-gray-200 hidden">
      <div class="p-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-xl font-bold text-gray-900 mb-1 flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg mr-3">
                <i class="fas fa-file-alt text-blue-600"></i>
              </div>
              Overtime Request History
            </h3>
            <p class="text-gray-600">
              Track the status of your overtime requests
            </p>
          </div>
        </div>

        <?php if (empty($overtime_history)): ?>
          <!-- No History Message -->
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
              <i class="fas fa-inbox text-2xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Requests Yet</h3>
            <p class="text-gray-600 mb-4">
              You haven't submitted any overtime requests yet.
            </p>
            <button onclick="switchTab('submit')" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">
              <i class="fas fa-plus mr-2"></i>
              Submit Your First Request
            </button>
          </div>
        <?php else: ?>
          <!-- History Table -->
          <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
              <table class="min-w-full bg-white">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                        Date & Time
                      </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-clock mr-2 text-purple-500"></i>
                        OT Details
                      </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      <div class="flex items-center">
                        <i class="fas fa-tag mr-2 text-green-500"></i>
                        Type & Hours
                      </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      Submitted
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach ($overtime_history as $index => $request): 
                    $statusClass = '';
                    $statusIcon = '';
                    $statusText = '';
                    
                    switch(strtolower($request['status'])) {
                      case 'approved':
                        $statusClass = 'status-approved';
                        $statusIcon = 'fas fa-check-circle';
                        $statusText = 'Approved';
                        break;
                      case 'declined':
                        $statusClass = 'status-declined';
                        $statusIcon = 'fas fa-times-circle';
                        $statusText = 'Declined';
                        break;
                      default:
                        $statusClass = 'status-pending';
                        $statusIcon = 'fas fa-clock';
                        $statusText = 'Pending';
                    }
                  ?>
                  <tr class="hover:bg-gray-50 transition-colors duration-200 animate-slide-in-right"
                      style="animation-delay: <?= $index * 0.05 ?>s;">
                    
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-base font-semibold text-gray-900">
                          <?= $request['formatted_log_date'] ?? 'N/A' ?>
                        </div>
                        <div class="text-sm text-gray-500 flex items-center">
                          <i class="fas fa-sign-in-alt mr-1 text-green-500"></i>
                          <?= $request['formatted_time_in'] ?? 'N/A' ?>
                          <span class="mx-2">→</span>
                          <i class="fas fa-sign-out-alt mr-1 text-orange-500"></i>
                          <?= $request['formatted_time_out'] ?? 'N/A' ?>
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-900 max-w-xs">
                        <div class="font-medium truncate" title="<?= htmlspecialchars($request['reason']) ?>">
                          <?= htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : '') ?>
                        </div>
                        <?php if (!empty($request['admin_comment'])): ?>
                          <div class="text-xs text-gray-500 mt-1 italic">
                            "<?= htmlspecialchars(substr($request['admin_comment'], 0, 40)) . (strlen($request['admin_comment']) > 40 ? '...' : '') ?>"
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>
                    
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          <?= htmlspecialchars($request['ot_type']) ?>
                        </div>
                        <div class="text-sm text-gray-500 flex items-center">
                          <i class="fas fa-hourglass-half mr-1 text-purple-500"></i>
                          <?= number_format($request['ot_duration'], 2) ?> hours
                        </div>
                      </div>
                    </td>
                    
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                        <i class="<?= $statusIcon ?> mr-2"></i>
                        <?= $statusText ?>
                      </span>
                    </td>
                    
                    <td class="px-6 py-4">
                      <div class="text-sm text-gray-900">
                        <?= $request['formatted_created_at'] ?>
                      </div>
                      <?php if ($request['status'] !== 'pending' && $request['formatted_reviewed_at']): ?>
                        <div class="text-xs text-gray-500">
                          Reviewed: <?= $request['formatted_reviewed_at'] ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    
                    <td class="px-6 py-4">
                      <div class="flex space-x-2">
                        <button onclick="viewOTDetails(<?= $request['id'] ?>)" 
                                class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-sm font-medium transition-colors">
                          <i class="fas fa-eye mr-1"></i>
                          View
                        </button>
                        <?php if (!empty($request['attachment'])): ?>
                          <a href="../uploads/overtime_attachments/<?= htmlspecialchars($request['attachment']) ?>" target="_blank"
                             class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-paperclip mr-1"></i>
                            File
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Overtime Request Modal -->
<div id="overtimeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" onclick="closeOvertimeModal()"></div>

        <!-- Modal content -->
        <div class="inline-block w-full max-w-3xl px-0 pt-0 pb-0 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle border border-gray-200 animate-fade-in-up">
            
            <!-- Modal Header -->
            <div class="bg-emerald-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-2 bg-white/20 rounded-lg mr-3">
                            <i class="fas fa-clock text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Submit Overtime Request</h3>
                            <p class="text-emerald-100 text-sm">Fill out the details for your overtime request</p>
                        </div>
                    </div>
                    <button onclick="closeOvertimeModal()" class="p-2 text-white/80 hover:text-white rounded-lg hover:bg-white/20 transition-colors focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <form id="overtimeForm" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" id="selected_time_log_id" name="time_log_id">
                    <input type="hidden" id="selected_time_in" name="time_in">
                    <input type="hidden" id="selected_time_out" name="time_out">
                    
                    <!-- Information Grid -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-emerald-600 mr-2"></i>
                            Request Information
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Overtime Hours</label>
                                <div class="relative">
                                    <input type="number" id="overtime_hours" name="overtime_hours" step="0.25" min="0.25" max="12" 
                                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white font-semibold text-center text-emerald-600 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                           readonly>
                                    <i class="fas fa-clock absolute left-3 top-1/2 transform -translate-y-1/2 text-emerald-500"></i>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Date</label>
                                <div class="relative">
                                    <input type="text" id="selected_date" name="selected_date" 
                                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                           readonly>
                                    <i class="fas fa-calendar absolute left-3 top-1/2 transform -translate-y-1/2 text-blue-500"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Time In</label>
                                <div class="relative">
                                    <input type="text" id="display_time_in" 
                                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                           readonly>
                                    <i class="fas fa-sign-in-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-green-500"></i>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Time Out</label>
                                <div class="relative">
                                    <input type="text" id="display_time_out" 
                                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                           readonly>
                                    <i class="fas fa-sign-out-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-orange-500"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- OT Type and Attachment -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Overtime Type <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="ot_type" id="ot_type" required
                                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all appearance-none bg-white">
                                    <option value="">Select OT Type</option>
                                    <option value="Regular OT">Regular OT</option>
                                    <option value="Special Holiday OT">Special Holiday OT</option>
                                    <option value="Regular Holiday OT">Regular Holiday OT</option>
                                    <option value="Restday OT">Restday OT</option>
                                </select>
                                <i class="fas fa-briefcase absolute left-3 top-1/2 transform -translate-y-1/2 text-purple-500"></i>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-600">Choose the appropriate overtime type</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Supporting Document <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   name="attachment" 
                                   id="attachment" 
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   required
                                   class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                            <p class="text-xs text-gray-600">Upload PDF, JPG, or PNG (Max 5MB)</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Reason for Overtime <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="4" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all resize-none"
                                  placeholder="Please provide a detailed explanation for your overtime work..."></textarea>
                        <p class="text-xs text-gray-600">Provide a clear reason for your overtime request</p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeOvertimeModal()" 
                                class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button type="submit" 
                                class="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Open overtime modal with data
function openOvertimeModal(button) {
    const row = button.closest('tr');
    const timeLogId = row.dataset.logId;
    const timeIn = row.dataset.timeIn;
    const timeOut = row.dataset.timeOut;
    const otHours = parseFloat(row.dataset.otHours);
    const date = row.dataset.date;
    
    console.log('Modal Data:', { timeLogId, timeIn, timeOut, otHours, date }); // Debug log
    
    // Populate form fields
    document.getElementById('selected_time_log_id').value = timeLogId || '';
    document.getElementById('selected_time_in').value = timeIn || '';
    document.getElementById('selected_time_out').value = timeOut || '';
    document.getElementById('overtime_hours').value = (otHours || 0).toFixed(2);
    document.getElementById('selected_date').value = date || '';
    
    // Format and display times - only if timeIn and timeOut exist
    if (timeIn && timeOut) {
        try {
            const timeInFormatted = new Date('2000-01-01 ' + timeIn).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const timeOutFormatted = new Date('2000-01-01 ' + timeOut).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            document.getElementById('display_time_in').value = timeInFormatted;
            document.getElementById('display_time_out').value = timeOutFormatted;
        } catch (error) {
            console.error('Time formatting error:', error);
            document.getElementById('display_time_in').value = timeIn;
            document.getElementById('display_time_out').value = timeOut;
        }
    }
    
    // Set default OT type
    document.getElementById('ot_type').value = 'Regular OT';
    
    // Clear previous values
    document.getElementById('reason').value = '';
    document.getElementById('attachment').value = '';
    
    // Show modal
    document.getElementById('overtimeModal').classList.remove('hidden');
    
    // Focus on OT type field
    setTimeout(() => {
        document.getElementById('ot_type').focus();
    }, 100);

    // Enable all fields for normal OT
    document.getElementById('ot_type').removeAttribute('disabled');
    document.getElementById('selected_time_in').removeAttribute('readonly');
    document.getElementById('selected_time_out').removeAttribute('readonly');
    document.getElementById('selected_date').setAttribute('readonly', 'readonly');
    document.getElementById('overtime_hours').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_in').setAttribute('readonly', 'readonly');
    document.getElementById('display_time_out').setAttribute('readonly', 'readonly');
    document.getElementById('reason').removeAttribute('readonly');
    document.getElementById('attachment').removeAttribute('disabled');
}

// Add missing close function for modal
function closeOvertimeModal() {
    document.getElementById('overtimeModal').classList.add('hidden');
}

// Tab switching functionality
function switchTab(tab) {
    const submitTab = document.getElementById('submitTab');
    const historyTab = document.getElementById('historyTab');
    const submitContent = document.getElementById('submitContent');
    const historyContent = document.getElementById('historyContent');
    
    if (tab === 'submit') {
        submitTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-active';
        historyTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-inactive';
        submitContent.classList.remove('hidden');
        historyContent.classList.add('hidden');
    } else {
        submitTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-inactive';
        historyTab.className = 'flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 tab-active';
        submitContent.classList.add('hidden');
        historyContent.classList.remove('hidden');
    }
}

// View OT details function
function viewOTDetails(requestId) {
    // You can implement a modal or redirect to a detailed view
    fetch('get_overtime_details.php?id=' + requestId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Display details in a modal or alert
                let details = `Overtime Request Details:\n\n`;
                details += `Date: ${data.request.log_date}\n`;
                details += `Type: ${data.request.ot_type}\n`;
                details += `Hours: ${data.request.overtime_hours}\n`;
                details += `Status: ${data.request.status}\n`;
                details += `Reason: ${data.request.reason}\n`;
                if (data.request.reviewed_comment) {
                    details += `Review Comment: ${data.request.reviewed_comment}\n`;
                }
                alert(details);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching details');
        });
}

// Enhanced form submission handler - submit to dedicated processor
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('overtimeForm');
    if (!form) {
        console.error('Overtime form not found!');
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Form submission started...');
        
        // Wait a moment to ensure any pending input is captured
        setTimeout(() => {
            submitOvertimeForm(this);
        }, 100);
    });
});

function submitOvertimeForm(form) {
    // Get form elements more reliably with explicit checks
    const timeLogId = form.querySelector('#selected_time_log_id')?.value || '';
    const otType = form.querySelector('#ot_type')?.value || '';
    const reasonElement = form.querySelector('#reason');
    const overtimeHours = form.querySelector('#overtime_hours')?.value || '';
    const timeIn = form.querySelector('#selected_time_in')?.value || '';
    const timeOut = form.querySelector('#selected_time_out')?.value || '';
    const attachment = form.querySelector('#attachment')?.files[0];
    
    // Debug all form elements
    console.log('All form inputs:', {
        timeLogElement: form.querySelector('#selected_time_log_id'),
        otTypeElement: form.querySelector('#ot_type'),
        reasonElement: reasonElement,
        hoursElement: form.querySelector('#overtime_hours'),
        timeInElement: form.querySelector('#selected_time_in'),
        timeOutElement: form.querySelector('#selected_time_out'),
        attachmentElement: form.querySelector('#attachment')
    });
    
    // Check if reason element exists and get its value
    let reason = '';
    if (reasonElement) {
        reason = reasonElement.value.trim();
        console.log('Reason element found:', {
            element: reasonElement,
            value: reasonElement.value,
            trimmed: reason,
            focused: document.activeElement === reasonElement,
            placeholder: reasonElement.placeholder,
            required: reasonElement.required
        });
    } else {
        console.error('Reason element not found!');
        alert('Error: Reason input field not found. Please refresh the page and try again.');
        return;
    }
    
    // Additional debugging for reason field
    const reasonById = document.getElementById('reason');
    const reasonByName = document.getElementsByName('reason')[0];
    const allTextareas = document.querySelectorAll('textarea');
    
    console.log('Comprehensive reason field debugging:', {
        byId: reasonById ? { value: reasonById.value, id: reasonById.id } : 'NOT FOUND',
        byName: reasonByName ? { value: reasonByName.value, name: reasonByName.name } : 'NOT FOUND',
        allTextareas: Array.from(allTextareas).map(ta => ({ 
            id: ta.id, 
            name: ta.name, 
            value: ta.value,
            placeholder: ta.placeholder 
        })),
        modalVisible: !document.getElementById('overtimeModal').classList.contains('hidden')
    });
    
    // Validation with detailed error messages
    if (!timeLogId) {
        alert('Error: Time log ID is missing. Please close the modal and try again.');
        return;
    }
    
    if (!otType) {
        alert('Error: Please select an overtime type.');
        form.querySelector('#ot_type')?.focus();
        return;
    }
    
    if (!reason || reason.length === 0) {
        alert('Error: Please provide a reason for overtime.\n\nCurrent status:\n- Field found: ' + (reasonElement ? 'Yes' : 'No') + '\n- Field value: "' + reason + '"\n- Field length: ' + reason.length);
        if (reasonElement) {
            reasonElement.focus();
            reasonElement.style.border = '2px solid red';
            // Try to force focus and highlight
            reasonElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }
    
    if (!attachment) {
        alert('Error: Please upload a supporting document.');
        form.querySelector('#attachment')?.focus();
        return;
    }
    
    if (!overtimeHours || parseFloat(overtimeHours) <= 0) {
        alert('Error: Invalid overtime hours: ' + overtimeHours);
        return;
    }
    
    // Create FormData manually with additional validation
    const formData = new FormData();
    formData.append('time_log_id', timeLogId);
    formData.append('ot_type', otType);
    formData.append('reason', reason);
    formData.append('overtime_hours', overtimeHours);
    formData.append('time_in', timeIn);
    formData.append('time_out', timeOut);
    formData.append('attachment', attachment);
    
    // Final validation of FormData
    console.log('FormData validation:');
    let hasReason = false;
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: "${value}"`);
        if (key === 'reason') {
            hasReason = true;
            if (!value || value.trim().length === 0) {
                alert('Error: Reason field is empty in FormData. Please try typing your reason again.');
                return;
            }
        }
    }
    
    if (!hasReason) {
        alert('Error: Reason field not found in form data. Please refresh and try again.');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
    submitBtn.disabled = true;
    
    // Submit to dedicated processing file
    fetch('process_overtime_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            alert(data.message);
            closeOvertimeModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        // Remove error styling
        if (reasonElement) {
            reasonElement.style.border = '';
        }
    });
}
</script>