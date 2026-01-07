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
<div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-sm border border-gray-100 p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-calendar-alt text-white text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Schedule Calendar</h2>
                <p class="text-sm text-gray-500 mt-0.5"><?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></p>
            </div>
        </div>
        <div class="text-right">
            <div class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                <i class="fas fa-calendar text-gray-400"></i>
                <span class="font-semibold text-gray-800"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Calendar Section -->
        <div class="xl:col-span-3">
            <!-- Month Navigation -->
            <div class="flex items-center justify-between mb-6">
                <a href="?id=<?= $employeeId ?>&ym=<?= $nav['prev'] ?>#current-schedule" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all text-sm font-medium text-gray-700 shadow-sm">
                    <i class="fas fa-chevron-left text-xs"></i>
                    <span>Previous</span>
                </a>
                
                <h3 class="text-lg font-bold text-gray-800"><?= date('F Y', strtotime("$year-$month-01")) ?></h3>
                
                <a href="?id=<?= $employeeId ?>&ym=<?= $nav['next'] ?>#current-schedule" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all text-sm font-medium text-gray-700 shadow-sm">
                    <span>Next</span>
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <!-- Weekday Headers -->
                <div class="grid grid-cols-7 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Sun</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Mon</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Tue</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Wed</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Thu</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Fri</div>
                    <div class="py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Sat</div>
                </div>

                <!-- Calendar Days -->
                <?php foreach($matrix as $week): ?>
                <div class="grid grid-cols-7 border-b border-gray-100 last:border-b-0">
                    <?php foreach($week as $date): ?>
                        <?php if ($date): ?>
                            <?php 
                            $cell = getScheduleCell_admin($pdo, $emp_id, $date, $scheduleOptions);
                            $dayNum = date('j', strtotime($date));
                            $isToday = ($date === date('Y-m-d'));
                            $isPast = ($date < date('Y-m-d'));
                            $isWeekend = (date('w', strtotime($date)) == 0 || date('w', strtotime($date)) == 6);
                            ?>
                            <div class="border-r border-gray-100 last:border-r-0 p-3 min-h-[100px] relative group transition-all hover:bg-blue-50/30 cursor-pointer <?= $isPast ? 'bg-gray-50/30' : '' ?>" 
                                 onclick="openOverride_admin('<?= $date ?>')">
                                
                                <!-- Date Number -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="relative">
                                        <?php if ($isToday): ?>
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white text-xs font-bold shadow-md">
                                                <?= $dayNum ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm font-semibold text-gray-700">
                                                <?= $dayNum ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Source Badge -->
                                    <?php if ($cell['source'] === 'admin_override'): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700 border border-green-200">
                                            <i class="fas fa-user-shield mr-1"></i>ADMIN
                                        </span>
                                    <?php elseif ($cell['source'] === 'approved_request'): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700 border border-purple-200">
                                            <i class="fas fa-check-circle mr-1"></i>REQ
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Schedule Info -->
                                <div class="space-y-1">
                                    <?php if ($cell['is_holiday']): ?>
                                        <div class="flex items-start gap-1.5 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                                            <i class="fas fa-star text-yellow-500 text-xs mt-0.5"></i>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-semibold text-yellow-800 truncate">
                                                    <?= htmlspecialchars($cell['holiday']['holiday_name']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($cell['is_rest_day']): ?>
                                        <div class="flex items-center gap-1.5 p-2 bg-gray-100 border border-gray-200 rounded-lg">
                                            <i class="fas fa-moon text-gray-400 text-xs"></i>
                                            <span class="text-xs font-medium text-gray-500">Rest Day</span>
                                        </div>
                                    <?php elseif ($cell['actual_schedule']): ?>
                                        <div class="p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                            <div class="text-xs font-semibold text-blue-900 mb-1 truncate">
                                                <?= htmlspecialchars($cell['actual_schedule']['name']) ?>
                                            </div>
                                            <div class="flex items-center gap-1 text-[11px] text-blue-700">
                                                <i class="fas fa-clock text-blue-400"></i>
                                                <span><?= date('g:i A', strtotime($cell['actual_schedule']['time_in'])) ?></span>
                                                <span class="text-blue-300">-</span>
                                                <span><?= date('g:i A', strtotime($cell['actual_schedule']['time_out'])) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-2 bg-gray-50 border border-gray-200 rounded-lg">
                                            <span class="text-xs text-gray-400 italic">No schedule</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Hover Effect - Now applies to all dates including past -->
                                <div class="absolute inset-0 bg-blue-500 opacity-0 group-hover:opacity-5 pointer-events-none transition-opacity rounded"></div>
                                
                                <!-- Past Date Indicator -->
                                <?php if ($isPast): ?>
                                <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-medium bg-blue-500 text-white shadow-sm">
                                        <i class="fas fa-history"></i>
                                        <span>Edit History</span>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="border-r border-gray-100 last:border-r-0 p-3 bg-gray-50/30"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
            <!-- Add Override Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-5 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Add Override</span>
                    </h3>
                </div>
                
                <form method="post" class="p-5 space-y-4">
                    <input type="hidden" name="action" value="add_override">
                    <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
                    <input type="hidden" name="reason" value="Admin override">
                    
                    <!-- Info Alert -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex gap-2">
                            <i class="fas fa-info-circle text-blue-500 text-sm mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-xs font-medium text-blue-800">Admin Privilege</p>
                                <p class="text-[10px] text-blue-600 mt-1">You can edit schedules for any date including past dates and historical records.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Date</label>
                        <input type="date" id="override_date" name="schedule_date" required 
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <p class="text-[10px] text-gray-500 mt-1.5">
                            <i class="fas fa-calendar-check text-gray-400"></i> Click any date on the calendar or select manually
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Schedule</label>
                        <select name="override_schedule_id" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                            <option value="">-- OFF / Rest Day --</option>
                            <?php 
                            // Sort schedules by time_in
                            usort($workSchedules_cal, function($a, $b) {
                                return strtotime($a['time_in']) - strtotime($b['time_in']);
                            });
                            foreach($workSchedules_cal as $ws): 
                            ?>
                                <option value="<?= $ws['id'] ?>">
                                    <?= date('g:i A', strtotime($ws['time_in'])) ?> - <?= date('g:i A', strtotime($ws['time_out'])) ?>
                                    <?= !empty($ws['name']) ? ' (' . htmlspecialchars($ws['name']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 px-4 rounded-lg transition-all font-medium text-sm shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i>Create Override
                    </button>
                </form>
            </div>

            <!-- Upcoming Overrides Card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-5 py-4">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-list-check"></i>
                        <span>Upcoming Overrides</span>
                    </h3>
                </div>
                
                <div class="p-5">
                    <?php if ($pendingOverrides): ?>
                        <div class="space-y-3 max-h-[400px] overflow-y-auto custom-scrollbar">
                            <?php foreach($pendingOverrides as $po): ?>
                                <div class="group relative bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:shadow-md transition-all">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-calendar-day text-blue-600 text-xs"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-gray-800">
                                                    <?= date('M d, Y', strtotime($po['schedule_date'])) ?>
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    <?= date('l', strtotime($po['schedule_date'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($po['is_rest_day']): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                                <i class="fas fa-moon"></i>OFF
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold bg-green-100 text-green-700 border border-green-200">
                                                <i class="fas fa-briefcase"></i>WORK
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$po['is_rest_day'] && $po['time_in']): ?>
                                        <div class="flex items-center gap-2 p-2 bg-white border border-gray-200 rounded text-xs">
                                            <i class="fas fa-clock text-gray-400"></i>
                                            <span class="font-semibold text-gray-700">
                                                <?= date('g:i A', strtotime($po['time_in'])) ?>
                                            </span>
                                            <span class="text-gray-300">→</span>
                                            <span class="font-semibold text-gray-700">
                                                <?= date('g:i A', strtotime($po['time_out'])) ?>
                                            </span>
                                        </div>
                                        <?php if ($po['schedule_name']): ?>
                                            <div class="mt-1 text-[10px] text-gray-500 truncate">
                                                <?= htmlspecialchars($po['schedule_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                <i class="fas fa-calendar-check text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">No upcoming overrides</p>
                            <p class="text-[10px] text-gray-400 mt-1">Click a date to add one</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
function openOverride_admin(date) {
    const input = document.getElementById('override_date');
    if (input) {
        input.value = date;
        // Smooth scroll to form
        input.closest('form').scrollIntoView({behavior: 'smooth', block: 'center'});
        // Focus and highlight
        setTimeout(() => {
            input.focus();
            input.classList.add('ring-2', 'ring-blue-400');
            setTimeout(() => {
                input.classList.remove('ring-2', 'ring-blue-400');
            }, 1000);
        }, 500);
    }
}

function deleteOverride(date, displayDate) {
    if (confirm('Are you sure you want to cancel the schedule override for ' + displayDate + '?')) {
        // Show loading indicator
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Canceling...';
        
        // Use AJAX for faster response
        fetch('employee-edit.php?id=<?= $employeeId ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'delete_override',
                'employee_id': '<?= $emp_id ?>',
                'schedule_date': date
            })
        })
        .then(response => response.text())
        .then(() => {
            // Just reload the page quickly without alert
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            alert('Failed to cancel override. Please try again.');
        });
    }
}
</script>
