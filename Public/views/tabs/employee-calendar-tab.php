<?php
// Calendar Scheduling System - Separated Module
// This file contains the complete calendar scheduling interface for employee schedule management

// Calendar Helper Functions
function getMonthsNav_schedule($year, $month) {
    $prev = date('Y-n', strtotime("$year-$month-01 -1 month"));
    $next = date('Y-n', strtotime("$year-$month-01 +1 month"));
    return ['prev'=>$prev, 'next'=>$next];
}

function getScheduleCell_admin($pdo, $employee_id, $date, $scheduleOptions) {
    // Query the pre-computed cache table - SIMPLE & FAST!
    $stmt = $pdo->prepare("
        SELECT 
            schedule_date,
            employee_id,
            work_schedule_id,
            is_rest_day,
            is_holiday,
            schedule_name,
            time_in,
            time_out,
            holiday_name,
            source,
            source_id
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $cache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If found in cache, return formatted data
    if ($cache) {
        $cell = [
            'date' => $date,
            'employee_id' => $employee_id,
            'actual_schedule' => null,
            'is_rest_day' => $cache['is_rest_day'],
            'is_holiday' => $cache['is_holiday'],
            'source' => $cache['source'],
            'schedule_color' => getScheduleColor_admin($cache['source'], $cache['is_rest_day'], $cache['is_holiday']),
            'override_data' => ($cache['source'] === 'admin_override' || $cache['source'] === 'approved_request') ? ['id' => $cache['source_id']] : null
        ];
        
        // Add schedule details if not a rest day
        if ($cache['work_schedule_id']) {
            $cell['actual_schedule'] = [
                'id' => $cache['work_schedule_id'],
                'name' => $cache['schedule_name'],
                'time_in' => $cache['time_in'],
                'time_out' => $cache['time_out']
            ];
        }
        
        // Add holiday details if holiday
        if ($cache['is_holiday']) {
            $cell['holiday'] = ['holiday_name' => $cache['holiday_name']];
        }
        
        return $cell;
    }
    
    // Fallback for past/present/future dates: Check employee_default_schedules
    $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday
    $weeklyStmt = $pdo->prepare("
        SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
        FROM employee_default_schedules edd
        LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
        WHERE edd.employee_id = ? 
          AND edd.day_of_week = ? 
          AND edd.effective_from <= ? 
          AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
        LIMIT 1
    ");
    $weeklyStmt->execute([$employee_id, $dayOfWeek, $date, $date]);
    $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weekly) {
        if ($weekly['is_rest_day']) {
            return [
                'date' => $date,
                'employee_id' => $employee_id,
                'actual_schedule' => null,
                'is_rest_day' => 1,
                'is_holiday' => 0,
                'source' => 'weekly_default',
                'schedule_color' => '#f3f4f6',
                'override_data' => null
            ];
        } elseif ($weekly['work_schedule_id']) {
            return [
                'date' => $date,
                'employee_id' => $employee_id,
                'actual_schedule' => [
                    'id' => $weekly['work_schedule_id'],
                    'name' => $weekly['name'],
                    'time_in' => $weekly['time_in'],
                    'time_out' => $weekly['time_out']
                ],
                'is_rest_day' => 0,
                'is_holiday' => 0,
                'source' => 'weekly_default',
                'schedule_color' => '#3b82f6',
                'override_data' => null
            ];
        }
    }
    
    // Final fallback: Check if weekend
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return [
            'date' => $date,
            'employee_id' => $employee_id,
            'actual_schedule' => null,
            'is_rest_day' => 1,
            'is_holiday' => 0,
            'source' => 'weekend',
            'schedule_color' => '#f3f4f6',
            'override_data' => null
        ];
    }
    
    // Absolute fallback: return empty/rest day
    return [
        'date' => $date,
        'employee_id' => $employee_id,
        'actual_schedule' => null,
        'is_rest_day' => 1,
        'is_holiday' => 0,
        'source' => 'none',
        'schedule_color' => '#f3f4f6',
        'override_data' => null
    ];
}

// Helper function to get color based on source
function getScheduleColor_admin($source, $is_rest_day, $is_holiday) {
    if ($is_holiday) {
        return '#fbbf24'; // Yellow for holidays
    }
    
    switch ($source) {
        case 'approved_request':
            return '#8b5cf6'; // Purple for approved change requests
        case 'admin_override':
            return '#10b981'; // Green for admin overrides
        case 'weekly_default':
            return $is_rest_day ? '#f3f4f6' : '#3b82f6'; // Light gray or blue
        case 'weekend':
            return '#f3f4f6'; // Light gray
        default:
            return '#9ca3af'; // Gray for no data
    }
}

function getTimeLogStatus_admin($pdo, $employee_id, $date, $scheduleOptions) {
    $timeLog = $pdo->prepare("
        SELECT tl.*, e.fname, e.lname, e.official_sched 
        FROM time_logs tl 
        JOIN employees e ON tl.employee_id = e.id 
        WHERE tl.employee_id = ? AND tl.log_date = ?
    ");
    $timeLog->execute([$employee_id, $date]);
    $log = $timeLog->fetch(PDO::FETCH_ASSOC);
    
    $scheduleInfo = getScheduleCell_admin($pdo, $employee_id, $date, $scheduleOptions);
    
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
            $status['status'] = 'Complete';
        }
    } else {
        if ($scheduleInfo['is_rest_day']) {
            $status['status'] = 'Rest Day';
        } elseif ($scheduleInfo['is_holiday']) {
            $status['status'] = 'Holiday';
        } else {
            $status['status'] = 'No Log';
        }
    }
    
    return $status;
}

function monthMatrix_admin($year,$month){
    $first = strtotime("$year-$month-01");
    $start_w = date('w',$first);
    $days = date('t',$first);
    $matrix = [];
    $week = array_fill(0,7,null);
    $day = 1;
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_override') {
        $schedule_employee_id = (int)$_POST['employee_id'];
        $schedule_date = $_POST['schedule_date'];
        
        // Prevent overrides for past dates
        if ($schedule_date < date('Y-m-d')) {
            echo "<script>alert('Cannot create override for past dates. Please select today or a future date.'); window.location.href='employee-edit.php?id={$employeeId}#current-schedule';</script>";
            exit;
        }
        
        $override_schedule_input = $_POST['override_schedule_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $override_type = $_POST['override_type'] ?? 'schedule_change';
        
        // Handle "OFF" option - set override_schedule_id to null for rest day
        if ($override_schedule_input === 'OFF' || $override_schedule_input === '') {
            $actual_schedule_id = null;
            $is_rest_day = 1;
        } else {
            $actual_schedule_id = (int)$override_schedule_input;
            $is_rest_day = 0;
        }
        
        // Use employee_daily_schedules table (advanced calendar system)
        // First delete any existing override for this date
        $pdo->prepare("DELETE FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ?")
            ->execute([$schedule_employee_id, $schedule_date]);
        
        // Insert new daily override
        $stmt = $pdo->prepare("INSERT INTO employee_daily_schedules (employee_id, schedule_date, actual_schedule_id, is_rest_day, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$schedule_employee_id, $schedule_date, $actual_schedule_id, $is_rest_day, $reason]);
        
        // Add to audit trail (schedule_override_history uses: employee_id, schedule_date, original_schedule_id, new_schedule_id, override_reason, applied_by, applied_at)
        $pdo->prepare("INSERT INTO schedule_override_history (employee_id, schedule_date, new_schedule_id, override_reason, applied_by, applied_at) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([$schedule_employee_id, $schedule_date, $actual_schedule_id, $reason, $_SESSION['user_id'] ?? null]);
        
        echo "<script>alert('Schedule override created successfully!'); window.location.href='employee-edit.php?id={$employeeId}#current-schedule';</script>";
        exit;
    }
    
    if ($_POST['action'] === 'delete_override') {
        $schedule_employee_id = (int)$_POST['employee_id'];
        $schedule_date = $_POST['schedule_date'];
        
        // Prevent deletion of past overrides
        if ($schedule_date < date('Y-m-d')) {
            echo "<script>alert('Cannot delete override for past dates.'); window.location.href='employee-edit.php?id={$employeeId}#current-schedule';</script>";
            exit;
        }
        
        // Delete the override from employee_daily_schedules
        $stmt = $pdo->prepare("DELETE FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ?");
        $stmt->execute([$schedule_employee_id, $schedule_date]);
        
        // Add to audit trail
        $pdo->prepare("INSERT INTO schedule_override_history (employee_id, schedule_date, new_schedule_id, override_reason, applied_by, applied_at) VALUES (?, ?, NULL, 'Override cancelled by admin', ?, NOW())")
            ->execute([$schedule_employee_id, $schedule_date, $_SESSION['user_id'] ?? null]);
        
        echo "<script>alert('Schedule override cancelled successfully!'); window.location.href='employee-edit.php?id={$employeeId}#current-schedule';</script>";
        exit;
    }
}

// UI Setup
$emp_id = $employeeId; // Use current employee ID from context
$ym = isset($_GET['ym']) ? $_GET['ym'] : date('Y-n');
list($year, $month) = explode('-', $ym);
$month = (int)$month; 
$year = (int)$year;
$nav = getMonthsNav_schedule($year,$month);
$matrix = monthMatrix_admin($year,$month);

// Fetch work schedules and daily overrides for employee
$workSchedules_cal = $pdo->query("SELECT * FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$pendingOverrides = $pdo->prepare("SELECT eds.*, ws.name as schedule_name, ws.time_in, ws.time_out 
    FROM employee_daily_schedules eds 
    LEFT JOIN work_schedules ws ON eds.actual_schedule_id = ws.id 
    WHERE eds.employee_id = ? AND eds.schedule_date >= ? 
    ORDER BY eds.schedule_date ASC LIMIT 10");
$pendingOverrides->execute([$emp_id, date('Y-m-d')]);
$pendingOverrides = $pendingOverrides->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Current Schedule Tab Content -->
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Employee Schedule Calendar</h2>
            <p class="text-sm text-gray-500">View and manage <?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?>'s schedule</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">Current Month</p>
            <p class="font-semibold text-lg"><?= date('F Y', strtotime("$year-$month-01")) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Calendar -->
        <div class="lg:col-span-2 bg-white rounded shadow p-4">
            <!-- Month Navigation -->
            <div class="flex justify-between items-center mb-4">
                <a href="?id=<?= $employeeId ?>&ym=<?= $nav['prev'] ?>#current-schedule" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded transition">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <h3 class="text-lg font-bold"><?= date('F Y', strtotime("$year-$month-01")) ?></h3>
                <a href="?id=<?= $employeeId ?>&ym=<?= $nav['next'] ?>#current-schedule" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded transition">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <!-- Legend -->
            <div class="mb-4 p-3 bg-gray-50 rounded text-xs">
                <strong>Legend:</strong>
                <span class="inline-block ml-3"><span class="inline-block w-3 h-3 bg-purple-500 rounded mr-1"></span>Approved Request</span>
                <span class="inline-block ml-3"><span class="inline-block w-3 h-3 bg-green-500 rounded mr-1"></span>Daily Override</span>
                <span class="inline-block ml-3"><span class="inline-block w-3 h-3 bg-blue-500 rounded mr-1"></span>Weekly Default</span>
                <span class="inline-block ml-3"><span class="inline-block w-3 h-3 bg-yellow-500 rounded mr-1"></span>Holiday</span>
                <span class="inline-block ml-3"><span class="inline-block w-3 h-3 bg-red-500 rounded mr-1"></span>OFF/Rest</span>
            </div>

            <!-- Calendar Grid -->
            <div class="border rounded overflow-hidden">
                <div class="grid grid-cols-7 bg-gray-100 text-center font-semibold text-sm">
                    <div class="p-2 border">Sun</div>
                    <div class="p-2 border">Mon</div>
                    <div class="p-2 border">Tue</div>
                    <div class="p-2 border">Wed</div>
                    <div class="p-2 border">Thu</div>
                    <div class="p-2 border">Fri</div>
                    <div class="p-2 border">Sat</div>
                </div>

                <?php foreach($matrix as $week): ?>
                <div class="grid grid-cols-7">
                    <?php foreach($week as $date): ?>
                        <?php if ($date): ?>
                            <?php 
                            $cell = getScheduleCell_admin($pdo, $emp_id, $date, $scheduleOptions);
                            $dayNum = date('j', strtotime($date));
                            $isToday = ($date === date('Y-m-d'));
                            $isPast = ($date < date('Y-m-d'));
                            ?>
                            <div class="border p-2 min-h-[80px] <?= $isPast ? 'cursor-not-allowed opacity-75' : 'cursor-pointer hover:bg-gray-50' ?> transition relative" 
                                 <?= $isPast ? '' : "onclick=\"openOverride_admin('$date')\"" ?>
                                 style="background-color: <?= $cell['schedule_color'] ?>22;">
                                
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-semibold <?= $isToday ? 'bg-blue-600 text-white px-2 rounded-full' : '' ?>">
                                        <?= $dayNum ?>
                                    </span>
                                    <?php if ($cell['source'] !== 'none' && $cell['source'] !== 'weekend'): ?>
                                        <span class="text-xs px-1 rounded" style="background-color: <?= $cell['schedule_color'] ?>; color: white;">
                                            <?php
                                            switch($cell['source']) {
                                                case 'approved_change_request': echo 'A'; break;
                                                case 'daily_override': echo 'D'; break;
                                                case 'rotating_schedule': echo 'R'; break;
                                                case 'weekly_default': echo 'W'; break;
                                                case 'holiday': echo 'H'; break;
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="text-xs">
                                    <?php if ($cell['is_holiday']): ?>
                                        <div class="font-semibold text-yellow-700"><?= htmlspecialchars(substr($cell['holiday']['holiday_name'], 0, 15)) ?></div>
                                    <?php elseif ($cell['is_rest_day']): ?>
                                        <div class="text-gray-500 font-medium">OFF</div>
                                    <?php elseif ($cell['actual_schedule']): ?>
                                        <div class="font-medium"><?= htmlspecialchars($cell['actual_schedule']['name']) ?></div>
                                        <div class="text-gray-600">
                                            <?= date('g:iA', strtotime($cell['actual_schedule']['time_in'])) ?> - 
                                            <?= date('g:iA', strtotime($cell['actual_schedule']['time_out'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-gray-400">No schedule</div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($cell['override_data']): ?>
                                    <div class="absolute top-1 right-1">
                                        <i class="fas fa-edit text-green-600 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="border p-2 bg-gray-50"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="bg-gray-50 rounded shadow p-4">
            <!-- Add Override Form -->
            <div class="mb-6">
                <h3 class="font-semibold mb-3 flex items-center">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Add Schedule Override
                </h3>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="action" value="add_override">
                    <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Date</label>
                        <input type="date" id="override_date" name="schedule_date" required 
                               class="w-full text-sm p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Schedule</label>
                        <select name="override_schedule_id" class="w-full text-sm p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="OFF">-- OFF / Rest Day --</option>
                            <?php foreach($workSchedules_cal as $ws): ?>
                                <option value="<?= $ws['id'] ?>">
                                    <?= htmlspecialchars($ws['name']) ?> 
                                    (<?= date('g:i A', strtotime($ws['time_in'])) ?> - <?= date('g:i A', strtotime($ws['time_out'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium mb-1">Reason / Notes</label>
                        <textarea name="reason" rows="2" 
                                  class="w-full text-sm p-2 border rounded focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Optional notes..."></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded transition text-sm">
                        <i class="fas fa-save mr-2"></i>Add Override
                    </button>
                </form>
            </div>

            <!-- Upcoming Overrides -->
            <div>
                <h3 class="font-semibold mb-3 flex items-center">
                    <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
                    Upcoming Overrides
                </h3>
                
                <?php if ($pendingOverrides): ?>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php foreach($pendingOverrides as $po): ?>
                            <div class="bg-white border rounded p-2 text-xs">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-semibold"><?= date('M d, Y', strtotime($po['schedule_date'])) ?></span>
                                    <div class="flex items-center gap-1">
                                        <?php if ($po['is_rest_day']): ?>
                                            <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded">OFF</span>
                                        <?php else: ?>
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">
                                                <?= htmlspecialchars($po['schedule_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <button onclick="deleteOverride('<?= $po['schedule_date'] ?>', '<?= date('M d, Y', strtotime($po['schedule_date'])) ?>')" 
                                                class="text-red-600 hover:text-red-800 ml-1" 
                                                title="Cancel Override">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if (!$po['is_rest_day'] && $po['time_in']): ?>
                                    <div class="text-gray-600">
                                        <?= date('g:i A', strtotime($po['time_in'])) ?> - <?= date('g:i A', strtotime($po['time_out'])) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($po['notes']): ?>
                                    <div class="text-gray-500 mt-1 italic"><?= htmlspecialchars(substr($po['notes'], 0, 50)) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-xs text-gray-500 text-center py-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        No upcoming overrides
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openOverride_admin(date) {
    const input = document.getElementById('override_date');
    if (input) {
        input.value = date;
        input.scrollIntoView({behavior:'smooth', block:'center'});
        input.focus();
    }
}

function deleteOverride(date, displayDate) {
    if (confirm('Are you sure you want to cancel the schedule override for ' + displayDate + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_override">
            <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
            <input type="hidden" name="schedule_date" value="${date}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
