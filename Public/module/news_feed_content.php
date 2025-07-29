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
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .facebook-post:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);

        border-color: rgba(0, 0, 0, 0.08);
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
    .widget-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        padding: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .widget-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .weather-icon {
        font-size: 2rem;
    }
    .news-item {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .news-item:hover {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 8px 12px;
        margin: 0 -12px;
    }
    .news-item:last-child {
        border-bottom: none;
    }
    .quote-text {
        font-style: italic;
        line-height: 1.6;
    }
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

        
<div class="flex gap-6">
    <!-- Main Content -->
    <div class="flex-1">
        <!-- News Feed Header -->
        <div class="mb-4">
            <div class="rounded-lg p-4 bg-white border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800 mb-1 flex items-center">
                            <span class="mr-2">📰</span> News Feed
                        </h1>
                        <p class="text-sm text-gray-500">
                            Stay updated with the latest announcements and company news
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scrollable Feed Container -->
        <div class="max-h-[90vh] overflow-y-auto w-full" id="news-feed-container" style="overflow-x: visible !important;">

            <!-- Lightbox Modal -->
            <div id="lightbox-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50" onclick="closeLightbox()">
                <span class="absolute top-5 right-5 text-white text-3xl cursor-pointer hover:text-gray-300 z-60" onclick="closeLightbox()">×</span>
                <div class="max-h-[90vh] max-w-[90vw] relative" onclick="event.stopPropagation()">
                    <img id="lightbox-image" src="" class="max-h-[90vh] max-w-[90vw] rounded shadow-xl" alt="Expanded Image">
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-70 text-white px-4 py-2 rounded-lg">
                        <p class="text-sm" id="lightbox-caption">Click image to close</p>
                    </div>
                </div>
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
                                    
                                    // Clean up the file path
                                    $filePath = str_replace(['\\', '//'], '/', $filePath);
                                    $filePath = ltrim($filePath, '/');
                                    
                                    // Construct proper file URL - simplified path construction
                                    if (strpos($filePath, 'uploads/') === 0) {
                                        // File is already in uploads folder
                                        $fileUrl = '/Timekeeping-system/Public/views/' . $filePath;
                                    } elseif (strpos($filePath, 'views/uploads/') === 0) {
                                        // File path includes views/uploads
                                        $fileUrl = '/Timekeeping-system/Public/' . $filePath;
                                    } else {
                                        // Default case - assume it's in uploads
                                        $fileUrl = '/Timekeeping-system/Public/views/uploads/' . basename($filePath);
                                    }
                                    
                                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                                    
                                    // Get file size if possible
                                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileUrl;
                                    $fileSize = '';
                                    if (file_exists($fullPath)) {
                                        $size = filesize($fullPath);
                                        if ($size !== false) {
                                            if ($size > 1024 * 1024) {
                                                $fileSize = round($size / (1024 * 1024), 1) . ' MB';
                                            } elseif ($size > 1024) {
                                                $fileSize = round($size / 1024, 1) . ' KB';
                                            } else {
                                                $fileSize = $size . ' B';
                                            }
                                        }
                                    }
                                ?>
                                    <?php if ($isImage): ?>
                                        <div class="rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                            <img src="<?= htmlspecialchars($fileUrl) ?>" 
                                                 alt="<?= htmlspecialchars($originalName) ?>" 
                                                 class="w-full h-auto cursor-pointer hover:opacity-90 transition-opacity" 
                                                 onclick="openLightbox('<?= htmlspecialchars($fileUrl) ?>')"
                                                 style="max-height: 500px; object-fit: contain; background: white;"
                                                 onerror="console.log('Image failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <!-- Fallback for broken images -->
                                            <div class="hidden p-4 text-center bg-gray-100">
                                                <i class="fas fa-image text-gray-400 text-2xl mb-2"></i>
                                                <p class="text-gray-500 text-sm">Image not available</p>
                                                <p class="text-xs text-gray-400 mb-2">Tried path: <?= htmlspecialchars($fileUrl) ?></p>
                                                <a href="<?= htmlspecialchars($fileUrl) ?>" 
                                                   class="text-blue-600 hover:underline text-sm" 
                                                   download="<?= htmlspecialchars($originalName) ?>">
                                                    Download <?= htmlspecialchars($originalName) ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors">
                                            <div class="flex-shrink-0">
                                                <?php
                                                // Get appropriate icon based on file extension
                                                $iconClass = 'fas fa-file';
                                                switch ($ext) {
                                                    case 'pdf':
                                                        $iconClass = 'fas fa-file-pdf text-red-600';
                                                        break;
                                                    case 'doc':
                                                    case 'docx':
                                                        $iconClass = 'fas fa-file-word text-blue-600';
                                                        break;
                                                    case 'xls':
                                                    case 'xlsx':
                                                        $iconClass = 'fas fa-file-excel text-green-600';
                                                        break;
                                                    case 'ppt':
                                                    case 'pptx':
                                                        $iconClass = 'fas fa-file-powerpoint text-orange-600';
                                                        break;
                                                    case 'txt':
                                                        $iconClass = 'fas fa-file-alt text-gray-600';
                                                        break;
                                                    case 'zip':
                                                    case 'rar':
                                                    case '7z':
                                                        $iconClass = 'fas fa-file-archive text-purple-600';
                                                        break;
                                                    case 'mp4':
                                                    case 'avi':
                                                    case 'mov':
                                                        $iconClass = 'fas fa-file-video text-red-500';
                                                        break;
                                                    case 'mp3':
                                                    case 'wav':
                                                        $iconClass = 'fas fa-file-audio text-green-500';
                                                        break;
                                                    default:
                                                        $iconClass = 'fas fa-file text-gray-500';
                                                }
                                                ?>
                                                <i class="<?= $iconClass ?> text-2xl"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-medium text-gray-800 truncate"><?= htmlspecialchars($originalName) ?></h4>
                                                <div class="flex items-center space-x-2 text-sm text-gray-500">
                                                    <span class="uppercase"><?= $ext ?> file</span>
                                                    <?php if ($fileSize): ?>
                                                        <span>•</span>
                                                        <span><?= $fileSize ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <a href="<?= htmlspecialchars($fileUrl) ?>" 
                                                   class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors" 
                                                   download="<?= htmlspecialchars($originalName) ?>"
                                                   title="Download <?= htmlspecialchars($originalName) ?>">
                                                    <i class="fas fa-download mr-1"></i>
                                                    Download
                                                </a>
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
                                    <?php 
                                    // Get reaction text and color
                                    $reaction_text = 'Like';
                                    $reaction_color = 'text-gray-600';
                                    
                                    if ($user_reaction) {
                                        $emoji_to_text = [
                                            '👍' => 'Like',
                                            '❤️' => 'Love',
                                            '😂' => 'Haha',
                                            '😮' => 'Wow',
                                            '😢' => 'Sad',
                                            '😡' => 'Angry'
                                        ];
                                        
                                        $emoji_to_color = [
                                            '👍' => 'text-blue-600',
                                            '❤️' => 'text-red-500',
                                            '😂' => 'text-yellow-500',
                                            '😮' => 'text-yellow-500',
                                            '😢' => 'text-yellow-500',
                                            '😡' => 'text-red-600'
                                        ];
                                        
                                        $reaction_text = $emoji_to_text[$user_reaction] ?? 'Like';
                                        $reaction_color = $emoji_to_color[$user_reaction] ?? 'text-blue-600';
                                    }
                                    ?>
                                    
                                    <button id="react-btn-<?= $aid ?>" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg flex-1 justify-center <?= $reaction_color ?>" <?= !$current_user_id ? 'disabled title="Please log in to react"' : '' ?>>
                                        <span class="text-lg" id="react-emoji-<?= $aid ?>"><?= $user_reaction ?: '👍' ?></span>
                                        <span class="font-medium" id="react-text-<?= $aid ?>"><?= $reaction_text ?></span>
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
                                <form onsubmit="submitComment(event, <?= $aid ?>)" class="flex space-x-3">
                                    <input type="hidden" name="type" value="comment">
                                    <input type="hidden" name="announcement_id" value="<?= $aid ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-sm">
                                        <?= isset($_SESSION['employee']['fname']) ? strtoupper(substr($_SESSION['employee']['fname'], 0, 1)) : 'U' ?>
                                    </div>
                                    <div class="flex-1 flex space-x-2">
                                        <input 
                                            type="text" 
                                            name="comment" 
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
    </div>

    <!-- Sidebar with Widgets -->
    <div class="w-80 space-y-4">
        <!-- Weather Widget -->
        <div class="widget-card" id="weather-widget">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-cloud-sun text-blue-500 mr-2"></i>
                    Weather
                </h3>
                <button onclick="refreshWeather()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div id="weather-content">
                <div class="loading-spinner mx-auto"></div>
                <p class="text-center text-gray-500 mt-2">Loading weather...</p>
            </div>
        </div>

        <!-- Daily Quote Widget -->
        <div class="widget-card" id="quote-widget">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-quote-left text-purple-500 mr-2"></i>
                    Daily Quote
                </h3>
                <button onclick="refreshQuote()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div id="quote-content">
                <div class="loading-spinner mx-auto"></div>
                <p class="text-center text-gray-500 mt-2">Loading quote...</p>
            </div>
        </div>

        <!-- News Widget -->
        <div class="widget-card" id="news-widget">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-newspaper text-red-500 mr-2"></i>
                    Latest News
                </h3>
                <button onclick="refreshNews()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div id="news-content">
                <div class="loading-spinner mx-auto"></div>
                <p class="text-center text-gray-500 mt-2">Loading news...</p>
            </div>
        </div>

        <!-- Fun Facts Widget -->
        <div class="widget-card" id="facts-widget">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Fun Fact
                </h3>
                <button onclick="refreshFact()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div id="fact-content">
                <div class="loading-spinner mx-auto"></div>
                <p class="text-center text-gray-500 mt-2">Loading fact...</p>
            </div>
        </div>
    </div>
</div>


<script>
let reactionTimeouts = {};

async function loadWeather() {
    try {
        // Visual Crossing Weather API for Clark, Pampanga
        const apiKey = 'WGNTPH8KPCN49BMK8GHNJKYVA';
        const url = `https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/Clark%2C%20Pampanga?unitGroup=metric&key=${apiKey}&contentType=json`;

        console.log('Fetching weather from:', url);
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Weather API error: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Weather data received:', data);

        // Get today's weather
        const today = data.days && data.days.length > 0 ? data.days[0] : null;
        if (!today) throw new Error('No weather data for today');

        const temp = Math.round(today.temp);
        const condition = today.conditions || 'Partly Cloudy';
        const humidity = today.humidity || '--';
        const wind = Math.round(today.windspeed || 0);
        const location = `${data.resolvedAddress || 'Clark, Pampanga'}`;

        document.getElementById('weather-content').innerHTML = `
            <div class="text-center">
                <div class="weather-icon text-4xl mb-2">${getWeatherIcon(condition)}</div>
                <h4 class="text-xl font-bold text-gray-800">${temp}°C</h4>
                <p class="text-gray-600 capitalize">${condition}</p>
                <p class="text-sm text-gray-500 mt-1">${location}</p>
                <div class="flex justify-between mt-3 text-sm">
                    <span>💧 ${humidity}%</span>
                    <span>💨 ${wind} km/h</span>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Weather loading error:', error);
        // Fallback weather data for Clark, Pampanga
        document.getElementById('weather-content').innerHTML = `
            <div class="text-center">
                <div class="weather-icon text-4xl mb-2">🌤️</div>
                <h4 class="text-xl font-bold text-gray-800">29°C</h4>
                <p class="text-gray-600">Partly Cloudy</p>
                <p class="text-sm text-gray-500 mt-1">Clark, Philippines</p>
                <div class="flex justify-between mt-3 text-sm">
                    <span>💧 75%</span>
                    <span>💨 12 km/h</span>
                </div>
                <p class="text-xs text-red-500 mt-2">Unable to fetch live data</p>
            </div>
        `;
    }
}

async function loadQuote() {
    try {
        const response = await fetch('https://api.quotable.io/random?minLength=50&maxLength=150');
        
        if (!response.ok) {
            throw new Error('Quote service unavailable');
        }
        
        const data = await response.json();
        document.getElementById('quote-content').innerHTML = `
            <div class="text-center">
                <p class="quote-text text-gray-700 mb-3">"${data.content}"</p>
                <p class="text-sm text-gray-500">— ${data.author}</p>
            </div>
        `;
    } catch (error) {
        console.error('Quote loading error:', error);
        // Fallback quotes
        const fallbackQuotes = [
            { content: "The only way to do great work is to love what you do.", author: "Steve Jobs" },
            { content: "Innovation distinguishes between a leader and a follower.", author: "Steve Jobs" },
            { content: "Success is not final, failure is not fatal: it is the courage to continue that counts.", author: "Winston Churchill" },
            { content: "The future belongs to those who believe in the beauty of their dreams.", author: "Eleanor Roosevelt" },
            { content: "Excellence is never an accident. It is always the result of high intention, sincere effort, and intelligent execution.", author: "Aristotle" }
        ];
        
        const randomQuote = fallbackQuotes[Math.floor(Math.random() * fallbackQuotes.length)];
        document.getElementById('quote-content').innerHTML = `
            <div class="text-center">
                <p class="quote-text text-gray-700 mb-3">"${randomQuote.content}"</p>
                <p class="text-sm text-gray-500">— ${randomQuote.author}</p>
            </div>
        `;
    }
}

async function loadNews() {
    try {
        // Note: NewsAPI requires a valid API key for production
        // For demo purposes, we'll use fallback news
        throw new Error('Using fallback news for demo');
        
    } catch (error) {
        console.log('Using fallback news data');
        // Fallback news with realistic Philippine content
        document.getElementById('news-content').innerHTML = `
            <div class="news-item" onclick="window.open('https://www.bworldonline.com/', '_blank')">
                <h5 class="font-medium text-gray-800 text-sm leading-tight mb-1">Philippine Economy Shows Steady Growth in Q3</h5>
                <p class="text-xs text-gray-500">Business World • ${new Date().toLocaleDateString()}</p>
            </div>
            <div class="news-item" onclick="window.open('https://technews.ph/', '_blank')">
                <h5 class="font-medium text-gray-800 text-sm leading-tight mb-1">Clark Freeport Zone Expands Tech Infrastructure</h5>
                <p class="text-xs text-gray-500">Tech News PH • ${new Date().toLocaleDateString()}</p>
            </div>
            <div class="news-item" onclick="window.open('https://www.deped.gov.ph/', '_blank')">
                <h5 class="font-medium text-gray-800 text-sm leading-tight mb-1">Education Department Launches Digital Learning Initiative</h5>
                <p class="text-xs text-gray-500">DepEd Official • Yesterday</p>
            </div>
            <div class="news-item" onclick="window.open('https://www.doh.gov.ph/', '_blank')">
                <h5 class="font-medium text-gray-800 text-sm leading-tight mb-1">Health Ministry Reports Improved Healthcare Access</h5>
                <p class="text-xs text-gray-500">DOH Philippines • Yesterday</p>
            </div>
            <div class="news-item" onclick="window.open('https://www.bsp.gov.ph/', '_blank')">
                <h5 class="font-medium text-gray-800 text-sm leading-tight mb-1">Central Bank Announces New Digital Payment Guidelines</h5>
                <p class="text-xs text-gray-500">BSP • 2 days ago</p>
            </div>
        `;
    }
}

async function loadFact() {
    try {
        const response = await fetch('https://uselessfacts.jsph.pl/random.json?language=en');
        
        if (!response.ok) {
            throw new Error('Facts service unavailable');
        }
        
        const data = await response.json();
        document.getElementById('fact-content').innerHTML = `
            <div class="text-center">
                <p class="text-gray-700 leading-relaxed">${data.text}</p>
            </div>
        `;
    } catch (error) {
        console.error('Fact loading error:', error);
        // Fallback facts
        const fallbackFacts = [
            "Honey never spoils. Archaeologists have found pots of honey in ancient Egyptian tombs that are over 3,000 years old and still perfectly edible.",
            "Octopuses have three hearts and blue blood. Two hearts pump blood to the gills, while the third pumps blood to the rest of the body.",
            "A group of flamingos is called a 'flamboyance', which perfectly describes their vibrant pink appearance.",
            "Bananas are technically berries, but strawberries aren't. Botanically speaking, berries have seeds inside their flesh.",
            "The shortest war in history lasted only 38-45 minutes between Britain and Zanzibar in 1896.",
            "Philippines has over 7,640 islands, but only about 2,000 are inhabited by people.",
            "A single cloud can weigh more than a million pounds, yet it floats in the sky due to air density differences.",
            "Wombat droppings are cube-shaped, making them the only known animal to produce square feces.",
            "The human brain uses about 20% of the body's total energy, despite weighing only about 2% of body weight."
        ];
        
        const randomFact = fallbackFacts[Math.floor(Math.random() * fallbackFacts.length)];
        document.getElementById('fact-content').innerHTML = `
            <div class="text-center">
                <p class="text-gray-700 leading-relaxed">${randomFact}</p>
            </div>
        `;
    }
}

function getWeatherIcon(condition) {
    condition = condition.toLowerCase();
    if (condition.includes('sunny') || condition.includes('clear')) return '☀️';
    if (condition.includes('cloud')) return '☁️';
    if (condition.includes('rain')) return '🌧️';
    if (condition.includes('storm') || condition.includes('thunder')) return '⛈️';
    if (condition.includes('snow')) return '❄️';
    if (condition.includes('fog') || condition.includes('mist')) return '🌫️';
    if (condition.includes('drizzle')) return '🌦️';
    if (condition.includes('wind')) return '💨';
    return '🌤️'; // Default partly cloudy
}

// Refresh functions
function refreshWeather() {
    document.getElementById('weather-content').innerHTML = '<div class="loading-spinner mx-auto"></div><p class="text-center text-gray-500 mt-2">Loading weather...</p>';
    loadWeather();
}

function refreshQuote() {
    document.getElementById('quote-content').innerHTML = '<div class="loading-spinner mx-auto"></div><p class="text-center text-gray-500 mt-2">Loading quote...</p>';
    loadQuote();
}

function refreshNews() {
    document.getElementById('news-content').innerHTML = '<div class="loading-spinner mx-auto"></div><p class="text-center text-gray-500 mt-2">Loading news...</p>';
    loadNews();
}

function refreshFact() {
    document.getElementById('fact-content').innerHTML = '<div class="loading-spinner mx-auto"></div><p class="text-center text-gray-500 mt-2">Loading fact...</p>';
    loadFact();
}

function toggleComments(announcementId) {
    const comments = document.getElementById('comments-' + announcementId);
    comments.classList.toggle('hidden');
}
// Load all widgets when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Loading all widgets...');
    loadWeather();
    loadQuote();
    loadNews();
    loadFact();
    
    // Auto-refresh every 30 minutes for weather and news
    setInterval(() => {
        console.log('Auto-refreshing weather and news...');
        loadWeather();
        loadNews();
    }, 30 * 60 * 1000);
    
    // Refresh quote and fact every hour
    setInterval(() => {
        console.log('Auto-refreshing quote and fact...');
        loadQuote();
        loadFact();
    }, 60 * 60 * 1000);
});

// Reaction functions
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
    const text = document.getElementById('react-text-' + postId);
    
    // Map emojis to their text labels
    const emojiToText = {
        '👍': 'Like',
        '❤️': 'Love',
        '😂': 'Haha',
        '😮': 'Wow',
        '😢': 'Sad',
        '😡': 'Angry'
    };
    
    // Map emojis to their colors (Facebook-style)
    const emojiToColor = {
        '👍': 'text-blue-600',
        '❤️': 'text-red-500',
        '😂': 'text-yellow-500',
        '😮': 'text-yellow-500',
        '😢': 'text-yellow-500',
        '😡': 'text-red-600'
    };
    
    if (button && emoji && text) {
        if (userReaction) {
            // User has reacted - show their reaction
            emoji.textContent = userReaction;
            text.textContent = emojiToText[userReaction] || 'Like';
            
            // Remove all color classes
            button.classList.remove('text-gray-600', 'text-blue-600', 'text-red-500', 'text-yellow-500', 'text-red-600');
            // Add the specific color for this reaction
            button.classList.add(emojiToColor[userReaction] || 'text-blue-600');
        } else {
            // No reaction - show default
            emoji.textContent = '👍';
            text.textContent = 'Like';
            
            // Remove all color classes and set to default
            button.classList.remove('text-blue-600', 'text-red-500', 'text-yellow-500', 'text-red-600');
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
function submitComment(event, announcementId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const commentInput = form.querySelector('input[name="comment"]');
    const commentText = commentInput.value.trim();
    
    if (!commentText) {
        alert('Please enter a comment.');
        return;
    }
    
    // Disable submit button to prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalIcon = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
    
    fetch('submit_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear the input
            commentInput.value = '';
            
            // Add the new comment to the comments section
            const commentsContainer = document.querySelector(`#comments-${announcementId} .space-y-3`);
            const newCommentHtml = `
                <div class="flex space-x-3">
                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-sm">
                        ${data.comment.user_initial}
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-100 rounded-2xl px-3 py-2">
                            <h4 class="font-semibold text-sm text-gray-900">${data.comment.user_name}</h4>
                            <p class="text-gray-800">${data.comment.content}</p>
                        </div>
                        <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                            <span>${data.comment.time}</span>
                            <button class="hover:underline">Like</button>
                            <button class="hover:underline">Reply</button>
                        </div>
                    </div>
                </div>
            `;
            
            commentsContainer.insertAdjacentHTML('beforeend', newCommentHtml);
            
            // Update comment count
            const commentCountElement = document.getElementById(`comments-count-${announcementId}`);
            if (commentCountElement) {
                const currentCount = parseInt(commentCountElement.textContent.match(/\d+/)[0]) || 0;
                const newCount = currentCount + 1;
                commentCountElement.textContent = `${newCount} comment${newCount !== 1 ? 's' : ''}`;
            }
            
            // Show the reaction summary if it was hidden
            const summaryDiv = document.getElementById(`reaction-summary-${announcementId}`);
            if (summaryDiv) {
                summaryDiv.style.display = 'block';
            }
            
        } else {
            alert('Failed to post comment: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to post comment. Please try again.');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.innerHTML = originalIcon;
        submitBtn.disabled = false;
    });
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
    const img = document.getElementById('lightbox-image');
    const modal = document.getElementById('lightbox-modal');
    const caption = document.getElementById('lightbox-caption');
    
    img.src = src;
    
    // Extract filename for caption
    const filename = src.split('/').pop();
    caption.textContent = filename || 'Click anywhere to close';
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Prevent body scrolling
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const modal = document.getElementById('lightbox-modal');
    const img = document.getElementById('lightbox-image');
    
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    img.src = '';
    
    // Restore body scrolling
    document.body.style.overflow = '';
}

// Close lightbox with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>

