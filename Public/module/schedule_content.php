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

// Get employee's schedule for a specific date - ADVANCED VERSION
function getScheduleCell_schedule($pdo, $employee_id, $date) {
    $cell = [
        'date'=>$date, 
        'employee_id'=>$employee_id, 
        'base_schedule'=>null, 
        'actual_schedule'=>null, 
        'is_rest_day'=>0, 
        'is_holiday'=>0, 
        'has_override'=>0, 
        'override_status'=>null, 
        'schedule_color'=>'#9ca3af', 
        'override_data'=>null, 
        'source'=>'none'
    ];

    // PRIORITY 1: Check for daily override in employee_daily_schedules
    $dailyStmt = $pdo->prepare("SELECT * FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ? LIMIT 1");
    $dailyStmt->execute([$employee_id, $date]);
    $dailyOverride = $dailyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dailyOverride) {
        $cell['has_override'] = 1;
        $cell['override_status'] = 'approved';
        $cell['override_data'] = $dailyOverride;
        $cell['source'] = 'daily_override';
        
        if ($dailyOverride['is_rest_day']) {
            $cell['is_rest_day'] = 1;
            $cell['schedule_color'] = '#ef4444'; // Red for OFF day
            $cell['actual_schedule'] = null;
        } elseif ($dailyOverride['actual_schedule_id']) {
            $schedStmt = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
            $schedStmt->execute([$dailyOverride['actual_schedule_id']]);
            $sched = $schedStmt->fetch(PDO::FETCH_ASSOC);
            if ($sched) {
                $cell['actual_schedule'] = $sched;
                $cell['schedule_color'] = '#10b981'; // Green for daily override
            }
        }
        return $cell; // Return early - highest priority
    }

    // PRIORITY 2: Check for weekly default in employee_default_schedules (using day_of_week structure)
    $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 1=Monday, ..., 6=Saturday
    $weeklyStmt = $pdo->prepare("SELECT work_schedule_id, is_rest_day FROM employee_default_schedules WHERE employee_id = ? AND day_of_week = ? AND effective_from <= ? AND (effective_until IS NULL OR effective_until >= ?) LIMIT 1");
    $weeklyStmt->execute([$employee_id, $dayOfWeek, $date, $date]);
    $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weekly) {
        if ($weekly['is_rest_day']) {
            $cell['is_rest_day'] = 1;
            $cell['schedule_color'] = '#f3f4f6'; // Light gray for rest day
            $cell['source'] = 'weekly_default';
        } elseif ($weekly['work_schedule_id']) {
            $schedStmt = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
            $schedStmt->execute([$weekly['work_schedule_id']]);
            $sched = $schedStmt->fetch(PDO::FETCH_ASSOC);
            if ($sched) {
                $cell['actual_schedule'] = $sched;
                $cell['schedule_color'] = '#3b82f6'; // Blue for weekly default
                $cell['source'] = 'weekly_default';
            }
        }
        return $cell;
    }

    // PRIORITY 3: Check for holiday
    $hstmt = $pdo->prepare("SELECT * FROM company_holidays WHERE DATE(holiday_date) = DATE(?) OR (is_recurring=1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(?, '%m-%d')) LIMIT 1");
    $hstmt->execute([$date, $date]);
    $holiday = $hstmt->fetch(PDO::FETCH_ASSOC);
    if ($holiday) {
        $cell['is_holiday'] = 1;
        $cell['holiday'] = $holiday;
        $cell['schedule_color'] = '#fbbf24'; // Yellow for holidays
        $cell['is_rest_day'] = 1;
        $cell['source'] = 'holiday';
        return $cell;
    }

    // PRIORITY 4: Check weekend (fallback)
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $cell['is_rest_day'] = 1;
        $cell['schedule_color'] = '#f3f4f6'; // Light gray for weekends
        $cell['source'] = 'weekend';
    }

    return $cell;
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

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-calendar-check text-blue-500 mr-3"></i>
            Schedule Management
        </h2>
        <p class="text-gray-600 text-lg">View and manage your work schedule</p>
    </div>

    <?php
    // Display success/error messages
    if (isset($_GET['schedule'])) {
        $schedule_msg = $_GET['schedule'];
        if ($schedule_msg === 'success') {
            echo '<div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>Schedule change request submitted successfully! Please wait for approval.
            </div>';
        } elseif ($schedule_msg === 'error') {
            echo '<div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>Error submitting schedule request. Please try again.
            </div>';
        } elseif ($schedule_msg === 'invalid_input') {
            echo '<div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>Please fill in all required fields.
            </div>';
        } elseif ($schedule_msg === 'past_date') {
            echo '<div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>Cannot request schedule changes for past dates.
            </div>';
        }
    }
    ?>

<div class="bg-white">
    <!-- Calendar -->
    <div class="bg-white">
        <!-- Calendar Navigation -->
        <div class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white">
            <div class="flex items-center gap-4">
                <a href="?ym=<?= h_schedule($nav_schedule['prev']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-9 h-9 flex items-center justify-center rounded hover:bg-gray-100 text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <h3 class="text-2xl font-semibold text-gray-900">
                    <?= date('F Y', strtotime("$year_schedule-$month_schedule-01")) ?>
                </h3>
                <a href="?ym=<?= h_schedule($nav_schedule['next']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-9 h-9 flex items-center justify-center rounded hover:bg-gray-100 text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <div class="flex gap-3">
                <a href="?ym=<?= date('Y-n') ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                    Today
                </a>
                <button onclick="openScheduleChangeModal()"
                        class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-sm">
                    <i class="fas fa-plus mr-2 text-xs"></i>New Request
                </button>
            </div>
        </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-0 border-l border-t border-gray-200">
                <!-- Weekday Headers -->
                <?php $weekdays_schedule = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
                      foreach($weekdays_schedule as $idx => $wd): 
                        $isWeekend = ($idx == 0 || $idx == 6);
                ?>
                    <div class="text-center py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider bg-gray-50 border-r border-b border-gray-200">
                        <?= $wd ?>
                    </div>
                <?php endforeach; ?>

                <!-- Calendar Days -->
                <?php foreach($matrix_schedule as $week): ?>
                    <?php foreach($week as $cellDate): ?>
                        <?php if (!$cellDate): ?>
                            <div class="min-h-[140px] bg-gray-50/30 border-r border-b border-gray-200"></div>
                        <?php else: 
                              $cell = getScheduleCell_schedule($pdo, $employee_id, $cellDate);
                              $isToday = $cellDate === date('Y-m-d');
                              $isPast = $cellDate < date('Y-m-d');
                              
                              // Clean, minimal styling like Monday.com
                              $bgClass = 'bg-white hover:bg-gray-50';
                              $borderClass = 'border-r border-b border-gray-200';
                              
                              if ($isToday) {
                                  $bgClass = 'bg-blue-50/30';
                              }
                              
                              if ($isPast) {
                                  $bgClass .= ' opacity-50';
                              }
                        ?>
                            <div class="min-h-[140px] p-3 <?= $bgClass ?> <?= $borderClass ?> transition-colors <?= !$isPast ? 'cursor-pointer' : '' ?>"
                                 <?= !$isPast ? "onclick=\"openScheduleChangeModal('$cellDate')\"" : '' ?>>
                              
                              <!-- Date Number -->
                              <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold <?= $isToday ? 'bg-blue-600 text-white w-7 h-7 flex items-center justify-center rounded-full' : 'text-gray-700' ?>">
                                  <?= date('j', strtotime($cellDate)) ?>
                                </span>
                                
                                <?php if ($cell['source'] === 'daily_override'): ?>
                                  <span class="text-xs text-green-600 font-medium">Override</span>
                                <?php elseif ($cell['is_holiday']): ?>
                                  <i class="fas fa-star text-amber-500 text-xs"></i>
                                <?php endif; ?>
                              </div>

                              <!-- Schedule Card (if exists) -->
                              <?php if ($cell['is_rest_day'] || $cell['is_holiday']): ?>
                                <div class="p-2.5 rounded-lg <?= $cell['is_holiday'] ? 'bg-amber-50 border border-amber-200' : 'bg-gray-50 border border-gray-200' ?>">
                                  <div class="flex items-center gap-2 mb-1">
                                    <div class="w-1 h-8 rounded-full <?= $cell['is_holiday'] ? 'bg-amber-500' : 'bg-gray-400' ?>"></div>
                                    <div class="flex-1 min-w-0">
                                      <div class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                                        <?= $cell['is_holiday'] ? 'HOLIDAY' : 'REST DAY' ?>
                                      </div>
                                      <?php if ($cell['is_holiday'] && isset($cell['holiday'])): ?>
                                        <div class="text-xs text-gray-600 truncate mt-0.5">
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
                                  if ($cell['source'] === 'daily_override') {
                                      $cardColor = 'green';
                                  } elseif ($cell['source'] === 'weekly_default') {
                                      $cardColor = 'blue';
                                  }
                                ?>
                                <div class="p-2.5 rounded-lg bg-<?= $cardColor ?>-50 border border-<?= $cardColor ?>-200 hover:shadow-md transition-shadow">
                                  <div class="flex items-start gap-2">
                                    <div class="w-1 h-full rounded-full bg-<?= $cardColor ?>-500"></div>
                                    <div class="flex-1 min-w-0">
                                      <?php if (!empty($cell['actual_schedule']['name'])): ?>
                                        <div class="text-xs font-semibold text-gray-800 truncate mb-1">
                                          <?= h_schedule($cell['actual_schedule']['name']) ?>
                                        </div>
                                      <?php endif; ?>
                                      <div class="flex items-center gap-1.5 text-xs text-gray-600">
                                        <i class="fas fa-clock text-[10px]"></i>
                                        <span class="font-medium"><?= date('g:i A', strtotime($cell['actual_schedule']['time_in'])) ?></span>
                                      </div>
                                      <div class="flex items-center gap-1.5 text-xs text-gray-600 mt-0.5">
                                        <i class="fas fa-arrow-right text-[10px]"></i>
                                        <span class="font-medium"><?= date('g:i A', strtotime($cell['actual_schedule']['time_out'])) ?></span>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="text-xs text-gray-400 italic">
                                  No schedule
                                </div>
                              <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Bottom Legend Bar -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-200">
              <div class="flex flex-wrap gap-8 text-sm text-gray-600 items-center">
                <div class="flex items-center gap-2.5">
                  <div class="w-4 h-4 rounded bg-green-500"></div>
                  <span>Daily Override</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <div class="w-4 h-4 rounded bg-blue-500"></div>
                  <span>Weekly Schedule</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <div class="w-4 h-4 rounded bg-amber-500"></div>
                  <span>Holiday</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <div class="w-4 h-4 rounded bg-gray-400"></div>
                  <span>Rest Day</span>
                </div>
                <div class="flex items-center gap-2.5 ml-auto">
                  <i class="fas fa-info-circle text-blue-500"></i>
                  <span class="text-gray-500">Click any date to request a schedule change</span>
                </div>
              </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Request Modal -->
<div id="scheduleRequestModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Request Schedule Change</h3>
                <button onclick="closeScheduleRequestModal()"
                        class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="post" action="time_log_create.php" class="space-y-4">
                <input type="hidden" name="action" value="schedule_request">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="schedule_date" id="modal_schedule_date_schedule" required 
                           min="<?= date('Y-m-d') ?>"
                           class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                    <select name="request_type" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="schedule_change">Schedule Change</option>
                        <option value="rest_day">Rest Day Request</option>
                        <option value="overtime">Overtime Request</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Schedule (leave blank for rest day)</label>
                    <select name="new_schedule_id" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" rows="3" required placeholder="Please explain why you need this schedule change..."
                              class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeScheduleRequestModal()"
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
// Schedule content JavaScript functions are now handled by the modal in schedule_change_form.php
// The openScheduleChangeModal() function is defined in the schedule_change_form.php file
</script>

<style>
/* Full-screen Monday.com-inspired calendar - SCOPED TO SCHEDULE VIEW ONLY */

/* Remove default margins and ensure full width - ONLY for schedule view */
#scheduleView {
    margin: 0 !important;
    padding: 0 !important;
    max-width: none !important;
}

/* Calendar takes full viewport - ONLY inside schedule view */
#scheduleView .grid.grid-cols-7 {
    min-height: calc(100vh - 200px);
}

/* Subtle hover effect on calendar cells - ONLY inside schedule view */
#scheduleView .min-h-\[160px\]:hover {
    background-color: #f9fafb !important;
}

/* Today's date circle - ONLY inside schedule view */
#scheduleView .bg-blue-600.rounded-full {
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

/* Schedule card hover effect - ONLY inside schedule view */
#scheduleView .rounded-lg.hover\:shadow-md {
    transition: all 0.2s ease;
}

#scheduleView .rounded-lg.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

/* Smooth transitions - ONLY inside schedule view */
#scheduleView * {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Clean borders - ONLY inside schedule view */
#scheduleView .border-gray-200 {
    border-color: #e5e7eb;
}

/* Calendar grid consistency - ONLY inside schedule view */
#scheduleView .grid.grid-cols-7 > div {
    position: relative;
}

/* Vertical accent line in schedule cards - ONLY inside schedule view */
#scheduleView .w-1.h-10,
#scheduleView .w-1.h-full {
    flex-shrink: 0;
}

/* Text truncation - ONLY inside schedule view */
#scheduleView .truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Button hover states - ONLY inside schedule view */
#scheduleView button:hover,
#scheduleView a:hover {
    transform: translateY(0);
}

/* Ensure cells grow to fill space evenly - ONLY inside schedule view */
#scheduleView .min-h-\[160px\] {
    flex: 1;
}
</style>