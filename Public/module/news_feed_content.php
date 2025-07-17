<?php
$current_user_id = $_SESSION['employee']['id'] ?? null;

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<meta charset="UTF-8">
<!-- Scrollable Feed Container -->
<div class="max-h-[90vh] overflow-y-auto w-full" id="news-feed-container">

    <!-- Lightbox Modal -->
    <div id="lightbox-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
        <span class="absolute top-5 right-5 text-white text-3xl cursor-pointer" onclick="closeLightbox()">×</span>
        <img id="lightbox-image" src="" class="max-h-[90vh] max-w-[90vw] rounded shadow-xl" alt="Expanded Image">
    </div>

    <!-- Announcements -->
    <div class="w-full space-y-5 px-0">
        <?php foreach ($announcements as $announcement):
            $aid = $announcement['announcement_id'];

            // Comment count
            $comment_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE announcement_id = ?");
            $comment_count_stmt->execute([$aid]);
            $comment_count = $comment_count_stmt->fetchColumn();

            // Reactions
            $reaction_stmt = $pdo->prepare("
                SELECT emoji, COUNT(*) as count
                FROM reactions
                WHERE announcement_id = ?
                GROUP BY emoji
            ");
            $reaction_stmt->execute([$aid]);
            $reactions = $reaction_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // User reaction
            $user_reaction_stmt = $pdo->prepare("
                SELECT emoji FROM reactions
                WHERE announcement_id = ? AND employee_id = ?
                LIMIT 1
            ");
            $user_reaction_stmt->execute([$aid, $current_user_id]);
            $user_reaction = $user_reaction_stmt->fetchColumn();
        ?>

        <!-- Announcement Card -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 transition hover:shadow-lg overflow-hidden">

            <!-- Header -->
            <div class="flex items-center px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-500 text-white">
                <div class="bg-white text-green-600 font-bold w-10 h-10 rounded-full flex items-center justify-center text-lg">
                    <?= strtoupper(substr($announcement['admin_name'], 0, 1)) ?>
                </div>
                <div class="ml-3">
                    <p class="font-semibold"><?= htmlspecialchars($announcement['admin_name']) ?></p>
                    <p class="text-xs opacity-80"><?= date('F j, Y · g:i A', strtotime($announcement['created_at'])) ?></p>
                </div>
            </div>

            <!-- Content -->
            <?php $cleanedContent = preg_replace('/[\r\n]{2,}/', "\n", trim($announcement['content'])); ?>
            <div class="px-5 pt-3 pb-4 text-gray-800 whitespace-pre-line leading-relaxed">
                <?= nl2br(htmlspecialchars($cleanedContent)) ?>
            </div>

            <!-- Media Files -->
            <?php
                if (!empty($announcement['image'])):
                    $files = json_decode($announcement['image'], true);
if (!is_array($files)) {
    $files = [['stored' => $announcement['image'], 'original' => basename($announcement['image'])]];
}
                    if (!is_array($files)) {
                        $files = [$announcement['image']];
                    }
            ?>
            <div class="px-5 pb-3 space-y-2">
          <?php foreach ($files as $fileInfo): 
    $storedFile = is_array($fileInfo) ? $fileInfo['stored'] : $fileInfo;
    $originalName = is_array($fileInfo) ? $fileInfo['original'] : basename($fileInfo);
    $ext = strtolower(pathinfo($storedFile, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    $fileUrl = "/Public/views/" . htmlspecialchars($storedFile);
?>
    <?php if ($isImage): ?>
        <img src="<?= $fileUrl ?>"
             alt="<?= htmlspecialchars($originalName) ?>"
             class="rounded-md cursor-pointer transition hover:brightness-90 max-w-full"
             onclick="openLightbox(this.src)">
    <?php else: ?>
        <a href="<?= $fileUrl ?>" download
           class="block text-sm text-blue-600 hover:underline">
           📎 <?= htmlspecialchars($originalName) ?>
        </a>
    <?php endif; ?>
<?php endforeach; ?>

            </div>
            <?php endif; ?>

            <!-- Emoji Counts -->
            <div id="emoji-counts-<?= $aid ?>" class="px-5 pt-2 text-sm text-gray-500 flex flex-wrap gap-2 border-t bg-gray-50">
                <?php foreach ($reactions as $emoji => $count): ?>
                    <span class="flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-full">
                        <span class="text-xl"><?= htmlspecialchars($emoji) ?></span>
                        <span class="text-sm"><?= $count ?></span>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Reaction + Comments -->
            <div class="flex justify-between items-center px-5 py-2 border-t bg-gray-50 text-sm text-gray-700 relative">
                <?php
                    $emoji = $user_reaction ?: '👍';
                    $emoji_labels = ['👍' => 'Like', '❤️' => 'Love'];
                    $label = $emoji_labels[$emoji] ?? 'Like';
                ?>
                <div class="relative" onmouseenter="showReactions(this)" onmouseleave="hideReactions(this)">
                    <button id="react-btn-<?= $aid ?>"
                            class="flex items-center gap-1 px-3 py-1 rounded hover:bg-green-100 transition font-medium">
                        <?= htmlspecialchars($emoji) ?> <?= $label ?>
                    </button>

                    <div class="reaction-menu absolute left-0 bottom-full mb-2 hidden bg-white shadow-md border rounded-full px-2 py-1 gap-1 z-10 transition-all">
                        <?php foreach (['👍', '❤️'] as $emo): ?>
                            <button onclick="reactTo(<?= $aid ?>, '<?= $emo ?>')"
                                    class="hover:scale-110 transition transform px-2 py-1 rounded-full text-xl hover:bg-green-100"
                                    title="<?= $emo ?>">
                                <?= $emo ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button onclick="openCommentsModal(<?= $aid ?>)"
                        class="hover:text-green-600 transition-colors font-medium">
                    💬 <?= $comment_count ?> Comment<?= $comment_count != 1 ? 's' : '' ?>
                </button>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Scripts -->
<script>
function openLightbox(src) {
    document.getElementById('lightbox-image').src = src;
    document.getElementById('lightbox-modal').classList.remove('hidden');
    document.getElementById('lightbox-modal').classList.add('flex');
}

function closeLightbox() {
    document.getElementById('lightbox-modal').classList.add('hidden');
    document.getElementById('lightbox-modal').classList.remove('flex');
    document.getElementById('lightbox-image').src = '';
}

function reactTo(announcementId, emoji) {
    fetch('react.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ announcement_id: announcementId, emoji: emoji })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const container = document.querySelector(`#emoji-counts-${announcementId}`);
            if (container) {
                container.innerHTML = '';
                Object.entries(data.reactions).forEach(([emo, count]) => {
                    const span = document.createElement('span');
                    span.className = 'flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-full';
                    span.innerHTML = `<span class="text-xl">${emo}</span> <span class="text-sm">${count}</span>`;
                    container.appendChild(span);
                });
            }

            const btn = document.querySelector(`#react-btn-${announcementId}`);
            if (btn) {
                btn.innerHTML = `${data.user_reaction || 'Like'}`;
            }
        } else {
            alert('Failed to react. Please try again.');
        }
    });
}

let hideTimeout;
function showReactions(container) {
    clearTimeout(hideTimeout);
    const menu = container.querySelector('.reaction-menu');
    menu.classList.remove('hidden');
    menu.classList.add('flex');
}

function hideReactions(container) {
    hideTimeout = setTimeout(() => {
        const menu = container.querySelector('.reaction-menu');
        menu.classList.add('hidden');
        menu.classList.remove('flex');
    }, 300);
}
</script>