<?php

$current_user_id = $_SESSION['employee']['id'] ?? null;
$isDemoMode = !empty($_SESSION['demo_mode']);

function newsFeedTableExists(PDO $pdo, string $table): bool {
    static $tableCache = [];

    if (!array_key_exists($table, $tableCache)) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $tableCache[$table] = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Unable to inspect table $table for news feed: " . $e->getMessage());
            $tableCache[$table] = false;
        }
    }

    return $tableCache[$table];
}

function newsFeedTableColumnExists(PDO $pdo, string $table, string $column): bool {
    static $columnsByTable = [];

    if (!isset($columnsByTable[$table])) {
        $columnsByTable[$table] = [];
        try {
            $safeTable = str_replace('`', '``', $table);
            $stmt = $pdo->query("SHOW COLUMNS FROM `$safeTable`");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columnsByTable[$table][$row['Field']] = true;
            }
        } catch (PDOException $e) {
            error_log("Unable to inspect $table columns for news feed: " . $e->getMessage());
        }
    }

    return isset($columnsByTable[$table][$column]);
}

// Fetch announcements. Demo mode intentionally hides internal announcements.
$newsPage = max(1, (int)($_GET['news_page'] ?? 1));
$newsPerPage = 15;
$newsTotal = 0;
$newsTotalPages = 1;
if ($isDemoMode) {
    $announcements = [];
} else {
    $newsTotal = (int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE deleted = 0")->fetchColumn();
    $newsTotalPages = max(1, (int)ceil($newsTotal / $newsPerPage));
    $newsPage = min($newsPage, $newsTotalPages);
    $newsOffset = ($newsPage - 1) * $newsPerPage;
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE deleted = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $newsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $newsOffset, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Batch-load reactions and comments for the current page. Previously each post
// issued three additional queries while rendering.
$type_to_emoji = [
    'like' => '👍', 'love' => '❤️', 'laugh' => '😂',
    'wow' => '😮', 'sad' => '😢', 'angry' => '😡'
];
$reactionsByAnnouncement = [];
$userReactionByAnnouncement = [];
$commentsByAnnouncement = [];
$announcementIds = array_values(array_map('intval', array_column($announcements, 'announcement_id')));
if ($announcementIds) {
    $announcementPlaceholders = implode(',', array_fill(0, count($announcementIds), '?'));

    $reactionStmt = $pdo->prepare("
        SELECT announcement_id, reaction_type, COUNT(*) AS reaction_count
        FROM post_reactions
        WHERE announcement_id IN ({$announcementPlaceholders})
        GROUP BY announcement_id, reaction_type
    ");
    $reactionStmt->execute($announcementIds);
    foreach ($reactionStmt->fetchAll(PDO::FETCH_ASSOC) as $reactionRow) {
        $announcementId = (int)$reactionRow['announcement_id'];
        $emoji = $type_to_emoji[$reactionRow['reaction_type']] ?? '👍';
        $reactionsByAnnouncement[$announcementId][$emoji] = (int)$reactionRow['reaction_count'];
    }

    if ($current_user_id) {
        $userReactionStmt = $pdo->prepare("
            SELECT announcement_id, reaction_type
            FROM post_reactions
            WHERE employee_id = ? AND announcement_id IN ({$announcementPlaceholders})
        ");
        $userReactionStmt->execute(array_merge([(int)$current_user_id], $announcementIds));
        foreach ($userReactionStmt->fetchAll(PDO::FETCH_ASSOC) as $reactionRow) {
            $userReactionByAnnouncement[(int)$reactionRow['announcement_id']] =
                $type_to_emoji[$reactionRow['reaction_type']] ?? '👍';
        }
    }

    $commentsDeletedWhere = newsFeedTableColumnExists($pdo, 'comments', 'deleted') ? 'AND c.deleted = 0' : '';
    $commentsStmt = $pdo->prepare("
        SELECT c.*, e.fname, e.lname
        FROM comments c
        JOIN employees e ON e.id = c.employee_id
        WHERE c.announcement_id IN ({$announcementPlaceholders}) {$commentsDeletedWhere}
        ORDER BY c.created_at ASC
    ");
    $commentsStmt->execute($announcementIds);
    foreach ($commentsStmt->fetchAll(PDO::FETCH_ASSOC) as $commentRow) {
        $commentsByAnnouncement[(int)$commentRow['announcement_id']][] = $commentRow;
    }
}

$upcomingEvents = [];
$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+90 days'));

try {
    if ($current_user_id && newsFeedTableExists($pdo, 'employee_daily_schedule_cache')) {
        $cacheTable = 'employee_daily_schedule_cache';
        $hasHolidayName = newsFeedTableColumnExists($pdo, $cacheTable, 'holiday_name');
        $holidayNameSelect = $hasHolidayName ? 'holiday_name' : "'Holiday' AS holiday_name";
        $holidayTypeSelect = newsFeedTableColumnExists($pdo, $cacheTable, 'holiday_type') ? 'holiday_type' : "NULL AS holiday_type";
        $isHolidayWhere = newsFeedTableColumnExists($pdo, $cacheTable, 'is_holiday') ? 'AND is_holiday = 1' : '';
        $holidayNameWhere = $hasHolidayName ? 'AND holiday_name IS NOT NULL' : '';

        $eventStmt = $pdo->prepare("
            SELECT schedule_date, $holidayNameSelect, $holidayTypeSelect
            FROM employee_daily_schedule_cache
            WHERE employee_id = ?
              AND schedule_date BETWEEN ? AND ?
              $isHolidayWhere
              $holidayNameWhere
            ORDER BY schedule_date ASC
            LIMIT 3
        ");
        $eventStmt->execute([$current_user_id, $today, $endDate]);
        $upcomingEvents = $eventStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Unable to load employee upcoming holidays for news feed: " . $e->getMessage());
    $upcomingEvents = [];
}

if (empty($upcomingEvents) && newsFeedTableExists($pdo, 'company_holidays')) {
    try {
        $fallbackEventStmt = $pdo->prepare("
            SELECT holiday_date AS schedule_date, holiday_name, holiday_type
            FROM company_holidays
            WHERE holiday_date BETWEEN ? AND ?
            ORDER BY holiday_date ASC
            LIMIT 3
        ");
        $fallbackEventStmt->execute([$today, $endDate]);
        $upcomingEvents = $fallbackEventStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Unable to load fallback company holidays for news feed: " . $e->getMessage());
    }
}
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
    
    /* Reddit-style news image cards */
    .news-image-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .news-image-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: #3b82f6;
    }
    
    .news-image-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .news-image-card:hover img {
        transform: scale(1.05);
    }
    
    .news-image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
        padding: 20px 16px 16px;
        color: white;
    }
    
    .news-image-title {
        font-weight: 600;
        font-size: 0.875rem;
        line-height: 1.25;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .news-image-source {
        font-size: 0.75rem;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .news-image-card .upvote-indicator {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #059669;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .news-feed-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 18px;
        align-items: start;
        max-width: 1460px;
        margin: 0 auto;
        color: #111827;
    }

    .feed-main {
        min-width: 0;
    }

    .feed-panel,
    .widget-card,
    .facebook-post {
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .feed-panel {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
    }

    .feed-section-header {
        min-height: 48px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #eef2f7;
    }

    .feed-heading {
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

    .feed-link-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 0;
        background: transparent;
        color: #2563eb;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .trending-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.42fr) minmax(280px, 0.78fr);
        grid-template-rows: repeat(2, 138px);
        gap: 14px;
        padding: 14px 16px 16px;
    }

    .news-image-card {
        min-height: 164px;
        border-radius: 8px;
        box-shadow: none;
        border: 0;
    }

    .news-image-card img {
        height: 100%;
    }

    .news-image-card.is-featured {
        grid-row: span 2;
        min-height: 290px;
    }

    .news-image-card.is-featured .news-image-title {
        font-size: 22px;
        line-height: 1.1;
        max-width: 90%;
    }

    .news-image-card.is-compact {
        min-height: 138px;
    }

    .news-image-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        border-color: transparent;
    }

    .news-image-overlay {
        padding: 48px 14px 38px;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0), rgba(15, 23, 42, 0.88));
    }

    .news-image-title {
        font-size: 14px;
        line-height: 1.18;
        text-shadow: 0 1px 8px rgba(0, 0, 0, 0.45);
    }

    .news-image-source {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.86);
    }

    .news-category-badge {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        border-radius: 6px;
        background: #2563eb;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.02em;
        padding: 4px 8px;
        text-transform: uppercase;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
    }

    .news-image-card:not(.is-featured) .news-category-badge {
        background: rgba(255, 255, 255, 0.92);
        color: #1d4ed8;
    }

    .news-image-card .upvote-indicator {
        top: auto;
        bottom: 12px;
        left: 14px;
        border-radius: 999px;
        padding: 2px 0;
        color: #fff;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
        font-size: 11px;
    }

    .news-image-card .comment-indicator {
        position: absolute;
        bottom: 12px;
        left: 72px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
    }

    #news-feed-container {
        padding-right: 4px;
    }

    .facebook-post {
        border-radius: 10px;
        margin-bottom: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .facebook-post:hover {
        transform: none;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
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

    .reaction-button {
        min-height: 36px;
        font-size: 12px;
    }

    .widget-sidebar {
        width: 100%;
        position: sticky;
        top: 88px;
    }

    .widget-card {
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        margin-bottom: 12px;
        padding: 16px;
    }

    .widget-card:hover {
        transform: none;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .widget-title {
        font-size: 14px;
        font-weight: 800;
        color: #111827;
    }

    .widget-refresh {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: #64748b;
    }

    .widget-refresh:hover {
        background: #f8fafc;
        color: #2563eb;
    }

    .news-item {
        padding: 9px 0;
    }

    .news-item:hover {
        padding: 9px 10px;
        margin: 0 -10px;
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
        .news-feed-shell {
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

        
<div class="news-feed-shell">
    <!-- Main Content -->
    <div class="feed-main">
        
        <!-- Reddit-style News Images Section -->
        <div class="mb-4">
            <div class="feed-panel">
                <div class="feed-section-header">
                    <h2 class="feed-heading">
                        <span class="feed-heading-icon"><i class="fas fa-chart-line"></i></span>
                        Featured News
                    </h2>
                    <div class="flex items-center gap-2">
                        <button onclick="refreshNewsImages()" class="feed-link-button" title="Refresh trending news">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button onclick="nextNewsImages()" class="feed-link-button" title="Next news">
                            <span>View all</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div id="news-images-container" class="trending-grid">
                    <!-- Loading placeholder -->
                    <div class="flex items-center justify-center col-span-full py-8">
                        <div class="loading-spinner mx-auto"></div>
                        <p class="text-center text-gray-500 ml-3">Loading trending news...</p>
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

                    $reactions = $reactionsByAnnouncement[(int)$aid] ?? [];
                    $user_reaction = $userReactionByAnnouncement[(int)$aid] ?? null;
                    $comments = $commentsByAnnouncement[(int)$aid] ?? [];
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
                                        <h3 class="font-semibold text-gray-900 text-sm">
                                            <?= htmlspecialchars($a['admin_name']) ?>
                                            <span class="admin-badge">Administrator</span>
                                        </h3>
                                        <p class="text-xs text-gray-500"><?= date('M j, Y \a\t g:i A', strtotime($a['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 text-gray-400">
                                    <button type="button" class="hover:text-blue-600" title="Pin announcement">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                    <button type="button" class="hover:text-gray-700" title="More options">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
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
                                            Read more
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
                                    
                                    // Remove 'uploads/' prefix if present to avoid duplication
                                    $cleanFileName = basename($filePath);
                                    
                                    // Try multiple possible paths for compatibility
                                    // Detect if we're on production or local
                                    $isProduction = (strpos($_SERVER['HTTP_HOST'] ?? '', 'resourcestaffonline.com') !== false);
                                    
                                    if ($isProduction) {
                                        // Production server - try relative path from document root
                                        $fileUrl = '/Public/views/uploads/' . $cleanFileName;
                                    } else {
                                        // Local development (XAMPP)
                                        $fileUrl = '/Timekeeping-system/Public/views/uploads/' . $cleanFileName;
                                    }
                                    
                                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                                    $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'wmv', 'mpeg', '3gp']);
                                    
                                    // Get file size if possible
                                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $fileUrl;
                                    $fileSize = '';
                                    $fileExists = is_file($fullPath);
                                    if ($fileExists) {
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
                                    <?php if (!$fileExists): ?>
                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border text-gray-500">
                                            <i class="fas fa-file-circle-xmark text-gray-400 text-xl"></i>
                                            <div>
                                                <p class="text-sm font-medium"><?= htmlspecialchars($originalName) ?></p>
                                                <p class="text-xs">Attachment is no longer available on the server.</p>
                                            </div>
                                        </div>
                                    <?php elseif ($isVideo): ?>
                                        <div class="rounded-lg overflow-hidden border border-gray-200 bg-black">
                                            <video controls preload="metadata" class="w-full" style="max-height: 500px;"
                                                   onerror="console.error('Video failed to load:', this.querySelector('source').src); this.nextElementSibling.style.display='block';"
                                                   onloadeddata="console.log('Video loaded successfully');">
                                                <source src="<?= htmlspecialchars($fileUrl) ?>" type="video/<?= $ext === 'mov' ? 'quicktime' : ($ext === 'avi' ? 'x-msvideo' : ($ext === 'wmv' ? 'x-ms-wmv' : $ext)) ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                            <!-- Fallback if video fails -->
                                            <div class="hidden p-4 text-center bg-gray-700">
                                                <i class="fas fa-exclamation-triangle text-yellow-400 text-2xl mb-2"></i>
                                                <p class="text-white text-sm mb-2">Video could not be loaded</p>
                                                <p class="text-gray-400 text-xs mb-3">Path: <?= htmlspecialchars($fileUrl) ?></p>
                                                <a href="<?= htmlspecialchars($fileUrl) ?>" 
                                                   class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" 
                                                   download="<?= htmlspecialchars($originalName) ?>">
                                                    <i class="fas fa-download mr-2"></i>Download Video
                                                </a>
                                            </div>
                                            <div class="p-2 bg-gray-800 text-sm text-gray-300 flex items-center justify-between">
                                                <span><i class="fas fa-video mr-2"></i><?= htmlspecialchars($originalName) ?></span>
                                                <?php if ($fileSize): ?>
                                                    <span class="text-gray-400"><?= $fileSize ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif ($isImage): ?>
                                        <div class="rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                            <img src="<?= htmlspecialchars($fileUrl) ?>"
                                                 loading="lazy" decoding="async"
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
                            <div class="post-action-row">
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
                                    
                                    <button id="react-btn-<?= $aid ?>" class="reaction-button flex items-center space-x-2 px-4 py-2 rounded-lg w-full justify-center <?= $reaction_color ?>" <?= !$current_user_id ? 'disabled title="Please log in to react"' : '' ?>>
                                        <span class="text-lg" id="react-emoji-<?= $aid ?>"><?= $user_reaction ?: '👍' ?></span>
                                        <span class="font-semibold" id="react-text-<?= $aid ?>"><?= $reaction_text ?></span>
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
                                    <span class="font-semibold">Comment</span>
                                </button>

                                <span class="text-xs text-gray-400 justify-self-end hidden sm:inline" id="comments-count-inline-<?= $aid ?>"><?= $commentCount ?> comment<?= $commentCount !== 1 ? 's' : '' ?></span>
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

            <?php if ($newsTotalPages > 1): ?>
                <nav class="flex items-center justify-center gap-2 mt-6" aria-label="Announcement pages">
                    <?php if ($newsPage > 1): ?>
                        <a class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
                           href="?view=news&news_page=<?= $newsPage - 1 ?>">Previous</a>
                    <?php endif; ?>
                    <span class="px-3 py-2 text-sm text-gray-600">Page <?= $newsPage ?> of <?= $newsTotalPages ?></span>
                    <?php if ($newsPage < $newsTotalPages): ?>
                        <a class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50"
                           href="?view=news&news_page=<?= $newsPage + 1 ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

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
                            $eventLabel = $holidayTypeLabels[$holidayType] ?? 'Employee Holiday';
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
                    <div class="text-sm text-gray-500 py-2">No upcoming employee holidays found.</div>
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


<script>
let reactionTimeouts = {};
let featuredNewsCursor = Number(sessionStorage.getItem('featuredNewsCursor') || (new Date().getDate() % 6));

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
        const location = 'Clark, Philippines';
        const updatedLabel = current.time ? `Updated ${new Date(current.time).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}` : 'Updated just now';

        document.getElementById('weather-content').innerHTML = `
            <div>
                <div class="flex items-center justify-center gap-5 mb-4">
                    <div class="weather-icon text-5xl">${getWeatherIcon(condition)}</div>
                    <div class="text-left">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>${location}</p>
                        <h4 class="text-3xl font-extrabold text-gray-900 leading-none">${temp}°C</h4>
                        <p class="text-sm font-semibold text-gray-600 capitalize mt-1">${condition}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs text-gray-600 pt-3 border-t border-gray-100">
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-tint text-blue-400"></i>${humidity}%</span>
                    <span class="flex items-center justify-center gap-2"><i class="fas fa-wind text-slate-400"></i>${wind} km/h</span>
                </div>
                <p class="text-[10px] text-gray-400 text-center mt-4">${updatedLabel}</p>
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
                <p class="text-[10px] text-gray-400 text-center mt-4">Showing saved estimate</p>
            </div>
        `;
    }
}

function loadQuote() {
    const fallbackQuotes = [
        { content: "The only way to do great work is to love what you do.", author: "Steve Jobs" },
        { content: "Innovation distinguishes between a leader and a follower.", author: "Steve Jobs" },
        { content: "Success is not final, failure is not fatal: it is the courage to continue that counts.", author: "Winston Churchill" },
        { content: "The future belongs to those who believe in the beauty of their dreams.", author: "Eleanor Roosevelt" },
        { content: "Excellence is never an accident. It is always the result of high intention, sincere effort, and intelligent execution.", author: "Aristotle" }
    ];

    const randomQuote = fallbackQuotes[Math.floor(Math.random() * fallbackQuotes.length)];
    document.getElementById('quote-content').innerHTML = `
        <div class="pl-1">
            <p class="quote-text text-gray-800 text-sm mb-4">"${escapeHtml(randomQuote.content)}"</p>
            <p class="text-xs text-gray-500 text-center">— ${escapeHtml(randomQuote.author)}</p>
        </div>
    `;
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

// Reddit-style News Images Loader
async function loadNewsImages() {
    try {
        const fallbackNewsImages = [
            {
                id: 'market',
                title: "Philippine Stock Exchange Reaches New Heights in Technology Sector",
                image: "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=400&h=300&fit=crop&auto=format",
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
                image: "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=400&h=300&fit=crop&auto=format",
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
                image: "https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=400&h=300&fit=crop&auto=format",
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
                image: "https://images.unsplash.com/photo-1466611653911-95081537e5b7?w=400&h=300&fit=crop&auto=format",
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
                image: "https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=400&h=300&fit=crop&auto=format",
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
                image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=300&fit=crop&auto=format",
                source: "DepEd Official",
                url: "https://www.deped.gov.ph/",
                upvotes: Math.floor(Math.random() * 300) + 90,
                comments: 12,
                category: "Education",
                timeAgo: "1 day ago"
            }
        ];

        const selectedNews = [0, 1, 2].map(offset => fallbackNewsImages[(featuredNewsCursor + offset) % fallbackNewsImages.length]);
        sessionStorage.setItem('featuredNewsCursor', String(featuredNewsCursor));

        let newsHtml = '';
        selectedNews.forEach((news, index) => {
            const sizeClass = index === 0 ? 'is-featured' : 'is-compact';
            const badge = index === 0 ? 'Featured' : news.category;
            const imageUrl = `${news.image}&sig=${encodeURIComponent(news.id)}`;
            newsHtml += `
                <div class="news-image-card ${sizeClass}" onclick="openNewsArticle(event, '${news.url}', '${news.title.replace(/'/g, "\\'")}')">
                    <div class="news-category-badge">${badge}</div>
                    <div class="upvote-indicator">
                        <i class="fas fa-thumbs-up"></i>
                        ${news.upvotes}
                    </div>
                    <div class="comment-indicator">
                        <i class="far fa-comment"></i>
                        ${news.comments}
                    </div>
                    <img src="${imageUrl}" alt="${news.title}" onerror="this.src='https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=400&h=300&fit=crop&auto=format'">
                    <div class="news-image-overlay">
                        <h3 class="news-image-title">${news.title}</h3>
                        <div class="news-image-source">
                            <i class="fas fa-newspaper"></i>
                            ${news.source} • ${news.timeAgo}
                        </div>
                    </div>
                </div>
            `;
        });

        document.getElementById('news-images-container').innerHTML = newsHtml;
    } catch (error) {
        console.error('News images loading error:', error);
        document.getElementById('news-images-container').innerHTML = `
            <div class="col-span-full text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-2"></i>
                <p class="text-gray-500">Unable to load news images</p>
            </div>
        `;
    }
}

// Function to handle news article clicks
function openNewsArticle(event, url, title) {
    // Add click animation
    const clickedCard = event.currentTarget;
    clickedCard.style.transform = 'scale(0.95)';
    setTimeout(() => {
        clickedCard.style.transform = '';
    }, 150);

    // Open article in new tab
    window.open(url, '_blank');
    
    // Optional: Track clicks for analytics
    console.log(`Clicked news article: ${title}`);
}

// Refresh function for news images
function refreshNewsImages() {
    featuredNewsCursor = (featuredNewsCursor + 3) % 6;
    document.getElementById('news-images-container').innerHTML = `
        <div class="flex items-center justify-center col-span-full py-8">
            <div class="loading-spinner mx-auto"></div>
            <p class="text-center text-gray-500 ml-3">Refreshing news...</p>
        </div>
    `;
    
    setTimeout(() => {
        loadNewsImages();
    }, 1000); // Add slight delay for better UX
}

// Next news function for navigation
function nextNewsImages() {
    featuredNewsCursor = (featuredNewsCursor + 1) % 6;
    document.getElementById('news-images-container').innerHTML = `
        <div class="flex items-center justify-center col-span-full py-8">
            <div class="loading-spinner mx-auto"></div>
            <p class="text-center text-gray-500 ml-3">Loading next news...</p>
        </div>
    `;
    
    setTimeout(() => {
        loadNewsImages();
    }, 800); // Slightly faster than refresh for better UX
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
    loadNewsImages(); // Load the Reddit-style news images
    
    // Auto-refresh every 30 minutes for weather and news
    setInterval(() => {
        console.log('Auto-refreshing weather and news...');
        loadWeather();
        loadNews();
        loadNewsImages(); // Also refresh news images
    }, 30 * 60 * 1000);
    
    // Refresh quote every hour
    setInterval(() => {
        console.log('Auto-refreshing quote...');
        loadQuote();
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

                const inlineCommentCount = document.getElementById(`comments-count-inline-${announcementId}`);
                if (inlineCommentCount) {
                    inlineCommentCount.textContent = `${newCount} comment${newCount !== 1 ? 's' : ''}`;
                }
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

