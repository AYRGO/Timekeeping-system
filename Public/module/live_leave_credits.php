<?php
// File: live_leave_credits.php
// Real-time monitoring version of leave credits with auto-refresh
in                <div class="bg-gradient-to-r from-blue-500 to-emerald-500 text-white rounded-xl p-4 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-xl mr-3"></i>
                <div>
                    <p class="font-semibold">Monthly Accrual System Active</p>
                    <p class="text-sm opacity-90">Sick Leave: +0.42 days • Vacation Leave: +1.25 days per month</p>
                </div>
            </div>/config/db.php');
$current_user_id = $_SESSION['employee']['id'] ?? null;

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
$leaveHistory = [];

if ($current_user_id) {
    // Fetch leave credits - only for main leave types
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation') ORDER BY leave_type");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recent leave updates for monitoring
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation') ORDER BY updated_at DESC LIMIT 10");
    $stmt->execute([$current_user_id, date('Y')]);
    $recentUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Live Leave Credits Monitor</title>
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
        
        .updating {
            background: linear-gradient(45deg, #f0f9ff, #e0f2fe, #f0f9ff);
            background-size: 400% 400%;
            animation: gradientShift 2s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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
                <p class="text-slate-600 mt-2">Monthly accrual monitoring • Updates at end of each month</p>
            </div>
            <div class="text-right">
                <div class="bg-slate-800 text-white rounded-xl px-6 py-4" id="countdownContainer">
                    <p class="text-sm font-medium" id="countdownLabel">Next Update In</p>
                    <p class="text-2xl font-bold counter" id="countdown">10</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" id="statusIndicator">
                            <i class="fas fa-circle mr-1 text-xs pulsing"></i>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Banner -->
    <div class="bg-gradient-to-r from-blue-500 to-emerald-500 text-white rounded-xl p-4 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-xl mr-3"></i>
                <div>
                    <p class="font-semibold">Accrual Test Mode Active</p>
                    <p class="text-sm opacity-90">Sick Leave: +0.42 days • Vacation Leave: +1.25 days per interval</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-90">Last Update</p>
                <p class="font-semibold" id="lastUpdate"><?= date('H:i:s') ?></p>
            </div>
        </div>
    </div>

    <!-- Leave Credits Cards -->
    <div id="leaveCreditsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <?php foreach ($credits as $row): 
            $type = $row['leave_type'];
            $balance = floatval($row['balance']);
            
            $config = $leave_config[$type] ?? [
                'label' => ucfirst(str_replace('_', ' ', $type)),
                'class' => 'bg-white border-slate-200',
                'color' => 'text-slate-700',
                'icon' => 'fas fa-calendar',
                'accent' => 'bg-slate-500'
            ];
        ?>
        <div class="<?= $config['class'] ?> border rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300" id="card-<?= $type ?>">
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
                    <span class="text-4xl font-bold text-slate-800 counter" id="balance-<?= $type ?>">
                        <?= number_format($balance, 2) ?>
                    </span>
                    <span class="text-sm text-slate-500 ml-2">days</span>
                </div>
                
                <div class="text-xs text-slate-600 bg-slate-100 px-4 py-2 rounded-full">
                    <i class="fas fa-clock mr-1"></i>
                    Updated: <span id="updated-<?= $type ?>"><?= date('H:i:s', strtotime($row['updated_at'])) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Statistics -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas fa-chart-line text-blue-600 mr-3"></i>
            Accrual Statistics
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Total Days</p>
                <p class="text-2xl font-bold text-blue-800 counter" id="totalDays"><?= number_format($totalDays, 2) ?></p>
            </div>
            <div class="bg-emerald-50 rounded-lg p-4">
                <p class="text-sm text-emerald-600 font-medium">Updates Count</p>
                <p class="text-2xl font-bold text-emerald-800 counter" id="updateCount">0</p>
            </div>
            <div class="bg-violet-50 rounded-lg p-4">
                <p class="text-sm text-violet-600 font-medium">Sick Rate</p>
                <p class="text-lg font-bold text-violet-800">+0.42/10s</p>
            </div>
            <div class="bg-rose-50 rounded-lg p-4">
                <p class="text-sm text-rose-600 font-medium">Vacation Rate</p>
                <p class="text-lg font-bold text-rose-800">+1.25/10s</p>
            </div>
        </div>
    </div>

    <!-- Control Panel -->
    <div class="bg-white shadow-lg rounded-xl p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
            <i class="fas fa-cogs text-slate-600 mr-3"></i>
            Control Panel
        </h3>
        <div class="flex flex-wrap gap-4">
            <button onclick="toggleAutoRefresh()" id="refreshToggle" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                <i class="fas fa-pause mr-2" id="toggleIcon"></i>
                <span id="toggleText">Pause Auto-Refresh</span>
            </button>
            <button onclick="manualRefresh()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                <i class="fas fa-sync-alt mr-2"></i>
                Manual Refresh
            </button>
            <button onclick="resetCountdown()" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                <i class="fas fa-redo mr-2"></i>
                Reset Timer
            </button>
            <button onclick="stopMonitoring()" id="stopButton" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                <i class="fas fa-stop mr-2"></i>
                Stop Monitoring
            </button>
        </div>
        
        <div class="mt-4 grid md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 rounded-lg">
                <p class="text-sm text-slate-600">
                    <i class="fas fa-terminal mr-2"></i>
                    <strong>To start the accrual system:</strong> Run 
                    <code class="bg-slate-200 px-2 py-1 rounded">php Public/cron/realtime_leave_accrual.php</code> 
                    in your terminal
                </p>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-600 font-semibold mb-2">
                    <i class="fas fa-keyboard mr-2"></i>
                    Keyboard Shortcuts:
                </p>
                <div class="text-xs text-blue-700 space-y-1">
                    <div><kbd class="bg-blue-200 px-2 py-1 rounded">Space</kbd> - Pause/Resume</div>
                    <div><kbd class="bg-blue-200 px-2 py-1 rounded">R</kbd> - Manual Refresh</div>
                    <div><kbd class="bg-blue-200 px-2 py-1 rounded">S</kbd> or <kbd class="bg-blue-200 px-2 py-1 rounded">Esc</kbd> - Stop</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let autoRefreshEnabled = true;
let countdownTimer = 10;
let updateCounter = 0;
let countdownInterval;
let refreshInterval;
let isPaused = false;
let isMonitoringStopped = false;

function startCountdown() {
    countdownInterval = setInterval(() => {
        if (!isPaused && !isMonitoringStopped) {
            countdownTimer--;
            document.getElementById('countdown').textContent = countdownTimer;
            
            if (countdownTimer <= 0) {
                if (autoRefreshEnabled) {
                    refreshLeaveCredits();
                }
                countdownTimer = 10;
            }
        } else if (isPaused) {
            document.getElementById('countdown').textContent = 'PAUSED';
        } else if (isMonitoringStopped) {
            document.getElementById('countdown').textContent = 'STOPPED';
        }
    }, 1000);
}

function refreshLeaveCredits() {
    // Add visual feedback
    const container = document.getElementById('leaveCreditsContainer');
    container.classList.add('updating');
    
    fetch('live_leave_credits_ajax.php?user_id=<?= $current_user_id ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCounter++;
                document.getElementById('updateCount').textContent = updateCounter;
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                
                let totalDays = 0;
                data.credits.forEach(credit => {
                    const balance = parseFloat(credit.balance);
                    totalDays += balance;
                    
                    // Update balance display with animation
                    const balanceElement = document.getElementById(`balance-${credit.leave_type}`);
                    const updatedElement = document.getElementById(`updated-${credit.leave_type}`);
                    const cardElement = document.getElementById(`card-${credit.leave_type}`);
                    
                    if (balanceElement) {
                        // Flash effect for updated value
                        cardElement.style.background = 'linear-gradient(45deg, #ecfdf5, #f0fdf4)';
                        setTimeout(() => {
                            cardElement.style.background = '';
                        }, 2000);
                        
                        balanceElement.textContent = balance.toFixed(2);
                        if (updatedElement) {
                            updatedElement.textContent = new Date(credit.updated_at).toLocaleTimeString();
                        }
                    }
                });
                
                document.getElementById('totalDays').textContent = totalDays.toFixed(2);
            }
        })
        .catch(error => {
            console.error('Refresh error:', error);
        })
        .finally(() => {
            container.classList.remove('updating');
        });
}

function toggleAutoRefresh() {
    if (isMonitoringStopped) {
        // Restart monitoring
        isMonitoringStopped = false;
        isPaused = false;
        autoRefreshEnabled = true;
        countdownTimer = 10;
        updateStatusDisplays();
        return;
    }
    
    isPaused = !isPaused;
    
    if (!isPaused) {
        autoRefreshEnabled = true;
        if (countdownTimer <= 0) countdownTimer = 10;
    }
    
    updateStatusDisplays();
}

function updateStatusDisplays() {
    const button = document.getElementById('refreshToggle');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');
    const stopButton = document.getElementById('stopButton');
    const statusIndicator = document.getElementById('statusIndicator');
    const countdownLabel = document.getElementById('countdownLabel');
    const countdownContainer = document.getElementById('countdownContainer');
    
    if (isMonitoringStopped) {
        button.className = 'bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300';
        icon.className = 'fas fa-play mr-2';
        text.textContent = 'Start Monitoring';
        stopButton.disabled = true;
        stopButton.className = 'bg-gray-400 text-white px-6 py-3 rounded-lg font-semibold cursor-not-allowed';
        statusIndicator.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
        statusIndicator.innerHTML = '<i class="fas fa-stop-circle mr-1 text-xs"></i>Stopped';
        countdownLabel.textContent = 'Status';
        countdownContainer.className = 'bg-red-700 text-white rounded-xl px-6 py-4';
    } else if (isPaused) {
        button.className = 'bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300';
        icon.className = 'fas fa-play mr-2';
        text.textContent = 'Resume Auto-Refresh';
        stopButton.disabled = false;
        stopButton.className = 'bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300';
        statusIndicator.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800';
        statusIndicator.innerHTML = '<i class="fas fa-pause-circle mr-1 text-xs"></i>Paused';
        countdownLabel.textContent = 'Status';
        countdownContainer.className = 'bg-amber-600 text-white rounded-xl px-6 py-4';
    } else {
        button.className = 'bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300';
        icon.className = 'fas fa-pause mr-2';
        text.textContent = 'Pause Auto-Refresh';
        stopButton.disabled = false;
        stopButton.className = 'bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300';
        statusIndicator.className = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
        statusIndicator.innerHTML = '<i class="fas fa-circle mr-1 text-xs pulsing"></i>Active';
        countdownLabel.textContent = 'Next Update In';
        countdownContainer.className = 'bg-slate-800 text-white rounded-xl px-6 py-4';
    }
}

function manualRefresh() {
    if (!isMonitoringStopped) {
        refreshLeaveCredits();
        if (!isPaused) {
            resetCountdown();
        }
    }
}

function resetCountdown() {
    countdownTimer = 10;
    if (!isPaused && !isMonitoringStopped) {
        document.getElementById('countdown').textContent = countdownTimer;
    }
}

function stopMonitoring() {
    isMonitoringStopped = true;
    isPaused = false;
    autoRefreshEnabled = false;
    updateStatusDisplays();
    
    // Show confirmation
    const container = document.getElementById('leaveCreditsContainer');
    const existingAlert = document.getElementById('stoppedAlert');
    
    if (!existingAlert) {
        const alertDiv = document.createElement('div');
        alertDiv.id = 'stoppedAlert';
        alertDiv.className = 'bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center';
        alertDiv.innerHTML = `
            <i class="fas fa-stop-circle mr-3 text-lg"></i>
            <div>
                <strong>Monitoring Stopped</strong>
                <p class="text-sm">Auto-refresh has been disabled. Click "Start Monitoring" to resume.</p>
            </div>
        `;
        container.parentNode.insertBefore(alertDiv, container);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Space bar to toggle pause
    if (e.code === 'Space' && !e.ctrlKey && !e.altKey) {
        e.preventDefault();
        toggleAutoRefresh();
    }
    // 'R' key for manual refresh
    else if (e.code === 'KeyR' && !e.ctrlKey && !e.altKey) {
        e.preventDefault();
        manualRefresh();
    }
    // 'S' key to stop
    else if (e.code === 'KeyS' && !e.ctrlKey && !e.altKey) {
        e.preventDefault();
        if (!isMonitoringStopped) {
            stopMonitoring();
        }
    }
    // 'Escape' key to stop
    else if (e.code === 'Escape') {
        e.preventDefault();
        if (!isMonitoringStopped) {
            stopMonitoring();
        }
    }
});

// Initialize
startCountdown();
updateStatusDisplays();
</script>

</body>
</html>