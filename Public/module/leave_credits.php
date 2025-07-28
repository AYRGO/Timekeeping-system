<?php

include('../config/db.php');
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
    'paternity' => [
        'label' => 'Paternity Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-baby',
        'accent' => 'bg-violet-500'
    ],
    'maternity' => [
        'label' => 'Maternity Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-heart',
        'accent' => 'bg-rose-500'
    ],
    'solo_parent' => [
        'label' => 'Solo Parent Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-user-friends',
        'accent' => 'bg-amber-500'
    ],
    'halfday' => [
        'label' => 'Half Day Vacation',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-clock',
        'accent' => 'bg-indigo-500'
    ],
    'halfday_sick' => [
        'label' => 'Half Day Sick',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-user-clock',
        'accent' => 'bg-cyan-500'
    ],
    'lwop' => [
        'label' => 'Leave Without Pay',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-ban',
        'accent' => 'bg-slate-500'
    ],
    'bereavement' => [
        'label' => 'Bereavement Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-dove',
        'accent' => 'bg-slate-600'
    ],
];

$credits = [];

if ($current_user_id) {
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ?");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate total available days
$totalDays = 0;
foreach ($credits as $row) {
    $totalDays += floatval($row['balance']);
}
?>

<div class="bg-white shadow-lg rounded-lg overflow-hidden border border-slate-200">
    <!-- Header Section -->
    <div class="bg-slate-50 border-b border-slate-200 px-6 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800 flex items-center">
                    <i class="fas fa-calendar-check mr-3 text-slate-600"></i>
                    Leave Credits <?= date('Y') ?>
                </h2>
                <p class="text-slate-600 mt-1">Monitor your available leave balances</p>
            </div>
            <div class="text-right">
                <div class="bg-white border border-slate-200 rounded-lg px-4 py-3 shadow-sm">
                    <p class="text-sm text-slate-500 font-medium">Total Available</p>
                    <p class="text-xl font-semibold text-slate-800"><?= number_format($totalDays, 1) ?> days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        <?php if (!empty($credits)): ?>
            <!-- Leave Types Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
                <?php foreach ($credits as $row): 
                    $type = $row['leave_type'];
                    $balance = floatval($row['balance']);
                    $carry = isset($row['carry_over']) ? floatval($row['carry_over']) : 0;
                    
                    $config = $leave_config[$type] ?? [
                        'label' => ucfirst(str_replace('_', ' ', $type)),
                        'class' => 'bg-white border-slate-200',
                        'color' => 'text-slate-700',
                        'icon' => 'fas fa-calendar',
                        'accent' => 'bg-slate-500'
                    ];
                ?>
                <div class="<?= $config['class'] ?> border rounded-lg p-5 hover:shadow-md transition-all duration-200 hover:border-slate-300">
                    <!-- Icon and Title -->
                    <div class="flex items-center justify-center mb-4">
                        <div class="<?= $config['accent'] ?> rounded-lg p-3 inline-flex">
                            <i class="<?= $config['icon'] ?> text-white text-lg"></i>
                        </div>
                    </div>
                    
                    <h3 class="font-medium <?= $config['color'] ?> text-sm mb-4 leading-tight text-center">
                        <?= $config['label'] ?>
                    </h3>
                    
                    <!-- Balance Display -->
                    <div class="text-center">
                        <div class="mb-3">
                            <span class="text-3xl font-semibold text-slate-800">
                                <?= number_format($balance, 1) ?>
                            </span>
                            <span class="text-sm text-slate-500 ml-1">days</span>
                        </div>
                        
                        <?php if ($type === 'vacation' && $carry > 0): ?>
                            <div class="text-xs text-slate-500 bg-slate-50 px-3 py-1 rounded-full inline-block">
                                Carried over: <?= number_format($carry, 1) ?> days
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-center border-t border-slate-200 pt-6">
                <button class="bg-slate-800 hover:bg-slate-700 text-white px-6 py-2.5 rounded-lg font-medium shadow-sm transition-all duration-200 hover:shadow-md">
                    <i class="fas fa-plus mr-2"></i>Request Leave
                </button>
                <button class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-6 py-2.5 rounded-lg font-medium shadow-sm transition-all duration-200 hover:shadow-md">
                    <i class="fas fa-history mr-2"></i>Leave History
                </button>
                <button class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-6 py-2.5 rounded-lg font-medium shadow-sm transition-all duration-200 hover:shadow-md">
                    <i class="fas fa-download mr-2"></i>Export Report
                </button>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="bg-slate-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-times text-slate-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-slate-700 mb-2">No Leave Credits Found</h3>
                <p class="text-slate-500 mb-6 max-w-sm mx-auto">It looks like your leave credits haven't been set up yet. Please contact HR for assistance.</p>
                <button class="bg-slate-800 hover:bg-slate-700 text-white px-6 py-2.5 rounded-lg font-medium shadow-sm transition-all duration-200">
                    <i class="fas fa-envelope mr-2"></i>Contact HR
                </button>
            </div>
        <?php endif; ?>

        <!-- Footer Note -->
        <div class="mt-8 bg-slate-50 rounded-lg p-4 border border-slate-200">
            <div class="flex items-start">
                <div class="bg-slate-200 rounded-full p-2 mr-3 mt-0.5">
                    <i class="fas fa-info text-slate-600 text-xs"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-700 font-medium mb-1">Leave Policy Information</p>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Leave balances are updated monthly according to company policy. Unused vacation leaves may be carried over to the following year subject to management approval. 
                        For inquiries regarding your leave credits, please contact the Human Resources department.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Subtle animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.leave-card {
    animation: fadeIn 0.4s ease-out;
}

/* Professional scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Focus states for accessibility */
button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>