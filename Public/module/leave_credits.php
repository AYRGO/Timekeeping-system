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
    'bereavement' => [
        'label' => 'Bereavement Leave',
        'class' => 'bg-white border-slate-200',
        'color' => 'text-slate-700',
        'icon' => 'fas fa-dove',
        'accent' => 'bg-slate-600'
    ],
];

$credits = [];
$leaveHistory = [];

if ($current_user_id) {
    // Fetch leave credits - only for main leave types
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ? AND leave_type IN ('sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'bereavement')");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch leave requests from post_leave_requests (all statuses for history)
    $stmt = $pdo->prepare("SELECT * FROM post_leave_requests WHERE employee_id = ? ORDER BY start_date DESC LIMIT 10");
    $stmt->execute([$current_user_id]);
    $leaveHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate total available days
$totalDays = 0;
foreach ($credits as $row) {
    $totalDays += floatval($row['balance']);
}

// Helper function for status badges
function getStatusBadge($status) {
    switch($status) {
        case 'approved':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        <i class="fas fa-check-circle mr-1"></i>Approved
                    </span>';
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        <i class="fas fa-clock mr-1"></i>Pending
                    </span>';
        case 'rejected':
        case 'declined':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i>Rejected
                    </span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        <i class="fas fa-ban mr-1"></i>Cancelled
                    </span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">' . ucfirst($status) . '</span>';
    }
}
?>

<div class="space-y-8">
    <!-- Leave Credits Section -->
    <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-slate-200">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border-b border-slate-200 px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center">
                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                        </div>
                        Leave Credits <?= date('Y') ?>
                    </h2>
                    <p class="text-slate-600 mt-1">Monitor your available leave balances</p>
                </div>
                <div class="text-right">
                    <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 shadow-sm">
                        <p class="text-sm text-slate-500 font-medium">Total Available</p>
                        <p class="text-2xl font-bold text-slate-800"><?= number_format($totalDays, 1) ?> <span class="text-sm font-normal text-slate-500">days</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-6">
            <?php if (!empty($credits)): ?>
                <!-- Leave Types Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
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
                    <div class="group <?= $config['class'] ?> border rounded-xl p-6 hover:shadow-lg transition-all duration-300 hover:border-slate-300 hover:scale-105 relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute top-0 right-0 w-16 h-16 <?= $config['accent'] ?> opacity-5 rounded-full transform translate-x-8 -translate-y-8"></div>
                        
                        <!-- Icon and Title -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="<?= $config['accent'] ?> rounded-xl p-4 inline-flex shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="<?= $config['icon'] ?> text-white text-xl"></i>
                            </div>
                        </div>
                        
                        <h3 class="font-semibold <?= $config['color'] ?> text-sm mb-4 leading-tight text-center">
                            <?= $config['label'] ?>
                        </h3>
                        
                        <!-- Balance Display -->
                        <div class="text-center">
                            <div class="mb-3">
                                <span class="text-3xl font-bold text-slate-800">
                                    <?= number_format($balance, 1) ?>
                                </span>
                                <span class="text-sm text-slate-500 ml-1">days</span>
                            </div>
                            
                            <?php if ($type === 'vacation' && $carry > 0): ?>
                                <div class="text-xs text-slate-600 bg-slate-100 px-3 py-1.5 rounded-full inline-block">
                                    <i class="fas fa-plus-circle mr-1"></i>
                                    Carried: <?= number_format($carry, 1) ?> days
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end border-t border-slate-200 pt-8">
                    <div class="flex gap-4">
                        <button onclick="toggleLeaveHistory()" class="group bg-white hover:bg-slate-50 text-slate-700 border-2 border-slate-300 hover:border-slate-400 px-8 py-3 rounded-xl font-semibold shadow-md transition-all duration-300 hover:shadow-lg hover:scale-105 flex items-center">
                            <i class="fas fa-history mr-3 group-hover:rotate-12 transition-transform duration-300"></i>
                            Leave History
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-20">
                    <div class="bg-gradient-to-br from-slate-100 to-blue-50 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <i class="fas fa-calendar-times text-slate-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-700 mb-3">No Leave Credits Found</h3>
                    <p class="text-slate-500 mb-8 max-w-md mx-auto">It looks like your leave credits haven't been set up yet. Please contact HR for assistance.</p>
                    <button class="bg-gradient-to-r from-slate-700 to-slate-800 hover:from-slate-800 hover:to-slate-900 text-white px-8 py-3 rounded-xl font-semibold shadow-lg transition-all duration-300 hover:shadow-xl">
                        <i class="fas fa-envelope mr-3"></i>Contact HR
                    </button>
                </div>
            <?php endif; ?>

            <!-- Footer Note -->
            <div class="mt-8 bg-gradient-to-r from-slate-50 to-blue-50 rounded-xl p-6 border border-slate-200">
                <div class="flex items-start">
                    <div class="bg-blue-100 rounded-full p-3 mr-4 mt-0.5">
                        <i class="fas fa-info text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-700 font-semibold mb-2">Leave Policy Information</p>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Leave balances are updated monthly according to company policy. Unused vacation leaves may be carried over to the following year subject to management approval. 
                            For inquiries regarding your leave credits, please contact the Human Resources department.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave History Section (Initially Hidden) -->
    <div id="leaveHistorySection" class="bg-white shadow-lg rounded-xl overflow-hidden border border-slate-200 hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-50 to-green-50 border-b border-slate-200 px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-800 flex items-center">
                        <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-history text-emerald-600 text-lg"></i>
                        </div>
                        Approved Leave History
                    </h3>
                    <p class="text-slate-600 mt-1">Your recent approved leave requests</p>
                </div>
                <button onclick="toggleLeaveHistory()" class="text-slate-500 hover:text-slate-700 p-2 rounded-lg hover:bg-white transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (!empty($leaveHistory)): ?>
                <div class="space-y-4">
                    <?php foreach ($leaveHistory as $leave): ?>
                    <div class="group bg-gradient-to-r from-white to-slate-50 border border-slate-200 rounded-xl p-6 hover:shadow-md transition-all duration-300 hover:border-slate-300">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Leave Info -->
                            <div class="flex items-start space-x-4">
                                <div class="bg-emerald-100 p-3 rounded-lg">
                                    <?php
                                    $icon = $leave_config[$leave['leave_type']]['icon'] ?? 'fas fa-calendar';
                                    ?>
                                    <i class="<?= $icon ?> text-emerald-600 text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-800 text-lg">
                                        <?= $leave_config[$leave['leave_type']]['label'] ?? ucfirst(str_replace('_', ' ', $leave['leave_type'])) ?>
                                    </h4>
                                    <p class="text-slate-600 text-sm mb-2">
                                        <?= date('M d, Y', strtotime($leave['start_date'])) ?> - <?= date('M d, Y', strtotime($leave['end_date'])) ?>
                                    </p>
                                    <?php if (!empty($leave['reason'])): ?>
                                        <p class="text-slate-500 text-sm italic">"<?= htmlspecialchars($leave['reason']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Duration & Status -->
                            <div class="flex flex-col md:items-end gap-2">
                                <div class="flex items-center gap-4">
                                    <?php
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                    ?>
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                        <i class="fas fa-calendar-day mr-1"></i>
                                        <?= $days ?> day<?= $days > 1 ? 's' : '' ?>
                                    </span>
                                    <?= getStatusBadge($leave['status']) ?>
                                </div>
                                <span class="text-xs text-slate-500">
                                    <i class="fas fa-clock mr-1"></i>
                                    Approved <?= date('M d, Y', strtotime($leave['approved_at'] ?? $leave['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Empty History State -->
                <div class="text-center py-16">
                    <div class="bg-gradient-to-br from-slate-100 to-emerald-50 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-check text-emerald-400 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-medium text-slate-700 mb-2">No Approved Leaves Yet</h4>
                    <p class="text-slate-500 max-w-sm mx-auto">Your approved leave requests will appear here once processed by HR.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.leave-card {
    animation: fadeInUp 0.5s ease-out;
}

#leaveHistorySection.show {
    animation: slideDown 0.4s ease-out;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #94a3b8, #64748b);
}

/* Focus states for accessibility */
button:focus, a:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Hover effects */
.group:hover .group-hover\:rotate-90 {
    transform: rotate(90deg);
}

.group:hover .group-hover\:rotate-12 {
    transform: rotate(12deg);
}

.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>

<script>
function toggleLeaveHistory() {
    const section = document.getElementById('leaveHistorySection');
    
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        section.classList.add('show');
        // Smooth scroll to the section
        setTimeout(() => {
            section.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 100);
    } else {
        section.classList.add('hidden');
        section.classList.remove('show');
    }
}

// Add ripple effect to buttons
document.querySelectorAll('button, a').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});
</script>

<style>
/* Ripple effect */
.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: rippleEffect 0.6s linear;
    pointer-events: none;
}

@keyframes rippleEffect {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

button, a {
    position: relative;
    overflow: hidden;
}
</style>