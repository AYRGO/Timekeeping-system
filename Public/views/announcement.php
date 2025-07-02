<?php
session_start();
include('../config/db.php');

// Set PHP and MySQL timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

$current_user_id = $_SESSION['id'] ?? null;

// Handle new announcement post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && $_POST['type'] === 'announcement') {
    $content = trim($_POST['content']);
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        $uniqueName = uniqid('img_', true) . '.' . $ext;
        $imagePath = 'uploads/' . $uniqueName;
        move_uploaded_file($imageTmpPath, $uploadDir . '/' . $uniqueName);
    }

    if ($content !== '') {
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO announcements (content, admin_name, created_at, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$content, 'Admin', $createdAt, $imagePath]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle delete announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $pdo->prepare("DELETE FROM comments WHERE announcement_id = ?")->execute([$delete_id]);
    $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?")->execute([$delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['announcement_id']) && $_POST['type'] === 'comment') {
    $comment = trim($_POST['comment']);
    $announcement_id = (int)$_POST['announcement_id'];

    if ($comment !== '' && $current_user_id) {
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO comments (announcement_id, content, created_at, employee_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$announcement_id, $comment, $createdAt, $current_user_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load announcements & comments
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$commentsStmt = $pdo->query("
    SELECT c.*, e.fname, e.lname 
    FROM comments c 
    LEFT JOIN employees e ON c.employee_id = e.id 
    ORDER BY c.created_at ASC
");
$comments = [];
foreach ($commentsStmt as $c) {
    $comments[$c['announcement_id']][] = $c;
}

include(__DIR__ . '/header.php');
?>

<main class="flex-grow">
    <div class="max-w-4xl mx-auto mt-10 px-4">
        <h1 class="text-2xl font-bold text-green-600 mb-6">📢 Announcements Feed</h1>

        <!-- Post Form -->
        <div class="bg-white rounded-xl shadow-md px-3 py-3 mb-6 border border-gray-200">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" value="announcement">

                <textarea name="content" rows="3" required placeholder="What's on your mind?"
                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-400 focus:outline-none resize-none text-sm"></textarea>

                <div class="flex items-center justify-between mt-3 px-1">
                    <label for="imageUpload"
                        class="flex items-center space-x-2 cursor-pointer text-green-600 hover:text-green-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M4 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V7.828a1 1 0 00-.293-.707l-4.828-4.828A1 1 0 0011.172 2H4zm5 6a3 3 0 110 6 3 3 0 010-6z" />
                        </svg>
                        <span class="text-sm">Upload Image</span>
                    </label>
                    <input type="file" id="imageUpload" name="image" accept="image/*" class="hidden">
                </div>

                <img id="imagePreview" class="mt-3 hidden max-w-xs rounded-md shadow" />
                <div class="flex justify-end mt-2">
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white font-semibold rounded hover:bg-green-700">Post</button>
                </div>
            </form>
        </div>


     <!-- Announcements -->
<div class="space-y-6 max-h-[calc(100vh-300px)] overflow-y-auto pr-2">
    <?php if ($announcements): ?>
        <?php foreach ($announcements as $a): ?>
            <div class="relative bg-white rounded-xl shadow-md border border-green-300 hover:shadow-lg transition-shadow duration-200">
                
                <!-- Green Header -->
                <div class="bg-green-600 text-white rounded-t-xl px-5 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <!-- Default Logo -->
                        <div class="h-9 w-9 rounded-full bg-white text-green-600 flex items-center justify-center font-bold text-base">A</div>
                        <span class="font-semibold">Admin</span>
                    </div>
                    <span class="text-sm opacity-90"><?= date('M j, Y · g:i A', strtotime($a['created_at'])) ?></span>
                </div>

                <!-- Content -->
                <div class="px-5 py-4">
                    <p class="mt-3 text-gray-800 text-sm"><?= nl2br(htmlspecialchars(trim($a['content']))) ?></p>

                    <?php if (!empty($a['image'])): ?>
                        <div class="mt-4">
                            <img src="/Timekeeping-system/Public/views/<?= htmlspecialchars($a['image']) ?>" alt="Uploaded Image" class="rounded-lg shadow max-w-full h-auto">
                        </div>
                    <?php endif; ?>

                    <!-- Delete Button -->
                    <form method="POST" class="mt-4 flex justify-end" onsubmit="return confirm('Delete this post?');">
                        <input type="hidden" name="delete_id" value="<?= $a['announcement_id'] ?>">
                        <button type="submit" class="text-red-500 hover:text-red-700 flex items-center gap-1 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 8a1 1 0 011 1v6a1 1 0 11-2 0V9a1 1 0 011-1zm4 0a1 1 0 011 1v6a1 1 0 11-2 0V9a1 1 0 011-1zm4 0a1 1 0 011 1v6a1 1 0 11-2 0V9a1 1 0 011-1z" clip-rule="evenodd" />
                                <path fill-rule="evenodd" d="M4 3a1 1 0 011-1h10a1 1 0 011 1v1H4V3zm2 3a1 1 0 011-1h6a1 1 0 011 1v1H6V6z" clip-rule="evenodd" />
                            </svg>
                            Delete
                        </button>
                    </form>

                    <!-- Comments -->
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <?php if (!empty($comments[$a['announcement_id']] ?? [])): ?>
                            <?php foreach ($comments[$a['announcement_id']] as $c): ?>
                                <div class="mb-3 text-sm text-gray-800 bg-gray-50 p-3 rounded-md border border-gray-200">
                                    <p class="whitespace-pre-line"><?= nl2br(htmlspecialchars(trim($c['content']))) ?></p>
                                    <div class="text-xs text-gray-500 mt-1 flex justify-between">
                                        <span><?= date('M j, Y · g:i A', strtotime($c['created_at'])) ?></span>
                                        <?php if (isset($c['fname'])): ?>
                                            <span><?= htmlspecialchars($c['fname'] . ' ' . $c['lname']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic">No comments yet.</p>
                        <?php endif; ?>

                        <!-- Comment Form -->
                        <form method="POST" class="mt-4 space-y-2">
                            <input type="hidden" name="type" value="comment">
                            <input type="hidden" name="announcement_id" value="<?= $a['announcement_id'] ?>">
                            <textarea name="comment" rows="2" required placeholder="Write a comment..." class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-400 focus:outline-none resize-none text-sm"></textarea>
                            <div class="flex justify-end">
                                <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition-colors duration-150">Comment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-gray-500 text-center italic py-10">No announcements yet. Be the first to post!</div>
    <?php endif; ?>
</div>

        <div class="mt-8 text-center">
            <a href="dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>
        </div>
    </div>
</main>

<script>
    const fileInput = document.getElementById('imageUpload');
    const preview = document.getElementById('imagePreview');

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // Trim all textarea inputs before form submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('textarea').forEach(textarea => {
                textarea.value = textarea.value.trim();
            });
        });
    });
</script>

<?php include(__DIR__ . '/footer.php'); ?>
