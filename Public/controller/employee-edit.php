<?php
session_start();
include('../config/db.php');

// Authentication
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Get employee ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid employee ID");
}
$employeeId = (int)$_GET['id'];

$documents = [
  'letter_offer' => 'Signed Letter of Offer',
  'employment_contract' => 'Signed Employment Contract',
  'medical' => 'Medical',
  'nbi_clearance' => 'NBI Clearance',
  'diploma_tor' => 'Diploma / TOR',
  'psa' => 'PSA',
  'sss' => 'SSS',
  'tin' => 'TIN',
  'philhealth' => 'PhilHealth',
  'pagibig' => 'Pagibig',
  'coe' => 'Certificate of Employment (Recent Employer)'
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

        header("Location: employee_edit.php?id=$employeeId&deleted=1");
        exit;
    }
}

// Save form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_credits'])) {
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($fname && $lname && $email) {
        $stmt = $pdo->prepare("UPDATE employees SET fname = ?, lname = ?, email = ?, contact = ?, position = ?, status = ? WHERE id = ?");
        $stmt->execute([$fname, $lname, $email, $contact, $position, $status, $employeeId]);

        $uploadDir = '../uploads/checklist/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $uploadedFiles = [];
        foreach ($documents as $field => $label) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $filename = uniqid($field . "_") . "_" . basename($_FILES[$field]['name']);
                move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename);
                $uploadedFiles[$field] = $filename;
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM employee_checklist WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        $exists = $stmt->fetch();

        if ($uploadedFiles) {
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

        header("Location: employee-edit.php?id=$employeeId&updated=1");
        exit;
    } else {
        $error = "Please fill in all required fields.";
    }
}

$leaveTypes = [
    'sick',
    'vacation',
    'paternity',
    'maternity',
    'solo_parent',
    'halfday',
    'halfday_sick',
    'lwop',
    'bereavement'
];

// Update leave credits (Admin input)
if (isset($_POST['update_credits']) && isset($_POST['credits'])) {
    foreach ($_POST['credits'] as $leaveType => $data) {
        $balance = is_numeric($data['balance']) ? floatval($data['balance']) : null;
        $monthlyIncrement = is_numeric($data['monthly_increment']) ? floatval($data['monthly_increment']) : null;
        $carryOver = isset($data['carry_over']) && is_numeric($data['carry_over']) ? floatval($data['carry_over']) : null;

        // Make sure a record exists (insert if not)
        $check = $pdo->prepare("SELECT COUNT(*) FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type = ?");
        $check->execute([$employeeId, date('Y'), $leaveType]);
        if ($check->fetchColumn() == 0) {
            $insert = $pdo->prepare("
                INSERT INTO leave_credits (employee_id, leave_type, year)
                VALUES (?, ?, ?)
            ");
            $insert->execute([$employeeId, $leaveType, date('Y')]);
        }

        // Clear previous values
        $clear = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = NULL, carry_over = NULL, monthly_increment = NULL 
            WHERE employee_id = ? AND year = ? AND leave_type = ?
        ");
        $clear->execute([$employeeId, date('Y'), $leaveType]);

        // Update with new values
        $stmt = $pdo->prepare("
            UPDATE leave_credits 
            SET balance = ?, monthly_increment = ?, carry_over = ?, updated_at = NOW() 
            WHERE employee_id = ? AND year = ? AND leave_type = ?
        ");
        $stmt->execute([
            $balance,
            $monthlyIncrement,
            $leaveType === 'vacation' ? $carryOver : null,
            $employeeId,
            date('Y'),
            $leaveType
        ]);
    }

    header("Location: employee-edit.php?id=$employeeId&credits_updated=1");
    exit;
}


// Fetch employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) die("Employee not found");

$stmt = $pdo->prepare("SELECT * FROM employee_checklist WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Employee</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-100 py-10 px-4">
  <div class="max-w-6xl mx-auto space-y-10">

    <!-- Flash Messages -->
    <div class="space-y-2">
      <?php if (isset($_GET['updated'])): ?>
        <div class="flex items-center bg-green-100 text-green-700 px-4 py-2 rounded shadow">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 100 20 10 10 0 000-20zm1 15.414l-4.293-4.293 1.414-1.414L11 12.586l3.879-3.879 1.414 1.414L11 15.414z"/></svg>
          Employee updated successfully.
        </div>
      <?php endif; ?>
      <?php if (isset($error)): ?>
        <div class="flex items-center bg-red-100 text-red-700 px-4 py-2 rounded shadow">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 100 20 10 10 0 000-20zm1 15.414l-4.293-4.293 1.414-1.414L11 12.586l3.879-3.879 1.414 1.414L11 15.414z"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Employee Profile -->
    <div class="bg-white shadow-lg rounded-xl p-8">
      <h2 class="text-2xl font-semibold mb-6 text-gray-800">Employee Profile</h2>
      <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium">First Name</label>
          <input name="fname" type="text" required value="<?= htmlspecialchars($employee['fname']) ?>" class="w-full mt-1 border px-4 py-2 rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium">Last Name</label>
          <input name="lname" type="text" required value="<?= htmlspecialchars($employee['lname']) ?>" class="w-full mt-1 border px-4 py-2 rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium">Email</label>
          <input name="email" type="email" required value="<?= htmlspecialchars($employee['email']) ?>" class="w-full mt-1 border px-4 py-2 rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium">Contact</label>
          <input name="contact" type="text" value="<?= htmlspecialchars($employee['contact']) ?>" class="w-full mt-1 border px-4 py-2 rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium">Position</label>
          <input name="position" type="text" value="<?= htmlspecialchars($employee['position']) ?>" class="w-full mt-1 border px-4 py-2 rounded-md" />
        </div>
        <div>
          <label class="block text-sm font-medium">Status</label>
          <select name="status" class="w-full mt-1 border px-4 py-2 rounded-md">
            <option value="Active" <?= $employee['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $employee['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="md:col-span-2 flex justify-between items-center pt-4">
          <a href="../views/employee_list.php" class="text-sm text-blue-600 hover:underline">← Back to List</a>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">Update Profile</button>
        </div>
      </form>
    </div>

    <!-- 201 Checklist -->
    <div class="bg-white shadow-lg rounded-xl p-8">
      <h2 class="text-2xl font-semibold mb-6 text-gray-800">201 Checklist</h2>
      <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($documents as $field => $label): ?>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium"><?= $label ?></label>
          <input type="file" name="<?= $field ?>" class="mt-1 block w-full border px-4 py-2 rounded-md" />
          <?php if (!empty($checklist[$field])): ?>
            <div class="mt-1 text-sm space-x-3">
              <a href="../uploads/checklist/<?= htmlspecialchars($checklist[$field]) ?>" target="_blank" class="text-blue-600 underline">View</a>
              <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=<?= $field ?>" onclick="return confirm('Delete this file?');" class="text-red-500 underline">Delete</a>
            </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">Upload Documents</button>
        </div>
      </form>
    </div>
<!-- Leave Credits -->
<div class="bg-white shadow-lg rounded-xl p-8">
  <h2 class="text-2xl font-semibold mb-6 text-gray-800">Leave Credits (<?= date('Y') ?>)</h2>

  <?php if (isset($_GET['credits_updated'])): ?>
    <div class="flex items-center bg-green-100 text-green-700 px-4 py-2 rounded shadow mb-4">
      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 0a10 10 0 100 20 10 10 0 000-20zm1 15.414l-4.293-4.293 1.414-1.414L11 12.586l3.879-3.879 1.414 1.414L11 15.414z"/>
      </svg>
      Leave credits updated successfully.
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php
      $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ?");
      $stmt->execute([$employeeId, date('Y')]);

      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $credit):
        $leaveType = $credit['leave_type'];
        $label = ucwords(str_replace('_', ' ', $leaveType));
      ?>
      <div class="border p-4 rounded-lg bg-gray-50 shadow">
        <h3 class="text-sm font-semibold mb-2"><?= $label ?></h3>

        <!-- Balance -->
        <label class="text-xs block mb-1">Balance</label>
        <input 
          type="number" 
          step="0.01" 
          name="credits[<?= $leaveType ?>][balance]" 
          value="<?= $credit['balance'] ?>" 
          class="w-full mb-2 px-3 py-1 border rounded" 
        />

        <!-- Monthly Increment -->
        <input 
          type="hidden" 
          name="credits[<?= $leaveType ?>][monthly_increment]" 
          value="<?= $credit['monthly_increment'] ?? 0 ?>" 
        />

        <?php if ($leaveType === 'vacation'): ?>
          <!-- Carry Over -->
          <label class="text-xs block mb-1">Carry Over</label>
          <input 
            type="number" 
            step="0.01" 
            name="credits[vacation][carry_over]" 
            value="<?= $credit['carry_over'] ?? 0 ?>" 
            class="w-full mb-2 px-3 py-1 border rounded" 
          />
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="flex justify-end mt-6">
      <button type="submit" name="update_credits" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
        Save Credits
      </button>
    </div>
  </form>
</div>



  </div>
</body>
</html>
