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
    // Profile Update
    if (isset($_POST['update_profile'])) {
        $fname = $_POST['fname'] ?? '';
        $lname = $_POST['lname'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $position = $_POST['position'] ?? '';
        $status = $_POST['status'] ?? '';
        $company = $_POST['company'] ?? '';

        if ($fname && $lname && $email) {
            $stmt = $pdo->prepare("UPDATE employees SET fname = ?, lname = ?, email = ?, contact = ?, position = ?, status = ?, company = ? WHERE id = ?");
            $stmt->execute([$fname, $lname, $email, $contact, $position, $status, $company, $employeeId]);
            echo "<script>alert('Employee updated successfully!'); window.location.href = 'employee-edit.php?id=$employeeId';</script>";
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
}

// Fetch employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) die("Employee not found");

$stmt = $pdo->prepare("SELECT * FROM employee_checklist WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all employee requests
// Leave Requests
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employeeId]);
$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Schedule Change Requests
$stmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employeeId]);
$scheduleRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overtime Requests
$stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employeeId]);
$overtimeRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Time Adjustment Requests
$stmt = $pdo->prepare("SELECT * FROM time_adjustment_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employeeId]);
$timeAdjustmentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    3 => ['in' => '07:30 AM', 'out' => '04:30 PM'],
    4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
    5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
    6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
    7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
    8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
    9 => ['in' => '08:00 AM', 'out' => '04:30 PM'],
    10 => ['in' => '07:40 AM', 'out' => '04:40 PM'],
    11 => ['in' => '06:30 AM', 'out' => '03:00 PM'],
];
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
                            <div class="flex gap-2">
                                <button onclick="toggleEditMode()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                    <i class="fas fa-edit mr-2"></i>Edit Profile
                                </button>
                            </div>
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
                <button onclick="openTab(event, 'profile')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition active" id="default-tab">
                    <i class="fas fa-user mr-2"></i>Profile
                </button>
                <button onclick="openTab(event, 'current-schedule')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-calendar-check mr-2"></i>Current Schedule
                </button>
                <button onclick="openTab(event, 'checklist')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-tasks mr-2"></i>201 Checklist
                </button>
                <button onclick="openTab(event, 'leave-credits')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-300 transition">
                    <i class="fas fa-calendar-alt mr-2"></i>Leave Credits
                </button>
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
            <div id="profile" class="tab-content active">
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
                    </div>

                    <!-- Edit Form (Initially Hidden) -->
                    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 hidden" id="editForm">
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

                        <div class="md:col-span-2 flex justify-between items-center pt-4">
                            <button type="button" onclick="toggleEditMode()" class="text-sm text-gray-600 hover:underline">Cancel</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>

                    <!-- Edit Button -->
                    <div class="mt-6 flex justify-end" id="editButton">
                        <button onclick="toggleEditMode()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition">
                            <i class="fas fa-edit mr-2"></i>Edit Profile
                        </button>
                    </div>
                </div>
            </div>

            <?php include('../views/tabs/employee-schedule-tab.php'); ?>

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

                                <input 
                                    type="hidden" 
                                    name="credits[<?= $leaveType ?>][monthly_increment]" 
                                    value="<?= $credit['monthly_increment'] ?? 0 ?>" 
                                />

                                <?php if ($leaveType === 'vacation'): ?>
                                    <label class="text-xs block mb-1 text-gray-600">Carry Over</label>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        name="credits[vacation][carry_over]" 
                                        value="<?= $credit['carry_over'] ?? 0 ?>" 
                                        class="w-full mb-2 px-3 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    />
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit" name="update_credits" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Save Credits
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
                            3 => ['in' => '07:30 AM', 'out' => '04:30 PM'],
                            4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
                            5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
                            6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
                            7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
                            8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
                            9 => ['in' => '08:00 AM', 'out' => '04:30 PM'],
                            10 => ['in' => '07:40 AM', 'out' => '04:40 PM'],
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
                                                // Calculate OT hours
                                                $start = new DateTime($request['start_time']);
                                                $end = new DateTime($request['end_time']);
                                                $interval = $start->diff($end);
                                                $hours = $interval->h + ($interval->i / 60);
                                                ?>
                                                <span class="text-xs text-gray-500">(<?= number_format($hours, 1) ?> hrs)</span>
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
<script>
function toggleEditMode() {
    const editForm = document.getElementById('editForm');
    const editButton = document.getElementById('editButton');
    const displayInfo = document.getElementById('displayInfo');

    if (editForm.classList.contains('hidden')) {
        editForm.classList.remove('hidden');
        editButton.classList.add('hidden');
        displayInfo.classList.add('hidden');
    } else {
        editForm.classList.add('hidden');
        editButton.classList.remove('hidden');
        displayInfo.classList.remove('hidden');
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

// Show default tab on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('default-tab').click();
});
</script>

</body>
</html>