<?php
/**
 * RESTORE LEAVE CREDITS — March 2, 2026
 * Sets leave credits to the EXACT values provided by HR/Admin.
 * Also fixes system_settings duplicate rows (root cause of the accrual bug).
 * 
 * USAGE: Upload to production, visit URL
 *   Dry run:  https://yourdomain.com/restore_leave_credits_mar2.php
 *   Execute:  https://yourdomain.com/restore_leave_credits_mar2.php?confirm=yes
 */

$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

date_default_timezone_set('Asia/Manila');

echo "<html><head><title>Restore Leave Credits — Mar 2, 2026</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h2 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; background: white; }
th { background: #333; color: white; padding: 8px 12px; text-align: left; font-size: 13px; }
td { padding: 6px 12px; border: 1px solid #ddd; font-size: 13px; }
tr:nth-child(even) { background: #f9f9f9; }
.alert { padding: 15px; border-radius: 5px; margin: 10px 0; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.alert-warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
.changed { background: #d4edda !important; }
.nochange { opacity: 0.6; }
.notfound { background: #f8d7da !important; }
</style></head><body>";

echo "<h2>Restore Leave Credits — March 2, 2026</h2>";
echo "<p><strong>Script Run:</strong> " . date('Y-m-d H:i:s') . "</p>";

$isDryRun = !isset($_GET['confirm']) || $_GET['confirm'] !== 'yes';

if ($isDryRun) {
    echo "<div class='alert alert-warning'><strong>DRY RUN MODE</strong> — No changes will be made. Review below, then add <code>?confirm=yes</code> to the URL to execute.</div>";
} else {
    echo "<div class='alert alert-danger'><strong>LIVE MODE</strong> — Changes WILL be applied!</div>";
}

// ==============================================
// Exact leave credit values as of March 2, 2026
// Format: [lname_search, fname_search, VL, SL, SPL (or null)]
// ==============================================
$employeeData = [
    ['AGAS', 'JILLIAN LAO', 6.50, 4, null],
    ['AGUILAR', 'IAN MYCO', 6.50, 4, null],
    ['ALIMURONG', 'JOEL LUSUNG', 6.00, 5, null],
    ['ALVAREZ', 'JOHN BRYAN', 6.00, 4, null],
    ['ANTONIO', 'VINCENT KEVIN', 4.50, 4, null],
    ['ANGELES', 'CHRISTINE', 2.00, 5, null],
    ['ARNIGO', 'CEDRICK', 6.50, 4, null],
    ['AUSTRIA', 'LOUIS FERNAND', 3.50, 5, null],
    ['BACONGALLO', 'NIKA NUEVA', 0.75, 5, null],
    ['BANSIL', 'KRISTIAN DAVID', 3.00, 1, null],
    ['BAUTISTA', 'OLIVE SANTOS', 7.00, 5, 7],
    ['BALDERAS', 'GLORY ANN', 3.00, 5, null],
    ['BENALLA', 'RENNECA', 7.50, 5, null],
    ['BONDOC', 'FRANCIS EUGENE', 7.50, 5, null],
    ['BRIONES', 'JOHN MICHAEL', 3.50, 5, null],
    ['CAMERINO', 'YRIS GAELLE', 5.00, 4, null],
    ['CAPATI', 'ALLEN SOBREPENA', 2.75, 4, null],
    ['CAPIRAL', 'GABRIEL', 0.00, 0, null],
    ['CARAAN', 'SARAH', 7.00, 5, null],
    ['CASTRO', 'AIZEL SANTOS', 6.50, 5, 7],
    ['CATOLOGO', 'MARNIE', 3.00, 5, null],
    ['CELESTE', 'LOVELAINE GUDOY', 8.00, 5, null],
    ['COLIS', 'REYMARK BRYAN', 3.50, 5, null],
    ['CRISANTO', 'ELRITZ', 3.00, 5, null],
    ['DACQUIL', 'KIMBERLY', 7.50, 5, null],
    ['DAVID', 'REBECCA', 7.50, 5, null],
    ['DAVID', 'RYAN ARWIN', 6.50, 5, null],
    ['DIMLA', 'JHOSUA', 1.50, 5, null],
    ['DELA CRUZ', 'JONAS', 6.75, 5, null],
    ['DELA CRUZ', 'SHAINA DIMAYUGO', 3.25, 5, null],
    ['DOLLENTES', 'MARIA NINA', 5.00, 5, null],
    ['ESTANIO', 'ANGELICA ROSARIO', 7.75, 5, null],
    ['FERNANDEZ', 'FRANCIS EMMANUEL', 5.50, 5, null],
    ['FERNANDEZ', 'MARIANNE JAE', 2.75, 5, null],
    ['GATBONTON', 'ANALIZA TALOBAN', 1.50, 4, null],
    ['GATBONTON', 'BEVERLY TALOBAN', 1.25, 5, null],
    ['GUECO', 'JOHANA ROSE', 3.50, 5, 7],
    ['GUILLERMO', 'ALFIE', 7.50, 5, null],
    ['JABINAL', 'ADONIS DEL MUNDO', 4.75, 5, null],
    ['JOSAFAT', 'RENALYN', 3.00, 4, null],
    ['LOZANO', 'ALDWIN JOHN', 5.00, 4, null],
    ['MACAPAGAL', 'JEFFRY TUAZON', 7.00, 5, null],
    ['MACLANG', 'JULIE ANNE', 6.00, 5, null],
    ['MAKABENTA', 'ALTHEA', 6.50, 2, null],
    ['MALINAO', 'ROGELIO DELA PENA', 3.25, 4, null],
    ['MANALILI', 'JOSHUA', 8.75, 5, null],
    ['MATAGA', 'EDITH DAVID', 7.00, 2.5, null],
    ['MAR', 'CHRISTIAN NIODA', 9.00, 4, null],
    ['MCGREGOR', 'TRISHA MAE', 3.00, 5, null],
    ['MENDOZA', 'SEAN JUSTINE', 5.75, 5, null],
    ['MONIS', 'JOSHWEA MERCADO', 8.75, 5, null],
    ['NAVALON', 'EVANEL CAACBAY', 1.50, 4, null],
    ['NUNEZ', 'IVY', 10.00, 4, null],
    ['NUNEZA', 'DOU LESTER', 7.00, 5, null],
    ['OCAMPO', 'ALFRED NAGUIT', 4.00, 5, null],
    ['OCAMPO', 'GODWIN', 3.00, 4, null],
    ['OTSUKA', 'SHIGERU CENTINA', 5.00, 5, null],
    ['PANGAN', 'CRISTINA MIRANDA', 2.00, 5, null],
    ['PANGILINAN', 'ROI DANE', 8.75, 5, null],
    ['PATAWARAN', 'SHERRY', 6.50, 5, null],
    ['YAP', 'APRYL', 3.00, 3, null],
    ['PATRIMONIO', 'RYAN REX', 3.50, 3, null],
    ['PINEDA', 'ERIKA SERIOSA', 2.75, 4, null],
    ['PLATERO', 'MA. CHARISMA', 7.50, 5, null],
    ['QUIZON', 'SHIRMILEY', 6.00, 5, null],
    ['RONQUILLO', 'RHEGENE INGAT', 1.00, 5, null],
    ['SAMODIO', 'JHUNEL CARLO', 7.00, 4, null],
    ['SOLAYAO', 'JANETH SEDON', 3.75, 4, null],
    ['SINGH', 'RAY JINDER', 4.50, 4, null],
    ['SORIANO', 'MARY ANN VALLEJOS', 3.75, 5, null],
    ['TAYAO', 'ALEXANDER', 1.50, 4, null],
    ['TOLOMIA', 'RICA JOY', 7.50, 5, null],
    ['TRINIDAD', 'JENNIFER M', 3.25, 4, null],
    ['YULO', 'BRITTANY', 1.75, 4, null],
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='alert alert-success'>Connected to production database</div>";
    
    // Get all employees
    $stmt = $pdo->query("SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name, Emp_Type, status FROM employees ORDER BY lname, fname");
    $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all current 2026 leave credits
    $stmt = $pdo->query("
        SELECT lc.id, lc.employee_id, lc.leave_type, lc.balance, lc.carry_over, 
               CONCAT(e.fname, ' ', e.lname) as full_name, e.Emp_Type
        FROM leave_credits lc 
        JOIN employees e ON lc.employee_id = e.id 
        WHERE lc.year = 2026 
        AND lc.leave_type IN ('sick', 'vacation', 'solo_parent')
        ORDER BY e.lname, e.fname, lc.leave_type
    ");
    $currentCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Index current credits by employee_id + leave_type
    $creditIndex = [];
    foreach ($currentCredits as $c) {
        $creditIndex[$c['employee_id'] . '_' . $c['leave_type']] = $c;
    }
    
    // ============================================
    // Match employees and build restore plan
    // ============================================
    echo "<h2>Restore Plan</h2>";
    echo "<table>";
    echo "<tr><th>#</th><th>Search Name</th><th>Matched Employee</th><th>ID</th><th>Type</th><th>Leave</th><th>Current</th><th>→ Target</th><th>Status</th></tr>";
    
    $restoreActions = [];
    $matchedCount = 0;
    $notFoundList = [];
    $rowNum = 0;
    
    foreach ($employeeData as $idx => $data) {
        $searchLname = $data[0];
        $searchFname = $data[1];
        $targetVL = $data[2];
        $targetSL = $data[3];
        $targetSPL = $data[4];
        
        // Find employee by name match
        $matchedEmp = null;
        foreach ($allEmployees as $emp) {
            $empLname = strtoupper($emp['lname']);
            $empFname = strtoupper($emp['fname']);
            $sLname = strtoupper($searchLname);
            $sFname = strtoupper($searchFname);
            
            // Match last name (contains or contained-in)
            $lnameMatch = false;
            if (strpos($empLname, $sLname) !== false || strpos($sLname, $empLname) !== false) {
                $lnameMatch = true;
            }
            // Also try: last name words overlap
            if (!$lnameMatch) {
                $empLnameWords = explode(' ', $empLname);
                $sLnameWords = explode(' ', $sLname);
                foreach ($empLnameWords as $ew) {
                    foreach ($sLnameWords as $sw) {
                        if (strlen($ew) >= 3 && strlen($sw) >= 3 && ($ew === $sw || strpos($ew, $sw) === 0 || strpos($sw, $ew) === 0)) {
                            $lnameMatch = true;
                            break 2;
                        }
                    }
                }
            }
            
            if (!$lnameMatch) continue;
            
            // Match first name (starts with)
            $fnameMatch = false;
            $sFnameShort = substr($sFname, 0, 3);
            if (strpos($empFname, $sFname) === 0 || strpos($empFname, $sFnameShort) === 0 || strpos($sFname, substr($empFname, 0, 3)) === 0) {
                $fnameMatch = true;
            }
            
            if ($fnameMatch) {
                $matchedEmp = $emp;
                break;
            }
        }
        
        if (!$matchedEmp) {
            $notFoundList[] = "$searchLname, $searchFname (VL=$targetVL, SL=$targetSL)";
            $rowNum++;
            echo "<tr class='notfound'><td>$rowNum</td><td>$searchLname, $searchFname</td><td colspan='7'><strong>NOT FOUND</strong> — needs manual check</td></tr>";
            continue;
        }
        
        $empId = $matchedEmp['id'];
        $matchedCount++;
        
        // --- Vacation Leave ---
        $rowNum++;
        $key = $empId . '_vacation';
        $current = $creditIndex[$key] ?? null;
        $currentVL = $current ? floatval($current['balance']) : null;
        $vlChanged = ($currentVL === null || abs($currentVL - $targetVL) > 0.001);
        $vlClass = $vlChanged ? 'changed' : 'nochange';
        $vlStatus = $vlChanged ? '<strong>UPDATE</strong>' : 'OK';
        
        echo "<tr class='$vlClass'><td>$rowNum</td><td>$searchLname, $searchFname</td><td>{$matchedEmp['full_name']}</td><td>$empId</td><td>{$matchedEmp['Emp_Type']}</td><td>VL</td>";
        echo "<td>" . ($currentVL !== null ? $currentVL : 'MISSING') . "</td><td><strong>$targetVL</strong></td><td>$vlStatus</td></tr>";
        
        if ($current && $vlChanged) {
            $restoreActions[] = ['id' => $current['id'], 'type' => 'vacation', 'balance' => $targetVL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => $current['carry_over']];
        } elseif (!$current && $targetVL > 0) {
            $restoreActions[] = ['id' => null, 'type' => 'vacation', 'balance' => $targetVL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => null, 'insert' => true];
        }
        
        // --- Sick Leave ---
        $rowNum++;
        $key = $empId . '_sick';
        $current = $creditIndex[$key] ?? null;
        $currentSL = $current ? floatval($current['balance']) : null;
        $slChanged = ($currentSL === null || abs($currentSL - $targetSL) > 0.001);
        $slClass = $slChanged ? 'changed' : 'nochange';
        $slStatus = $slChanged ? '<strong>UPDATE</strong>' : 'OK';
        
        echo "<tr class='$slClass'><td>$rowNum</td><td></td><td></td><td></td><td></td><td>SL</td>";
        echo "<td>" . ($currentSL !== null ? $currentSL : 'MISSING') . "</td><td><strong>$targetSL</strong></td><td>$slStatus</td></tr>";
        
        if ($current && $slChanged) {
            $restoreActions[] = ['id' => $current['id'], 'type' => 'sick', 'balance' => $targetSL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => $current['carry_over']];
        } elseif (!$current && $targetSL > 0) {
            $restoreActions[] = ['id' => null, 'type' => 'sick', 'balance' => $targetSL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => null, 'insert' => true];
        }
        
        // --- Solo Parent Leave (if applicable) ---
        if ($targetSPL !== null) {
            $rowNum++;
            $key = $empId . '_solo_parent';
            $current = $creditIndex[$key] ?? null;
            $currentSPL = $current ? floatval($current['balance']) : null;
            $splChanged = ($currentSPL === null || abs($currentSPL - $targetSPL) > 0.001);
            $splClass = $splChanged ? 'changed' : 'nochange';
            $splStatus = $splChanged ? '<strong>UPDATE</strong>' : 'OK';
            
            echo "<tr class='$splClass'><td>$rowNum</td><td></td><td></td><td></td><td></td><td>SPL</td>";
            echo "<td>" . ($currentSPL !== null ? $currentSPL : 'MISSING') . "</td><td><strong>$targetSPL</strong></td><td>$splStatus</td></tr>";
            
            if ($current && $splChanged) {
                $restoreActions[] = ['id' => $current['id'], 'type' => 'solo_parent', 'balance' => $targetSPL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => $current['carry_over']];
            } elseif (!$current) {
                $restoreActions[] = ['id' => null, 'type' => 'solo_parent', 'balance' => $targetSPL, 'name' => $matchedEmp['full_name'], 'emp_id' => $empId, 'carry_over' => null, 'insert' => true];
            }
        }
    }
    echo "</table>";
    
    $updateCount = count(array_filter($restoreActions, function($a) { return !isset($a['insert']); }));
    $insertCount = count(array_filter($restoreActions, function($a) { return isset($a['insert']); }));
    
    echo "<div class='alert alert-info'>";
    echo "Employees matched: <strong>$matchedCount</strong> / " . count($employeeData) . "<br>";
    echo "Records to UPDATE: <strong>$updateCount</strong><br>";
    echo "Records to INSERT: <strong>$insertCount</strong><br>";
    echo "</div>";
    
    if (!empty($notFoundList)) {
        echo "<div class='alert alert-danger'><strong>NOT FOUND (" . count($notFoundList) . "):</strong><br>";
        foreach ($notFoundList as $nf) echo "- $nf<br>";
        echo "</div>";
    }
    
    // ============================================
    // Execute
    // ============================================
    if (!$isDryRun) {
        echo "<h2>Executing...</h2>";
        
        $pdo->beginTransaction();
        
        try {
            $updatedCount = 0;
            $insertedCount = 0;
            
            foreach ($restoreActions as $action) {
                if (isset($action['insert'])) {
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO leave_credits (employee_id, leave_type, balance, carry_over, year, updated_at) VALUES (?, ?, ?, ?, 2026, NOW())");
                    $stmt->execute([$action['emp_id'], $action['type'], $action['balance'], $action['carry_over']]);
                    $insertedCount++;
                } else {
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$action['balance'], $action['id']]);
                    $updatedCount++;
                }
            }
            
            echo "<div class='alert alert-success'>Updated <strong>$updatedCount</strong> records, Inserted <strong>$insertedCount</strong> records</div>";
            
            // ============================================
            // Fix system_settings duplicates (ROOT CAUSE of accrual bug)
            // ============================================
            echo "<h2>Fixing system_settings (Root Cause)</h2>";
            
            $stmt = $pdo->query("SELECT setting_key, COUNT(*) as cnt FROM system_settings GROUP BY setting_key HAVING cnt > 1");
            $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($dupes) > 0) {
                echo "<div class='alert alert-danger'>Found duplicate keys: ";
                foreach ($dupes as $d) echo "<strong>{$d['setting_key']}</strong> ({$d['cnt']} rows) ";
                echo "</div>";
                
                foreach ($dupes as $d) {
                    $key = $d['setting_key'];
                    $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$key]);
                    $maxId = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?");
                    $stmt->execute([$key, $maxId]);
                    echo "<p>Deleted <strong>{$stmt->rowCount()}</strong> duplicate rows for <code>$key</code></p>";
                }
            } else {
                echo "<div class='alert alert-info'>No duplicate system_settings rows found.</div>";
            }
            
            // Add UNIQUE index
            try {
                $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                echo "<div class='alert alert-success'>Added UNIQUE index on setting_key</div>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $stmt = $pdo->query("SELECT DISTINCT setting_key FROM system_settings");
                    $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($keys as $key) {
                        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM system_settings WHERE setting_key = ?");
                        $stmt->execute([$key]);
                        $maxId = $stmt->fetchColumn();
                        $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ? AND id != ?")->execute([$key, $maxId]);
                    }
                    try {
                        $pdo->exec("ALTER TABLE system_settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
                        echo "<div class='alert alert-success'>Added UNIQUE index (2nd attempt)</div>";
                    } catch (Exception $e2) {
                        echo "<div class='alert alert-warning'>Index: " . htmlspecialchars($e2->getMessage()) . "</div>";
                    }
                } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<div class='alert alert-info'>UNIQUE index already exists</div>";
                } else {
                    echo "<div class='alert alert-warning'>Index: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
            // Set last_auto_accrual_month = 2 (Feb is done — these values include Feb accrual)
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = '2', updated_at = NOW() WHERE setting_key = 'last_auto_accrual_month'");
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $pdo->exec("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES ('last_auto_accrual_month', '2', NOW())");
            }
            echo "<p>Set last_auto_accrual_month = 2 (Feb accrual marked as done)</p>";
            
            // Enable auto-accrual (fixed code is deployed)
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = '1', updated_at = NOW() WHERE setting_key = 'auto_accrual_enabled'");
            $stmt->execute();
            echo "<p>Auto-accrual enabled</p>";
            
            $pdo->commit();
            
            echo "<div class='alert alert-success' style='font-size:18px;'><strong>ALL CHANGES COMMITTED!</strong></div>";
            echo "<div class='alert alert-info'>";
            echo "<strong>Summary:</strong><br>";
            echo "- Leave credits set to exact HR-provided values (Mar 2, 2026)<br>";
            echo "- System_settings duplicates cleaned up<br>";
            echo "- UNIQUE index added to prevent future duplicates<br>";
            echo "- last_auto_accrual_month = 2 (Feb marked as processed)<br>";
            echo "- Auto-accrual enabled — next accrual will run on March 31<br>";
            echo "</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>ROLLED BACK! Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<h2>Ready to Execute</h2>";
        echo "<div class='alert alert-warning'>";
        echo "<strong>Review the plan above.</strong> When ready, add <code>?confirm=yes</code> to the URL.<br>";
        echo "This will:<br>";
        echo "1. Set all leave credits to the exact values shown above<br>";
        echo "2. Fix system_settings duplicate rows<br>";
        echo "3. Add UNIQUE index to prevent future duplicates<br>";
        echo "4. Set last_auto_accrual_month = 2 (Feb done, next accrual = March 31)<br>";
        echo "5. Enable auto-accrual<br>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
