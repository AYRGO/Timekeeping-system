<!-- Schedule Change Modal -->
<div id="scheduleChangeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
  <div class="flex items-center justify-center min-h-screen px-4">
    <div class="schedule-change-modal-container bg-white shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">

      <!-- Modal Header - Monday.com Style -->
      <div class="bg-white border-b-4 border-green-600 px-8 py-6 relative flex-shrink-0">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-calendar-alt text-green-600 text-lg"></i>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Schedule Change Request</h2>
              <p class="text-sm text-gray-500 mt-0.5">Submit your schedule modification</p>
            </div>
          </div>
          
          <!-- Close Button -->
          <button onclick="closeScheduleChangeModal()" 
                  class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-all">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
      </div>

      <!-- Modal Form Content -->
      <div class="p-8 overflow-y-auto flex-1">
        <form method="POST" action="time_log_create.php" enctype="multipart/form-data" id="scheduleChangeForm">
          <?= csrf_token_field() ?>
          <input type="hidden" name="submit_schedule_change" value="1">

          <!-- Request Type Selector -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Request Type</label>
            <div class="grid grid-cols-2 gap-3">
              <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-green-500 transition-all request-type-option active">
                <input type="radio" name="request_type" value="single_day" class="sr-only" checked>
                <div class="flex items-center gap-3 w-full">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-day text-blue-600"></i>
                  </div>
                  <div class="flex-1">
                    <div class="font-semibold text-gray-900">Single Day</div>
                    <div class="text-xs text-gray-500">Change one specific date</div>
                  </div>
                  <div class="check-icon hidden">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                  </div>
                </div>
              </label>
              
              <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-green-500 transition-all request-type-option">
                <input type="radio" name="request_type" value="monthly" class="sr-only">
                <div class="flex items-center gap-3 w-full">
                  <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-alt text-purple-600"></i>
                  </div>
                  <div class="flex-1">
                    <div class="font-semibold text-gray-900">Monthly Schedule</div>
                    <div class="text-xs text-gray-500">Set schedule for entire month</div>
                  </div>
                  <div class="check-icon hidden">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Single Day Section -->
          <div id="singleDaySection" class="request-section">
          
          <!-- Row 1: Single Date -->
          <div class="mb-6">
            <label for="schedule_date" class="block text-sm font-semibold text-gray-700 mb-2">
              Select Date
            </label>
            <div class="relative">
              <input type="date" name="date_range" id="schedule_date" placeholder="Select date"
                     min="<?= date('Y-m-d') ?>"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white">
              <i class="fas fa-calendar absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
            </div>
            
            <!-- Current Schedule Display -->
            <div id="currentScheduleDisplay" class="hidden mt-3">
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                  </div>
                  <div class="flex-1">
                    <div class="text-xs font-semibold text-blue-800 uppercase tracking-wide mb-1">Current Schedule</div>
                    <div id="currentScheduleText" class="text-sm text-blue-900 font-medium"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Row 2: Work Hours (Full Width) -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Select New Work Schedule
            </label>
            
            <!-- Hidden input to store selected schedule ID -->
            <input type="hidden" name="work_schedule_id" id="work_schedule_id">
            
            <!-- Search/Select Input -->
            <div class="relative mb-3">
              <input type="text" id="scheduleSearch" placeholder="Type time to search or select rest day..."
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white pr-10"
                     autocomplete="off">
              <i class="fas fa-search absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
            </div>
            
            <!-- Selected schedule display (inline, minimal) -->
            <div id="selectedScheduleDisplay" class="hidden mb-3">
              <div class="px-4 py-2 bg-green-50 border-l-4 border-green-500 rounded">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2 flex-1">
                    <i class="fas fa-arrow-right text-green-600 text-sm"></i>
                    <div class="flex-1">
                      <div class="text-xs font-semibold text-green-800 uppercase tracking-wide">New Schedule</div>
                      <span class="text-sm font-medium text-green-900" id="selectedScheduleText"></span>
                    </div>
                  </div>
                  <button type="button" onclick="clearScheduleSelection()" 
                          class="text-gray-400 hover:text-red-500 text-sm transition-colors">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Schedule Grid (Hidden by default, shows on search) -->
            <div id="scheduleGrid" class="hidden border border-gray-200 rounded-lg bg-white max-h-[400px] overflow-y-auto">
              
              <!-- REST DAY OPTION (Always at top) -->
              <div class="schedule-category border-b-2 border-gray-300">
                <div class="px-4 py-2 bg-red-50 border-b border-red-200">
                  <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-times text-red-500 text-xs"></i>
                    <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Rest Day Request</span>
                  </div>
                </div>
                <div class="schedule-list">
                  <div class="schedule-card rest-day-card border-b border-gray-100 px-4 py-3 cursor-pointer hover:bg-red-50 transition-colors"
                       data-schedule-id="rest_day"
                       data-schedule-name="Rest Day / Day Off"
                       data-schedule-time="No Work Hours"
                       onclick="selectSchedule(this)">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-3 flex-1">
                        <div class="w-8 h-8 bg-red-100 rounded flex items-center justify-center flex-shrink-0">
                          <i class="fas fa-bed text-red-600 text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="font-medium text-gray-900 text-sm">Rest Day / Day Off</div>
                          <div class="text-gray-500 text-xs">Request a day off from work</div>
                        </div>
                      </div>
                      <div class="schedule-check hidden">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <?php 
              // Categorize schedules by time
              $day_shifts = [];
              $night_shifts = [];
              $evening_shifts = [];
              
              foreach ($work_schedules as $ws) {
                  $time_in_hour = !empty($ws['time_in']) ? (int)date("H", strtotime($ws['time_in'])) : 0;
                  
                  if ($time_in_hour >= 5 && $time_in_hour < 12) {
                      $day_shifts[] = $ws;
                  } elseif ($time_in_hour >= 17 || $time_in_hour < 5) {
                      $night_shifts[] = $ws;
                  } else {
                      $evening_shifts[] = $ws;
                  }
              }
              
              // Function to render schedule cards (minimal Monday.com style)
              function renderScheduleCards($schedules, $category_color) {
                  foreach ($schedules as $ws) {
                      $name = !empty($ws['name']) ? htmlspecialchars($ws['name']) : '';
                      $timeIn = !empty($ws['time_in']) ? $ws['time_in'] : '00:00:00';
                      $timeOut = !empty($ws['time_out']) ? $ws['time_out'] : '00:00:00';
                      $time_display = date("g:i A", strtotime($timeIn)) . ' - ' . date("g:i A", strtotime($timeOut));
                      
                      // Use time as display name if no custom name exists
                      $display_name = !empty($name) ? $name : $time_display;
                      
                      ?>
                      <div class="schedule-card border-b border-gray-100 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                           data-schedule-id="<?= $ws['id'] ?>"
                           data-schedule-name="<?= $display_name ?>"
                           data-schedule-time="<?= $time_display ?>"
                           onclick="selectSchedule(this)">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center gap-3 flex-1">
                            <div class="w-8 h-8 bg-<?= $category_color ?>-100 rounded flex items-center justify-center flex-shrink-0">
                              <i class="fas fa-clock text-<?= $category_color ?>-600 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                              <?php if (!empty($name)): ?>
                                <div class="font-medium text-gray-900 text-sm"><?= $name ?></div>
                                <div class="text-gray-500 text-xs"><?= $time_display ?></div>
                              <?php else: ?>
                                <div class="font-medium text-gray-900 text-sm"><?= $time_display ?></div>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="schedule-check hidden">
                            <i class="fas fa-check-circle text-green-500 text-lg"></i>
                          </div>
                        </div>
                      </div>
                      <?php
                  }
              }
              ?>
              
              <!-- Minimal list view -->
              <div class="schedule-results-container">
                <?php if (!empty($day_shifts)): ?>
                  <div class="schedule-category">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                      <div class="flex items-center gap-2">
                        <i class="fas fa-sun text-yellow-500 text-xs"></i>
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Day Shifts</span>
                      </div>
                    </div>
                    <div class="schedule-list">
                      <?php renderScheduleCards($day_shifts, 'yellow'); ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <?php if (!empty($evening_shifts)): ?>
                  <div class="schedule-category">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                      <div class="flex items-center gap-2">
                        <i class="fas fa-cloud-sun text-orange-500 text-xs"></i>
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Afternoon/Evening</span>
                      </div>
                    </div>
                    <div class="schedule-list">
                      <?php renderScheduleCards($evening_shifts, 'orange'); ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <?php if (!empty($night_shifts)): ?>
                  <div class="schedule-category">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                      <div class="flex items-center gap-2">
                        <i class="fas fa-moon text-indigo-500 text-xs"></i>
                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Night Shifts</span>
                      </div>
                    </div>
                    <div class="schedule-list">
                      <?php renderScheduleCards($night_shifts, 'indigo'); ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          </div>
          <!-- End Single Day Section -->

          <!-- Monthly Schedule Section -->
          <div id="monthlySection" class="request-section hidden">
            
            <!-- Month Selector -->
            <div class="mb-6">
              <label for="schedule_month" class="block text-sm font-semibold text-gray-700 mb-2">
                Select Month
              </label>
              <div class="relative">
                <input type="month" name="schedule_month" id="schedule_month" 
                       min="<?= date('Y-m') ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white">
                <i class="fas fa-calendar-alt absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
              </div>
            </div>

            <!-- Weekly Schedule Configuration -->
            <div class="mb-6">
              <label class="block text-sm font-semibold text-gray-700 mb-3">
                Configure Weekly Schedule
              </label>
              <p class="text-xs text-gray-500 mb-4">Type to search and select a schedule for each day. This will apply to all weeks in the selected month.</p>
              
              <div class="space-y-3">
                <?php 
                $weekDays = [
                  ['key' => 'sunday', 'label' => 'Sunday', 'icon' => 'fa-sun', 'color' => 'red'],
                  ['key' => 'monday', 'label' => 'Monday', 'icon' => 'fa-briefcase', 'color' => 'blue'],
                  ['key' => 'tuesday', 'label' => 'Tuesday', 'icon' => 'fa-briefcase', 'color' => 'blue'],
                  ['key' => 'wednesday', 'label' => 'Wednesday', 'icon' => 'fa-briefcase', 'color' => 'blue'],
                  ['key' => 'thursday', 'label' => 'Thursday', 'icon' => 'fa-briefcase', 'color' => 'blue'],
                  ['key' => 'friday', 'label' => 'Friday', 'icon' => 'fa-briefcase', 'color' => 'blue'],
                  ['key' => 'saturday', 'label' => 'Saturday', 'icon' => 'fa-moon', 'color' => 'indigo']
                ];
                
                foreach ($weekDays as $day): 
                ?>
                <div class="day-schedule-row border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-all bg-white">
                  <div class="flex items-start gap-4">
                    <div class="flex items-center gap-3 w-32 flex-shrink-0 mt-2">
                      <i class="fas <?= $day['icon'] ?> text-<?= $day['color'] ?>-500 text-sm"></i>
                      <span class="font-semibold text-gray-700"><?= $day['label'] ?></span>
                    </div>
                    
                    <div class="flex-1">
                      <!-- Hidden input to store selected schedule ID -->
                      <input type="hidden" name="<?= $day['key'] ?>_schedule" id="<?= $day['key'] ?>_schedule_id" class="day-schedule-value">
                      
                      <!-- Search Input -->
                      <div class="relative">
                        <input type="text" 
                               id="<?= $day['key'] ?>_search" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm day-search-input" 
                               placeholder="Type to search schedule or rest day..."
                               autocomplete="off"
                               data-day="<?= $day['key'] ?>">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                      </div>
                      
                      <!-- Selected schedule display -->
                      <div id="<?= $day['key'] ?>_selected" class="hidden mt-2">
                        <div class="px-3 py-2 bg-green-50 border-l-4 border-green-500 rounded flex items-center justify-between">
                          <div class="flex items-center gap-2 flex-1">
                            <i class="fas fa-check-circle text-green-600 text-xs"></i>
                            <span class="text-sm font-medium text-green-900" id="<?= $day['key'] ?>_selected_text"></span>
                          </div>
                          <button type="button" onclick="clearDaySchedule('<?= $day['key'] ?>')" 
                                  class="text-gray-400 hover:text-red-500 text-xs transition-colors">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                      </div>
                      
                      <!-- Schedule dropdown (hidden by default) -->
                      <div id="<?= $day['key'] ?>_dropdown" class="hidden mt-2 border border-gray-200 rounded-lg bg-white max-h-64 overflow-y-auto shadow-lg">
                        <!-- Rest Day Option -->
                        <div class="schedule-option rest-day-option border-b border-gray-100 px-3 py-2 cursor-pointer hover:bg-red-50 transition-colors"
                             data-schedule-id="rest_day"
                             data-schedule-name="Rest Day / Day Off"
                             data-day="<?= $day['key'] ?>"
                             onclick="selectDaySchedule(this)">
                          <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-red-100 rounded flex items-center justify-center flex-shrink-0">
                              <i class="fas fa-bed text-red-600 text-xs"></i>
                            </div>
                            <div class="flex-1">
                              <div class="font-medium text-gray-900 text-sm">Rest Day / Day Off</div>
                            </div>
                          </div>
                        </div>
                        
                        <?php
                        // Get all work schedules for this day's dropdown
                        try {
                          $schedStmt = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules ORDER BY time_in");
                          while ($sched = $schedStmt->fetch(PDO::FETCH_ASSOC)) {
                            $name = !empty($sched['name']) ? htmlspecialchars($sched['name']) : '';
                            $timeIn = !empty($sched['time_in']) ? $sched['time_in'] : '00:00:00';
                            $timeOut = !empty($sched['time_out']) ? $sched['time_out'] : '00:00:00';
                            $timeDisplay = date('g:i A', strtotime($timeIn)) . ' - ' . date('g:i A', strtotime($timeOut));
                            $displayName = !empty($name) ? $name : $timeDisplay;
                            ?>
                            <div class="schedule-option border-b border-gray-100 px-3 py-2 cursor-pointer hover:bg-gray-50 transition-colors"
                                 data-schedule-id="<?= $sched['id'] ?>"
                                 data-schedule-name="<?= htmlspecialchars($displayName) ?>"
                                 data-schedule-time="<?= $timeDisplay ?>"
                                 data-day="<?= $day['key'] ?>"
                                 onclick="selectDaySchedule(this)">
                              <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center flex-shrink-0">
                                  <i class="fas fa-clock text-blue-600 text-xs"></i>
                                </div>
                                <div class="flex-1">
                                  <?php if (!empty($name)): ?>
                                    <div class="font-medium text-gray-900 text-sm"><?= $name ?></div>
                                    <div class="text-gray-500 text-xs"><?= $timeDisplay ?></div>
                                  <?php else: ?>
                                    <div class="font-medium text-gray-900 text-sm"><?= $timeDisplay ?></div>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>
                            <?php
                          }
                        } catch (PDOException $e) {
                          // Handle error silently
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              
              <div class="mt-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start gap-2">
                  <i class="fas fa-info-circle text-blue-600 text-sm mt-0.5"></i>
                  <div class="text-xs text-blue-800">
                    <strong>Tip:</strong> Simply type the time (e.g., "7:00" or "4pm") or "rest" to quickly find schedules. Each day's schedule will apply to all matching days in the selected month.
                  </div>
                </div>
              </div>
            </div>
            
          </div>
          <!-- End Monthly Section -->

          <!-- Row 3: Reason and Attachment (Common for both types) -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Reason -->
            <div>
              <label for="reason" class="block text-sm font-semibold text-gray-700 mb-2">
                Reason for Change
                <span class="text-red-500 ml-1">*</span>
              </label>
              <textarea name="reason" id="reason" rows="4" placeholder="Provide details about your schedule change request..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white resize-none" required></textarea>
              <p class="text-xs text-gray-500 mt-1.5">
                Be specific to help with approval
              </p>
            </div>

            <!-- Attachment -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Supporting Document
                <span class="text-red-500 ml-1">*</span>
              </label>
              <input type="file" name="attachment_scr" id="fileInput" accept=".pdf,.jpg,.jpeg,.png"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer" required>
              <p class="text-xs text-gray-500 mt-1.5">
                PDF, JPG, JPEG, PNG (Max 10MB)
              </p>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-3 pt-6 border-t border-gray-200">
            <button type="button" onclick="closeScheduleChangeModal()"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
              Cancel
            </button>
            <button type="submit" id="submitBtn"
                    class="flex-1 px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium text-sm">
              Submit Request
            </button>
          </div>
        </form>

        <!-- Message Box -->
        <div id="messageBox" class="hidden mb-6 p-4 text-center text-white rounded-xl font-medium"></div>
      </div>
    </div>
  </div>
</div>

<style>
/* Scope all styles to the schedule change modal container */
.schedule-change-modal-container {
    /* Modal Animations */
    animation: modalSlideIn 0.3s ease-out;
}

#scheduleChangeModal {
    animation: modalFadeIn 0.3s ease-out;
    backdrop-filter: blur(4px);
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalSlideIn {
    from { 
        opacity: 0; 
        transform: translateY(-20px) scale(0.98); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

/* Error states */
.schedule-change-modal-container .border-red-400 {
    border-color: #f87171 !important;
    background-color: #fef2f2 !important;
}

/* Schedule card styles - Monday.com minimal */
.schedule-change-modal-container .schedule-card {
    transition: background-color 0.15s ease;
}

.schedule-change-modal-container .schedule-card:hover {
    background-color: #f9fafb;
}

.schedule-change-modal-container .schedule-card.rest-day-card:hover {
    background-color: #fef2f2;
}

.schedule-change-modal-container .schedule-card.selected {
    background-color: #f0fdf4;
    border-left: 3px solid #22c55e;
}

.schedule-change-modal-container .schedule-card.rest-day-card.selected {
    background-color: #fef2f2;
    border-left: 3px solid #ef4444;
}

.schedule-change-modal-container .schedule-card.selected .schedule-check {
    display: block !important;
}

/* Custom scrollbar for schedule grid */
.schedule-change-modal-container #scheduleGrid::-webkit-scrollbar {
    width: 6px;
}

.schedule-change-modal-container #scheduleGrid::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.schedule-change-modal-container #scheduleGrid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.schedule-change-modal-container #scheduleGrid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Monthly schedule option styles */
.schedule-change-modal-container .schedule-option {
    transition: background-color 0.15s ease;
}

.schedule-change-modal-container .schedule-option:hover {
    background-color: #f9fafb;
}

.schedule-change-modal-container .rest-day-option:hover {
    background-color: #fef2f2;
}

/* Custom scrollbar for day dropdowns */
.schedule-change-modal-container .day-schedule-row [id$='_dropdown']::-webkit-scrollbar {
    width: 4px;
}

.schedule-change-modal-container .day-schedule-row [id$='_dropdown']::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.schedule-change-modal-container .day-schedule-row [id$='_dropdown']::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.schedule-change-modal-container .day-schedule-row [id$='_dropdown']::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .schedule-change-modal-container .lg\\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

/* Focus states - clean and minimal */
.schedule-change-modal-container input:focus,
.schedule-change-modal-container textarea:focus,
.schedule-change-modal-container select:focus {
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

/* Request type selector styles */
.request-type-option {
    position: relative;
}

.request-type-option.active {
    border-color: #22c55e !important;
    background-color: #f0fdf4;
}

.request-type-option.active .check-icon {
    display: block !important;
}

.request-type-option .check-icon {
    display: none;
}

/* Section visibility */
.request-section {
    display: block;
}

.request-section.hidden {
    display: none;
}


/* Empty state for schedule grid */
.schedule-change-modal-container .schedule-results-container:empty::after {
    content: "No schedules found";
    display: block;
    padding: 3rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}
</style>

<script>
// Modal Functions
function openScheduleChangeModal(date) {
    const modal = document.getElementById('scheduleChangeModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Reset form first
    document.getElementById('scheduleChangeForm').reset();
    // Clear any error states
    document.querySelectorAll('.border-red-400').forEach(field => {
        field.classList.remove('border-red-400');
    });
    
    // If a date was provided, set it in the date field
    if (date) {
        const dateField = document.getElementById('schedule_date');
        if (dateField) {
            dateField.value = date;
            console.log('📅 Pre-filled date:', dateField.value);
            
            // Fetch and display current schedule for this date
            setTimeout(() => fetchCurrentSchedule(date), 100);
        }
    }
}

function closeScheduleChangeModal() {
    const modal = document.getElementById('scheduleChangeModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Re-enable background scrolling
}

// Form Validation
document.getElementById('scheduleChangeForm').addEventListener('submit', function(e) {
    const requestType = document.querySelector('input[name="request_type"]:checked').value;
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    // Validate based on request type
    if (requestType === 'single_day') {
        // Check if schedule is selected (either a work schedule or rest day)
        const scheduleSearch = document.getElementById('scheduleSearch');
        if (!scheduleSearch.value.trim()) {
            scheduleSearch.classList.add('border-red-400');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = scheduleSearch;
            alert('Please select a work schedule or rest day.');
        }
        
        // Validate date is selected
        const scheduleDate = document.getElementById('schedule_date');
        if (!scheduleDate.value.trim()) {
            scheduleDate.classList.add('border-red-400');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = scheduleDate;
        }
    } else if (requestType === 'monthly') {
        // Validate month is selected
        const scheduleMonth = document.getElementById('schedule_month');
        if (!scheduleMonth.value.trim()) {
            scheduleMonth.classList.add('border-red-400');
            isValid = false;
            if (!firstInvalidField) firstInvalidField = scheduleMonth;
            alert('Please select a month.');
        }
        
        // Validate at least one day has a schedule selected
        const dayValueInputs = document.querySelectorAll('.day-schedule-value');
        let hasSchedule = false;
        dayValueInputs.forEach(input => {
            if (input.value) {
                hasSchedule = true;
            }
        });
        
        if (!hasSchedule) {
            isValid = false;
            alert('Please select a schedule for at least one day of the week.');
            const firstSearchInput = document.querySelector('.day-search-input');
            if (firstSearchInput) {
                firstSearchInput.focus();
                if (!firstInvalidField) firstInvalidField = firstSearchInput;
            }
        }
    }
    
    // Check required fields (reason and attachment)
    requiredFields.forEach(field => {
        // Skip fields that are not in the active section
        const fieldSection = field.closest('.request-section');
        if (fieldSection && fieldSection.classList.contains('hidden')) {
            return;
        }
        
        // Special handling for file input
        if (field.type === 'file') {
            if (!field.files || field.files.length === 0) {
                field.classList.add('border-red-400');
                isValid = false;
                if (!firstInvalidField) firstInvalidField = field;
                
                field.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        this.classList.remove('border-red-400');
                    }
                }, { once: true });
            }
        } else {
            // Regular field validation
            if (!field.value.trim()) {
                field.classList.add('border-red-400');
                isValid = false;
                if (!firstInvalidField) firstInvalidField = field;
                
                field.addEventListener('input', function() {
                    this.classList.remove('border-red-400');
                }, { once: true });
            }
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        
        // Show alert for missing fields
        if (!alert.shown) {
            alert('Please fill in all required fields including the supporting document.');
        }
        
        // Focus and scroll to first error
        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    } else {
        // Additional validation based on request type
        const requestType = document.querySelector('input[name="request_type"]:checked').value;
        
        if (requestType === 'single_day') {
            // Log form data before submission for debugging (single day)
            const scheduleDate = document.getElementById('schedule_date').value;
            const workScheduleId = document.getElementById('work_schedule_id').value;
            const fileInput = document.getElementById('fileInput');
            const hasFile = fileInput.files && fileInput.files.length > 0;
            
            console.log('📝 Single day form submission data:', {
                schedule_date: scheduleDate,
                work_schedule_id: workScheduleId || 'REST_DAY',
                date_format: scheduleDate ? 'valid' : 'EMPTY!',
                has_attachment: hasFile,
                attachment_name: hasFile ? fileInput.files[0].name : 'none',
                is_rest_day: !workScheduleId
            });
            
            if (!scheduleDate || scheduleDate.trim() === '') {
                console.error('❌ Date is empty!');
                alert('Please select a date before submitting.');
                e.preventDefault();
                document.getElementById('schedule_date').focus();
                document.getElementById('schedule_date').classList.add('border-red-400');
                return;
            }
        } else if (requestType === 'monthly') {
            // Log form data before submission for debugging (monthly)
            const scheduleMonth = document.getElementById('schedule_month').value;
            const fileInput = document.getElementById('fileInput');
            const hasFile = fileInput.files && fileInput.files.length > 0;
            
            // Collect selected schedules
            const selectedSchedules = {};
            document.querySelectorAll('.day-schedule-select').forEach(select => {
                if (select.value) {
                    selectedSchedules[select.id] = select.value;
                }
            });
            
            console.log('📝 Monthly form submission data:', {
                schedule_month: scheduleMonth,
                request_type: requestType,
                selected_schedules: selectedSchedules,
                has_attachment: hasFile,
                attachment_name: hasFile ? fileInput.files[0].name : 'none'
            });
            
            if (!scheduleMonth || scheduleMonth.trim() === '') {
                console.error('❌ Month is empty!');
                alert('Please select a month before submitting.');
                e.preventDefault();
                document.getElementById('schedule_month').focus();
                document.getElementById('schedule_month').classList.add('border-red-400');
                return;
            }
        }
        
        console.log('✅ Form validation passed, submitting...');
    }
});

// Close modal when clicking outside
document.getElementById('scheduleChangeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScheduleChangeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('scheduleChangeModal');
        if (modal.style.display === 'block') {
            closeScheduleChangeModal();
        }
    }
});

// Legacy function for compatibility
function hideScheduleView() {
    closeScheduleChangeModal();
}

// Schedule selection functions
function selectSchedule(cardElement) {
    // Remove selection from all cards
    document.querySelectorAll('#scheduleChangeModal .schedule-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    cardElement.classList.add('selected');
    
    // Set hidden input value
    const scheduleId = cardElement.getAttribute('data-schedule-id');
    const scheduleName = cardElement.getAttribute('data-schedule-name');
    const scheduleTime = cardElement.getAttribute('data-schedule-time');
    
    // Handle rest day selection
    if (scheduleId === 'rest_day') {
        document.getElementById('work_schedule_id').value = ''; // Empty value for rest day
        document.getElementById('selectedScheduleText').textContent = '🛌 ' + scheduleName;
        document.getElementById('scheduleSearch').value = scheduleName;
    } else {
        document.getElementById('work_schedule_id').value = scheduleId;
        
        // Check if schedule name is same as time (no custom name)
        if (scheduleName === scheduleTime) {
            // Only show once
            document.getElementById('selectedScheduleText').textContent = scheduleTime;
            document.getElementById('scheduleSearch').value = scheduleTime;
        } else {
            // Show custom name + time
            document.getElementById('selectedScheduleText').textContent = scheduleName + ' • ' + scheduleTime;
            document.getElementById('scheduleSearch').value = scheduleName + ' (' + scheduleTime + ')';
        }
    }
    
    // Show selected schedule display
    document.getElementById('selectedScheduleDisplay').classList.remove('hidden');
    
    // Hide the grid after selection
    document.getElementById('scheduleGrid').classList.add('hidden');
    
    // Remove any validation error
    document.getElementById('scheduleSearch').classList.remove('border-red-400');
}

function clearScheduleSelection() {
    // Clear hidden input
    document.getElementById('work_schedule_id').value = '';
    
    // Hide selected display
    document.getElementById('selectedScheduleDisplay').classList.add('hidden');
    
    // Clear search input
    document.getElementById('scheduleSearch').value = '';
    
    // Remove selection from all cards
    document.querySelectorAll('#scheduleChangeModal .schedule-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Hide grid
    document.getElementById('scheduleGrid').classList.add('hidden');
    
    // Focus search input
    document.getElementById('scheduleSearch').focus();
}
 
// Function to fetch and display current schedule for a date
function fetchCurrentSchedule(date) {
    console.log('🔍 fetchCurrentSchedule called with date:', date);
    
    if (!date) {
        console.log('❌ No date provided, hiding display');
        document.getElementById('currentScheduleDisplay').classList.add('hidden');
        return;
    }
    
    // Show loading state
    console.log('⏳ Showing loading state...');
    document.getElementById('currentScheduleDisplay').classList.remove('hidden');
    document.getElementById('currentScheduleText').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading current schedule...';
    
    // Fetch schedule from backend
    console.log('📡 Fetching from: ../controller/ajax_get_schedule_for_date.php');
    fetch('../controller/ajax_get_schedule_for_date.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'date=' + encodeURIComponent(date)
    })
    .then(response => {
        console.log('📥 Response received:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Data received:', data);
        
        if (data.success && data.has_schedule) {
            document.getElementById('currentScheduleText').textContent = data.display;
            document.getElementById('currentScheduleDisplay').classList.remove('hidden');
            console.log('✅ Current schedule displayed:', data.display);
        } else if (data.success) {
            document.getElementById('currentScheduleText').textContent = data.display || 'No schedule set';
            document.getElementById('currentScheduleDisplay').classList.remove('hidden');
            console.log('ℹ️ No schedule found:', data.display);
        } else {
            console.error('❌ Error from server:', data.error);
            document.getElementById('currentScheduleText').textContent = 'Error loading schedule: ' + (data.error || 'Unknown error');
            document.getElementById('currentScheduleDisplay').classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('❌ Network error:', error);
        document.getElementById('currentScheduleText').textContent = 'Failed to load current schedule. Please try again.';
        document.getElementById('currentScheduleDisplay').classList.remove('hidden');
    });
}

// Add event listener for date changes - fetch current schedule when date is selected
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Schedule change form initialized');
    
    const dateField = document.getElementById('schedule_date');
    
    if (dateField) {
        console.log('✅ Date field found');
        
        // Add change event listener
        dateField.addEventListener('change', function() {
            const selectedDate = this.value;
            console.log('📅 Date changed to:', selectedDate);
            
            if (selectedDate) {
                // Fetch and display current schedule for this date
                fetchCurrentSchedule(selectedDate);
            } else {
                // Hide current schedule if no date selected
                document.getElementById('currentScheduleDisplay').classList.add('hidden');
            }
        });
        
        // Also add input event for better responsiveness
        dateField.addEventListener('input', function() {
            const selectedDate = this.value;
            console.log('📅 Date input:', selectedDate);
            
            if (selectedDate) {
                fetchCurrentSchedule(selectedDate);
            }
        });
    } else {
        console.error('❌ Date field not found!');
    }
});

// Search functionality - shows dropdown on type
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('scheduleSearch');
    const scheduleGrid = document.getElementById('scheduleGrid');
    
    if (searchInput && scheduleGrid) {
        // Store original grid content
        const originalGridContent = scheduleGrid.innerHTML;
        
        // Show grid when user starts typing
        searchInput.addEventListener('focus', function() {
            if (this.value.trim() !== '') {
                scheduleGrid.classList.remove('hidden');
            }
        });
        
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                // Hide grid if search is empty
                scheduleGrid.classList.add('hidden');
                // Restore original content
                scheduleGrid.innerHTML = originalGridContent;
                return;
            }
            
            // Restore original content first if it was replaced with "no results" message
            if (!scheduleGrid.querySelector('.schedule-results-container')) {
                scheduleGrid.innerHTML = originalGridContent;
            }
            
            // Show grid when typing
            scheduleGrid.classList.remove('hidden');
            
            const cards = document.querySelectorAll('#scheduleChangeModal .schedule-card');
            const categories = document.querySelectorAll('#scheduleChangeModal .schedule-category');
            
            let hasVisibleResults = false;
            
            // Filter cards - search by time numbers AND schedule name
            cards.forEach(card => {
                const scheduleName = card.getAttribute('data-schedule-name').toLowerCase();
                const time = card.getAttribute('data-schedule-time').toLowerCase();
                
                // Check if it's the rest day card
                const isRestDay = card.classList.contains('rest-day-card');
                
                if (isRestDay) {
                    // For rest day, search in name only (rest, day, off)
                    if (scheduleName.includes(searchTerm) || 
                        'rest'.includes(searchTerm) || 
                        'off'.includes(searchTerm) || 
                        'day off'.includes(searchTerm)) {
                        card.style.display = '';
                        hasVisibleResults = true;
                    } else {
                        card.style.display = 'none';
                    }
                } else {
                    // For regular schedules, search by time numbers
                    const timeNumbers = time.replace(/[^0-9:]/g, '');
                    
                    // Check if search term appears in the time or name (including formatted and raw numbers)
                    if (time.includes(searchTerm) || 
                        timeNumbers.includes(searchTerm) || 
                        scheduleName.includes(searchTerm)) {
                        card.style.display = '';
                        hasVisibleResults = true;
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
            
            // Hide empty categories
            categories.forEach(category => {
                const visibleCards = category.querySelectorAll('.schedule-card:not([style*="display: none"])');
                if (visibleCards.length === 0) {
                    category.style.display = 'none';
                } else {
                    category.style.display = '';
                }
            });
            
            // Show no results message if needed
            if (!hasVisibleResults) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-results-message p-8 text-center text-gray-400 text-sm';
                noResultsDiv.textContent = 'No schedules found matching "' + searchTerm + '"';
                scheduleGrid.innerHTML = '';
                scheduleGrid.appendChild(noResultsDiv);
            } else {
                // Remove no results message if it exists
                const noResultsMsg = scheduleGrid.querySelector('.no-results-message');
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        });
        
        // Hide grid when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !scheduleGrid.contains(e.target)) {
                if (!document.getElementById('work_schedule_id').value) {
                    scheduleGrid.classList.add('hidden');
                }
            }
        });
    }
});

// Request type switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const requestTypeOptions = document.querySelectorAll('.request-type-option');
    const singleDaySection = document.getElementById('singleDaySection');
    const monthlySection = document.getElementById('monthlySection');
    
    requestTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            const requestType = radio.value;
            
            // Update active state
            requestTypeOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide sections
            if (requestType === 'single_day') {
                singleDaySection.classList.remove('hidden');
                monthlySection.classList.add('hidden');
                
                // Set required fields for single day
                document.getElementById('schedule_date').required = true;
                document.getElementById('schedule_month').required = false;
                
                // Clear monthly fields
                document.getElementById('schedule_month').value = '';
                clearAllDaySchedules();
            } else {
                singleDaySection.classList.add('hidden');
                monthlySection.classList.remove('hidden');
                
                // Set required fields for monthly
                document.getElementById('schedule_date').required = false;
                document.getElementById('schedule_month').required = true;
                
                // Clear single day fields
                document.getElementById('schedule_date').value = '';
                document.getElementById('work_schedule_id').value = '';
                document.getElementById('scheduleSearch').value = '';
                document.getElementById('selectedScheduleDisplay').classList.add('hidden');
            }
        });
    });
});

// Monthly schedule day selection functions
function selectDaySchedule(optionElement) {
    const day = optionElement.getAttribute('data-day');
    const scheduleId = optionElement.getAttribute('data-schedule-id');
    const scheduleName = optionElement.getAttribute('data-schedule-name');
    const scheduleTime = optionElement.getAttribute('data-schedule-time');
    
    // Set hidden input value
    const hiddenInput = document.getElementById(day + '_schedule_id');
    hiddenInput.value = scheduleId === 'rest_day' ? 'rest_day' : scheduleId;
    
    // Update search input display
    const searchInput = document.getElementById(day + '_search');
    if (scheduleId === 'rest_day') {
        searchInput.value = '🛌 ' + scheduleName;
    } else {
        searchInput.value = scheduleName === scheduleTime ? scheduleTime : scheduleName + ' (' + scheduleTime + ')';
    }
    
    // Update selected display
    const selectedDiv = document.getElementById(day + '_selected');
    const selectedText = document.getElementById(day + '_selected_text');
    
    if (scheduleId === 'rest_day') {
        selectedText.textContent = '🛌 ' + scheduleName;
    } else {
        selectedText.textContent = scheduleName === scheduleTime ? scheduleTime : scheduleName + ' • ' + scheduleTime;
    }
    
    selectedDiv.classList.remove('hidden');
    
    // Hide dropdown
    document.getElementById(day + '_dropdown').classList.add('hidden');
    
    console.log('✅ Selected schedule for ' + day + ':', scheduleId);
}

function clearDaySchedule(day) {
    // Clear hidden input
    document.getElementById(day + '_schedule_id').value = '';
    
    // Clear search input
    document.getElementById(day + '_search').value = '';
    
    // Hide selected display
    document.getElementById(day + '_selected').classList.add('hidden');
    
    // Hide dropdown
    document.getElementById(day + '_dropdown').classList.add('hidden');
    
    // Focus search input
    document.getElementById(day + '_search').focus();
    
    console.log('🗑️ Cleared schedule for ' + day);
}

function clearAllDaySchedules() {
    const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    days.forEach(day => {
        const hiddenInput = document.getElementById(day + '_schedule_id');
        const searchInput = document.getElementById(day + '_search');
        const selectedDiv = document.getElementById(day + '_selected');
        const dropdown = document.getElementById(day + '_dropdown');
        
        if (hiddenInput) hiddenInput.value = '';
        if (searchInput) searchInput.value = '';
        if (selectedDiv) selectedDiv.classList.add('hidden');
        if (dropdown) dropdown.classList.add('hidden');
    });
}

// Search functionality for monthly schedule inputs
document.addEventListener('DOMContentLoaded', function() {
    const daySearchInputs = document.querySelectorAll('.day-search-input');
    
    daySearchInputs.forEach(searchInput => {
        const day = searchInput.getAttribute('data-day');
        const dropdown = document.getElementById(day + '_dropdown');
        
        if (!dropdown) return;
        
        // Store original dropdown content
        const originalDropdownContent = dropdown.innerHTML;
        
        // Show dropdown on focus
        searchInput.addEventListener('focus', function() {
            if (this.value.trim() !== '') {
                dropdown.classList.remove('hidden');
            }
        });
        
        // Search on input
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                dropdown.classList.add('hidden');
                // Restore original content
                dropdown.innerHTML = originalDropdownContent;
                return;
            }
            
            // Restore original content first if it was replaced
            if (!dropdown.querySelector('.schedule-option')) {
                dropdown.innerHTML = originalDropdownContent;
            }
            
            // Show dropdown
            dropdown.classList.remove('hidden');
            
            const options = dropdown.querySelectorAll('.schedule-option');
            let hasVisibleResults = false;
            
            // Filter options
            options.forEach(option => {
                const scheduleName = option.getAttribute('data-schedule-name').toLowerCase();
                const scheduleTime = option.getAttribute('data-schedule-time');
                const isRestDay = option.classList.contains('rest-day-option');
                
                if (isRestDay) {
                    // Search for rest day keywords
                    if (scheduleName.includes(searchTerm) || 
                        'rest'.includes(searchTerm) || 
                        'off'.includes(searchTerm) || 
                        'day off'.includes(searchTerm)) {
                        option.style.display = '';
                        hasVisibleResults = true;
                    } else {
                        option.style.display = 'none';
                    }
                } else {
                    // Search by time and name
                    const time = scheduleTime ? scheduleTime.toLowerCase() : '';
                    const timeNumbers = time.replace(/[^0-9:]/g, '');
                    
                    if (time.includes(searchTerm) || 
                        timeNumbers.includes(searchTerm) || 
                        scheduleName.includes(searchTerm)) {
                        option.style.display = '';
                        hasVisibleResults = true;
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
            
            // Show no results message if needed
            if (!hasVisibleResults) {
                dropdown.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">No schedules found matching "' + searchTerm + '"</div>';
            }
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                const hiddenInput = document.getElementById(day + '_schedule_id');
                if (!hiddenInput.value) {
                    dropdown.classList.add('hidden');
                }
            }
        });
    });
});

</script>