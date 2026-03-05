<?php
session_start();
include('../config/db.php');

// Get employee ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid employee ID");
}
$employeeId = (int)$_GET['id'];

$adjustmentField = 'employment_adjustment_form';
$documents[$adjustmentField] = 'Employment Adjustment Form';

// Documents array reorganized
$documents = [
    // Basic Employment Documents
    'letter_offer' => 'Signed Letter of Offer',
    'employment_contract' => 'Signed Employment Contract',
    'employment_adjustment_form' => 'Employment Adjustment Form',
    
    // Medical & Clearances
    'medical' => 'Medical Certificate',
    'nbi_clearance' => 'NBI Clearance',
    
    // Educational Documents
    'diploma_tor' => 'Diploma / TOR',
    
    // Government IDs & Documents
    'psa' => 'PSA Birth Certificate',
    'sss' => 'SSS ID/E1 Form',
    'tin' => 'TIN ID/BIR Form',
    'philhealth' => 'PhilHealth ID/MDR',
    'pagibig' => 'Pagibig ID/MDF',
    
    // Valid IDs
    'valid_id' => 'Valid ID (Primary)',
    'Valid_id_2' => 'Valid ID (Secondary)',
    
    // Special Documents
    'solo_parent_id' => 'Solo Parent ID',
    'coe' => 'Certificate of Employment (Previous Employer)',
];

// Delete attachment
if (isset($_GET['delete_attachment']) && isset($_GET['field'])) {
    $field = $_GET['field'];
    if (array_key_exists($field, $documents)) {
        $stmt = $pdo->prepare("SELECT $field FROM employee_checklist WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $file = $stmt->fetchColumn();

        if ($file) {
            $filePath = '../uploads/checklist/' . $file;
            if (file_exists($filePath)) unlink($filePath);
            $stmt = $pdo->prepare("UPDATE employee_checklist SET $field = NULL, updated_at = NOW() WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
        }

        header("Location:employee-edit.php?id=$employeeId&deleted=1");
        exit;
    }
}

// Save form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Calendar Override Handlers (must be before any output for header redirects)
    if (isset($_POST['action']) && $_POST['action'] === 'add_override') {
        $schedule_employee_id = (int)$_POST['employee_id'];
        $schedule_date = $_POST['schedule_date'];
        
        $override_schedule_input = $_POST['override_schedule_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $override_type = $_POST['override_type'] ?? 'schedule_change';
        
        // Handle "OFF" option - set override_schedule_id to null for rest day
        if ($override_schedule_input === 'OFF' || $override_schedule_input === '') {
            $actual_schedule_id = null;
            $is_rest_day = 1;
        } else {
            $actual_schedule_id = (int)$override_schedule_input;
            $is_rest_day = 0;
        }
        
        // ADMIN OVERRIDE: Delete any existing schedule for this date (from ANY source)
        $pdo->prepare("DELETE FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ?")
            ->execute([$schedule_employee_id, $schedule_date]);
        
        $pdo->prepare("DELETE FROM employee_daily_schedule_cache WHERE employee_id = ? AND schedule_date = ?")
            ->execute([$schedule_employee_id, $schedule_date]);
        
        // Insert new daily override
        $stmt = $pdo->prepare("INSERT INTO employee_daily_schedules (employee_id, schedule_date, actual_schedule_id, is_rest_day, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$schedule_employee_id, $schedule_date, $actual_schedule_id, $is_rest_day, $reason]);
        
        // INSERT INTO CACHE
        if ($is_rest_day) {
            $pdo->prepare("INSERT INTO employee_daily_schedule_cache 
                (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
                VALUES (?, ?, NULL, 1, 0, NULL, NULL, NULL, NULL, 'admin_override', NULL, NOW(), NOW())")
                ->execute([$schedule_employee_id, $schedule_date]);
        } else {
            $schedStmt = $pdo->prepare("SELECT id, name, time_in, time_out FROM work_schedules WHERE id = ?");
            $schedStmt->execute([$actual_schedule_id]);
            $schedDetails = $schedStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($schedDetails) {
                $pdo->prepare("INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
                    VALUES (?, ?, ?, 0, 0, ?, ?, ?, NULL, 'admin_override', NULL, NOW(), NOW())")
                    ->execute([
                        $schedule_employee_id, 
                        $schedule_date, 
                        $schedDetails['id'],
                        $schedDetails['name'],
                        $schedDetails['time_in'],
                        $schedDetails['time_out']
                    ]);
            }
        }
        
        // Add to audit trail
        $pdo->prepare("INSERT INTO schedule_override_history (employee_id, schedule_date, new_schedule_id, override_reason, applied_by, applied_at) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([$schedule_employee_id, $schedule_date, $actual_schedule_id, $reason, $_SESSION['user_id'] ?? null]);
        
        $_SESSION['success_message'] = 'Schedule override created successfully!';
        header("Location: employee-edit.php?id={$employeeId}#current-schedule");
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete_override') {
        $schedule_employee_id = (int)$_POST['employee_id'];
        $schedule_date = $_POST['schedule_date'];
        
        $pdo->prepare("DELETE FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ?")
            ->execute([$schedule_employee_id, $schedule_date]);
        
        $pdo->prepare("DELETE FROM employee_daily_schedule_cache WHERE employee_id = ? AND schedule_date = ? AND source = 'admin_override'")
            ->execute([$schedule_employee_id, $schedule_date]);
        
        // REBUILD CACHE FROM DEFAULT SCHEDULE
        $dayOfWeek = date('w', strtotime($schedule_date));
        $weeklyStmt = $pdo->prepare("
            SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
            FROM employee_default_schedules edd
            LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
            WHERE edd.employee_id = ? AND edd.day_of_week = ?
        ");
        $weeklyStmt->execute([$schedule_employee_id, $dayOfWeek]);
        $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($weekly) {
            if ($weekly['is_rest_day']) {
                $pdo->prepare("INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
                    VALUES (?, ?, NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekly_default', NULL, NOW(), NOW())")
                    ->execute([$schedule_employee_id, $schedule_date]);
            } else {
                $pdo->prepare("INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at) 
                    VALUES (?, ?, ?, 0, 0, ?, ?, ?, NULL, 'weekly_default', NULL, NOW(), NOW())")
                    ->execute([
                        $schedule_employee_id, 
                        $schedule_date, 
                        $weekly['work_schedule_id'],
                        $weekly['name'],
                        $weekly['time_in'],
                        $weekly['time_out']
                    ]);
            }
        }
        
        $pdo->prepare("INSERT INTO schedule_override_history (employee_id, schedule_date, new_schedule_id, override_reason, applied_by, applied_at) VALUES (?, ?, NULL, 'Override cancelled by admin', ?, NOW())")
            ->execute([$schedule_employee_id, $schedule_date, $_SESSION['user_id'] ?? null]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Profile Update
    if (isset($_POST['update_profile'])) {
        $fname = $_POST['fname'] ?? '';
        $lname = $_POST['lname'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $position = $_POST['position'] ?? '';
        $status = $_POST['status'] ?? '';
        $company = $_POST['company'] ?? '';
        $empType = $_POST['emp_type'] ?? 'Probationary';
        
        // Get previous Emp_Type to detect regularization
        $stmt = $pdo->prepare("SELECT Emp_Type FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $previousEmpType = $stmt->fetchColumn();
        
        $wasRegularized = ($previousEmpType === 'Probationary' && $empType === 'Regular');

        if ($fname && $lname && $email) {
            $stmt = $pdo->prepare("UPDATE employees SET fname = ?, lname = ?, email = ?, contact = ?, position = ?, status = ?, company = ?, Emp_Type = ? WHERE id = ?");
            $stmt->execute([$fname, $lname, $email, $contact, $position, $status, $company, $empType, $employeeId]);
            
            // If employee was just regularized, grant VL and SL credits
            if ($wasRegularized) {
                $currentYear = (int)date('Y');
                
                // Grant 7.5 days Vacation Leave
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'vacation' AND year = ?");
                $stmt->execute([$employeeId, $currentYear]);
                $existingVL = $stmt->fetch();
                
                if ($existingVL) {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = 7.5, monthly_increment = 1.25, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$existingVL['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, 'vacation', 7.5, NULL, ?, 1.25, NOW())");
                    $stmt->execute([$employeeId, $currentYear]);
                }
                
                // Grant 5 days Sick Leave
                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'sick' AND year = ?");
                $stmt->execute([$employeeId, $currentYear]);
                $existingSL = $stmt->fetch();
                
                if ($existingSL) {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = 5.0, monthly_increment = 0, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$existingSL['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, monthly_increment, updated_at) VALUES (?, 'sick', 5.0, NULL, ?, 0, NOW())");
                    $stmt->execute([$employeeId, $currentYear]);
                }
                
                echo "<script>alert('Employee regularized successfully! Granted 7.5 days VL and 5 days SL.'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
            } else {
                echo "<script>alert('Employee updated successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
            }
            exit;
        } else {
            echo "<script>alert('Please fill in all required fields.');</script>";
        }
    }

    // Update Schedule
    if (isset($_POST['update_schedule'])) {
        $newSchedule = $_POST['official_sched'] ?? '';
        
        if ($newSchedule) {
            $stmt = $pdo->prepare("UPDATE employees SET official_sched = ? WHERE id = ?");
            $stmt->execute([$newSchedule, $employeeId]);
            echo "<script>alert('Schedule updated successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
            exit;
        } else {
            echo "<script>alert('Please select a schedule.');</script>";
        }
    }

    // 201 Checklist Upload
    if (isset($_POST['upload_documents'])) {
        $uploadDir = '../uploads/checklist/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $uploadedFiles = [];

        foreach ($documents as $field => $label) {
            if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'][0])) {
                $filenames = [];

                // Ensure it's multiple files
                $files = $_FILES[$field];
                $fileCount = is_array($files['name']) ? count($files['name']) : 0;

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $filename = uniqid($field . "_") . "_" . basename($files['name'][$i]);
                        move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename);
                        $filenames[] = $filename;
                    }
                }

                if (!empty($filenames)) {
                    // Store filenames as comma-separated string
                    $uploadedFiles[$field] = implode(',', $filenames);
                }
            }
        }

        if ($uploadedFiles) {
            $stmt = $pdo->prepare("SELECT id FROM employee_checklist WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            $exists = $stmt->fetch();

            $columns = array_keys($uploadedFiles);
            $values = array_values($uploadedFiles); 

            if ($exists) {
                $sets = [];
                foreach ($columns as $col) $sets[] = "$col = ?";
                $values[] = $employeeId;
                $stmt = $pdo->prepare("UPDATE employee_checklist SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE employee_id = ?");
                $stmt->execute($values);
            } else {
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                array_unshift($values, $employeeId);
                $stmt = $pdo->prepare("INSERT INTO employee_checklist (employee_id, " . implode(', ', $columns) . ") VALUES (?, $placeholders)");
                $stmt->execute($values);
            }
        }

        echo "<script>alert('Documents uploaded successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
        exit;
    }

    // Update leave credits
    if (isset($_POST['update_credits']) && isset($_POST['credits'])) {
        foreach ($_POST['credits'] as $leaveType => $data) {
            $balance = is_numeric($data['balance']) ? floatval($data['balance']) : null;
            $monthlyIncrement = is_numeric($data['monthly_increment']) ? floatval($data['monthly_increment']) : null;
            $carryOver = isset($data['carry_over']) && is_numeric($data['carry_over']) ? floatval($data['carry_over']) : null;

            // Make sure a record exists (insert if not)
            $check = $pdo->prepare("SELECT COUNT(*) FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type = ?");
            $check->execute([$employeeId, date('Y'), $leaveType]);
            if ($check->fetchColumn() == 0) {
                $insert = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, year) VALUES (?, ?, ?)");
                $insert->execute([$employeeId, $leaveType, date('Y')]);
            }

            // Update with new values
            $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, monthly_increment = ?, carry_over = ?, updated_at = NOW() WHERE employee_id = ? AND year = ? AND leave_type = ?");
            $stmt->execute([
                $balance,
                $monthlyIncrement,
                $leaveType === 'vacation' ? $carryOver : null,
                $employeeId,
                date('Y'),
                $leaveType
            ]);
        }

        echo "<script>alert('Leave credits updated successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
        exit;
    }

    // Update Default Weekly Schedule (using day_of_week structure)
    if (isset($_POST['action']) && $_POST['action'] === 'update_weekly_schedule' && isset($_POST['weekly_sched'])) {
        $weekly_sched = $_POST['weekly_sched'];
        $days = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];
        
        // Delete existing default schedules for this employee
        $pdo->prepare("DELETE FROM employee_default_schedules WHERE employee_id = ?")->execute([$employeeId]);
        
        // Insert new weekly schedule (one row per day)
        $stmt = $pdo->prepare("INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($days as $dayName => $dayNum) {
            $scheduleId = $weekly_sched[$dayName] ?? null;
            
            if ($scheduleId === 'OFF' || $scheduleId === '' || $scheduleId === null) {
                // Rest day
                $stmt->execute([$employeeId, $dayNum, null, 1, date('Y-m-d')]);
            } else {
                // Regular schedule
                $stmt->execute([$employeeId, $dayNum, (int)$scheduleId, 0, date('Y-m-d')]);
            }
        }
        
        // Rebuild cache for this employee (next 1 month only - for performance)
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+1 month'));
        
        // Delete existing cache entries for this date range
        $pdo->prepare("DELETE FROM employee_daily_schedule_cache WHERE employee_id = ? AND schedule_date BETWEEN ? AND ?")
            ->execute([$employeeId, $startDate, $endDate]);
        
        // Fetch all default schedules for this employee at once
        $schedStmt = $pdo->prepare("
            SELECT eds.day_of_week, eds.work_schedule_id, eds.is_rest_day, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ?
        ");
        $schedStmt->execute([$employeeId]);
        $weeklySchedules = [];
        while ($row = $schedStmt->fetch(PDO::FETCH_ASSOC)) {
            $weeklySchedules[$row['day_of_week']] = $row;
        }
        
        // Prepare batch insert statement
        $cacheStmt = $pdo->prepare("
            INSERT INTO employee_daily_schedule_cache 
            (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, schedule_name, time_in, time_out, holiday_name, source, source_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'weekly_default', NULL, NOW())
        ");
        
        // Build cache day by day
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dayOfWeek = date('w', strtotime($currentDate)); // 0 (Sunday) to 6 (Saturday)
            
            if (isset($weeklySchedules[$dayOfWeek])) {
                $defaultSched = $weeklySchedules[$dayOfWeek];
                $cacheStmt->execute([
                    $employeeId,
                    $currentDate,
                    $defaultSched['work_schedule_id'],
                    $defaultSched['is_rest_day'],
                    0, // is_holiday
                    $defaultSched['schedule_name'],
                    $defaultSched['time_in'],
                    $defaultSched['time_out'],
                    null // holiday_name
                ]);
            }
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        // Note: schedule_override_history table uses different columns (schedule_date, original_schedule_id, new_schedule_id, override_reason, applied_by)
        // Skipping history logging for weekly schedule updates since it's for daily overrides
        
        $_SESSION['success_message'] = 'Weekly schedule updated successfully!';
        header("Location: employee-edit.php?id=$employeeId#current-schedule");
        exit;
    }
    
    // Assign Rotating Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'assign_rotating_schedule') {
        $pattern_id = intval($_POST['pattern_id']);
        $start_date = $_POST['start_date'];
        $cycle_start_date = $_POST['cycle_start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // End any existing rotating schedules
        $pdo->prepare("UPDATE employee_rotating_schedules SET end_date = ? WHERE employee_id = ? AND (end_date IS NULL OR end_date > ?)")
            ->execute([date('Y-m-d', strtotime($start_date . ' -1 day')), $employeeId, $start_date]);
        
        // Insert new rotating schedule
        $stmt = $pdo->prepare("INSERT INTO employee_rotating_schedules (employee_id, pattern_id, start_date, cycle_start_date, end_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$employeeId, $pattern_id, $start_date, $cycle_start_date, $end_date]);
        
        // Note: Skipping history logging - table structure incompatible with rotating schedule tracking
        
        echo "<script>alert('Rotating schedule assigned!'); window.location.href = 'employee-edit.php?id=$employeeId#rotating-schedule';</script>";
        exit;
    }
    
    // Remove Rotating Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'remove_rotating_schedule') {
        $rotating_id = intval($_POST['rotating_id']);
        
        // Set end date to today
        $pdo->prepare("UPDATE employee_rotating_schedules SET end_date = ? WHERE id = ? AND employee_id = ?")
            ->execute([date('Y-m-d'), $rotating_id, $employeeId]);
        
        // Note: Skipping history logging - table structure incompatible
        
        echo "<script>alert('Rotating schedule removed!'); window.location.href = 'employee-edit.php?id=$employeeId#rotating-schedule';</script>";
        exit;
    }
    
    // Add New Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        $name = $_POST['schedule_name'] ?? null;
        
        if ($time_in && $time_out) {
            $stmt = $pdo->prepare("INSERT INTO work_schedules (name, time_in, time_out) VALUES (?, ?, ?)");
            $stmt->execute([$name, $time_in, $time_out]);
            echo "<script>alert('Schedule added successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
            exit;
        } else {
            echo "<script>alert('Please provide both time in and time out.');</script>";
        }
    }
    
    // Edit Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'edit_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? 0;
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        $name = $_POST['schedule_name'] ?? null;
        
        if ($schedule_id && $time_in && $time_out) {
            $stmt = $pdo->prepare("UPDATE work_schedules SET name = ?, time_in = ?, time_out = ? WHERE id = ?");
            $stmt->execute([$name, $time_in, $time_out, $schedule_id]);
            echo "<script>alert('Schedule updated successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid schedule data.');</script>";
        }
    }
    
    // Delete Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'delete_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? 0;
        
        if ($schedule_id) {
            // Check if any employee is using this schedule
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM employees WHERE official_sched = ?");
            $checkStmt->execute([$schedule_id]);
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                echo "<script>alert('Cannot delete schedule. It is currently assigned to $count employee(s).');</script>";
            } else {
                $stmt = $pdo->prepare("DELETE FROM work_schedules WHERE id = ?");
                $stmt->execute([$schedule_id]);
                echo "<script>alert('Schedule deleted successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
                exit;
            }
        }
    }
}

// Fetch employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) die("Employee not found");

$stmt = $pdo->prepare("SELECT * FROM employee_checklist WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all employee requests (from both pending and processed/archived tables)
// Leave Requests
$stmt = $pdo->prepare("
    SELECT *, 'pending' as source FROM leave_requests WHERE employee_id = ?
    UNION ALL
    SELECT *, 'processed' as source FROM post_leave_requests WHERE employee_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$employeeId, $employeeId]);
$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Schedule Change Requests
$stmt = $pdo->prepare("
    SELECT *, 'pending' as source FROM schedule_change_requests WHERE employee_id = ?
    UNION ALL
    SELECT *, 'processed' as source FROM post_schedule_change_requests WHERE employee_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$employeeId, $employeeId]);
$scheduleRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overtime Requests (from active and archived tables)
$stmt = $pdo->prepare("
    SELECT *, 'active' as source FROM post_ot_requests WHERE employee_id = ?
    UNION ALL
    SELECT *, 'archived' as source FROM post2_overtime_requests WHERE employee_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$employeeId, $employeeId]);
$overtimeRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Time Adjustment Requests
$stmt = $pdo->prepare("
    SELECT *, 'pending' as source FROM time_adjustment_requests WHERE employee_id = ?
    UNION ALL
    SELECT *, 'processed' as source FROM post_time_adjustment_requests WHERE employee_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$employeeId, $employeeId]);
$timeAdjustmentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all schedules from work_schedules table
$stmt = $pdo->prepare("SELECT * FROM work_schedules ORDER BY id ASC");
$stmt->execute();
$allSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function for status badges
function getStatusBadge($status) {
    switch($status) {
        case 'approved':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Approved
                    </span>';
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i>Pending
                    </span>';
        case 'rejected':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Rejected
                    </span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($status) . '</span>';
    }
}

$leaveTypes = ['sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'bereavement'];

// Schedule options
$scheduleOptions = [
    1 => ['in' => '06:30 AM', 'out' => '03:30 PM'],
    2 => ['in' => '08:00 AM', 'out' => '07:00 PM'],
    3 => ['in' => '07:30 AM', 'out' => '04:30 PM'],
    4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
    5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
    6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
    7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
    8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
    9 => ['in' => '08:00 AM', 'out' => '04:30 PM'],
    10 => ['in' => '07:40 AM', 'out' => '04:40 PM'],
    11 => ['in' => '06:30 AM', 'out' => '03:00 PM'],
    12 => ['in' => '06:30 AM', 'out' => '05:30 PM'],
    13 => ['in' => '07:00 AM', 'out' => '06:00 PM'],
    14 => ['in' => '06:00 AM', 'out' => '05:00 PM'],
    15 => ['in' => '06:00 AM', 'out' => '04:00 PM'],
    16 => ['in' => '08:30 AM', 'out' => '04:30 PM'],
    17 => ['in' => '06:00 AM', 'out' => '12:00 PM'],
    18 => ['in' => '06:00 AM', 'out' => '02:30 PM'],
                            19 => ['in' => '07:00 PM', 'out' => '03:00 AM'],    
                            20 => ['in' => '07:00 PM', 'out' => '04:30 AM'],
                            21 => ['in' => '05:00 PM', 'out' => '02:00 AM'],
                            22 => ['in' => '05:30 PM', 'out' => '02:00 AM'],
];  

// Sort schedule options by 'in' time (earliest to latest)
function timeToSortable($time) {
    // Handles both AM/PM and 24hr formats
    $dt = DateTime::createFromFormat('h:i A', $time);
    if (!$dt) $dt = DateTime::createFromFormat('H:i:s', $time);
    if (!$dt) $dt = DateTime::createFromFormat('H:i', $time);
    return $dt ? $dt->format('H:i:s') : $time;
}
$sortedScheduleOptions = $scheduleOptions;
uasort($sortedScheduleOptions, function($a, $b) {
    return strcmp(timeToSortable($a['in']), timeToSortable($b['in']));
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Employee Profile - <?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
  <style>
    /* Hide all tab content by default */
    .tab-content {
      display: none;
    }
    
    /* Show active tab content */
    .tab-content.active {
      display: block;
    }
    
    /* Active tab button styles */
    .tab-button.active {
      color: #2563eb;
      border-bottom-color: #2563eb;
    }
  </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php 
        $pageTitle = "Employee Profile - " . htmlspecialchars($employee['fname'] . ' ' . $employee['lname']);
        include('../views/header.php'); 
        ?>
        
        <main class="flex-1 p-6 overflow-y-auto">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md shadow-md" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200">
                        <?php if (!empty($employee['profile_picture'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($employee['profile_picture']) ?>" 
                                 alt="Profile picture" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                                <?= strtoupper(substr($employee['fname'], 0, 1) . substr($employee['lname'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></h1>
                                <p class="text-gray-600"><?= htmlspecialchars($employee['position']) ?></p>
                            </div>
                            <!-- Edit Profile button removed from header - use the one in Profile tab instead -->
                        </div>
                        
                        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Employee ID</p>
                                <p class="font-medium">EMP-<?= str_pad($employee['id'], 5, '0', STR_PAD_LEFT) ?></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium"><?= htmlspecialchars($employee['email']) ?></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Status</p>
                                <p class="font-medium">
                                    <span class="text-green-600">
                                        <?= htmlspecialchars($employee['status']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-sm text-gray-500">Contact</p>
                                <p class="font-medium"><?= htmlspecialchars($employee['contact']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="flex overflow-x-auto border-b border-gray-200 mb-6">
                <!-- Schedule Management Group -->
                <button onclick="openTab(event, 'current-schedule')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition active" id="default-tab">
                    <i class="fas fa-calendar-check mr-2"></i>Current Schedule
                </button>
                <button onclick="openTab(event, 'weekly-schedule')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-calendar-week mr-2"></i>Weekly Schedule
                </button>
                <button onclick="openTab(event, 'manage-schedules')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-cog mr-2"></i>Manage Schedules (<?= count($allSchedules) ?>)
                </button>
                
                <!-- Divider -->
                <div class="border-r border-gray-300 mx-2"></div>
                
                <!-- Employee Info & Records Group -->
                <button onclick="openTab(event, 'profile')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-user mr-2"></i>Profile
                </button>
                <button onclick="openTab(event, 'checklist')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-tasks mr-2"></i>201 Checklist
                </button>
                <button onclick="openTab(event, 'leave-credits')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-calendar-alt mr-2"></i>Leave Credits
                </button>
                
                <!-- Divider -->
                <div class="border-r border-gray-300 mx-2"></div>
                
                <!-- Requests & Approvals Group -->
                <button onclick="openTab(event, 'leave-requests')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-plane mr-2"></i>Leave Requests (<?= count($leaveRequests) ?>)
                </button>
                <button onclick="openTab(event, 'schedule-changes')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-clock mr-2"></i>Schedule Changes (<?= count($scheduleRequests) ?>)
                </button>
                <button onclick="openTab(event, 'overtime-requests')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-business-time mr-2"></i>Overtime (<?= count($overtimeRequests) ?>)
                </button>
                <button onclick="openTab(event, 'time-adjustments')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-history mr-2"></i>Time Adjustments (<?= count($timeAdjustmentRequests) ?>)
                </button>
            </div>
            
            <!-- Tab Contents -->
            <div id="profile" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Personal Information</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="displayInfo">
                        <div>
                            <p class="text-sm text-gray-500">First Name</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['fname']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Last Name</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['lname']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Contact Number</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['contact']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Position</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['position']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Company</p>
                            <p class="font-medium"><?= htmlspecialchars($employee['company'] ?? 'Not specified') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <p class="font-medium">
                                <span class="<?= $employee['status'] === 'Active' ? 'text-green-600' : 'text-gray-600' ?>">
                                    <?= htmlspecialchars($employee['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Employment Type</p>
                            <p class="font-medium">
                                <?php 
                                $empType = $employee['Emp_Type'] ?? 'Probationary';
                                $colorClass = 'text-orange-600';
                                $displayText = $empType;
                                
                                if ($empType === 'Regular') {
                                    $colorClass = 'text-blue-600 font-semibold';
                                    $displayText = 'Regular';
                                } elseif ($empType === 'Probationary') {
                                    $colorClass = 'text-orange-600';
                                    $displayText = 'Probationary';
                                }
                                ?>
                                <span class="<?= $colorClass ?>">
                                    <?= htmlspecialchars($displayText) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Edit Form (Initially Hidden) -->
                    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6" id="editForm" style="display: none;">
                        <input type="hidden" name="update_profile" value="1">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input name="fname" type="text" required value="<?= htmlspecialchars($employee['fname']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input name="lname" type="text" required value="<?= htmlspecialchars($employee['lname']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input name="email" type="email" required value="<?= htmlspecialchars($employee['email']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                            <input name="contact" type="text" value="<?= htmlspecialchars($employee['contact']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                            <input name="position" type="text" value="<?= htmlspecialchars($employee['position']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                            <input name="company" type="text" value="<?= htmlspecialchars($employee['company'] ?? '') ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Active" <?= $employee['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $employee['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Employment Type 
                                <span class="text-xs text-gray-500">(Regular gets 7.5 VL + 5 SL immediately)</span>
                            </label>
                            <select id="empTypeSelect" name="emp_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Probationary" <?= ($employee['Emp_Type'] ?? 'Probationary') === 'Probationary' ? 'selected' : '' ?>>Probationary</option>
                                <option value="Regular" <?= ($employee['Emp_Type'] ?? 'Probationary') === 'Regular' ? 'selected' : '' ?>>Regular</option>
                            </select>
                        </div>

                        <div class="md:col-span-2 flex justify-between items-center pt-4">
                            <button type="button" onclick="toggleEditMode()" class="text-sm text-gray-600 hover:underline">Cancel</button>
                            <button type="submit" id="updateProfileBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>

                    <!-- Edit Button -->
                    <div class="mt-6 flex justify-end" id="editButton">
                        <button type="button" onclick="event.stopPropagation(); toggleEditMode();" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                            <i class="fas fa-edit mr-2"></i>Edit Profile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule Tab - Admin Assignable Schedule -->
            <div id="weekly-schedule" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Default Weekly Schedule</h2>
                    <?php
                    // Fetch current weekly schedule for employee (using day_of_week structure)
                    $weeklySchedStmt = $pdo->prepare("SELECT day_of_week, work_schedule_id, is_rest_day FROM employee_default_schedules WHERE employee_id = ? ORDER BY day_of_week");
                    $weeklySchedStmt->execute([$employeeId]);
                    $weeklyRows = $weeklySchedStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Convert to associative array by day name
                    $weekly = [];
                    $dayMapping = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                    foreach ($weeklyRows as $row) {
                        $dayName = $dayMapping[$row['day_of_week']];
                        $weekly[$dayName] = $row['is_rest_day'] ? null : $row['work_schedule_id'];
                    }
                    
                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    // Get all work schedules
                    $allSchedules = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <form method="post" action="employee-edit.php?id=<?= $employeeId ?>" class="space-y-4" id="weeklyScheduleForm">
                        <input type="hidden" name="action" value="update_weekly_schedule">
                        
                        <!-- Hidden fields for schedule IDs -->
                        <?php foreach($days as $d): ?>
                            <input type="hidden" name="weekly_sched[<?= $d ?>]" id="schedule_id_<?= $d ?>" value="<?= $weekly[$d] ?? '' ?>">
                        <?php endforeach; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($days as $d): 
                            // Get current schedule details
                            $currentSchedule = null;
                            if (isset($weekly[$d]) && $weekly[$d]) {
                                foreach($allSchedules as $sched) {
                                    if ($sched['id'] == $weekly[$d]) {
                                        $currentSchedule = $sched;
                                        break;
                                    }
                                }
                            }
                            $currentValue = $currentSchedule ? htmlspecialchars($currentSchedule['name']) . ' (' . date('g:i A', strtotime($currentSchedule['time_in'])) . ' - ' . date('g:i A', strtotime($currentSchedule['time_out'])) . ')' : '';
                        ?>
                            <div class="schedule-input-wrapper">
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?= $d ?></label>
                                <input type="text" 
                                       class="schedule-autocomplete w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       data-day="<?= $d ?>"
                                       value="<?= $currentValue ?>"
                                       placeholder="Type schedule name or time, or 'OFF' for rest day"
                                       autocomplete="off"
                                       spellcheck="false">
                                <div class="autocomplete-dropdown hidden absolute z-10 w-full bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto"></div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        
                        <div class="flex justify-end mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Update Weekly Schedule
                            </button>
                        </div>
                    </form>
                    
                    <!-- JavaScript for Autocomplete -->
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Schedule data for autocomplete
                        const schedules = <?= json_encode(array_map(function($s) {
                            return [
                                'id' => $s['id'],
                                'name' => $s['name'],
                                'time_in' => date('g:i A', strtotime($s['time_in'])),
                                'time_out' => date('g:i A', strtotime($s['time_out'])),
                                'display' => $s['name'] . ' (' . date('g:i A', strtotime($s['time_in'])) . ' - ' . date('g:i A', strtotime($s['time_out'])) . ')'
                            ];
                        }, $allSchedules)) ?>;
                        
                        // Add OFF option
                        schedules.unshift({
                            id: '',
                            name: 'OFF',
                            display: 'OFF / Rest Day',
                            isOff: true
                        });
                        
                        // Setup autocomplete for each input
                        document.querySelectorAll('.schedule-autocomplete').forEach(input => {
                            const day = input.dataset.day;
                            const dropdown = input.nextElementSibling;
                            const hiddenInput = document.getElementById('schedule_id_' + day);
                            
                            // Show dropdown on focus
                            input.addEventListener('focus', function() {
                                showAllOptions(input, dropdown, schedules, hiddenInput, day);
                            });
                            
                            // Filter on input with improved search
                            input.addEventListener('input', function() {
                                const query = this.value.toLowerCase().trim();
                                
                                // Always clear hidden field when typing manually
                                if (query === '') {
                                    hiddenInput.value = '';
                                    showAllOptions(input, dropdown, schedules, hiddenInput, day);
                                    return;
                                }
                                
                                // Enhanced filter - searches in name, time_in, time_out, and handles numeric searches better
                                const filtered = schedules.filter(s => {
                                    if (s.isOff) {
                                        return 'off'.includes(query) || 'rest'.includes(query);
                                    }
                                    
                                    const nameMatch = s.name.toLowerCase().includes(query);
                                    const displayMatch = s.display.toLowerCase().includes(query);
                                    
                                    // Better numeric/time matching - removes colons and spaces for comparison
                                    const timeInClean = s.time_in ? s.time_in.replace(/[:\s]/g, '').toLowerCase() : '';
                                    const timeOutClean = s.time_out ? s.time_out.replace(/[:\s]/g, '').toLowerCase() : '';
                                    const queryClean = query.replace(/[:\s]/g, '');
                                    
                                    const timeInMatch = timeInClean.includes(queryClean) || (s.time_in && s.time_in.toLowerCase().includes(query));
                                    const timeOutMatch = timeOutClean.includes(queryClean) || (s.time_out && s.time_out.toLowerCase().includes(query));
                                    
                                    return nameMatch || displayMatch || timeInMatch || timeOutMatch;
                                });
                                
                                // Clear hidden field when typing (only set when clicking an option)
                                hiddenInput.value = '';
                                
                                // Show filtered results
                                showFilteredOptions(input, dropdown, filtered, hiddenInput, day);
                            });
                            
                            // Hide dropdown on blur (with delay for click)
                            input.addEventListener('blur', function() {
                                setTimeout(() => {
                                    dropdown.classList.add('hidden');
                                }, 200);
                            });
                            
                            // Allow easy deletion with keyboard
                            input.addEventListener('keydown', function(e) {
                                if (e.key === 'Backspace' || e.key === 'Delete') {
                                    // Allow normal deletion behavior
                                    if (this.value === '') {
                                        hiddenInput.value = '';
                                    }
                                }
                            });
                        });
                        
                        function showAllOptions(input, dropdown, schedules, hiddenInput, day) {
                            dropdown.innerHTML = '';
                            
                            schedules.forEach(schedule => {
                                const option = createOption(schedule, input, hiddenInput, dropdown, day);
                                dropdown.appendChild(option);
                            });
                            
                            dropdown.classList.remove('hidden');
                        }
                        
                        function showFilteredOptions(input, dropdown, filtered, hiddenInput, day) {
                            dropdown.innerHTML = '';
                            
                            if (filtered.length === 0) {
                                dropdown.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">No schedules found</div>';
                            } else {
                                filtered.forEach(schedule => {
                                    const option = createOption(schedule, input, hiddenInput, dropdown, day);
                                    dropdown.appendChild(option);
                                });
                            }
                            
                            dropdown.classList.remove('hidden');
                        }
                        
                        function createOption(schedule, input, hiddenInput, dropdown, day) {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-blue-50 cursor-pointer text-sm border-b last:border-b-0';
                            
                            if (schedule.isOff) {
                                div.innerHTML = `
                                    <div class="font-medium text-gray-700">
                                        <i class="fas fa-ban text-red-500 mr-2"></i>
                                        ${schedule.display}
                                    </div>
                                `;
                            } else {
                                div.innerHTML = `
                                    <div class="font-medium text-gray-800">${schedule.name}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <i class="far fa-clock mr-1"></i>
                                        ${schedule.time_in} - ${schedule.time_out}
                                    </div>
                                `;
                            }
                            
                            div.addEventListener('click', function() {
                                input.value = schedule.display;
                                hiddenInput.value = schedule.id;
                                dropdown.classList.add('hidden');
                                
                                // Visual feedback
                                input.classList.add('bg-green-50');
                                setTimeout(() => {
                                    input.classList.remove('bg-green-50');
                                }, 300);
                            });
                            
                            return div;
                        }
                        
                        // Close all dropdowns when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!e.target.closest('.schedule-input-wrapper')) {
                                document.querySelectorAll('.autocomplete-dropdown').forEach(dd => {
                                    dd.classList.add('hidden');
                                });
                            }
                        });
                    });
                    </script>
                    
                    <style>
                    .schedule-input-wrapper {
                        position: relative;
                    }
                    
                    .autocomplete-dropdown {
                        margin-top: -1px;
                    }
                    
                    .schedule-autocomplete:focus {
                        outline: none;
                    }
                    </style>
                </div>
            </div>

            <?php /* ROTATING SCHEDULE TAB - TEMPORARILY DISABLED
            <!-- Rotating Schedule Tab -->
            <div id="rotating-schedule" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Rotating Schedule Assignment</h2>
                    
                    <?php
                    // Fetch available rotating patterns
                    $patterns = $pdo->query("SELECT * FROM rotating_schedule_patterns ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Fetch current rotating assignment
                    $currentRotating = $pdo->prepare("SELECT * FROM employee_rotating_schedules WHERE employee_id = ? ORDER BY start_date DESC LIMIT 1");
                    $currentRotating->execute([$employeeId]);
                    $rotating = $currentRotating->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Current Rotating Schedule</h3>
                        <?php if ($rotating): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Pattern ID</p>
                                        <p class="font-medium"><?= $rotating['pattern_id'] ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Start Date</p>
                                        <p class="font-medium"><?= date('M d, Y', strtotime($rotating['start_date'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Cycle Start Date</p>
                                        <p class="font-medium"><?= date('M d, Y', strtotime($rotating['cycle_start_date'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">End Date</p>
                                        <p class="font-medium"><?= $rotating['end_date'] ? date('M d, Y', strtotime($rotating['end_date'])) : 'Ongoing' ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>No rotating schedule assigned
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="action" value="assign_rotating_schedule">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Rotating Pattern</label>
                            <select name="pattern_id" required class="w-full p-2 border rounded">
                                <option value="">-- Select Pattern --</option>
                                <?php foreach($patterns as $pattern): ?>
                                    <option value="<?= $pattern['id'] ?>" <?= $rotating && $rotating['pattern_id'] == $pattern['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pattern['pattern_name']) ?> (<?= $pattern['cycle_length'] ?> day cycle)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Choose a predefined rotation pattern for this employee</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="date" name="start_date" required value="<?= $rotating ? $rotating['start_date'] : date('Y-m-d') ?>" class="w-full p-2 border rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cycle Start Date</label>
                                <input type="date" name="cycle_start_date" required value="<?= $rotating ? $rotating['cycle_start_date'] : date('Y-m-d') ?>" class="w-full p-2 border rounded">
                                <p class="text-xs text-gray-500 mt-1">The date to start counting the rotation cycle</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date (Optional)</label>
                            <input type="date" name="end_date" value="<?= $rotating && $rotating['end_date'] ? $rotating['end_date'] : '' ?>" class="w-full p-2 border rounded">
                            <p class="text-xs text-gray-500 mt-1">Leave blank for ongoing rotation</p>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-6">
                            <?php if ($rotating): ?>
                                <button type="button" onclick="if(confirm('Remove rotating schedule?')) { document.getElementById('removeRotatingForm').submit(); }" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md transition">
                                    <i class="fas fa-trash mr-2"></i>Remove Rotation
                                </button>
                            <?php endif; ?>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i><?= $rotating ? 'Update' : 'Assign' ?> Rotating Schedule
                            </button>
                        </div>
                    </form>
                    
                    <?php if ($rotating): ?>
                        <form id="removeRotatingForm" method="post" style="display:none;">
                            <input type="hidden" name="action" value="remove_rotating_schedule">
                            <input type="hidden" name="rotating_id" value="<?= $rotating['id'] ?>">
                        </form>
                    <?php endif; ?>
                    
                    <!-- Pattern Preview -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4">Available Rotation Patterns</h3>
                        <div class="space-y-4">
                            <?php foreach($patterns as $pattern): ?>
                                <div class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-semibold"><?= htmlspecialchars($pattern['pattern_name']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($pattern['description']) ?></p>
                                        </div>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"><?= $pattern['cycle_length'] ?> days</span>
                                    </div>
                                    <?php
                                    // Fetch pattern details
                                    $details = $pdo->prepare("SELECT rpd.*, ws.name as schedule_name, ws.time_in, ws.time_out 
                                                             FROM rotating_schedule_pattern_days rpd 
                                                             LEFT JOIN work_schedules ws ON rpd.work_schedule_id = ws.id 
                                                             WHERE rpd.pattern_id = ? 
                                                             ORDER BY rpd.day_number");
                                    $details->execute([$pattern['id']]);
                                    $days = $details->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="grid grid-cols-7 gap-1 mt-2">
                                        <?php foreach($days as $day): ?>
                                            <div class="text-center p-2 bg-gray-50 rounded text-xs">
                                                <div class="font-semibold">Day <?= $day['day_number'] ?></div>
                                                <?php if ($day['is_rest_day']): ?>
                                                    <div class="text-red-600">OFF</div>
                                                <?php else: ?>
                                                    <div class="text-gray-700"><?= htmlspecialchars($day['schedule_name'] ?? 'N/A') ?></div>
                                                    <?php if ($day['time_in']): ?>
                                                        <div class="text-gray-500"><?= date('g:i A', strtotime($day['time_in'])) ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($patterns)): ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    <span class="text-yellow-800">No rotation patterns available. Create patterns first.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            END ROTATING SCHEDULE TAB */ ?>

            <?php /* SCHEDULE SUMMARY TAB - TEMPORARILY DISABLED
            <!-- Schedule Summary Tab -->
            <div id="schedule-summary" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Complete Schedule Summary</h2>
                    
                    <?php
                    // Fetch all schedule-related data for this employee
                    
                    // 1. Weekly Default Schedule
                    $weeklyDefault = $pdo->prepare("SELECT eds.*, GROUP_CONCAT(DISTINCT ws.name SEPARATOR ', ') as schedules_used 
                                                    FROM employee_default_schedules eds 
                                                    LEFT JOIN work_schedules ws ON ws.id IN (eds.monday, eds.tuesday, eds.wednesday, eds.thursday, eds.friday, eds.saturday, eds.sunday)
                                                    WHERE eds.employee_id = ? 
                                                    GROUP BY eds.id");
                    $weeklyDefault->execute([$employeeId]);
                    $weeklyData = $weeklyDefault->fetch(PDO::FETCH_ASSOC);
                    
                    // 2. Rotating Schedule
                    $rotatingData = $pdo->prepare("SELECT ers.*, rsp.pattern_name, rsp.cycle_length, rsp.description 
                                                   FROM employee_rotating_schedules ers 
                                                   LEFT JOIN rotating_schedule_patterns rsp ON ers.pattern_id = rsp.id 
                                                   WHERE ers.employee_id = ? 
                                                   ORDER BY ers.start_date DESC 
                                                   LIMIT 5");
                    $rotatingData->execute([$employeeId]);
                    $rotatingSchedules = $rotatingData->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 3. Daily Overrides
                    $dailyData = $pdo->prepare("SELECT eds.*, ws.name as schedule_name, ws.time_in, ws.time_out 
                                               FROM employee_daily_schedules eds 
                                               LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id 
                                               WHERE eds.employee_id = ? 
                                               ORDER BY eds.schedule_date DESC 
                                               LIMIT 20");
                    $dailyData->execute([$employeeId]);
                    $dailyOverrides = $dailyData->fetchAll(PDO::FETCH_ASSOC);
                    
                    // 4. Change History
                    $historyData = $pdo->prepare("SELECT * FROM schedule_override_history 
                                                  WHERE employee_id = ? 
                                                  ORDER BY changed_at DESC 
                                                  LIMIT 50");
                    $historyData->execute([$employeeId]);
                    $history = $historyData->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <!-- Weekly Default Schedule Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-calendar-week text-blue-600 mr-2"></i>
                            Default Weekly Schedule
                        </h3>
                        <?php if ($weeklyData): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <?php foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                                <th class="px-4 py-2 border text-sm"><?= $day ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day): 
                                                $schedId = $weeklyData[$day] ?? null;
                                                if ($schedId):
                                                    $sched = $pdo->query("SELECT * FROM work_schedules WHERE id = $schedId")->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                                <td class="px-4 py-2 border text-sm text-center bg-blue-50">
                                                    <div class="font-semibold"><?= htmlspecialchars($sched['name'] ?? 'Sched ' . $schedId) ?></div>
                                                    <div class="text-xs text-gray-600"><?= date('g:i A', strtotime($sched['time_in'])) ?> - <?= date('g:i A', strtotime($sched['time_out'])) ?></div>
                                                </td>
                                            <?php else: ?>
                                                <td class="px-4 py-2 border text-sm text-center bg-gray-50 text-gray-500">OFF</td>
                                            <?php endif; endforeach; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Last updated: <?= date('M d, Y g:i A', strtotime($weeklyData['updated_at'] ?? $weeklyData['created_at'])) ?></p>
                        <?php else: ?>
                            <div class="bg-gray-50 border rounded p-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>No weekly default schedule set
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Rotating Schedule Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-sync-alt text-purple-600 mr-2"></i>
                            Rotating Schedule History
                        </h3>
                        <?php if ($rotatingSchedules): ?>
                            <div class="space-y-3">
                                <?php foreach($rotatingSchedules as $rot): ?>
                                    <div class="border rounded-lg p-4 <?= (!$rot['end_date'] || $rot['end_date'] >= date('Y-m-d')) ? 'bg-purple-50 border-purple-200' : 'bg-gray-50' ?>">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-semibold"><?= htmlspecialchars($rot['pattern_name']) ?></h4>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($rot['description']) ?></p>
                                                <p class="text-xs text-gray-500 mt-1">Cycle: <?= $rot['cycle_length'] ?> days</p>
                                            </div>
                                            <div class="text-right text-sm">
                                                <div><strong>Start:</strong> <?= date('M d, Y', strtotime($rot['start_date'])) ?></div>
                                                <div><strong>Cycle Start:</strong> <?= date('M d, Y', strtotime($rot['cycle_start_date'])) ?></div>
                                                <div><strong>End:</strong> <?= $rot['end_date'] ? date('M d, Y', strtotime($rot['end_date'])) : 'Ongoing' ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-50 border rounded p-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>No rotating schedules assigned
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Daily Overrides Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-calendar-day text-green-600 mr-2"></i>
                            Daily Schedule Overrides (Recent 20)
                        </h3>
                        <?php if ($dailyOverrides): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 border text-left text-sm">Date</th>
                                            <th class="px-4 py-2 border text-left text-sm">Schedule</th>
                                            <th class="px-4 py-2 border text-left text-sm">Time</th>
                                            <th class="px-4 py-2 border text-left text-sm">Notes</th>
                                            <th class="px-4 py-2 border text-left text-sm">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dailyOverrides as $daily): ?>
                                            <tr class="<?= $daily['schedule_date'] >= date('Y-m-d') ? 'bg-green-50' : '' ?>">
                                                <td class="px-4 py-2 border text-sm"><?= date('M d, Y (D)', strtotime($daily['schedule_date'])) ?></td>
                                                <td class="px-4 py-2 border text-sm">
                                                    <?php if ($daily['is_rest_day']): ?>
                                                        <span class="text-red-600 font-semibold">🚫 OFF</span>
                                                    <?php else: ?>
                                                        <span class="text-green-600 font-semibold"><?= htmlspecialchars($daily['schedule_name'] ?? 'Schedule ' . $daily['work_schedule_id']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2 border text-sm">
                                                    <?php if (!$daily['is_rest_day'] && $daily['time_in']): ?>
                                                        <?= date('g:i A', strtotime($daily['time_in'])) ?> - <?= date('g:i A', strtotime($daily['time_out'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2 border text-sm text-gray-600"><?= htmlspecialchars($daily['notes'] ?? '-') ?></td>
                                                <td class="px-4 py-2 border text-sm text-gray-500"><?= date('M d, Y', strtotime($daily['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-50 border rounded p-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>No daily overrides found
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Change History Section -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-history text-gray-600 mr-2"></i>
                            Schedule Change History (Recent 50)
                        </h3>
                        <?php if ($history): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 border text-left">Date & Time</th>
                                            <th class="px-4 py-2 border text-left">Change Type</th>
                                            <th class="px-4 py-2 border text-left">Details</th>
                                            <th class="px-4 py-2 border text-left">Changed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($history as $h): ?>
                                            <tr>
                                                <td class="px-4 py-2 border"><?= date('M d, Y g:i A', strtotime($h['changed_at'])) ?></td>
                                                <td class="px-4 py-2 border">
                                                    <span class="px-2 py-1 rounded text-xs font-medium 
                                                        <?= $h['change_type'] === 'daily_override' ? 'bg-green-100 text-green-800' : '' ?>
                                                        <?= $h['change_type'] === 'weekly_default' ? 'bg-blue-100 text-blue-800' : '' ?>
                                                        <?= $h['change_type'] === 'rotating_assignment' ? 'bg-purple-100 text-purple-800' : '' ?>
                                                        <?= $h['change_type'] === 'rotating_removed' ? 'bg-red-100 text-red-800' : '' ?>">
                                                        <?= ucwords(str_replace('_', ' ', $h['change_type'])) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 border text-xs">
                                                    <code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars(substr($h['change_details'], 0, 100)) ?><?= strlen($h['change_details']) > 100 ? '...' : '' ?></code>
                                                </td>
                                                <td class="px-4 py-2 border"><?= htmlspecialchars($h['changed_by']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-50 border rounded p-4 text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>No change history found
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            END SCHEDULE SUMMARY TAB */ ?>

            <!-- Current Schedule Tab - Calendar Scheduling System -->
            <div id="current-schedule" class="tab-content">
                <?php include('../views/tabs/employee-calendar-tab.php'); ?>
            </div>

            <?php include('../views/tabs/employee-checklist-tab.php'); ?>

            <!-- Leave Credits Tab -->
            <div id="leave-credits" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Leave Credits (<?= date('Y') ?>)</h2>

                    <form method="post">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ?");
                            $stmt->execute([$employeeId, date('Y')]);
                            $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // If no credits exist, create default entries
                            if (empty($credits)) {
                                foreach ($leaveTypes as $type) {
                                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, year, balance) VALUES (?, ?, ?, 0)");
                                    $stmt->execute([$employeeId, $type, date('Y')]);
                                }
                                // Re-fetch after creating
                                $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ?");
                                $stmt->execute([$employeeId, date('Y')]);
                                $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }

                            foreach ($credits as $credit):
                                $leaveType = $credit['leave_type'];
                                // Skip halfday vacation, halfday sick, lwop, halfday
                                if (in_array($leaveType, ['halfday_vacation', 'halfday_sick', 'lwop', 'halfday'])) continue;
                                $label = ucwords(str_replace('_', ' ', $leaveType));
                            ?>
                            <div class="border border-gray-200 p-4 rounded-lg bg-gray-50">
                                <h3 class="text-sm font-semibold mb-2 text-gray-800"><?= $label ?></h3>

                                <label class="text-xs block mb-1 text-gray-600">Balance</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="credits[<?= $leaveType ?>][balance]" 
                                    value="<?= $credit['balance'] ?>" 
                                    class="w-full mb-2 px-3 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                />

                                <label class="text-xs block mb-1 text-gray-600">Monthly Increment</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="credits[<?= $leaveType ?>][monthly_increment]" 
                                    value="<?= $credit['monthly_increment'] ?? 0 ?>" 
                                    class="w-full mb-2 px-3 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                />

                                <?php if ($leaveType === 'vacation'): ?>
                                <label class="text-xs block mb-1 text-gray-600">Carry Over (from previous year)</label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="credits[<?= $leaveType ?>][carry_over]" 
                                    value="<?= $credit['carry_over'] ?? 0 ?>" 
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                />
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" name="update_credits" value="1" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Update Leave Credits
                            </button>
                        </div>
                    </form>
                </div>
            </div>

              <!-- Leave Requests Tab -->
            <div id="leave-requests" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Leave Requests</h2>
                    <?php if (empty($leaveRequests)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-calendar-alt text-4xl mb-4 opacity-50"></i>
                            <p>No leave requests found</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notified</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($leaveRequests as $request): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $request['id'] ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= ucfirst(str_replace('_', ' ', $request['leave_type'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($request['start_date'])) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($request['end_date'])) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $start = new DateTime($request['start_date']);
                                            $end = new DateTime($request['end_date']);
                                            $days = $start->diff($end)->days + 1;
                                            echo $days . ' day' . ($days > 1 ? 's' : '');
                                            ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500 max-w-xs">
                                            <div class="truncate" title="<?= htmlspecialchars($request['reason'] ?? '') ?>">
                                                <?= htmlspecialchars($request['reason'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['attachment_lr'])): ?>
                                                <a href="../uploads/leave_attachments/<?= htmlspecialchars($request['attachment_lr']) ?>" 
                                                   target="_blank" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?= getStatusBadge($request['status']) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <?php if ($request['notified']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Yes
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Card -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 mb-3">Leave Request Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php
                                $summary = [];
                                foreach ($leaveRequests as $req) {
                                    $status = $req['status'];
                                    $summary[$status] = ($summary[$status] ?? 0) + 1;
                                }
                                ?>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900"><?= count($leaveRequests) ?></div>
                                    <div class="text-xs text-gray-500">Total Requests</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-green-600"><?= $summary['approved'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Approved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-yellow-600"><?= $summary['pending'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-red-600"><?= $summary['rejected'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Rejected</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
 <!-- Schedule Changes Tab -->
            <div id="schedule-changes" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Schedule Change Requests</h2>
                    <?php if (empty($scheduleRequests)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-clock text-4xl mb-4 opacity-50"></i>
                            <p>No schedule change requests found</p>
                        </div>
                    <?php else: ?>
                        <?php
                        // Define schedule times for reference
                        $schedule_times = [
                            1 => ['in' => '06:30:00', 'out' => '15:30:00'],
                            2 => ['in' => '07:00:00', 'out' => '19:00:00'],
                            3 => ['in' => '07:30 AM', 'out' => '04:30 PM'],
                            4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
                            5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
                            6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
                            7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
                            8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
                            9 => ['in' => '08:00 AM', 'out' => '04:30 PM'],
                            10 => ['in' => '07:40 AM', 'out' => '04:40 PM'],
                            11 => ['in' => '06:30 AM', 'out' => '03:00 PM'],
                            12 => ['in' => '06:30 AM', 'out' => '05:30 PM'],
                            13 => ['in' => '07:00 AM', 'out' => '06:00 PM'],
                            14 => ['in' => '06:00 AM', 'out' => '05:00 PM'],
                            15 => ['in' => '06:00 AM', 'out' => '04:00 PM'],
                            16 => ['in' => '08:30 AM', 'out' => '04:30 PM'],
                            17 => ['in' => '06:00 AM', 'out' => '12:00 PM'],
                            18 => ['in' => '06:00 AM', 'out' => '02:30 PM'],
                            19 => ['in' => '07:00 PM', 'out' => '03:00 AM'],    
                            20 => ['in' => '07:00 PM', 'out' => '04:30 AM'],
                            21 => ['in' => '05:00 PM', 'out' => '02:00 AM'],
                            22 => ['in' => '05:30 PM', 'out' => '02:00 AM'],
                        ];
                        ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Schedule</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Schedule</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notified</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($scheduleRequests as $request): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $request['id'] ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-blue-600">Start:</span>
                                                <span><?= date('M d, Y', strtotime($request['start_date'])) ?></span>
                                                <span class="font-medium text-blue-600 mt-1">End:</span>
                                                <span><?= date('M d, Y', strtotime($request['end_date'])) ?></span>
                                                <?php
                                                $start = new DateTime($request['start_date']);
                                                $end = new DateTime($request['end_date']);
                                                $days = $start->diff($end)->days + 1;
                                                ?>
                                                <span class="text-xs text-gray-500 mt-1">(<?= $days ?> day<?= $days > 1 ? 's' : '' ?>)</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            $currentScheduleId = $request['current_work_schedule_id'] ?? null;
                                            if ($currentScheduleId && isset($schedule_times[$currentScheduleId])): ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-red-600">Schedule <?= $currentScheduleId ?>:</span>
                                                    <span><?= $schedule_times[$currentScheduleId]['in'] ?> - <?= $schedule_times[$currentScheduleId]['out'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">Not recorded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            $requestedScheduleId = $request['work_schedule_id'] ?? null;
                                            if ($requestedScheduleId && isset($schedule_times[$requestedScheduleId])): ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-green-600">Schedule <?= $requestedScheduleId ?>:</span>
                                                    <span><?= $schedule_times[$requestedScheduleId]['in'] ?> - <?= $schedule_times[$requestedScheduleId]['out'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">Invalid schedule</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500 max-w-xs">
                                            <div class="truncate" title="<?= htmlspecialchars($request['reason'] ?? '') ?>">
                                                <?= htmlspecialchars($request['reason'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['attachment_scr'])): ?>
                                                <a href="../uploads/schedule_attachments/<?= htmlspecialchars($request['attachment_scr']) ?>" 
                                                   target="_blank" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?= getStatusBadge($request['status']) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <?php if ($request['notified']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Yes
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Card -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 mb-3">Schedule Change Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php
                                $scSummary = [];
                                foreach ($scheduleRequests as $req) {
                                    $status = $req['status'];
                                    $scSummary[$status] = ($scSummary[$status] ?? 0) + 1;
                                }
                                ?>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900"><?= count($scheduleRequests) ?></div>
                                    <div class="text-xs text-gray-500">Total Requests</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-green-600"><?= $scSummary['approved'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Approved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-yellow-600"><?= $scSummary['pending'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-red-600"><?= $scSummary['rejected'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Rejected</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Overtime Requests Tab -->
            <div id="overtime-requests" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Overtime Requests</h2>
                    <?php if (empty($overtimeRequests)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-business-time text-4xl mb-4 opacity-50"></i>
                            <p>No overtime requests found</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notified</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($overtimeRequests as $request): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $request['id'] ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= date('M d, Y', strtotime($request['date'])) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-blue-600">OT Schedule:</span>
                                                <span><?= date('h:i A', strtotime($request['start_time'])) ?> - <?= date('h:i A', strtotime($request['end_time'])) ?></span>
                                                <?php
                                                // Calculate OT hours in both formats
                                                $start = new DateTime($request['start_time']);
                                                $end = new DateTime($request['end_time']);
                                                $interval = $start->diff($end);
                                                $decimalHours = $interval->h + ($interval->i / 60);
                                                $wholeHours = $interval->h;
                                                $minutes = $interval->i;
                                                ?>
                                                <span class="text-xs font-semibold text-blue-700">
                                                    <?= number_format($decimalHours, 2) ?> hrs
                                                    <span class="text-gray-500">(<?= $wholeHours ?>h <?= $minutes ?>m)</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['time_in']) && !empty($request['time_out'])): ?>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-green-600">Shift Schedule:</span>
                                                    <span><?= date('h:i A', strtotime($request['time_in'])) ?> - <?= date('h:i A', strtotime($request['time_out'])) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500 max-w-xs">
                                            <div class="truncate" title="<?= htmlspecialchars($request['reason'] ?? '') ?>">
                                                <?= htmlspecialchars($request['reason'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['attachment_ot'])): ?>
                                                <a href="../uploads/overtime_attachments/<?= htmlspecialchars($request['attachment_ot']) ?>" 
                                                   target="_blank" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?= getStatusBadge($request['status']) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <?php if ($request['notified']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Yes
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Card -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 mb-3">Overtime Request Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <?php
                                $otSummary = [];
                                $totalHours = 0;
                                foreach ($overtimeRequests as $req) {
                                    $status = $req['status'];
                                    $otSummary[$status] = ($otSummary[$status] ?? 0) + 1;
                                    
                                    // Calculate total OT hours for approved requests
                                    if ($status === 'approved') {
                                        $start = new DateTime($req['start_time']);
                                        $end = new DateTime($req['end_time']);
                                        $interval = $start->diff($end);
                                        $totalHours += $interval->h + ($interval->i / 60);
                                    }
                                }
                                ?>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900"><?= count($overtimeRequests) ?></div>
                                    <div class="text-xs text-gray-500">Total Requests</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-green-600"><?= $otSummary['approved'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Approved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-yellow-600"><?= $otSummary['pending'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-red-600"><?= $otSummary['rejected'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Rejected</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-blue-600"><?= number_format($totalHours, 1) ?></div>
                                    <div class="text-xs text-gray-500">Approved Hours</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Time Adjustments Tab -->
            <div id="time-adjustments" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Time Adjustment Requests</h2>
                    <?php if (empty($timeAdjustmentRequests)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-history text-4xl mb-4 opacity-50"></i>
                            <p>No time adjustment requests found</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Log Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attachment</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notified</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                       
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($timeAdjustmentRequests as $request): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?= $request['id'] ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= date('M d, Y', strtotime($request['log_date'])) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-red-600">Current:</span>
                                                <span><?= date('h:i A', strtotime($request['current_time_in'])) ?> - <?= date('h:i A', strtotime($request['current_time_out'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-green-600">Requested:</span>
                                                <span><?= date('h:i A', strtotime($request['requested_time_in'])) ?> - <?= date('h:i A', strtotime($request['requested_time_out'])) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500 max-w-xs">
                                            <div class="truncate" title="<?= htmlspecialchars($request['reason'] ?? '') ?>">
                                                <?= htmlspecialchars($request['reason'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['attachment'])): ?>
                                                <a href="../uploads/time_adjustment_attachments/<?= htmlspecialchars($request['attachment']) ?>" 
                                                   target="_blank" 
                                                   class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-paperclip mr-1"></i>View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?= getStatusBadge($request['status']) ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <?php if ($request['notified']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Yes
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-times mr-1"></i>No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($request['submitted_at'])): ?>
                                                <?= date('M d, Y', strtotime($request['submitted_at'])) ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Card -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 mb-3">Time Adjustment Summary</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php
                                $taSummary = [];
                                foreach ($timeAdjustmentRequests as $req) {
                                    $status = $req['status'];
                                    $taSummary[$status] = ($taSummary[$status] ?? 0) + 1;
                                }
                                ?>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-gray-900"><?= count($timeAdjustmentRequests) ?></div>
                                    <div class="text-xs text-gray-500">Total Requests</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-green-600"><?= $taSummary['approved'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Approved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-yellow-600"><?= $taSummary['pending'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-semibold text-red-600"><?= $taSummary['rejected'] ?? 0 ?></div>
                                    <div class="text-xs text-gray-500">Rejected</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Manage Schedules Tab -->
            <div id="manage-schedules" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-cog mr-2 text-blue-600"></i>Manage Work Schedules
                        </h2>
                        <button onclick="toggleAddScheduleForm()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                            <i class="fas fa-plus mr-2"></i>Add New Schedule
                        </button>
                    </div>

                    <!-- Add Schedule Form (Hidden by default) -->
                    <div id="addScheduleForm" class="hidden mb-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Schedule</h3>
                        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="hidden" name="action" value="add_schedule">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Name (Optional)</label>
                                <input type="text" name="schedule_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                       placeholder="e.g., Morning Shift, Night Shift">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Shift <span class="text-red-500">*</span></label>
                                <input type="time" name="time_in" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Shift <span class="text-red-500">*</span></label>
                                <input type="time" name="time_out" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div class="md:col-span-3 flex justify-end gap-2">
                                <button type="button" onclick="toggleAddScheduleForm()" 
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                                    <i class="fas fa-save mr-2"></i>Save Schedule
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Schedules Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Shift</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Shift</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Employees Using</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($allSchedules as $sched): 
                                    // Count employees using this schedule
                                    $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM employees WHERE official_sched = ?");
                                    $countStmt->execute([$sched['id']]);
                                    $empCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    
                                    // Calculate duration
                                    $timeIn = new DateTime($sched['time_in']);
                                    $timeOut = new DateTime($sched['time_out']);
                                    if ($timeOut < $timeIn) {
                                        $timeOut->modify('+1 day');
                                    }
                                    $interval = $timeIn->diff($timeOut);
                                    $duration = $interval->format('%h hrs %i min');
                                ?>
                                <tr class="hover:bg-gray-50" id="schedule-row-<?= $sched['id'] ?>">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $sched['id'] ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span id="name-display-<?= $sched['id'] ?>">
                                            <?= !empty($sched['name']) ? htmlspecialchars($sched['name']) : '<span class="text-gray-400 italic">No name</span>' ?>
                                        </span>
                                        <input type="text" id="name-edit-<?= $sched['id'] ?>" 
                                               value="<?= htmlspecialchars($sched['name'] ?? '') ?>"
                                               class="hidden w-full px-2 py-1 border border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span id="timein-display-<?= $sched['id'] ?>">
                                            <?= date('h:i A', strtotime($sched['time_in'])) ?>
                                        </span>
                                        <input type="time" id="timein-edit-<?= $sched['id'] ?>" 
                                               value="<?= $sched['time_in'] ?>"
                                               class="hidden w-full px-2 py-1 border border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span id="timeout-display-<?= $sched['id'] ?>">
                                            <?= date('h:i A', strtotime($sched['time_out'])) ?>
                                        </span>
                                        <input type="time" id="timeout-edit-<?= $sched['id'] ?>" 
                                               value="<?= $sched['time_out'] ?>"
                                               class="hidden w-full px-2 py-1 border border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $duration ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $empCount > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= $empCount ?> employee<?= $empCount != 1 ? 's' : '' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                        <div id="actions-view-<?= $sched['id'] ?>">
                                            <button onclick="editSchedule(<?= $sched['id'] ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 mr-3">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($empCount == 0): ?>
                                                <button onclick="deleteSchedule(<?= $sched['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400 cursor-not-allowed" title="Cannot delete - schedule in use">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div id="actions-edit-<?= $sched['id'] ?>" class="hidden">
                                            <button onclick="saveSchedule(<?= $sched['id'] ?>)" 
                                                    class="text-green-600 hover:text-green-800 mr-3">
                                                <i class="fas fa-check"></i> Save
                                            </button>
                                            <button onclick="cancelEdit(<?= $sched['id'] ?>)" 
                                                    class="text-gray-600 hover:text-gray-800">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Card -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-800 mb-3">Schedule Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900"><?= count($allSchedules) ?></div>
                                <div class="text-xs text-gray-500">Total Schedules</div>
                            </div>
                            <?php
                            $usedSchedules = 0;
                            foreach ($allSchedules as $sched) {
                                $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM employees WHERE official_sched = ?");
                                $countStmt->execute([$sched['id']]);
                                if ($countStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                                    $usedSchedules++;
                                }
                            }
                            ?>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-600"><?= $usedSchedules ?></div>
                                <div class="text-xs text-gray-500">In Use</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-600"><?= count($allSchedules) - $usedSchedules ?></div>
                                <div class="text-xs text-gray-500">Available</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<script>
function toggleEditMode() {
    const editForm = document.getElementById('editForm');
    const editButton = document.getElementById('editButton');
    const displayInfo = document.getElementById('displayInfo');

    if (editForm.style.display === 'none' || editForm.style.display === '') {
        editForm.style.display = 'grid';
        editButton.classList.add('hidden');
        displayInfo.classList.add('hidden');
        
        // Initialize employment type change confirmation
        initializeEmpTypeConfirmation();
    } else {
        editForm.style.display = 'none';
        editButton.classList.remove('hidden');
        displayInfo.classList.remove('hidden');
    }
}

// Track original employment type value
let originalEmpType = '';

function initializeEmpTypeConfirmation() {
    const empTypeSelect = document.getElementById('empTypeSelect');
    const editForm = document.getElementById('editForm');
    
    if (empTypeSelect) {
        // Store original value when form opens
        originalEmpType = empTypeSelect.value;
        
        // Remove any existing listeners
        const newSelect = empTypeSelect.cloneNode(true);
        empTypeSelect.parentNode.replaceChild(newSelect, empTypeSelect);
        
        // Add change event listener
        document.getElementById('empTypeSelect').addEventListener('change', function(e) {
            const newValue = this.value;
            
            // Only show confirmation when changing FROM Probationary TO Regular
            if (originalEmpType === 'Probationary' && newValue === 'Regular') {
                const confirmed = confirm(
                    '⚠️ REGULARIZATION CONFIRMATION\n\n' +
                    'You are about to regularize this employee.\n\n' +
                    '✅ Will receive 7.5 days Vacation Leave (VL)\n' +
                    '✅ Will receive 5 days Sick Leave (SL)\n' +
                    '✅ VL will accrue 1.25 days monthly\n' +
                    '❌ NO monthly SL accrual\n\n' +
                    'Do you want to proceed with regularization?'
                );
                
                if (!confirmed) {
                    // Revert to original value if not confirmed
                    this.value = originalEmpType;
                }
            }
        });
    }
    
    // Add form submit confirmation for employment type changes
    if (editForm && !editForm.hasAttribute('data-listener-added')) {
        editForm.setAttribute('data-listener-added', 'true');
        editForm.addEventListener('submit', function(e) {
            const empTypeSelect = document.getElementById('empTypeSelect');
            if (empTypeSelect && originalEmpType === 'Probationary' && empTypeSelect.value === 'Regular') {
                const confirmed = confirm(
                    '🎯 FINAL CONFIRMATION\n\n' +
                    'Employee Name: <?= htmlspecialchars($employee["fname"] . " " . $employee["lname"]) ?>\n' +
                    'Action: Probationary → Regular\n\n' +
                    '✅ 7.5 days Vacation Leave will be granted\n' +
                    '✅ 5 days Sick Leave will be granted\n' +
                    '✅ Monthly VL accrual (1.25 days) will begin\n\n' +
                    'Click OK to confirm regularization.'
                );
                
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
}

function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    
    // Hide all tab content
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    
    // Remove active class from all tab buttons
    tablinks = document.getElementsByClassName("tab-button");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    
    // Show the selected tab content and mark button as active
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}

// Show default tab on page load, or tab from URL hash
document.addEventListener('DOMContentLoaded', function() {
    let tabOpened = false;
    
    // Check if there's a hash in the URL
    const hash = window.location.hash.substring(1); // Remove the # symbol
    
    if (hash) {
        // Check if a tab with this ID exists
        const tabElement = document.getElementById(hash);
        if (tabElement && tabElement.classList.contains('tab-content')) {
            // Find the corresponding tab button and click it
            const tabButtons = document.querySelectorAll('.tab-button');
            for (let button of tabButtons) {
                const onclick = button.getAttribute('onclick');
                if (onclick && (onclick.includes(`'${hash}'`) || onclick.includes(`"${hash}"`))) {
                    button.click();
                    tabOpened = true;
                    break;
                }
            }
        }
    }
    
    // Default: open the first tab only if no tab was opened from hash
    if (!tabOpened) {
        document.getElementById('default-tab').click();
    }
});

function confirmScheduleChange(scheduleId, inTime, outTime, isCurrent) {
    if (isCurrent) return; // Don't allow changing to current schedule
    if (confirm(`Change schedule to ${inTime} - ${outTime}?`)) {
        document.getElementById('official_sched_input').value = scheduleId;
        document.getElementById('scheduleForm').submit();
    }
}

// Manage Schedules Functions
function toggleAddScheduleForm() {
    const form = document.getElementById('addScheduleForm');
    form.classList.toggle('hidden');
}

function editSchedule(scheduleId) {
    // Hide display elements
    document.getElementById('name-display-' + scheduleId).classList.add('hidden');
    document.getElementById('timein-display-' + scheduleId).classList.add('hidden');
    document.getElementById('timeout-display-' + scheduleId).classList.add('hidden');
    document.getElementById('actions-view-' + scheduleId).classList.add('hidden');
    
    // Show edit inputs
    document.getElementById('name-edit-' + scheduleId).classList.remove('hidden');
    document.getElementById('timein-edit-' + scheduleId).classList.remove('hidden');
    document.getElementById('timeout-edit-' + scheduleId).classList.remove('hidden');
    document.getElementById('actions-edit-' + scheduleId).classList.remove('hidden');
}

function cancelEdit(scheduleId) {
    // Show display elements
    document.getElementById('name-display-' + scheduleId).classList.remove('hidden');
    document.getElementById('timein-display-' + scheduleId).classList.remove('hidden');
    document.getElementById('timeout-display-' + scheduleId).classList.remove('hidden');
    document.getElementById('actions-view-' + scheduleId).classList.remove('hidden');
    
    // Hide edit inputs
    document.getElementById('name-edit-' + scheduleId).classList.add('hidden');
    document.getElementById('timein-edit-' + scheduleId).classList.add('hidden');
    document.getElementById('timeout-edit-' + scheduleId).classList.add('hidden');
    document.getElementById('actions-edit-' + scheduleId).classList.add('hidden');
}

function saveSchedule(scheduleId) {
    const name = document.getElementById('name-edit-' + scheduleId).value;
    const timeIn = document.getElementById('timein-edit-' + scheduleId).value;
    const timeOut = document.getElementById('timeout-edit-' + scheduleId).value;
    
    if (!timeIn || !timeOut) {
        alert('Please fill in both time in and time out.');
        return;
    }
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'post';
    form.innerHTML = `
        <input type="hidden" name="action" value="edit_schedule">
        <input type="hidden" name="schedule_id" value="${scheduleId}">
        <input type="hidden" name="schedule_name" value="${name}">
        <input type="hidden" name="time_in" value="${timeIn}">
        <input type="hidden" name="time_out" value="${timeOut}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteSchedule(scheduleId) {
    if (confirm('Are you sure you want to delete this schedule?\n\nThis action cannot be undone.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_schedule">
            <input type="hidden" name="schedule_id" value="${scheduleId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

</script>

</body>
</html>
