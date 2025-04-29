<?php include('../config/db.php'); ?>
<?php
// Query to fetch employees ordered by ID in descending order
$stmt = $pdo->query("SELECT * FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<html>
<head><title>Employee List</title></head>
<body>
<h1>Employees</h1>
<a href="employee-create.php">Add New Employee</a>
<table border="1">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Contact</th><th>Position</th><th>Status</th><th>Actions</th></tr>
<?php foreach($employees as $emp): ?>
<tr>
<td><?= $emp['id'] ?></td>
<td><?= htmlspecialchars($emp['fname']) ?> <?= htmlspecialchars($emp['lname']) ?></td>
<td><?= htmlspecialchars($emp['email']) ?></td>
<td><?= htmlspecialchars($emp['contact']) ?></td>
<td><?= htmlspecialchars($emp['position']) ?></td>
<td><?= htmlspecialchars($emp['status']) ?></td>
<td>
    <a href="employee-edit.php?id=<?= $emp['id'] ?>">Edit</a> |
    <a href="employee-delete.php?id=<?= $emp['id'] ?>">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
