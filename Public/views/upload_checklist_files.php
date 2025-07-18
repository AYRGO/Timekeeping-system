<?php
include('../config/db.php');
session_start();

$employee_id = $_POST['employee_id'] ?? null;
if (!$employee_id) {
    die("Invalid employee ID.");
}

$upload_dir = '../uploads/checklists/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$fileFields = [
    "signed_letter_offer",
    "signed_employment_contract",
    "medical",
    "nbi_clearance",
    "diploma_tor",
    "psa",
    "sss",
    "tin",
    "philhealth",
    "coe_recent_employer"
];

$update_fields = [];
$update_values = [];

foreach ($fileFields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $filename = $employee_id . '_' . $field . '_' . basename($_FILES[$field]['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
            $relative_path = 'uploads/checklists/' . $filename;
            $update_fields[] = "$field = ?";
            $update_values[] = $relative_path;
        }
    }
}

if (!empty($update_fields)) {
    $sql = "UPDATE employee_checklist SET " . implode(', ', $update_fields) . " WHERE employee_id = ?";
    $update_values[] = $employee_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($update_values);
}

header("Location: profile.php"); // Redirect back to profile
exit;
