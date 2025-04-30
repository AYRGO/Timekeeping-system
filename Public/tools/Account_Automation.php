<?php
include('../config/db.php');

// Fetch all employees without usernames
$stmt = $pdo->query("SELECT id, fname, lname FROM employees WHERE username IS NULL OR password IS NULL");
$employees = $stmt->fetchAll();

foreach ($employees as $emp) {
    $username = strtolower($emp['fname'] . '.' . $emp['lname']);
    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 5);

    $update = $pdo->prepare("UPDATE employees SET username = ?, password = ? WHERE id = ?");
    $update->execute([$username, $password, $emp['id']]);

    echo "Updated ID {$emp['id']}: Username: $username | Password: $password<br>";
}
