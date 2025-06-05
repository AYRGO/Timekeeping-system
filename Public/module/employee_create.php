<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Employee</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-10 px-4">

  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg p-8 flex flex-col gap-8 border-t-8 border-blue-500">

    <!-- Header -->
    <div class="text-center">
      <h1 class="text-3xl font-bold text-gray-900">Add New Employee</h1>
      <p class="text-gray-500 mt-1">Fill out the form to add a new employee to the system.</p>
    </div>

    <!-- Form -->
    <form method="POST" action="../controller/save_employee.php" class="space-y-6">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
          <input type="text" name="fname" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
          <input type="text" name="lname" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
          <input type="text" name="contact" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
          <input type="text" name="position" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-400 focus:outline-none">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Save Button -->
      <div class="pt-4">
        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition duration-200 text-lg">
          Save Employee
        </button>

        <p class="text-center mt-4">
          <a href="../views/employee_list.php" class="text-blue-600 font-medium underline hover:text-blue-800">
            ← Back to Employee List
          </a>
        </p>
      </div>
    </form>

  </div>

</body>
</html>
