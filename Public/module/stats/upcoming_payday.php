<?php
date_default_timezone_set('Asia/Manila');

$today = new DateTime();
$currentDay = (int)$today->format('j');
$currentMonth = $today->format('n');
$currentYear = $today->format('Y');

// Determine next payday (5th or 20th)
if ($currentDay <= 5) {
    // If today is 5th or before, next payday is 5th of current month
    $nextPayday = new DateTime("$currentYear-$currentMonth-05");
    
    // If today is exactly the 5th, next payday is 20th of current month
    if ($currentDay == 5) {
        $nextPayday = new DateTime("$currentYear-$currentMonth-20");
    }
} elseif ($currentDay <= 20) {
    // If between 6th and 20th, next payday is 20th of current month
    $nextPayday = new DateTime("$currentYear-$currentMonth-20");
    
    // If today is exactly the 20th, next payday is 5th of next month
    if ($currentDay == 20) {
        $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
        $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
        $nextPayday = new DateTime("$nextYear-$nextMonth-05");
    }
} else {
    // If after 20th, next payday is 5th of next month
    $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
    $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
    $nextPayday = new DateTime("$nextYear-$nextMonth-05");
}

// Calculate days remaining
$interval = $today->diff($nextPayday);
$daysRemaining = $interval->days;

// If it's payday today, show 0 days
if ($daysRemaining == 0 || ($currentDay == 5) || ($currentDay == 20)) {
    $daysRemaining = 0;
    $paydayMessage = "Today is Payday! 🎉";
} else {
    $paydayMessage = "$daysRemaining day" . ($daysRemaining === 1 ? '' : 's') . " remaining";
}

$paydayLabel = $nextPayday->format('F j, Y');
?>

<!-- 💰 Upcoming Payday Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">
                <?= ($daysRemaining == 0) ? 'Payday Today!' : 'Upcoming Payday' ?>
            </p>
            <h3 class="text-2xl font-bold mt-1 <?= ($daysRemaining == 0) ? 'text-green-600' : '' ?>">
                <?= $paydayLabel ?>
            </h3>
        </div>
        <div class="w-12 h-12 rounded-full <?= ($daysRemaining == 0) ? 'bg-green-100' : 'bg-blue-100' ?> flex items-center justify-center">
            <i class="fas fa-money-bill-wave <?= ($daysRemaining == 0) ? 'text-green-700' : 'text-blue-700' ?> text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <?php if ($daysRemaining > 0): ?>
            <div class="mb-2 bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= max(10, 100 - ($daysRemaining * 6.67)) ?>%"></div>
            </div>
        <?php endif; ?>
        <p class="text-sm <?= ($daysRemaining == 0) ? 'text-green-600 font-semibold' : 'text-gray-600' ?>">
            <?= $paydayMessage ?>
        </p>
    </div>
</div>



