<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    // Debug logging for form submission
    error_log("OT Request Debug - Form submitted via POST");
    error_log("OT Request Debug - POST data: " . print_r($_POST, true));
    error_log("OT Request Debug - FILES data: " . print_r($_FILES, true));
    error_log("OT Request Debug - Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'not set'));
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("OT Request Debug - CSRF token validation failed");
        error_log("OT Request Debug - POST CSRF token: " . ($_POST['csrf_token'] ?? 'not set'));
        $error_message = "Invalid CSRF token. Please refresh the page and try again.";
    } else {
        error_log("OT Request Debug - CSRF token validation passed");
        $ot_date = $_POST['ot_date'];
        $time_in = $_POST['time_in'];
        $time_out = $_POST['time_out'];
        $ot_type = $_POST['ot_type'];
        $reason = $_POST['reason'];
        
        // Debug logging
        error_log("OT Request Debug - Date: " . $ot_date);
        error_log("OT Request Debug - Time In: " . $time_in);
        error_log("OT Request Debug - Time Out: " . $time_out);
        error_log("OT Request Debug - OT Type: " . $ot_type);
        error_log("OT Request Debug - Reason: '" . $reason . "'");
        error_log("OT Request Debug - Reason length: " . strlen($reason));
        error_log("OT Request Debug - Reason trimmed: '" . trim($reason) . "'");
        
        // Validate required fields
        error_log("OT Request Debug - Validating required fields");
        error_log("OT Request Debug - OT Date: " . ($ot_date ?? 'null'));
        error_log("OT Request Debug - Time In: " . ($time_in ?? 'null'));
        error_log("OT Request Debug - Time Out: " . ($time_out ?? 'null'));
        error_log("OT Request Debug - OT Type: " . ($ot_type ?? 'null'));
        error_log("OT Request Debug - Reason: " . ($reason ?? 'null'));
        
        if (empty($ot_date) || empty($time_in) || empty($time_out) || empty($ot_type)) {
            error_log("OT Request Debug - Required fields validation failed");
            $error_message = "Date, time in, time out, and overtime type are required.";
        } elseif (empty(trim($reason))) {
            error_log("OT Request Debug - Reason validation failed");
            $error_message = "Please provide a reason for your overtime request.";
        } else {
            error_log("OT Request Debug - All required fields validation passed");
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
                    
                    // Proceed with database insertion for all valid OT types
                    error_log("OT Request Debug - Starting database insertion process");
                    try {
                        // Debug logging for database insertion
                        error_log("OT Request Debug - About to insert into database");
                        error_log("OT Request Debug - Employee ID: " . $employee_id);
                        error_log("OT Request Debug - OT Date: " . $ot_date);
                        error_log("OT Request Debug - Time In: " . $time_in);
                        error_log("OT Request Debug - Time Out: " . $time_out);
                        error_log("OT Request Debug - OT Duration: " . round($ot_duration, 2));
                        error_log("OT Request Debug - OT Type: " . $ot_type);
                        error_log("OT Request Debug - Reason: " . $reason);
                        error_log("OT Request Debug - Attachment: " . ($attachment ?? 'none'));
                        
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
                        
                        error_log("OT Request Debug - Time Log ID: " . ($time_log_id ?? 'null'));
                        
                        // Insert into post_ot_requests table
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO post_ot_requests (
                                employee_id, time_log_id, time_in, time_out, ot_duration, 
                                ot_type, attachment, reason, status, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                        ");
                        
                        error_log("OT Request Debug - About to execute INSERT statement");
                        $insert_stmt->execute([
                            $employee_id, $time_log_id, $time_in, $time_out, 
                            round($ot_duration, 2), $ot_type, $attachment, $reason
                        ]);
                        
                        error_log("OT Request Debug - Database insertion successful");
                        $success_message = "Overtime request submitted successfully!";
                        
                        // Refresh history
                        $history_stmt->execute([$employee_id]);
                        $overtime_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                    } catch (Exception $e) {
                        error_log("OT Request Database Error: " . $e->getMessage());
                        error_log("OT Request Database Error - Stack trace: " . $e->getTraceAsString());
                        $error_message = "Error submitting request: " . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<div id="overtimeView" class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 hidden">
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
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
                                           readonly
                                           class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base bg-gray-50 cursor-not-allowed">
                                    <p class="text-sm text-gray-500 mt-3" id="time_in_help">Automatically filled from your time log (not editable)</p>
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
                                           readonly
                                           class="w-full px-6 py-4 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-base bg-gray-50 cursor-not-allowed">
                                    <p class="text-sm text-gray-500 mt-3" id="time_out_help">Automatically filled from your time log (not editable)</p>
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
                                          placeholder="Please provide a detailed explanation for your overtime work..."
                                          onchange="console.log('Reason field changed:', this.value)"
                                          onblur="console.log('Reason field blur:', this.value)"></textarea>
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
                                
                                                                 <!-- Real-time validation feedback -->
                                 <div id="validation_feedback" class="mt-4 p-4 rounded-lg hidden">
                                     <div id="reason_feedback" class="text-sm font-medium"></div>
                                 </div>
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

                                         <!-- Recent Requests with Pagination -->
                     <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                         <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                             <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                 <i class="fas fa-history text-gray-600 mr-2"></i>
                                 Overtime Requests
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
                                 <!-- Pagination Controls -->
                                 <?php
                                 $total_requests = count($overtime_history);
                                 $requests_per_page = 5;
                                 $total_pages = ceil($total_requests / $requests_per_page);
                                 $current_page = isset($_GET['ot_page']) ? max(1, min($total_pages, intval($_GET['ot_page']))) : 1;
                                 $start_index = ($current_page - 1) * $requests_per_page;
                                 $end_index = min($start_index + $requests_per_page, $total_requests);
                                 $current_requests = array_slice($overtime_history, $start_index, $requests_per_page);
                                 ?>
                                 
                                 <!-- Page Info -->
                                 <div class="text-xs text-gray-500 mb-3 text-center">
                                     Showing <?= $start_index + 1 ?>-<?= $end_index ?> of <?= $total_requests ?> requests
                                 </div>
                                 
                                 <!-- Requests List -->
                                 <div class="space-y-3">
                                     <?php foreach ($current_requests as $request): ?>
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
                                 
                                 <!-- Pagination Navigation -->
                                 <?php if ($total_pages > 1): ?>
                                     <div class="mt-4 flex items-center justify-center space-x-2">
                                         <!-- Previous Page -->
                                         <?php if ($current_page > 1): ?>
                                             <a href="?ot_page=<?= $current_page - 1 ?>" 
                                                class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                                                 <i class="fas fa-chevron-left mr-1"></i>Prev
                                             </a>
                                         <?php endif; ?>
                                         
                                         <!-- Page Numbers -->
                                         <?php
                                         $start_page = max(1, $current_page - 2);
                                         $end_page = min($total_pages, $current_page + 2);
                                         
                                         for ($i = $start_page; $i <= $end_page; $i++):
                                         ?>
                                             <a href="?ot_page=<?= $i ?>" 
                                                class="px-3 py-1 text-xs rounded-md transition-colors <?= $i === $current_page ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                                                 <?= $i ?>
                                             </a>
                                         <?php endfor; ?>
                                         
                                         <!-- Next Page -->
                                         <?php if ($current_page < $total_pages): ?>
                                             <a href="?ot_page=<?= $current_page + 1 ?>" 
                                                class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                                                 Next<i class="fas fa-chevron-right ml-1"></i>
                                             </a>
                                         <?php endif; ?>
                                     </div>
                                     
                                     <!-- Jump to Page -->
                                     <div class="mt-3 text-center">
                                         <form method="GET" class="inline-flex items-center space-x-2">
                                             <span class="text-xs text-gray-500">Go to page:</span>
                                             <input type="number" name="ot_page" min="1" max="<?= $total_pages ?>" 
                                                    value="<?= $current_page ?>" 
                                                    class="w-16 px-2 py-1 text-xs border border-gray-300 rounded-md">
                                             <button type="submit" class="px-2 py-1 text-xs bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                                 Go
                                             </button>
                                         </form>
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
                    
                    // Always auto-fill time fields from time log data
                    document.getElementById('time_in').value = dateData.time_in;
                    document.getElementById('time_out').value = dateData.time_out;
                    
                    // Show work hours
                    document.getElementById('work_hours').textContent = dateData.work_hours;
                    document.getElementById('work_hours_display').classList.remove('hidden');
                    
                    // Show overtime duration display
                    document.getElementById('ot_duration_display').classList.remove('hidden');
                    calculateOTDuration();
                    
                    // Update OT type eligibility
                    updateOTTypeEligibility(dateData.can_regular_ot);
                } else {
                    // Hide displays if no date selected
                    document.getElementById('work_hours_display').classList.add('hidden');
                    document.getElementById('ot_duration_display').classList.add('hidden');
                    
                    // Clear time fields
                    document.getElementById('time_in').value = '';
                    document.getElementById('time_out').value = '';
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
                    // Use the actual work hours from the selected date data
                    const selectedDate = document.getElementById('ot_date').value;
                    if (selectedDate && dateTimeData[selectedDate]) {
                        const workHours = dateTimeData[selectedDate].work_hours;
                        otHours = Math.max(0, workHours - 8);
                        durationLabel.textContent = 'Overtime Duration (beyond 8 hours):';
                        calculationNote.textContent = `Regular OT: You worked ${workHours} hours, so overtime is ${workHours - 8} hours beyond the standard 8-hour workday.`;
                    } else {
                        otHours = Math.max(0, totalHours - 8);
                        durationLabel.textContent = 'Overtime Duration (beyond 8 hours):';
                        calculationNote.textContent = 'Regular OT: Hours worked beyond the standard 8-hour workday.';
                    }
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
            
            otTypeInfo.classList.remove('hidden');
            
            if (this.value === 'Regular OT') {
                regularOtInfo.classList.remove('hidden');
                restdayOtInfo.classList.add('hidden');
                
            } else if (this.value === 'Rest Day OT') {
                regularOtInfo.classList.add('hidden');
                restdayOtInfo.classList.remove('hidden');
                
            } else {
                regularOtInfo.classList.add('hidden');
                restdayOtInfo.classList.add('hidden');
            }
            
            // Show/hide overtime duration display and recalculate
            if (this.value) {
                document.getElementById('ot_duration_display').classList.remove('hidden');
                calculateOTDuration();
            } else {
                document.getElementById('ot_duration_display').classList.add('hidden');
            }
        });

        // Time fields are now readonly, so no need for change event listeners
        // Duration is calculated when date is selected and OT type changes

                 // Add real-time validation for reason field with visual feedback
         document.getElementById('reason').addEventListener('input', function() {
             const reasonValue = this.value;
             const feedbackDiv = document.getElementById('validation_feedback');
             const reasonFeedback = document.getElementById('reason_feedback');
             
             console.log('Reason field input event - Value:', reasonValue);
             console.log('Reason field trimmed value:', reasonValue.trim());
             console.log('Reason field length:', reasonValue.length);
             
             // Show real-time validation feedback
             if (!reasonValue || reasonValue.trim() === '') {
                 feedbackDiv.classList.remove('hidden');
                 feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 border border-red-200';
                 reasonFeedback.className = 'text-sm font-medium text-red-800';
                 reasonFeedback.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Please provide a reason for your overtime request.';
             } else {
                 feedbackDiv.classList.remove('hidden');
                 feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-green-50 border border-green-200';
                 reasonFeedback.className = 'text-sm font-medium text-green-800';
                 reasonFeedback.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Reason field is valid.';
             }
         });

        // Calculate duration on page load if time fields have values
        if (document.getElementById('time_in').value && document.getElementById('time_out').value) {
            calculateOTDuration();
        }

        // Debug: Log form elements on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DOM LOADED ===');
            console.log('Form elements loaded:');
            
            const reasonField = document.getElementById('reason');
            const form = document.getElementById('overtimeForm');
            
            console.log('Form element:', form);
            console.log('Reason field:', reasonField);
            
            if (reasonField) {
                console.log('Reason field value:', reasonField.value);
                console.log('Reason field type:', reasonField.type);
                console.log('Reason field name:', reasonField.name);
                console.log('Reason field required:', reasonField.required);
            } else {
                console.log('ERROR: Reason field not found!');
            }
            
            if (form) {
                console.log('Form method:', form.method);
                console.log('Form action:', form.action);
                console.log('Form enctype:', form.enctype);
            } else {
                console.log('ERROR: Form not found!');
            }
            
            console.log('=== END DOM LOADED ===');
        });

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

        

                 // Form validation - Wait for DOM to be fully loaded
         document.addEventListener('DOMContentLoaded', function() {
             const overtimeForm = document.getElementById('overtimeForm');
             
             if (overtimeForm) {
                 // Form validation and submission
                 overtimeForm.addEventListener('submit', function(e) {
                     console.log('=== FORM SUBMISSION STARTED ===');
                     
                     // Capture all form values at submission time to ensure we have the latest data
                     const formData = new FormData(this);
                     const reasonValue = formData.get('reason') || '';
                     
                     console.log('FormData reason value:', reasonValue);
                     console.log('FormData reason trimmed:', reasonValue.trim());
                     
                     // Store the reason value globally so validateForm can access it
                     window.currentReasonValue = reasonValue;
                     
                     const validationResult = validateForm();
                     if (!validationResult.isValid) {
                         e.preventDefault(); // Prevent submission if validation fails
                         alert(validationResult.message);
                         return false;
                     }
                     
                     console.log('=== ALL VALIDATIONS PASSED ===');
                     
                     // Show confirmation dialog
                     if (!confirm('Are you sure you want to submit this overtime request?')) {
                         e.preventDefault(); // Prevent submission if user cancels
                         return false;
                     }
                     
                     // Set loading state
                     setLoadingState(true);
                     
                     // Allow form to submit naturally - don't prevent default
                     console.log('=== FORM SUBMITTING NATURALLY ===');
                 });
             } else {
                 console.error('Overtime form not found!');
             }
         });
        
                 // Form validation function with proper element existence checks
         function validateForm() {
             // First check if all required elements exist
             const timeInElement = document.getElementById('time_in');
             const timeOutElement = document.getElementById('time_out');
             const otTypeElement = document.getElementById('ot_type');
             const selectedDateElement = document.getElementById('ot_date');
             const reasonElement = document.getElementById('reason');
             
             // Check if elements exist before accessing their values
             if (!timeInElement || !timeOutElement || !otTypeElement || !selectedDateElement || !reasonElement) {
                 console.error('One or more form elements not found!');
                 return { isValid: false, message: 'Form elements not properly loaded. Please refresh the page and try again.' };
             }
             
             const timeIn = timeInElement.value;
             const timeOut = timeOutElement.value;
             const otType = otTypeElement.value;
             const selectedDate = selectedDateElement.value;
             
             // Get reason value - prioritize the captured value from FormData, fallback to element value
             let reason = window.currentReasonValue || reasonElement.value || '';
             
             console.log('Using reason value from:', window.currentReasonValue ? 'FormData' : 'DOM element');
             console.log('Final reason value:', reason);
             
             // Additional safety check - if reason is still empty, try to get it again
             if (!reason && reasonElement) {
                 console.log('Reason was empty, trying to get value again...');
                 const retryReason = reasonElement.value;
                 console.log('Retry reason value:', retryReason);
                 if (retryReason && retryReason.trim()) {
                     reason = retryReason;
                 }
             }
            
            console.log('Form validation - Date:', selectedDate);
            console.log('Form validation - Time In:', timeIn);
            console.log('Form validation - Time Out:', timeOut);
            console.log('Form validation - OT Type:', otType);
            console.log('Form validation - Reason value:', reason);
            console.log('Form validation - Reason length:', reason ? reason.length : 'undefined');
            console.log('Form validation - Reason trimmed:', reason ? reason.trim() : 'undefined');
            console.log('Form validation - Reason empty check:', !reason);
            console.log('Form validation - Reason trimmed empty check:', reason ? !reason.trim() : 'undefined');
            
            // Validate required fields
            if (!selectedDate) {
                return { isValid: false, message: 'Please select a date for your overtime request.' };
            }
            
            if (!otType) {
                return { isValid: false, message: 'Please select an overtime type.' };
            }
            
            if (!timeIn || !timeOut) {
                return { isValid: false, message: 'Please select a date to automatically fill the time fields.' };
            }
            
            // Enhanced reason validation with better debugging
            if (!reason) {
                console.log('Reason validation failed: reason is null/undefined');
                return { isValid: false, message: 'Please provide a reason for your overtime request.' };
            }
            
            if (typeof reason !== 'string') {
                console.log('Reason validation failed: reason is not a string, type:', typeof reason);
                return { isValid: false, message: 'Please provide a reason for your overtime request.' };
            }
            
            if (reason.trim() === '') {
                console.log('Reason validation failed: reason is only whitespace');
                return { isValid: false, message: 'Please provide a reason for your overtime request.' };
            }
            
            console.log('Reason validation passed:', reason);
            
            // Validate time duration
            if (timeIn && timeOut) {
                const timeInDt = new Date('2000-01-01 ' + timeIn);
                const timeOutDt = new Date('2000-01-01 ' + timeOut);
                
                if (timeOutDt <= timeInDt) {
                    timeOutDt.setDate(timeOutDt.getDate() + 1);
                }
                
                const diffMs = timeOutDt - timeInDt;
                const totalHours = diffMs / (1000 * 60 * 60);
                
                // Calculate actual overtime hours based on OT type
                let otHours = 0;
                if (otType === 'Regular OT') {
                    // For Regular OT, overtime is hours beyond 8
                    const selectedDateData = dateTimeData[selectedDate];
                    if (selectedDateData) {
                        const workHours = selectedDateData.work_hours;
                        otHours = Math.max(0, workHours - 8);
                    } else {
                        otHours = Math.max(0, totalHours - 8);
                    }
                } else {
                    // For Rest Day OT and Holiday OT, all hours are overtime
                    otHours = totalHours;
                }
                
                // Validate minimum overtime duration (30 minutes)
                if (otHours < 0.5) {
                    return { isValid: false, message: 'Overtime duration must be at least 30 minutes.' };
                }
                
                // Validate maximum overtime duration (14 hours)
                if (otHours > 14) {
                    return { isValid: false, message: 'Overtime duration cannot exceed 14 hours. Please check your time entries.' };
                }
            }
            
            // Check if Regular OT is selected but not eligible
            if (otType === 'Regular OT') {
                const selectedDateData = dateTimeData[selectedDate];
                if (selectedDateData && !selectedDateData.can_regular_ot) {
                    return { isValid: false, message: 'Regular OT is not available for this date. You worked less than 8 hours. Please select "Rest Day OT" instead.' };
                }
            }
            
            return { isValid: true, message: 'Validation passed' };
        }
    </script>