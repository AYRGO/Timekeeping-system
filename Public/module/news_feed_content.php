<?php

$current_user_id = $_SESSION['employee']['id'] ?? null;

// Fetch announcements
$stmt = $pdo->query("SELECT * FROM announcements WHERE deleted = 0 ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<meta charset="UTF-8">
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
    .content-preview {
        max-height: 200px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .content-expanded {
        max-height: none;
    }
    .reaction-menu {
        position: fixed !important;
        background: white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border: 1px solid #e5e7eb;
        border-radius: 50px;
        padding: 8px 12px;
        display: none;
        gap: 8px;
        z-index: 9999 !important;
        min-width: max-content;
    }
    .reaction-menu.show {
        display: flex !important;
    }
    #news-feed-container {
        overflow-y: auto;
        overflow-x: visible !important;
    }
</style>

<!-- Show user ID for debugging -->
<div class="bg-blue-100 p-2 mb-4 rounded text-sm">
    User ID: <?= $current_user_id ? $current_user_id : 'NOT LOGGED IN' ?>
</div>

<!-- Scrollable Feed Container -->
<div class="max-h-[90vh] overflow-y-auto w-full" id="news-feed-container" style="overflow-x: visible !important;">

    <!-- Lightbox Modal -->
    <div id="lightbox-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
        <span class="absolute top-5 right-5 text-white text-3xl cursor-pointer" onclick="closeLightbox()">×</span>
        <img id="lightbox-image" src="" class="max-h-[90vh] max-w-[90vw] rounded shadow-xl" alt="Expanded Image">
    </div>

    <!-- Posts Feed -->
    <div class="space-y-4">
        <?php foreach ($announcements as $a):
            $aid = $a['announcement_id'];

            // Get reactions using new table
            try {
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
                $user_reaction_stmt = $pdo->prepare("SELECT reaction_type FROM post_reactions WHERE announcement_id = ? AND employee_id = ? LIMIT 1");
                $user_reaction_stmt->execute([$aid, $current_user_id]);
                $user_reaction_type = $user_reaction_stmt->fetchColumn();
                $user_reaction = $user_reaction_type ? $type_to_emoji[$user_reaction_type] : null;

            } catch (PDOException $e) {
                $reactions = [];
                $user_reaction = null;
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
            
            // Check if content is long
            $content = $a['content'];
            $isLongContent = strlen($content) > 300;
            $previewContent = $isLongContent ? substr($content, 0, 300) . '...' : $content;
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
                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($a['admin_name']) ?></h3>
                                <p class="text-sm text-gray-500"><?= date('F j, Y \a\t g:i A', strtotime($a['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post Content -->
                <div class="px-4 py-3">
                    <?php if (!empty($a['title'])): ?>
                        <h2 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($a['title']) ?></h2>
                    <?php endif; ?>
                    
                    <!-- Content with See More/Less -->
                    <div id="content-<?= $aid ?>">
                        <div id="preview-<?= $aid ?>" class="text-gray-800 leading-relaxed <?= $isLongContent ? '' : 'hidden' ?>">
                            <?= nl2br(htmlspecialchars($previewContent)) ?>
                            <?php if ($isLongContent): ?>
                                <button onclick="toggleContent(<?= $aid ?>, true)" class="text-blue-600 hover:text-blue-700 font-medium ml-2">
                                    See more
                                </button>
                            <?php endif; ?>
                        </div>
                        <div id="full-<?= $aid ?>" class="text-gray-800 leading-relaxed <?= $isLongContent ? 'hidden' : '' ?>">
                            <?= nl2br(htmlspecialchars($content)) ?>
                            <?php if ($isLongContent): ?>
                                <button onclick="toggleContent(<?= $aid ?>, false)" class="text-blue-600 hover:text-blue-700 font-medium ml-2">
                                    See less
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

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
                            $fileUrl = "/Public/views/" . htmlspecialchars($filePath);
                        ?>
                            <?php if ($isImage): ?>
                                <div class="rounded-lg overflow-hidden border border-gray-200">
                                    <img src="<?= $fileUrl ?>" alt="<?= htmlspecialchars($originalName) ?>" class="w-full h-auto cursor-pointer" onclick="openLightbox(this.src)">
                                </div>
                            <?php else: ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
                                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                                    <div class="flex-1">
                                        <a href="<?= $fileUrl ?>" 
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
                        <!-- Like Button with Reaction Menu -->
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
                        <button onclick="openCommentsModal(<?= $aid ?>)" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg flex-1 justify-center text-gray-600">
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
                        <form method="POST" action="add_comment.php" class="flex space-x-3">
                            <input type="hidden" name="announcement_id" value="<?= $aid ?>">
                            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-sm">
                                <?= isset($_SESSION['employee']['fname']) ? strtoupper(substr($_SESSION['employee']['fname'], 0, 1)) : 'U' ?>
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
            <p class="text-gray-500">Check back later for updates!</p>
        </div>
    <?php endif; ?>
</div>

<script>
let reactionTimeouts = {};

function handleReaction(postId, emoji) {
    console.log('Sending reaction:', { postId, emoji });
    
    fetch('../views/react.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            announcement_id: postId, 
            reaction_type: emoji 
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Reaction response:', data);
        if (data.success) {
            updateReactionUI(postId, data.user_reaction, data.reactions);
            hideReactions(postId);
        } else {
            console.error('Reaction failed:', data);
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
    const button = document.getElementById('react-btn-' + postId);
    
    if (menu && button) {
        const buttonRect = button.getBoundingClientRect();
        const menuWidth = 300;
        
        const left = buttonRect.left + (buttonRect.width / 2) - (menuWidth / 2);
        const top = buttonRect.top - 60;
        
        menu.style.left = Math.max(10, left) + 'px';
        menu.style.top = Math.max(10, top) + 'px';
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

function toggleContent(postId, showFull) {
    const preview = document.getElementById('preview-' + postId);
    const full = document.getElementById('full-' + postId);
    
    if (showFull) {
        preview.classList.add('hidden');
        full.classList.remove('hidden');
    } else {
        full.classList.add('hidden');
        preview.classList.remove('hidden');
    }
}

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

function openCommentsModal(announcementId) {
    const comments = document.getElementById('comments-' + announcementId);
    comments.classList.toggle('hidden');
}
</script>