<?php

session_start();
include('../config/db.php');

date_default_timezone_set('Asia/Manila');
$pdo->exec("SET time_zone = '+08:00'");

// Try to get current user ID from multiple possible session variables
$current_user_id = null;
if (isset($_SESSION['id'])) {
    $current_user_id = $_SESSION['id'];
} elseif (isset($_SESSION['employee']['id'])) {
    $current_user_id = $_SESSION['employee']['id'];
} elseif (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['admin_id'])) {
    $current_user_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['employee_id'])) {
    $current_user_id = $_SESSION['employee_id'];
}

// Debug - remove this after testing
// echo "<pre>Session: " . print_r($_SESSION, true) . "</pre>";
// echo "<pre>Current User ID: " . $current_user_id . "</pre>";

// Handle announcement post
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['content']) &&
    $_POST['type'] === 'announcement'
) {
    $title = trim($_POST['title']);
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
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, admin_name, created_at, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, 'Admin', $createdAt, $attachmentsJson]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $announcementId = (int)$_POST['announcement_id'];
    $commentContent = trim($_POST['comment_content']);
    
    if ($commentContent !== '' && $current_user_id) {
        $stmt = $pdo->prepare("INSERT INTO comments (announcement_id, employee_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$announcementId, $current_user_id, $commentContent]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "#post-" . $announcementId);
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $stmt = $pdo->prepare("UPDATE announcements SET deleted = 1 WHERE announcement_id = ?");
    $stmt->execute([$delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch announcements
$announcements = $pdo->query("SELECT * FROM announcements WHERE deleted = 0 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Announcements</title>
    <style>
        .facebook-post {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
        }
        .reaction-button {
            transition: all 0.2s ease;
        }
        .reaction-button:hover {
            background-color: #f3f4f6;
            transform: scale(1.05);
        }
        .comment-input {
            background-color: #f1f3f4;
            border-radius: 20px;
            border: none;
            padding: 8px 12px;
        }
        .reaction-menu {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 50px;
            padding: 8px 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            display: none;
            gap: 8px;
            z-index: 1000;
            margin-bottom: 8px;
        }
        .reaction-menu.show {
            display: flex;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('header.php'); ?>
        
        <main class="flex-1 p-6 overflow-y-auto">
            <div class="max-w-2xl mx-auto">
                
                <!-- Debug Info (remove after testing) -->
                <?php if ($current_user_id): ?>
                    <div class="bg-green-100 p-2 mb-4 rounded text-sm">
                        ✅ User logged in: ID = <?= $current_user_id ?>
                    </div>
                <?php else: ?>
                    <div class="bg-red-100 p-2 mb-4 rounded text-sm">
                        ❌ No user logged in - reactions will not work!
                    </div>
                <?php endif; ?>
                
                <!-- Create Post -->
                <div class="facebook-post mb-6">
                    <div class="p-4">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Admin</h3>
                                <p class="text-sm text-gray-500">Create new announcement</p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="type" value="announcement">
                            
                            <input 
                                type="text" 
                                name="title" 
                                placeholder="Post title..." 
                                class="w-full p-3 border border-gray-300 rounded-lg text-lg font-semibold focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            >
                            
                            <textarea 
                                name="content" 
                                rows="3" 
                                placeholder="What's happening?" 
                                class="w-full p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            ></textarea>

                            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <label for="attachmentUpload" class="flex items-center space-x-2 cursor-pointer text-green-600 hover:text-green-700 px-3 py-2 rounded-lg hover:bg-green-50">
                                        <i class="fas fa-image"></i>
                                        <span>Photo/Video</span>
                                    </label>
                                    <input type="file" id="attachmentUpload" name="attachments[]" multiple class="hidden" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                    
                                    <label for="documentUpload" class="flex items-center space-x-2 cursor-pointer text-blue-600 hover:text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-50">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Document</span>
                                    </label>
                                    <input type="file" id="documentUpload" name="attachments[]" multiple class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                                </div>
                                
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Post
                                </button>
                            </div>
                            
                            <div id="filePreview" class="hidden space-y-2"></div>
                        </form>
                    </div>
                </div>

                <!-- Posts Feed -->
                <div class="space-y-4">
                    <?php foreach ($announcements as $a):
                        $aid = $a['announcement_id'];

                        // Get reactions using post_reactions table
                        $reaction_stmt = $pdo->prepare("
                            SELECT reaction_type, COUNT(*) as count 
                            FROM post_reactions 
                            WHERE announcement_id = ? 
                            GROUP BY reaction_type
                        ");
                        $reaction_stmt->execute([$aid]);
                        $reaction_data = $reaction_stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Convert to emoji format
                        $type_to_emoji = [
                            'like' => '👍',
                            'love' => '❤️', 
                            'laugh' => '😂',
                            'wow' => '😮',
                            'sad' => '😢',
                            'angry' => '😡'
                        ];

                        $reactions = [];
                        foreach ($reaction_data as $row) {
                            $emoji = $type_to_emoji[$row['reaction_type']] ?? '👍';
                            $reactions[$emoji] = (int)$row['count'];
                        }

                        // Get user's reaction
                        $user_reaction = null;
                        if ($current_user_id) {
                            $user_reaction_stmt = $pdo->prepare("SELECT reaction_type FROM post_reactions WHERE announcement_id = ? AND employee_id = ? LIMIT 1");
                            $user_reaction_stmt->execute([$aid, $current_user_id]);
                            $user_reaction_type = $user_reaction_stmt->fetchColumn();
                            $user_reaction = $user_reaction_type ? $type_to_emoji[$user_reaction_type] : null;
                        }

                        // Get comments
                        try {
                            $comment_stmt = $pdo->prepare("
                                SELECT c.*, e.fname, e.lname 
                                FROM comments c 
                                JOIN employees e ON c.employee_id = e.id 
                                WHERE c.announcement_id = ? AND c.deleted = 0 
                                ORDER BY c.created_at ASC
                            ");
                            $comment_stmt->execute([$aid]);
                            $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $comment_stmt = $pdo->prepare("
                                SELECT c.*, e.fname, e.lname 
                                FROM comments c 
                                JOIN employees e ON c.employee_id = e.id 
                                WHERE c.announcement_id = ? 
                                ORDER BY c.created_at ASC
                            ");
                            $comment_stmt->execute([$aid]);
                            $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                        }
                        $commentCount = count($comments);
                    ?>
                        <div class="facebook-post" id="post-<?= $aid ?>">
                            <!-- Post Header -->
                            <div class="p-4 pb-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">Admin</h3>
                                            <p class="text-sm text-gray-500"><?= date('F j, Y \a\t g:i A', strtotime($a['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="relative">
                                        <button onclick="toggleDropdown(<?= $aid ?>)" class="p-2 rounded-full hover:bg-gray-100">
                                            <i class="fas fa-ellipsis-h text-gray-500"></i>
                                        </button>
                                        <div id="dropdown-<?= $aid ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                                            <form method="POST" class="p-1">
                                                <input type="hidden" name="delete_id" value="<?= $aid ?>">
                                                <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded flex items-center space-x-2">
                                                    <i class="fas fa-trash text-sm"></i>
                                                    <span>Delete Post</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="px-4 py-3">
                                <?php if (!empty($a['title'])): ?>
                                    <h2 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($a['title']) ?></h2>
                                <?php endif; ?>
                                <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($a['content'])) ?></p>

                                <!-- Attachments -->
                                <?php if (!empty($a['image'])):
                                    $files = json_decode($a['image'], true);
                                    if (!is_array($files)) $files = [$a['image']];
                                ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($files as $fileInfo):
                                        $filePath = is_array($fileInfo) ? $fileInfo['stored'] : $fileInfo;
                                        $originalName = is_array($fileInfo) ? $fileInfo['original'] : basename($fileInfo);
                                        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png']);
                                    ?>
                                        <?php if ($isImage): ?>
                                            <div class="rounded-lg overflow-hidden border border-gray-200">
                                                <img src="<?= htmlspecialchars($filePath) ?>" alt="<?= htmlspecialchars($originalName) ?>" class="w-full h-auto">
                                            </div>
                                        <?php else: ?>
                                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
                                                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                                                <div class="flex-1">
                                                    <a href="<?= htmlspecialchars($filePath) ?>" 
                                                       class="text-blue-600 hover:underline font-medium" download>
                                                        <?= htmlspecialchars($originalName) ?>
                                                    </a>
                                                    <p class="text-sm text-gray-500">Click to download</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Reaction Summary -->
                            <div class="px-4 py-2 border-t border-gray-100" id="reaction-summary-<?= $aid ?>" <?= empty($reactions) && $commentCount == 0 ? 'style="display: none;"' : '' ?>>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center space-x-1" id="reactions-display-<?= $aid ?>">
                                        <?php foreach ($reactions as $emoji => $count): ?>
                                            <span class="flex items-center space-x-1 bg-gray-100 px-2 py-1 rounded-full">
                                                <span><?= htmlspecialchars($emoji) ?></span>
                                                <span><?= $count ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <span id="comments-count-<?= $aid ?>"><?= $commentCount ?> comment<?= $commentCount !== 1 ? 's' : '' ?></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="px-4 py-2 border-t border-gray-100">
                                <div class="flex items-center justify-around">
                                    <!-- Like Button -->
                                    <div class="relative" onmouseenter="showReactions(<?= $aid ?>)" onmouseleave="hideReactions(<?= $aid ?>)">
                                        <button id="react-btn-<?= $aid ?>" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg flex-1 justify-center <?= $user_reaction ? 'text-blue-600' : 'text-gray-600' ?>" <?= !$current_user_id ? 'disabled title="Please log in to react"' : '' ?>>
                                            <span class="text-lg" id="react-emoji-<?= $aid ?>"><?= $user_reaction ?: '👍' ?></span>
                                            <span class="font-medium">Like</span>
                                        </button>
                                        <?php if ($current_user_id): ?>
                                        <div class="reaction-menu" id="reaction-menu-<?= $aid ?>">
                                            <?php foreach (['👍', '❤️', '😂', '😮', '😢', '😡'] as $emo): ?>
                                                <button onclick="handleReaction(<?= $aid ?>, '<?= $emo ?>')" 
                                                        class="hover:scale-125 transition-transform text-2xl p-1 rounded-full hover:bg-gray-100">
                                                    <?= $emo ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Comment Button -->
                                    <button onclick="toggleComments(<?= $aid ?>)" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg flex-1 justify-center text-gray-600">
                                        <i class="far fa-comment"></i>
                                        <span class="font-medium">Comment</span>
                                    </button>

                                    <!-- Share Button -->
                                    <button class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg flex-1 justify-center text-gray-600">
                                        <i class="far fa-share"></i>
                                        <span class="font-medium">Share</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <div id="comments-<?= $aid ?>" class="hidden border-t border-gray-100">
                                <!-- Existing Comments -->
                                <div class="px-4 py-3 space-y-3 max-h-96 overflow-y-auto">
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="flex space-x-3">
                                            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-sm">
                                                <?= strtoupper(substr($comment['fname'], 0, 1)) ?>
                                            </div>
                                            <div class="flex-1">
                                                <div class="bg-gray-100 rounded-2xl px-3 py-2">
                                                    <h4 class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($comment['fname'] . ' ' . $comment['lname']) ?></h4>
                                                    <p class="text-gray-800"><?= htmlspecialchars($comment['content']) ?></p>
                                                </div>
                                                <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                                                    <span><?= date('M j \a\t g:i A', strtotime($comment['created_at'])) ?></span>
                                                    <button class="hover:underline">Like</button>
                                                    <button class="hover:underline">Reply</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Comment Input -->
                                <div class="px-4 py-3 border-t border-gray-100">
                                    <form method="POST" class="flex space-x-3">
                                        <input type="hidden" name="announcement_id" value="<?= $aid ?>">
                                        <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-sm">
                                            <?= isset($_SESSION['fname']) ? strtoupper(substr($_SESSION['fname'], 0, 1)) : 'U' ?>
                                        </div>
                                        <div class="flex-1 flex space-x-2">
                                            <input 
                                                type="text" 
                                                name="comment_content" 
                                                placeholder="Write a comment..." 
                                                class="comment-input flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                required
                                                <?= !$current_user_id ? 'disabled placeholder="Please log in to comment"' : '' ?>
                                            >
                                            <button type="submit" class="text-blue-600 hover:text-blue-700" <?= !$current_user_id ? 'disabled' : '' ?>>
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($announcements)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-bullhorn text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No announcements yet</h3>
                        <p class="text-gray-500">Be the first to create an announcement!</p>
                    </div>
                <?php endif; ?>

                <div class="mt-8 text-center">
                    <a href="admin_homepage.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let reactionTimeouts = {};

function handleReaction(postId, emoji) {
    fetch('react.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            announcement_id: postId, 
            reaction_type: emoji 
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Reaction response:', data);
        if (data.success) {
            updateReactionUI(postId, data.user_reaction, data.reactions);
            hideReactions(postId);
        } else {
            console.error('Reaction failed:', data);
            // Show more detailed error info
            if (data.debug) {
                console.error('Debug info:', data.debug);
            }
            alert('Failed to react: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('Failed to react. Please try again.');
    });
}

function updateReactionUI(postId, userReaction, reactions) {
    // Update the reaction button
    const button = document.getElementById('react-btn-' + postId);
    const emoji = document.getElementById('react-emoji-' + postId);
    
    if (button && emoji) {
        emoji.textContent = userReaction || '👍';
        
        if (userReaction) {
            button.classList.remove('text-gray-600');
            button.classList.add('text-blue-600');
        } else {
            button.classList.remove('text-blue-600');
            button.classList.add('text-gray-600');
        }
    }
    
    // Update reaction counts
    const reactionsDisplay = document.getElementById('reactions-display-' + postId);
    const summaryDiv = document.getElementById('reaction-summary-' + postId);
    
    if (reactionsDisplay) {
        if (reactions && Object.keys(reactions).length > 0) {
            const reactionsHtml = Object.entries(reactions).map(([emoji, count]) => 
                `<span class="flex items-center space-x-1 bg-gray-100 px-2 py-1 rounded-full">
                    <span>${emoji}</span>
                    <span>${count}</span>
                </span>`
            ).join('');
            
            reactionsDisplay.innerHTML = reactionsHtml;
            summaryDiv.style.display = 'block';
        } else {
            reactionsDisplay.innerHTML = '';
            // Only hide if no comments either
            const commentsCount = document.getElementById('comments-count-' + postId);
            if (commentsCount && commentsCount.textContent.includes('0 comment')) {
                summaryDiv.style.display = 'none';
            }
        }
    }
}

function showReactions(postId) {
    clearTimeout(reactionTimeouts[postId]);
    const menu = document.getElementById('reaction-menu-' + postId);
    if (menu) {
        menu.classList.add('show');
    }
}

function hideReactions(postId) {
    reactionTimeouts[postId] = setTimeout(() => {
        const menu = document.getElementById('reaction-menu-' + postId);
        if (menu) {
            menu.classList.remove('show');
        }
    }, 300);
}

function toggleComments(announcementId) {
    const comments = document.getElementById('comments-' + announcementId);
    comments.classList.toggle('hidden');
}

function toggleDropdown(announcementId) {
    const dropdown = document.getElementById('dropdown-' + announcementId);
    dropdown.classList.toggle('hidden');
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#dropdown-' + announcementId) && !e.target.closest('button')) {
            dropdown.classList.add('hidden');
        }
    }, { once: true });
}

// File preview functionality
document.getElementById('attachmentUpload').addEventListener('change', handleFilePreview);
document.getElementById('documentUpload').addEventListener('change', handleFilePreview);

function handleFilePreview(e) {
    const files = e.target.files;
    const preview = document.getElementById('filePreview');
    
    if (files.length > 0) {
        preview.classList.remove('hidden');
        preview.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2 p-2 bg-gray-50 rounded border';
            div.innerHTML = `
                <i class="fas fa-file text-blue-600"></i>
                <span class="text-sm">${file.name}</span>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            `;
            preview.appendChild(div);
        });
    } else {
        preview.classList.add('hidden');
    }
}
</script>

</body>
</html>