<?php
/**
 * Generate Special Access Links for Scott - Enhanced Version
 * Admin tool to create secure access links with permanent and temporary options
 */

require_once '../../../config/db.php';

// Handle link generation request
if ($_POST['action'] ?? false) {
    $linkType = $_POST['link_type'] ?? 'temporary';
    
    if ($linkType === 'permanent') {
        // Generate permanent token (valid indefinitely)
        $secret = 'scott-ot-approval-bugardi-permanent-2025';
        $permanentToken = hash('sha256', 'scott-permanent' . $secret);
        $generatedLink = getPermanentLink($permanentToken);
        $linkExpiry = 'Never expires';
        $linkDescription = 'Permanent Access Link';
    } else {
        // Generate temporary token (valid for 24 hours)
        $secret = 'scott-ot-approval-bugardi-2025';
        $date = date('Y-m-d');
        $temporaryToken = hash('sha256', 'scott' . $date . $secret);
        $generatedLink = getTemporaryLink($temporaryToken);
        $linkExpiry = 'Expires in 24 hours (' . date('M d, Y g:i A', strtotime('+1 day')) . ')';
        $linkDescription = 'Temporary Access Link';
    }
}

function getPermanentLink($token) {
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    return $baseUrl . '/Timekeeping-system/Public/Bugardi/scott_ot_approval.php?token=' . $token . '&type=permanent';
}

function getTemporaryLink($token) {
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    return $baseUrl . '/Timekeeping-system/Public/Bugardi/scott_ot_approval.php?token=' . $token . '&type=temporary';
}

// Get current statistics
$stmt = $pdo->query("
    SELECT COUNT(*) as pending_count 
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id 
    WHERE o.status = 'Pending' AND LOWER(e.company) = 'bugardi'
");
$pendingOT = $stmt->fetchColumn();

// Check email configuration
$emailConfigured = !empty($_ENV['SMTP_HOST']) && !empty($_ENV['SMTP_USER']) && !empty($_ENV['SMTP_PASS']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scott's OT Access Links - Bugardi</title>
    <link href="../../../../src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-link text-blue-600 mr-3"></i>
                            Scott's OT Access Links
                        </h1>
                        <p class="text-gray-600 mt-1">Generate secure access links for overtime request approval</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-2">
                            <div class="text-sm font-medium text-yellow-800">Pending OT Requests</div>
                            <div class="text-2xl font-bold text-yellow-900"><?= $pendingOT ?></div>
                        </div>
                        <?php if ($pendingOT > 0): ?>
                        <div class="mt-2">
                            <button onclick="sendNotificationToScott()" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md transition-colors">
                                <i class="fas fa-bell mr-1"></i>Notify Scott
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Email Configuration Status -->
                        <div class="mt-3">
                            <?php if ($emailConfigured): ?>
                            <div class="text-xs text-green-600 flex items-center">
                                <i class="fas fa-check-circle mr-1"></i>Email Configured
                            </div>
                            <?php else: ?>
                            <div class="text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <a href="test_scott_email.php" class="underline">Setup Email</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Link Generation Options -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                
                <!-- Temporary Link Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-clock text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Temporary Link</h3>
                                <p class="text-sm text-gray-600">Valid for 24 hours only</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Expires automatically after 24 hours</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>More secure for temporary access</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Need to regenerate daily</span>
                            </div>
                        </div>
                        
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="generate">
                            <input type="hidden" name="link_type" value="temporary">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-link mr-2"></i>Generate Temporary Link
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Permanent Link Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-infinity text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Permanent Link</h3>
                                <p class="text-sm text-gray-600">Never expires</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>Works indefinitely</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                <span>No need to regenerate</span>
                            </div>
                            <div class="flex items-center text-sm text-yellow-600">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Less secure if compromised</span>
                            </div>
                        </div>
                        
                        <form method="POST" class="w-full">
                            <input type="hidden" name="action" value="generate">
                            <input type="hidden" name="link_type" value="permanent">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-infinity mr-2"></i>Generate Permanent Link
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Generated Link Display -->
            <?php if (isset($generatedLink)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?= $linkDescription ?> Generated</h3>
                        <p class="text-sm text-gray-600"><?= $linkExpiry ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Generated Link:</label>
                    <div class="flex">
                        <input type="text" 
                               id="generatedLink" 
                               value="<?= htmlspecialchars($generatedLink) ?>" 
                               readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md bg-gray-50 text-sm font-mono">
                        <button onclick="copyToClipboard('generatedLink')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md transition duration-200">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <a href="mailto:scott@bugardi.com?subject=OT Approval Access&body=Hi Scott,%0A%0AHere's your secure link to approve overtime requests:%0A%0A<?= urlencode($generatedLink) ?>%0A%0A<?= urlencode($linkExpiry) ?>%0A%0ABest regards" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-md text-center transition duration-200 text-sm">
                        <i class="fas fa-envelope mr-2"></i>Send via Email
                    </a>
                    
                    <a href="<?= htmlspecialchars($generatedLink) ?>" 
                       target="_blank" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-md text-center transition duration-200 text-sm">
                        <i class="fas fa-external-link-alt mr-2"></i>Test Link
                    </a>

                    <button onclick="shareViaWhatsApp('<?= htmlspecialchars($generatedLink) ?>')" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-md text-center transition duration-200 text-sm">
                        <i class="fab fa-whatsapp mr-2"></i>Send via WhatsApp
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Security Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="fas fa-shield-alt mr-1"></i>Security Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                    <div>
                        <h4 class="font-medium mb-1">Temporary Links:</h4>
                        <ul class="space-y-1 text-xs">
                            <li>• Token expires after 24 hours</li>
                            <li>• More secure for regular use</li>
                            <li>• Recommended for daily operations</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium mb-1">Permanent Links:</h4>
                        <ul class="space-y-1 text-xs">
                            <li>• Never expires automatically</li>
                            <li>• Convenient for long-term access</li>
                            <li>• Keep link confidential</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Back Navigation -->
            <div class="text-center">
                <a href="../../Admin_dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Admin Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(inputId) {
            const linkInput = document.getElementById(inputId);
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(linkInput.value).then(function() {
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.classList.add('bg-green-600');
                button.classList.remove('bg-blue-600');
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('bg-green-600');
                    button.classList.add('bg-blue-600');
                }, 2000);
            });
        }

        function shareViaWhatsApp(link) {
            const message = `Hi Scott,\n\nHere's your secure link to approve overtime requests:\n\n${link}\n\n<?= isset($linkExpiry) ? $linkExpiry : '' ?>\n\nBest regards`;
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }
        
        function sendNotificationToScott() {
            if (confirm('Send an email notification to Scott about pending OT requests?')) {
                fetch('send_scott_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({action: 'send_reminder'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Notification sent to Scott successfully!');
                    } else {
                        alert('❌ Failed to send notification: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('❌ Error sending notification: ' + error.message);
                });
            }
        }
    </script>

</body>
</html>
