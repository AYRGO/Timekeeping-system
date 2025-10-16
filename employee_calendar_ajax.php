<?php
// employee_calendar_ajax.php
// AJAX endpoint for calendar month changes

date_default_timezone_set('Asia/Manila');

// Use existing database connection
require_once __DIR__ . '/Public/config/db.php';

// Start session to get employee info
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$ym = isset($_GET['ym']) ? $_GET['ym'] : date('Y-n');

if (!$employee_id) {
    echo json_encode(['success' => false, 'error' => 'Employee ID required']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{1,2}$/', $ym)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

list($year, $month) = explode('-', $ym);
$month = (int)$month; 
$year = (int)$year;

// Validate date ranges
if ($month < 1 || $month > 12 || $year < 2020 || $year > 2030) {
    echo json_encode(['success' => false, 'error' => 'Invalid date range']);
    exit;
}

// ---------- Helper Functions (copied from main file) ----------
function h($s){ return htmlspecialchars($s,ENT_QUOTES); }

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

try {
    // Generate calendar matrix
    $matrix = monthMatrix($year, $month);
    
    // Generate calendar HTML
    ob_start();
    
    // Weekday Headers
    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
    foreach($weekdays as $wd): ?>
      <div class="text-center font-semibold py-3 text-gray-700 bg-gray-50 rounded">
        <?= substr($wd, 0, 3) ?>
      </div>
    <?php endforeach;

    // Calendar Days
    foreach($matrix as $week):
      foreach($week as $cellDate):
        if (!$cellDate): ?>
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
        <?php endif;
      endforeach;
    endforeach;
    
    $calendar_html = ob_get_clean();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'calendar_html' => $calendar_html,
        'month_title' => date('F Y', strtotime("$year-$month-01")),
        'year' => $year,
        'month' => $month
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>