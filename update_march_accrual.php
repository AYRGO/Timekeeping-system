<?php
/**
 * UPDATE LEAVE CREDITS FROM MARCH 2026 ACCRUAL SPREADSHEET
 * 
 * This script updates leave_credits based on March 2026 accrual data
 * and generates a mismatch report for employees not found in the system.
 * 
 * IMPORTANT: Run with ?confirm=yes to execute updates
 */

// Hostinger production settings
$host = 'localhost';
$dbname = 'u816220874_calendartype';
$username = 'u816220874_calendartype';
$password = 'Gr33n$$wRf';

echo "<h2>UPDATE LEAVE CREDITS FROM MARCH 2026 ACCRUAL SPREADSHEET</h2>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p><hr>";

// March 2026 accrual data from spreadsheet
$marchAccrualData = [
    ['name' => 'AGAS, JILLIAN LAO', 'vl' => 7.75, 'sl' => 3.0, 'spl' => null],
    ['name' => 'AGUILAR, IAN MYCO', 'vl' => 7.75, 'sl' => 3.0, 'spl' => null],
    ['name' => 'ALIMURONG, JOEL LUSUNG', 'vl' => 7.25, 'sl' => 3.0, 'spl' => null],
    ['name' => 'ALVAREZ, JOHN BRYAN', 'vl' => 6.25, 'sl' => 2.0, 'spl' => null],
    ['name' => 'ANTONIO, VINCENT KEVIN SANTOS', 'vl' => 3.75, 'sl' => 2.0, 'spl' => null, 'db_lname' => 'Santos', 'db_fname' => 'Vincent Kevin'],
    ['name' => 'ANGELES, CHRISTINE', 'vl' => 2.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'AUSTRIA, LOUIS FERNAND BALUYOT', 'vl' => 5.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'BACONGALLO, NIKA NUEVA', 'vl' => 1.5, 'sl' => 4.0, 'spl' => null],
    ['name' => 'BANSIL, KRISTIAN DAVID', 'vl' => 2.5, 'sl' => 0.0, 'spl' => null],
    ['name' => 'BAUTISTA, OLIVE SANTOS', 'vl' => 6.25, 'sl' => 3.0, 'spl' => 6.0],
    ['name' => 'BALDERAS, GLORY ANN GARCIA', 'vl' => 4.25, 'sl' => 3.0, 'spl' => null],
    ['name' => 'BENALLA, RENNECA Villapaña', 'vl' => 4.75, 'sl' => 4.0, 'spl' => null, 'db_lname' => 'Benalla', 'db_fname' => 'Reneeca Villapaña'],
    ['name' => 'BRIONES, JOHN MICHAEL', 'vl' => 4.75, 'sl' => 4.0, 'spl' => null],
    ['name' => 'CAMERINO, YRIS GAELLE PARREÑAS', 'vl' => 6.25, 'sl' => 4.0, 'spl' => null, 'db_lname' => 'Camerino', 'db_fname' => 'Yris Gaelle'],
    ['name' => 'CAPATI, ALLEN SOBREPENA', 'vl' => 2.0, 'sl' => 3.0, 'spl' => null],
    ['name' => 'CAPIRAL, GABRIEL', 'vl' => 2.5, 'sl' => 0.0, 'spl' => null], // Probationary employee
    ['name' => 'CARAAN, SARAH', 'vl' => 6.25, 'sl' => 5.0, 'spl' => null],
    ['name' => 'CASTRO, AIZEL SANTOS', 'vl' => 7.75, 'sl' => 4.0, 'spl' => 1.0],
    ['name' => 'CATOLOGO, MARNIE', 'vl' => 2.25, 'sl' => 3.0, 'spl' => null, 'db_lname' => 'Catalogo', 'db_fname' => 'Marnie Perez'],
    ['name' => 'CELESTE, LOVELAINE GUDOY', 'vl' => 5.75, 'sl' => 3.0, 'spl' => null],
    ['name' => 'COLIS, REYMARK BRYAN SILVANO', 'vl' => 1.0, 'sl' => 5.0, 'spl' => null],
    ['name' => 'CRISANTO, ELRITZ T', 'vl' => 4.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'CUETO, RON', 'vl' => 8.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'DACQUIL, KIMBERLY', 'vl' => 8.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'DAVID, REBECCA', 'vl' => 8.75, 'sl' => 5.0, 'spl' => null, 'db_lname' => 'David', 'db_fname' => 'Rebecca'],
    ['name' => 'DAVID, RYAN ARWIN', 'vl' => 7.25, 'sl' => 1.5, 'spl' => null, 'db_lname' => 'David', 'db_fname' => 'Ryan Arwin'],
    ['name' => 'DIMLA, JHOSUA', 'vl' => 1.75, 'sl' => 5.0, 'spl' => null, 'db_lname' => 'Dimla', 'db_fname' => 'Johsua'],
    ['name' => 'DELA CRUZ, JONAS', 'vl' => 3.0, 'sl' => 4.0, 'spl' => null, 'db_lname' => 'Dela Cruz', 'db_fname' => 'Jonas'],
    ['name' => 'DELA CRUZ, SHAINA DIMAYUGO', 'vl' => 4.5, 'sl' => 4.0, 'spl' => null, 'db_lname' => 'Dela Cruz', 'db_fname' => 'Shaina'],
    ['name' => 'DOLLENTES, MARIA NINA', 'vl' => 1.25, 'sl' => 0.0, 'spl' => null, 'db_lname' => 'Dollentes Cruz', 'db_fname' => 'Maria Nina'],
    ['name' => 'ESTANIO, ANGELICA ROSARIO', 'vl' => 8.5, 'sl' => 5.0, 'spl' => null],
    ['name' => 'FERNANDEZ, FRANCIS EMMANUEL VELOSO', 'vl' => 6.75, 'sl' => 1.0, 'spl' => null, 'db_lname' => 'Fernandez', 'db_fname' => 'Francis Emmanuel'],
    ['name' => 'FERNANDEZ, MARIANNE JAE', 'vl' => 3.0, 'sl' => 5.0, 'spl' => null, 'db_lname' => 'Fernandez', 'db_fname' => 'Jae'], // Marianne Jae Fernandez
    ['name' => 'GATBONTON, ANALIZA TALOBAN', 'vl' => 1.75, 'sl' => 2.0, 'spl' => null, 'db_lname' => 'Gatbonton', 'db_fname' => 'Analiza'],
    ['name' => 'GATBONTON, BEVERLY TALOBAN', 'vl' => 2.5, 'sl' => 3.0, 'spl' => null, 'db_lname' => 'Gatbonton', 'db_fname' => 'Beverly'],
    ['name' => 'GUECO, JOHANA ROSE PEREZ', 'vl' => 1.25, 'sl' => 5.0, 'spl' => 4.0],
    ['name' => 'GUILLERMO, ALFIE', 'vl' => 6.75, 'sl' => 4.0, 'spl' => null],
    ['name' => 'JABINAL, ADONIS DEL MUNDO', 'vl' => 5.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'JOSAIAT, RENALYN', 'vl' => 4.25, 'sl' => 1.0, 'spl' => null, 'db_lname' => 'Josafat', 'db_fname' => 'Renalyn Abamo'], // Typo: JOSAIAT should be JOSAFAT
    ['name' => 'LOZANO, ALDWIN JOHN', 'vl' => 2.25, 'sl' => 2.0, 'spl' => null, 'db_lname' => 'Arceo Lozano', 'db_fname' => 'Aldwin John'],
    ['name' => 'MACAPAGAL, JEFFRY TUAZON', 'vl' => 5.0, 'sl' => 4.5, 'spl' => null],
    ['name' => 'MACLANG, JULIE ANNE GUINTO', 'vl' => 6.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'MAKABENTA, ALTHEA', 'vl' => 3.75, 'sl' => 0.0, 'spl' => null],
    ['name' => 'MALINAO, ROGELIO DELA PENA', 'vl' => 4.5, 'sl' => 3.0, 'spl' => null, 'db_lname' => 'Malinao Jr', 'db_fname' => 'Rogelio'],
    ['name' => 'MANALILI, JOSHUA', 'vl' => 11.25, 'sl' => 3.0, 'spl' => null],
    ['name' => 'MATAGA, EDITH DAVID', 'vl' => 1.25, 'sl' => 1.5, 'spl' => null, 'db_lname' => 'David Mataga', 'db_fname' => 'Edith'],
    ['name' => 'MAR, CHRISTIAN NIODA', 'vl' => 8.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'MCGREGOR, TRISHA MAE', 'vl' => 2.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'MENDOZA, SEAN JUSTINE F', 'vl' => 7.0, 'sl' => 5.0, 'spl' => null],
    ['name' => 'MONIS, JOSHWEA MERCADO', 'vl' => 9.0, 'sl' => 5.0, 'spl' => null],
    ['name' => 'NAVALON, EVANEL CAACBAY', 'vl' => 2.75, 'sl' => 4.0, 'spl' => null, 'db_lname' => 'Navalon', 'db_fname' => 'Evanel'],
    ['name' => 'NUÑEZ, IVY', 'vl' => 10.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'NUNEZA, DOU LESTER SABANDO', 'vl' => 3.75, 'sl' => 5.0, 'spl' => null, 'db_lname' => 'Nuñeza', 'db_fname' => 'Dou Lester'],
    ['name' => 'OCAMPO, GODWIN', 'vl' => 4.25, 'sl' => 3.0, 'spl' => null, 'db_lname' => 'Ocampo', 'db_fname' => 'Godwin'],
    ['name' => 'OTSUKA, SHIGERU CENTINA', 'vl' => 6.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'PANGAN, CRISTINA MIRANDA', 'vl' => 2.25, 'sl' => 0.0, 'spl' => null],
    ['name' => 'PANGILINAN, ROI DANE', 'vl' => 9.0, 'sl' => 5.0, 'spl' => null],
    ['name' => 'PATAWARAN, SHERRY', 'vl' => 6.75, 'sl' => 4.0, 'spl' => null], // May be terminated
    ['name' => 'YAP, APRYL', 'vl' => 3.25, 'sl' => 2.0, 'spl' => null],
    ['name' => 'PATRIMONIO, RYAN REX', 'vl' => -0.25, 'sl' => 2.0, 'spl' => null], // Negative VL
    ['name' => 'PINEDA, ERIKA SERIOSA', 'vl' => 2.0, 'sl' => 3.0, 'spl' => null],
    ['name' => 'PLATERO, MA. CHARISMA', 'vl' => 1.25, 'sl' => 0.0, 'spl' => null],
    ['name' => 'QUIZON, SHIRMILEY', 'vl' => 3.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'RONQUILLO, RHEGENE INGAT', 'vl' => 7.25, 'sl' => 4.0, 'spl' => null],
    ['name' => 'SAMODIO, JHUNEL CARLO TRAIFALGAR', 'vl' => 4.25, 'sl' => 2.0, 'spl' => null, 'db_lname' => 'Samodio', 'db_fname' => 'Jhunel Carlo'],
    ['name' => 'SOLAYAO, JANETH SEDON', 'vl' => 2.0, 'sl' => 2.5, 'spl' => null],
    ['name' => 'SINGH, RAY JINDER VILLENA', 'vl' => 3.75, 'sl' => 4.0, 'spl' => null],
    ['name' => 'SORIANO, MARY ANN VALLEJOS', 'vl' => 4.0, 'sl' => 5.0, 'spl' => null],
    ['name' => 'TAYAO, ALEXANDER', 'vl' => 1.75, 'sl' => 3.0, 'spl' => null],
    ['name' => 'TOLOMIA, RICA JOY VIRAY', 'vl' => 8.75, 'sl' => 5.0, 'spl' => null],
    ['name' => 'TRINIDAD, JENNIFER M', 'vl' => 2.5, 'sl' => 3.0, 'spl' => null],
    ['name' => 'YULO, BRITTANY', 'vl' => 2.5, 'sl' => 3.0, 'spl' => null],
];

// Safety check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<p style='color:orange;'><strong>⚠️ PREVIEW MODE:</strong> This will show what changes would be made.</p>";
    echo "<p>Add <strong>?confirm=yes</strong> to the URL to execute updates.</p>";
    echo "<p>Example: update_march_accrual.php?confirm=yes</p><hr>";
    $previewMode = true;
} else {
    $previewMode = false;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green;'>✅ Connected to database</p>";
    
    if (!$previewMode) {
        $pdo->beginTransaction();
    }
    
    $updated = 0;
    $notFound = [];
    $discrepancies = [];
    
    echo "<h3>Processing March 2026 Accrual Updates:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr style='background:#333; color:white;'>";
    echo "<th>Spreadsheet Name</th><th>DB Name</th><th>Emp ID</th>";
    echo "<th>VL (Spr)</th><th>VL (DB)</th><th>VL Diff</th>";
    echo "<th>SL (Spr)</th><th>SL (DB)</th><th>SL Diff</th>";
    echo "<th>SPL (Spr)</th><th>SPL (DB)</th><th>SPL Diff</th>";
    echo "<th>Status</th></tr>";
    
    foreach ($marchAccrualData as $emp) {
        // Get name parts for matching
        if (isset($emp['db_lname']) && isset($emp['db_fname'])) {
            $lastName = $emp['db_lname'];
            $firstName = $emp['db_fname'];
        } else {
            $nameParts = explode(', ', $emp['name']);
            $lastName = trim($nameParts[0]);
            $firstNameParts = isset($nameParts[1]) ? explode(' ', trim($nameParts[1])) : [''];
            $firstName = $firstNameParts[0];
        }
        
        // Strategy 1: Exact match on last name, first word of first name
        $stmt = $pdo->prepare("
            SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
            FROM employees 
            WHERE UPPER(lname) = UPPER(?) 
            AND UPPER(fname) LIKE UPPER(?)
            AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$lastName, $firstName . '%']);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Strategy 2: Last name match with partial first name
        if (!$employee) {
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) = UPPER(?) 
                AND UPPER(fname) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$lastName, '%' . $firstName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 3: Match on full name pattern
        if (!$employee) {
            $fullSearchName = str_replace(', ', ' ', $emp['name']);
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(CONCAT(fname, ' ', lname)) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $lastName . '%' . $firstName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 4: SURNAME ONLY match
        if (!$employee) {
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) = UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$lastName]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 5: Partial surname match for compound names
        if (!$employee && strpos($lastName, ' ') !== false) {
            $lastNameParts = explode(' ', $lastName);
            $lastPart = end($lastNameParts);
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(lname) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $lastPart . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Strategy 6: First name search if last name doesn't match
        if (!$employee) {
            $stmt = $pdo->prepare("
                SELECT id, fname, lname, CONCAT(fname, ' ', lname) as full_name 
                FROM employees 
                WHERE UPPER(fname) LIKE UPPER(?)
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute(['%' . $firstName . '%']);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($employee) {
            $empId = $employee['id'];
            $dbName = $employee['full_name'];
            
            // Get current leave credits from database
            $creditStmt = $pdo->prepare("
                SELECT leave_type, balance 
                FROM leave_credits 
                WHERE employee_id = ? AND year = 2026
            ");
            $creditStmt->execute([$empId]);
            $dbCredits = $creditStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $dbVL = $dbCredits['vacation'] ?? null;
            $dbSL = $dbCredits['sick'] ?? null;
            $dbSPL = $dbCredits['solo_parent'] ?? null;
            
            // Calculate differences
            $vlDiff = ($emp['vl'] !== null && $dbVL !== null) ? round($emp['vl'] - $dbVL, 2) : '-';
            $slDiff = ($emp['sl'] !== null && $dbSL !== null) ? round($emp['sl'] - $dbSL, 2) : '-';
            $splDiff = ($emp['spl'] !== null && $dbSPL !== null) ? round($emp['spl'] - $dbSPL, 2) : '-';
            
            // Track discrepancies
            $hasDiscrepancy = false;
            if ($vlDiff !== '-' && $vlDiff != 0) $hasDiscrepancy = true;
            if ($slDiff !== '-' && $slDiff != 0) $hasDiscrepancy = true;
            if ($splDiff !== '-' && $splDiff != 0) $hasDiscrepancy = true;
            
            if ($hasDiscrepancy) {
                $discrepancies[] = [
                    'name' => $emp['name'],
                    'db_name' => $dbName,
                    'emp_id' => $empId,
                    'vl_spr' => $emp['vl'],
                    'vl_db' => $dbVL,
                    'vl_diff' => $vlDiff,
                    'sl_spr' => $emp['sl'],
                    'sl_db' => $dbSL,
                    'sl_diff' => $slDiff,
                    'spl_spr' => $emp['spl'],
                    'spl_db' => $dbSPL,
                    'spl_diff' => $splDiff,
                ];
            }
            
            // Update only if not in preview mode
            if (!$previewMode) {
                // Update VL (vacation)
                if ($emp['vl'] !== null) {
                    $updateVL = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, updated_at = NOW() 
                        WHERE employee_id = ? AND leave_type = 'vacation' AND year = 2026
                    ");
                    $updateVL->execute([$emp['vl'], $empId]);
                }
                
                // Update SL (sick)
                if ($emp['sl'] !== null) {
                    $updateSL = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, updated_at = NOW() 
                        WHERE employee_id = ? AND leave_type = 'sick' AND year = 2026
                    ");
                    $updateSL->execute([$emp['sl'], $empId]);
                }
                
                // Update SPL (solo_parent) if applicable
                if ($emp['spl'] !== null) {
                    $updateSPL = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = ?, updated_at = NOW() 
                        WHERE employee_id = ? AND leave_type = 'solo_parent' AND year = 2026
                    ");
                    $updateSPL->execute([$emp['spl'], $empId]);
                }
            }
            
            // Row styling
            $rowStyle = $hasDiscrepancy ? "background:#fff3cd;" : "";
            
            echo "<tr style='$rowStyle'>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>{$dbName}</td>";
            echo "<td>{$empId}</td>";
            echo "<td>" . ($emp['vl'] ?? '-') . "</td>";
            echo "<td>" . ($dbVL ?? '-') . "</td>";
            echo "<td style='color:" . ($vlDiff != 0 && $vlDiff !== '-' ? 'red' : 'green') . ";'>" . ($vlDiff !== '-' ? ($vlDiff > 0 ? '+' . $vlDiff : $vlDiff) : '-') . "</td>";
            echo "<td>" . ($emp['sl'] ?? '-') . "</td>";
            echo "<td>" . ($dbSL ?? '-') . "</td>";
            echo "<td style='color:" . ($slDiff != 0 && $slDiff !== '-' ? 'red' : 'green') . ";'>" . ($slDiff !== '-' ? ($slDiff > 0 ? '+' . $slDiff : $slDiff) : '-') . "</td>";
            echo "<td>" . ($emp['spl'] ?? '-') . "</td>";
            echo "<td>" . ($dbSPL ?? '-') . "</td>";
            echo "<td style='color:" . ($splDiff != 0 && $splDiff !== '-' ? 'red' : 'green') . ";'>" . ($splDiff !== '-' ? ($splDiff > 0 ? '+' . $splDiff : $splDiff) : '-') . "</td>";
            echo "<td style='color:green;'>✅ " . ($previewMode ? 'MATCHED' : 'Updated') . "</td>";
            echo "</tr>";
            
            $updated++;
            
        } else {
            $notFound[] = [
                'name' => $emp['name'],
                'vl' => $emp['vl'],
                'sl' => $emp['sl'],
                'spl' => $emp['spl']
            ];
            
            echo "<tr style='background:#ffeeee;'>";
            echo "<td>{$emp['name']}</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>" . ($emp['vl'] ?? '-') . "</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>" . ($emp['sl'] ?? '-') . "</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>" . ($emp['spl'] ?? '-') . "</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td style='color:red;'>❌ NOT FOUND</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Summary Section
    echo "<hr><h3>📊 Summary:</h3>";
    echo "<p>✅ Matched/Updated: <strong>$updated</strong> employees</p>";
    echo "<p>❌ Not Found: <strong>" . count($notFound) . "</strong> employees</p>";
    echo "<p>⚠️ Discrepancies: <strong>" . count($discrepancies) . "</strong> employees</p>";
    
    // Not Found Report
    if (!empty($notFound)) {
        echo "<hr><h3>❌ EMPLOYEES NOT FOUND IN SYSTEM:</h3>";
        echo "<p style='color:red;'>The following employees from the spreadsheet could not be matched in the database:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background:#dc3545; color:white;'><th>#</th><th>Name in Spreadsheet</th><th>VL</th><th>SL</th><th>SPL</th><th>Possible Reason</th></tr>";
        
        $i = 1;
        foreach ($notFound as $emp) {
            $reason = "Employee not in system";
            // Check for possible reasons
            if (stripos($emp['name'], 'PATAWARAN') !== false || stripos($emp['name'], 'FERNANDEZ, MARIANNE') !== false) {
                $reason = "Possibly terminated";
            } elseif (stripos($emp['name'], 'CAPIRAL') !== false) {
                $reason = "Possibly probationary";
            } elseif (stripos($emp['name'], 'OCAMPO, ALFRED') !== false) {
                $reason = "Missing from DB - verify employee status";
            }
            
            echo "<tr>";
            echo "<td>$i</td>";
            echo "<td><strong>{$emp['name']}</strong></td>";
            echo "<td>" . ($emp['vl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['sl'] ?? '-') . "</td>";
            echo "<td>" . ($emp['spl'] ?? '-') . "</td>";
            echo "<td style='color:orange;'>$reason</td>";
            echo "</tr>";
            $i++;
        }
        echo "</table>";
    }
    
    // Discrepancy Report
    if (!empty($discrepancies)) {
        echo "<hr><h3>⚠️ DISCREPANCY REPORT (DB vs Spreadsheet Before Update):</h3>";
        echo "<p>These employees had different values in the database compared to the spreadsheet:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background:#ffc107; color:black;'>";
        echo "<th>#</th><th>Name</th><th>DB Name</th><th>Emp ID</th>";
        echo "<th>VL (Spr)</th><th>VL (DB)</th><th>VL Diff</th>";
        echo "<th>SL (Spr)</th><th>SL (DB)</th><th>SL Diff</th>";
        echo "<th>SPL (Spr)</th><th>SPL (DB)</th><th>SPL Diff</th>";
        echo "</tr>";
        
        $i = 1;
        foreach ($discrepancies as $d) {
            echo "<tr>";
            echo "<td>$i</td>";
            echo "<td>{$d['name']}</td>";
            echo "<td>{$d['db_name']}</td>";
            echo "<td>{$d['emp_id']}</td>";
            echo "<td>{$d['vl_spr']}</td>";
            echo "<td>" . ($d['vl_db'] ?? '-') . "</td>";
            echo "<td style='color:" . ($d['vl_diff'] != 0 ? 'red' : 'green') . ";'>{$d['vl_diff']}</td>";
            echo "<td>{$d['sl_spr']}</td>";
            echo "<td>" . ($d['sl_db'] ?? '-') . "</td>";
            echo "<td style='color:" . ($d['sl_diff'] != 0 ? 'red' : 'green') . ";'>{$d['sl_diff']}</td>";
            echo "<td>" . ($d['spl_spr'] ?? '-') . "</td>";
            echo "<td>" . ($d['spl_db'] ?? '-') . "</td>";
            echo "<td style='color:" . ($d['spl_diff'] != 0 ? 'red' : 'green') . ";'>{$d['spl_diff']}</td>";
            echo "</tr>";
            $i++;
        }
        echo "</table>";
    }
    
    if (!$previewMode) {
        $pdo->commit();
        echo "<p style='color:green; font-size:18px;'><strong>✅ ALL CHANGES COMMITTED!</strong></p>";
    } else {
        echo "<hr><p style='color:blue; font-size:14px;'><strong>ℹ️ PREVIEW MODE - No changes made. Add ?confirm=yes to apply updates.</strong></p>";
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color:red;'>❌ DATABASE ERROR: " . $e->getMessage() . "</p>";
}
?>
