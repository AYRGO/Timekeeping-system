<?php
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$currentYear = date('Y');

// Fetch all active employees
$employees = $pdo->query("SELECT id FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($employees as $emp) {
    $employee_id = $emp['id'];

    // --- SL (Sick Leave) ---
    $sl = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'SL' AND year = ?");
    $sl->execute([$employee_id, $currentYear]);
    $slRow = $sl->fetch();

    if ($slRow) {
        if ($slRow['balance'] < 15) {
            $new_sl = min(15, $slRow['balance'] + 0.42);
            $update = $pdo->prepare("UPDATE leave_credits SET balance = ? WHERE id = ?");
            $update->execute([$new_sl, $slRow['id']]);
        }
    } else {
        $insert = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, 'SL', 0.42, ?)");
        $insert->execute([$employee_id, $currentYear]);
    }

    // --- VL (Vacation Leave) ---
    $vl = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'VL' AND year = ?");
    $vl->execute([$employee_id, $currentYear]);
    $vlRow = $vl->fetch();

    if ($vlRow) {
        if ($vlRow['balance'] < 5) {
            $new_vl = min(5, $vlRow['balance'] + 1.25);
            $update = $pdo->prepare("UPDATE leave_credits SET balance = ? WHERE id = ?");
            $update->execute([$new_vl, $vlRow['id']]);
        }
    } else {
        $insert = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, 'VL', 1.25, ?)");
        $insert->execute([$employee_id, $currentYear]);
    }

    // --- SPL (Solo Parent Leave), fixed 7 days per year ---
    $spl = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'SPL' AND year = ?");
    $spl->execute([$employee_id, $currentYear]);
    $splRow = $spl->fetch();

    if (!$splRow) {
        $insertSpl = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, 'SPL', 7, ?)");
        $insertSpl->execute([$employee_id, $currentYear]);
    }

    // --- Half-day SL ---
    $halfSl = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'Half_SL' AND year = ?");
    $halfSl->execute([$employee_id, $currentYear]);
    $halfSlRow = $halfSl->fetch();

    if ($halfSlRow) {
        if ($halfSlRow['balance'] < 15) {
            $new_half_sl = min(15, $halfSlRow['balance'] + 0.50);
            $update = $pdo->prepare("UPDATE leave_credits SET balance = ? WHERE id = ?");
            $update->execute([$new_half_sl, $halfSlRow['id']]);
        }
    } else {
        $insertHalfSl = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, 'Half_SL', 0.50, ?)");
        $insertHalfSl->execute([$employee_id, $currentYear]);
    }

    // --- Half-day VL ---
    $halfVl = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND leave_type = 'Half_VL' AND year = ?");
    $halfVl->execute([$employee_id, $currentYear]);
    $halfVlRow = $halfVl->fetch();

    if ($halfVlRow) {
        if ($halfVlRow['balance'] < 5) {
            $new_half_vl = min(5, $halfVlRow['balance'] + 0.50);
            $update = $pdo->prepare("UPDATE leave_credits SET balance = ? WHERE id = ?");
            $update->execute([$new_half_vl, $halfVlRow['id']]);
        }
    } else {
        $insertHalfVl = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, year) VALUES (?, 'Half_VL', 0.50, ?)");
        $insertHalfVl->execute([$employee_id, $currentYear]);
    }

    // --- LWOP (Leave Without Pay) --- no credits to track, so skip
}

echo "Leave credits updated successfully.";
?>
