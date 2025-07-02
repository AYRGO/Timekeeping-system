<?php
$current_user_id = $_SESSION['employee']['id'] ?? null;

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Full-Width, Scrollable Feed -->
<div class="max-h-[90vh] overflow-y-auto w-full" id="news-feed-container">

    <!-- Lightbox Modal -->
    <div id="lightbox-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
        <span class="absolute top-5 right-5 text-white text-3xl cursor-pointer" onclick="closeLightbox()">×</span>
        <img id="lightbox-image" src="" class="max-h-[90vh] max-w-[90vw] rounded shadow-xl" alt="Expanded Image">
    </div>

    <!-- Announcements Feed -->
    <div class="w-full space-y-4 px-0"> <!-- Reduced space between announcements -->

        <?php foreach ($announcements as $announcement):
            $aid = $announcement['announcement_id'];

            $comment_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE announcement_id = ?");
            $comment_count_stmt->execute([$aid]);
            $comment_count = $comment_count_stmt->fetchColumn();
        ?>

        <!-- Single Announcement Card -->
        <div class="bg-white rounded-md shadow border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="flex items-center px-4 py-2 bg-green-600"> <!-- Reduced padding -->
                <div class="bg-white text-green-600 rounded-full w-8 h-8 flex items-center justify-center font-bold text-lg"> <!-- Adjusted size -->
                    <?= strtoupper(substr($announcement['admin_name'], 0, 1)) ?>
                </div>
                <div class="ml-2"> <!-- Reduced margin -->
                    <p class="font-semibold text-white"><?= htmlspecialchars($announcement['admin_name']) ?></p>
                    <p class="text-xs text-green-100"><?= date('F j, Y · g:i A', strtotime($announcement['created_at'])) ?></p>
                </div>
            </div>

            <!-- Content -->
<?php
$cleanedContent = preg_replace('/[\r\n]{2,}/', "\n", trim($announcement['content']));
?>
<div class="px-4 pt-2 pb-2 text-gray-800 whitespace-pre-line leading-relaxed">
    <?= nl2br(htmlspecialchars($cleanedContent)) ?>
</div>


            <!-- Image -->
            <?php if (!empty($announcement['image'])): ?>
                <div class="px-4 pb-2">
                    <img src="/Timekeeping-system/Public/views/<?= htmlspecialchars($announcement['image']) ?>"
                         alt="Announcement Image"
                         class="rounded-md mt-1 cursor-pointer transition hover:brightness-90"
                         onclick="openLightbox(this.src)">
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="px-4 py-1 border-t flex justify-between items-center text-sm text-gray-700 bg-gray-50"> <!-- Reduced padding -->
                <button onclick="openCommentsModal(<?= $aid ?>)"
                        class="hover:text-green-600 transition-colors font-medium">
                    💬 <?= $comment_count ?> Comment<?= $comment_count != 1 ? 's' : '' ?>
                </button>
                <div></div>
            </div>
        </div>

        <?php endforeach; ?>

    </div>
</div>

<!-- Lightbox JS -->
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
</script>
