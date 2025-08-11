<?php
include('Public/config/db.php');

echo "=== INSERTING SAMPLE TIME LOGS FOR BUGARDI EMPLOYEES ===\n";

// Get all Bugardi employees
$stmt = $pdo->query("SELECT id, fname, lname FROM employees WHERE LOWER(TRIM(company)) = 'bugardi'");
$bugardi_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($bugardi_employees) . " Bugardi employees:\n";
foreach ($bugardi_employees as $emp) {
    echo "- " . $emp['fname'] . " " . $emp['lname'] . " (ID: " . $emp['id'] . ")\n";
}

// Sample time log data for the past 2 weeks
$sample_dates = [
    ['2025-08-01', '08:00:00', '17:00:00'],
    ['2025-08-02', '08:15:00', '17:30:00'],
    ['2025-08-05', '07:45:00', '16:45:00'],
    ['2025-08-06', '08:30:00', '17:15:00'],
    ['2025-08-07', '08:00:00', '17:00:00'],
    ['2025-08-08', '08:10:00', '17:10:00'],
    ['2025-08-09', '07:55:00', '16:55:00'],
    ['2025-08-10', '08:20:00', '17:20:00'],
    ['2025-08-11', '08:00:00', null], // Today - still working
];

$insert_stmt = $pdo->prepare("
    INSERT INTO time_logs (employee_id, log_date, time_in, time_out, is_late_in, is_early_out) 
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    time_in = VALUES(time_in),
    time_out = VALUES(time_out),
    is_late_in = VALUES(is_late_in),
    is_early_out = VALUES(is_early_out)
");

$total_inserted = 0;

foreach ($bugardi_employees as $employee) {
    echo "\nInserting time logs for " . $employee['fname'] . " " . $employee['lname'] . ":\n";
    
    foreach ($sample_dates as $date_data) {
        $log_date = $date_data[0];
        $time_in = $date_data[1];
        $time_out = $date_data[2];
        
        // Determine if late (after 8:00 AM) or early out (before 5:00 PM)
        $is_late_in = ($time_in > '08:00:00') ? 1 : 0;
        $is_early_out = ($time_out && $time_out < '17:00:00') ? 1 : 0;
        
        try {
            $insert_stmt->execute([
                $employee['id'],
                $log_date,
                $time_in,
                $time_out,
                $is_late_in,
                $is_early_out
            ]);
            
            echo "  ✓ " . $log_date . " - In: " . $time_in . " Out: " . ($time_out ?: 'Still working') . "\n";
            $total_inserted++;
        } catch (Exception $e) {
            echo "  ✗ Error inserting " . $log_date . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total time logs inserted/updated: " . $total_inserted . "\n";

// Verify the insertion
$verify_stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM time_logs tl 
    JOIN employees e ON tl.employee_id = e.id 
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
");
$result = $verify_stmt->fetch();
echo "Total Bugardi time logs in database: " . $result['count'] . "\n";

echo "\n=== RECENT BUGARDI TIME LOGS ===\n";
$recent_stmt = $pdo->query("
    SELECT tl.log_date, tl.time_in, tl.time_out, e.fname, e.lname
    FROM time_logs tl 
    JOIN employees e ON tl.employee_id = e.id 
    WHERE LOWER(TRIM(e.company)) = 'bugardi'
    ORDER BY tl.log_date DESC, e.fname
    LIMIT 10
");

while ($row = $recent_stmt->fetch()) {
    echo $row['log_date'] . " - " . $row['fname'] . " " . $row['lname'] . 
         " (In: " . $row['time_in'] . ", Out: " . ($row['time_out'] ?: 'Working') . ")\n";
}

echo "\n✅ Sample time logs have been inserted successfully!\n";
echo "You can now view them at: http://localhost/Timekeeping-system/Public/Bugardi/time_logs.php\n";
?>
