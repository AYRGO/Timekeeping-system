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

          <!-- Row 1: Date Range (Full Width) -->
          <div class="mb-6">
            <label for="date_range" class="block text-sm font-semibold text-gray-700 mb-2">
              Effective Date Range
            </label>
            <div class="relative">
              <input type="text" name="date_range" id="date_range" placeholder="Select date range"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all bg-white flatpickr-input" required>
              <i class="fas fa-calendar absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
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
                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                    <span class="text-sm font-medium text-gray-700" id="selectedScheduleText"></span>
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
                  $time_in_hour = (int)date("H", strtotime($ws['time_in']));
                  
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
                      $time_display = date("g:i A", strtotime($ws['time_in'])) . ' - ' . date("g:i A", strtotime($ws['time_out']));
                      
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

          <!-- Row 3: Reason and Attachment -->
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

/* Responsive adjustments */
@media (max-width: 1024px) {
    .schedule-change-modal-container .lg\\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

/* Focus states - clean and minimal */
.schedule-change-modal-container input:focus,
.schedule-change-modal-container textarea:focus {
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
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
    
    // If a date was provided, set it in the date range field
    if (date) {
        setTimeout(function() {
            const dateRangeField = document.getElementById('date_range');
            if (dateRangeField && window.dateRangePicker) {
                // Set the date using flatpickr's setDate method
                const dateObj = new Date(date);
                window.dateRangePicker.setDate([dateObj, dateObj], true);
                
                // Also set the input value directly as fallback
                dateRangeField.value = date + ' to ' + date;
                
                console.log('📅 Pre-filled date range:', dateRangeField.value);
            } else if (dateRangeField) {
                // Fallback if flatpickr not initialized yet
                dateRangeField.value = date + ' to ' + date;
            }
        }, 150);
    }
}

function closeScheduleChangeModal() {
    const modal = document.getElementById('scheduleChangeModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Re-enable background scrolling
}

// Form Validation
document.getElementById('scheduleChangeForm').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    // Check if schedule is selected (either a work schedule or rest day)
    const scheduleSearch = document.getElementById('scheduleSearch');
    if (!scheduleSearch.value.trim()) {
        scheduleSearch.classList.add('border-red-400');
        isValid = false;
        if (!firstInvalidField) firstInvalidField = scheduleSearch;
        alert('Please select a work schedule or rest day.');
    }
    
    requiredFields.forEach(field => {
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
        // Log form data before submission for debugging
        const dateRange = document.getElementById('date_range').value;
        const workScheduleId = document.getElementById('work_schedule_id').value;
        const fileInput = document.getElementById('fileInput');
        const hasFile = fileInput.files && fileInput.files.length > 0;
        
        console.log('📝 Form submission data:', {
            date_range: dateRange,
            work_schedule_id: workScheduleId || 'REST_DAY',
            date_range_format: dateRange ? 'valid' : 'EMPTY!',
            has_attachment: hasFile,
            attachment_name: hasFile ? fileInput.files[0].name : 'none',
            is_rest_day: !workScheduleId
        });
        
        if (!dateRange || dateRange.trim() === '') {
            console.error('❌ Date range is empty! This will cause Jan 1, 1970 issue.');
            alert('Please select a date range before submitting.');
            e.preventDefault();
            document.getElementById('date_range').focus();
            document.getElementById('date_range').classList.add('border-red-400');
        }
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
 
// Initialize flatpickr for schedule change date range
if (typeof flatpickr !== 'undefined') {
    const dateRangeInput = document.getElementById('date_range');
    
    const fp = flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        minDate: "today",
        altInput: false, // Changed to false - use single input with readable format
        onChange: function(selectedDates, dateStr, instance) {
            console.log('📅 Flatpickr date selected:', dateStr);
            console.log('📅 Selected dates array:', selectedDates);
            console.log('📅 Input value:', document.getElementById('date_range').value);
            
            // Manually format for display but keep Y-m-d for submission
            if (selectedDates.length === 2) {
                const startDate = selectedDates[0];
                const endDate = selectedDates[1];
                
                // Format as YYYY-MM-DD for backend
                const startFormatted = startDate.getFullYear() + '-' + 
                    String(startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(startDate.getDate()).padStart(2, '0');
                const endFormatted = endDate.getFullYear() + '-' + 
                    String(endDate.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(endDate.getDate()).padStart(2, '0');
                
                // Set the value in Y-m-d format
                dateRangeInput.value = startFormatted + ' to ' + endFormatted;
                
                console.log('✅ Date range set to:', dateRangeInput.value);
            } else if (selectedDates.length === 1) {
                const startDate = selectedDates[0];
                const startFormatted = startDate.getFullYear() + '-' + 
                    String(startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(startDate.getDate()).padStart(2, '0');
                dateRangeInput.value = startFormatted;
                console.log('✅ Single date set to:', dateRangeInput.value);
            }
        },
        onReady: function(selectedDates, dateStr, instance) {
            console.log('📅 Flatpickr initialized successfully');
        }
    });
    
    // Store flatpickr instance globally for debugging
    window.dateRangePicker = fp;
} else {
    console.warn('⚠️ Flatpickr is not loaded! Date picker will not work properly.');
}

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
</script>