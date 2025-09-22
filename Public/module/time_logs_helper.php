<?php
function isOvertimeEligible($time_in, $time_out, $ot_type = null) {
    if (!$time_in || !$time_out) return false;
    
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    
    // Calculate total hours worked (including lunch) and include days span
    $interval = $timeIn->diff($timeOut);
    $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Only subtract 1 hour for lunch break if total hours is 8 or above
    $actualWorkedHours = $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours;
    
    // For Restday OT, allow filing for any positive worked hours (after lunch)
    if ($ot_type === 'Restday OT') {
        return $actualWorkedHours > 0;
    }
    
    // For regular OT, require 8.5 hours (8hrs 30mins)
    return $actualWorkedHours >= 8.5;
}

function calculateOvertimeHours($time_in, $time_out, $ot_type = null) {
    if (!$time_in || !$time_out) return 0;
    
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    
    // Calculate total hours worked (including lunch) and include days span
    $interval = $timeIn->diff($timeOut);
    $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Only subtract 1 hour for lunch break if total hours is 8 or above
    $actualWorkedHours = $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours;
    
    // For Restday OT, return all worked hours (minus lunch), even if < 8 hours
    if ($ot_type === 'Restday OT') {
        return $actualWorkedHours;
    }
    
    // Calculate overtime (anything over 8 hours of actual work)
    // Only count OT if they worked at least 8.5 hours for regular OT
    if ($actualWorkedHours >= 8.5) {
        return max(0, $actualWorkedHours - 8);
    }
    
    return 0;
}

function calculateActualHoursWorked($time_in, $time_out) {
    if (!$time_in || !$time_out) return 0;
    
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    
    // Calculate total hours and include days span
    $interval = $timeIn->diff($timeOut);
    $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Only subtract 1 hour for lunch break if total hours is 8 or above, but ensure no negative values
    return $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours;
}

function hasExistingOTRequest($time_log_id) {
    global $pdo;
    
    // Check active requests table first
    $stmt = $pdo->prepare("SELECT status FROM post_ot_requests WHERE time_log_id = ?");
    $stmt->execute([$time_log_id]);
    $activeRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeRequest) {
        return $activeRequest['status']; // pending
    }
    
    // Check archived requests table
    $stmt = $pdo->prepare("SELECT status FROM post2_overtime_requests WHERE time_log_id = ?");
    $stmt->execute([$time_log_id]);
    $archivedRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($archivedRequest) {
        return $archivedRequest['status']; // approved or declined
    }
    
    return false; // No request exists
}
?>

