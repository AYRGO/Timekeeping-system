<!-- Profile Section -->

<div id="profileView" class="hidden min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100">

  <!-- Main Content Container -->
  <div class="max-w-7xl mx-auto px-6 py-12">
    
    <!-- Hero Section -->
    <div style="background: linear-gradient(135deg, rgb(16, 185, 72) 0%, rgb(5, 101, 211) 100%);" class="text-white rounded-2xl p-8 mb-8">
      <div class="flex flex-col lg:flex-row items-center gap-8 w-full">
        <!-- Profile Picture -->
        <div class="relative">
          <div class="w-32 h-32 lg:w-40 lg:h-40 rounded-full overflow-hidden border-4 border-white shadow-2xl bg-white">
            <?php if ($profile_picture): ?>
              <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-full h-full object-cover" alt="Profile">
            <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-6xl text-gray-400">
                <i class="fas fa-user"></i>
              </div>
            <?php endif; ?>

            <div 
              class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm flex items-center justify-center opacity-0 hover:opacity-100 transition-all duration-300 cursor-pointer rounded-full"
              onclick="document.getElementById('fileInput').click()"
            >
              <span class="bg-white text-blue-600 px-3 py-1 text-xs rounded-full font-semibold hover:bg-blue-50 transition shadow-lg">
                <i class="fas fa-camera mr-1"></i>Change
              </span>
            </div>
          </div>

          <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
            <input type="file" id="fileInput" name="profile_picture" class="hidden" onchange="this.form.submit()">
          </form>
        </div>

        <!-- Profile Info -->
        <div class="text-center lg:text-left flex-1">
          <h1 class="text-4xl lg:text-5xl font-bold mb-2"><?= htmlspecialchars($fname . ' ' . $lname) ?></h1>
          <p class="text-xl lg:text-2xl text-blue-100 font-medium mb-2"><?= htmlspecialchars($position) ?></p>
          <p class="text-lg text-blue-200 mb-4"><?= htmlspecialchars($company) ?></p>
          <div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-6">
            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-4 py-2">
              <span class="text-sm font-medium">Employee ID</span>
              <p class="text-lg font-bold">EMP-<?= str_pad($employee_id ?? '001', 3, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg px-4 py-2">
              <span class="text-sm font-medium">Status</span>
              <p class="text-lg font-bold text-green-300">Active</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-col gap-3">
          <button onclick="openEditModal()" class="bg-white text-blue-600 px-6 py-3 rounded-xl hover:bg-blue-50 transition-all duration-200 font-semibold shadow-lg flex items-center">
            <i class="fas fa-edit mr-2"></i>Edit Profile
          </button>
          <button class="bg-blue-800 bg-opacity-50 backdrop-blur-sm text-white px-6 py-3 rounded-xl hover:bg-opacity-70 transition-all duration-200 font-semibold border border-white border-opacity-20">
            <i class="fas fa-download mr-2"></i>Download CV
          </button>
        </div>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Personal Information Card -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-2xl font-bold text-gray-900">Personal Information</h2>
                <p class="text-gray-600 mt-1">Manage your personal details and contact information</p>
              </div>
              <div class="bg-blue-100 p-3 rounded-xl">
                <i class="fas fa-user text-blue-600 text-xl"></i>
              </div>
            </div>
          </div>
          
          <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <?php
                $fields = [
                  'First Name' => ['value' => $fname, 'icon' => 'fas fa-user'],
                  'Last Name' => ['value' => $lname, 'icon' => 'fas fa-user'],
                  'Email Address' => ['value' => $email, 'icon' => 'fas fa-envelope'],
                  'Mobile Number' => ['value' => $contact, 'icon' => 'fas fa-phone'],
                  'Position' => ['value' => $position, 'icon' => 'fas fa-briefcase'],
                  'Company' => ['value' => $company, 'icon' => 'fas fa-building']
                ];
                foreach ($fields as $label => $data):
              ?>
              <div class="group">
                <div class="flex items-center mb-2">
                  <div class="w-8 h-8 bg-gray-100 group-hover:bg-blue-100 rounded-lg flex items-center justify-center mr-3 transition-colors">
                    <i class="<?= $data['icon'] ?> text-gray-600 group-hover:text-blue-600 text-sm transition-colors"></i>
                  </div>
                  <span class="text-sm font-semibold text-gray-500 uppercase tracking-wide"><?= $label ?></span>
                </div>
                <p class="text-lg font-medium text-gray-900 ml-11"><?= htmlspecialchars($data['value']) ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats Card -->
      <div class="space-y-6">
        <!-- Profile Completion -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Profile Completion</h3>
            <div class="bg-green-100 p-2 rounded-lg">
              <i class="fas fa-chart-pie text-green-600"></i>
            </div>
          </div>
          <?php
            $completedFields = 0;
            $totalFields = 6;
            foreach ($fields as $field) {
              if (!empty($field['value'])) $completedFields++;
            }
            $completionPercentage = ($completedFields / $totalFields) * 100;
          ?>
          <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-600">Completed</span>
              <span class="text-sm font-bold text-gray-900"><?= round($completionPercentage) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
              <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300" style="width: <?= $completionPercentage ?>%"></div>
            </div>
          </div>
          <p class="text-sm text-gray-600"><?= $completedFields ?> of <?= $totalFields ?> fields completed</p>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <button class="w-full text-left bg-gray-50 hover:bg-blue-50 p-4 rounded-xl transition-all duration-200 group">
              <div class="flex items-center">
                <div class="bg-blue-100 group-hover:bg-blue-200 p-2 rounded-lg mr-3">
                  <i class="fas fa-key text-blue-600 text-sm"></i>
                </div>
                <div>
                  <p class="font-medium text-gray-900">Change Password</p>
                  <p class="text-sm text-gray-600">Update your account security</p>
                </div>
              </div>
            </button>
            
            <button class="w-full text-left bg-gray-50 hover:bg-purple-50 p-4 rounded-xl transition-all duration-200 group">
              <div class="flex items-center">
                <div class="bg-purple-100 group-hover:bg-purple-200 p-2 rounded-lg mr-3">
                  <i class="fas fa-bell text-purple-600 text-sm"></i>
                </div>
                <div>
                  <p class="font-medium text-gray-900">Notification Settings</p>
                  <p class="text-sm text-gray-600">Manage your preferences</p>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 201 Checklist Section -->
    <div class="mt-12">
      <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-8 py-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">201 File Checklist</h2>
              <p class="text-gray-600 mt-1">Track your document submission progress</p>
            </div>
            <div class="bg-green-100 p-3 rounded-xl">
              <i class="fas fa-clipboard-check text-green-600 text-xl"></i>
            </div>
          </div>
        </div>

        <div class="p-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- RSS Documents -->
            <div>
              <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-xl mr-4">
                  <i class="fas fa-building text-blue-600"></i>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">RSS Documents</h3>
                  <p class="text-gray-600">Company-specific documentation</p>
                </div>
              </div>
              
              <div class="space-y-4">
                <?php foreach (['letter_offer' => 'Signed Letter of Offer', 'employment_contract' => 'Signed Employment Contract'] as $key => $label): ?>
                <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="w-10 h-10 rounded-lg <?= !empty($checklist[$key]) ? 'bg-green-100' : 'bg-red-100' ?> flex items-center justify-center mr-3">
                        <i class="fas <?= !empty($checklist[$key]) ? 'fa-check text-green-600' : 'fa-times text-red-600' ?>"></i>
                      </div>
                      <div>
                        <p class="font-medium text-gray-900"><?= $label ?></p>
                        <p class="text-sm text-gray-600"><?= !empty($checklist[$key]) ? 'Submitted' : 'Pending submission' ?></p>
                      </div>
                    </div>
                    <div>
                      <?php if (!empty($checklist[$key])): ?>
                        <?php
                          $files = is_array($checklist[$key]) ? $checklist[$key] : explode(',', $checklist[$key]);
                          foreach ($files as $file):
                            $file = trim($file);
                            if ($file):
                        ?>
                          <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-eye mr-1"></i>View
                          </a>
                        <?php endif; endforeach; ?>
                      <?php else: ?>
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-lg text-sm font-medium">Missing</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>

                <!-- Employment Adjustment Form -->
                <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="w-10 h-10 rounded-lg <?= !empty($checklist['employment_adjustment_form']) ? 'bg-green-100' : 'bg-red-100' ?> flex items-center justify-center mr-3">
                        <i class="fas <?= !empty($checklist['employment_adjustment_form']) ? 'fa-check text-green-600' : 'fa-times text-red-600' ?>"></i>
                      </div>
                      <div>
                        <p class="font-medium text-gray-900">Employment Adjustment Form</p>
                        <p class="text-sm text-gray-600"><?= !empty($checklist['employment_adjustment_form']) ? 'Submitted' : 'Pending submission' ?></p>
                      </div>
                    </div>
                    <div>
                      <?php if (!empty($checklist['employment_adjustment_form'])): ?>
                        <?php
                          $files = is_array($checklist['employment_adjustment_form']) ? $checklist['employment_adjustment_form'] : explode(',', $checklist['employment_adjustment_form']);
                          foreach ($files as $file):
                            $file = trim($file);
                            if ($file):
                        ?>
                          <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-eye mr-1"></i>View
                          </a>
                        <?php endif; endforeach; ?>
                      <?php else: ?>
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-lg text-sm font-medium">Missing</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pre-Employment Requirements -->
            <div>
              <div class="flex items-center mb-6">
                <div class="bg-purple-100 p-3 rounded-xl mr-4">
                  <i class="fas fa-file-alt text-purple-600"></i>
                </div>
                <div>
                  <h3 class="text-xl font-bold text-gray-900">Pre-Employment Requirements</h3>
                  <p class="text-gray-600">Government and personal documents</p>
                </div>
              </div>

              <div class="space-y-3 max-h-96 overflow-y-auto">
                <?php
                $preItems = [
                  'medical' => 'Medical Certificate',
                  'nbi_clearance' => 'NBI Clearance',
                  'diploma_tor' => 'Diploma/TOR',
                  'psa' => 'PSA Birth Certificate',
                  'sss' => 'SSS ID/E1 Form',
                  'tin' => 'TIN ID/BIR Form',
                  'philhealth' => 'PhilHealth ID/MDR',
                  'pagibig' => 'Pag-IBIG ID/MDF',
                  'coe' => 'Certificate of Employment',
                  'valid_id' => 'Valid ID (Primary)',
                  'valid_id_2' => 'Valid ID (Secondary)',
                  'solo_parent_id' => 'Solo Parent ID',
                ];
                foreach ($preItems as $key => $label): ?>
                <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-lg <?= !empty($checklist[$key]) ? 'bg-green-100' : 'bg-red-100' ?> flex items-center justify-center mr-3">
                        <i class="fas <?= !empty($checklist[$key]) ? 'fa-check text-green-600 text-xs' : 'fa-times text-red-600 text-xs' ?>"></i>
                      </div>
                      <p class="font-medium text-gray-900 text-sm"><?= $label ?></p>
                    </div>
                    <div>
                      <?php if (!empty($checklist[$key])): ?>
                        <?php
                          $files = is_array($checklist[$key]) ? $checklist[$key] : explode(',', $checklist[$key]);
                          foreach ($files as $file):
                            $file = trim($file);
                            if ($file):
                        ?>
                          <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
                            <i class="fas fa-eye"></i>
                          </a>
                        <?php endif; endforeach; ?>
                      <?php else: ?>
                        <span class="text-red-600 text-xs">
                          <i class="fas fa-exclamation-triangle"></i>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Checklist Progress -->
          <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6">
            <?php
              $totalDocs = count(['letter_offer', 'employment_contract', 'employment_adjustment_form']) + count($preItems);
              $submittedDocs = 0;
              foreach (['letter_offer', 'employment_contract', 'employment_adjustment_form'] as $doc) {
                if (!empty($checklist[$doc])) $submittedDocs++;
              }
              foreach ($preItems as $key => $label) {
                if (!empty($checklist[$key])) $submittedDocs++;
              }
              $progressPercentage = ($submittedDocs / $totalDocs) * 100;
            ?>
            <div class="flex items-center justify-between mb-4">
              <div>
                <h4 class="text-lg font-bold text-gray-900">Overall Progress</h4>
                <p class="text-gray-600"><?= $submittedDocs ?> of <?= $totalDocs ?> documents submitted</p>
              </div>
              <div class="text-right">
                <p class="text-3xl font-bold text-blue-600"><?= round($progressPercentage) ?>%</p>
                <p class="text-sm text-gray-600">Complete</p>
              </div>
            </div>
            <div class="w-full bg-white rounded-full h-4 shadow-inner">
              <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-4 rounded-full transition-all duration-500 shadow-sm" style="width: <?= $progressPercentage ?>%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-11/12 md:w-1/3">
    <h4 class="text-xl font-semibold mb-4">Edit Profile</h4>
    
    <form id="edit-profile-form" method="POST" action="update_profile.php">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <!-- First Name -->
      <div class="mb-4">
        <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
        <input type="text" id="fname" name="fname" value="<?= htmlspecialchars($fname) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Last Name -->
      <div class="mb-4">
        <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
        <input type="text" id="lname" name="lname" value="<?= htmlspecialchars($lname) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Email -->
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Mobile Number -->
      <div class="mb-4">
        <label for="contact" class="block text-sm font-medium text-gray-700">Mobile Number</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($contact) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Position -->
      <div class="mb-4">
        <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
        <input type="text" id="position" name="position" value="<?= htmlspecialchars($position) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Company -->
      <div class="mb-4">
        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
        <input type="text" id="company" name="company" value="<?= htmlspecialchars($company) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Buttons -->
      <div class="flex justify-end">
        <button type="button" onclick="closeEditModal()" class="mr-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-11/12 md:w-1/3">
    <h4 class="text-xl font-semibold mb-4 text-gray-800">Confirm Update</h4>
    <p class="text-gray-600 mb-6">Are you sure you want to save the changes to your profile?</p>
    <div class="flex justify-end">
      <button onclick="closeConfirmModal()" class="mr-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
      <button onclick="submitProfileForm()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Yes, Save</button>
    </div>
  </div>
</div>
