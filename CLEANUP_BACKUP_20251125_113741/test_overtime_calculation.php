<?php
// Test script for SIMPLIFIED overtime calculation
include('Public/config/db.php');
include('Public/module/new_overtime.php');

// Test parameters for your specific case
$employee_id = 1; // Change to your actual employee ID
$test_date = '2025-08-14'; // Change to the date you want to test
$test_time_in = '2025-08-14 07:00:00'; // Your actual time in
$test_time_out = '2025-08-14 17:00:00'; // Your actual time out (adjust as needed)

echo "<h2>🕐 SIMPLIFIED Overtime Calculation Test</h2>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>📋 New Simple Rules:</h3>";
echo "<ol>";
echo "<li><strong>Clock in</strong> at your normal start time</li>";
echo "<li><strong>Work your regular shift</strong> as usual</li>";
echo "<li><strong>Keep working past your scheduled end time</strong> if doing OT</li>";
echo "<li><strong>Clock out</strong> when done (including OT)</li>";
echo "<li><strong>System auto-flags OT</strong> if you work 30+ minutes past scheduled end time</li>";
echo "<li><strong>1-hour lunch break</strong> is always deducted</li>";
echo "<li><strong>5-day expiry</strong> for OT requests</li>";
echo "</ol>";
echo "</div>";

echo "<strong>Testing for Employee ID:</strong> $employee_id<br>";
echo "<strong>Date:</strong> $test_date<br>";
echo "<strong>Time In:</strong> $test_time_in<br>";
echo "<strong>Time Out:</strong> $test_time_out<br><br>";

// Get actual data from database for this employee and date
$actual_sql = "SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ? ORDER BY time_in DESC LIMIT 1";
$actual_stmt = $pdo->prepare($actual_sql);
$actual_stmt->execute([$employee_id, $test_date]);
$actual_log = $actual_stmt->fetch(PDO::FETCH_ASSOC);

if ($actual_log) {
    echo "<h3>🔍 Actual Database Data for $test_date:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f8ff;'><td><strong>Time In</strong></td><td>" . $actual_log['time_in'] . "</td></tr>";
    echo "<tr><td><strong>Time Out</strong></td><td>" . $actual_log['time_out'] . "</td></tr>";
    echo "<tr style='background-color: #f0f8ff;'><td><strong>Log Date</strong></td><td>" . $actual_log['log_date'] . "</td></tr>";
    echo "<tr><td><strong>Log ID</strong></td><td>" . $actual_log['id'] . "</td></tr>";
    echo "</table><br>";
    
    // Use actual data for testing
    $test_time_in = $actual_log['time_in'];
    $test_time_out = $actual_log['time_out'];
    $test_date = $actual_log['log_date'];
    
    echo "<strong>📊 Using Actual Data:</strong><br>";
    echo "Time In: $test_time_in<br>";
    echo "Time Out: $test_time_out<br>";
    echo "Date: $test_date<br><br>";
} else {
    echo "<p style='color: red; background-color: #ffe6e6; padding: 10px; border-radius: 5px;'>";
    echo "<strong>⚠️ No data found in database for Employee $employee_id on $test_date</strong><br>";
    echo "Using test parameters instead...</p><br>";
}

// Get schedule information
$scheduleInfo = getScheduleForDate($employee_id, $test_date, $pdo);
echo "<h3>📅 Schedule Information:</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f8ff;'><td><strong>Scheduled Time In</strong></td><td>" . $scheduleInfo['time_in'] . "</td></tr>";
echo "<tr><td><strong>Scheduled Time Out</strong></td><td>" . $scheduleInfo['time_out'] . "</td></tr>";
echo "<tr style='background-color: #f0f8ff;'><td><strong>Schedule ID</strong></td><td>" . $scheduleInfo['schedule_id'] . "</td></tr>";
echo "<tr><td><strong>Status</strong></td><td>" . $scheduleInfo['status_text'] . "</td></tr>";
echo "<tr style='background-color: #f0f8ff;'><td><strong>Was Changed</strong></td><td>" . ($scheduleInfo['was_changed'] ? 'Yes' : 'No') . "</td></tr>";
echo "</table><br>";

// Test eligibility using simplified logic
$isEligible = isOvertimeEligibleBySchedule($test_time_in, $test_time_out, $test_date, $employee_id, $pdo);
$otHours = calculateOvertimeHoursBySchedule($test_time_in, $test_time_out, $test_date, $employee_id, $pdo);
$details = getOvertimeCalculationDetails($test_time_in, $test_time_out, $test_date, $employee_id, $pdo);

echo "<h3>🎯 SIMPLIFIED Overtime Results:</h3>";
$eligibleColor = $isEligible ? 'green' : 'red';
$eligibleBg = $isEligible ? '#e8f5e8' : '#ffe6e6';
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background-color: $eligibleBg;'><td><strong>🏆 Eligible</strong></td><td style='color: $eligibleColor; font-weight: bold; font-size: 16px;'>" . ($isEligible ? '✅ YES' : '❌ NO') . "</td></tr>";
echo "<tr><td><strong>⏰ OT Hours</strong></td><td><strong>" . $otHours . " hours</strong></td></tr>";
echo "<tr style='background-color: #f9f9f9;'><td><strong>📝 Reason</strong></td><td>" . $details['reason'] . "</td></tr>";
echo "</table><br>";

echo "<h3>🔬 Detailed Calculation Breakdown:</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";

// Schedule details
echo "<tr style='background-color: #e6f3ff;'><td colspan='2'><strong>📋 SCHEDULE</strong></td></tr>";
echo "<tr><td><strong>Scheduled End Time</strong></td><td>" . $details['details']['schedule']['out'] . "</td></tr>";
echo "<tr style='background-color: #f9f9f9;'><td><strong>Scheduled Hours (with lunch)</strong></td><td>" . $details['details']['schedule']['hours'] . "h</td></tr>";
echo "<tr><td><strong>Scheduled Work Hours (- 1h lunch)</strong></td><td>" . $details['details']['schedule']['hours_minus_lunch'] . "h</td></tr>";

// Actual work details
echo "<tr style='background-color: #fff2e6;'><td colspan='2'><strong>⏱️ ACTUAL WORK</strong></td></tr>";
echo "<tr><td><strong>Actual End Time</strong></td><td>" . $details['details']['actual']['out'] . "</td></tr>";
echo "<tr style='background-color: #f9f9f9;'><td><strong>Total Time (with lunch)</strong></td><td>" . $details['details']['actual']['hours'] . "h</td></tr>";
echo "<tr><td><strong>Work Hours (- 1h lunch)</strong></td><td>" . $details['details']['actual']['hours_minus_lunch'] . "h</td></tr>";

// Overtime calculation
echo "<tr style='background-color: #e8f5e8;'><td colspan='2'><strong>💰 OVERTIME CALCULATION</strong></td></tr>";
echo "<tr><td><strong>Minutes Past Scheduled End</strong></td><td>" . $details['details']['overtime_minutes'] . " minutes</td></tr>";
echo "<tr style='background-color: #f9f9f9;'><td><strong>Overtime Hours</strong></td><td>" . $details['details']['overtime_hours'] . " hours</td></tr>";
echo "<tr><td><strong>Minimum Required</strong></td><td>" . $details['details']['minimum_required'] . " minutes past end time</td></tr>";
echo "<tr style='background-color: " . ($details['details']['eligible'] ? '#e8f5e8' : '#ffe6e6') . ";'><td><strong>Eligible</strong></td><td>" . ($details['details']['eligible'] ? '✅ YES' : '❌ NO') . "</td></tr>";

echo "</table><br>";

// Analysis and recommendations
echo "<h3>🧐 Analysis:</h3>";
if ($details['details']['overtime_minutes'] == 0) {
    echo "<div style='background-color: #ffe6e6; border: 2px solid #ff9999; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='color: #cc0000;'>❌ No Overtime Detected</h4>";
    echo "<p><strong>Issue:</strong> You did not work past your scheduled end time of " . $details['details']['schedule']['out'] . "</p>";
    echo "<p><strong>Your end time:</strong> " . $details['details']['actual']['out'] . "</p>";
    echo "</div><br>";
} elseif ($details['details']['overtime_minutes'] > 0 && $details['details']['overtime_minutes'] < 30) {
    echo "<div style='background-color: #fff3cd; border: 2px solid #ffecb5; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='color: #856404;'>⚠️ Insufficient Overtime</h4>";
    echo "<p><strong>Issue:</strong> You worked " . $details['details']['overtime_minutes'] . " minutes past your scheduled end time.</p>";
    echo "<p><strong>Requirement:</strong> You need to work at least 30 minutes past scheduled end time to qualify for OT.</p>";
    echo "<p><strong>Short by:</strong> " . (30 - $details['details']['overtime_minutes']) . " minutes</p>";
    echo "</div><br>";
} else {
    echo "<div style='background-color: #d4edda; border: 2px solid #c3e6cb; padding: 15px; border-radius: 8px;'>";
    echo "<h4 style='color: #155724;'>✅ Overtime Qualified!</h4>";
    echo "<p><strong>Great!</strong> You worked " . $details['details']['overtime_minutes'] . " minutes past your scheduled end time.</p>";
    echo "<p><strong>OT Hours:</strong> " . $details['details']['overtime_hours'] . " hours</p>";
    echo "<p><strong>Status:</strong> You should be able to submit an OT request for this day!</p>";
    echo "</div><br>";
}

echo "<h3>📝 Step-by-Step Example:</h3>";
echo "<div style='background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Example Scenario:</strong></p>";
echo "<ul>";
echo "<li><strong>Your Schedule:</strong> 7:00 AM - 4:00 PM (9 hours including lunch)</li>";
echo "<li><strong>Your Work:</strong> 7:00 AM - 5:30 PM (10.5 hours including lunch)</li>";
echo "<li><strong>Calculation:</strong> You worked 1.5 hours (90 minutes) past 4:00 PM</li>";
echo "<li><strong>Result:</strong> ✅ OT Eligible (90 min > 30 min requirement)</li>";
echo "<li><strong>OT Hours:</strong> 1.5 hours</li>";
echo "</ul>";
echo "</div><br>";

echo "<h3>🛠️ Troubleshooting:</h3>";
echo "<div style='background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px;'>";
echo "<ul>";
echo "<li><strong>Button not showing?</strong> Refresh the overtime page after these changes</li>";
echo "<li><strong>Still not eligible?</strong> Make sure you worked at least 30 minutes past your scheduled END time</li>";
echo "<li><strong>Wrong schedule?</strong> Check if you have schedule changes that affected this date</li>";
echo "<li><strong>Data looks wrong?</strong> Contact IT/HR to verify time clock data</li>";
echo "</ul>";
echo "</div>";
?>

echo "<h3>Overtime Calculation Results:</h3>";
echo "<strong>Eligible:</strong> " . ($isEligible ? 'YES' : 'NO') . "<br>";
echo "<strong>OT Hours:</strong> " . $otHours . "<br>";
echo "<strong>Reason:</strong> " . $details['reason'] . "<br><br>";

echo "<h3>Detailed Breakdown:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><td><strong>Scheduled Hours</strong></td><td>" . $details['details']['schedule']['hours'] . "</td></tr>";
echo "<tr><td><strong>Actual Hours Worked</strong></td><td>" . $details['details']['actual']['hours'] . "</td></tr>";
echo "<tr><td><strong>Late Minutes</strong></td><td>" . $details['details']['late_minutes'] . "</td></tr>";
echo "<tr><td><strong>Raw Overtime Minutes</strong></td><td>" . $details['details']['raw_overtime_minutes'] . "</td></tr>";
echo "<tr><td><strong>Net Overtime Minutes</strong></td><td>" . $details['details']['net_overtime_minutes'] . "</td></tr>";
echo "<tr><td><strong>Net Overtime Hours</strong></td><td>" . $details['details']['net_overtime_hours'] . "</td></tr>";
echo "<tr><td><strong>Minimum Required</strong></td><td>" . $details['details']['minimum_required'] . " minutes</td></tr>";
echo "</table><br>";

echo "<h3>Schedule Details:</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Type</th><th>Time In</th><th>Time Out</th><th>Duration</th></tr>";
echo "<tr><td>Scheduled</td><td>" . $details['details']['schedule']['in'] . "</td><td>" . $details['details']['schedule']['out'] . "</td><td>" . $details['details']['schedule']['hours'] . "h</td></tr>";
echo "<tr><td>Actual</td><td>" . $details['details']['actual']['in'] . "</td><td>" . $details['details']['actual']['out'] . "</td><td>" . $details['details']['actual']['hours'] . "h</td></tr>";
echo "</table><br>";

// Instructions
echo "<h3>How to Use This Test:</h3>";
echo "<ol>";
echo "<li>Modify the test parameters at the top of this file</li>";
echo "<li>Set your employee ID, date, time in, and time out</li>";
echo "<li>Run this file to see the detailed calculation</li>";
echo "<li>This will help you understand why you may not be OT eligible</li>";
echo "</ol>";

echo "<h3>Common Reasons for Not Being OT Eligible:</h3>";
echo "<ul>";
echo "<li><strong>Not working past scheduled end time:</strong> You need to work beyond your scheduled end time to earn OT</li>";
echo "<li><strong>Late arrival penalty:</strong> Being late more than 15 minutes reduces your OT hours</li>";
echo "<li><strong>Insufficient OT time:</strong> You need at least 30 minutes of net overtime to be eligible</li>";
echo "<li><strong>Schedule issue:</strong> Your active schedule might be different than expected</li>";
echo "</ul>";
?>
