<?php
// employee_calendar.php
// Employee Calendar View - Simplified interface for employees to view their schedule and request changes
// Requirements: PHP 7.4+ with PDO MySQL enabled.

date_default_timezone_set('Asia/Manila');

// Use existing database connection
require_once __DIR__ . '/Public/config/db.php';

// Start session to get employee info (assuming employee is logged in)
session_start();

// For demonstration, we'll use GET parameter for employee_id
// In production, this should come from session after login
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : (isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 1);

// ---------- Helpers ----------
function h($s){ return htmlspecialchars($s,ENT_QUOTES); }

function getEmployeeInfo($pdo, $employee_id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND status = 'active'");
    $stmt->execute([$employee_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getMonthsNav($year, $month) {
    $prev = date('Y-n', strtotime("$year-$month-01 -1 month"));
    $next = date('Y-n', strtotime("$year-$month-01 +1 month"));
    return ['prev'=>$prev, 'next'=>$next];
}

// Get employee's schedule for a specific date
function getEmployeeScheduleForDate($pdo, $employee_id, $date) {
    $cell = [
        'date' => $date, 
        'employee_id' => $employee_id, 
        'base_schedule' => null, 
        'actual_schedule' => null, 
        'is_rest_day' => 0, 
        'is_holiday' => 0, 
        'has_override' => 0, 
        'override_status' => null, 
        'schedule_color' => '#9ca3af'
    ];

    // Check for active schedule assignment
    $assignStmt = $pdo->prepare("
        SELECT * FROM employee_schedule_assignments
        WHERE employee_id = ? 
        AND (end_date IS NULL OR date(end_date) >= date(?))
        AND date(start_date) <= date(?)
        ORDER BY start_date DESC
        LIMIT 1
    ");
    $assignStmt->execute([$employee_id, $date, $date]);
    $assign = $assignStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assign) {
        if ($assign['assignment_type'] === 'fixed' && $assign['work_schedule_id']) {
            $ws = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
            $ws->execute([$assign['work_schedule_id']]);
            $cell['base_schedule'] = $ws->fetch(PDO::FETCH_ASSOC);
        } elseif ($assign['assignment_type'] === 'pattern' && $assign['schedule_pattern_id']) {
            // Handle pattern-based schedule
            $pat = $pdo->prepare("SELECT * FROM schedule_patterns WHERE id = ?");
            $pat->execute([$assign['schedule_pattern_id']]);
            $pat = $pat->fetch(PDO::FETCH_ASSOC);
            
            if ($pat) {
                $cycle_len = max(1, (int)$pat['cycle_length']);
                $cycle_start = $assign['cycle_start_date'] ? $assign['cycle_start_date'] : $assign['start_date'];
                $start_ts = strtotime($cycle_start);
                $d_ts = strtotime($date);
                $diff_days = (int) floor(($d_ts - $start_ts) / 86400);
                $day_of_cycle = ($diff_days % $cycle_len) + 1;
                
                $detail = $pdo->prepare("
                    SELECT spd.*, ws.* 
                    FROM schedule_pattern_details spd 
                    LEFT JOIN work_schedules ws ON ws.id = spd.work_schedule_id 
                    WHERE spd.pattern_id = ? AND spd.day_of_cycle = ? 
                    LIMIT 1
                ");
                $detail->execute([$pat['id'], $day_of_cycle]);
                $d = $detail->fetch(PDO::FETCH_ASSOC);
                
                if ($d) {
                    if ($d['is_rest_day']) { 
                        $cell['is_rest_day'] = 1; 
                    }
                    $cell['base_schedule'] = $d;
                } else {
                    $cell['is_rest_day'] = 1;
                }
            }
        }
    }

    // Check for holidays
    $hstmt = $pdo->prepare("
        SELECT * FROM holiday_calendar 
        WHERE DATE(holiday_date) = DATE(?) 
        OR (is_recurring=1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(?, '%m-%d')) 
        LIMIT 1
    ");
    $hstmt->execute([$date, $date]);
    $holiday = $hstmt->fetch(PDO::FETCH_ASSOC);
    if ($holiday) {
        $cell['is_holiday'] = 1;
        $cell['holiday'] = $holiday;
    }

    // Check for schedule overrides
    $ov = $pdo->prepare("
        SELECT * FROM calendar_schedule_overrides 
        WHERE employee_id = ? AND date(schedule_date) = date(?) 
        ORDER BY created_at DESC LIMIT 1
    ");
    $ov->execute([$employee_id, $date]);
    $override = $ov->fetch(PDO::FETCH_ASSOC);
    
    if ($override) {
        $cell['has_override'] = 1;
        $cell['override_status'] = $override['is_approved'] ? 'approved' : 'pending';
        $cell['override'] = $override;
        
        if ($override['is_approved']) {
            if ($override['override_schedule_id']) {
                $ws = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
                $ws->execute([$override['override_schedule_id']]);
                $cell['actual_schedule'] = $ws->fetch(PDO::FETCH_ASSOC);
                $cell['schedule_color'] = $cell['actual_schedule']['color_code'] ?? $cell['schedule_color'];
            } else {
                // Rest day override
                $cell['actual_schedule'] = null;
                $cell['is_rest_day'] = 1;
                $cell['schedule_color'] = '#6b7280';
            }
        } else {
            // Pending override - show base schedule but indicate pending
            $cell['actual_schedule'] = $cell['base_schedule'];
            $cell['schedule_color'] = '#f59e0b';
        }
    } else {
        // No override - actual = base
        if (!empty($cell['base_schedule'])) {
            $cell['actual_schedule'] = $cell['base_schedule'];
            $cell['schedule_color'] = $cell['base_schedule']['color_code'] ?? $cell['schedule_color'];
        } else {
            if ($cell['is_rest_day']) $cell['schedule_color'] = '#f3f4f6';
        }
    }

    return $cell;
}

// Get employee's time log status for a date
function getEmployeeTimeLogStatus($pdo, $employee_id, $date) {
    $timeLog = $pdo->prepare("
        SELECT * FROM time_logs 
        WHERE employee_id = ? AND log_date = ?
    ");
    $timeLog->execute([$employee_id, $date]);
    $log = $timeLog->fetch(PDO::FETCH_ASSOC);
    
    $scheduleInfo = getEmployeeScheduleForDate($pdo, $employee_id, $date);
    
    $status = [
        'has_log' => (bool)$log,
        'log_data' => $log,
        'schedule_info' => $scheduleInfo,
        'status' => 'No Data',
        'time_in' => null,
        'time_out' => null
    ];
    
    if ($log) {
        $status['time_in'] = $log['time_in'];
        $status['time_out'] = $log['time_out'];
        
        if ($scheduleInfo['is_rest_day'] && !$scheduleInfo['has_override']) {
            $status['status'] = 'Rest Day Work';
        } elseif ($scheduleInfo['is_holiday']) {
            $status['status'] = 'Holiday Work';
        } elseif ($log['status'] === 'incomplete' || !$log['time_out'] || $log['time_out'] === 'INC') {
            $status['status'] = 'Incomplete';
        } else {
            $scheduledIn = $scheduleInfo['actual_schedule']['time_in'] ?? '08:00:00';
            $actualIn = $log['time_in'];
            
            if ($actualIn > date('H:i:s', strtotime($scheduledIn . ' +15 minutes'))) {
                $status['status'] = 'Late';
            } else {
                $status['status'] = 'Present';
            }
        }
    } else {
        if ($scheduleInfo['is_rest_day']) {
            $status['status'] = 'Rest Day';
        } elseif ($scheduleInfo['is_holiday']) {
            $status['status'] = 'Holiday';
        } else {
            $status['status'] = 'Absent';
        }
    }
    
    return $status;
}

// Handle form submissions for override requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_override') {
        $schedule_date = $_POST['schedule_date'];
        $override_schedule_id = $_POST['override_schedule_id'] ? (int)$_POST['override_schedule_id'] : null;
        $reason = $_POST['reason'] ?? '';
        $override_type = $_POST['override_type'] ?? 'schedule_change';
        
        // Get original schedule
        $origCell = getEmployeeScheduleForDate($pdo, $employee_id, $schedule_date);
        $origId = $origCell['base_schedule']['id'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO calendar_schedule_overrides 
            (employee_id, schedule_date, original_schedule_id, override_schedule_id, override_type, reason, is_approved, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $employee_id, 
            $schedule_date, 
            $origId, 
            $override_schedule_id, 
            $override_type, 
            $reason, 
            0, 
            $employee_id, 
            date('Y-m-d H:i:s')
        ]);
        
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . '?employee_id=' . $employee_id . '&success=override_requested');
        exit;
    }
}

// ---------- UI Setup ----------
$employee = getEmployeeInfo($pdo, $employee_id);
if (!$employee) {
    die("Employee not found or inactive.");
}

$ym = isset($_GET['ym']) ? $_GET['ym'] : date('Y-n');
list($year, $month) = explode('-', $ym);
$month = (int)$month; 
$year = (int)$year;
$nav = getMonthsNav($year, $month);

function monthMatrix($year, $month) {
    $first = strtotime("$year-$month-01");
    $start_w = date('w', $first);
    $days = date('t', $first);
    $matrix = [];
    $week = array_fill(0, 7, null);
    $day = 1;
    $wday = $start_w;
    
    while ($day <= $days) {
        $week[$wday] = date('Y-m-d', strtotime("$year-$month-$day"));
        $wday++;
        if ($wday === 7) {
            $matrix[] = $week;
            $week = array_fill(0, 7, null);
            $wday = 0;
        }
        $day++;
    }
    if (array_filter($week)) $matrix[] = $week;
    return $matrix;
}

$matrix = monthMatrix($year, $month);

// Get work schedules for override form
$workSchedules = $pdo->query("SELECT * FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get employee's pending override requests
$pendingRequests = $pdo->prepare("
    SELECT * FROM calendar_schedule_overrides 
    WHERE employee_id = ? AND is_approved = 0 
    ORDER BY created_at DESC
");
$pendingRequests->execute([$employee_id]);
$pendingRequests = $pendingRequests->fetchAll(PDO::FETCH_ASSOC);

// Success message
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'override_requested') {
    $success_message = 'Schedule override request submitted successfully! Please wait for approval.';
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Schedule - Employee Calendar</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header -->
  <header class="bg-white shadow-sm border-b">
    <div class="max-w-6xl mx-auto px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <i class="fas fa-calendar-alt text-blue-600 text-2xl"></i>
          <div>
            <h1 class="text-xl font-bold text-gray-800">My Schedule</h1>
            <p class="text-sm text-gray-600">Welcome, <?= h($employee['fname'] . ' ' . $employee['lname']) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-right text-sm">
            <div class="text-gray-600">Company: <?= h($employee['company'] ?? 'N/A') ?></div>
            <div class="text-gray-600">Position: <?= h($employee['position'] ?? 'N/A') ?></div>
          </div>
          <a href="Public/" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-home mr-1"></i> Dashboard
          </a>
        </div>
      </div>
    </div>
  </header>

  <?php if ($success_message): ?>
    <div class="max-w-6xl mx-auto px-6 pt-4">
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i><?= h($success_message) ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="max-w-6xl mx-auto px-6 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Calendar -->
      <div class="lg:col-span-3 bg-white rounded-lg shadow p-6">
        <!-- Calendar Navigation -->
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <button onclick="changeMonth('<?= h($nav['prev']) ?>')" 
               class="p-2 rounded-lg hover:bg-gray-100 text-gray-600">
              <i class="fas fa-chevron-left"></i>
            </button>
            <h2 id="calendar-title" class="text-2xl font-bold text-gray-800">
              <?= date('F Y', strtotime("$year-$month-01")) ?>
            </h2>
            <button onclick="changeMonth('<?= h($nav['next']) ?>')" 
               class="p-2 rounded-lg hover:bg-gray-100 text-gray-600">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <button onclick="changeMonth('<?= date('Y-n') ?>')" 
             class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
            <i class="fas fa-calendar-day mr-1"></i> Today
          </button>
        </div>

        <!-- Calendar Grid -->
        <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-sm">
          <!-- Weekday Headers -->
          <?php $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
                foreach($weekdays as $wd): ?>
            <div class="text-center font-semibold py-3 text-gray-700 bg-gray-50 rounded">
              <?= substr($wd, 0, 3) ?>
            </div>
          <?php endforeach; ?>

          <!-- Calendar Days -->
          <?php foreach($matrix as $week): ?>
            <?php foreach($week as $cellDate): ?>
              <?php if (!$cellDate): ?>
                <div class="h-24 bg-gray-50 rounded"></div>
              <?php else: 
                    $cell = getEmployeeScheduleForDate($pdo, $employee_id, $cellDate);
                    $timeStatus = getEmployeeTimeLogStatus($pdo, $employee_id, $cellDate);
                    $isToday = $cellDate === date('Y-m-d');
                    
                    // Status-based colors
                    $statusColors = [
                        'Present' => '#10b981', 'Late' => '#f59e0b', 'Incomplete' => '#ef4444',
                        'Absent' => '#ef4444', 'Rest Day' => '#6b7280', 'Holiday' => '#8b5cf6',
                        'Rest Day Work' => '#06b6d4', 'Holiday Work' => '#06b6d4'
                    ];
                    $statusColor = $statusColors[$timeStatus['status']] ?? '#9ca3af';
              ?>
                <div class="h-24 p-2 border rounded-lg relative overflow-hidden <?= $isToday ? 'ring-2 ring-blue-500' : '' ?>"
                     style="background: linear-gradient(135deg, <?= h($statusColor) ?>15, <?= h($statusColor) ?>05);">
                  
                  <!-- Date -->
                  <div class="flex justify-between items-start mb-1">
                    <span class="font-medium <?= $isToday ? 'text-blue-600' : 'text-gray-700' ?>">
                      <?= date('j', strtotime($cellDate)) ?>
                    </span>
                    
                    <!-- Status indicators -->
                    <div class="flex gap-1">
                      <?php if ($cell['is_holiday']): ?>
                        <div class="w-2 h-2 bg-purple-500 rounded-full" title="Holiday"></div>
                      <?php endif; ?>
                      <?php if ($cell['has_override']): ?>
                        <div class="w-2 h-2 <?= $cell['override_status'] === 'approved' ? 'bg-green-500' : 'bg-yellow-500' ?> rounded-full" 
                             title="Override <?= $cell['override_status'] ?>"></div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Status Badge -->
                  <div class="text-xs mb-1">
                    <span class="px-2 py-1 rounded-full text-white text-xs font-medium"
                          style="background-color: <?= h($statusColor) ?>;">
                      <?= h($timeStatus['status']) ?>
                    </span>
                  </div>

                  <!-- Schedule Time -->
                  <div class="text-xs text-gray-600">
                    <?php if ($cell['is_rest_day'] && !$cell['actual_schedule']): ?>
                      <i class="fas fa-bed mr-1"></i>Rest Day
                    <?php elseif ($cell['actual_schedule']): ?>
                      <i class="fas fa-clock mr-1"></i>
                      <?= date('g:i A', strtotime($cell['actual_schedule']['time_in'])) ?> - 
                      <?= date('g:i A', strtotime($cell['actual_schedule']['time_out'])) ?>
                    <?php else: ?>
                      <i class="fas fa-question mr-1"></i>No Schedule
                    <?php endif; ?>
                  </div>

                  <!-- Attendance Time -->
                  <?php if ($timeStatus['has_log']): ?>
                    <div class="text-xs text-gray-500 mt-1">
                      <?php if ($timeStatus['time_in']): ?>
                        In: <?= date('g:i A', strtotime($timeStatus['time_in'])) ?>
                      <?php endif; ?>
                      <?php if ($timeStatus['time_out'] && $timeStatus['time_out'] !== 'INC'): ?>
                        <br>Out: <?= date('g:i A', strtotime($timeStatus['time_out'])) ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Click for details (future dates only) -->
                  <?php if ($cellDate >= date('Y-m-d')): ?>
                    <button onclick="openRequestModal('<?= $cellDate ?>')" 
                            class="absolute inset-0 w-full h-full opacity-0 hover:opacity-10 bg-blue-500 transition-opacity cursor-pointer"
                            title="Click to request schedule change">
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Status Legend -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold mb-4 text-gray-800">
            <i class="fas fa-info-circle mr-2 text-blue-600"></i>Status Legend
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-green-500"></div>
              <span>Present / On Time</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
              <span>Late</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-red-500"></div>
              <span>Absent / Incomplete</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-gray-500"></div>
              <span>Rest Day</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-purple-500"></div>
              <span>Holiday</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-4 h-4 rounded-full bg-cyan-500"></div>
              <span>Overtime Work</span>
            </div>
          </div>
        </div>

        <!-- Quick Request -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold mb-4 text-gray-800">
            <i class="fas fa-edit mr-2 text-blue-600"></i>Request Schedule Change
          </h3>
          <button onclick="openRequestModal()"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Request
          </button>
        </div>

        <!-- Pending Requests -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold mb-4 text-gray-800">
            <i class="fas fa-clock mr-2 text-yellow-600"></i>Pending Requests
          </h3>
          <?php if ($pendingRequests): ?>
            <div class="space-y-3">
              <?php foreach($pendingRequests as $request): ?>
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                  <div class="text-sm font-medium text-gray-800">
                    <?= date('M j, Y', strtotime($request['schedule_date'])) ?>
                  </div>
                  <div class="text-xs text-gray-600 mt-1">
                    <?= h($request['reason']) ?>
                  </div>
                  <div class="text-xs text-yellow-600 mt-1">
                    <i class="fas fa-hourglass-half mr-1"></i>Awaiting approval
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-sm text-gray-500">No pending requests</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Request Modal -->
  <div id="requestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Request Schedule Change</h3>
        <button onclick="closeRequestModal()"
                class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="post" class="space-y-4">
        <input type="hidden" name="action" value="request_override">
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" name="schedule_date" id="modal_schedule_date" required 
                 min="<?= date('Y-m-d') ?>"
                 class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
          <select name="override_type" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="schedule_change">Schedule Change</option>
            <option value="rest_day">Rest Day Request</option>
            <option value="overtime">Overtime Request</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">New Schedule (leave blank for rest day)</label>
          <select name="override_schedule_id" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Rest Day --</option>
            <?php foreach($workSchedules as $ws): ?>
              <option value="<?= $ws['id'] ?>">
                <?= h($ws['name'] ?? 'Schedule ' . $ws['id']) ?> 
                (<?= date('g:i A', strtotime($ws['time_in'])) ?> - <?= date('g:i A', strtotime($ws['time_out'])) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
          <textarea name="reason" rows="3" required placeholder="Please explain why you need this schedule change..."
                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="flex gap-3">
          <button type="button" onclick="closeRequestModal()"
                  class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Submit Request
          </button>
        </div>
      </form>
      </div>
    </div>
  </div>

  <script>
    // Current employee ID for AJAX requests
    const employeeId = <?= $employee_id ?>;
    
    function openRequestModal(date) {
      const modal = document.getElementById('requestModal');
      if (date) {
        document.getElementById('modal_schedule_date').value = date;
      }
      modal.classList.remove('hidden');
      modal.style.display = 'block';
    }

    function closeRequestModal() {
      const modal = document.getElementById('requestModal');
      modal.classList.add('hidden');
      modal.style.display = 'none';
    }

    // AJAX function to change calendar month without page refresh
    function changeMonth(ym) {
      // Show loading state
      const calendarGrid = document.getElementById('calendar-grid');
      const calendarTitle = document.getElementById('calendar-title');
      
      calendarGrid.innerHTML = '<div class="col-span-7 text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><br><span class="text-gray-500 mt-2">Loading...</span></div>';
      
      // Make AJAX request
      fetch(`employee_calendar_ajax.php?employee_id=${employeeId}&ym=${ym}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            calendarGrid.innerHTML = data.calendar_html;
            calendarTitle.textContent = data.month_title;
            
            // Update browser URL without refresh
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('ym', ym);
            window.history.pushState({}, '', newUrl);
          } else {
            throw new Error(data.error || 'Failed to load calendar');
          }
        })
        .catch(error => {
          console.error('Error loading calendar:', error);
          calendarGrid.innerHTML = '<div class="col-span-7 text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl"></i><br><span class="mt-2">Error loading calendar. Please refresh the page.</span></div>';
        });
    }

    // Close modal when clicking outside
    document.getElementById('requestModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeRequestModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeRequestModal();
      }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
      const urlParams = new URLSearchParams(window.location.search);
      const ym = urlParams.get('ym') || '<?= date('Y-n') ?>';
      changeMonth(ym);
    });
  </script>
</body>
</html>