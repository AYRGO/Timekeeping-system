<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['employee']['id'])) {
    header("Location: ../employee/login.php");
    exit;
}

$employee_id = $_SESSION['employee']['id'];

// Get employee details
$stmt = $pdo->prepare("SELECT fname, lname, email, position, company FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

// Get available dates with time logs for the employee (last 30 days)
$available_dates_sql = "SELECT DISTINCT 
                           DATE(log_date) as log_date,
                           MIN(time_in) as time_in,
                           MAX(time_out) as time_out,
                           TIMESTAMPDIFF(MINUTE, MIN(time_in), MAX(time_out)) as total_minutes
                        FROM time_logs 
                        WHERE employee_id = ? 
                        AND log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        AND time_in IS NOT NULL 
                        AND time_out IS NOT NULL
                        GROUP BY DATE(log_date)
                        ORDER BY log_date DESC";
$available_dates_stmt = $pdo->prepare($available_dates_sql);
$available_dates_stmt->execute([$employee_id]);
$available_dates = $available_dates_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create array of available dates for Flatpickr
$available_dates_array = [];
$date_time_data = [];
foreach ($available_dates as $date_data) {
    $available_dates_array[] = $date_data['log_date'];
    
    // Calculate work hours (minus 1 hour lunch)
    $work_hours = ($date_data['total_minutes'] / 60) - 1;
    $work_hours = max(0, $work_hours);
    
    $date_time_data[$date_data['log_date']] = [
        'time_in' => $date_data['time_in'],
        'time_out' => $date_data['time_out'],
        'work_hours' => round($work_hours, 2),
        'can_regular_ot' => $work_hours >= 8
    ];
}

// Get overtime request history
$history_sql = "SELECT ot.*, tl.log_date, tl.time_in, tl.time_out,
                       DATE_FORMAT(ot.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at,
                       DATE_FORMAT(tl.log_date, '%M %d, %Y') as formatted_log_date,
                       DATE_FORMAT(tl.time_in, '%h:%i %p') as formatted_time_in,
                       DATE_FORMAT(tl.time_out, '%h:%i %p') as formatted_time_out,
                       ot.status as request_status
                FROM post_ot_requests ot 
                LEFT JOIN time_logs tl ON ot.time_log_id = tl.id 
                WHERE ot.employee_id = ? 
                ORDER BY ot.created_at DESC LIMIT 20";
$history_stmt = $pdo->prepare($history_sql);
$history_stmt->execute([$employee_id]);
$overtime_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ot_date = $_POST['ot_date'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];
    $ot_type = $_POST['ot_type'];
    $reason = trim($_POST['reason']);
    
    // Validate required fields
    if (empty($ot_date) || empty($time_in) || empty($time_out) || empty($ot_type) || empty($reason)) {
        $error_message = "All fields are required.";
    } else {
        // Validate that the selected date has time logs
        $date_exists = false;
        foreach ($available_dates as $date_data) {
            if ($date_data['log_date'] === $ot_date) {
                $date_exists = true;
                $selected_date_data = $date_data;
                break;
            }
        }
        
        if (!$date_exists) {
            $error_message = "Selected date does not have time logs. Please select a valid date.";
        } else {
            // Calculate overtime duration
            $time_in_dt = new DateTime($time_in);
            $time_out_dt = new DateTime($time_out);
            
            if ($time_out_dt < $time_in_dt) {
                $time_out_dt->modify('+1 day');
            }
            
            $interval = $time_in_dt->diff($time_out_dt);
            $ot_duration = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            
            // Validate Regular OT - must work 8+ hours
            $is_regular_ot_eligible = true;
            if ($ot_type === 'Regular OT') {
                // Calculate work hours from the selected date data
                $work_hours = ($selected_date_data['total_minutes'] / 60) - 1; // Deduct 1 hour lunch
                $work_hours = max(0, $work_hours);
                
                $is_regular_ot_eligible = $work_hours >= 8;
            }
            
            if (!$is_regular_ot_eligible && $ot_type === 'Regular OT') {
                $error_message = "Regular OT is not available. You must work at least 8 hours before requesting regular overtime. Please select 'Rest Day OT' instead.";
            } else {
                // Handle file upload
                $attachment = null;
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/overtime_attachments/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = 'ot_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                            $attachment = $filename;
                        }
                    }
                }
            }
            
            try {
                // Get time_log_id for the selected date
                $time_log_stmt = $pdo->prepare("
                    SELECT id FROM time_logs 
                    WHERE employee_id = ? AND DATE(log_date) = ? 
                    ORDER BY time_in ASC 
                    LIMIT 1
                ");
                $time_log_stmt->execute([$employee_id, $ot_date]);
                $time_log = $time_log_stmt->fetch();
                $time_log_id = $time_log ? $time_log['id'] : null;
                
                // Insert into post_ot_requests table
                $insert_stmt = $pdo->prepare("
                    INSERT INTO post_ot_requests (
                        employee_id, time_log_id, time_in, time_out, ot_duration, 
                        ot_type, attachment, reason, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                
                $insert_stmt->execute([
                    $employee_id, $time_log_id, $time_in, $time_out, 
                    round($ot_duration, 2), $ot_type, $attachment, $reason
                ]);
                
                $success_message = "Overtime request submitted successfully!";
                
                // Refresh history
                $history_stmt->execute([$employee_id]);
                $overtime_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $error_message = "Error submitting request: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-white text-lg"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-2xl font-bold text-gray-900">Overtime Management</h1>
                            <p class="text-sm text-gray-600">Submit and manage your overtime requests</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($employee['position']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success_message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error_message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                <!-- Overtime Request Form -->
                <div class="xl:col-span-3">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                                Submit New Overtime Request
                            </h2>
                            <p class="text-sm text-gray-600 mt-2">Fill out the form below to submit your overtime request</p>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="p-8 space-y-8" id="overtimeForm">
                            <!-- Date Selection -->
                            <div>
                                <label for="ot_date" class="block text-base font-medium text-gray-700 mb-3">
                                    <i class="fas fa-calendar text-blue-600 mr-2"></i>
                                    Select Date <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="ot_date" 
                                       name="ot_date" 
                                       required
                                       class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base"
                                       placeholder="Click to select date">
                                <p class="text-sm text-gray-500 mt-3 flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Choose the date for your overtime request (only dates with time logs are available)
                                </p>
                            </div>

                            <!-- Time Selection -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="time_in" class="block text-base font-medium text-gray-700 mb-3">
                                        <i class="fas fa-sign-in-alt text-green-600 mr-2"></i>
                                        Time In <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" 
                                           id="time_in" 
                                           name="time_in" 
                                           required
                                           class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base">
                                    <p class="text-sm text-gray-500 mt-3" id="time_in_help">Automatically filled from your time log</p>
                                </div>
                                
                                <div>
                                    <label for="time_out" class="block text-base font-medium text-gray-700 mb-3">
                                        <i class="fas fa-sign-out-alt text-red-600 mr-2"></i>
                                        Time Out <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" 
                                           id="time_out" 
                                           name="time_out" 
                                           required
                                           class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base">
                                    <p class="text-sm text-gray-500 mt-3" id="time_out_help">Automatically filled from your time log</p>
                                </div>
                            </div>

                            <!-- Work Hours Display -->
                            <div id="work_hours_display" class="hidden bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-green-600 mr-3 text-lg"></i>
                                    <span class="text-base font-medium text-green-800">
                                        Work Hours on Selected Date: <span id="work_hours" class="font-bold text-lg">0</span> hours
                                    </span>
                                </div>
                            </div>

                            <!-- Overtime Duration Display -->
                            <div id="ot_duration_display" class="hidden bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-blue-600 mr-3 text-lg"></i>
                                    <span class="text-base font-medium text-blue-800">
                                        <span id="ot_duration_label">Overtime Duration:</span> <span id="duration_hours" class="font-bold text-lg">0</span> hours
                                    </span>
                                </div>
                                <div class="mt-3 text-sm text-blue-600">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <span id="ot_calculation_note">This shows the calculated overtime hours based on your selected times.</span>
                                </div>
                            </div>

                            <!-- Overtime Type -->
                            <div>
                                <label for="ot_type" class="block text-base font-medium text-gray-700 mb-3">
                                    <i class="fas fa-tag text-purple-600 mr-2"></i>
                                    Overtime Type <span class="text-red-500">*</span>
                                </label>
                                <select id="ot_type" 
                                        name="ot_type" 
                                        required
                                        class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base">
                                    <option value="">Select overtime type</option>
                                    <option value="Regular OT">Regular OT</option>
                                    <option value="Rest Day OT">Rest Day OT</option>
                                    <option value="Special Holiday OT">Special Holiday OT</option>
                                    <option value="Regular Holiday OT">Regular Holiday OT</option>
                                </select>
                                
                                <!-- OT Type Information -->
                                <div id="ot_type_info" class="mt-4 p-5 bg-gray-50 rounded-lg text-sm text-gray-600 hidden">
                                    <div id="regular_ot_info" class="hidden">
                                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                        <strong>Regular OT:</strong> You must work at least 8 hours on the selected date to be eligible.
                                    </div>
                                    <div id="restday_ot_info" class="hidden">
                                        <i class="fas fa-info-circle text-green-600 mr-1"></i>
                                        <strong>Rest Day OT:</strong> Available regardless of hours worked on the selected date.
                                    </div>
                                </div>
                            </div>

                            <!-- Reason -->
                            <div>
                                <label for="reason" class="block text-base font-medium text-gray-700 mb-3">
                                    <i class="fas fa-comment text-orange-600 mr-2"></i>
                                    Reason for Overtime <span class="text-red-500">*</span>
                                </label>
                                <textarea id="reason" 
                                          name="reason" 
                                          rows="5" 
                                          required
                                          class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base"
                                          placeholder="Please provide a detailed explanation for your overtime work..."></textarea>
                                <p class="text-sm text-gray-500 mt-3">Explain why overtime is necessary</p>
                            </div>

                            <!-- Attachment -->
                            <div>
                                <label for="attachment" class="block text-base font-medium text-gray-700 mb-3">
                                    <i class="fas fa-paperclip text-gray-600 mr-2"></i>
                                    Supporting Document
                                </label>
                                <input type="file" 
                                       id="attachment" 
                                       name="attachment" 
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base file:mr-4 file:py-3 file:px-6 file:rounded-md file:border-0 file:text-base file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-sm text-gray-500 mt-3">Upload PDF, JPG, or PNG (optional but recommended)</p>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-8 border-t border-gray-200">
                                <button type="submit" 
                                        id="submitBtn"
                                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-4 px-8 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed text-lg">
                                    <i class="fas fa-paper-plane mr-3" id="submitIcon"></i>
                                    <span id="submitText">Submit Overtime Request</span>
                                    <i class="fas fa-spinner fa-spin hidden mr-3" id="loadingIcon"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar - Recent Requests & Info -->
                <div class="space-y-8">
                    <!-- Quick Info Card -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            Overtime Guidelines
                        </h3>
                        <div class="space-y-3 text-sm text-gray-600">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                                <span><strong>Regular OT:</strong> Must work 8+ hours on the selected date</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                                <span><strong>Rest Day OT:</strong> Available regardless of hours worked</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-clock text-blue-500 mr-2 mt-0.5"></i>
                                <span>Requests must be submitted within 5 days</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-file-alt text-purple-500 mr-2 mt-0.5"></i>
                                <span>Supporting documents are recommended</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Requests -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-history text-gray-600 mr-2"></i>
                                Recent Requests
                            </h3>
                        </div>
                        <div class="p-6">
                            <?php if (empty($overtime_history)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-inbox text-gray-400 text-3xl mb-3"></i>
                                    <p class="text-sm text-gray-500">No overtime requests yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Submit your first request above</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach (array_slice($overtime_history, 0, 5) as $request): ?>
                                        <div class="border-l-4 border-blue-500 pl-4 py-3 bg-gray-50 rounded-r-lg">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="text-sm font-semibold text-gray-900">
                                                        <?= htmlspecialchars($request['ot_type']) ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <?= $request['formatted_log_date'] ?? 'N/A' ?>
                                                    </div>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        Duration: <?= $request['ot_duration'] ?? '0' ?> hours
                                                    </div>
                                                </div>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                                    <?php
                                                    $status = strtolower($request['request_status'] ?? $request['status'] ?? 'pending');
                                                    switch($status) {
                                                        case 'approved':
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'rejected':
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                        default:
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                    }
                                                    ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($overtime_history) > 5): ?>
                                    <div class="mt-4 text-center">
                                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            View All Requests
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // PHP data for available dates and time information
        const availableDates = <?= json_encode($available_dates_array) ?>;
        const dateTimeData = <?= json_encode($date_time_data) ?>;
        
        // Initialize Flatpickr for date selection
        flatpickr("#ot_date", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            minDate: new Date().fp_incr(-30), // Allow dates up to 30 days ago
            enable: availableDates, // Only enable dates with time logs
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr && dateTimeData[dateStr]) {
                    const dateData = dateTimeData[dateStr];
                    const otType = document.getElementById('ot_type').value;
                    
                    // Only auto-fill time fields for Regular OT or when no OT type is selected
                    if (otType === '' || otType === 'Regular OT') {
                        document.getElementById('time_in').value = dateData.time_in;
                        document.getElementById('time_out').value = dateData.time_out;
                    }
                    
                    // Show work hours
                    document.getElementById('work_hours').textContent = dateData.work_hours;
                    document.getElementById('work_hours_display').classList.remove('hidden');
                    
                    // Only show overtime duration if OT type is selected
                    if (otType) {
                        document.getElementById('ot_duration_display').classList.remove('hidden');
                        calculateOTDuration();
                    }
                    
                    // Update OT type eligibility
                    updateOTTypeEligibility(dateData.can_regular_ot);
                } else {
                    // Hide displays if no date selected
                    document.getElementById('work_hours_display').classList.add('hidden');
                    document.getElementById('ot_duration_display').classList.add('hidden');
                    
                    // Clear time fields only if not Rest Day OT
                    const currentOtType = document.getElementById('ot_type').value;
                    if (currentOtType !== 'Rest Day OT') {
                        document.getElementById('time_in').value = '';
                        document.getElementById('time_out').value = '';
                    }
                }
            }
        });

        // Calculate overtime duration when times change
        function calculateOTDuration() {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;
            const otType = document.getElementById('ot_type').value;
            const durationDisplay = document.getElementById('ot_duration_display');
            const durationHours = document.getElementById('duration_hours');
            const durationLabel = document.getElementById('ot_duration_label');
            const calculationNote = document.getElementById('ot_calculation_note');
            
            if (timeIn && timeOut) {
                const timeInDt = new Date('2000-01-01 ' + timeIn);
                const timeOutDt = new Date('2000-01-01 ' + timeOut);
                
                if (timeOutDt <= timeInDt) {
                    timeOutDt.setDate(timeOutDt.getDate() + 1);
                }
                
                const diffMs = timeOutDt - timeInDt;
                const totalHours = diffMs / (1000 * 60 * 60);
                
                let otHours = 0;
                
                if (otType === 'Regular OT') {
                    // For Regular OT, calculate overtime beyond 8 hours
                    otHours = Math.max(0, totalHours - 8);
                    durationLabel.textContent = 'Overtime Duration (beyond 8 hours):';
                    calculationNote.textContent = 'Regular OT: Hours worked beyond the standard 8-hour workday.';
                } else if (otType === 'Rest Day OT') {
                    // For Rest Day OT, the entire duration is overtime
                    otHours = totalHours;
                    durationLabel.textContent = 'Total Overtime Duration:';
                    calculationNote.textContent = 'Rest Day OT: All hours worked on rest days are considered overtime.';
                } else {
                    // For other OT types, use total hours as overtime
                    otHours = totalHours;
                    durationLabel.textContent = 'Overtime Duration:';
                    calculationNote.textContent = 'Holiday OT: All hours worked on holidays are considered overtime.';
                }
                
                // Ensure minimum duration is 0.01 hours (about 36 seconds)
                const finalHours = Math.max(otHours, 0.01);
                
                durationHours.textContent = finalHours.toFixed(2);
                durationDisplay.classList.remove('hidden');
            } else {
                durationDisplay.classList.add('hidden');
            }
        }

        // Function to update OT type eligibility based on work hours
        function updateOTTypeEligibility(canRegularOT) {
            const regularOTOption = document.querySelector('option[value="Regular OT"]');
            const regularOTInfo = document.getElementById('regular_ot_info');
            
            if (canRegularOT) {
                regularOTOption.disabled = false;
                regularOTOption.textContent = 'Regular OT';
                regularOTInfo.innerHTML = '<i class="fas fa-info-circle text-blue-600 mr-1"></i><strong>Regular OT:</strong> You must work at least 8 hours on the selected date to be eligible.';
            } else {
                regularOTOption.disabled = true;
                regularOTOption.textContent = 'Regular OT (Not eligible - below 8 hours)';
                regularOTInfo.innerHTML = '<i class="fas fa-exclamation-triangle text-orange-600 mr-1"></i><strong>Regular OT:</strong> Not available. You worked less than 8 hours on this date. Please select "Rest Day OT" instead.';
            }
        }

        // Show/hide OT type information based on selection
        document.getElementById('ot_type').addEventListener('change', function() {
            const otTypeInfo = document.getElementById('ot_type_info');
            const regularOtInfo = document.getElementById('regular_ot_info');
            const restdayOtInfo = document.getElementById('restday_ot_info');
            const timeIn = document.getElementById('time_in');
            const timeOut = document.getElementById('time_out');
            const timeInHelp = document.getElementById('time_in_help');
            const timeOutHelp = document.getElementById('time_out_help');
            
            otTypeInfo.classList.remove('hidden');
            
            if (this.value === 'Regular OT') {
                regularOtInfo.classList.remove('hidden');
                restdayOtInfo.classList.add('hidden');
                
                // For Regular OT, make time fields readonly and auto-filled
                timeIn.readOnly = true;
                timeOut.readOnly = true;
                timeIn.classList.add('bg-gray-50');
                timeOut.classList.add('bg-gray-50');
                timeInHelp.textContent = 'Automatically filled from your time log';
                timeOutHelp.textContent = 'Automatically filled from your time log';
                
            } else if (this.value === 'Rest Day OT') {
                regularOtInfo.classList.add('hidden');
                restdayOtInfo.classList.remove('hidden');
                
                // For Rest Day OT, make time fields editable
                timeIn.readOnly = false;
                timeOut.readOnly = false;
                timeIn.classList.remove('bg-gray-50');
                timeOut.classList.remove('bg-gray-50');
                timeInHelp.textContent = 'Enter your overtime start time';
                timeOutHelp.textContent = 'Enter your overtime end time';
                
                // Clear time fields for manual entry
                timeIn.value = '';
                timeOut.value = '';
                
            } else {
                regularOtInfo.classList.add('hidden');
                restdayOtInfo.classList.add('hidden');
                
                // Reset to default state
                timeIn.readOnly = true;
                timeOut.readOnly = true;
                timeIn.classList.add('bg-gray-50');
                timeOut.classList.add('bg-gray-50');
                timeInHelp.textContent = 'Automatically filled from your time log';
                timeOutHelp.textContent = 'Automatically filled from your time log';
            }
            
            // Show/hide overtime duration display and recalculate
            if (this.value) {
                document.getElementById('ot_duration_display').classList.remove('hidden');
                calculateOTDuration();
            } else {
                document.getElementById('ot_duration_display').classList.add('hidden');
            }
        });

        // Add event listeners for time fields to recalculate duration
        document.getElementById('time_in').addEventListener('change', calculateOTDuration);
        document.getElementById('time_out').addEventListener('change', calculateOTDuration);

        // Calculate duration on page load if time fields have values
        if (document.getElementById('time_in').value && document.getElementById('time_out').value) {
            calculateOTDuration();
        }

        // Loading state management
        function setLoadingState(loading) {
            const submitBtn = document.getElementById('submitBtn');
            const submitIcon = document.getElementById('submitIcon');
            const submitText = document.getElementById('submitText');
            const loadingIcon = document.getElementById('loadingIcon');
            
            if (loading) {
                submitBtn.disabled = true;
                submitIcon.classList.add('hidden');
                submitText.textContent = 'Submitting...';
                loadingIcon.classList.remove('hidden');
            } else {
                submitBtn.disabled = false;
                submitIcon.classList.remove('hidden');
                submitText.textContent = 'Submit Overtime Request';
                loadingIcon.classList.add('hidden');
            }
        }

        // Form validation
        document.getElementById('overtimeForm').addEventListener('submit', function(e) {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;
            const otType = document.getElementById('ot_type').value;
            const selectedDate = document.getElementById('ot_date').value;
            const reason = document.getElementById('reason').value.trim();
            
            // Validate required fields
            if (!selectedDate) {
                e.preventDefault();
                alert('Please select a date for your overtime request.');
                return false;
            }
            
            if (!otType) {
                e.preventDefault();
                alert('Please select an overtime type.');
                return false;
            }
            
            if (!timeIn || !timeOut) {
                e.preventDefault();
                alert('Please enter both start and end times for your overtime.');
                return false;
            }
            
            if (!reason) {
                e.preventDefault();
                alert('Please provide a reason for your overtime request.');
                return false;
            }
            
            // Validate time duration
            if (timeIn && timeOut) {
                const timeInDt = new Date('2000-01-01 ' + timeIn);
                const timeOutDt = new Date('2000-01-01 ' + timeOut);
                
                if (timeOutDt <= timeInDt) {
                    timeOutDt.setDate(timeOutDt.getDate() + 1);
                }
                
                const diffMs = timeOutDt - timeInDt;
                const diffHours = diffMs / (1000 * 60 * 60);
                
                if (diffHours < 0.5) {
                    e.preventDefault();
                    alert('Overtime duration must be at least 30 minutes.');
                    return false;
                }
                
                if (diffHours > 12) {
                    e.preventDefault();
                    alert('Overtime duration cannot exceed 12 hours. Please check your time entries.');
                    return false;
                }
            }
            
            // Check if Regular OT is selected but not eligible
            if (otType === 'Regular OT') {
                const selectedDateData = dateTimeData[selectedDate];
                if (selectedDateData && !selectedDateData.can_regular_ot) {
                    e.preventDefault();
                    alert('Regular OT is not available for this date. You worked less than 8 hours. Please select "Rest Day OT" instead.');
                    return false;
                }
            }
            
            // Show confirmation dialog
            if (!confirm('Are you sure you want to submit this overtime request?')) {
                e.preventDefault();
                return false;
            }
            
            // Set loading state
            setLoadingState(true);
        });
    </script>