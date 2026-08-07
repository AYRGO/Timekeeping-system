<?php
session_start();
date_default_timezone_set('Asia/Manila'); // ✅ Use Manila time

include('../config/db.php');

$current_user_id = $_SESSION['employee']['id'] ?? null;
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<!-- Comments Modal -->
<div id="commentsModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
        <button onclick="closeCommentsModal()" class="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-2xl">&times;</button>
        <div id="commentsContent"></div>
    </div>
</div>

<!-- Announcements Feed -->
<div class="p-4 space-y-6">
<?php foreach ($announcements as $announcement): ?>
    <?php
    $aid = $announcement['announcement_id'];

    $comment_stmt = $pdo->prepare("
        SELECT c.comment_id, c.content, c.created_at, e.fname, e.lname, c.employee_id
        FROM comments c
        JOIN employees e ON c.employee_id = e.id
        WHERE c.announcement_id = ?
        ORDER BY c.created_at ASC
    ");
    $comment_stmt->execute([$aid]);
    $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Format all comment timestamps to Manila time
    foreach ($comments as &$c) {
        $c['created_at'] = date('M j, Y · g:i A', strtotime($c['created_at']));
    }
    unset($c);
    ?>
    <div class="bg-white p-5 rounded shadow">
        <div class="flex justify-between mb-2">
            <span class="font-bold text-green-600"><?= htmlspecialchars($announcement['admin_name']) ?></span>
            <span class="text-sm text-gray-500"><?= date('F j, Y · g:i A', strtotime($announcement['created_at'])) ?></span>
        </div>
        <h2 class="text-lg font-semibold text-gray-800">
            <?= !empty($announcement['title']) ? htmlspecialchars($announcement['title']) : '📢 Announcement' ?>
        </h2>
        <p class="text-gray-700 mt-2 whitespace-pre-line"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>

        <!-- Attachments (Videos & Images) -->
        <?php if (!empty($announcement['image'])):
            $files = json_decode($announcement['image'], true);
            if (!is_array($files)) $files = [$announcement['image']];
        ?>
        <div class="mt-4 space-y-3">
            <?php foreach ($files as $fileInfo):
                $filePath = is_array($fileInfo) ? ($fileInfo['stored'] ?? $fileInfo['path'] ?? $fileInfo['filename'] ?? $fileInfo) : $fileInfo;
                $originalName = is_array($fileInfo) ? ($fileInfo['original'] ?? basename($filePath)) : basename($filePath);
                
                // Clean up the file path
                $filePath = str_replace(['\\', '//'], '/', $filePath);
                $filePath = ltrim($filePath, '/');
                
                // Get just the filename
                $cleanFileName = basename($filePath);
                
                // Detect if we're on production or local
                $isProduction = (strpos($_SERVER['HTTP_HOST'] ?? '', 'resourcestaffonline.com') !== false);
                
                if ($isProduction) {
                    // Production server - use relative path from document root
                    $fileUrl = '/Public/views/uploads/' . $cleanFileName;
                } else {
                    // Local development (XAMPP)
                    $fileUrl = '/Timekeeping-system/Public/views/uploads/' . $cleanFileName;
                }
                
                // Get extension from both original name and actual filename
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (empty($ext)) {
                    $ext = strtolower(pathinfo($cleanFileName, PATHINFO_EXTENSION));
                }
                
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'mpeg', '3gp']);
                
                // URL encode the filename (but not the path slashes)
                $pathParts = explode('/', $fileUrl);
                $pathParts[count($pathParts) - 1] = rawurlencode($pathParts[count($pathParts) - 1]);
                $encodedFileUrl = implode('/', $pathParts);
            ?>
                <?php if ($isVideo): ?>
                    <div class="rounded-lg overflow-hidden border border-gray-200 bg-black">
                        <video controls class="w-full" style="max-height: 500px;" preload="auto" playsinline>
                            <source src="<?= $encodedFileUrl ?>" type="video/mp4">
                            <source src="<?= $encodedFileUrl ?>" type="video/<?= $ext ?>">
                            Your browser does not support the video tag.
                        </video>
                        <div class="p-2 bg-gray-50 text-sm text-gray-600">
                            <i class="fas fa-video mr-2"></i><?= htmlspecialchars($originalName) ?>
                        </div>
                    </div>
                <?php elseif ($isImage): ?>
                    <div class="rounded-lg overflow-hidden border border-gray-200">
                        <img src="<?= $encodedFileUrl ?>" 
                             alt="<?= htmlspecialchars($originalName) ?>" 
                             class="w-full h-auto"
                             style="max-height: 500px; object-fit: contain; background: white;">
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-gray-50 rounded border border-gray-200 flex items-center justify-between">
                        <span class="text-sm text-gray-700">
                            <i class="fas fa-file mr-2"></i><?= htmlspecialchars($originalName) ?>
                        </span>
                        <a href="<?= $encodedFileUrl ?>" 
                           download="<?= htmlspecialchars($originalName) ?>"
                           class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="mt-4 border-t pt-3">
            <button onclick="openCommentsModal('<?= $aid ?>')" class="text-sm text-blue-600 hover:underline">
                💬 View Comments (<?= count($comments) ?>)
            </button>
        </div>

        <!-- JS preload comment data -->
        <script>
        window.commentsData = window.commentsData || {};
        window.commentsData["<?= $aid ?>"] = {
            comments: <?= json_encode($comments) ?>,
            currentUser: <?= json_encode($current_user_id) ?>
        };
        </script>
    </div>
<?php endforeach; ?>
</div>

<!-- Modal Scripts -->
<script>
function openCommentsModal(aid) {
    const modal = document.getElementById('commentsModal');
    const content = document.getElementById('commentsContent');
    const data = window.commentsData[aid];

    if (!data) {
        content.innerHTML = "<p class='text-red-600'>No comment data found.</p>";
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        return;
    }

    const comments = data.comments || [];
    const currentUser = data.currentUser;

    let html = `
        <h2 class="text-lg font-semibold mb-3">Comments</h2>
        <div class="space-y-2 max-h-64 overflow-y-auto mb-3">
    `;

    if (comments.length > 0) {
        comments.forEach(c => {
            const isMine = c.employee_id == currentUser;
            html += `
                <div class="${isMine ? 'bg-green-100 text-right' : 'bg-gray-100 text-left'} p-3 rounded">
                    <p class="text-sm text-gray-800">
                        <strong class="text-xs text-gray-500">#${c.comment_id}</strong><br>
                        ${c.content.replace(/\n/g, "<br>")}
                    </p>
                    <span class="text-xs text-gray-500 block mt-1">
                        ${c.created_at} - ${isMine ? 'You' : c.fname + ' ' + c.lname}
                    </span>
                </div>
            `;
        });
    } else {
        html += "<p class='text-sm text-gray-500'>No comments yet.</p>";
    }

    html += `</div>
        <form method="POST" action="add_comment.php" class="space-y-2">
            <input type="hidden" name="announcement_id" value="${aid}">
            <textarea name="content" rows="3" required class="w-full border p-2 rounded" placeholder="Write a comment..."></textarea>
            <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Post Comment</button>
        </form>
    `;

    content.innerHTML = html;
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function closeCommentsModal() {
    const modal = document.getElementById('commentsModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
}
</script>

</body>
</html>
