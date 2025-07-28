
<!-- Request Change Schedule -->
<div id="scheduleView" class="hidden mt-12">
  <div class="flex justify-center items-start min-h-[60vh] px-4">
    <div class="w-full max-w-4xl">

      <!-- Header -->
      <div class="bg-blue-700 p-6 rounded-t-lg shadow-lg">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 rounded-lg mb-3">
            <i class="fas fa-calendar-alt text-white"></i>
          </div>
          <h2 class="text-xl font-semibold text-white mb-1">
            Schedule Change Request
          </h2>
          <p class="text-blue-100 text-sm">
            Submit your schedule modification request
          </p>
        </div>
      </div>

      <!-- Form Card -->
      <div class="bg-white p-6 rounded-b-lg shadow-lg border border-slate-200">
        <form method="POST" enctype="multipart/form-data" id="scheduleChangeForm" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="submit_schedule_change" value="1">

          <!-- Row 1: Date Range and Work Hours -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Date Range -->
            <div>
              <label for="date_range" class="block text-sm font-medium text-slate-700 mb-2">
                <i class="fas fa-calendar mr-2 text-slate-500"></i>Effective Date Range
              </label>
              <input type="text" name="date_range" id="date_range"
                class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all"
                placeholder="Select date range" required>
            </div>

            <!-- Work Hours -->
            <div>
              <label for="work_schedule_id" class="block text-sm font-medium text-slate-700 mb-2">
                <i class="fas fa-clock mr-2 text-slate-500"></i>New Work Hours
              </label>
              <select name="work_schedule_id" id="work_schedule_id"
                class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all bg-white"
                required>
                <option value="" disabled selected>Select work hours</option>
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

          <!-- Row 2: Reason -->
          <div>
            <label for="reason" class="block text-sm font-medium text-slate-700 mb-2">
              <i class="fas fa-edit mr-2 text-slate-500"></i>Reason for Change
            </label>
            <textarea name="reason" id="reason" rows="3"
              class="w-full p-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all resize-none"
              placeholder="Provide reason for schedule change..." required></textarea>
          </div>

          <!-- Row 3: File Upload -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              <i class="fas fa-paperclip mr-2 text-slate-500"></i>Supporting Document <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input 
                type="file" 
                name="attachment_scr" 
                id="fileInput"
                accept=".pdf,.jpg,.jpeg,.png"
                required
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
              
              <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center hover:border-slate-400 transition-all" id="fileDropArea">
                <div class="flex flex-col md:flex-row items-center justify-center gap-4">
                  <div class="flex items-center gap-3">
                    <i class="fas fa-upload text-slate-400 text-xl"></i>
                    <div class="text-left">
                      <p class="text-sm text-slate-600">
                        <span class="font-medium text-slate-800">Click to upload</span> or drag file here
                      </p>
                      <p class="text-xs text-slate-500">PDF, JPG, PNG (Max 10MB)</p>
                    </div>
                  </div>
                  <div id="fileName" class="text-sm text-emerald-600 hidden font-medium bg-emerald-50 px-3 py-1 rounded-full"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Row 4: Action Buttons and Info -->
          <div class="flex flex-col md:flex-row gap-6 pt-4">
            <!-- Action Buttons -->
            <div class="flex gap-3 md:w-1/2">
              <button type="button" onclick="hideScheduleView()" 
                class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 py-2.5 px-4 rounded-lg font-medium transition-all border border-blue-300">
                <i class="fas fa-times mr-2"></i>Cancel
              </button>
              <button type="submit"
                class="flex-1 bg-blue-700 hover:bg-blue-800 text-white py-2.5 px-4 rounded-lg font-medium transition-all shadow-sm">
                <i class="fas fa-paper-plane mr-2"></i>Submit Request
              </button>
            </div>

            <!-- Info -->
            <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 md:w-1/2 flex items-center">
              <div class="flex items-center text-sm text-slate-600">
                <i class="fas fa-info-circle text-slate-400 mr-2"></i>
                <span>Requests are processed within 3-5 business days.</span>
              </div>
            </div>
          </div>
        </form>
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

/* Input Focus */
#scheduleView input:focus, 
#scheduleView select:focus, 
#scheduleView textarea:focus {
    transform: translateY(-1px);
    box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.1);
}

/* File Upload */
#scheduleView #fileDropArea.drag-over {
    border-color: #64748b;
    background-color: #f8fafc;
}

/* File selected state */
#scheduleView #fileDropArea.file-selected {
    border-color: #10b981;
    background-color: #ecfdf5;
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
@media (max-width: 768px) {
    #scheduleView .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    #scheduleView .md\\:flex-row {
        flex-direction: column;
    }
    
    #scheduleView .md\:w-1\/2 {
        width: 100%;
    }
}

/* Enhanced file upload area for horizontal layout */
#scheduleView #fileDropArea {
    min-height: 80px;
}

/* Grid gap responsive */
@media (min-width: 768px) {
    #scheduleView .gap-6 {
        gap: 1.5rem;
    }
}
</style>

<script>
// File Upload
document.getElementById('fileInput').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    const fileNameDisplay = document.getElementById('fileName');
    const fileDropArea = document.getElementById('fileDropArea');
    
    if (fileName) {
        fileNameDisplay.textContent = `✓ ${fileName}`;
        fileNameDisplay.classList.remove('hidden');
        fileDropArea.classList.add('file-selected');
    } else {
        fileNameDisplay.classList.add('hidden');
        fileDropArea.classList.remove('file-selected');
    }
});

// Drag & Drop
const fileDropArea = document.getElementById('fileDropArea');
const fileInput = document.getElementById('fileInput');

['dragover', 'dragenter'].forEach(eventName => {
    fileDropArea.addEventListener(eventName, (e) => {
        e.preventDefault();
        fileDropArea.classList.add('drag-over');
    });
});

['dragleave', 'drop'].forEach(eventName => {
    fileDropArea.addEventListener(eventName, (e) => {
        e.preventDefault();
        fileDropArea.classList.remove('drag-over');
    });
});

fileDropArea.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

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

// Enhanced responsive behavior
function handleResize() {
    const isMobile = window.innerWidth < 768;
    const fileDropArea = document.getElementById('fileDropArea');
    
    if (fileDropArea && fileDropArea.querySelector('.flex')) {
        if (isMobile) {
            fileDropArea.querySelector('.flex').classList.remove('md:flex-row');
            fileDropArea.querySelector('.flex').classList.add('flex-col');
        } else {
            fileDropArea.querySelector('.flex').classList.add('md:flex-row');
            fileDropArea.querySelector('.flex').classList.remove('flex-col');
        }
    }
}

window.addEventListener('resize', handleResize);
document.addEventListener('DOMContentLoaded', handleResize);

// Hide schedule view function
function hideScheduleView() {
    document.getElementById('scheduleView').classList.add('hidden');
}
</script>