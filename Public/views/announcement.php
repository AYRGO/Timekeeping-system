<?php

session_start();
include('../config/db.php');

$pageTitle = 'News Feed';

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

// Handle announcement post
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['content']) &&
    $_POST['type'] === 'announcement'
) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $uploadedFiles = [];

    // Use only one file input for all attachments
    if (!empty($_FILES['attachments']['name'][0])) {
        $allowedTypes = [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            // Video types
            'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
            'video/x-msvideo', 'video/x-ms-wmv', 'video/mpeg', 'video/3gpp',
        ];

        // Use correct upload directory (relative to Public/views)
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['attachments']['tmp_name'] as $index => $tmpName) {
            // Skip empty files
            if (empty($tmpName) || empty($_FILES['attachments']['name'][$index])) {
                continue;
            }
            
            $fileName = $_FILES['attachments']['name'][$index];
            $fileError = $_FILES['attachments']['error'][$index];
            
            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Validate file exists and is uploaded
            if (!is_uploaded_file($tmpName)) {
                continue;
            }
            
            $fileType = mime_content_type($tmpName);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);

            if (in_array($fileType, $allowedTypes)) {
                $uniqueName = uniqid('file_', true) . '.' . $ext;
                $filePath = 'uploads/' . $uniqueName; // Save relative to Public/views
                
                if (move_uploaded_file($tmpName, $uploadDir . '/' . $uniqueName)) {
                    $uploadedFiles[] = ['original' => $fileName, 'stored' => $filePath];
                }
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

$upcomingEvents = [];
$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+90 days'));

try {
    $tableStmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $tableStmt->execute(['company_holidays']);

    if ($tableStmt->rowCount() > 0) {
        $eventStmt = $pdo->prepare("
            SELECT holiday_date AS schedule_date, holiday_name, holiday_type
            FROM company_holidays
            WHERE holiday_date BETWEEN ? AND ?
            ORDER BY holiday_date ASC
            LIMIT 3
        ");
        $eventStmt->execute([$today, $endDate]);
        $upcomingEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Unable to load admin upcoming holidays: " . $e->getMessage());
    $upcomingEvents = [];
}
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
        .content-preview {
            max-height: 200px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        .content-expanded {
            max-height: none;
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

        .admin-news-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 22px;
            align-items: start;
            max-width: 1500px;
            margin: 0 auto;
        }

        .admin-feed-main {
            min-width: 0;
        }

        .feed-panel,
        .widget-card,
        .facebook-post {
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .facebook-post {
            border-radius: 10px;
        }

        .feed-panel {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .feed-section-header {
            min-height: 48px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eef2f7;
        }

        .feed-heading,
        .widget-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 800;
            color: #111827;
        }

        .feed-heading-icon {
            display: inline-flex;
            width: 22px;
            height: 22px;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 13px;
        }

        .feed-link-button,
        .widget-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 0;
            background: transparent;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .widget-refresh {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            color: #64748b;
        }

        .widget-refresh:hover {
            background: #f8fafc;
            color: #2563eb;
        }

        .trending-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.42fr) minmax(280px, 0.78fr);
            grid-template-rows: repeat(2, 138px);
            gap: 14px;
            padding: 14px 16px 16px;
        }

        .news-image-card {
            min-height: 138px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            background: #111827;
        }

        .news-image-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .news-image-card:hover img {
            transform: scale(1.05);
        }

        .news-image-card.is-featured {
            grid-row: span 2;
            min-height: 290px;
        }

        .news-image-overlay {
            position: absolute;
            inset: auto 0 0;
            padding: 48px 14px 38px;
            color: #fff;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, 0.88));
        }

        .news-image-title {
            font-weight: 800;
            font-size: 14px;
            line-height: 1.18;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.45);
        }

        .news-image-card.is-featured .news-image-title {
            font-size: 22px;
            line-height: 1.1;
            max-width: 90%;
        }

        .news-image-source {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.86);
            margin-top: 5px;
        }

        .news-category-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;
            border-radius: 6px;
            background: #2563eb;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.02em;
            padding: 4px 8px;
            text-transform: uppercase;
        }

        .news-image-card:not(.is-featured) .news-category-badge {
            background: rgba(255, 255, 255, 0.92);
            color: #1d4ed8;
        }

        .upvote-indicator,
        .comment-indicator {
            position: absolute;
            bottom: 12px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
        }

        .upvote-indicator {
            left: 14px;
        }

        .comment-indicator {
            left: 72px;
        }

        .post-avatar {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.22);
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 7px;
            margin-left: 8px;
        }

        .post-title {
            font-size: 18px;
            line-height: 1.25;
            font-weight: 800;
            color: #111827;
        }

        .post-body {
            color: #1f2937;
            font-size: 13px;
            line-height: 1.75;
        }

        .post-action-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
            align-items: center;
            gap: 8px;
        }

        .widget-sidebar {
            width: 100%;
            position: sticky;
            top: 88px;
        }

        .widget-card {
            border-radius: 10px;
            margin-bottom: 12px;
            padding: 16px;
        }

        .widget-card:hover {
            transform: none;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .event-list {
            display: grid;
            gap: 10px;
        }

        .event-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
        }

        .event-date {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            background: #fff;
        }

        .event-month {
            display: block;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 800;
            line-height: 18px;
            text-transform: uppercase;
        }

        .event-day {
            display: block;
            color: #111827;
            font-size: 16px;
            font-weight: 900;
            line-height: 24px;
        }

        .event-title {
            color: #111827;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.2;
        }

        .event-time {
            color: #6b7280;
            font-size: 11px;
            margin-top: 2px;
        }

        .inspiration-card {
            background:
                linear-gradient(145deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.96)),
                #fff;
        }

        @media (max-width: 1180px) {
            .admin-news-shell {
                grid-template-columns: 1fr;
            }

            .widget-sidebar {
                position: static;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }
        }

        @media (max-width: 820px) {
            .trending-grid,
            .widget-sidebar {
                grid-template-columns: 1fr;
            }

            .trending-grid {
                grid-template-rows: auto;
            }

            .news-image-card.is-featured {
                grid-row: auto;
                min-height: 240px;
            }

            .post-action-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">
    <?php include('sidebar.php'); ?>

    <div class="flex-1 flex flex-col">
        <?php include('header.php'); ?>
        
        <main class="flex-1 p-6 overflow-y-auto">
            <div class="admin-news-shell">
                    <div class="admin-feed-main">
                        <div class="feed-panel">
                            <div class="feed-section-header">
                                <h2 class="feed-heading">
                                    <span class="feed-heading-icon"><i class="fas fa-chart-line"></i></span>
                                    Featured News
                                </h2>
                                <div class="flex items-center gap-2">
                                    <button onclick="refreshNewsImages()" class="feed-link-button" title="Refresh featured news">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button onclick="nextNewsImages()" class="feed-link-button" title="Next news">
                                        <span>View all</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="news-images-container" class="trending-grid">
                                <div class="flex items-center justify-center col-span-full py-8">
                                    <div class="loading-spinner mx-auto"></div>
                                    <p class="text-center text-gray-500 ml-3">Loading trending news...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Create Post -->
                        <div class="facebook-post mb-6">
                            <div class="p-4">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="post-avatar">
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
                                                <span>Photo/Video/Document</span>
                                            </label>
                                            <input type="file" id="attachmentUpload" name="attachments[]" multiple class="hidden" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
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
                                                <div class="post-avatar">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-semibold text-gray-900 text-sm">Admin <span class="admin-badge">Administrator</span></h3>
                                                    <p class="text-xs text-gray-500"><?= date('M j, Y \a\t g:i A', strtotime($a['created_at'])) ?></p>
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
                                            <h2 class="post-title mb-2"><?= htmlspecialchars($a['title']) ?></h2>
                                        <?php endif; ?>
                                        
                                        <!-- Content with See More/Less -->
                                        <div id="content-<?= $aid ?>">
                                            <div id="preview-<?= $aid ?>" class="post-body <?= $isLongContent ? '' : 'hidden' ?>">
                                                <?= nl2br(htmlspecialchars($previewContent)) ?>
                                                <?php if ($isLongContent): ?>
                                                    <button onclick="toggleContent(<?= $aid ?>, true)" class="text-blue-600 hover:text-blue-700 font-medium ml-2">
                                                        See more
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div id="full-<?= $aid ?>" class="post-body <?= $isLongContent ? 'hidden' : '' ?>">
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
                                                             class="w-full h-auto cursor-pointer hover:opacity-90 transition-opacity"
                                                             style="max-height: 500px; object-fit: contain; background: white;"
                                                             onclick="openLightbox('<?= $encodedFileUrl ?>')"
                                                             onerror="console.log('Image failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <!-- Fallback for broken images -->
                                                        <div class="hidden p-4 text-center bg-gray-100">
                                                            <i class="fas fa-image text-gray-400 text-2xl mb-2"></i>
                                                            <p class="text-gray-500 text-sm">Image not available</p>
                                                            <a href="<?= $encodedFileUrl ?>" 
                                                               class="text-blue-600 hover:underline text-sm" 
                                                               download="<?= htmlspecialchars($originalName) ?>">
                                                                Download <?= htmlspecialchars($originalName) ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
                                                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                                                        <div class="flex-1">
                                                            <a href="<?= $encodedFileUrl ?>" 
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
                                        <div class="post-action-row">
                                            <!-- Like Button -->
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
                                                
                                                <button id="react-btn-<?= $aid ?>" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg w-full justify-center <?= $reaction_color ?>" <?= !$current_user_id ? 'disabled title="Please log in to react"' : '' ?>>
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
                                            <button onclick="toggleComments(<?= $aid ?>)" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg w-full justify-center text-gray-600">
                                                <i class="far fa-comment"></i>
                                                <span class="font-medium">Comment</span>
                                            </button>

                                            <span class="text-xs text-gray-400 justify-self-end hidden sm:inline"><?= $commentCount ?> comment<?= $commentCount !== 1 ? 's' : '' ?></span>
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

                    <!-- Sidebar with Widgets -->
                    <div class="widget-sidebar">
                        <!-- Weather Widget -->
                        <div class="widget-card" id="weather-widget">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="widget-title flex items-center">
                                    <i class="fas fa-cloud-sun text-blue-500 mr-2"></i>
                                    Weather
                                </h3>
                                <button onclick="refreshWeather()" class="widget-refresh" title="Refresh weather">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div id="weather-content">
                                <div class="loading-spinner mx-auto"></div>
                                <p class="text-center text-gray-500 mt-2">Loading weather...</p>
                            </div>
                        </div>

                        <!-- Upcoming Events Widget -->
                        <div class="widget-card" id="events-widget">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="widget-title flex items-center">
                                    <i class="fas fa-calendar-day text-red-500 mr-2"></i>
                                    Upcoming Events
                                </h3>
                                <button type="button" class="feed-link-button" title="View all events">
                                    <span>View all</span>
                                </button>
                            </div>
                            <div class="event-list">
                                <?php if (!empty($upcomingEvents)): ?>
                                    <?php foreach ($upcomingEvents as $event): ?>
                                        <?php
                                            $eventDate = $event['schedule_date'];
                                            $holidayType = $event['holiday_type'] ?? '';
                                            $holidayTypeLabels = [
                                                'regular' => 'Regular Holiday',
                                                'special_non_working' => 'Special Non-Working Holiday',
                                                'special_working' => 'Special Working Holiday'
                                            ];
                                            $eventLabel = $holidayTypeLabels[$holidayType] ?? 'Company Holiday';
                                        ?>
                                        <div class="event-item">
                                            <div class="event-date">
                                                <span class="event-month"><?= htmlspecialchars(date('M', strtotime($eventDate))) ?></span>
                                                <span class="event-day"><?= htmlspecialchars(date('d', strtotime($eventDate))) ?></span>
                                            </div>
                                            <div>
                                                <div class="event-title"><?= htmlspecialchars($event['holiday_name'] ?? 'Holiday') ?></div>
                                                <div class="event-time"><?= htmlspecialchars($eventLabel) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500 py-2">No upcoming company holidays found.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- News Widget -->
                        <div class="widget-card" id="news-widget">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="widget-title flex items-center">
                                    <i class="fas fa-newspaper text-red-500 mr-2"></i>
                                    Latest Headlines
                                </h3>
                                <button onclick="refreshNews()" class="feed-link-button" title="Refresh latest news">
                                    <span>View all</span>
                                </button>
                            </div>
                            <div id="news-content">
                                <div class="loading-spinner mx-auto"></div>
                                <p class="text-center text-gray-500 mt-2">Loading news...</p>
                            </div>
                        </div>

                        <!-- Daily Quote Widget -->
                        <div class="widget-card inspiration-card" id="quote-widget">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="widget-title flex items-center">
                                    <i class="fas fa-quote-left text-blue-500 mr-2"></i>
                                    Daily Inspiration
                                </h3>
                                <button onclick="refreshQuote()" class="widget-refresh" title="Refresh quote">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div id="quote-content">
                                <div class="loading-spinner mx-auto"></div>
                                <p class="text-center text-gray-500 mt-2">Loading quote...</p>
                            </div>
                        </div>
                    </div>
                </div>
        </main>
    </div>
</div>

<script>
let reactionTimeouts = {};
let featuredNewsCursor = Number(sessionStorage.getItem('adminFeaturedNewsCursor') || (new Date().getDate() % 6));

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

async function fetchJsonWithTimeout(url, timeoutMs = 7000) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetch(url, {
            signal: controller.signal,
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return await response.json();
    } finally {
        clearTimeout(timeout);
    }
}

async function loadWeather() {
    try {
        const url = 'https://api.open-meteo.com/v1/forecast?latitude=15.185&longitude=120.539&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FManila';
        const data = await fetchJsonWithTimeout(url);
        const current = data.current || {};
        const temp = Math.round(current.temperature_2m ?? 29);
        const condition = getWeatherCondition(current.weather_code);
        const humidity = Math.round(current.relative_humidity_2m ?? 75);
        const wind = Math.round(current.wind_speed_10m ?? 12);
        const updatedLabel = current.time ? `Updated ${new Date(current.time).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}` : 'Updated just now';

        document.getElementById('weather-content').innerHTML = `
            <div>
                <div class="flex items-center justify-center gap-5 mb-4">
                    <div class="weather-icon text-5xl">${getWeatherIcon(condition)}</div>
                    <div class="text-left">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>Clark, Philippines</p>
                        <h4 class="text-3xl font-extrabold text-gray-900 leading-none">${temp}°C</h4>
                        <p class="text-sm font-semibold text-gray-600 capitalize mt-1">${condition}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs text-gray-600 pt-3 border-t border-gray-100">
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-tint text-blue-400"></i>${humidity}%</span>
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-wind text-slate-400"></i>${wind} km/h</span>
                </div>
                <p class="text-xs text-gray-400 text-center mt-4">${updatedLabel}</p>
            </div>
        `;
    } catch (error) {
        console.error('Weather loading error:', error);
        document.getElementById('weather-content').innerHTML = `
            <div>
                <div class="flex items-center justify-center gap-5 mb-4">
                    <div class="weather-icon text-5xl">🌤️</div>
                    <div class="text-left">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>Clark, Philippines</p>
                        <h4 class="text-3xl font-extrabold text-gray-900 leading-none">29°C</h4>
                        <p class="text-sm font-semibold text-gray-600 mt-1">Partly Cloudy</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs text-gray-600 pt-3 border-t border-gray-100">
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-tint text-blue-400"></i>75%</span>
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-wind text-slate-400"></i>12 km/h</span>
                </div>
                <p class="text-xs text-gray-400 text-center mt-4">Showing saved estimate</p>
            </div>
        `;
    }
}

async function loadQuote() {
    const fallbackQuotes = [
        { content: "The only way to do great work is to love what you do.", author: "Steve Jobs" },
        { content: "Innovation distinguishes between a leader and a follower.", author: "Steve Jobs" },
        { content: "Success is not final, failure is not fatal: it is the courage to continue that counts.", author: "Winston Churchill" },
        { content: "The future belongs to those who believe in the beauty of their dreams.", author: "Eleanor Roosevelt" },
        { content: "Excellence is never an accident. It is always the result of high intention, sincere effort, and intelligent execution.", author: "Aristotle" }
    ];

    try {
        const data = await fetchJsonWithTimeout('https://api.quotable.io/random?minLength=50&maxLength=150', 5000);
        document.getElementById('quote-content').innerHTML = `
            <div class="pl-1">
                <p class="quote-text text-gray-800 text-sm mb-4">"${escapeHtml(data.content)}"</p>
                <p class="text-xs text-gray-500 text-center">— ${escapeHtml(data.author)}</p>
            </div>
        `;
    } catch (error) {
        console.error('Quote loading error:', error);
        const randomQuote = fallbackQuotes[new Date().getDate() % fallbackQuotes.length];
        document.getElementById('quote-content').innerHTML = `
            <div class="pl-1">
                <p class="quote-text text-gray-800 text-sm mb-4">"${escapeHtml(randomQuote.content)}"</p>
                <p class="text-xs text-gray-500 text-center">— ${escapeHtml(randomQuote.author)}</p>
            </div>
        `;
    }
}

async function loadNews() {
    const fallbackHeadlines = [
        { title: 'Philippine Economy Shows Steady Growth in Q3', source: 'Business World', date: 'Today', url: 'https://www.bworldonline.com/' },
        { title: 'Clark Freeport Zone Expands Tech Infrastructure', source: 'Tech News PH', date: 'Today', url: 'https://technews.ph/' },
        { title: 'Education Department Launches Digital Learning Initiative', source: 'DepEd Official', date: 'Yesterday', url: 'https://www.deped.gov.ph/' },
        { title: 'Health Ministry Reports Improved Healthcare Access', source: 'DOH Philippines', date: 'Yesterday', url: 'https://www.doh.gov.ph/' },
        { title: 'Central Bank Announces New Digital Payment Guidelines', source: 'BSP', date: '2 days ago', url: 'https://www.bsp.gov.ph/' }
    ];

    const renderHeadlines = (items) => {
        document.getElementById('news-content').innerHTML = items.slice(0, 5).map(item => `
            <div class="news-item" onclick="window.open('${escapeHtml(item.url)}', '_blank')">
                <h5 class="font-semibold text-gray-800 text-xs leading-tight mb-1">${escapeHtml(item.title)}</h5>
                <p class="text-xs text-gray-500">${escapeHtml(item.source)} • ${escapeHtml(item.date)}</p>
            </div>
        `).join('');
    };

    try {
        const rssUrl = encodeURIComponent('https://news.google.com/rss/search?q=Philippines%20business%20Clark%20Pampanga&hl=en-PH&gl=PH&ceid=PH:en');
        const data = await fetchJsonWithTimeout(`https://api.rss2json.com/v1/api.json?rss_url=${rssUrl}`, 7000);

        if (!data.items || !data.items.length) {
            throw new Error('No RSS items returned');
        }

        renderHeadlines(data.items.map(item => ({
            title: item.title,
            source: item.author || 'Google News',
            date: item.pubDate ? new Date(item.pubDate).toLocaleDateString() : 'Today',
            url: item.link
        })));
    } catch (error) {
        console.log('Using fallback news data', error);
        renderHeadlines(fallbackHeadlines);
    }
}

async function loadNewsImages() {
    const fallbackNewsImages = [
        {
            id: 'market',
            title: "Philippine Stock Exchange Reaches New Heights in Technology Sector",
            image: "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=900&h=520&fit=crop&auto=format",
            source: "Business World",
            url: "https://www.bworldonline.com/",
            upvotes: Math.floor(Math.random() * 500) + 100,
            comments: 18,
            category: "Business",
            timeAgo: "2 hours ago"
        },
        {
            id: 'airport',
            title: "Clark International Airport Expansion Project Shows Significant Progress",
            image: "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=900&h=520&fit=crop&auto=format",
            source: "Philippine News Agency",
            url: "https://www.pna.gov.ph/",
            upvotes: Math.floor(Math.random() * 400) + 150,
            comments: 18,
            category: "Business",
            timeAgo: "4 hours ago"
        },
        {
            id: 'digital',
            title: "New Digital Infrastructure Initiative Launched in Metro Manila",
            image: "https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=900&h=520&fit=crop&auto=format",
            source: "Tech News PH",
            url: "https://technews.ph/",
            upvotes: Math.floor(Math.random() * 600) + 200,
            comments: 31,
            category: "Technology",
            timeAgo: "6 hours ago"
        },
        {
            id: 'energy',
            title: "Renewable Energy Projects Boost Philippines' Sustainability Goals",
            image: "https://images.unsplash.com/photo-1466611653911-95081537e5b7?w=900&h=520&fit=crop&auto=format",
            source: "Environmental News",
            url: "https://www.doe.gov.ph/",
            upvotes: Math.floor(Math.random() * 350) + 80,
            comments: 24,
            category: "Environment",
            timeAgo: "8 hours ago"
        },
        {
            id: 'healthcare',
            title: "Healthcare Digitization Program Improves Patient Services Nationwide",
            image: "https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=900&h=520&fit=crop&auto=format",
            source: "DOH Philippines",
            url: "https://www.doh.gov.ph/",
            upvotes: Math.floor(Math.random() * 450) + 120,
            comments: 16,
            category: "Health",
            timeAgo: "12 hours ago"
        },
        {
            id: 'education',
            title: "Education Technology Integration Shows Promising Results in Public Schools",
            image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=900&h=520&fit=crop&auto=format",
            source: "DepEd Official",
            url: "https://www.deped.gov.ph/",
            upvotes: Math.floor(Math.random() * 300) + 90,
            comments: 12,
            category: "Education",
            timeAgo: "1 day ago"
        }
    ];

    const selectedNews = [0, 1, 2].map(offset => fallbackNewsImages[(featuredNewsCursor + offset) % fallbackNewsImages.length]);
    sessionStorage.setItem('adminFeaturedNewsCursor', String(featuredNewsCursor));

    document.getElementById('news-images-container').innerHTML = selectedNews.map((news, index) => {
        const sizeClass = index === 0 ? 'is-featured' : 'is-compact';
        const badge = index === 0 ? 'Featured' : news.category;
        const imageUrl = `${news.image}&sig=${encodeURIComponent(news.id)}-${featuredNewsCursor}`;
        return `
            <div class="news-image-card ${sizeClass}" onclick="openNewsArticle(event, '${escapeHtml(news.url)}', '${escapeHtml(news.title)}')">
                <div class="news-category-badge">${escapeHtml(badge)}</div>
                <div class="upvote-indicator"><i class="fas fa-thumbs-up"></i>${news.upvotes}</div>
                <div class="comment-indicator"><i class="far fa-comment"></i>${news.comments}</div>
                <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(news.title)}" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=900&h=520&fit=crop&auto=format'">
                <div class="news-image-overlay">
                    <h3 class="news-image-title">${escapeHtml(news.title)}</h3>
                    <div class="news-image-source"><i class="fas fa-newspaper"></i> ${escapeHtml(news.source)} • ${escapeHtml(news.timeAgo)}</div>
                </div>
            </div>
        `;
    }).join('');
}

function openNewsArticle(event, url, title) {
    const clickedCard = event.currentTarget;
    clickedCard.style.transform = 'scale(0.98)';
    setTimeout(() => {
        clickedCard.style.transform = '';
    }, 150);
    window.open(url, '_blank');
    console.log(`Clicked news article: ${title}`);
}

function refreshNewsImages() {
    featuredNewsCursor = (featuredNewsCursor + 3) % 6;
    document.getElementById('news-images-container').innerHTML = `
        <div class="flex items-center justify-center col-span-full py-8">
            <div class="loading-spinner mx-auto"></div>
            <p class="text-center text-gray-500 ml-3">Refreshing news...</p>
        </div>
    `;
    setTimeout(loadNewsImages, 500);
}

function nextNewsImages() {
    featuredNewsCursor = (featuredNewsCursor + 1) % 6;
    document.getElementById('news-images-container').innerHTML = `
        <div class="flex items-center justify-center col-span-full py-8">
            <div class="loading-spinner mx-auto"></div>
            <p class="text-center text-gray-500 ml-3">Loading next news...</p>
        </div>
    `;
    setTimeout(loadNewsImages, 350);
}

function getWeatherIcon(condition) {
    condition = condition.toLowerCase();
    if (condition.includes('sunny') || condition.includes('clear')) return '☀️';
    if (condition.includes('cloud')) return '☁️';
    if (condition.includes('rain')) return '🌧️';
    if (condition.includes('storm') || condition.includes('thunder')) return '⛈️';
    if (condition.includes('snow')) return '❄️';
    if (condition.includes('fog') || condition.includes('mist')) return '🌫️';
    return '🌤️';
}

function getWeatherCondition(code) {
    const weatherCodes = {
        0: 'Clear',
        1: 'Mostly Clear',
        2: 'Partly Cloudy',
        3: 'Cloudy',
        45: 'Fog',
        48: 'Fog',
        51: 'Drizzle',
        53: 'Drizzle',
        55: 'Drizzle',
        61: 'Rain',
        63: 'Rain',
        65: 'Heavy Rain',
        80: 'Rain Showers',
        81: 'Rain Showers',
        82: 'Heavy Rain Showers',
        95: 'Thunderstorm',
        96: 'Thunderstorm',
        99: 'Thunderstorm'
    };

    return weatherCodes[Number(code)] || 'Partly Cloudy';
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

// Load all widgets when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadNewsImages();
    loadWeather();
    loadQuote();
    loadNews();
    
    // Auto-refresh every 30 minutes
    setInterval(() => {
        loadWeather();
        loadNews();
    }, 30 * 60 * 1000);
    
    // Refresh quote every hour
    setInterval(() => {
        loadQuote();
    }, 60 * 60 * 1000);
});

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
const attachmentUpload = document.getElementById('attachmentUpload');
const documentUpload = document.getElementById('documentUpload');

if (attachmentUpload) {
    attachmentUpload.addEventListener('change', handleFilePreview);
}

if (documentUpload) {
    documentUpload.addEventListener('change', handleFilePreview);
}

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

// Lightbox functionality
function openLightbox(src) {
    // Create lightbox if it doesn't exist
    let lightbox = document.getElementById('image-lightbox');
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'image-lightbox';
        lightbox.className = 'fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden';
        lightbox.innerHTML = `
            <span class="absolute top-5 right-5 text-white text-3xl cursor-pointer hover:text-gray-300" onclick="closeLightbox()">×</span>
            <div class="max-h-[90vh] max-w-[90vw] relative" onclick="event.stopPropagation()">
                <img id="lightbox-img" src="" class="max-h-[90vh] max-w-[90vw] rounded shadow-xl" alt="Expanded Image">
            </div>
        `;
        lightbox.onclick = closeLightbox;
        document.body.appendChild(lightbox);
    }
    
    const img = document.getElementById('lightbox-img');
    img.src = src;
    lightbox.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('image-lightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
        document.getElementById('lightbox-img').src = '';
        document.body.style.overflow = '';
    }
}

// Close lightbox with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>

</body>
</html>
