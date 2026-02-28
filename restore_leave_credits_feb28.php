<?php
/**
 * RESTORE LEAVE CREDITS - Undo Feb 28, 2026 Buggy Accrual
 * 
 * This script:
 * 1. Restores VL to January 2026 spreadsheet values (known-good baseline)
 * 2. Restores SL ONLY where it went UP (bug), leaves usage-based decreases alone
 * 3. Checks for approved VL/SL leaves filed in Feb to deduct them from restored balance
 * 4. Fixes system_settings duplicate rows (ROOT CAUSE of the bug)
 * 5. Disables auto-accrual until code is fixed
 * 6. Lists employees not in spreadsheet for manual review
 * 
 * ROOT CAUSE: system_settings has DUPLICATE rows for 'last_auto_accrual_month'.
 * SELECT gets the oldest row (stale value), INSERT...ON DUPLICATE KEY creates new rows
 * instead of updating. So the "already processed" check NEVER works → accrual runs
 * hundreds of times, maxing out all VL to 15.00 + carry_over 5.00
 * 
 * USAGE: Upload to production, run with ?confirm=yes
 * URL: https://yourdomain.com/restore_leave_credits_feb28.php?confirm=yes
 */

$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

date_default_timezone_set('Asia/Manila');

echo "<html><head><title>Restore Leave Credits - Feb 28 Fix</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
h3 { color: #555; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; background: white; }
th { background: #333; color: white; padding: 8px 12px; text-align: left; font-size: 13px; }
td { padding: 6px 12px; border: 1px solid #ddd; font-size: 13px; }
tr:nth-child(even) { background: #f9f9f9; }
.alert { padding: 15px; border-radius: 5px; margin: 10px 0; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.alert-warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
.fixed { background: #d4edda !important; }
.manual { background: #fff3cd !important; }
</style></head><body>";

echo "<h2>🔧 Restore Leave Credits — Undo Feb 28 Buggy Accrual</h2>";
echo "<p><strong>Script Run:</strong> " . date('Y-m-d H:i:s') . "</p>";

$isDryRun = !isset($_GET['confirm']) || $_GET['confirm'] !== 'yes';

if ($isDryRun) {
    echo "<div class='alert alert-warning'><strong>⚠️ DRY RUN MODE</strong> — No changes will be made. Review the plan below, then add <code>?confirm=yes</code> to execute.</div>";
} else {
    echo "<div class='alert alert-danger'><strong>🔴 LIVE MODE</strong> — Changes WILL be applied to the database!</div>";
}

// January 2026 spreadsheet data - the KNOWN CORRECT baseline
// These are the post-January balances (after any Jan usage, before Feb accrual)
$spreadsheetData = [
    // [db_match_lname, db_match_fname (optional), vl, sl]
    ['lname' => 'Agas', 'fname' => 'Jillian', 'vl' => 6.25, 'sl' => 4],
    ['lname' => 'Aguilar', 'fname' => 'Ian Myco', 'vl' => 6.25, 'sl' => 4],
    ['lname' => 'Alimurong', 'fname' => 'Joel', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Alvarez', 'fname' => 'John Bryan', 'vl' => 5.75, 'sl' => 4],
    ['lname' => 'Santos', 'fname' => 'Vincent Kevin', 'vl' => 3.25, 'sl' => 4],
    ['lname' => 'Angeles', 'fname' => 'Christine', 'vl' => 1.75, 'sl' => 5],
    ['lname' => 'Arnigo', 'fname' => 'Cedrick', 'vl' => 5.25, 'sl' => 4],
    ['lname' => 'Austria', 'fname' => 'Louis', 'vl' => 4.75, 'sl' => 5],
    ['lname' => 'Bacongallo', 'fname' => 'Nika', 'vl' => 2.00, 'sl' => 5],
    ['lname' => 'Bansil', 'fname' => 'Kristian', 'vl' => 1.75, 'sl' => 1],
    ['lname' => 'Bautista', 'fname' => 'Oliv', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Balderas', 'fname' => 'Glory', 'vl' => 1.75, 'sl' => 5],
    ['lname' => 'Benalla', 'fname' => 'Rene', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Bondoc', 'fname' => 'Francis', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Briones', 'fname' => 'John Michael', 'vl' => 3.25, 'sl' => 5],
    ['lname' => 'Camerino', 'fname' => 'Yris', 'vl' => 5.25, 'sl' => 4],
    ['lname' => 'Capati', 'fname' => 'Allen', 'vl' => 2.50, 'sl' => 4],
    ['lname' => 'Caraan', 'fname' => 'Sarah', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Castro', 'fname' => 'Aizel', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Catalogo', 'fname' => 'Marnie', 'vl' => 1.75, 'sl' => 5],
    ['lname' => 'Celeste', 'fname' => 'Lovelaine', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Colis', 'fname' => 'Reymark', 'vl' => 2.25, 'sl' => 5],
    ['lname' => 'Crisanto', 'fname' => 'Elritz', 'vl' => 3.25, 'sl' => 5],
    ['lname' => 'David', 'fname' => 'Ryan Arwin', 'vl' => 5.25, 'sl' => 5],
    ['lname' => 'Dimla', 'fname' => 'Jhosua', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Dela Cruz', 'fname' => 'Jonas', 'vl' => 5.50, 'sl' => 5],
    ['lname' => 'Dela Cruz', 'fname' => 'Shaina', 'vl' => 2.00, 'sl' => 5],
    ['lname' => 'Dollentes Cruz', 'fname' => 'Maria', 'vl' => 4.75, 'sl' => 5],
    ['lname' => 'Estanio', 'fname' => 'Angelica', 'vl' => 7.50, 'sl' => 5],
    ['lname' => 'Fernandez', 'fname' => 'Francis Emmanuel', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Gatbonton', 'fname' => 'Analiza', 'vl' => 1.25, 'sl' => 4],
    ['lname' => 'Gatbonton', 'fname' => 'Beverly', 'vl' => 2.00, 'sl' => 5],
    ['lname' => 'Gueco', 'fname' => 'Johana', 'vl' => 2.25, 'sl' => 5],
    ['lname' => 'Guillermo', 'fname' => 'Alfie', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Jabinal', 'fname' => 'Adonis', 'vl' => 3.50, 'sl' => 5],
    ['lname' => 'Josafat', 'fname' => 'Renalyn', 'vl' => 1.75, 'sl' => 4],
    ['lname' => 'Arceo Lozano', 'fname' => 'Aldwin', 'vl' => 3.75, 'sl' => 4],
    ['lname' => 'Macapagal', 'fname' => 'Jeffry', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Maclang', 'fname' => 'Julie Anne', 'vl' => 4.75, 'sl' => 5],
    ['lname' => 'Makabenta', 'fname' => 'Althea', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Malinao Jr', 'fname' => 'Rogelio', 'vl' => 3.00, 'sl' => 4],
    ['lname' => 'Manalili', 'fname' => 'Joshua', 'vl' => 7.50, 'sl' => null],
    ['lname' => 'David Mataga', 'fname' => 'Edith', 'vl' => 6.25, 'sl' => 2.5],
    ['lname' => 'Mar', 'fname' => 'Christian', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'McGregor', 'fname' => 'Trisha', 'vl' => 1.75, 'sl' => 5],
    ['lname' => 'Mendoza', 'fname' => 'Sean', 'vl' => 5.50, 'sl' => 5],
    ['lname' => 'Monis', 'fname' => 'Joshwea', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Navalon', 'fname' => 'Evanel', 'vl' => 1.25, 'sl' => 4],
    ['lname' => 'Nuñez', 'fname' => 'Ivy', 'vl' => 6.25, 'sl' => 4],
    ['lname' => 'Nuñeza', 'fname' => 'Dou Lester', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Ocampo', 'fname' => 'Alfred', 'vl' => 2.75, 'sl' => 5],
    ['lname' => 'Ocampo', 'fname' => 'Godwin', 'vl' => 1.75, 'sl' => 4],
    ['lname' => 'Otsuka', 'fname' => 'Shigeru', 'vl' => 3.75, 'sl' => 5],
    ['lname' => 'Pangan', 'fname' => 'Cristina', 'vl' => 5.25, 'sl' => 5],
    ['lname' => 'Pangilinan', 'fname' => 'Roi Dane', 'vl' => 7.50, 'sl' => null],
    ['lname' => 'Yap', 'fname' => 'Apryl', 'vl' => 1.75, 'sl' => 3],
    ['lname' => 'Patrimonio', 'fname' => 'R', 'vl' => 2.25, 'sl' => 3],
    ['lname' => 'Pineda', 'fname' => 'Erika', 'vl' => 2.50, 'sl' => 4],
    ['lname' => 'Platero', 'fname' => 'Charisma', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Quizon', 'fname' => 'Shirmiley', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Ronquillo', 'fname' => 'Rhegene', 'vl' => 5.75, 'sl' => 5],
    ['lname' => 'Samodio', 'fname' => 'Jhunel', 'vl' => 5.75, 'sl' => 4],
    ['lname' => 'Solayao', 'fname' => 'Janeth', 'vl' => 2.50, 'sl' => 4],
    ['lname' => 'Singh', 'fname' => 'Ray Jinder', 'vl' => 3.25, 'sl' => 4],
    ['lname' => 'Soriano', 'fname' => 'Mary Ann', 'vl' => 2.50, 'sl' => 5],
    ['lname' => 'Tayao', 'fname' => 'Alexander', 'vl' => 1.25, 'sl' => 4],
    ['lname' => 'Tolomia', 'fname' => 'Rica Joy', 'vl' => 6.25, 'sl' => 5],
    ['lname' => 'Trinidad', 'fname' => 'Jennifer', 'vl' => 2.00, 'sl' => 4],
    ['lname' => 'Yulo', 'fname' => 'Brittany', 'vl' => 1.50, 'sl' => 4],
    ['lname' => 'Dimla', 'fname' => 'Jhosua', 'vl' => 6.25, 'sl' => 5],
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='alert alert-success'>✅ Connected to production database</div>";
    
    // ============================================
    // STEP 1: Get all current 2026 leave credits for Regular employees
    // ============================================
    echo "<h2>Step 1: Current State</h2>";
    
    $stmt = $pdo->query("
        SELECT lc.id, lc.employee_id, lc.leave_type, lc.balance, lc.carry_over, lc.updated_at,
               e.fname, e.lname, e.Emp_Type,
               CONCAT(e.fname, ' ', e.lname) as full_name
        FROM leave_credits lc 
        JOIN employees e ON lc.employee_id = e.id 
        WHERE lc.year = 2026 
        AND lc.leave_type IN ('sick', 'vacation')
        AND e.Emp_Type IN ('Regular', 'Old_Regular')
        AND e.status = 'active'
        ORDER BY e.lname, e.fname, lc.leave_type
    ");
    $allCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // STEP 2: Get approved VL/SL leaves filed in Feb 2026
    // ============================================
    echo "<h2>Step 2: Approved Feb Leaves (will deduct from restored balance)</h2>";
    
    $febLeaves = [];
    try {
        $stmt = $pdo->query("
            SELECT lr.employee_id, lr.leave_type, lr.start_date, lr.end_date, lr.leave_dates,
                   CONCAT(e.fname, ' ', e.lname) as full_name,
                   DATEDIFF(lr.end_date, lr.start_date) + 1 as day_count
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            WHERE lr.status = 'approved'
            AND lr.deleted = 0
            AND lr.leave_type IN ('vacation', 'sick', 'halfday', 'halfday_sick')
            AND (lr.start_date >= '2026-02-01' AND lr.start_date <= '2026-02-28')
            ORDER BY e.lname, lr.leave_type
        ");
        $febLeaveRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also check post_leave_requests
        $stmt2 = $pdo->query("
            SELECT plr.employee_id, plr.leave_type, plr.start_date, plr.end_date,
                   '' as leave_dates,
                   CONCAT(e.fname, ' ', e.lname) as full_name,
                   DATEDIFF(plr.end_date, plr.start_date) + 1 as day_count
            FROM post_leave_requests plr
            JOIN employees e ON plr.employee_id = e.id
            WHERE plr.status = 'approved'
            AND plr.leave_type IN ('vacation', 'sick', 'halfday', 'halfday_sick')
            AND (plr.start_date >= '2026-02-01' AND plr.start_date <= '2026-02-28')
            ORDER BY e.lname, plr.leave_type
        ");
        $febLeaveRaw = array_merge($febLeaveRaw, $stmt2->fetchAll(PDO::FETCH_ASSOC));
        
        if (count($febLeaveRaw) > 0) {
            echo "<table><tr><th>Employee</th><th>Leave Type</th><th>Start</th><th>End</th><th>Days</th></tr>";
            foreach ($febLeaveRaw as $lr) {
                $empId = $lr['employee_id'];
                $type = $lr['leave_type'];
                
                // Calculate actual days
                $days = 0;
                if (!empty($lr['leave_dates'])) {
                    $dates = json_decode($lr['leave_dates'], true);
                    $days = is_array($dates) ? count($dates) : $lr['day_count'];
                } else {
                    $days = $lr['day_count'];
                }
                
                // Map halfday types
                $creditType = $type;
                if ($type === 'halfday') { $creditType = 'vacation'; $days = $days * 0.5; }
                if ($type === 'halfday_sick') { $creditType = 'sick'; $days = $days * 0.5; }
                
                if (!isset($febLeaves[$empId])) $febLeaves[$empId] = ['vacation' => 0, 'sick' => 0];
                $febLeaves[$empId][$creditType] += $days;
                
                echo "<tr><td>{$lr['full_name']}</td><td>$type</td><td>{$lr['start_date']}</td><td>{$lr['end_date']}</td><td>$days</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='alert alert-info'>No approved VL/SL leaves found in February 2026.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-warning'>Could not check leave requests: " . $e->getMessage() . "</div>";
    }
    
    // ============================================
    // STEP 3: Match employees and calculate restores
    // ============================================
    echo "<h2>Step 3: Restore Plan</h2>";
    
    $matched = [];
    $unmatched = [];
    $restoreActions = [];
    
    foreach ($allCredits as $credit) {
        $empId = $credit['employee_id'];
        $empFname = $credit['fname'];
        $empLname = $credit['lname'];
        $leaveType = $credit['leave_type'];
        $currentBalance = floatval($credit['balance']);
        $currentCarryOver = floatval($credit['carry_over'] ?? 0);
        
        // Try to find in spreadsheet
        $found = null;
        foreach ($spreadsheetData as $sd) {
            $sdLname = $sd['lname'];
            $sdFname = $sd['fname'];
            
            // Match by last name (case insensitive) + first name starts with
            if (stripos($empLname, $sdLname) !== false || stripos($sdLname, $empLname) !== false) {
                if (stripos($empFname, $sdFname) === 0 || stripos($sdFname, substr($empFname, 0, 3)) === 0) {
                    $found = $sd;
                    break;
                }
            }
            // Try first name match with different last name format
            if (stripos($empFname, $sdFname) === 0 && 
                (stripos($empLname, explode(' ', $sdLname)[0]) !== false)) {
                $found = $sd;
                break;
            }
        }
        
        if ($found) {
            $key = $empId . '_' . $leaveType;
            if (isset($matched[$key])) continue;
            $matched[$key] = true;
            
            if ($leaveType === 'vacation') {
                $targetVL = $found['vl'];
                // Deduct any approved VL leaves in Feb
                $febVLUsed = $febLeaves[$empId]['vacation'] ?? 0;
                $restoredVL = $targetVL - $febVLUsed;
                if ($restoredVL < 0) $restoredVL = 0;
                
                $restoreActions[] = [
                    'id' => $credit['id'],
                    'employee_id' => $empId,
                    'name' => $credit['full_name'],
                    'type' => 'vacation',
                    'current' => $currentBalance,
                    'current_carry' => $currentCarryOver,
                    'target' => round($restoredVL, 2),
                    'target_carry' => 0,
                    'jan_value' => $targetVL,
                    'feb_used' => $febVLUsed,
                    'reason' => 'Restore VL to Jan value' . ($febVLUsed > 0 ? " minus $febVLUsed Feb usage" : ''),
                    'source' => 'spreadsheet'
                ];
            } elseif ($leaveType === 'sick' && $found['sl'] !== null) {
                $targetSL = $found['sl'];
                // Deduct any approved SL leaves in Feb
                $febSLUsed = $febLeaves[$empId]['sick'] ?? 0;
                $restoredSL = $targetSL - $febSLUsed;
                if ($restoredSL < 0) $restoredSL = 0;
                
                // Only restore if current is HIGHER than target (bug inflated it)
                // If current is LOWER, employee used SL in Feb → keep current value
                if ($currentBalance > $targetSL) {
                    $restoreActions[] = [
                        'id' => $credit['id'],
                        'employee_id' => $empId,
                        'name' => $credit['full_name'],
                        'type' => 'sick',
                        'current' => $currentBalance,
                        'current_carry' => $currentCarryOver,
                        'target' => round($restoredSL, 2),
                        'target_carry' => null,
                        'jan_value' => $targetSL,
                        'feb_used' => $febSLUsed,
                        'reason' => 'SL went UP (bug) → restore to Jan value',
                        'source' => 'spreadsheet'
                    ];
                }
                // If current is LOWER or equal, SL is correct (employee used it) → skip
            }
        } else {
            // Not in spreadsheet — flag for manual review
            $unmatched[$empId . '_' . $leaveType] = [
                'id' => $credit['id'],
                'employee_id' => $empId,
                'name' => $credit['full_name'],
                'type' => $leaveType,
                'current' => $currentBalance,
                'current_carry' => $currentCarryOver
            ];
        }
    }
    
    // Show restore plan
    echo "<table>";
    echo "<tr><th>Employee</th><th>Leave</th><th>Current</th><th>Carry</th><th>→ Restored To</th><th>→ Carry</th><th>Jan Value</th><th>Feb Used</th><th>Reason</th></tr>";
    
    foreach ($restoreActions as $action) {
        $changed = (abs($action['current'] - $action['target']) > 0.01 || abs($action['current_carry'] - ($action['target_carry'] ?? 0)) > 0.01);
        $rowClass = $changed ? "class='fixed'" : "";
        
        echo "<tr {$rowClass}>";
        echo "<td>{$action['name']}</td>";
        echo "<td>{$action['type']}</td>";
        echo "<td><strong>{$action['current']}</strong></td>";
        echo "<td>{$action['current_carry']}</td>";
        echo "<td><strong>{$action['target']}</strong></td>";
        echo "<td>" . ($action['target_carry'] !== null ? $action['target_carry'] : '-') . "</td>";
        echo "<td>{$action['jan_value']}</td>";
        echo "<td>{$action['feb_used']}</td>";
        echo "<td>{$action['reason']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-info'>Total restore actions: <strong>" . count($restoreActions) . "</strong></div>";
    
    // Show unmatched employees
    if (!empty($unmatched)) {
        echo "<h3>⚠️ Employees NOT in Jan Spreadsheet (Manual Review Required)</h3>";
        echo "<table><tr><th>Employee</th><th>Leave Type</th><th>Current Balance</th><th>Carry Over</th><th>Note</th></tr>";
        foreach ($unmatched as $um) {
            echo "<tr class='manual'>";
            echo "<td>{$um['name']}</td>";
            echo "<td>{$um['type']}</td>";
            echo "<td><strong>{$um['current']}</strong></td>";
            echo "<td>{$um['current_carry']}</td>";
            echo "<td>Not in Jan spreadsheet — check manually</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ============================================
    // STEP 4: Execute restores
    // ============================================
    if (!$isDryRun) {
        echo "<h2>Step 4: Executing Restores...</h2>";
        
        $pdo->beginTransaction();
        
        try {
            $restoredCount = 0;
            
            foreach ($restoreActions as $action) {
                if ($action['type'] === 'vacation') {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, carry_over = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$action['target'], $action['target_carry'], $action['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$action['target'], $action['id']]);
                }
                $restoredCount++;
            }
            
            echo "<div class='alert alert-success'>✅ Restored <strong>$restoredCount</strong> leave credit records</div>";
            
            // ============================================
            // STEP 5: Fix system_settings duplicates (ROOT CAUSE)
            // ============================================
            echo "<h2>Step 5: Fixing system_settings (Root Cause)</h2>";
            
            // Check how many duplicate rows exist
            $stmt = $pdo->query("SELECT setting_key, COUNT(*) as cnt FROM system_settings GROUP BY setting_key HAVING cnt > 1");
            $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($dupes) > 0) {
                echo "<div class='alert alert-danger'>Found duplicate keys: ";
                foreach ($dupes as $d) echo "<strong>{$d['setting_key']}</strong> ({$d['cnt']} rows) ";
                echo "</div>";
                
                // For each duplicate key, keep only the LATEST row and delete the rest
                foreach ($dupes as $d) {
                    $key = $d['setting_key'];
                    
                    // Get the MAX id (latest row)
                    $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $maxId = $stmt->fetchColumn();
                    
                    // Delete all but the latest
                    $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?");
                    $stmt->execute([$key, $maxId]);
                    
                    $deleted = $stmt->rowCount();
                    echo "<p>🗑️ Deleted <strong>$deleted</strong> duplicate rows for <code>$key</code></p>";
                }
            }
            
            // Ensure there's a UNIQUE index on setting_key
            try {
                $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                echo "<div class='alert alert-success'>✅ Added UNIQUE index on setting_key</div>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "<div class='alert alert-warning'>⚠️ Still have duplicates. Cleaning again...</div>";
                    // More aggressive cleanup
                    $stmt = $pdo->query("SELECT DISTINCT setting_key FROM system_settings");
                    $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($keys as $key) {
                        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                        $stmt->execute([$key]);
                        $maxId = $stmt->fetchColumn();
                        $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?")->execute([$key, $maxId]);
                    }
                    // Try adding unique index again
                    try {
                        $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                        echo "<div class='alert alert-success'>✅ Added UNIQUE index on setting_key (2nd attempt)</div>";
                    } catch (Exception $e2) {
                        echo "<div class='alert alert-warning'>Note: " . $e2->getMessage() . "</div>";
                    }
                } else if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'key_name') !== false) {
                    echo "<div class='alert alert-info'>ℹ️ UNIQUE index already exists</div>";
                } else {
                    echo "<div class='alert alert-warning'>Note: " . $e->getMessage() . "</div>";
                }
            }
            
            // Set last_auto_accrual_month to 1 (January) — Feb NOT processed yet
            // After code is fixed, a single proper Feb accrual can run
            $pdo->exec("UPDATE system_settings SET setting_value = '1', updated_at = NOW() WHERE setting_key = 'last_auto_accrual_month'");
            echo "<p>📅 Set last_auto_accrual_month = 1 (February marked as unprocessed)</p>";
            
            // Disable auto-accrual until code is fixed
            $pdo->exec("UPDATE system_settings SET setting_value = '0', updated_at = NOW() WHERE setting_key = 'auto_accrual_enabled'");
            echo "<p>🔴 Disabled auto-accrual (re-enable after code fix)</p>";
            
            $pdo->commit();
            
            echo "<div class='alert alert-success' style='font-size:18px;'><strong>✅ ALL CHANGES COMMITTED SUCCESSFULLY!</strong></div>";
            echo "<div class='alert alert-warning'>";
            echo "<strong>Next Steps:</strong><br>";
            echo "1. Deploy the fixed <code>process_auto_accrual.php</code> code<br>";
            echo "2. Deploy the fixed <code>auto_accrual_cron.php</code> code<br>";
            echo "3. Review employees NOT in the spreadsheet above<br>";
            echo "4. Re-enable auto-accrual from admin dashboard<br>";
            echo "5. The system will properly run ONE February accrual (+1.25 VL only)<br>";
            echo "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>❌ ROLLED BACK! Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<h2>Step 4: Ready to Execute</h2>";
        echo "<div class='alert alert-warning'>";
        echo "<strong>Review the plan above.</strong> When ready, run with:<br>";
        echo "<code>?confirm=yes</code>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
