<?php

session_start();
include '../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/ScheduleCacheRebuilder.php';
require_once __DIR__ . '/../config/demo_guard.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['switch_to_employee'])) {
    $_SESSION['view_mode'] = 'employee';
    header("Location: ../module/time_log_create.php");
    exit;
}

if (
    !isset($_SESSION['employee']['id']) ||
    ($_SESSION['employee']['role'] ?? '') !== 'internal' ||
    ($_SESSION['view_mode'] ?? '') !== 'admin'
) {
    header("Location: ../module/time_log_create.php");
    exit;
}

$pageTitle = 'Holidays';

date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

$profileSheetMap = [
    'PH' => 'PH_HOLIDAY',
    'SA' => 'SOUTH_AUSTRALIA',
    'WA' => 'WESTERN_AUSTRALIA',
    'NSW' => 'NEW_SOUTH_WALES',
    'VIC' => 'VICTORIA',
    'QLD' => 'QUEENSLAND',
];

function holidayTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function holidayNormalizeName(?string $name): string
{
    $name = trim((string)$name);
    $name = str_replace("\xc2\xa0", ' ', $name);
    $name = str_replace(["\t", "\r", "\n"], ' ', $name);
    if (class_exists('Transliterator')) {
        $transliterator = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
        $name = $transliterator ? $transliterator->transliterate($name) : $name;
    } elseif (function_exists('iconv')) {
        $asciiName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = $asciiName !== false ? $asciiName : $name;
    }
    $name = preg_replace('/[^\p{L}\p{N},]+/u', ' ', $name);
    $name = preg_replace('/\s*,\s*/u', ', ', $name);
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = trim($name);

    return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
}

function holidayParseName(?string $name): array
{
    $normalized = holidayNormalizeName($name);
    if ($normalized === '') {
        return ['given' => '', 'last' => '', 'exact_keys' => []];
    }

    if (str_contains($normalized, ',')) {
        [$last, $given] = array_pad(array_map('trim', explode(',', $normalized, 2)), 2, '');
    } else {
        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $last = count($parts) > 1 ? (string)array_pop($parts) : '';
        $given = implode(' ', $parts);
    }

    $exactKeys = array_values(array_unique(array_filter([
        trim($last . ', ' . $given),
        trim($given . ' ' . $last),
    ])));

    return [
        'given' => $given,
        'last' => $last,
        'given_tokens' => preg_split('/\s+/u', $given, -1, PREG_SPLIT_NO_EMPTY) ?: [],
        'last_tokens' => preg_split('/\s+/u', $last, -1, PREG_SPLIT_NO_EMPTY) ?: [],
        'all_tokens' => array_values(array_unique(preg_split('/\s+/u', trim($given . ' ' . $last), -1, PREG_SPLIT_NO_EMPTY) ?: [])),
        'exact_keys' => $exactKeys,
    ];
}

function holidayEmployeeLabel(array $employee): string
{
    return trim(($employee['fname'] ?? '') . ' ' . ($employee['lname'] ?? ''));
}

function holidayBuildEmployeeIndex(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, fname, lname, email, status
        FROM employees
        WHERE LOWER(status) = 'active'
        ORDER BY lname, fname
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $index = ['exact' => [], 'employees' => []];

    foreach ($employees as $employee) {
        $fname = trim((string)($employee['fname'] ?? ''));
        $lname = trim((string)($employee['lname'] ?? ''));
        $parsed = holidayParseName($lname . ', ' . $fname);
        $employee['_holiday_name'] = $parsed;
        $index['employees'][] = $employee;

        foreach ($parsed['exact_keys'] as $key) {
            $index['exact'][$key][] = $employee;
        }
    }

    return $index;
}

function holidayIsTokenSubset(array $shorter, array $longer): bool
{
    if (empty($shorter) || count($shorter) >= count($longer)) {
        return false;
    }

    return count(array_diff($shorter, $longer)) === 0;
}

function holidayIsSameTokenSet(array $left, array $right): bool
{
    return count($left) === count($right)
        && count(array_diff($left, $right)) === 0
        && count(array_diff($right, $left)) === 0;
}

function holidayFindEmployeeMatches(array $employeeIndex, string $sourceName): array
{
    $parsed = holidayParseName($sourceName);
    $exactMatches = [];

    foreach ($parsed['exact_keys'] as $key) {
        foreach ($employeeIndex['exact'][$key] ?? [] as $employee) {
            $exactMatches[(int)$employee['id']] = $employee;
        }
    }

    if (!empty($exactMatches)) {
        return array_values($exactMatches);
    }

    $scored = [];
    foreach ($employeeIndex['employees'] as $employee) {
        $candidate = $employee['_holiday_name'];
        $score = 0;
        $sameLastName = $parsed['last'] !== '' && $parsed['last'] === $candidate['last'];
        $lastNameContained = count(array_intersect($parsed['last_tokens'], $candidate['last_tokens'])) > 0;

        if ($sameLastName || $lastNameContained) {
            if ($parsed['given'] === $candidate['given']) {
                $score = 100;
            } elseif (
                str_starts_with($parsed['given'] . ' ', $candidate['given'] . ' ') ||
                str_starts_with($candidate['given'] . ' ', $parsed['given'] . ' ')
            ) {
                $score = 90;
            } elseif (
                holidayIsTokenSubset($parsed['given_tokens'], $candidate['given_tokens']) ||
                holidayIsTokenSubset($candidate['given_tokens'], $parsed['given_tokens'])
            ) {
                $score = 85;
            } elseif (
                !empty($parsed['given_tokens']) &&
                !empty($candidate['given_tokens']) &&
                levenshtein($parsed['given_tokens'][0], $candidate['given_tokens'][0]) <= 2
            ) {
                $score = 80;
            } elseif (
                !empty($parsed['given_tokens']) &&
                !empty($candidate['given_tokens']) &&
                $parsed['given_tokens'][0] === $candidate['given_tokens'][0]
            ) {
                $score = 70;
            }
        }

        $sharedTokens = count(array_intersect($parsed['all_tokens'], $candidate['all_tokens']));
        $unionTokens = count(array_unique(array_merge($parsed['all_tokens'], $candidate['all_tokens'])));
        $tokenSimilarity = $unionTokens > 0 ? $sharedTokens / $unionTokens : 0;

        if (holidayIsSameTokenSet($parsed['all_tokens'], $candidate['all_tokens'])) {
            $score = max($score, 95);
        } elseif (
            count($candidate['all_tokens']) >= 3 &&
            holidayIsTokenSubset($candidate['all_tokens'], $parsed['all_tokens']) &&
            count($parsed['all_tokens']) === count($candidate['all_tokens']) + 1
        ) {
            $score = max($score, 88);
        } elseif ($sharedTokens >= 3 && $tokenSimilarity >= 0.6) {
            $score = max($score, 82);
        }

        if ($score > 0) {
            $scored[(int)$employee['id']] = ['score' => $score, 'employee' => $employee];
        }
    }

    if (empty($scored)) {
        return [];
    }

    $bestScore = max(array_column($scored, 'score'));
    return array_values(array_map(
        static fn($match) => $match['employee'],
        array_filter($scored, static fn($match) => $match['score'] === $bestScore)
    ));
}

function holidayLoadProfiles(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, profile_code, profile_name FROM employee_holiday_profiles WHERE is_active = 1 ORDER BY profile_name");
    $profiles = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $profile) {
        $profiles[$profile['profile_code']] = $profile;
    }

    return $profiles;
}

function holidayGetProfileAtDate(PDO $pdo, int $employeeId, string $date): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.id AS assignment_id, a.profile_id, p.profile_code, p.profile_name
        FROM employee_holiday_profile_assignments a
        INNER JOIN employee_holiday_profiles p ON p.id = a.profile_id
        WHERE a.employee_id = ?
          AND a.is_active = 1
          AND p.is_active = 1
          AND a.effective_from <= ?
          AND (a.effective_until IS NULL OR a.effective_until >= ?)
        ORDER BY a.effective_from DESC, a.id DESC
        LIMIT 1
    ");
    $stmt->execute([$employeeId, $date, $date]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function holidayLoadProfileStats(PDO $pdo, array $profileSheetMap): array
{
    $stats = [];
    $today = date('Y-m-d');
    $totalActive = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE LOWER(status) = 'active'")->fetchColumn();
    $assignedActive = 0;

    foreach ($profileSheetMap as $sheet => $profileCode) {
        $stmt = $pdo->prepare("
            SELECT p.profile_name, COUNT(DISTINCT e.id) AS employee_count
            FROM employee_holiday_profiles p
            LEFT JOIN employee_holiday_profile_assignments a
                ON a.profile_id = p.id
               AND a.is_active = 1
               AND a.effective_from <= ?
               AND (a.effective_until IS NULL OR a.effective_until >= ?)
            LEFT JOIN employees e
                ON e.id = a.employee_id
               AND LOWER(e.status) = 'active'
            WHERE p.profile_code = ?
            GROUP BY p.id, p.profile_name
        ");
        $stmt->execute([$today, $today, $profileCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int)($row['employee_count'] ?? 0);
        $assignedActive += $count;
        $stats[] = [
            'sheet' => $sheet,
            'profile_code' => $profileCode,
            'profile_name' => $row['profile_name'] ?? $profileCode,
            'employee_count' => $count,
        ];
    }

    $stats[] = [
        'sheet' => 'Default',
        'profile_code' => 'COMPANY',
        'profile_name' => 'Company Holidays',
        'employee_count' => max(0, $totalActive - $assignedActive),
    ];

    return $stats;
}

function holidayProcessImport(PDO $pdo, array $profileSheetMap, bool $commitChanges = true): array
{
    $result = [
        'success' => false,
        'message' => '',
        'matched' => 0,
        'updated' => [],
        'unchanged' => [],
        'unmatched' => [],
        'duplicates' => [],
        'ambiguous' => [],
        'missing_profiles' => [],
        'missing_sheets' => [],
        'cache' => ['employees' => 0, 'days' => 0],
        'effective_from' => $_POST['effective_from'] ?? date('Y-m-d'),
        'cache_start' => date('Y-m-d'),
        'cache_end' => date('Y-m-d', strtotime('+12 months')),
    ];

    $effectiveDate = DateTime::createFromFormat('Y-m-d', $_POST['effective_from'] ?? '');
    $dateErrors = DateTime::getLastErrors();
    if (
        !$effectiveDate ||
        ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
        $effectiveDate->format('Y-m-d') !== ($_POST['effective_from'] ?? '')
    ) {
        throw new RuntimeException('Please choose a valid effective date.');
    }
    $effectiveFrom = $effectiveDate->format('Y-m-d');
    $previousAssignmentEnd = (clone $effectiveDate)->modify('-1 day')->format('Y-m-d');
    $result['effective_from'] = $effectiveFrom;
    $result['cache_start'] = min(date('Y-m-d'), $effectiveFrom);
    $result['cache_end'] = date('Y-m-d', strtotime($result['cache_start'] . ' +12 months'));

    if (empty($_FILES['holiday_file']) || ($_FILES['holiday_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please upload a valid Excel file.');
    }

    $originalName = $_FILES['holiday_file']['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('Only .xlsx and .xls files are supported.');
    }
    if (($_FILES['holiday_file']['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('The Excel file must be 10 MB or smaller.');
    }

    $profiles = holidayLoadProfiles($pdo);
    $employeeIndex = holidayBuildEmployeeIndex($pdo);
    $uploadedPath = $_FILES['holiday_file']['tmp_name'];
    try {
        IOFactory::identify($uploadedPath);
        $spreadsheet = IOFactory::load($uploadedPath);
    } catch (Throwable $e) {
        throw new RuntimeException('The uploaded file could not be read as an Excel workbook.');
    }
    $rows = [];
    $occurrences = [];
    $foundSheets = 0;

    foreach ($profileSheetMap as $sheetName => $profileCode) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (!$sheet) {
            $result['missing_sheets'][] = $sheetName;
            continue;
        }
        $foundSheets++;

        $nameColumn = 1;
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumn; $col++) {
            $headerCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            if (holidayNormalizeName((string)$sheet->getCell($headerCoordinate)->getValue()) === 'name') {
                $nameColumn = $col;
                break;
            }
        }

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $nameCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nameColumn) . $row;
            $name = trim((string)$sheet->getCell($nameCoordinate)->getFormattedValue());
            if ($name === '') {
                continue;
            }

            $normalized = holidayNormalizeName($name);
            $entry = [
                'sheet' => $sheetName,
                'profile_code' => $profileCode,
                'name' => $name,
                'normalized' => $normalized,
                'row' => $row,
            ];
            $rows[] = $entry;
            $occurrences[$normalized][] = $entry;
        }
    }

    if ($foundSheets === 0) {
        throw new RuntimeException('No supported holiday sheets were found. Expected PH, SA, WA, NSW, VIC, or QLD.');
    }
    if (empty($rows)) {
        throw new RuntimeException('No employee names were found in the supported holiday sheets.');
    }

    $duplicateNames = [];
    foreach ($occurrences as $normalized => $items) {
        if (count($items) > 1) {
            $duplicateNames[$normalized] = $items;
            $result['duplicates'][] = [
                'name' => $items[0]['name'],
                'locations' => array_map(static fn($item) => $item['sheet'] . ' row ' . $item['row'], $items),
            ];
        }
    }

    $resolved = [];
    foreach ($rows as $row) {
        if (isset($duplicateNames[$row['normalized']])) {
            continue;
        }

        if (!isset($profiles[$row['profile_code']])) {
            $result['missing_profiles'][] = [
                'sheet' => $row['sheet'],
                'profile_code' => $row['profile_code'],
                'name' => $row['name'],
            ];
            continue;
        }

        $matches = holidayFindEmployeeMatches($employeeIndex, $row['name']);
        if (count($matches) === 0) {
            $result['unmatched'][] = [
                'sheet' => $row['sheet'],
                'row' => $row['row'],
                'name' => $row['name'],
            ];
            continue;
        }

        if (count($matches) > 1) {
            $result['ambiguous'][] = [
                'sheet' => $row['sheet'],
                'row' => $row['row'],
                'name' => $row['name'],
                'matches' => array_map(static fn($employee) => holidayEmployeeLabel($employee) . ' (#' . $employee['id'] . ')', $matches),
            ];
            continue;
        }

        $employee = $matches[0];
        $resolved[] = [
            'employee_id' => (int)$employee['id'],
            'employee_name' => holidayEmployeeLabel($employee),
            'sheet' => $row['sheet'],
            'target_profile' => $profiles[$row['profile_code']],
            'source_name' => $row['name'],
        ];
    }

    $byEmployee = [];
    foreach ($resolved as $entry) {
        $byEmployee[$entry['employee_id']][] = $entry;
    }

    $finalEntries = [];
    foreach ($byEmployee as $employeeId => $items) {
        if (count($items) > 1) {
            $result['duplicates'][] = [
                'name' => $items[0]['employee_name'],
                'locations' => array_map(static fn($item) => $item['sheet'] . ' (' . $item['target_profile']['profile_code'] . ')', $items),
            ];
            continue;
        }

        $finalEntries[] = $items[0];
    }

    $changedEmployeeIds = [];
    $pdo->beginTransaction();

    try {
        foreach ($finalEntries as $entry) {
            $currentProfile = holidayGetProfileAtDate($pdo, $entry['employee_id'], $effectiveFrom);
            $targetProfileId = (int)$entry['target_profile']['id'];

            if ($currentProfile && (int)$currentProfile['profile_id'] === $targetProfileId) {
                $result['unchanged'][] = [
                    'employee_id' => $entry['employee_id'],
                    'employee_name' => $entry['employee_name'],
                    'profile_name' => $entry['target_profile']['profile_name'],
                    'sheet' => $entry['sheet'],
                ];
                continue;
            }

            $deactivateStmt = $pdo->prepare("
                UPDATE employee_holiday_profile_assignments
                SET effective_until = ?,
                    is_active = CASE WHEN ? < CURDATE() THEN 0 ELSE 1 END,
                    updated_at = NOW()
                WHERE employee_id = ?
                  AND is_active = 1
                  AND effective_from < ?
                  AND (effective_until IS NULL OR effective_until >= ?)
            ");
            $deactivateStmt->execute([
                $previousAssignmentEnd,
                $previousAssignmentEnd,
                $entry['employee_id'],
                $effectiveFrom,
                $effectiveFrom,
            ]);

            $nextAssignmentStmt = $pdo->prepare("
                SELECT MIN(effective_from)
                FROM employee_holiday_profile_assignments
                WHERE employee_id = ?
                  AND effective_from > ?
            ");
            $nextAssignmentStmt->execute([$entry['employee_id'], $effectiveFrom]);
            $nextEffectiveFrom = $nextAssignmentStmt->fetchColumn();
            $newEffectiveUntil = $nextEffectiveFrom
                ? date('Y-m-d', strtotime($nextEffectiveFrom . ' -1 day'))
                : null;

            $pdo->prepare("
                DELETE FROM employee_holiday_profile_assignments
                WHERE employee_id = ?
                  AND effective_from = ?
            ")->execute([$entry['employee_id'], $effectiveFrom]);

            $assignmentStmt = $pdo->prepare("
                INSERT INTO employee_holiday_profile_assignments
                    (employee_id, profile_id, effective_from, effective_until, is_active, notes)
                VALUES (?, ?, ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    profile_id = VALUES(profile_id),
                    effective_until = VALUES(effective_until),
                    is_active = VALUES(is_active),
                    notes = VALUES(notes),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $assignmentStmt->execute([
                $entry['employee_id'],
                $targetProfileId,
                $effectiveFrom,
                $newEffectiveUntil,
                'Imported from Holidays Excel upload',
            ]);

            $changedEmployeeIds[] = $entry['employee_id'];
            $result['updated'][] = [
                'employee_id' => $entry['employee_id'],
                'employee_name' => $entry['employee_name'],
                'from_profile' => $currentProfile['profile_name'] ?? 'Company Holidays',
                'to_profile' => $entry['target_profile']['profile_name'],
                'sheet' => $entry['sheet'],
            ];
        }

        $result['matched'] = count($finalEntries);

        if (!empty($changedEmployeeIds)) {
            $rebuilder = new ScheduleCacheRebuilder($pdo);
            $result['cache'] = $rebuilder->rebuildForEmployees(
                $changedEmployeeIds,
                $result['cache_start'],
                $result['cache_end'],
                true
            );
        }

        if ($commitChanges) {
            $pdo->commit();
        } else {
            $pdo->rollBack();
        }
        $result['success'] = true;
        $result['message'] = $commitChanges
            ? 'Holiday profile import completed.'
            : 'Holiday profile import dry run completed.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $result;
}

$requiredTables = [
    'employee_holiday_profiles',
    'employee_holiday_profile_assignments',
    'employee_holiday_profile_holidays',
    'employee_daily_schedule_cache',
];
$missingTables = array_values(array_filter($requiredTables, static fn($table) => !holidayTableExists($pdo, $table)));
$importResult = null;
$importError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_holidays') {
    try {
        if (demo_is_admin_mode()) {
            throw new RuntimeException('Holiday imports are disabled in admin demo mode.');
        }
        if (!empty($missingTables)) {
            throw new RuntimeException('Holiday profiles are not ready. Run the holiday profile seeder in profiles-only mode first.');
        }
        $importResult = holidayProcessImport($pdo, $profileSheetMap);
    } catch (Throwable $e) {
        $importError = $e->getMessage();
    }
}

try {
    $profileStats = empty($missingTables) ? holidayLoadProfileStats($pdo, $profileSheetMap) : [];
} catch (Throwable $e) {
    $profileStats = [];
}

function renderHolidayIssueList(array $items, string $emptyText): void
{
    if (empty($items)) {
        echo '<div class="holiday-empty-state">';
        echo '<i class="fas fa-check-circle" aria-hidden="true"></i>';
        echo '<p>' . htmlspecialchars($emptyText) . '</p>';
        echo '</div>';
        return;
    }

    echo '<div class="holiday-list">';
    foreach ($items as $item) {
        echo '<div class="holiday-list-item">';
        echo '<div class="holiday-list-avatar"><i class="fas fa-user" aria-hidden="true"></i></div>';
        echo '<div class="min-w-0">';
        echo '<div class="font-semibold text-slate-800">' . htmlspecialchars($item['name'] ?? $item['employee_name'] ?? 'Unknown') . '</div>';
        if (isset($item['sheet'])) {
            echo '<div class="holiday-list-meta">' . htmlspecialchars($item['sheet'] . (isset($item['row']) ? ' row ' . $item['row'] : '')) . '</div>';
        }
        if (!empty($item['locations'])) {
            echo '<div class="holiday-list-meta">' . htmlspecialchars(implode(', ', $item['locations'])) . '</div>';
        }
        if (!empty($item['matches'])) {
            echo '<div class="holiday-list-meta">' . htmlspecialchars(implode(', ', $item['matches'])) . '</div>';
        }
        echo '</div></div>';
    }
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidays Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --holiday-navy: #0f172a;
            --holiday-blue: #2563eb;
            --holiday-blue-soft: #eff6ff;
            --holiday-border: #e2e8f0;
            --holiday-muted: #64748b;
        }

        .holiday-page {
            background:
                radial-gradient(circle at 78% 0%, rgba(37, 99, 235, .08), transparent 28rem),
                #f8fafc;
        }

        .holiday-shell {
            width: min(100%, 1440px);
            margin-inline: auto;
        }

        .holiday-card {
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 30px rgba(15, 23, 42, .035);
        }

        .holiday-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            color: #2563eb;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .holiday-profile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem;
        }

        .holiday-profile-card {
            position: relative;
            min-height: 5.65rem;
            overflow: hidden;
            border: 1px solid var(--holiday-border);
            border-radius: .8rem;
            background: #fff;
            padding: .8rem .85rem;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .holiday-profile-card::before {
            position: absolute;
            inset: 0 auto 0 0;
            width: 3px;
            background: var(--profile-accent, #2563eb);
            content: "";
        }

        .holiday-profile-card:hover {
            transform: translateY(-2px);
            border-color: #bfdbfe;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .09);
        }

        .holiday-upload-zone {
            display: flex;
            min-height: 3rem;
            cursor: pointer;
            align-items: center;
            gap: .75rem;
            border: 1.5px dashed #bfdbfe;
            border-radius: .9rem;
            background: #f8fbff;
            padding: .55rem .75rem;
            transition: border-color .18s ease, background .18s ease;
        }

        .holiday-upload-zone:hover,
        .holiday-upload-zone:focus-within {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .holiday-upload-icon {
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: .8rem;
            background: #dbeafe;
            color: #2563eb;
        }

        .holiday-field {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: #fff;
            padding: .78rem .9rem;
            color: #0f172a;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .holiday-field:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .holiday-primary-button {
            display: inline-flex;
            min-height: 3rem;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            border-radius: .75rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            padding: .78rem 1.25rem;
            color: #fff;
            font-weight: 750;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .2);
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
        }

        .holiday-primary-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, .25);
            filter: brightness(1.03);
        }

        .holiday-primary-button:disabled {
            cursor: not-allowed;
            box-shadow: none;
            filter: grayscale(.25);
            opacity: .5;
            transform: none;
        }

        .holiday-summary-card {
            border: 1px solid var(--holiday-border);
            border-radius: .8rem;
            background: #fff;
            padding: .9rem;
        }

        .holiday-summary-icon {
            display: grid;
            width: 1.9rem;
            height: 1.9rem;
            place-items: center;
            border-radius: .55rem;
            background: var(--summary-bg);
            color: var(--summary-color);
            font-size: .78rem;
        }

        .holiday-result-panel {
            min-height: 12rem;
            overflow: hidden;
            border: 1px solid var(--holiday-border);
            border-radius: .9rem;
            background: #fff;
        }

        .holiday-result-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
            padding: .9rem 1rem;
        }

        .holiday-count {
            display: inline-flex;
            min-width: 1.6rem;
            height: 1.6rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f1f5f9;
            padding-inline: .45rem;
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
        }

        .holiday-list {
            max-height: 14rem;
            overflow-y: auto;
            padding: 0 .95rem;
        }

        .holiday-list-item {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            border-bottom: 1px solid #f1f5f9;
            padding: .75rem 0;
            font-size: .82rem;
        }

        .holiday-list-item:last-child {
            border-bottom: 0;
        }

        .holiday-list-avatar {
            display: grid;
            width: 1.9rem;
            height: 1.9rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: .68rem;
        }

        .holiday-list-meta {
            margin-top: .12rem;
            color: #64748b;
            font-size: .72rem;
        }

        .holiday-empty-state {
            display: flex;
            min-height: 8.5rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            padding: 1.25rem;
            text-align: center;
            color: #94a3b8;
            font-size: .8rem;
        }

        .holiday-empty-state i {
            color: #86efac;
            font-size: 1.25rem;
        }

        .holiday-table-row:hover {
            background: #f8fafc;
        }

        @media (max-width: 1100px) {
            .holiday-profile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .holiday-profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="holiday-page">
<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('header.php'); ?>

        <main class="flex-1 overflow-y-auto px-4 py-5 sm:px-6 lg:px-8">
            <div class="holiday-shell space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="holiday-eyebrow">
                            <i class="fas fa-calendar-day" aria-hidden="true"></i>
                            Workforce settings
                        </div>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Holiday management</h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-500">
                            Keep employee holiday calendars aligned with their assigned regional profile.
                        </p>
                    </div>
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <?= count($profileStats) ?> profiles available
                    </div>
                </div>

                <?php if ($importError): ?>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm" role="alert">
                        <div class="flex items-start gap-3">
                            <div class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-red-100 text-red-600">
                                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                            </div>
                            <div>
                                <div class="font-bold">Import failed</div>
                                <p class="mt-0.5 text-sm text-red-700"><?= htmlspecialchars($importError) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($missingTables)): ?>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm" role="alert">
                        <div class="flex items-start gap-3">
                            <div class="grid h-9 w-9 flex-none place-items-center rounded-lg bg-amber-100 text-amber-600">
                                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            </div>
                            <div>
                                <div class="font-bold">Holiday profile setup is incomplete</div>
                                <p class="mt-0.5 text-sm text-amber-800">
                                    Missing tables: <?= htmlspecialchars(implode(', ', $missingTables)) ?>.
                                    Run <span class="font-mono text-xs">setup_employee_holiday_profiles.php?confirm=yes&amp;profiles_only=yes</span> before importing.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <section class="holiday-card overflow-hidden">
                    <div class="grid lg:grid-cols-[minmax(0,0.9fr)_minmax(520px,1.1fr)]">
                        <div class="flex flex-col justify-between bg-slate-950 p-6 text-white sm:p-7">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-blue-100 ring-1 ring-inset ring-white/10">
                                    <i class="fas fa-file-excel" aria-hidden="true"></i>
                                    Excel profile import
                                </span>
                                <h2 class="mt-4 text-2xl font-extrabold tracking-tight">Assign observed holidays by region</h2>
                                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">
                                    Upload the team workbook and choose when assignments take effect. Employees are matched from the Name column on each supported sheet.
                                </p>
                            </div>
                            <div class="mt-6 grid grid-cols-3 gap-3 border-t border-white/10 pt-5 text-xs text-slate-300">
                                <div>
                                    <div class="font-bold text-white">6 sheets</div>
                                    <div class="mt-0.5">Regional profiles</div>
                                </div>
                                <div>
                                    <div class="font-bold text-white">10 MB</div>
                                    <div class="mt-0.5">Maximum file size</div>
                                </div>
                                <div>
                                    <div class="font-bold text-white">12 months</div>
                                    <div class="mt-0.5">Cache coverage</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-slate-900">Current profile coverage</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">Active employees assigned to each holiday calendar.</p>
                                </div>
                                <i class="fas fa-chart-pie text-slate-300" aria-hidden="true"></i>
                            </div>
                            <div class="holiday-profile-grid">
                                <?php
                                    $profileAccents = [
                                        'PH' => '#2563eb',
                                        'SA' => '#0f766e',
                                        'WA' => '#7c3aed',
                                        'NSW' => '#db2777',
                                        'VIC' => '#ea580c',
                                        'QLD' => '#16a34a',
                                        'Default' => '#64748b',
                                    ];
                                ?>
                                <?php foreach ($profileStats as $stat): ?>
                                    <div class="holiday-profile-card" style="--profile-accent: <?= htmlspecialchars($profileAccents[$stat['sheet']] ?? '#2563eb') ?>">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-xs font-extrabold tracking-wide text-slate-500"><?= htmlspecialchars($stat['sheet']) ?></span>
                                            <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Employees</span>
                                        </div>
                                        <div class="mt-1 text-2xl font-black tracking-tight text-slate-900"><?= (int)$stat['employee_count'] ?></div>
                                        <div class="mt-0.5 truncate text-[11px] font-medium text-slate-500" title="<?= htmlspecialchars($stat['profile_name']) ?>">
                                            <?= htmlspecialchars($stat['profile_name']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 border-t border-slate-200 bg-white p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_230px_auto] lg:items-end">
                        <input type="hidden" name="action" value="import_holidays">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" for="holiday_file">Excel workbook</label>
                            <label class="holiday-upload-zone" for="holiday_file">
                                <span class="holiday-upload-icon">
                                    <i class="fas fa-cloud-arrow-up" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span id="holiday-file-name" class="block truncate text-sm font-bold text-slate-800">Choose an Excel file</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">.xlsx or .xls, up to 10 MB</span>
                                </span>
                            </label>
                            <input
                                id="holiday_file"
                                type="file"
                                name="holiday_file"
                                accept=".xlsx,.xls"
                                required
                                <?= !empty($missingTables) ? 'disabled' : '' ?>
                                class="sr-only"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700" for="effective_from">Effective from</label>
                            <input
                                id="effective_from"
                                type="date"
                                name="effective_from"
                                value="<?= htmlspecialchars(date('Y-m-d')) ?>"
                                required
                                <?= !empty($missingTables) ? 'disabled' : '' ?>
                                class="holiday-field"
                            >
                        </div>
                        <button
                            type="submit"
                            <?= !empty($missingTables) ? 'disabled' : '' ?>
                            class="holiday-primary-button"
                        >
                            <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                            Import profiles
                        </button>
                    </form>
                </section>

                <?php if ($importResult): ?>
                    <section class="holiday-card overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-emerald-100 bg-emerald-50/70 p-5 sm:p-6">
                            <div class="flex items-start gap-3">
                                <div class="grid h-11 w-11 flex-none place-items-center rounded-xl bg-emerald-100 text-emerald-600">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <div class="holiday-eyebrow !text-emerald-700">Latest import</div>
                                    <h3 class="mt-0.5 text-xl font-extrabold text-slate-900"><?= htmlspecialchars($importResult['message']) ?></h3>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Effective <?= htmlspecialchars(date('M j, Y', strtotime($importResult['effective_from']))) ?>
                                        <span class="mx-1.5 text-slate-300">|</span>
                                        Cache <?= htmlspecialchars(date('M j, Y', strtotime($importResult['cache_start']))) ?> to <?= htmlspecialchars(date('M j, Y', strtotime($importResult['cache_end']))) ?>
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-extrabold text-emerald-700 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Complete
                            </span>
                        </div>

                        <div class="p-5 sm:p-6">
                            <?php
                                $summaryCards = [
                                    ['Matched', $importResult['matched'], 'fa-link', '#2563eb', '#dbeafe'],
                                    ['Updated', count($importResult['updated']), 'fa-arrows-rotate', '#16a34a', '#dcfce7'],
                                    ['Unchanged', count($importResult['unchanged']), 'fa-equals', '#475569', '#f1f5f9'],
                                    ['Unmatched', count($importResult['unmatched']), 'fa-user-slash', '#dc2626', '#fee2e2'],
                                    ['Duplicates', count($importResult['duplicates']), 'fa-clone', '#ca8a04', '#fef9c3'],
                                    ['Ambiguous', count($importResult['ambiguous']), 'fa-code-branch', '#9333ea', '#f3e8ff'],
                                    ['Sheet Issues', count($importResult['missing_sheets']) + count($importResult['missing_profiles']), 'fa-table-cells', '#ea580c', '#ffedd5'],
                                    ['Cache Days', $importResult['cache']['days'] ?? 0, 'fa-calendar-check', '#4f46e5', '#e0e7ff'],
                                ];
                            ?>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-8">
                                <?php foreach ($summaryCards as [$label, $value, $icon, $color, $background]): ?>
                                    <div class="holiday-summary-card" style="--summary-color: <?= htmlspecialchars($color) ?>; --summary-bg: <?= htmlspecialchars($background) ?>">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="holiday-summary-icon">
                                                <i class="fas <?= htmlspecialchars($icon) ?>" aria-hidden="true"></i>
                                            </div>
                                            <div class="text-2xl font-black tracking-tight" style="color: <?= htmlspecialchars($color) ?>"><?= (int)$value ?></div>
                                        </div>
                                        <div class="mt-2 text-[11px] font-extrabold uppercase tracking-wide text-slate-500"><?= htmlspecialchars($label) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="holiday-result-panel">
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user-check text-emerald-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Updated employees</h4>
                                        </div>
                                        <span class="holiday-count"><?= count($importResult['updated']) ?></span>
                                    </div>
                                    <?php if (empty($importResult['updated'])): ?>
                                        <div class="holiday-empty-state">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            <p>No employee profiles changed.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="holiday-list">
                                            <?php foreach ($importResult['updated'] as $item): ?>
                                                <div class="holiday-list-item">
                                                    <div class="holiday-list-avatar"><i class="fas fa-user" aria-hidden="true"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="font-semibold text-slate-800">
                                                            <?= htmlspecialchars($item['employee_name']) ?>
                                                            <span class="font-normal text-slate-400">#<?= (int)$item['employee_id'] ?></span>
                                                        </div>
                                                        <div class="holiday-list-meta">
                                                            <?= htmlspecialchars($item['from_profile']) ?> <i class="fas fa-arrow-right mx-1 text-[9px]"></i> <?= htmlspecialchars($item['to_profile']) ?>
                                                            via <?= htmlspecialchars($item['sheet']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="holiday-result-panel">
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user-xmark text-red-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Unmatched names</h4>
                                        </div>
                                        <span class="holiday-count"><?= count($importResult['unmatched']) ?></span>
                                    </div>
                                    <?php renderHolidayIssueList($importResult['unmatched'], 'All workbook names matched employees.'); ?>
                                </div>

                                <div class="holiday-result-panel">
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-clone text-amber-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Duplicates</h4>
                                        </div>
                                        <span class="holiday-count"><?= count($importResult['duplicates']) ?></span>
                                    </div>
                                    <?php renderHolidayIssueList($importResult['duplicates'], 'No duplicate workbook names found.'); ?>
                                </div>

                                <div class="holiday-result-panel">
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-code-branch text-purple-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Ambiguous matches</h4>
                                        </div>
                                        <span class="holiday-count"><?= count($importResult['ambiguous']) ?></span>
                                    </div>
                                    <?php renderHolidayIssueList($importResult['ambiguous'], 'No ambiguous employee matches found.'); ?>
                                </div>

                                <div class="holiday-result-panel">
                                    <?php $sheetIssueCount = count($importResult['missing_sheets']) + count($importResult['missing_profiles']); ?>
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-table-cells text-orange-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Workbook sheet issues</h4>
                                        </div>
                                        <span class="holiday-count"><?= $sheetIssueCount ?></span>
                                    </div>
                                    <?php if ($sheetIssueCount === 0): ?>
                                        <div class="holiday-empty-state">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            <p>All supported sheets and holiday profiles were available.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-4">
                                            <?php if (!empty($importResult['missing_sheets'])): ?>
                                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Missing sheets</p>
                                                <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars(implode(', ', $importResult['missing_sheets'])) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($importResult['missing_profiles'])): ?>
                                                <div class="mt-3">
                                                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Missing database profiles</p>
                                                    <?php renderHolidayIssueList($importResult['missing_profiles'], ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="holiday-result-panel">
                                    <div class="holiday-result-heading">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-user-clock text-slate-500" aria-hidden="true"></i>
                                            <h4 class="font-bold text-slate-900">Already assigned</h4>
                                        </div>
                                        <span class="holiday-count"><?= count($importResult['unchanged']) ?></span>
                                    </div>
                                    <?php if (empty($importResult['unchanged'])): ?>
                                        <div class="holiday-empty-state">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            <p>No matched employees already had the selected profile.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="holiday-list">
                                            <?php foreach ($importResult['unchanged'] as $item): ?>
                                                <div class="holiday-list-item">
                                                    <div class="holiday-list-avatar"><i class="fas fa-user" aria-hidden="true"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($item['employee_name']) ?></div>
                                                        <div class="holiday-list-meta"><?= htmlspecialchars($item['profile_name']) ?> via <?= htmlspecialchars($item['sheet']) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <details class="holiday-card group overflow-hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600">
                                <i class="fas fa-table-list" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900">Workbook sheet mapping</h3>
                                <p class="mt-0.5 text-xs text-slate-500">Reference the sheet names and database profile codes used during import.</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="overflow-x-auto border-t border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr class="border-b border-slate-200">
                                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Excel sheet</th>
                                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Holiday profile code</th>
                                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Import behavior</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php foreach ($profileSheetMap as $sheet => $profileCode): ?>
                                    <tr class="holiday-table-row">
                                        <td class="px-5 py-3">
                                            <span class="inline-flex min-w-12 justify-center rounded-md bg-blue-50 px-2 py-1 text-xs font-extrabold text-blue-700">
                                                <?= htmlspecialchars($sheet) ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-700"><?= htmlspecialchars($profileCode) ?></td>
                                        <td class="px-5 py-3 text-slate-500">Assign names from this sheet to the linked holiday profile.</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </main>
    </div>
</div>
<script>
    const holidayFileInput = document.getElementById('holiday_file');
    const holidayFileName = document.getElementById('holiday-file-name');

    holidayFileInput?.addEventListener('change', () => {
        const selectedFile = holidayFileInput.files?.[0];
        holidayFileName.textContent = selectedFile ? selectedFile.name : 'Choose an Excel file';
    });
</script>
</body>
</html>
