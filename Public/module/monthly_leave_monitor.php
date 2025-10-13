<?php
// File: monthly_leave_monitor.php
// Monthly leave credits monitoring interface
include('../config/db.php');
$current_user_id = $_SESSION['employee']['id'] ?? null;

// Get current accrual mode from database
$currentMode = 'testing'; // Default to testing
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'accrual_mode'");
    $stmt->execute();
    $modeResult = $stmt->fetchColumn();
    if ($modeResult) {
        $currentMode = $modeResult;
    }
} catch (Exception $e) {
    // If table doesn't exist, create it with default testing mode
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('accrual_mode', 'testing')");
        $stmt->execute();
    } catch (Exception $e2) {
        // Ignore if we can't create the table
    }
}

$leave_config = [
    'sick' => [
        'label' => 'Sick Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-thermometer-half',
        'accent' => 'bg-blue-500'
    ],
    'vacation' => [
        'label' => 'Vacation Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-umbrella-beach',
        'accent' => 'bg-emerald-500'
    ],
];

$credits = [];
$nextAccrualInfo = [];

if ($current_user_id) {
    // Fetch leave credits
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation') ORDER BY leave_type");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate next accrual date based on current mode
$currentDate = new DateTime();
if ($currentMode === 'production') {
    // Production mode - end of month
    $lastDayOfMonth = new DateTime($currentDate->format('Y-m-t'));
    $daysUntilAccrual = $currentDate->diff($lastDayOfMonth)->days;
    $nextAccrualDate = $lastDayOfMonth->format('F j, Y');
    $timeUnit = 'days';
} else {
    // Testing mode - every 10 seconds
    $nextAccrualTime = new DateTime();
    $nextAccrualTime->add(new DateInterval('PT10S'));
    $daysUntilAccrual = 10;
    $nextAccrualDate = $nextAccrualTime->format('H:i:s');
    $timeUnit = 'seconds';
}

// Calculate total available days
$totalDays = 0;
foreach ($credits as $row) {
    $totalDays += floatval($row['balance']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Leave Credits Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pulsing {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .counter {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white shadow-lg rounded-xl mb-8 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 flex items-center">
                    <div class="bg-emerald-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-calendar-alt text-emerald-600 text-xl pulsing"></i>
                    </div>
                    Monthly Leave Credits Monitor
                </h1>
                <p class="text-slate-600 mt-2"><?= strtoupper($currentMode) ?> MODE • <?= $currentMode === 'production' ? 'Processes at end of each month' : 'Processes every 10 seconds' ?></p>
            </div>
            <div class="text-right">
                <div class="<?= $currentMode === 'production' ? 'bg-slate-800' : 'bg-red-600' ?> text-white rounded-xl px-6 py-4">
                    <p class="text-sm font-medium"><?= $currentMode === 'production' ? 'Days Until Next Accrual' : 'Next Accrual In' ?></p>
                    <p class="text-3xl font-bold counter" id="countdown"><?= $daysUntilAccrual ?></p>
                    <p class="text-xs opacity-80"><?= $timeUnit ?> (<?= strtoupper($currentMode) ?>)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="bg-gradient-to-r <?= $currentMode === 'production' ? 'from-slate-600 to-slate-800' : 'from-red-500 to-orange-500' ?> text-white rounded-xl p-4 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas <?= $currentMode === 'production' ? 'fa-calendar-check' : 'fa-flask' ?> text-xl mr-3"></i>
                <div>
                    <p class="font-semibold"><?= strtoupper($currentMode) ?> MODE<?= $currentMode === 'production' ? ' - Monthly Accrual' : ' - 10 Second Accrual' ?></p>
                    <p class="text-sm opacity-90">Sick Leave: +0.42 days • Vacation Leave: +1.25 days per <?= $currentMode === 'production' ? 'month' : 'cycle' ?></p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-90"><?= ucfirst($currentMode) ?> Mode</p>
                <p class="font-semibold"><?= $currentMode === 'production' ? 'End of Month' : 'Every 10 Seconds' ?></p>
            </div>
        </div>
    </div>

    <!-- Leave Credits Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <?php foreach ($credits as $row): 
            $type = $row['leave_type'];
            $balance = floatval($row['balance']);
            // Check if processed this month by looking at updated_at date
            $lastUpdated = new DateTime($row['updated_at']);
            $thisMonthStart = new DateTime(date('Y-m-01'));
            
            $config = $leave_config[$type] ?? [
                'label' => ucfirst(str_replace('_', ' ', $type)),
                'class' => 'bg-white border-slate-200',
                'color' => 'text-slate-700',
                'icon' => 'fas fa-calendar',
                'accent' => 'bg-slate-500'
            ];
            
            $isProcessedThisMonth = $lastUpdated >= $thisMonthStart;
        ?>
        <div class="<?= $config['class'] ?> border rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300">
            <!-- Icon and Title -->
            <div class="flex items-center justify-center mb-6">
                <div class="<?= $config['accent'] ?> rounded-xl p-4 shadow-lg">
                    <i class="<?= $config['icon'] ?> text-white text-2xl"></i>
                </div>
            </div>
            
            <h3 class="font-bold <?= $config['color'] ?> text-lg mb-6 text-center">
                <?= $config['label'] ?>
            </h3>
            
            <!-- Balance Display -->
            <div class="text-center">
                <div class="mb-4">
                    <span class="text-4xl font-bold text-slate-800 counter">
                        <?= number_format($balance, 2) ?>
                    </span>
                    <span class="text-sm text-slate-500 ml-2">days</span>
                </div>
                
                <div class="flex flex-col gap-2">
                    <div class="text-xs text-slate-600 bg-slate-100 px-4 py-2 rounded-full">
                        <i class="fas fa-clock mr-1"></i>
                        Updated: <?= date('M j, Y H:i', strtotime($row['updated_at'])) ?>
                    </div>
                    
                    <?php if ($isProcessedThisMonth): ?>
                        <div class="text-xs text-emerald-700 bg-emerald-100 px-4 py-2 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i>
                            Processed for <?= date('F') ?>
                        </div>
                    <?php else: ?>
                        <div class="text-xs text-amber-700 bg-amber-100 px-4 py-2 rounded-full">
                            <i class="fas fa-hourglass-half mr-1"></i>
                            Pending for <?= date('F') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Schedule Display -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas <?= $currentMode === 'production' ? 'fa-calendar-week text-blue-600' : 'fa-stopwatch text-red-600' ?> mr-3"></i>
            <?= $currentMode === 'production' ? 'Monthly Accrual Schedule' : 'Testing Accrual Schedule (Every 10 Seconds)' ?>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php if ($currentMode === 'production'): ?>
                <?php
                // Show monthly schedule
                for ($i = 0; $i < 6; $i++) {
                    $month = date('n') + $i;
                    $year = date('Y');
                    
                    if ($month > 12) {
                        $month -= 12;
                        $year++;
                    }
                    
                    $lastDay = date('t', mktime(0, 0, 0, $month, 1, $year));
                    $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                    $accrualDate = date('M j, Y', mktime(0, 0, 0, $month, $lastDay, $year));
                    
                    $isCurrent = ($month == date('n') && $year == date('Y'));
                    $cardClass = $isCurrent ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-slate-200';
                ?>
                <div class="<?= $cardClass ?> border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-slate-800"><?= $monthName ?> <?= $year ?></h4>
                            <p class="text-sm text-slate-600"><?= $accrualDate ?></p>
                        </div>
                        <div class="text-right">
                            <?php if ($isCurrent): ?>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                    Current
                                </span>
                            <?php else: ?>
                                <i class="fas fa-calendar text-slate-400"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <?php else: ?>
                <?php
                // Show testing schedule
                for ($i = 0; $i < 6; $i++) {
                    $cycleTime = date('H:i:s', time() + ($i * 10));
                    $cycleNum = $i + 1;
                    
                    $isCurrent = ($i === 0);
                    $cardClass = $isCurrent ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200';
                ?>
                <div class="<?= $cardClass ?> border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-slate-800">Cycle <?= $cycleNum ?></h4>
                            <p class="text-sm text-slate-600"><?= $cycleTime ?></p>
                        </div>
                        <div class="text-right">
                            <?php if ($isCurrent): ?>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                    Next
                                </span>
                            <?php else: ?>
                                <i class="fas fa-clock text-slate-400"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas fa-chart-line text-blue-600 mr-3"></i>
            Accrual Statistics
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Total Balance</p>
                <p class="text-2xl font-bold text-blue-800 counter"><?= number_format($totalDays, 2) ?></p>
            </div>
            <div class="bg-emerald-50 rounded-lg p-4">
                <p class="text-sm text-emerald-600 font-medium">Monthly Sick</p>
                <p class="text-2xl font-bold text-emerald-800">+0.42</p>
            </div>
            <div class="bg-violet-50 rounded-lg p-4">
                <p class="text-sm text-violet-600 font-medium">Monthly Vacation</p>
                <p class="text-2xl font-bold text-violet-800">+1.25</p>
            </div>
            <div class="bg-rose-50 rounded-lg p-4">
                <p class="text-sm text-rose-600 font-medium"><?= ucfirst($timeUnit) ?> Until</p>
                <p class="text-2xl font-bold text-rose-800 counter" id="stats-countdown"><?= $daysUntilAccrual ?></p>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="bg-white shadow-lg rounded-xl p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas fa-cogs text-slate-600 mr-3"></i>
            System Information
        </h3>
        
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="p-4 <?= $currentMode === 'testing' ? 'bg-red-50' : 'bg-slate-50' ?> rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold <?= $currentMode === 'testing' ? 'text-red-800' : 'text-slate-600' ?> flex items-center">
                        <i class="fas fa-flask mr-2"></i>
                        Testing System <?= $currentMode === 'testing' ? '(ACTIVE)' : '' ?>
                    </h4>
                    <?php if ($currentMode !== 'testing'): ?>
                        <button onclick="switchMode('testing')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                            Activate
                        </button>
                    <?php endif; ?>
                </div>
                <p class="text-sm <?= $currentMode === 'testing' ? 'text-red-600' : 'text-slate-600' ?> mb-3">
                    Processes leave accruals every 10 seconds for rapid testing:
                </p>
                <code class="<?= $currentMode === 'testing' ? 'bg-red-200' : 'bg-slate-200' ?> px-3 py-2 rounded text-xs block">
                    php Public/cron/hostinger_leave_accrual.php
                </code>
            </div>
            
            <div class="p-4 <?= $currentMode === 'production' ? 'bg-blue-50' : 'bg-slate-50' ?> rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold <?= $currentMode === 'production' ? 'text-blue-800' : 'text-slate-600' ?> flex items-center">
                        <i class="fas fa-server mr-2"></i>
                        Production System <?= $currentMode === 'production' ? '(ACTIVE)' : '' ?>
                    </h4>
                    <?php if ($currentMode !== 'production'): ?>
                        <button onclick="switchMode('production')" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                            Activate
                        </button>
                    <?php endif; ?>
                </div>
                <p class="text-sm <?= $currentMode === 'production' ? 'text-blue-600' : 'text-slate-600' ?> mb-3">
                    Processes leave accruals at the end of each month:
                </p>
                <code class="<?= $currentMode === 'production' ? 'bg-blue-200' : 'bg-slate-200' ?> px-3 py-2 rounded text-xs block">
                    0 * * * * php Public/cron/monthly_leave_accrual_scheduler.php
                </code>
            </div>
        </div>
        
        <!-- Mode Switch Status -->
        <div id="mode-status" class="hidden p-3 rounded-lg mb-4">
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                <span>Switching mode...</span>
            </div>
        </div>
        
        <div class="p-4 <?= $currentMode === 'testing' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200' ?> rounded-lg">
            <div class="flex items-start">
                <i class="fas <?= $currentMode === 'testing' ? 'fa-exclamation-triangle text-red-600' : 'fa-info-circle text-blue-600' ?> mr-3 mt-1"></i>
                <div>
                    <h4 class="font-semibold <?= $currentMode === 'testing' ? 'text-red-800' : 'text-blue-800' ?>">
                        <?= strtoupper($currentMode) ?> MODE ACTIVE
                    </h4>
                    <p class="<?= $currentMode === 'testing' ? 'text-red-700' : 'text-blue-700' ?> text-sm mt-1">
                        <?php if ($currentMode === 'testing'): ?>
                            System is currently running in testing mode, processing leave accruals every 10 seconds. 
                            Each cycle adds 0.42 days for sick leave and 1.25 days for vacation leave. 
                            This is for testing purposes only - switch to production mode for live use.
                        <?php else: ?>
                            System is running in production mode. Leave credits are automatically processed on the last day of each month. 
                            The system adds 0.42 days for sick leave and 1.25 days for vacation leave monthly, 
                            subject to maximum balance limits and carry-over rules.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mode switching function
function switchMode(newMode) {
    const statusDiv = document.getElementById('mode-status');
    statusDiv.className = 'p-3 rounded-lg mb-4 bg-blue-50 text-blue-800';
    statusDiv.style.display = 'block';
    
    fetch('toggle_accrual_mode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            mode: newMode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.className = 'p-3 rounded-lg mb-4 bg-green-50 text-green-800';
            statusDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-check-circle mr-2"></i><span>' + data.message + '</span></div>';
            
            // Refresh page after 2 seconds
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            statusDiv.className = 'p-3 rounded-lg mb-4 bg-red-50 text-red-800';
            statusDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i><span>Error: ' + data.error + '</span></div>';
        }
    })
    .catch(error => {
        statusDiv.className = 'p-3 rounded-lg mb-4 bg-red-50 text-red-800';
        statusDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i><span>Network error occurred</span></div>';
    });
}

// Auto-refresh based on current mode
const currentMode = '<?= $currentMode ?>';
if (currentMode === 'testing') {
    // Refresh more frequently in testing mode
    setTimeout(function() {
        location.reload();
    }, 15000); // 15 seconds
} else {
    // Refresh less frequently in production mode
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 minutes
}

// Update countdown based on current mode
let countdown = <?= $daysUntilAccrual ?>;
const timeUnit = '<?= $timeUnit ?>';

function updateCountdown() {
    if (timeUnit === 'seconds') {
        countdown--;
        if (countdown <= 0) {
            countdown = 10; // Reset to 10 seconds
            // Refresh page when accrual should happen
            location.reload();
        }
        
        // Update both countdown displays
        const mainCountdown = document.getElementById('countdown');
        const statsCountdown = document.getElementById('stats-countdown');
        
        if (mainCountdown) {
            mainCountdown.textContent = countdown;
        }
        if (statsCountdown) {
            statsCountdown.textContent = countdown;
        }
    }
    // For production mode (days), no need to update countdown every second
}

if (timeUnit === 'seconds') {
    setInterval(updateCountdown, 1000);
}
</script>

</body>
</html>