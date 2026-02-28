<?php
/**
 * DIAGNOSTIC: Check Leave Accrual Status - February 28, 2026
 * Upload this to production and run it to see what happened
 * URL: https://yourdomain.com/check_accrual_feb28.php
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

date_default_timezone_set('Asia/Manila');

echo "<html><head><title>Leave Accrual Diagnostic - Feb 28, 2026</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; background: white; }
th { background: #333; color: white; padding: 8px 12px; text-align: left; }
td { padding: 6px 12px; border: 1px solid #ddd; }
tr:nth-child(even) { background: #f9f9f9; }
.alert { padding: 15px; border-radius: 5px; margin: 10px 0; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.alert-warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
.maxed { background: #f8d7da !important; font-weight: bold; color: #721c24; }
</style></head><body>";

echo "<h2>🔍 Leave Accrual Diagnostic Report</h2>";
echo "<p><strong>Report Generated:</strong> " . date('Y-m-d H:i:s') . " (Manila Time)</p>";
echo "<p><strong>Today:</strong> February 28, 2026 — Last day of the month (accrual trigger day)</p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='alert alert-success'>✅ Connected to production database</div>";
    
    // ============================================
    // 1. CHECK SYSTEM SETTINGS
    // ============================================
    echo "<h2>1. System Settings (Auto-Accrual Config)</h2>";
    $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>Setting Key</th><th>Value</th><th>Updated At</th></tr>";
    $accrualEnabled = '0';
    $accrualMode = 'production';
    $lastProcessedMonth = 0;
    
    foreach ($settings as $s) {
        $highlight = '';
        if ($s['setting_key'] === 'auto_accrual_enabled') {
            $accrualEnabled = $s['setting_value'];
            if ($s['setting_value'] == '1') $highlight = "style='background:#fff3cd;'";
        }
        if ($s['setting_key'] === 'accrual_mode') {
            $accrualMode = $s['setting_value'];
            if ($s['setting_value'] === 'testing') $highlight = "style='background:#f8d7da; font-weight:bold;'";
        }
        if ($s['setting_key'] === 'last_auto_accrual_month') {
            $lastProcessedMonth = (int)$s['setting_value'];
        }
        echo "<tr {$highlight}><td>{$s['setting_key']}</td><td><strong>{$s['setting_value']}</strong></td><td>{$s['updated_at']}</td></tr>";
    }
    echo "</table>";
    
    // Analysis
    if ($accrualMode === 'testing') {
        echo "<div class='alert alert-danger'>🚨 <strong>BUG FOUND:</strong> Accrual mode is set to <strong>TESTING</strong>! This means accrual runs EVERY 10 SECONDS with NO duplicate protection. This would max out all leave credits rapidly!</div>";
    }
    if ($accrualEnabled == '1') {
        echo "<div class='alert alert-warning'>⚠️ Auto-accrual is currently <strong>ENABLED</strong></div>";
    }
    echo "<div class='alert alert-info'>📅 Last processed month: <strong>Month $lastProcessedMonth</strong> (" . ($lastProcessedMonth > 0 ? date('F', mktime(0,0,0,$lastProcessedMonth,1)) : 'Never') . ")</div>";
    
    // ============================================
    // 2. CHECK ALL 2026 LEAVE CREDITS FOR REGULAR EMPLOYEES
    // ============================================
    echo "<h2>2. Current 2026 Leave Credits (Regular Employees)</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            lc.id,
            lc.employee_id,
            CONCAT(e.fname, ' ', e.lname) as full_name,
            e.Emp_Type,
            lc.leave_type,
            lc.balance,
            lc.carry_over,
            lc.carried_over,
            lc.monthly_increment,
            lc.updated_at
        FROM leave_credits lc 
        JOIN employees e ON lc.employee_id = e.id 
        WHERE lc.year = 2026 
        AND lc.leave_type IN ('sick', 'vacation')
        AND e.Emp_Type IN ('Regular', 'Old_Regular')
        AND e.status = 'active'
        ORDER BY e.lname, e.fname, lc.leave_type
    ");
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maxedVL = 0;
    $maxedSL = 0;
    $totalRegular = 0;
    $employeesSeen = [];
    
    echo "<table>";
    echo "<tr><th>Employee</th><th>Type</th><th>Leave Type</th><th>Balance</th><th>Carry Over</th><th>Monthly Inc</th><th>Last Updated</th></tr>";
    
    foreach ($credits as $c) {
        $isMaxed = false;
        if ($c['leave_type'] === 'vacation' && floatval($c['balance']) >= 15) { $maxedVL++; $isMaxed = true; }
        if ($c['leave_type'] === 'sick' && floatval($c['balance']) >= 5) { $maxedSL++; $isMaxed = true; }
        
        $rowClass = $isMaxed ? "class='maxed'" : "";
        
        if (!in_array($c['employee_id'], $employeesSeen)) {
            $employeesSeen[] = $c['employee_id'];
            $totalRegular++;
        }
        
        echo "<tr {$rowClass}>";
        echo "<td>{$c['full_name']}</td>";
        echo "<td>{$c['Emp_Type']}</td>";
        echo "<td>{$c['leave_type']}</td>";
        echo "<td><strong>{$c['balance']}</strong></td>";
        echo "<td>{$c['carry_over']}</td>";
        echo "<td>{$c['monthly_increment']}</td>";
        echo "<td>{$c['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Summary:</strong><br>";
    echo "Total Regular employees with 2026 credits: <strong>$totalRegular</strong><br>";
    echo "Employees with maxed VL (15.00): <strong style='color:red;'>$maxedVL</strong><br>";
    echo "Employees with maxed SL (5.00): <strong style='color:red;'>$maxedSL</strong><br>";
    echo "</div>";
    
    // ============================================
    // 3. CHECK WHAT VALUES SHOULD HAVE BEEN (Spreadsheet + Feb accrual)
    // ============================================
    echo "<h2>3. Expected vs Actual Comparison</h2>";
    echo "<p>Based on the January 2026 spreadsheet data + 1 month of VL accrual (1.25 for February):</p>";
    
    // The spreadsheet values represent post-January balances
    // February should add only +1.25 VL (no SL accrual per new policy)
    $expectedData = [
        'AGAS' => ['vl' => 6.25, 'sl' => 4],
        'AGUILAR' => ['vl' => 6.25, 'sl' => 4],
        'ALIMURONG' => ['vl' => 5.75, 'sl' => 5],
        'ALVAREZ' => ['vl' => 5.75, 'sl' => 4],
        'ANGELES' => ['vl' => 1.75, 'sl' => 5],
        'ARNIGO' => ['vl' => 5.25, 'sl' => 4],
        'AUSTRIA' => ['vl' => 4.75, 'sl' => 5],
        'BACONGALLO' => ['vl' => 2.00, 'sl' => 5],
        'BANSIL' => ['vl' => 1.75, 'sl' => 1],
        'BAUTISTA' => ['vl' => 5.75, 'sl' => 5],
        'BALDERAS' => ['vl' => 1.75, 'sl' => 5],
        'BENALLA' => ['vl' => 6.25, 'sl' => 5],
        'BONDOC' => ['vl' => 6.25, 'sl' => 5],
        'BRIONES' => ['vl' => 3.25, 'sl' => 5],
        'CAMERINO' => ['vl' => 5.25, 'sl' => 4],
        'CAPATI' => ['vl' => 2.50, 'sl' => 4],
        'CARAAN' => ['vl' => 5.75, 'sl' => 5],
        'CASTRO' => ['vl' => 6.25, 'sl' => 5],
        'CELESTE' => ['vl' => 6.25, 'sl' => 5],
        'COLIS' => ['vl' => 2.25, 'sl' => 5],
        'CRISANTO' => ['vl' => 3.25, 'sl' => 5],
        'DAVID' => ['vl' => 5.25, 'sl' => 5],
        'DIMLA' => ['vl' => 6.25, 'sl' => 5],
        'DELA CRUZ' => ['vl' => 5.50, 'sl' => 5],
        'DOLLENTES' => ['vl' => 4.75, 'sl' => 5],
        'ESTANIO' => ['vl' => 7.50, 'sl' => 5],
        'FERNANDEZ' => ['vl' => 6.25, 'sl' => 5],
        'GATBONTON' => ['vl' => 1.25, 'sl' => 4],
        'GUECO' => ['vl' => 2.25, 'sl' => 5],
        'GUILLERMO' => ['vl' => 6.25, 'sl' => 5],
        'JABINAL' => ['vl' => 3.50, 'sl' => 5],
        'JOSAFAT' => ['vl' => 1.75, 'sl' => 4],
        'MACAPAGAL' => ['vl' => 6.25, 'sl' => 5],
        'MACLANG' => ['vl' => 4.75, 'sl' => 5],
        'MAKABENTA' => ['vl' => 6.25, 'sl' => 5],
        'MANALILI' => ['vl' => 7.50, 'sl' => null],
        'MAR' => ['vl' => 6.25, 'sl' => 5],
        'MCGREGOR' => ['vl' => 1.75, 'sl' => 5],
        'MENDOZA' => ['vl' => 5.50, 'sl' => 5],
        'MONIS' => ['vl' => 6.25, 'sl' => 5],
        'NUÑEZ' => ['vl' => 6.25, 'sl' => 4],
        'NUNEZA' => ['vl' => 5.75, 'sl' => 5],
        'OCAMPO' => ['vl' => 2.75, 'sl' => 5],
        'OTSUKA' => ['vl' => 3.75, 'sl' => 5],
        'PANGAN' => ['vl' => 5.25, 'sl' => 5],
        'PANGILINAN' => ['vl' => 7.50, 'sl' => null],
        'YAP' => ['vl' => 1.75, 'sl' => 3],
        'PATRIMONIO' => ['vl' => 2.25, 'sl' => 3],
        'PINEDA' => ['vl' => 2.50, 'sl' => 4],
        'PLATERO' => ['vl' => 6.25, 'sl' => 5],
        'QUIZON' => ['vl' => 5.75, 'sl' => 5],
        'RONQUILLO' => ['vl' => 5.75, 'sl' => 5],
        'SAMODIO' => ['vl' => 5.75, 'sl' => 4],
        'SOLAYAO' => ['vl' => 2.50, 'sl' => 4],
        'SINGH' => ['vl' => 3.25, 'sl' => 4],
        'SORIANO' => ['vl' => 2.50, 'sl' => 5],
        'TAYAO' => ['vl' => 1.25, 'sl' => 4],
        'TOLOMIA' => ['vl' => 6.25, 'sl' => 5],
        'TRINIDAD' => ['vl' => 2.00, 'sl' => 4],
        'YULO' => ['vl' => 1.50, 'sl' => 4],
    ];
    
    echo "<table>";
    echo "<tr><th>Employee</th><th>Type</th><th>Leave</th><th>Jan Balance</th><th>Expected Feb (+1.25 VL)</th><th>Actual Now</th><th>Difference</th><th>Status</th></tr>";
    
    $wrongCount = 0;
    foreach ($credits as $c) {
        // Try to match by last name
        $nameParts = explode(' ', $c['full_name']);
        $lastName = end($nameParts);
        $lastNameUpper = strtoupper($lastName);
        
        if (isset($expectedData[$lastNameUpper]) || isset($expectedData[$lastName])) {
            $expected = $expectedData[$lastNameUpper] ?? $expectedData[$lastName] ?? null;
            if (!$expected) continue;
            
            $leaveKey = ($c['leave_type'] === 'vacation') ? 'vl' : 'sl';
            $janBalance = $expected[$leaveKey];
            if ($janBalance === null) continue;
            
            // Expected after Feb accrual: VL gets +1.25, SL stays same (no accrual per new policy)
            $expectedFeb = ($c['leave_type'] === 'vacation') ? $janBalance + 1.25 : $janBalance;
            $actual = floatval($c['balance']);
            $diff = $actual - $expectedFeb;
            
            $status = (abs($diff) < 0.01) ? "✅ OK" : "❌ WRONG (+$diff)";
            $rowStyle = (abs($diff) > 0.01) ? "class='maxed'" : "";
            
            if (abs($diff) > 0.01) $wrongCount++;
            
            echo "<tr {$rowStyle}>";
            echo "<td>{$c['full_name']}</td>";
            echo "<td>{$c['Emp_Type']}</td>";
            echo "<td>{$c['leave_type']}</td>";
            echo "<td>$janBalance</td>";
            echo "<td><strong>$expectedFeb</strong></td>";
            echo "<td><strong>$actual</strong></td>";
            echo "<td>" . ($diff > 0 ? "+$diff" : $diff) . "</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    if ($wrongCount > 0) {
        echo "<div class='alert alert-danger'>🚨 <strong>$wrongCount records have WRONG values!</strong> The auto-accrual likely ran multiple times.</div>";
    }
    
    // ============================================
    // 4. CHECK updated_at timestamps to see HOW MANY TIMES it ran today
    // ============================================
    echo "<h2>4. Accrual Activity Today (Feb 28)</h2>";
    $stmt = $pdo->query("
        SELECT updated_at, COUNT(*) as records_updated
        FROM leave_credits 
        WHERE year = 2026 
        AND DATE(updated_at) = '2026-02-28'
        GROUP BY updated_at
        ORDER BY updated_at DESC
        LIMIT 50
    ");
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($updates) > 0) {
        echo "<table><tr><th>Updated At</th><th>Records Updated</th></tr>";
        foreach ($updates as $u) {
            echo "<tr><td>{$u['updated_at']}</td><td>{$u['records_updated']}</td></tr>";
        }
        echo "</table>";
        echo "<div class='alert alert-warning'>Found <strong>" . count($updates) . "</strong> distinct update timestamps today. If there are many, the accrual ran multiple times!</div>";
    } else {
        echo "<div class='alert alert-info'>No updates found for today (Feb 28). The accrual may not have run yet today, or it ran on a different date.</div>";
        
        // Check recent updates
        $stmt = $pdo->query("
            SELECT DATE(updated_at) as update_date, COUNT(*) as records_updated
            FROM leave_credits 
            WHERE year = 2026 
            AND updated_at IS NOT NULL
            GROUP BY DATE(updated_at)
            ORDER BY update_date DESC
            LIMIT 10
        ");
        $recentUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Update Dates:</h3>";
        echo "<table><tr><th>Date</th><th>Records Updated</th></tr>";
        foreach ($recentUpdates as $ru) {
            echo "<tr><td>{$ru['update_date']}</td><td>{$ru['records_updated']}</td></tr>";
        }
        echo "</table>";
    }
    
    // ============================================
    // 5. CHECK if leave_credits table has last_processed_month column
    // ============================================
    echo "<h2>5. Table Structure Check</h2>";
    $stmt = $pdo->query("DESCRIBE leave_credits");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasLastProcessedMonth = false;
    echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        if ($col['Field'] === 'last_processed_month') $hasLastProcessedMonth = true;
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    if (!$hasLastProcessedMonth) {
        echo "<div class='alert alert-danger'>🚨 <strong>MISSING COLUMN:</strong> <code>last_processed_month</code> does NOT exist in leave_credits table! The per-record duplicate protection is NOT working!</div>";
    }
    
    echo "<hr><h2>🔧 Root Cause Analysis</h2>";
    echo "<div class='alert alert-warning'>";
    echo "<strong>Potential Issues Found:</strong><br>";
    echo "1. <code>process_auto_accrual.php</code> (JS-triggered) uses only global <code>last_auto_accrual_month</code> in system_settings for duplicate check<br>";
    echo "2. <code>auto_accrual_cron.php</code> still accrues BOTH sick + vacation (outdated code)<br>";
    echo "3. If accrual_mode = 'testing', accrual runs every 10 seconds with NO duplicate protection<br>";
    echo "4. Multiple entry points can trigger accrual (admin_homepage.php, time_log_create.php, cron)<br>";
    echo "</div>";
    
    echo "<p><em>Script complete. Please share these results so we can fix the issue.</em></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
