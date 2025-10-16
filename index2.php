<?php
// index2.php
// Calendar-Based Scheduling System integrated with existing HRIS
// Requirements: PHP 7.4+ with PDO MySQL enabled.

date_default_timezone_set('Asia/Manila');

// Use existing database connection
require_once __DIR__ . '/Public/config/db.php';

// ---------- DB INIT & SEED ----------
// Check if calendar scheduling tables exist, if not create them
try {
    // Check if our calendar tables exist
    $result = $pdo->query("SHOW TABLES LIKE 'schedule_patterns'")->fetch();
    $init = !$result;
} catch (Exception $e) {
    $init = true;
}

if ($init) {
    $pdo->exec("
    -- Calendar scheduling tables compatible with existing MySQL structure

    CREATE TABLE IF NOT EXISTS schedule_patterns (
        id INT(11) NOT NULL AUTO_INCREMENT,
        pattern_name VARCHAR(255) NOT NULL,
        description TEXT,
        cycle_length INT(11) NOT NULL,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE IF NOT EXISTS schedule_pattern_details (
        id INT(11) NOT NULL AUTO_INCREMENT,
        pattern_id INT(11) NOT NULL,
        day_of_cycle INT(11) NOT NULL,
        work_schedule_id INT(11),
        is_rest_day BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (pattern_id) REFERENCES schedule_patterns(id) ON DELETE CASCADE,
        FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE IF NOT EXISTS employee_schedule_assignments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        employee_id INT(11) NOT NULL,
        schedule_pattern_id INT(11),
        work_schedule_id INT(11),
        assignment_type ENUM('pattern','fixed','custom') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE,
        cycle_start_date DATE,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (schedule_pattern_id) REFERENCES schedule_patterns(id) ON DELETE SET NULL,
        FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE IF NOT EXISTS calendar_schedule_overrides (
        id INT(11) NOT NULL AUTO_INCREMENT,
        employee_id INT(11) NOT NULL,
        schedule_date DATE NOT NULL,
        original_schedule_id INT(11),
        override_schedule_id INT(11),
        override_type ENUM('schedule_change','rest_day','overtime','holiday') NOT NULL,
        reason TEXT,
        is_approved BOOLEAN DEFAULT 0,
        approved_by INT(11),
        approved_at DATETIME,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (original_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL,
        FOREIGN KEY (override_schedule_id) REFERENCES work_schedules(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    CREATE TABLE IF NOT EXISTS holiday_calendar (
        id INT(11) NOT NULL AUTO_INCREMENT,
        holiday_name VARCHAR(255) NOT NULL,
        holiday_date DATE NOT NULL,
        holiday_type ENUM('regular','special','company') NOT NULL,
        is_recurring BOOLEAN DEFAULT 0,
        recurrence_pattern VARCHAR(255),
        applies_to_all BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    -- Indexes for performance
    CREATE INDEX idx_assign_emp_dates ON employee_schedule_assignments(employee_id, start_date, end_date);
    CREATE INDEX idx_overrides_emp_date ON calendar_schedule_overrides(employee_id, schedule_date);
    CREATE INDEX idx_pattern_details ON schedule_pattern_details(pattern_id, day_of_cycle);
    ");

    // Seed sample data if tables are empty
    $patternCount = $pdo->query("SELECT COUNT(*) FROM schedule_patterns")->fetchColumn();
    
    if ($patternCount == 0) {
        $now = date('Y-m-d H:i:s');
        
        // Create default 5-Day Work Pattern
        $pdo->prepare("INSERT INTO schedule_patterns (pattern_name, description, cycle_length) VALUES (?, ?, ?)")
            ->execute(['5-Day Week', 'Monday-Friday work, Saturday-Sunday rest', 7]);
        $pattern_id = $pdo->lastInsertId();

        // Get existing work schedule (use official_sched from employees or default)
        $defaultSchedule = $pdo->query("SELECT id FROM work_schedules ORDER BY id LIMIT 1")->fetchColumn();
        
        // Setup pattern details (1=Monday, 2=Tuesday, ..., 6=Saturday, 7=Sunday)
        for ($d = 1; $d <= 7; $d++) {
            $isRestDay = ($d == 6 || $d == 7) ? 1 : 0; // Weekend rest days
            $schedId = $isRestDay ? null : $defaultSchedule;
            $pdo->prepare("INSERT INTO schedule_pattern_details (pattern_id, day_of_cycle, work_schedule_id, is_rest_day) VALUES (?, ?, ?, ?)")
                ->execute([$pattern_id, $d, $schedId, $isRestDay]);
        }

        // Add a sample holiday
        $pdo->prepare("INSERT INTO holiday_calendar (holiday_name, holiday_date, holiday_type, is_recurring) VALUES (?, ?, ?, ?)")
            ->execute(['New Year Day', '2025-01-01', 'regular', 1]);
        
        echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>✅ Calendar scheduling tables created successfully!</div>";
    }

}

// ---------- Helpers ----------
function h($s){ return htmlspecialchars($s,ENT_QUOTES); }

function getEmployees($pdo){
    return $pdo->query("SELECT * FROM employees ORDER BY lname, fname")->fetchAll(PDO::FETCH_ASSOC);
}

function getMonthsNav($year, $month) {
    $prev = date('Y-n', strtotime("$year-$month-01 -1 month"));
    $next = date('Y-n', strtotime("$year-$month-01 +1 month"));
    return ['prev'=>$prev, 'next'=>$next];
}

// Determine base schedule for a given employee & date
function getBaseScheduleForDate($pdo, $employee_id, $date) {
    // Find active assignment
    $row = $pdo->prepare("
      SELECT * FROM employee_schedule_assignments
      WHERE employee_id = ? 
        AND (end_date IS NULL OR end_date >= ?)
        AND date(start_date) <= date(?)
      ORDER BY start_date DESC
      LIMIT 1
    ");
    $row->execute([$employee_id, $date, $date]);
    $assign = $row->fetch(PDO::FETCH_ASSOC);
    if (!$assign) return null;

    if ($assign['assignment_type'] === 'fixed' && $assign['work_schedule_id']) {
        return $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?")->execute([$assign['work_schedule_id']]) ? null : null;
    }

    if ($assign['assignment_type'] === 'pattern' && $assign['schedule_pattern_id']) {
        // find pattern details: figure day_of_cycle
        $pattern = $pdo->prepare("SELECT * FROM schedule_patterns WHERE id = ? AND is_active = 1");
        $pattern->execute([$assign['schedule_pattern_id']]);
        $pat = $pattern->fetch(PDO::FETCH_ASSOC);
        if (!$pat) return null;
        $cycle_len = max(1,(int)$pat['cycle_length']);
        $cycle_start = $assign['cycle_start_date'] ? $assign['cycle_start_date'] : $assign['start_date'];
        $start_ts = strtotime($cycle_start);
        $d_ts = strtotime($date);
        $diff_days = (int) floor(($d_ts - $start_ts) / 86400);
        // day_of_cycle 1..cycle_length
        $day_of_cycle = ($diff_days % $cycle_len) + 1;
        $detail = $pdo->prepare("SELECT spd.*, ws.* FROM schedule_pattern_details spd
                    LEFT JOIN work_schedules ws ON ws.id = spd.work_schedule_id
                    WHERE spd.pattern_id = ? AND spd.day_of_cycle = ? LIMIT 1");
        $detail->execute([$pat['id'], $day_of_cycle]);
        $d = $detail->fetch(PDO::FETCH_ASSOC);
        if (!$d) return ['is_rest_day'=>1];
        return $d;
    }

    if ($assign['assignment_type'] === 'custom') {
        // rely on overrides or custom entries (not implemented fully here)
        return null;
    }

    return null;
}

// Function to integrate with time logging - check actual attendance vs scheduled
function getTimeLogStatus($pdo, $employee_id, $date) {
    // Get time log for this date
    $timeLog = $pdo->prepare("
        SELECT tl.*, e.fname, e.lname, e.official_sched 
        FROM time_logs tl 
        JOIN employees e ON tl.employee_id = e.id 
        WHERE tl.employee_id = ? AND tl.log_date = ?
    ");
    $timeLog->execute([$employee_id, $date]);
    $log = $timeLog->fetch(PDO::FETCH_ASSOC);
    
    // Get scheduled information from calendar system
    $scheduleInfo = getScheduleCell($pdo, $employee_id, $date);
    
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
        
        // Check against schedule expectations
        if ($scheduleInfo['is_rest_day']) {
            $status['status'] = 'Rest Day Work'; // Working on scheduled rest day
        } elseif ($scheduleInfo['is_holiday']) {
            $status['status'] = 'Holiday Work'; // Working on holiday
        } elseif ($log['status'] === 'incomplete' || !$log['time_out'] || $log['time_out'] === 'INC') {
            $status['status'] = 'Incomplete';
        } else {
            // Check for late/early based on calendar schedule
            $scheduledIn = $scheduleInfo['schedule']['time_in'] ?? '08:00:00';
            $actualIn = $log['time_in'];
            
            if ($actualIn > date('H:i:s', strtotime($scheduledIn . ' +15 minutes'))) {
                $status['status'] = 'Late';
            } else {
                $status['status'] = 'Present';
            }
        }
    } else {
        // No time log
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

// Better version: returns work schedule row or rest info; also fetch override and holiday
function getScheduleCell($pdo, $employee_id, $date) {
    $cell = ['date'=>$date, 'employee_id'=>$employee_id, 'base_schedule'=>null, 'actual_schedule'=>null, 'is_rest_day'=>0, 'is_holiday'=>0, 'has_override'=>0, 'override_status'=>null, 'schedule_color'=>'#9ca3af'];

    // base schedule
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
            $pat = $pdo->prepare("SELECT * FROM schedule_patterns WHERE id = ?");
            $pat->execute([$assign['schedule_pattern_id']]);
            $pat = $pat->fetch(PDO::FETCH_ASSOC);
            if ($pat) {
                $cycle_len = max(1,(int)$pat['cycle_length']);
                $cycle_start = $assign['cycle_start_date'] ? $assign['cycle_start_date'] : $assign['start_date'];
                $start_ts = strtotime($cycle_start);
                $d_ts = strtotime($date);
                $diff_days = (int) floor(($d_ts - $start_ts) / 86400);
                $day_of_cycle = ($diff_days % $cycle_len) + 1;
                $detail = $pdo->prepare("SELECT spd.*, ws.* FROM schedule_pattern_details spd LEFT JOIN work_schedules ws ON ws.id = spd.work_schedule_id WHERE spd.pattern_id = ? AND spd.day_of_cycle = ? LIMIT 1");
                $detail->execute([$pat['id'],$day_of_cycle]);
                $d = $detail->fetch(PDO::FETCH_ASSOC);
                if ($d) {
                    if ($d['is_rest_day']) { $cell['is_rest_day'] = 1; }
                    $cell['base_schedule'] = $d;
                } else {
                    $cell['is_rest_day'] = 1;
                }
            }
        }
    }

    // holiday check (including recurring)
    $hstmt = $pdo->prepare("SELECT * FROM holiday_calendar WHERE DATE(holiday_date) = DATE(?) OR (is_recurring=1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(?, '%m-%d')) LIMIT 1");
    $hstmt->execute([$date, $date]);
    $holiday = $hstmt->fetch(PDO::FETCH_ASSOC);
    if ($holiday) {
        $cell['is_holiday'] = 1;
        $cell['holiday'] = $holiday;
    }

    // override check
    $ov = $pdo->prepare("SELECT * FROM calendar_schedule_overrides WHERE employee_id = ? AND date(schedule_date) = date(?) ORDER BY created_at DESC LIMIT 1");
    $ov->execute([$employee_id, $date]);
    $override = $ov->fetch(PDO::FETCH_ASSOC);
    if ($override) {
        $cell['has_override'] = 1;
        $cell['override_status'] = $override['is_approved'] ? 'approved' : 'pending';
        // apply override if approved or even show pending as actual (choose behavior)
        if ($override['override_schedule_id']) {
            $ws = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
            $ws->execute([$override['override_schedule_id']]);
            $cell['actual_schedule'] = $ws->fetch(PDO::FETCH_ASSOC);
            $cell['schedule_color'] = $cell['actual_schedule']['color_code'] ?? $cell['schedule_color'];
        } else {
            // rest day override
            $cell['actual_schedule'] = null;
            $cell['is_rest_day'] = 1;
            $cell['schedule_color'] = '#ef4444';
        }
    } else {
        // no override => actual = base
        if (!empty($cell['base_schedule'])) {
            $cell['actual_schedule'] = $cell['base_schedule'];
            $cell['schedule_color'] = $cell['base_schedule']['color_code'] ?? $cell['schedule_color'];
        } else {
            if ($cell['is_rest_day']) $cell['schedule_color'] = '#f3f4f6';
        }
    }

    return $cell;
}

// Handle form submissions: add override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_override') {
        $employee_id = (int)$_POST['employee_id'];
        $schedule_date = $_POST['schedule_date'];
        $override_schedule_id = $_POST['override_schedule_id'] ? (int)$_POST['override_schedule_id'] : null;
        $reason = $_POST['reason'] ?? '';
        $override_type = $_POST['override_type'] ?? 'schedule_change';
        $stmt = $pdo->prepare("INSERT INTO calendar_schedule_overrides (employee_id,schedule_date,original_schedule_id,override_schedule_id,override_type,reason,is_approved,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        // naive original_schedule_id lookup
        $origCell = getScheduleCell($pdo, $employee_id, $schedule_date);
        $origId = $origCell['base_schedule']['id'] ?? null;
        $stmt->execute([$employee_id,$schedule_date,$origId,$override_schedule_id,$override_type,$reason,0,'webuser',date('Y-m-d H:i:s')]);
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    } elseif ($_POST['action'] === 'approve_override' && !empty($_POST['override_id'])) {
        $id = (int)$_POST['override_id'];
        $pdo->prepare("UPDATE calendar_schedule_overrides SET is_approved=1, approved_by=?, approved_at=? WHERE id=?")
            ->execute(['supervisor', date('Y-m-d H:i:s'), $id]);
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

// ---------- UI & Routing ----------
$employees = getEmployees($pdo);
$emp_id = isset($_GET['employee']) ? (int)$_GET['employee'] : ($employees[0]['id'] ?? 0);
$ym = isset($_GET['ym']) ? $_GET['ym'] : date('Y-n');
list($year, $month) = explode('-', $ym);
$month = (int)$month; $year = (int)$year;
$nav = getMonthsNav($year,$month);

function monthMatrix($year,$month){
    $first = strtotime("$year-$month-01");
    $start_w = date('w',$first); // 0 Sun..6 Sat
    $days = date('t',$first);
    // build weeks as arrays of dates (or null)
    $matrix = [];
    $week = array_fill(0,7,null);
    $day = 1;
    // place first day
    $wday = $start_w;
    while ($day <= $days) {
        $week[$wday] = date('Y-m-d', strtotime("$year-$month-$day"));
        $wday++;
        if ($wday === 7) {
            $matrix[] = $week;
            $week = array_fill(0,7,null);
            $wday = 0;
        }
        $day++;
    }
    if (array_filter($week)) $matrix[] = $week;
    return $matrix;
}

$matrix = monthMatrix($year,$month);

// small helper to fetch all work schedules for forms
$workSchedules = $pdo->query("SELECT * FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// fetch pending overrides list (for supervisor review)
$pendingOverrides = $pdo->query("SELECT o.*, e.fname, e.lname FROM calendar_schedule_overrides o JOIN employees e ON e.id=o.employee_id WHERE is_approved=0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// ---------- Render ----------
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>HRIS - Scheduling Calendar</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6">
  <div class="max-w-6xl mx-auto">
    <header class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">HRIS — Calendar Scheduling</h1>
        <p class="text-sm text-gray-600">Prototype: patterns, fixed schedules, overrides & holidays</p>
      </div>
      <div class="text-right">
        <form method="get" class="flex items-center gap-2">
          <label class="text-sm text-gray-700">Employee</label>
          <select name="employee" onchange="this.form.submit()" class="p-2 border rounded">
            <?php foreach($employees as $e): ?>
              <option value="<?= $e['id']?>" <?= $e['id']==$emp_id ? 'selected':''; ?>><?= h($e['fname'].' '.$e['lname']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </header>

    <main class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Calendar -->
      <section class="md:col-span-2 bg-white rounded shadow p-4">
        <div class="flex items-center justify-between mb-4">
          <div>
            <a href="?employee=<?= $emp_id ?>&ym=<?= h($nav['prev']) ?>" class="px-3 py-1 rounded hover:bg-gray-100">‹ Prev</a>
            <span class="mx-3 font-medium"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
            <a href="?employee=<?= $emp_id ?>&ym=<?= h($nav['next']) ?>" class="px-3 py-1 rounded hover:bg-gray-100">Next ›</a>
          </div>
          <div>
            <a href="?employee=<?= $emp_id ?>&ym=<?= date('Y-n') ?>" class="text-sm text-blue-600">Today</a>
          </div>
        </div>

        <div class="grid grid-cols-7 gap-1 text-sm">
          <?php $weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; foreach($weekdays as $wd): ?>
            <div class="text-center font-semibold py-2"><?= $wd ?></div>
          <?php endforeach; ?>

          <?php foreach($matrix as $week): ?>
            <?php foreach($week as $cellDate): ?>
              <?php if (!$cellDate): ?>
                <div class="p-2 border h-28 bg-gray-50"></div>
              <?php else: 
                    $cell = getScheduleCell($pdo,$emp_id,$cellDate);
                    $timeStatus = getTimeLogStatus($pdo,$emp_id,$cellDate);
                    $isToday = $cellDate === date('Y-m-d');
                    $color = $cell['schedule_color'] ?? '#9ca3af';
                    
                    // Status-based colors
                    $statusColors = [
                        'Present' => '#10b981', 'Late' => '#f59e0b', 'Incomplete' => '#ef4444',
                        'Absent' => '#ef4444', 'Rest Day' => '#6b7280', 'Holiday' => '#8b5cf6',
                        'Rest Day Work' => '#06b6d4', 'Holiday Work' => '#06b6d4'
                    ];
                    $statusColor = $statusColors[$timeStatus['status']] ?? $color;
              ?>
                <div class="p-2 border h-32 flex flex-col justify-between"
                     style="background:linear-gradient(120deg,<?= h($statusColor) ?>22,transparent); border-left: 4px solid <?= h($statusColor) ?>;">
                  <div class="flex justify-between items-start">
                    <div class="<?= $isToday ? 'font-bold text-blue-600' : 'text-gray-700' ?>"><?= date('j', strtotime($cellDate)) ?></div>
                    <?php if ($cell['is_holiday']): ?>
                      <div class="text-xs px-1 py-0.5 bg-red-100 text-red-700 rounded">Holiday</div>
                    <?php elseif ($cell['has_override']): ?>
                      <div class="text-xs px-1 py-0.5 bg-yellow-100 text-yellow-800 rounded"><?= h($cell['override_status']) ?></div>
                    <?php endif; ?>
                  </div>

                  <!-- Attendance Status -->
                  <div class="text-xs mb-1">
                    <div class="font-medium text-center px-1 py-0.5 rounded" style="background-color: <?= h($statusColor) ?>22; color: <?= h($statusColor) ?>;">
                      <?= h($timeStatus['status']) ?>
                    </div>
                  </div>

                  <!-- Schedule Info -->
                  <div class="text-xs">
                    <?php if ($cell['is_rest_day'] && !$cell['actual_schedule']): ?>
                      <div class="text-gray-500 italic text-center">Rest Day</div>
                    <?php elseif ($cell['actual_schedule']): ?>
                      <div class="text-center text-gray-600">
                        <?= isset($cell['actual_schedule']['time_in']) ? date('g:i A', strtotime($cell['actual_schedule']['time_in'])) . '-' . date('g:i A', strtotime($cell['actual_schedule']['time_out'])) : 'Schedule TBD' ?>
                      </div>
                    <?php else: ?>
                      <div class="text-gray-400 text-center">No Schedule</div>
                    <?php endif; ?>
                  </div>

                  <!-- Time Log Info -->
                  <?php if ($timeStatus['has_log']): ?>
                    <div class="text-xs text-gray-600 text-center">
                      <?php if ($timeStatus['time_in']): ?>
                        In: <?= date('g:i A', strtotime($timeStatus['time_in'])) ?>
                      <?php endif; ?>
                      <?php if ($timeStatus['time_out'] && $timeStatus['time_out'] !== 'INC'): ?>
                        | Out: <?= date('g:i A', strtotime($timeStatus['time_out'])) ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="flex gap-1 mt-2">
                    <button onclick="openOverride('<?= $cellDate ?>')" class="text-xs px-2 py-1 border rounded bg-white">Override</button>
                    <?php if ($cell['has_override']): ?>
                      <a href="#" class="text-xs px-2 py-1 border rounded bg-white">View</a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Sidebar: details & forms -->
      <aside class="bg-white rounded shadow p-4">
        <!-- Status Legend -->
        <div class="mb-6">
          <h3 class="font-semibold text-gray-800 mb-3">Status Legend</h3>
          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #10b981;"></div>
              <span>Present - On time</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #f59e0b;"></div>
              <span>Late - Arrived after grace period</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #ef4444;"></div>
              <span>Absent/Incomplete</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #6b7280;"></div>
              <span>Rest Day</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #8b5cf6;"></div>
              <span>Holiday</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 rounded" style="background-color: #06b6d4;"></div>
              <span>Overtime (Rest/Holiday Work)</span>
            </div>
          </div>
        </div>
        <h2 class="font-semibold mb-2">Employee Details</h2>
        <?php
          $emp = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
          $emp->execute([$emp_id]);
          $emp = $emp->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="text-sm text-gray-700 mb-4">
          <div><strong><?= h($emp['fname'].' '.$emp['lname']) ?></strong></div>
          <?php if (!empty($emp['company'])): ?><div class="text-xs">Company: <?= h($emp['company']) ?></div><?php endif; ?>
          <?php if (!empty($emp['position'])): ?><div class="text-xs">Position: <?= h($emp['position']) ?></div><?php endif; ?>
          <?php if (!empty($emp['official_sched'])): ?>
            <div class="text-xs">Default Schedule: <?= h($emp['official_sched']) ?></div>
          <?php endif; ?>
          <div class="text-xs">Status: <?= h($emp['status']) ?></div>
        </div>

        <h3 class="font-medium mb-2">Create Override</h3>
        <form method="post" class="space-y-2">
          <input type="hidden" name="action" value="add_override">
          <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
          <div>
            <label class="text-xs block text-gray-600">Date</label>
            <input type="date" name="schedule_date" required class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="text-xs block text-gray-600">Override To (leave blank for rest)</label>
            <select name="override_schedule_id" class="w-full p-2 border rounded">
              <option value="">-- Rest Day --</option>
              <?php foreach($workSchedules as $ws): ?>
                <option value="<?= $ws['id'] ?>"><?= h($ws['schedule_name'] . " ({$ws['start_time']} - {$ws['end_time']})") ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs block text-gray-600">Type</label>
            <select name="override_type" class="w-full p-2 border rounded">
              <option value="schedule_change">Schedule Change</option>
              <option value="rest_day">Rest Day</option>
              <option value="overtime">Overtime</option>
              <option value="holiday">Holiday</option>
            </select>
          </div>
          <div>
            <label class="text-xs block text-gray-600">Reason</label>
            <input name="reason" class="w-full p-2 border rounded" placeholder="Reason (optional)">
          </div>
          <div>
            <button class="px-3 py-2 bg-blue-600 text-white rounded">Create Override (pending approval)</button>
          </div>
        </form>

        <hr class="my-4">

        <h3 class="font-medium mb-2">Pending Overrides (Supervisor)</h3>
        <?php if ($pendingOverrides): ?>
          <div class="space-y-2 text-sm">
            <?php foreach($pendingOverrides as $o): ?>
              <div class="border p-2 rounded bg-gray-50">
                <div><strong><?= h($o['fname'].' '.$o['lname']) ?></strong> — <?= h($o['schedule_date']) ?></div>
                <div class="text-xs text-gray-600"><?= h($o['reason']) ?></div>
                <form method="post" class="mt-2">
                  <input type="hidden" name="action" value="approve_override">
                  <input type="hidden" name="override_id" value="<?= $o['id'] ?>">
                  <button class="text-xs px-2 py-1 bg-green-600 text-white rounded">Approve</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-xs text-gray-500">No pending overrides</div>
        <?php endif; ?>

        <hr class="my-4">

        <div class="text-xs text-gray-500">Prototype DB file: <code>hris.sqlite</code></div>
      </aside>
    </main>

  </div>

  <script>
    function openOverride(date) {
      // simplistic: scroll to form and set date
      const input = document.querySelector('input[name="schedule_date"]');
      if (input) {
        input.value = date;
        input.scrollIntoView({behavior:'smooth', block:'center'});
        input.focus();
      }
    }
  </script>
</body>
</html>
