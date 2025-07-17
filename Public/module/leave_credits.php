<?php
include('../config/db.php');
$current_user_id = $_SESSION['employee']['id'] ?? null;

$leave_config = [
    'sick' => [
        'label' => 'Sick Leave',
        'class' => 'bg-blue-50 text-blue-800'
    ],
    'vacation' => [
        'label' => 'Vacation Leave',
        'class' => 'bg-green-50 text-green-800'
    ],
    'paternity' => [
        'label' => 'Paternity Leave',
        'class' => 'bg-purple-50 text-purple-800'
    ],
    'maternity' => [
        'label' => 'Maternity Leave',
        'class' => 'bg-pink-50 text-pink-800'
    ],
    'solo_parent' => [
        'label' => 'Solo Parent Leave',
        'class' => 'bg-yellow-50 text-yellow-800'
    ],
    'halfday' => [
        'label' => 'Half Day Vacation',
        'class' => 'bg-indigo-50 text-indigo-800'
    ],
    'halfday_sick' => [
        'label' => 'Half Day Sick',
        'class' => 'bg-cyan-50 text-cyan-800'
    ],
    'lwop' => [
        'label' => 'Leave Without Pay',
        'class' => 'bg-gray-50 text-gray-800'
    ],
    'bereavement' => [
        'label' => 'Bereavement Leave',
        'class' => 'bg-red-50 text-red-800'
    ],
];


$credits = [];

if ($current_user_id) {
    $stmt = $pdo->prepare("SELECT * FROM leave_credits WHERE employee_id = ? AND year = ?");
    $stmt->execute([$current_user_id, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="bg-white shadow rounded-lg p-6"> 
    <h2 class="text-2xl font-bold text-green-700 mb-4">🏖️ Leave Credits (<?= date('Y') ?>)</h2>

    <?php if (!empty($credits)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
      <?php foreach ($credits as $row): 
    $type = $row['leave_type'];
    $balance = floatval($row['balance']);
    $carry = isset($row['carry_over']) ? floatval($row['carry_over']) : 0;

    $label = $leave_config[$type]['label'] ?? ucfirst(str_replace('_', ' ', $type));
    $classes = $leave_config[$type]['class'] ?? 'bg-gray-50 text-gray-800';
?>
<div class="p-4 rounded-lg <?= $classes ?>">
    <h3 class="font-semibold text-gray-600"><?= $label ?></h3>
    <p class="text-2xl font-bold mt-1"><?= number_format($balance, 1) ?> days</p>

    <?php if ($type === 'vacation' && $carry > 0): ?>
        <p class="text-xs text-gray-600 mt-1 italic">Carry Over: <?= number_format($carry, 1) ?> days</p>
    <?php endif; ?>
</div>
<?php endforeach; ?>

    </div>
    <?php else: ?>
        <p class="text-gray-500 italic mt-4">No leave credit data found.</p>
    <?php endif; ?>

    <p class="mt-6 text-gray-500 text-sm italic">Note: Leave balances are updated monthly based on HR policy.</p>
</div>
