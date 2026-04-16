<?php
/**
 * Seed employee-specific holiday profiles and assignments.
 *
 * Usage:
 *   setup_employee_holiday_profiles.php
 *   setup_employee_holiday_profiles.php?confirm=yes
 *
 * The script is safe to run in dry-run mode first.
 */

require_once __DIR__ . '/Public/config/db.php';

if (!isset($pdo)) {
    die("Database connection not available.");
}

set_time_limit(0);

function normalize_name(string $value): string
{
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii === false) {
        $ascii = $value;
    }

    $ascii = strtolower($ascii);
    $ascii = preg_replace('/[^a-z0-9]+/', ' ', $ascii);
    $ascii = preg_replace('/\s+/', ' ', $ascii);
    return trim($ascii);
}

function tokenize_name(string $value): array
{
    $normalized = normalize_name($value);
    if ($normalized === '') {
        return [];
    }

    $tokens = preg_split('/\s+/', $normalized) ?: [];
    $stopWords = ['jr', 'sr', 'ii', 'iii', 'iv'];

    $filtered = [];
    foreach ($tokens as $token) {
        if ($token === '' || in_array($token, $stopWords, true)) {
            continue;
        }
        $filtered[] = $token;
    }

    return array_values(array_unique($filtered));
}

function parse_employee_name(string $value): array
{
    $parts = explode(',', $value, 2);
    if (count($parts) === 2) {
        return [trim($parts[0]), trim($parts[1])];
    }

    $tokens = preg_split('/\s+/', trim($value));
    if (count($tokens) >= 2) {
        $lastName = array_shift($tokens);
        return [trim($lastName), trim(implode(' ', $tokens))];
    }

    return [trim($value), ''];
}

function score_candidate(string $needle, string $candidate): int
{
    $needleNormalized = normalize_name($needle);
    $candidateNormalized = normalize_name($candidate);

    if ($needleNormalized === $candidateNormalized) {
        return 0;
    }

    $needleTokens = tokenize_name($needle);
    $candidateTokens = tokenize_name($candidate);

    if (!$needleTokens || !$candidateTokens) {
        return levenshtein($needleNormalized, $candidateNormalized);
    }

    $commonTokens = array_intersect($needleTokens, $candidateTokens);
    $missingTokens = array_diff($needleTokens, $candidateTokens);
    $extraTokens = array_diff($candidateTokens, $needleTokens);

    $score = (count($missingTokens) * 12) + (count($extraTokens) * 2) + levenshtein($needleNormalized, $candidateNormalized);

    if ($commonTokens) {
        $score -= (count($commonTokens) * 8);
    }

    return max(0, $score);
}

function find_best_employee(PDO $pdo, string $displayName): ?array
{
    [$lastName, $firstName] = parse_employee_name($displayName);
    $searchValues = [
        normalize_name($displayName),
        normalize_name($lastName . ' ' . $firstName),
        normalize_name($firstName . ' ' . $lastName),
    ];

    $employeesStmt = $pdo->query("\n        SELECT id, fname, lname, CONCAT(lname, ', ', fname) AS display_name\n        FROM employees\n        WHERE status = 'active'\n        ORDER BY lname, fname\n    ");
    $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

    $bestMatch = null;
    $bestScore = PHP_INT_MAX;

    foreach ($employees as $employee) {
        $employeeFullName = $employee['fname'] . ' ' . $employee['lname'];
        $employeeReversedName = $employee['lname'] . ', ' . $employee['fname'];
        $candidates = [
            $employee['display_name'],
            $employeeFullName,
            $employeeReversedName,
            $employee['fname'],
            $employee['lname'],
        ];

        foreach ($searchValues as $searchValue) {
            foreach ($candidates as $candidate) {
                $score = score_candidate($searchValue, $candidate);
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $employee;
                }
                if ($score === 0) {
                    return $employee;
                }
            }
        }

        $employeeTokens = tokenize_name($employeeFullName);
        $searchTokens = tokenize_name($displayName);
        if ($employeeTokens && $searchTokens) {
            $tokenOverlap = count(array_intersect($employeeTokens, $searchTokens));
            $tokenScore = (count($employeeTokens) + count($searchTokens)) - (2 * $tokenOverlap);
            if ($tokenOverlap >= 2 && $tokenScore < $bestScore) {
                $bestScore = $tokenScore;
                $bestMatch = $employee;
            }
        }
    }

    return ($bestMatch && $bestScore <= 10) ? $bestMatch : null;
}

function ensure_table(PDO $pdo, string $sql): void
{
    $pdo->exec($sql);
}

echo "<pre>";
echo "========================================\n";
echo "Employee Holiday Profile Seeder\n";
echo "========================================\n\n";

ensure_table($pdo, "\n    CREATE TABLE IF NOT EXISTS employee_holiday_profiles (\n        id INT(11) NOT NULL AUTO_INCREMENT,\n        profile_code VARCHAR(50) NOT NULL,\n        profile_name VARCHAR(255) NOT NULL,\n        description TEXT NULL,\n        is_active TINYINT(1) DEFAULT 1,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        PRIMARY KEY (id),\n        UNIQUE KEY unique_profile_code (profile_code),\n        INDEX idx_profile_active (is_active)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

ensure_table($pdo, "\n    CREATE TABLE IF NOT EXISTS employee_holiday_profile_holidays (\n        id INT(11) NOT NULL AUTO_INCREMENT,\n        profile_id INT(11) NOT NULL,\n        holiday_date DATE NOT NULL,\n        holiday_name VARCHAR(255) NOT NULL,\n        holiday_type ENUM('regular', 'special_non_working', 'special_working') DEFAULT 'regular',\n        notes TEXT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        PRIMARY KEY (id),\n        UNIQUE KEY unique_profile_holiday (profile_id, holiday_date),\n        FOREIGN KEY (profile_id) REFERENCES employee_holiday_profiles(id) ON DELETE CASCADE,\n        INDEX idx_profile_holiday_date (profile_id, holiday_date),\n        INDEX idx_profile_holiday_type (holiday_type)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

ensure_table($pdo, "\n    CREATE TABLE IF NOT EXISTS employee_holiday_profile_assignments (\n        id INT(11) NOT NULL AUTO_INCREMENT,\n        employee_id INT(11) NOT NULL,\n        profile_id INT(11) NOT NULL,\n        effective_from DATE NOT NULL,\n        effective_until DATE NULL,\n        is_active TINYINT(1) DEFAULT 1,\n        notes TEXT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        PRIMARY KEY (id),\n        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,\n        FOREIGN KEY (profile_id) REFERENCES employee_holiday_profiles(id) ON DELETE CASCADE,\n        UNIQUE KEY unique_employee_profile_start (employee_id, profile_id, effective_from),\n        INDEX idx_employee_profile_dates (employee_id, effective_from, effective_until),\n        INDEX idx_profile_active (profile_id, is_active)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

$profiles = [
    [
        'profile_code' => 'PH_HOLIDAY',
        'profile_name' => 'PH - HOLIDAY',
        'description' => 'Philippine holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-04-02', 'Maundy Thursday', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-09', 'Araw ng Kagitingan', 'regular'],
            ['2026-05-01', 'Labor Day', 'regular'],
            ['2026-06-12', 'Independence Day', 'regular'],
            ['2026-08-25', 'Ninoy Aquino Day', 'special_non_working'],
            ['2026-08-31', 'National Heroes Day', 'regular'],
            ['2026-11-01', 'All Saints\' Day', 'special_non_working'],
            ['2026-11-30', 'Bonifacio Day', 'regular'],
            ['2026-12-24', 'Christmas Eve', 'special_non_working'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-30', 'Rizal Day', 'regular'],
            ['2026-12-31', 'New Year\'s Eve', 'special_non_working'],
        ],
        'employees' => [
            'Alvarez, John Bryan',
            'Angeles, Christine Khlaryss',
            'Asuncion, Jay Kimbert Argao',
            'Bautista, Oliva',
            'Bunda, Abigael',
            'Caraan, Sarah',
            'Cochon, Cesar',
            'David, Rebecca',
            'David, Ryan Arwin',
            'Gagote, Elmer',
            'Guevarra, Jeiel Nash',
            'Guillermo, Alfie',
            'Ignacio, Jairus',
            'Ocampo, Godwin',
            'Otaka, Eri',
            'Rivera, Alvir',
        ],
    ],
    [
        'profile_code' => 'SOUTH_AUSTRALIA',
        'profile_name' => 'SOUTH AUSTRALIA',
        'description' => 'South Australia holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-01-26', 'Australia Day', 'regular'],
            ['2026-03-09', 'Adelaide Cup Day', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-04', 'Easter Saturday', 'regular'],
            ['2026-04-05', 'Easter Sunday', 'regular'],
            ['2026-04-06', 'Easter Monday', 'regular'],
            ['2026-04-25', 'ANZAC Day', 'regular'],
            ['2026-06-08', 'King\'s Birthday', 'regular'],
            ['2026-10-05', 'Labour Day', 'regular'],
            ['2026-12-24', 'Christmas Eve', 'special_non_working'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-26', 'Boxing Day', 'regular'],
            ['2026-12-28', 'Proclamation Day', 'regular'],
            ['2026-12-31', 'New Year\'s Eve', 'special_non_working'],
        ],
        'employees' => [
            'Dela Cruz, Jonas',
            'Fernandez, Marianne Jae Andrada',
            'Gonzales, Cherry',
        ],
    ],
    [
        'profile_code' => 'WESTERN_AUSTRALIA',
        'profile_name' => 'WESTERN AUSTRALIA',
        'description' => 'Western Australia holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-01-26', 'Australia Day', 'regular'],
            ['2026-03-02', 'Labour Day', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-05', 'Easter Sunday', 'regular'],
            ['2026-04-06', 'Easter Monday', 'regular'],
            ['2026-04-25', 'ANZAC Day', 'regular'],
            ['2026-04-27', 'ANZAC Day (Observed)', 'regular'],
            ['2026-06-01', 'Western Australia Day', 'regular'],
            ['2026-09-28', 'King\'s Birthday', 'regular'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-26', 'Boxing Day', 'regular'],
            ['2026-12-28', 'Boxing Day (Observed)', 'regular'],
        ],
        'employees' => [
            'Arceo, Karla',
            'Balderas, Glory Ann',
            'Belangel, Karen',
            'Briones, John Michael Comprado',
            'Cabusao, Precious Zahra Cortez',
            'Calma, Michelle',
            'Capiral, Gabriel',
            'Costelloe, Neil Anthony',
            'Cueto, Ron Paulo',
            'Dacquil, Kimberly',
            'Dimla, Jhosua',
            'Espinosa, Jerome',
            'Herrera, Felicci',
            'Jabat, Justine Alex Gabrido',
            'Macapagal, Jeffry Tuazon',
            'McGregor, Trisha Mae Adriano',
            'Nunez, Dou Lester Sabando',
            'Pangan, Cristina Miranda',
            'Patacsil, Bon Febryx',
            'Platero, Ma. Charisma S.',
            'Santiago, Ria',
            'Tolomia, Rica Joy',
            'Yap, Apryl Pasion',
            'Yulo, Brittany Lacsamana',
        ],
    ],
    [
        'profile_code' => 'NEW_SOUTH_WALES',
        'profile_name' => 'NEW SOUTH WALES',
        'description' => 'New South Wales holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-01-26', 'Australia Day', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-04', 'Easter Saturday', 'regular'],
            ['2026-04-05', 'Easter Sunday', 'regular'],
            ['2026-04-06', 'Easter Monday', 'regular'],
            ['2026-04-25', 'ANZAC Day', 'regular'],
            ['2026-06-08', 'King\'s Birthday', 'regular'],
            ['2026-10-05', 'Labour Day', 'regular'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-26', 'Boxing Day', 'regular'],
            ['2026-12-28', 'Boxing Day (Observed)', 'regular'],
        ],
        'employees' => [
            'Agabin, Ria',
            'Flores, Chloedean Ong',
            'Patawaran, Sherry Rose Ann Nun',
            'Maniti, Sofia Olivia Gonzales',
            'Tupaz, Carl Dave Empleo',
        ],
    ],
    [
        'profile_code' => 'VICTORIA',
        'profile_name' => 'VICTORIA',
        'description' => 'Victoria holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-01-26', 'Australia Day', 'regular'],
            ['2026-03-09', 'Labour Day', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-04', 'Easter Saturday', 'regular'],
            ['2026-04-05', 'Easter Sunday', 'regular'],
            ['2026-04-06', 'Easter Monday', 'regular'],
            ['2026-04-25', 'ANZAC Day', 'regular'],
            ['2026-06-08', 'King\'s Birthday', 'regular'],
            ['2026-09-25', 'Friday before the AFL Grand Final', 'regular'],
            ['2026-11-03', 'Melbourne Cup Day', 'regular'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-26', 'Boxing Day', 'regular'],
            ['2026-12-28', 'Boxing Day (Observed)', 'regular'],
        ],
        'employees' => [
            'Antonio, Vincent Kevin Santos',
            'Agas, Jillian Lao',
            'Agkis, Efrael Olaes',
            'Aguilar, Ian Myco Vital',
            'Alimurong, Joel Lusung',
            'Austria, Louis Fernand Balyut',
            'Bacongallo, Nika Nueva',
            'Basilio, Abigail Ann Quizon',
            'Benalla, Reneeca Villapana',
            'Calabia, Nerine Tiama',
            'Calma, Michelle Pangan',
            'Camerino, Yris Gaelle Parrenas',
            'Capati, Allen Sobrepena',
            'Castro, Aizel Santos',
            'Catalogo, Marnie Perez',
            'Celeste, Lovelaine Gudoy',
            'Colis, Reymark Bryan Silvano',
            'Crisanto, Elritz',
            'Dela Cruz, Shaina Dimayugo',
            'Dollentes, Maria Nina Dizon',
            'Elias, Trisha Navarro',
            'Estanio, Angelica Rosario',
            'Fernandez, Francis Emmanuel Veloso',
            'Garcia, Marirey Cernal',
            'Gatbonton, Analiza Taloban',
            'Gatbonton, Beverly Taloban',
            'Gueco, Johana Rose Perez',
            'Josafat, Renalyn Abamo',
            'Linga, Trixie Jhem Gutierrez',
            'Lozano, Aldwin John Arceo',
            'Maclang, Julie Anne Guinto',
            'Malinao Jr, Rogelio Dela Pena',
            'Mar, Christian Nioda',
            'Mataga, Edith David',
            'Mendoza, Sean Justine Francisco',
            'Mira, Rowen Daniel Naguit',
            'Monis, Joshwea Mercado',
            'Navalon, Evanel Caacbay',
            'Nunez, Ivy',
            'Otsuka, Shigeru Centina',
            'Patrimonio, Rex Ryan',
            'Pineda, Erika Seriosa',
            'Ronquillo, Rhegene Ingat',
            'Rosario, Gener',
            'Samodio, Jhunel Carlo Traifalgar',
            'Solayao, Janeth Sedon',
            'Soriano, Mary Ann Vallejos',
            'Trinidad, Jennifer Mangingting',
            'Uncad, Jessica Mae Serrano',
        ],
    ],
    [
        'profile_code' => 'QUEENSLAND',
        'profile_name' => 'QUEENSLAND',
        'description' => 'Queensland holiday calendar profile',
        'holidays' => [
            ['2026-01-01', 'New Year\'s Day', 'regular'],
            ['2026-01-26', 'Australia Day', 'regular'],
            ['2026-04-03', 'Good Friday', 'regular'],
            ['2026-04-04', 'Easter Saturday', 'regular'],
            ['2026-04-05', 'Easter Sunday', 'regular'],
            ['2026-04-06', 'Easter Monday', 'regular'],
            ['2026-04-25', 'ANZAC Day', 'regular'],
            ['2026-05-04', 'Labour Day', 'regular'],
            ['2026-10-05', 'King\'s Birthday', 'regular'],
            ['2026-12-25', 'Christmas Day', 'regular'],
            ['2026-12-26', 'Boxing Day', 'regular'],
            ['2026-12-28', 'Boxing Day (Observed)', 'regular'],
        ],
        'employees' => [
            'Bansil, Kristian David',
            'Manalili, Joshua Manaloto',
            'Pangilinan, Roi Dane Dela Pena',
            'Bautista, Russell Rudolf Cura',
        ],
    ],
];

$confirm = strtolower($_GET['confirm'] ?? 'no');
$isConfirm = in_array($confirm, ['yes', 'true', '1'], true);

$profileStmt = $pdo->prepare("\n    INSERT INTO employee_holiday_profiles (profile_code, profile_name, description, is_active)\n    VALUES (?, ?, ?, 1)\n    ON DUPLICATE KEY UPDATE\n        profile_name = VALUES(profile_name),\n        description = VALUES(description),\n        is_active = VALUES(is_active),\n        updated_at = CURRENT_TIMESTAMP\n");

$profileLookupStmt = $pdo->prepare("SELECT id FROM employee_holiday_profiles WHERE profile_code = ? LIMIT 1");
$holidayStmt = $pdo->prepare("\n    INSERT INTO employee_holiday_profile_holidays (profile_id, holiday_date, holiday_name, holiday_type)\n    VALUES (?, ?, ?, ?)\n    ON DUPLICATE KEY UPDATE\n        holiday_name = VALUES(holiday_name),\n        holiday_type = VALUES(holiday_type),\n        updated_at = CURRENT_TIMESTAMP\n");

$assignmentStmt = $pdo->prepare("\n    INSERT INTO employee_holiday_profile_assignments (employee_id, profile_id, effective_from, effective_until, is_active, notes)\n    VALUES (?, ?, ?, NULL, 1, ?)\n    ON DUPLICATE KEY UPDATE\n        profile_id = VALUES(profile_id),\n        effective_from = VALUES(effective_from),\n        effective_until = VALUES(effective_until),\n        is_active = VALUES(is_active),\n        notes = VALUES(notes),\n        updated_at = CURRENT_TIMESTAMP\n");

$effectiveFrom = date('Y-m-d');

echo "Mode: " . ($isConfirm ? 'LIVE' : 'DRY RUN') . "\n\n";

foreach ($profiles as $profile) {
    echo "Profile: {$profile['profile_code']}\n";

    if ($isConfirm) {
        $profileStmt->execute([$profile['profile_code'], $profile['profile_name'], $profile['description']]);
        $profileLookupStmt->execute([$profile['profile_code']]);
        $profileId = (int)($profileLookupStmt->fetchColumn() ?: 0);
        if (!$profileId) {
            echo "  ! Missing profile row\n";
            continue;
        }
    } else {
        $profileId = null;
        echo "  Would create profile row\n";
    }

    echo "  Holidays: " . count($profile['holidays']) . "\n";
    foreach ($profile['holidays'] as $holiday) {
        echo "    - {$holiday[0]} | {$holiday[1]} | {$holiday[2]}\n";
        if ($isConfirm && $profileId) {
            $holidayStmt->execute([$profileId, $holiday[0], $holiday[1], $holiday[2]]);
        }
    }

    echo "  Employees: " . count($profile['employees']) . "\n";
    foreach ($profile['employees'] as $employeeName) {
        $match = find_best_employee($pdo, $employeeName);

        if (!$match) {
            echo "    - UNMATCHED: {$employeeName}\n";
            continue;
        }

        $matchedName = $match['display_name'];
        echo "    - {$employeeName} => {$matchedName} (ID: {$match['id']})\n";

        if ($isConfirm && $profileId) {
            $assignmentStmt->execute([
                $match['id'],
                $profileId,
                $effectiveFrom,
                'Seeded from holiday profile import'
            ]);
        }
    }

    echo "\n";
}

echo "Summary: " . ($isConfirm ? 'Applied assignments and profile holidays.' : 'Dry run only. Append ?confirm=yes to write changes.') . "\n";
echo "========================================\n";
echo "Done\n";
echo "========================================\n";
echo "</pre>";
