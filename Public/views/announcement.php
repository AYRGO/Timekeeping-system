<?php
session_start();
include('../config/db.php');

date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

$current_user_id = $_SESSION['id'] ?? null;

// Handle announcement post
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['content']) &&
    $_POST['type'] === 'announcement'
) {
    $content = trim($_POST['content']);
    $uploadedFiles = [];

    if (!empty($_FILES['attachments']['name'][0])) {
        $allowedTypes = [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png',
        ];

        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['attachments']['tmp_name'] as $index => $tmpName) {
            $fileName = $_FILES['attachments']['name'][$index];
            $fileType = mime_content_type($tmpName);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            if (in_array($fileType, $allowedTypes)) {
                $uniqueName = uniqid('file_', true) . '.' . $ext;
                $filePath = 'uploads/' . $uniqueName;
                move_uploaded_file($tmpName, $uploadDir . '/' . $uniqueName);
                $uploadedFiles[] = ['original' => $fileName, 'stored' => $filePath];
            }
        }
    }

    if ($content !== '') {
        $createdAt = date('Y-m-d H:i:s');
        $attachmentsJson = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;
        $stmt = $pdo->prepare("INSERT INTO announcements (content, admin_name, created_at, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$content, 'Admin', $createdAt, $attachmentsJson]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $pdo->prepare("DELETE FROM comments WHERE announcement_id = ?")->execute([$delete_id]);
    $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?")->execute([$delete_id]);
    $pdo->prepare("DELETE FROM reactions WHERE announcement_id = ?")->execute([$delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch announcements
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Announcements</title>
</head>
<body class="bg-gray-100">

 <div class="flex h-screen">
        <?php include('sidebar.php'); ?>

<div class="flex-1 flex flex-col">
            <?php include('header.php'); ?>
            
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="bg-white rounded-xl shadow-md px-3 py-3 mb-6 border border-gray-200">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="type" value="announcement">
                        <textarea name="content" rows="3" required placeholder="What's on your mind?" class="w-full p-3 border border-gray-300 rounded-md resize-none text-sm"></textarea>
                        <div class="flex items-center justify-between mt-3 px-1">
                            <label for="attachmentUpload" class="flex items-center space-x-2 cursor-pointer text-green-600 hover:text-green-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M4 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V7.828a1 1 0 00-.293-.707l-4.828-4.828A1 1 0 0011.172 2H4zm5 6a3 3 0 110 6 3 3 0 010-6z" />
                                </svg>
                                <span class="text-sm">Upload Files</span>
                            </label>
                            <input type="file" id="attachmentUpload" name="attachments[]" multiple class="hidden">
                        </div>
                        <div id="filePreview" class="mt-3 hidden"></div>
                        <div class="flex justify-end mt-2">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded hover:bg-green-700">Post</button>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <?php foreach ($announcements as $a):
                        $aid = $a['announcement_id'];

                        $reaction_stmt = $pdo->prepare("SELECT emoji, COUNT(*) as count FROM reactions WHERE announcement_id = ? GROUP BY emoji");
                        $reaction_stmt->execute([$aid]);
                        $reactions = $reaction_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                        $user_reaction_stmt = $pdo->prepare("SELECT emoji FROM reactions WHERE announcement_id = ? AND employee_id = ? LIMIT 1");
                        $user_reaction_stmt->execute([$aid, $current_user_id]);
                        $user_reaction = $user_reaction_stmt->fetchColumn();
                    ?>
                        <div class="bg-white rounded-xl shadow-md border border-green-300">
                            <div class="bg-green-600 text-white rounded-t-xl px-5 py-3 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-white text-green-600 flex items-center justify-center font-bold">A</div>
                                    <span class="font-semibold">Admin</span>
                                </div>
                                <span class="text-sm"><?= date('M j, Y · g:i A', strtotime($a['created_at'])) ?></span>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm text-gray-800"><?= nl2br(htmlspecialchars(trim($a['content']))) ?></p>

                                <?php if (!empty($a['image'])):
                                    $files = json_decode($a['image'], true);
                                    if (!is_array($files)) $files = [$a['image']];
                                ?>
                                <div class="mt-4 space-y-2">
                                    <?php foreach ($files as $fileInfo):
                                        $filePath = is_array($fileInfo) ? $fileInfo['stored'] : $fileInfo;
                                        $originalName = is_array($fileInfo) ? $fileInfo['original'] : basename($fileInfo);
                                        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png']);
                                    ?>
                                        <?php if ($isImage): ?>
                                            <img src="/Public/views/<?= htmlspecialchars($filePath) ?>" alt="<?= htmlspecialchars($originalName) ?>" class="rounded-lg shadow max-w-full h-auto">
                                        <?php else: ?>
                                            <a href="/Public/views/download.php?file=<?= urlencode(basename($filePath)) ?>&name=<?= urlencode($originalName) ?>" class="text-blue-600 hover:underline block">📎 <?= htmlspecialchars($originalName) ?></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <!-- Reaction Counts -->
                                <div id="emoji-counts-<?= $aid ?>" class="px-5 pt-2 text-sm text-gray-500 flex flex-wrap gap-2 border-t bg-gray-50">
                                    <?php foreach ($reactions as $emoji => $count): ?>
                                        <span class="flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-full">
                                            <span class="text-xl"><?= htmlspecialchars($emoji) ?></span>
                                            <span class="text-sm"><?= $count ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="flex justify-between items-center px-5 py-2 border-t bg-gray-50 text-sm text-gray-700 relative">
                                    <?php
                                        $emoji = $user_reaction ?: '👍';
                                        $emoji_labels = ['👍' => 'Like', '❤️' => 'Love'];
                                        $label = $emoji_labels[$emoji] ?? 'Like';
                                    ?>
                                    <div class="relative" onmouseenter="showReactions(this)" onmouseleave="hideReactions(this)">
                                        <button id="react-btn-<?= $aid ?>" class="flex items-center gap-1 px-3 py-1 rounded hover:bg-green-100 transition font-medium">
                                            <?= htmlspecialchars($emoji) ?> <?= $label ?>
                                        </button>
                                        <div class="reaction-menu absolute left-0 bottom-full mb-2 hidden bg-white shadow-md border rounded-full px-2 py-1 gap-1 z-10 transition-all">
                                            <?php foreach (['👍', '❤️'] as $emo): ?>
                                                <button onclick="reactTo(<?= $aid ?>, '<?= $emo ?>')" class="hover:scale-110 transition transform px-2 py-1 rounded-full text-xl hover:bg-green-100"><?= $emo ?></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <button onclick="openCommentsModal(<?= $aid ?>)" class="hover:text-green-600 transition-colors font-medium">💬 View all comments</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 text-center">
                    <a href="admin_homepage.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
                </div>
            </main>
        </div>
    </div>

    <script>
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
                    const emojiLabel = {'👍': 'Like', '❤️': 'Love'};
                    btn.innerHTML = `${emoji} ${emojiLabel[emoji]}`;
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

</body>
</html>
