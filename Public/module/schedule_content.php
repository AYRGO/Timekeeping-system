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

<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <!-- Modern Header Section -->
    <div class="px-8 py-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center shadow-sm">
                    <i class="fas fa-calendar-check text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Schedule Management</h2>
                    <p class="text-sm text-gray-500 mt-0.5">View and manage your work schedule</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.location.href='?ym=<?= date('Y-n') ?>#scheduleView'"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-calendar-day mr-2 text-gray-500"></i>Today
                </button>
                <button onclick="openScheduleChangeModal()"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
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
    
    // Display rest day success message
    if (isset($_GET['rest_day'])) {
        $rest_day_msg = $_GET['rest_day'];
        if ($rest_day_msg === 'success') {
            echo '<div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>Day off request submitted successfully! Please wait for approval.
            </div>';
        }
    }
    ?>

    <!-- Calendar Navigation -->
    <div class="px-8 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="?ym=<?= h_schedule($nav_schedule['prev']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-gray-200 text-gray-600 hover:text-gray-900 transition-all shadow-sm hover:shadow">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <h3 class="text-xl font-semibold text-gray-800 min-w-[180px] text-center">
                    <?= date('F Y', strtotime("$year_schedule-$month_schedule-01")) ?>
                </h3>
                <a href="?ym=<?= h_schedule($nav_schedule['next']) ?>#scheduleView" 
                   onclick="showSection('scheduleView')"
                   class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-gray-200 text-gray-600 hover:text-gray-900 transition-all shadow-sm hover:shadow">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Calendar Grid Container -->
    <div class="bg-white">

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
                                  $bgClass = 'bg-blue-50 border-l-4 border-l-blue-500';
                                  $borderClass = 'border-r border-b border-blue-200';
                              }
                              
                              if ($isPast) {
                                  $bgClass .= ' opacity-50';
                              }
                        ?>
                            <div class="min-h-[140px] p-3 <?= $bgClass ?> <?= $borderClass ?> transition-colors <?= !$isPast ? 'cursor-pointer' : '' ?>"
                                 <?= !$isPast ? "onclick=\"openScheduleChangeModal('$cellDate')\"" : '' ?>>
                              
                              <!-- Date Number -->
                              <div class="flex items-center justify-between mb-3">
                                <div class="w-8 h-8 flex items-center justify-center <?= $isToday ? 'bg-blue-600 text-white rounded-full' : '' ?>">
                                  <span class="text-base font-semibold <?= $isToday ? '' : 'text-gray-700' ?>"><?= date('j', strtotime($cellDate)) ?></span>
                                </div>
                                
                                <?php if ($cell['source'] === 'approved_change_request'): ?>
                                  <span class="text-xs text-amber-600 font-bold uppercase">Schedule Changed</span>
                                <?php elseif ($cell['is_holiday']): ?>
                                  <i class="fas fa-star text-amber-500 text-sm"></i>
                                <?php endif; ?>
                              </div>

                              <!-- Schedule Card (if exists) -->
                              <?php if ($cell['is_rest_day'] || $cell['is_holiday']): ?>
                                <div class="p-3 rounded-lg <?= $cell['is_holiday'] ? 'bg-amber-50 border border-amber-300' : 'bg-slate-50 border border-slate-300' ?>">
                                  <div class="flex items-center gap-2.5 mb-1">
                                    <div class="w-1.5 h-10 rounded-full <?= $cell['is_holiday'] ? 'bg-amber-500' : 'bg-slate-400' ?>"></div>
                                    <div class="flex-1 min-w-0">
                                      <div class="text-sm font-semibold <?= $cell['is_holiday'] ? 'text-amber-800' : 'text-slate-700' ?> uppercase tracking-wide">
                                        <?= $cell['is_holiday'] ? 'HOLIDAY' : 'REST DAY' ?>
                                      </div>
                                      <?php if ($cell['is_holiday'] && isset($cell['holiday'])): ?>
                                        <div class="text-sm text-amber-700 truncate mt-1">
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
                                <div class="p-3 rounded-lg bg-<?= $cardColor ?>-50 border border-<?= $cardColor ?>-200 hover:shadow-md transition-shadow">
                                  <div class="flex items-start gap-2.5">
                                    <div class="w-1.5 h-full rounded-full bg-<?= $cardColor ?>-500"></div>
                                    <div class="flex-1 min-w-0">
                                      <?php if ($badgeText): ?>
                                        <div class="text-xs font-bold text-<?= $cardColor ?>-600 uppercase tracking-wide mb-1">
                                          <?= $badgeText ?>
                                        </div>
                                      <?php endif; ?>
                                      <?php if (!empty($cell['actual_schedule']['name'])): ?>
                                        <div class="text-sm font-semibold text-gray-800 truncate mb-2">
                                          <?= h_schedule($cell['actual_schedule']['name']) ?>
                                        </div>
                                      <?php endif; ?>
                                      <div class="flex items-center gap-1.5">
                                        <i class="fas fa-clock text-<?= $cardColor ?>-500 text-xs"></i>
                                        <span class="font-bold text-sm text-<?= $cardColor ?>-700">
                                          <?= date('g:i A', strtotime($cell['actual_schedule']['time_in'])) ?> - <?= date('g:i A', strtotime($cell['actual_schedule']['time_out'])) ?>
                                        </span>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              <?php else: ?>
                                <div class="text-sm text-gray-400 italic">
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
    <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <i class="fas fa-info-circle text-gray-400"></i>
                <span>Click on any date to request a schedule change</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-check text-blue-500"></i>
                    <span><?= count(array_filter($matrix_schedule, function($week) { return array_filter($week); })) * 7 ?> days in view</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span>Updated <?= date('M d, Y') ?></span>
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
                           class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                    <select name="request_type" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="schedule_change">Schedule Change</option>
                        <option value="rest_day">Rest Day Request</option>
                        <option value="overtime">Overtime Request</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Schedule (leave blank for rest day)</label>
                    <select name="new_schedule_id" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                              class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeScheduleRequestModal()"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
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
#scheduleView .bg-green-600.rounded-full {
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
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