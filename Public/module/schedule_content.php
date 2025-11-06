<?php
// schedule_content.php
// Calendar content for the schedule view - ADVANCED CALENDAR SYSTEM

// Helper Functions for Schedule Management
function h_schedule($s){ return htmlspecialchars($s,ENT_QUOTES); }

function getMonthsNav_schedule($year, $month) {
    $prev = date('Y-n', strtotime("$year-$month-01 -1 month"));
    $next = date('Y-n', strtotime("$year-$month-01 +1 month"));
    return ['prev'=>$prev, 'next'=>$next];
}

// Get employee's schedule for a specific date - FROM PERSONAL CALENDAR CACHE
// This is the employee's ACTUAL calendar - showing approved requests, admin overrides, holidays, and defaults
function getScheduleCell_schedule($pdo, $employee_id, $date) {
    // Query the pre-computed cache table - THIS IS THE EMPLOYEE'S PERSONAL CALENDAR DATA
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
            source
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
            'schedule_color' => getScheduleColor_schedule($cache['source'], $cache['is_rest_day'], $cache['is_holiday'])
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
                'schedule_color' => '#e2e8f0'
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
                'schedule_color' => '#3b82f6'
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
            'schedule_color' => '#e2e8f0'
        ];
    }
    
    // Absolute fallback: return empty
    return [
        'date' => $date,
        'employee_id' => $employee_id,
        'actual_schedule' => null,
        'is_rest_day' => 1,
        'is_holiday' => 0,
        'source' => 'none',
        'schedule_color' => '#e2e8f0'
    ];
}

// Helper function to get color based on source
function getScheduleColor_schedule($source, $is_rest_day, $is_holiday) {
    if ($is_holiday) {
        return '#f59e0b'; // Amber for holidays
    }
    
    switch ($source) {
        case 'approved_request':
            return '#10b981'; // Green for approved change requests
        case 'admin_override':
            return '#8b5cf6'; // Purple for admin overrides
        case 'weekly_default':
            return $is_rest_day ? '#e2e8f0' : '#3b82f6'; // Light gray or blue
        case 'weekend':
            return '#e2e8f0'; // Light gray
        default:
            return '#9ca3af'; // Gray for no data
    }
}

// Calendar matrix generation
function monthMatrix_schedule($year, $month) {
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

// Setup calendar data
$ym_schedule = isset($_GET['ym']) ? $_GET['ym'] : date('Y-n');
list($year_schedule, $month_schedule) = explode('-', $ym_schedule);
$month_schedule = (int)$month_schedule; 
$year_schedule = (int)$year_schedule;
$nav_schedule = getMonthsNav_schedule($year_schedule, $month_schedule);
$matrix_schedule = monthMatrix_schedule($year_schedule, $month_schedule);

// Get work schedules for override form
try {
    $workSchedules_schedule = $pdo->query("SELECT * FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $workSchedules_schedule = [];
}
?>

<div class="bg-white rounded-2xl shadow-lg border border-gray-100">
    <!-- Modern Header Section -->
    <div class="px-8 py-7 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <i class="fas fa-calendar-check text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Schedule Management</h2>
                    <p class="text-sm text-gray-600 mt-1">View and manage your work schedule</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.location.href='?ym=<?= date('Y-n') ?>#scheduleView'"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow">
                    <i class="fas fa-calendar-day mr-2 text-gray-400"></i>Today
                </button>
                <button onclick="openScheduleChangeModal()"
                        class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40">
                    <i class="fas fa-plus mr-2"></i>New Request
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php
    // Display success/error messages
    if (isset($_GET['schedule'])) {
        $schedule_msg = $_GET['schedule'];
        if ($schedule_msg === 'success') {
            echo '<div class="mx-8 mt-6 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 px-5 py-4 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Schedule change request submitted successfully!</p>
                        <p class="text-sm text-green-700 mt-0.5">Please wait for approval.</p>
                    </div>
                </div>
            </div>';
        } elseif ($schedule_msg === 'error') {
            echo '<div class="mx-8 mt-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 px-5 py-4 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Error submitting schedule request</p>
                        <p class="text-sm text-red-700 mt-0.5">Please try again.</p>
                    </div>
                </div>
            </div>';
        } elseif ($schedule_msg === 'invalid_input') {
            echo '<div class="mx-8 mt-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 px-5 py-4 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Invalid input</p>
                        <p class="text-sm text-red-700 mt-0.5">Please fill in all required fields.</p>
                    </div>
                </div>
            </div>';
        } elseif ($schedule_msg === 'past_date') {
            echo '<div class="mx-8 mt-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 px-5 py-4 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Invalid date</p>
                        <p class="text-sm text-red-700 mt-0.5">Cannot request schedule changes for past dates.</p>
                    </div>
                </div>
            </div>';
        }
    }
    
    // Display rest day success message
    if (isset($_GET['rest_day'])) {
        $rest_day_msg = $_GET['rest_day'];
        if ($rest_day_msg === 'success') {
            echo '<div class="mx-8 mt-6 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 px-5 py-4 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Day off request submitted successfully!</p>
                        <p class="text-sm text-green-700 mt-0.5">Please wait for approval.</p>
                    </div>
                </div>
            </div>';
        }
    }
    ?>

    <!-- Calendar Navigation -->
    <div class="px-8 py-5 border-b border-gray-100 bg-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="?ym=<?= h_schedule($nav_schedule['prev']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-50 border border-gray-200 hover:border-gray-300 text-gray-600 hover:text-gray-900 transition-all duration-200 shadow-sm hover:shadow">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <h3 class="text-2xl font-bold text-gray-900 min-w-[200px] text-center tracking-tight">
                    <?= date('F Y', strtotime("$year_schedule-$month_schedule-01")) ?>
                </h3>
                <a href="?ym=<?= h_schedule($nav_schedule['next']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-50 border border-gray-200 hover:border-gray-300 text-gray-600 hover:text-gray-900 transition-all duration-200 shadow-sm hover:shadow">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Calendar Grid Container -->
    <div class="bg-white">

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-0 border-l border-t border-gray-100">
                <!-- Weekday Headers -->
                <?php $weekdays_schedule = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
                      foreach($weekdays_schedule as $idx => $wd): 
                        $isWeekend = ($idx == 0 || $idx == 6);
                ?>
                    <div class="text-center py-4 text-xs font-bold text-gray-500 uppercase tracking-widest bg-gray-50/80 border-r border-b border-gray-100">
                        <?= $wd ?>
                    </div>
                <?php endforeach; ?>

                <!-- Calendar Days -->
                <?php foreach($matrix_schedule as $week): ?>
                    <?php foreach($week as $cellDate): ?>
                        <?php if (!$cellDate): ?>
                            <div class="min-h-[140px] bg-gray-50/30 border-r border-b border-gray-100"></div>
                        <?php else: 
                              $cell = getScheduleCell_schedule($pdo, $employee_id, $cellDate);
                              $isToday = $cellDate === date('Y-m-d');
                              $isPast = $cellDate < date('Y-m-d');
                              
                              // Clean, minimal styling
                              $bgClass = 'bg-white hover:bg-gray-50/50';
                              $borderClass = 'border-r border-b border-gray-100';
                              
                              if ($isToday) {
                                  $bgClass = 'bg-blue-50/50 border-l-4 border-l-blue-500';
                                  $borderClass = 'border-r border-b border-blue-100';
                              }
                              
                              if ($isPast) {
                                  $bgClass .= ' opacity-60';
                              }
                        ?>
                            <div class="min-h-[140px] p-4 <?= $bgClass ?> <?= $borderClass ?> transition-all duration-200 <?= !$isPast ? 'cursor-pointer' : '' ?>"
                                 <?= !$isPast ? "onclick=\"openScheduleChangeModal('$cellDate')\"" : '' ?>>
                              
                              <!-- Date Number -->
                              <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center justify-center <?= $isToday ? 'w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-full shadow-lg shadow-blue-500/30' : 'w-8 h-8' ?>">
                                  <span class="text-base font-bold <?= $isToday ? '' : 'text-gray-700' ?>"><?= date('j', strtotime($cellDate)) ?></span>
                                </div>
                                
                                <?php if ($cell['source'] === 'approved_change_request'): ?>
                                  <span class="text-xs text-amber-600 font-extrabold uppercase tracking-wider">Changed</span>
                                <?php elseif ($cell['is_holiday']): ?>
                                  <i class="fas fa-star text-amber-400 text-sm"></i>
                                <?php endif; ?>
                              </div>

                              <!-- Schedule Card (if exists) -->
                              <?php if ($cell['is_rest_day'] || $cell['is_holiday']): ?>
                                <div class="p-3.5 rounded-xl <?= $cell['is_holiday'] ? 'bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-200' : 'bg-gradient-to-br from-slate-50 to-gray-100 border-2 border-slate-200' ?> shadow-sm">
                                  <div class="flex items-center gap-3 mb-1">
                                    <div class="w-1.5 h-12 rounded-full <?= $cell['is_holiday'] ? 'bg-gradient-to-b from-amber-400 to-amber-600' : 'bg-gradient-to-b from-slate-400 to-slate-600' ?>"></div>
                                    <div class="flex-1 min-w-0">
                                      <div class="text-sm font-bold <?= $cell['is_holiday'] ? 'text-amber-900' : 'text-slate-800' ?> uppercase tracking-wide">
                                        <?= $cell['is_holiday'] ? 'HOLIDAY' : 'REST DAY' ?>
                                      </div>
                                      <?php if ($cell['is_holiday'] && isset($cell['holiday'])): ?>
                                        <div class="text-sm text-amber-700 font-medium truncate mt-1">
                                          <?= h_schedule($cell['holiday']['holiday_name']) ?>
                                        </div>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php elseif ($cell['actual_schedule']): ?>
                                <?php 
                                  // Color coding based on source
                                  $cardColor = 'blue';
                                  $badgeText = '';
                                  
                                  if ($cell['source'] === 'approved_change_request') {
                                      $cardColor = 'amber';
                                      // Don't show badge text inside card for approved requests
                                  } elseif ($cell['source'] === 'daily_override') {
                                      $cardColor = 'blue';
                                      // Override displays same as normal schedule
                                  } elseif ($cell['source'] === 'weekly_default') {
                                      $cardColor = 'blue';
                                  }
                                ?>
                                <div class="p-3.5 rounded-xl bg-gradient-to-br from-<?= $cardColor ?>-50 to-<?= $cardColor ?>-100/50 border-2 border-<?= $cardColor ?>-200 hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                                  <div class="flex items-start gap-3">
                                    <div class="w-1.5 h-full rounded-full bg-gradient-to-b from-<?= $cardColor ?>-400 to-<?= $cardColor ?>-600"></div>
                                    <div class="flex-1 min-w-0">
                                      <?php if ($badgeText): ?>
                                        <div class="text-xs font-extrabold text-<?= $cardColor ?>-600 uppercase tracking-wider mb-1.5">
                                          <?= $badgeText ?>
                                        </div>
                                      <?php endif; ?>
                                      <?php if (!empty($cell['actual_schedule']['name'])): ?>
                                        <div class="text-sm font-bold text-gray-900 truncate mb-2.5">
                                          <?= h_schedule($cell['actual_schedule']['name']) ?>
                                        </div>
                                      <?php endif; ?>
                                      <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-<?= $cardColor ?>-600 text-xs"></i>
                                        <span class="font-bold text-sm text-<?= $cardColor ?>-800">
                                          <?= date('g:i A', strtotime($cell['actual_schedule']['time_in'])) ?> - <?= date('g:i A', strtotime($cell['actual_schedule']['time_out'])) ?>
                                        </span>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="text-sm text-gray-400 italic font-medium">
                                  No schedule
                                </div>
                              <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
    </div>

    <!-- Footer -->
    <div class="px-8 py-5 border-t border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-info-circle text-blue-600 text-sm"></i>
                </div>
                <span class="font-medium">Click on any future date to request a schedule change</span>
            </div>
            <div class="flex items-center gap-5">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <span class="text-xs font-medium text-gray-500">Work Day</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <span class="text-xs font-medium text-gray-500">Holiday</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                    <span class="text-xs font-medium text-gray-500">Rest Day</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Request Modal -->
<div id="scheduleRequestModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Request Schedule Change</h3>
                    <p class="text-sm text-gray-500 mt-1">Submit a request for schedule modification</p>
                </div>
                <button onclick="closeScheduleRequestModal()"
                        class="w-9 h-9 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="post" action="time_log_create.php" class="space-y-5">
                <input type="hidden" name="action" value="schedule_request">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                    <input type="date" name="schedule_date" id="modal_schedule_date_schedule" required 
                           min="<?= date('Y-m-d') ?>"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Request Type</label>
                    <select name="request_type" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="schedule_change">Schedule Change</option>
                        <option value="rest_day">Rest Day Request</option>
                        <option value="overtime">Overtime Request</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Schedule <span class="text-gray-400 font-normal">(optional for rest day)</span></label>
                    <select name="new_schedule_id" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">-- Rest Day --</option>
                        <?php foreach($workSchedules_schedule as $ws): ?>
                            <option value="<?= $ws['id'] ?>">
                                <?= h_schedule($ws['name'] ?? 'Schedule ' . $ws['id']) ?> 
                                (<?= date('g:i A', strtotime($ws['time_in'])) ?> - <?= date('g:i A', strtotime($ws['time_out'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Reason</label>
                    <textarea name="reason" rows="4" required placeholder="Please explain why you need this schedule change..."
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeScheduleRequestModal()"
                            class="flex-1 px-5 py-3 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 transition-all duration-200">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Schedule content JavaScript functions are now handled by the modal in schedule_change_form.php
// The openScheduleChangeModal() function is defined in the schedule_change_form.php file

// Auto-process approved schedule changes when calendar loads
document.addEventListener('DOMContentLoaded', function() {
    // Only run if we're on the schedule view
    if (document.getElementById('scheduleView')) {
        console.log('📅 Schedule view loaded - checking for approved schedule changes to process...');
        
        // Call the processor
        fetch('../controller/ajax_process_schedules.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Schedule processor:', data.message);
                if (data.processed > 0) {
                    console.log(`   Processed: ${data.processed}/${data.total_found} request(s)`);
                    
                    // Reload the calendar to show updated schedules
                    setTimeout(() => {
                        console.log('🔄 Reloading calendar to show updated schedules...');
                        window.location.reload();
                    }, 1000);
                } else {
                    console.log('   No new approved requests to process');
                }
                
                if (data.errors && data.errors.length > 0) {
                    console.warn('⚠️ Some errors occurred:', data.errors);
                }
            } else {
                console.error('❌ Error processing schedules:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Network error:', error);
        });
    }
});
</script>

<style>
/* Modern minimalist calendar - SCOPED TO SCHEDULE VIEW ONLY */

/* Remove default margins and ensure full width - ONLY for schedule view */
#scheduleView {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
}

/* Calendar takes full viewport - ONLY inside schedule view */
#scheduleView .grid.grid-cols-7 {
    min-height: calc(100vh - 220px);
}

/* Refined hover effect on calendar cells - ONLY inside schedule view */
#scheduleView .min-h-\[140px\]:hover {
    background-color: #f9fafb !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Today's date styling with glow effect - ONLY inside schedule view */
#scheduleView .bg-gradient-to-br.from-blue-600 {
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    animation: pulse-blue 2s ease-in-out infinite;
}

@keyframes pulse-blue {
    0%, 100% {
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    50% {
        box-shadow: 0 4px 16px rgba(37, 99, 235, 0.5);
    }
}

/* Schedule card enhanced hover effect - ONLY inside schedule view */
#scheduleView .hover\:shadow-lg:hover {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

/* Smooth transitions for all elements - ONLY inside schedule view */
#scheduleView * {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Refined border colors - ONLY inside schedule view */
#scheduleView .border-gray-100 {
    border-color: #f3f4f6;
}

#scheduleView .border-gray-200 {
    border-color: #e5e7eb;
}

/* Calendar grid consistency - ONLY inside schedule view */
#scheduleView .grid.grid-cols-7 > div {
    position: relative;
}

/* Vertical accent line in schedule cards - ONLY inside schedule view */
#scheduleView .w-1\.5 {
    flex-shrink: 0;
}

/* Text truncation with ellipsis - ONLY inside schedule view */
#scheduleView .truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Button hover states with scale - ONLY inside schedule view */
#scheduleView button:hover,
#scheduleView a:hover {
    transform: translateY(0);
}

#scheduleView button:active,
#scheduleView a:active {
    transform: scale(0.98);
}

/* Modal backdrop animation */
#scheduleRequestModal {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Modal content animation */
#scheduleRequestModal > div > div {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Focus states for form inputs */
#scheduleView input:focus,
#scheduleView select:focus,
#scheduleView textarea:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Gradient enhancements */
#scheduleView .bg-gradient-to-r,
#scheduleView .bg-gradient-to-br,
#scheduleView .bg-gradient-to-b {
    background-size: 200% 200%;
}

/* Ensure cells grow to fill space evenly - ONLY inside schedule view */
#scheduleView .min-h-\[140px\] {
    flex: 1;
}

/* Legend dots styling */
#scheduleView .w-2.h-2.rounded-full {
    box-shadow: 0 0 0 3px currentColor;
    opacity: 0.2;
}

/* Weekday header styling */
#scheduleView .tracking-widest {
    letter-spacing: 0.15em;
}
</style>