<!-- Request Leave -->
<div id="requestView" class="hidden mt-8">
  <div class="flex justify-center items-start min-h-[70vh] px-4">
    <div class="w-full max-w-6xl">

      <!-- Modern Header with Gradient -->
      <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 rounded-t-2xl shadow-lg">
        <div class="flex items-center justify-center space-x-3">
          <div class="bg-white bg-opacity-20 p-2 rounded-full">
            <i class="fas fa-calendar-plus text-white text-xl"></i>
          </div>
          <h2 class="text-3xl font-bold text-white">Submit Leave Request</h2>
        </div>
        <p class="text-green-100 text-center mt-2">Fill out the form below to request time off</p>
      </div>

      <!-- Horizontal Form Layout -->
      <div class="bg-white rounded-b-2xl shadow-xl border border-green-100 -mt-1">
        <form id="leaveRequestForm" action="time_log_create.php" method="POST" enctype="multipart/form-data" class="p-8">
          <?= csrf_token_field() ?>>

          <!-- Row 1: Leave Type and Date Range -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Leave Type -->
            <div class="space-y-2">
              <label class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-tags text-green-600 mr-2"></i>
                Leave Type
              </label>
              <select name="leaveType" id="leaveType" required 
                      class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white">
                <option value="" disabled selected>Choose your leave type</option>
                <?php
                $types = [
                  'sick'          => 'Sick Leave (SL)',
                  'vacation'      => 'Vacation Leave (VL)',
                  'paternity'     => 'Paternity Leave',
                  'maternity'     => 'Maternity Leave',
                  'solo_parent'   => 'Solo Parent Leave (SPL)',
                  'halfday'       => 'Half Day Vacation (Half_VL)',
                  'halfday_sick'  => 'Half Day Sick (Half_SL)',
                  'lwop'          => 'Leave Without Pay (LWOP)',
                  'bereavement'   => 'Bereavement Leave',
                ];
                foreach ($types as $val => $label):
                ?>
                  <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach; ?>
              </select>

              <!-- Leave Credit Display -->
              <div id="leaveBalance" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">
                  <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <span class="text-blue-800 font-medium text-sm"></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Date Range -->
            <div class="space-y-2">
              <label class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-calendar-alt text-green-600 mr-2"></i>
                Leave Dates
              </label>
              <div class="relative">
                <input type="text" name="date_range" id="date_range" placeholder="Select your leave dates"
                       class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white pl-12" required>
                <i class="fas fa-calendar absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
              </div>
            </div>
          </div>

          <!-- Row 2: Reason and Attachment -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Reason -->
            <div class="space-y-2">
              <label class="flex items-center text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-comment-alt text-green-600 mr-2"></i>
                Reason for Leave
              </label>
              <textarea name="reason" rows="4" placeholder="Provide details about your leave request..."
                        class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white resize-none"></textarea>
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
                <input type="file" name="attachment_lr" accept=".pdf,.jpg,.jpeg,.png" required
                       class="w-full p-4 border-2 border-gray-200 rounded-xl focus:ring-3 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-lg bg-gray-50 hover:bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
              </div>
              <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-3">
                <div class="flex items-start">
                  <i class="fas fa-exclamation-triangle text-amber-600 mr-2 mt-0.5"></i>
                  <div class="text-amber-800 text-sm">
                    <p class="font-medium">Accepted formats:</p>
                    <p>PDF, JPG, JPEG, PNG files only</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Leave Balance Warning -->
          <div id="leaveBalanceDisplay" class="hidden mb-6">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
              <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                <span class="text-red-700 font-medium"></span>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-center pt-6 border-t border-gray-100">
            <button type="submit" id="submitBtn"
                    class="group relative px-12 py-4 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-300 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
              <div class="flex items-center space-x-3">
                <i class="fas fa-paper-plane group-hover:translate-x-1 transition-transform duration-200"></i>
                <span>Submit Leave Request</span>
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