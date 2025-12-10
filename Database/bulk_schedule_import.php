<?php
/**
 * Bulk Employee Schedule Import Script
 * Purpose: Import weekly default schedules for all employees from CSV/array data
 * Date: December 10, 2025
 */

require_once '../Public/config/db.php';

// Output formatting for web browser
if (php_sapi_name() != 'cli') {
    echo "<!DOCTYPE html><html><head><title>Bulk Schedule Import</title></head><body>";
    echo "<h1>Bulk Employee Schedule Import</h1>";
    echo "<pre style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
}

// Schedule mapping: schedule description => work_schedule_id
$scheduleMap = [
    '6:30AM-3:30PM' => 1,
    '8AM-7PM' => 2,
    '7:30AM-4:30PM' => 3,
    '7AM-4PM' => 4,
    '8AM-5PM' => 5,
    '9AM-6PM' => 6,
    '10AM-7PM' => 7,
    '6AM-3PM' => 8,
    '8AM-4:30PM' => 9,
    '7:40AM-4:40PM' => 10,
    '6:30AM-3PM' => 11,
    '6:30AM-5:30PM' => 12,
    '7AM-6PM' => 13,
    '6AM-5PM' => 14,
    '6AM-4PM' => 15,
    '8:30AM-4:30PM' => 16,
    '6AM-12PM' => 17,
    '6AM-2:30PM' => 18,
    '7PM-3AM' => 19,
    '7PM-4:30AM' => 20,
    '5PM-2AM' => 21,
    '5:30PM-2AM' => 22,
];

// Create missing schedules if needed
try {
    $pdo->exec("INSERT IGNORE INTO work_schedules (id, name, time_in, time_out) VALUES
        (23, '5AM-2PM', '05:00:00', '14:00:00'),
        (24, '5:30AM-2:30PM', '05:30:00', '14:30:00'),
        (25, '6PM-3AM Night', '18:00:00', '03:00:00')");
    
    $scheduleMap['5AM-2PM'] = 23;
    $scheduleMap['5:30AM-2:30PM'] = 24;
    $scheduleMap['6PM-3AM'] = 25;
    
    echo "✓ Additional schedules created\n";
} catch (PDOException $e) {
    echo "Warning: " . $e->getMessage() . "\n";
}

// Employee schedule data
// Format: [first_name, last_name, schedule_pattern]
$employeeSchedules = [
    ['Jillian Lao', 'Agas', 'Wed-Fri 6AM-5PM Sat 7AM-6PM | OFF Sun-Tues'],
    ['Ian Myco', 'Aguilar', 'Tue-Fri 6AM-5PM | Sat-Mon OFF'],
    ['Joel Lusung', 'Alimurong', 'M-Tue;Th-Fri 7AM-6PM | Wed,Sat-Sun OFF'],
    ['John Bryan', 'Alvarez', 'M-F 8AM-4:30PM'],
    ['Vincent Kevin Santos', 'Antonio', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Christine Khlaryss', 'Angeles', 'M-F 6AM-3PM'],
    ['Cedrick', 'Arnigo', 'M-F 9AM-6PM'],
    ['Louis Fernand Baluyot', 'Austria', 'M-Th 9AM-6PM | Fri 7AM-4PM'],
    ['Nika Nueva', 'Bacongallo', 'M-Tue;Thu-Fri 6AM-5PM | Wed,Sat,Sun OFF'],
    ['Glory Ann', 'Balderas', 'M-Fri 7AM-4PM'],
    ['Kristian David', 'Bansil', 'M-F 7AM-4PM'],
    ['Oliva', 'Bautista', 'M-F 8AM-4:30PM'],
    ['Karen', 'Belangel', 'M-FRI 8AM-5PM'],
    ['Renneca Villapaña', 'Benalla', 'M-Fri 7AM-4PM'],
    ['Francis Eugene Aguhayon', 'Bondoc', 'M-Fri 8:30AM-4:30PM'],
    ['John Michael Comprado', 'Briones', 'M-Fri 7AM-4PM'],
    ['Precious Zahra Cortez', 'Cabusao', 'M-Fri 7AM-4PM'],
    ['Yris Gaelle Parreñas', 'Camerino', 'M-T;Thu-Fri 7AM-6PM | Wed,Sat-Sun OFF'],
    ['Allen', 'Capati', 'Tue-Thu 7AM-6PM | Fri 8AM-7PM | Sat-Mon OFF'],
    ['Gabriel', 'Capiral', 'M-Fri 7AM-4PM'],
    ['Sarah', 'Caraan', 'M-F 8AM-4:30PM'],
    ['Aizel Santos', 'Castro', 'M-Th 7AM-6PM | Fri-Sun OFF'],
    ['Marnie Perez', 'Catalogo', 'Sun-Wed 7AM-6PM | Thu-Sat OFF'],
    ['Lovelaine', 'Celeste', 'M-Tue 10AM-7PM | Wed 7AM-4PM | Fri 9AM-6PM | Thu,Sat-Sun OFF'],
    ['Reymark Bryan Silvano', 'Colis', 'M-F 6AM-3PM'],
    ['Neil Anthony', 'Costelloe', 'M-F 7AM-4PM'], // Flexi - using default
    ['Elritz Tongson', 'Crisanto', 'Tue-Fri 7AM-6PM | Sat-Mon OFF'],
    ['Ron Paulo', 'Cueto', 'M-Fri 7AM-4PM'],
    ['Kimberly', 'Dacquil', 'M-Fri 7AM-4PM'],
    ['Joseph', 'David', 'M-Fri 7AM-4PM'],
    ['Rebecca', 'David', 'M-Fri 8:30AM-4:30PM'],
    ['Ryan Arwin', 'David', 'M-Fri 8AM-4:30PM'],
    ['Jonas', 'Dela Cruz', 'M-Fri 6AM-3PM'],
    ['Shaina Dimayugo', 'Dela Cruz', 'Tue 8AM-5PM | M,W,Th,Fr 6AM-3PM'],
    ['Jhosua', 'Dimla', '6PM-3AM'],
    ['Maria Nina Dollentes', 'Cruz', 'M-Fri 7AM-4PM'],
    ['Angelica', 'Estanio', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Francis Emmanuel Veloso', 'Fernandez', 'Thu-Sun 7AM-6PM | Mon-Wed OFF'],
    ['Marianne Jae Andrada', 'Fernandez', 'M-Fri 6AM-3PM'],
    ['Chloedean', 'Flores', 'M-Fri 5AM-2PM'],
    ['Kryssa', 'Gabatino', 'M-Fri 6:30AM-3:30PM'],
    ['Analiza Taloban', 'Gatbonton', 'Mon-Tue;Th-Fri,Sun 6AM-5PM | Wed,Sat OFF'],
    ['Beverly Taloban', 'Gatbonton', 'M-Fri 8AM-5PM'],
    ['Johana Rose Perez', 'Gueco', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Alfie', 'Guillermo', 'M-Fri 8AM-4:30PM'],
    ['Felicci', 'Herrera', 'M-Fri 7AM-4PM'],
    ['Jairus', 'Ignacio', 'M-Fri 6AM-3PM'],
    ['Adonis Del Mundo', 'Jabinal', 'M-Fri 7AM-4PM'],
    ['Renalyn Abamo', 'Josafat', 'M,W,Th 6AM-5PM | Tue 6:30AM-5:30PM | Fri-Sun OFF'],
    ['Aldwin John Arceo', 'Lozano', 'M-F 6AM-3PM'],
    ['Jeffry Tuazon', 'Macapagal', 'Mon-Fri 7:30AM-4:30PM'],
    ['Julie Anne', 'Maclang', 'Th-Fri;Sun-Mon 6AM-5PM | Tue-Wed,Sat OFF'],
    ['Althea Tansingco', 'Makabenta', 'M-Fri 9AM-6PM'],
    ['Rogelio Dela Peña', 'Malinao', 'Tue-Fri 8AM-7PM | Sat-Mon OFF'],
    ['Joshua', 'Manalili', 'M-Fri 7AM-3PM'],
    ['Christian', 'Mar', 'M-Tue,Thurs 7AM-6PM | Wed 8AM-7PM | Fri-Sun OFF'],
    ['Edith David', 'Mataga', 'M-Fri 8AM-5PM'],
    ['Trisha Mae Adriano', 'McGregor', 'M-F 7AM-4PM'],
    ['Sean Justine Francisco', 'Mendoza', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed-Sat-Sun OFF'],
    ['Joshwea', 'Monis', 'Tue,Thurs,Fri,Sat 6AM-5PM | Wed,Sun,Mon OFF'],
    ['Evanel Caacbay', 'Navalon', 'Mon-Wed;Sun 8AM-7PM | Thu-Sat OFF'],
    ['Ivy', 'Nuñez', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Dou Lester Sabando', 'Nuñeza', 'M,TH,F,Sat,Sun 8AM-5PM | Tue-Wed OFF'],
    ['Alfred Naguit', 'Ocampo', 'M-Fri 7AM-4PM'],
    ['Godwin Dionicio', 'Ocampo', 'M-Fri 7AM-4PM'],
    ['Shigeru Centina', 'Otsuka', 'M-Fri 7AM-4PM'],
    ['Cristina Miranda', 'Pangan', 'M-Fri 7AM-4PM'],
    ['Roi Dane', 'Pangilinan', 'M-Fri 7AM-3PM'],
    ['Apryl Pasion', 'Yap', 'M-F 8AM-5PM'],
    ['Paul', 'Pasion', 'M-Fri 7AM-3PM'],
    ['Sherry Rose Ann Nunag', 'Patawaran', 'M-Fri 5AM-2PM'],
    ['Rex Ryan', 'Patrimonio', 'M-Fri 10AM-7PM'],
    ['Erika Seriosa', 'Pineda', 'M-Th 7AM-6PM | Fri-Sun OFF'],
    ['Ma. Charisma S.', 'Platero', 'M-Fri 7AM-4PM'],
    ['Shirmiley Canlas', 'Quizon', 'M-Fri,Sunday 9AM-6PM'],
    ['Rhegene Ingat', 'Ronquillo', 'W-Fri 6AM-5PM, Sat 7AM-6PM | Sun,Mon,Tue OFF'],
    ['Milbert', 'Sambile', 'M-Fri 7AM-4PM'],
    ['Jhunel Carlo Traifalgar', 'Samodio', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat-Sun OFF'],
    ['Ray Jinder', 'Singh', 'M-Fri 7AM-4PM'],
    ['Janeth', 'Solayao', 'Mon-Tue;Thurs;Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Mary Ann Vallejos', 'Soriano', 'Th-Mon 7AM-4PM | Tue-Wed OFF'],
    ['Alexander', 'Tayao', 'M-Fri 7AM-4PM'],
    ['Ryan Jae', 'Tiglao', 'M-Fri 8AM-5PM'],
    ['Carl Dave', 'Tupaz', 'M-Fri 6AM-3PM'],
    ['Rica Joy', 'Tolomia', 'M-Fri 7AM-4PM'],
    ['Mylene', 'Torres', 'M-Fri 5:30AM-2:30PM'],
    ['Jennifer Mangitngit', 'Trinidad', 'Mon-Tue;Thu-Fri 7AM-6PM | Wed,Sat,Sun OFF'],
    ['Brittany', 'Yulo', 'M-Fri 8AM-5PM'],
];

/**
 * Parse schedule pattern and return array of [day_of_week => schedule_id]
 */
function parseSchedulePattern($pattern, $scheduleMap) {
    $weekSchedule = [
        0 => ['schedule_id' => null, 'is_rest_day' => 1], // Sunday
        1 => ['schedule_id' => null, 'is_rest_day' => 1], // Monday
        2 => ['schedule_id' => null, 'is_rest_day' => 1], // Tuesday
        3 => ['schedule_id' => null, 'is_rest_day' => 1], // Wednesday
        4 => ['schedule_id' => null, 'is_rest_day' => 1], // Thursday
        5 => ['schedule_id' => null, 'is_rest_day' => 1], // Friday
        6 => ['schedule_id' => null, 'is_rest_day' => 1], // Saturday
    ];
    
    // Normalize pattern
    $pattern = str_replace([';', ','], '|', $pattern);
    $pattern = preg_replace('/\s+/', ' ', $pattern);
    
    // Extract OFF days
    if (preg_match('/OFF[:\s]+(.*?)(?:\||$)/i', $pattern, $matches)) {
        $offDays = $matches[1];
        // OFF days are already set as rest_day=1, so we keep them
    }
    
    // Simple M-F pattern
    if (preg_match('/^M-F[RI]*\s+(\d{1,2}(?::\d{2})?[AP]M)-(\d{1,2}(?::\d{2})?[AP]M)$/i', $pattern, $matches)) {
        $timePattern = normalizeTime($matches[1]) . '-' . normalizeTime($matches[2]);
        $schedId = findScheduleId($timePattern, $scheduleMap);
        
        for ($day = 1; $day <= 5; $day++) {
            $weekSchedule[$day] = ['schedule_id' => $schedId, 'is_rest_day' => 0];
        }
        return $weekSchedule;
    }
    
    // Complex patterns - split by |
    $parts = explode('|', $pattern);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part) || stripos($part, 'OFF') !== false) continue;
        
        // Extract days and time
        if (preg_match('/^(.*?)\s+(\d{1,2}(?::\d{2})?[AP]M)\s*-\s*(\d{1,2}(?::\d{2})?[AP]M)/i', $part, $matches)) {
            $daysPart = $matches[1];
            $timePattern = normalizeTime($matches[2]) . '-' . normalizeTime($matches[3]);
            $schedId = findScheduleId($timePattern, $scheduleMap);
            
            $days = parseDayRange($daysPart);
            
            foreach ($days as $dayNum) {
                $weekSchedule[$dayNum] = ['schedule_id' => $schedId, 'is_rest_day' => 0];
            }
        }
    }
    
    return $weekSchedule;
}

function normalizeTime($time) {
    $time = strtoupper(trim($time));
    $time = str_replace([' ', '.'], '', $time);
    // Convert 5AM to 5:00AM, 530AM to 5:30AM
    if (!strpos($time, ':')) {
        if (preg_match('/^(\d{1,2})([AP]M)$/', $time, $m)) {
            $time = $m[1] . ':00' . $m[2];
        } elseif (preg_match('/^(\d{1})(\d{2})([AP]M)$/', $time, $m)) {
            $time = $m[1] . ':' . $m[2] . $m[3];
        }
    }
    return $time;
}

function findScheduleId($timePattern, $scheduleMap) {
    // Try exact match first
    foreach ($scheduleMap as $key => $id) {
        if (stripos($key, $timePattern) !== false || stripos($timePattern, $key) !== false) {
            return $id;
        }
    }
    
    // Parse time ranges and find closest match
    if (preg_match('/(\d{1,2}):?(\d{2})?([AP]M)-(\d{1,2}):?(\d{2})?([AP]M)/i', $timePattern, $m)) {
        $startTime = $m[1] . ($m[2] ?? '00') . $m[3];
        $endTime = $m[4] . ($m[5] ?? '00') . $m[6];
        
        foreach ($scheduleMap as $key => $id) {
            if (stripos($key, $startTime) !== false) {
                return $id;
            }
        }
    }
    
    return 4; // Default to 7AM-4PM if not found
}

function parseDayRange($daysPart) {
    $dayMap = [
        'SUN' => 0, 'SUNDAY' => 0,
        'MON' => 1, 'MONDAY' => 1, 'M' => 1,
        'TUE' => 2, 'TUES' => 2, 'TUESDAY' => 2, 'T' => 2,
        'WED' => 3, 'WEDNESDAY' => 3, 'W' => 3,
        'THU' => 4, 'THUR' => 4, 'THURS' => 4, 'THURSDAY' => 4, 'TH' => 4,
        'FRI' => 5, 'FRIDAY' => 5, 'F' => 5,
        'SAT' => 6, 'SATURDAY' => 6,
    ];
    
    $days = [];
    $daysPart = strtoupper(trim($daysPart));
    $daysPart = str_replace([';', ',', ' '], '|', $daysPart);
    
    // Handle M-F, Mon-Fri patterns
    if (preg_match('/^([A-Z]+)-([A-Z]+)$/', $daysPart, $m)) {
        $start = $dayMap[$m[1]] ?? null;
        $end = $dayMap[$m[2]] ?? null;
        
        if ($start !== null && $end !== null) {
            for ($i = $start; $i <= $end; $i++) {
                $days[] = $i;
            }
            return $days;
        }
    }
    
    // Handle individual days
    $parts = explode('|', $daysPart);
    foreach ($parts as $part) {
        $part = trim($part);
        if (isset($dayMap[$part])) {
            $days[] = $dayMap[$part];
        }
    }
    
    return $days;
}

// Main execution
echo "Starting bulk schedule import...\n\n";
$successCount = 0;
$errorCount = 0;
$notFoundCount = 0;

foreach ($employeeSchedules as $empData) {
    list($fname, $lname, $schedule) = $empData;
    
    try {
        // Find employee
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE fname = ? AND lname = ?");
        $stmt->execute([$fname, $lname]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$employee) {
            echo "❌ Employee not found: $fname $lname\n";
            $notFoundCount++;
            continue;
        }
        
        $employeeId = $employee['id'];
        
        // Parse schedule
        $weekSchedule = parseSchedulePattern($schedule, $scheduleMap);
        
        // Delete existing schedules
        $pdo->prepare("DELETE FROM employee_default_schedules WHERE employee_id = ?")->execute([$employeeId]);
        
        // Insert new schedule
        $stmt = $pdo->prepare("INSERT INTO employee_default_schedules 
            (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from, created_at) 
            VALUES (?, ?, ?, ?, '2025-01-01', NOW())");
        
        foreach ($weekSchedule as $dayNum => $dayData) {
            $stmt->execute([
                $employeeId,
                $dayNum,
                $dayData['schedule_id'],
                $dayData['is_rest_day']
            ]);
        }
        
        echo "✓ $fname $lname - Schedule imported\n";
        $successCount++;
        
    } catch (PDOException $e) {
        echo "❌ Error for $fname $lname: " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n========================================\n";
echo "Import Complete!\n";
echo "✓ Success: $successCount employees\n";
echo "❌ Errors: $errorCount employees\n";
echo "⚠ Not Found: $notFoundCount employees\n";
echo "========================================\n";

// Output for web browser
if (php_sapi_name() != 'cli') {
    echo "</pre>";
    echo "<h3>Cache Rebuild</h3>";
    echo "<p><a href='rebuild_schedule_cache.php' class='btn'>Click here to rebuild schedule cache (generate calendar entries)</a></p>";
    echo "<style>.btn { background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }</style>";
}

// Optional: Rebuild cache for CLI
if (php_sapi_name() == 'cli') {
    echo "\nDo you want to rebuild the schedule cache for all employees? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) == 'y') {
        echo "Rebuilding cache...\n";
        include_once 'rebuild_schedule_cache.php';
    }
    fclose($handle);
}

?>
