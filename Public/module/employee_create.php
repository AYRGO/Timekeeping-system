

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Employee</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0fdfc] min-h-screen flex items-center justify-center px-4 py-10">

  <form method="POST" action="../controller/save_employee.php" class="w-full max-w-lg bg-white p-6 sm:p-8 rounded-2xl shadow-xl border-t-8 border-[#0fe0fc] space-y-5">

    <h2 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95]">Add Employee</h2>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
      <input type="text" name="fname" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
      <input type="text" name="lname" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
      <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
      <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
      <input type="text" name="contact" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
      <input type="text" name="position" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-[#0fe0fc] focus:outline-none">
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
    </div>

    <div class="pt-2">
      <button type="submit" class="w-full bg-[#2F9C95] hover:bg-[#269387] text-white font-semibold py-3 px-6 rounded-xl transition duration-200 text-lg">
        Save Employee
      </button>
       <p class="text-center mt-4">
      <a href="../views/employee_list.php" class="text-[#2F9C95] font-medium underline hover:text-[#40C9A2]">
        ← Back to dashboard
      </a>
    </p>
    </div>
  </form>
  

</body>
</html>
