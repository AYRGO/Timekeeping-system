<?php

// Get announcement counts
try {
    // Total announcements
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE deleted = 0");
    $totalStmt->execute();
    $totalAnnouncements = $totalStmt->fetchColumn();
    
    // Check if user_read_announcements table exists, if not create it
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_read_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        announcement_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_announcement (user_id, announcement_id),
        FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (announcement_id) REFERENCES announcements(announcement_id) ON DELETE CASCADE
    )");
    
    // Get current user ID
    $currentUserId = $_SESSION['employee']['id'] ?? null;
    
    if ($currentUserId) {
        // Count read announcements for current user
        $readStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT a.announcement_id) 
            FROM announcements a 
            INNER JOIN user_read_announcements ura ON a.announcement_id = ura.announcement_id 
            WHERE a.deleted = 0 AND ura.user_id = ?
        ");
        $readStmt->execute([$currentUserId]);
        $readAnnouncements = $readStmt->fetchColumn();
        
        // Calculate unread
        $unreadAnnouncements = $totalAnnouncements - $readAnnouncements;
    } else {
        $readAnnouncements = 0;
        $unreadAnnouncements = $totalAnnouncements;
    }
    
} catch (PDOException $e) {
    $totalAnnouncements = 0;
    $readAnnouncements = 0;
    $unreadAnnouncements = 0;
}
?>

<div class="rounded-2xl p-8 text-white mb-8 shadow-2xl -mt-10 relative overflow-hidden"
     style="background: linear-gradient(135deg, rgb(16, 185, 72) 0%, rgb(5, 101, 211) 100%);">
    
    <!-- Animated background elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-10 -right-10 w-60 h-60 bg-white bg-opacity-5 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/4 w-20 h-20 bg-white bg-opacity-10 rounded-full animate-ping" style="animation-duration: 2s;"></div>
        <div class="absolute top-1/4 right-1/3 w-16 h-16 bg-white bg-opacity-5 rounded-full animate-pulse" style="animation-duration: 4s;"></div>
        <div class="absolute bottom-1/4 left-1/3 w-12 h-12 bg-white bg-opacity-10 rounded-full animate-ping" style="animation-duration: 3s;"></div>
    </div>
    
    <div class="relative z-10">
        <div class="flex justify-between items-center">
            <div class="flex-1">
                <!-- Main greeting -->
                <div class="mb-8">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">
                        Welcome back, <?= htmlspecialchars($fname) ?> 
                        <span class="inline-block animate-wave" style="animation-duration: 2s;">👋</span>
                    </h1>
                    
                    <p class="text-lg md:text-xl font-light opacity-90 leading-relaxed">
                        Ready to stay connected with your team
                    </p>
                </div>
                
                <!-- Action buttons -->
                <div class="flex flex-wrap gap-4">
                    <?php if ($unreadAnnouncements > 0): ?>
                        <button
                            class="bg-white text-blue-800 px-7 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl flex items-center space-x-3 group"
                            onclick="handleUnreadClick(this)"
                            id="unreadBtn"
                        >
                            <div class="relative">
                                <i class="fas fa-bell text-lg group-hover:animate-pulse"></i>
                                <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold animate-bounce">
                                    <?= $unreadAnnouncements > 9 ? '9+' : $unreadAnnouncements ?>
                                </span>
                            </div>
                            <span class="text-base">You have <?= $unreadAnnouncements ?> unread message<?= $unreadAnnouncements == 1 ? '' : 's' ?></span>
                        </button>
                    <?php else: ?>
                        <button
                            class="bg-white bg-opacity-20 text-white px-7 py-3 rounded-xl font-semibold hover:bg-opacity-30 transition-all duration-300 transform hover:scale-105 flex items-center space-x-3 border-2 border-white border-opacity-30"
                            onclick="showSection('newsFeedView')"
                            id="caughtUpBtn"
                        >
                            <i class="fas fa-check-circle text-lg text-green-300"></i>
                            <span class="text-base">All caught up! View News Feed</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right side illustration -->
            <div class="hidden lg:flex items-center justify-center ml-12">
                <div class="relative">
                    <!-- Main illustration -->
                    <div class="relative bg-white bg-opacity-10 rounded-full p-6 backdrop-blur-sm border border-white border-opacity-20">
                        <i class="fas fa-newspaper text-7xl text-white opacity-80"></i>
                        
                        <!-- Floating notification badge -->
                        <?php if ($unreadAnnouncements > 0): ?>
                            <div class="absolute -top-3 -right-3 bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-base font-bold animate-bounce shadow-lg">
                                <?= $unreadAnnouncements > 99 ? '99+' : $unreadAnnouncements ?>
                            </div>
                        <?php else: ?>
                            <div class="absolute -top-3 -right-3 bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg">
                                <i class="fas fa-check text-base"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Floating mini elements -->
                        <div class="absolute -top-2 -left-2 w-6 h-6 bg-yellow-300 rounded-full animate-ping opacity-50"></div>
                        <div class="absolute -bottom-2 -right-2 w-4 h-4 bg-blue-300 rounded-full animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom animations -->
<style>
@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.animate-wave {
    animation: wave 2s ease-in-out infinite;
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Enhanced hover effects */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Backdrop blur support */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

/* Pulse animation for bell icon */
.group:hover .group-hover\:animate-pulse {
    animation: pulse 1s ease-in-out infinite;
}

/* Smooth transitions */
* {
    transition: all 0.3s ease;
}

/* Loading state for mark all button */
.loading {
    opacity: 0.7;
    pointer-events: none;
}
</style>

<script>
// Function to mark all announcements as read
function markAllAsRead() {
    const btn = document.getElementById('markAllBtn');
    if (!btn) return;
    
    if (confirm('Mark all announcements as read?')) {
        // Show loading state
        btn.classList.add('loading');
        const originalContent = btn.innerHTML;
        btn.innerHTML = `
            <i class="fas fa-spinner fa-spin text-sm"></i>
            <span class="text-sm">Marking...</span>
        `;
        
        // Updated path - remove 'module/' since we're already in the module folder
        fetch('mark_all_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_read',
                csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success briefly
                btn.innerHTML = `
                    <i class="fas fa-check text-sm"></i>
                    <span class="text-sm">Done!</span>
                `;
                
                // Reload after a short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to mark announcements as read: ' + error.message);
            
            // Restore button
            btn.classList.remove('loading');
            btn.innerHTML = originalContent;
        });
    }
}

// Function to mark individual announcement as read when viewing
function markAnnouncementAsRead(announcementId) {
    if (!announcementId) return;
    
    // Updated path
    fetch('mark_announcement_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            announcement_id: announcementId,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Announcement marked as read:', announcementId);
            // Optionally update UI elements here
        } else {
            console.error('Failed to mark announcement as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error marking as read:', error);
    });
}

function handleUnreadClick(btn) {
    btn.classList.add('loading');
    const originalContent = btn.innerHTML;
    btn.innerHTML = `
        <i class="fas fa-spinner fa-spin text-sm"></i>
        <span class="text-sm">Marking...</span>
    `;
    fetch('mark_all_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read',
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Change button to caught up state
            btn.classList.remove('loading');
            btn.className = "bg-white bg-opacity-20 text-white px-7 py-3 rounded-xl font-semibold hover:bg-opacity-30 transition-all duration-300 transform hover:scale-105 flex items-center space-x-3 border-2 border-white border-opacity-30";
            btn.innerHTML = `
                <i class="fas fa-check-circle text-lg text-green-300"></i>
                <span class="text-base">All caught up! View News Feed</span>
            `;
            setTimeout(() => {
                showSection('newsFeedView');
            }, 500);
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    })
    .catch(error => {
        alert('Failed to mark announcements as read: ' + error.message);
        btn.classList.remove('loading');
        btn.innerHTML = originalContent;
    });
}
</script>