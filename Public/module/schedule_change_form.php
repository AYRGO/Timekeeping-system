<!-- Request Change Schedule -->
<div id="scheduleView" class="hidden mt-8">
  <div class="flex justify-center items-start min-h-[70vh] px-4">
    <div class="w-full max-w-6xl">

      <!-- Modern Header with Gradient -->
      <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 rounded-t-2xl shadow-lg">
        <div class="flex items-center justify-center space-x-3">
          <div class="bg-white bg-opacity-20 p-2 rounded-full">
            <i class="fas fa-calendar-alt text-white text-xl"></i>
          </div>
          <h2 class="text-3xl font-bold text-white">Schedule Change Request</h2>
        </div>
        <p class="text-green-100 text-center mt-2">Submit your schedule modification request</p>
      </div>

      <!-- Horizontal Form Layout -->
      <div class="bg-white rounded-b-2xl shadow-xl border border-green-100 -mt-1">
        <form method="POST" enctype="multipart/form-data" id="scheduleChangeForm" class="p-8">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="submit_schedule_change" value="1">

          <!-- Row 1: Date Range and Work Hours -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Date Range -->
            <div class="space-y-2">
              <label for="date_range" class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-calendar text-green-600 mr-2"></i>
                Effective Date Range
              </label>
              <div class="relative">
                <input type="text" name="date_range" id="date_range" placeholder="Select date range"
                       class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white pl-12" required>
                <i class="fas fa-calendar absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
              </div>
            </div>

            <!-- Work Hours -->
            <div class="space-y-2">
              <label for="work_schedule_id" class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-clock text-green-600 mr-2"></i>
                New Work Hours
              </label>
              <select name="work_schedule_id" id="work_schedule_id" required
                      class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white">
                <option value="" disabled selected>Choose your work hours</option>
                <?php 
                $allowed = [3, 4, 5, 6, 7, 8, 9, 10];
                foreach ($work_schedules as $ws):
                    if (in_array($ws['id'], $allowed)):
                ?>
                    <option value="<?= $ws['id'] ?>">
                        <?= date("g:i A", strtotime($ws['time_in'])) ?> - <?= date("g:i A", strtotime($ws['time_out'])) ?>
                    </option>
                <?php 
                    endif;
                endforeach;
                ?>
              </select>
            </div>
          </div>

          <!-- Row 2: Reason and Attachment -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Reason -->
            <div class="space-y-2">
              <label for="reason" class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-comment-alt text-green-600 mr-2"></i>
                Reason for Change
              </label>
              <textarea name="reason" id="reason" rows="4" placeholder="Provide details about your schedule change request..."
                        class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white resize-none" required></textarea>
              <p class="text-sm text-gray-500 mt-2">
                <i class="fas fa-lightbulb mr-1"></i>
                Be specific about your reason to help with approval
              </p>
            </div>

            <!-- Attachment -->
            <div class="space-y-2">
              <label class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-paperclip text-green-600 mr-2"></i>
                Supporting Document
                <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full ml-2 font-bold">Required</span>
              </label>
              <div class="relative">
                <input type="file" name="attachment_scr" id="fileInput" accept=".pdf,.jpg,.jpeg,.png" required
                       class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
              </div>
              <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-3">
                <div class="flex items-start">
                  <i class="fas fa-exclamation-triangle text-amber-600 mr-2 mt-0.5"></i>
                  <div class="text-amber-800 text-sm">
                    <p class="font-medium">Accepted formats:</p>
                    <p>PDF, JPG, JPEG, PNG files only (Max 10MB)</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Info Banner -->
          <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
            <div class="flex items-center">
              <i class="fas fa-info-circle text-green-600 mr-3"></i>
              <span class="text-green-700 font-medium">Requests are processed within 3-5 business days.</span>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-center pt-6 border-t border-gray-100">
            <button type="submit" id="submitBtn"
                    class="group relative px-12 py-4 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
              <div class="flex items-center space-x-3">
                <i class="fas fa-paper-plane group-hover:translate-x-1 transition-transform duration-200"></i>
                <span>Submit Request</span>
              </div>
              <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 rounded-xl transition-opacity duration-200"></div>
            </button>
          </div>
        </form>

        <!-- Message Box -->
        <div id="messageBox" class="hidden mx-8 mb-6 p-4 text-center text-white rounded-xl font-medium"></div>
      </div>
    </div>
  </div>
</div>

<style>
/* Form Animations */
#scheduleView:not(.hidden) {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Enhanced focus effects */
#scheduleView input:focus, 
#scheduleView select:focus, 
#scheduleView textarea:focus {
    transform: translateY(-1px);
}

/* Button hover effects */
#scheduleView button:hover {
    transform: translateY(-1px);
}

/* Error states */
#scheduleView .border-red-400 {
    border-color: #f87171 !important;
    background-color: #fef2f2 !important;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    #scheduleView .lg\\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Form Validation
document.getElementById('scheduleChangeForm').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-400');
            isValid = false;
            
            field.addEventListener('input', function() {
                this.classList.remove('border-red-400');
            }, { once: true });
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        this.querySelector('.border-red-400')?.focus();
        
        // Scroll to first error
        this.querySelector('.border-red-400')?.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
});

// Hide schedule view function
function hideScheduleView() {
    document.getElementById('scheduleView').classList.add('hidden');
}
</script>