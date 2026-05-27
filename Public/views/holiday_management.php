<?php

session_start();
include '../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/ScheduleCacheRebuilder.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidays - Under Development</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('header.php'); ?>

        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <i class="fas fa-screwdriver-wrench text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">Holidays is under development</h1>
                    <p class="mt-3 text-gray-600">
                        This module is temporarily locked while holiday profile management is being finalized.
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        No holiday imports or employee holiday profile updates can be made from this page right now.
                    </p>
                    <a href="admin_homepage.php" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 font-semibold text-white hover:bg-gray-800">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php
exit;

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
    $name = str_replace(['.', "\t", "\r", "\n"], ' ', $name);
    $name = preg_replace('/\s*,\s*/u', ', ', $name);
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = trim($name);

    return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
}

function holidayEmployeeLabel(array $employee): string
{
    return trim(($employee['fname'] ?? '') . ' ' . ($employee['lname'] ?? ''));
}

function holidayBuildEmployeeIndex(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, fname, lname, email, status FROM employees ORDER BY lname, fname");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $index = [];

    foreach ($employees as $employee) {
        $fname = trim((string)($employee['fname'] ?? ''));
        $lname = trim((string)($employee['lname'] ?? ''));
        $keys = array_unique(array_filter([
            holidayNormalizeName($lname . ', ' . $fname),
            holidayNormalizeName($fname . ' ' . $lname),
        ]));

        foreach ($keys as $key) {
            $index[$key][] = $employee;
        }
    }

    return $index;
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

function holidayGetCurrentProfile(PDO $pdo, int $employeeId): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.id AS assignment_id, a.profile_id, p.profile_code, p.profile_name
        FROM employee_holiday_profile_assignments a
        INNER JOIN employee_holiday_profiles p ON p.id = a.profile_id
        WHERE a.employee_id = ?
          AND a.is_active = 1
          AND p.is_active = 1
        ORDER BY a.effective_from DESC, a.id DESC
        LIMIT 1
    ");
    $stmt->execute([$employeeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function holidayLoadProfileStats(PDO $pdo, array $profileSheetMap): array
{
    $stats = [];
    $totalActive = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE LOWER(status) = 'active'")->fetchColumn();
    $assignedActive = 0;

    foreach ($profileSheetMap as $sheet => $profileCode) {
        $stmt = $pdo->prepare("
            SELECT p.profile_name, COUNT(DISTINCT e.id) AS employee_count
            FROM employee_holiday_profiles p
            LEFT JOIN employee_holiday_profile_assignments a
                ON a.profile_id = p.id
               AND a.is_active = 1
            LEFT JOIN employees e
                ON e.id = a.employee_id
               AND LOWER(e.status) = 'active'
            WHERE p.profile_code = ?
            GROUP BY p.id, p.profile_name
        ");
        $stmt->execute([$profileCode]);
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

function holidayProcessImport(PDO $pdo, array $profileSheetMap): array
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
        'cache' => ['employees' => 0, 'days' => 0],
        'effective_from' => $_POST['effective_from'] ?? date('Y-m-d'),
        'cache_start' => date('Y-m-d'),
        'cache_end' => date('Y-m-d', strtotime('+6 months')),
    ];

    $effectiveDate = DateTime::createFromFormat('Y-m-d', $_POST['effective_from'] ?? '');
    if (!$effectiveDate) {
        throw new RuntimeException('Please choose a valid effective date.');
    }
    $effectiveFrom = $effectiveDate->format('Y-m-d');
    $effectiveUntil = $effectiveDate->modify('-1 day')->format('Y-m-d');
    $result['effective_from'] = $effectiveFrom;

    if (empty($_FILES['holiday_file']) || ($_FILES['holiday_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please upload a valid Excel file.');
    }

    $originalName = $_FILES['holiday_file']['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('Only .xlsx and .xls files are supported.');
    }

    $profiles = holidayLoadProfiles($pdo);
    $employeeIndex = holidayBuildEmployeeIndex($pdo);
    $spreadsheet = IOFactory::load($_FILES['holiday_file']['tmp_name']);
    $rows = [];
    $occurrences = [];

    foreach ($profileSheetMap as $sheetName => $profileCode) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (!$sheet) {
            continue;
        }

        $nameColumn = 1;
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumn; $col++) {
            $headerCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            if (holidayNormalizeName((string)$sheet->getCell($headerCoordinate)->getValue()) === 'name') {
                $nameColumn = $col;
                break;
            }
        }

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
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

        $matches = $employeeIndex[$row['normalized']] ?? [];
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
            $currentProfile = holidayGetCurrentProfile($pdo, $entry['employee_id']);
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
                SET is_active = 0,
                    effective_until = ?,
                    updated_at = NOW()
                WHERE employee_id = ?
                  AND is_active = 1
            ");
            $deactivateStmt->execute([$effectiveUntil, $entry['employee_id']]);

            $assignmentStmt = $pdo->prepare("
                INSERT INTO employee_holiday_profile_assignments
                    (employee_id, profile_id, effective_from, effective_until, is_active, notes)
                VALUES (?, ?, ?, NULL, 1, ?)
                ON DUPLICATE KEY UPDATE
                    effective_until = VALUES(effective_until),
                    is_active = VALUES(is_active),
                    notes = VALUES(notes),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $assignmentStmt->execute([
                $entry['employee_id'],
                $targetProfileId,
                $effectiveFrom,
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
                $result['cache_end']
            );
        }

        $pdo->commit();
        $result['success'] = true;
        $result['message'] = 'Holiday profile import completed.';
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
    'employee_daily_schedule_cache',
];
$missingTables = array_values(array_filter($requiredTables, static fn($table) => !holidayTableExists($pdo, $table)));
$importResult = null;
$importError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_holidays') {
    try {
        if (!empty($missingTables)) {
            throw new RuntimeException('Holiday profile tables are not ready. Please run the holiday profile migration first.');
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
        echo '<p class="text-sm text-gray-500">' . htmlspecialchars($emptyText) . '</p>';
        return;
    }

    echo '<div class="max-h-56 overflow-y-auto divide-y divide-gray-100">';
    foreach ($items as $item) {
        echo '<div class="py-2 text-sm">';
        echo '<div class="font-semibold text-gray-800">' . htmlspecialchars($item['name'] ?? $item['employee_name'] ?? 'Unknown') . '</div>';
        if (isset($item['sheet'])) {
            echo '<div class="text-xs text-gray-500">' . htmlspecialchars($item['sheet'] . (isset($item['row']) ? ' row ' . $item['row'] : '')) . '</div>';
        }
        if (!empty($item['locations'])) {
            echo '<div class="text-xs text-gray-500">' . htmlspecialchars(implode(', ', $item['locations'])) . '</div>';
        }
        if (!empty($item['matches'])) {
            echo '<div class="text-xs text-gray-500">' . htmlspecialchars(implode(', ', $item['matches'])) . '</div>';
        }
        echo '</div>';
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
</head>
<body class="bg-gray-100">
<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('header.php'); ?>

        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-7xl mx-auto space-y-6">
                <?php if ($importError): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                        <div class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-circle-exclamation"></i>
                            Import failed
                        </div>
                        <p class="mt-1 text-sm"><?= htmlspecialchars($importError) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($missingTables)): ?>
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
                        <div class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-triangle-exclamation"></i>
                            Holiday profile setup is incomplete
                        </div>
                        <p class="mt-1 text-sm">
                            Missing tables: <?= htmlspecialchars(implode(', ', $missingTables)) ?>.
                            Run <span class="font-mono">Database/create_employee_holiday_profiles.sql</span> before importing.
                        </p>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Observed Holidays Import</h2>
                            <p class="text-sm text-gray-500 mt-1">
                                Upload the team members workbook to update employee holiday profiles by sheet.
                            </p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                            <?php foreach ($profileStats as $stat): ?>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                    <div class="text-xs font-bold text-blue-600"><?= htmlspecialchars($stat['sheet']) ?></div>
                                    <div class="text-xl font-extrabold text-gray-900"><?= (int)$stat['employee_count'] ?></div>
                                    <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($stat['profile_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="mt-6 grid grid-cols-1 lg:grid-cols-[1fr_220px_auto] gap-4 items-end">
                        <input type="hidden" name="action" value="import_holidays">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Excel file</label>
                            <input
                                type="file"
                                name="holiday_file"
                                accept=".xlsx,.xls"
                                required
                                <?= !empty($missingTables) ? 'disabled' : '' ?>
                                class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <p class="text-xs text-gray-500 mt-2">Expected sheets: PH, SA, WA, NSW, VIC, QLD with a Name column.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Effective from</label>
                            <input
                                type="date"
                                name="effective_from"
                                value="<?= htmlspecialchars(date('Y-m-d')) ?>"
                                required
                                <?= !empty($missingTables) ? 'disabled' : '' ?>
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <button
                            type="submit"
                            <?= !empty($missingTables) ? 'disabled' : '' ?>
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i class="fas fa-upload"></i>
                            Import
                        </button>
                    </form>
                </section>

                <?php if ($importResult): ?>
                    <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($importResult['message']) ?></h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Effective <?= htmlspecialchars($importResult['effective_from']) ?>.
                                    Cache rebuilt from <?= htmlspecialchars($importResult['cache_start']) ?> to <?= htmlspecialchars($importResult['cache_end']) ?>.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">
                                <i class="fas fa-check-circle mr-2"></i>Complete
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mt-6">
                            <?php
                                $summaryCards = [
                                    ['Matched', $importResult['matched'], 'text-blue-600'],
                                    ['Updated', count($importResult['updated']), 'text-green-600'],
                                    ['Unchanged', count($importResult['unchanged']), 'text-gray-600'],
                                    ['Unmatched', count($importResult['unmatched']), 'text-red-600'],
                                    ['Duplicates', count($importResult['duplicates']), 'text-yellow-600'],
                                    ['Ambiguous', count($importResult['ambiguous']), 'text-purple-600'],
                                    ['Cache Days', $importResult['cache']['days'] ?? 0, 'text-indigo-600'],
                                ];
                            ?>
                            <?php foreach ($summaryCards as [$label, $value, $colorClass]): ?>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="text-xs font-bold uppercase text-gray-500"><?= htmlspecialchars($label) ?></div>
                                    <div class="text-2xl font-extrabold <?= htmlspecialchars($colorClass) ?>"><?= (int)$value ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-900 mb-3">Updated Employees</h4>
                                <?php if (empty($importResult['updated'])): ?>
                                    <p class="text-sm text-gray-500">No employee profiles changed.</p>
                                <?php else: ?>
                                    <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                                        <?php foreach ($importResult['updated'] as $item): ?>
                                            <div class="py-2 text-sm">
                                                <div class="font-semibold text-gray-800">
                                                    <?= htmlspecialchars($item['employee_name']) ?> #<?= (int)$item['employee_id'] ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?= htmlspecialchars($item['from_profile']) ?> to <?= htmlspecialchars($item['to_profile']) ?> via <?= htmlspecialchars($item['sheet']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-900 mb-3">Unmatched Names</h4>
                                <?php renderHolidayIssueList($importResult['unmatched'], 'All workbook names matched employees.'); ?>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-900 mb-3">Duplicates</h4>
                                <?php renderHolidayIssueList($importResult['duplicates'], 'No duplicate workbook names found.'); ?>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <h4 class="font-bold text-gray-900 mb-3">Ambiguous Matches</h4>
                                <?php renderHolidayIssueList($importResult['ambiguous'], 'No ambiguous employee matches found.'); ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Workbook Sheet Mapping</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600">Excel Sheet</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600">Holiday Profile Code</th>
                                    <th class="px-4 py-3 text-left font-bold text-gray-600">Behavior</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($profileSheetMap as $sheet => $profileCode): ?>
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($sheet) ?></td>
                                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($profileCode) ?></td>
                                        <td class="px-4 py-3 text-gray-500">Names in this sheet will be assigned to this profile.</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
