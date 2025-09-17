<?php
function isOvertimeEligible($time_in, $time_out, $ot_type = null) {
    if (!$time_in || !$time_out) return false;
    
    $timeIn = new DateTime($time_in);
    $timeOut = new DateTime($time_out);
    
    // Calculate total hours worked (including lunch) and include days span
    $interval = $timeIn->diff($timeOut);
    $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Subtract 1 hour for lunch break
    $actualWorkedHours = max(0, $totalHours - 1);
    
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
    
    // Subtract 1 hour for lunch break
    $actualWorkedHours = max(0, $totalHours - 1);
    
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
    
    // Subtract 1 hour for lunch break, but ensure no negative values
    return max(0, $totalHours - 1);
}

function hasExistingOTRequest($time_log_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM post_ot_requests WHERE time_log_id = ?");
    $stmt->execute([$time_log_id]);
    return $stmt->rowCount() > 0;
}
?>

