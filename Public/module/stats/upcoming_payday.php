<?php
date_default_timezone_set('Asia/Manila'); // adjust timezone as needed

$today = new DateTime();
$currentDay = (int)$today->format('j');

// Determine next payday (5th or 20th)
if ($currentDay < 5) {
    $nextPayday = new DateTime('5 ' . $today->format('F Y'));
} elseif ($currentDay < 20) {
    $nextPayday = new DateTime('20 ' . $today->format('F Y'));
} else {
    $nextPayday = new DateTime('5 ' . $today->modify('+1 month')->format('F Y'));
}

$daysRemaining = $today->diff($nextPayday)->days;
$paydayLabel = $nextPayday->format('F j');
?>

<!-- 💰 Upcoming Payday Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Upcoming Payday</p>
            <h3 class="text-2xl font-bold mt-1"><?= $paydayLabel ?></h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="fas fa-money-bill-wave text-blue-700 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <p class="text-sm text-gray-600"><?= $daysRemaining ?> day<?= $daysRemaining === 1 ? '' : 's' ?> remaining</p>
    </div>
</div>



    