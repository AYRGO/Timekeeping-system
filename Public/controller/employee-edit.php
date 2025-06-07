<?php
session_start();
include('../config/db.php');

// Check login
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Get employee ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid employee ID");
}
$employeeId = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? '';

    // Basic validation
    if ($fname && $lname && $email) {
        $stmt = $pdo->prepare("UPDATE employees SET fname = ?, lname = ?, email = ?, contact = ?, position = ?, status = ? WHERE id = ?");
        $stmt->execute([$fname, $lname, $email, $contact, $position, $status, $employeeId]);
        header("Location: ../views/employee_list.php?updated=1");
        exit;
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch employee info
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Employee</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-10">
  <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold mb-4">Edit Employee</h1>

    <?php if (isset($error)): ?>
      <div class="text-red-600 mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">First Name</label>
        <input type="text" name="fname" value="<?= htmlspecialchars($employee['fname']) ?>" required class="w-full px-4 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm font-medium">Last Name</label>
        <input type="text" name="lname" value="<?= htmlspecialchars($employee['lname']) ?>" required class="w-full px-4 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required class="w-full px-4 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm font-medium">Contact</label>
        <input type="text" name="contact" value="<?= htmlspecialchars($employee['contact']) ?>" class="w-full px-4 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm font-medium">Position</label>
        <input type="text" name="position" value="<?= htmlspecialchars($employee['position']) ?>" class="w-full px-4 py-2 border rounded" />
      </div>
      <div>
        <label class="block text-sm font-medium">Status</label>
        <select name="status" class="w-full px-4 py-2 border rounded">
          <option value="Active" <?= $employee['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= $employee['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="flex justify-between">
        <a href="../views/employee_list.php" class="text-blue-600 hover:underline">Back to List</a>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
      </div>
    </form>
  </div>
</body>
</html>
