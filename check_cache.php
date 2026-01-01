<?php
$pdo = new PDO("mysql:host=localhost;dbname=rss", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Checking cached schedules for Pangilinan and Pasion (January 2026):\n\n";

$stmt = $pdo->query("
    SELECT 
        e.lname, e.fname, 
        cache.schedule_date, 
        cache.schedule_name,
        cache.time_in, 
        cache.time_out,
        cache.source,
        cache.is_rest_day
    FROM employee_daily_schedule_cache cache
    JOIN employees e ON cache.employee_id = e.id
    WHERE e.lname IN ('Pangilinan', 'Pasion')
    AND cache.schedule_date BETWEEN '2026-01-01' AND '2026-01-10'
    ORDER BY e.lname, cache.schedule_date
");

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dayName = date('D', strtotime($row['schedule_date']));
    echo $row['lname'] . ', ' . $row['fname'] . "\n";
    echo "  " . $row['schedule_date'] . ' (' . $dayName . '): ';
    if ($row['is_rest_day']) {
        echo "REST DAY\n";
    } else {
        echo $row['schedule_name'] . ' - ' . $row['time_in'] . ' to ' . $row['time_out'];
        echo ' [Source: ' . $row['source'] . "]\n";
    }
    echo "\n";
}
