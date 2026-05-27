<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function demo_is_mode(): bool
{
    return !empty($_SESSION['demo_mode']);
}

function demo_is_admin_mode(): bool
{
    return !empty($_SESSION['demo_mode']) && !empty($_SESSION['demo_admin_mode']);
}

function demo_block_admin_mutation(string $message = 'This action is disabled in admin demo mode.'): void
{
    if (!demo_is_admin_mode()) {
        return;
    }

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['error'] = $message;
    $fallback = $_SERVER['HTTP_REFERER'] ?? '../views/admin_homepage.php';
    header('Location: ' . $fallback);
    exit;
}

function demo_block_mutation(string $message = 'This action is disabled in demo mode.'): void
{
    if (!demo_is_mode()) {
        return;
    }

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['error'] = $message;
    $fallback = $_SERVER['HTTP_REFERER'] ?? '../module/time_log_create.php';
    header('Location: ' . $fallback);
    exit;
}

function demo_render_admin_locked_page(string $title, string $message = ''): void
{
    if (!demo_is_admin_mode()) {
        return;
    }

    $pageTitle = $title;
    $message = $message ?: 'This admin section is locked in demo mode to protect real employee data.';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Demo Locked</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    </head>
    <body class="bg-gray-100">
    <div x-data="{ open: false }" class="flex h-screen">
        <?php include(__DIR__ . '/../views/sidebar.php'); ?>
        <div class="flex-1 flex flex-col min-w-0">
            <?php include(__DIR__ . '/../views/header.php'); ?>
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-lock text-2xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($title) ?> is locked in demo mode</h1>
                        <p class="mt-3 text-gray-600"><?= htmlspecialchars($message) ?></p>
                        <p class="mt-2 text-sm text-gray-500">
                            The admin demo shows the interface without exposing internal records or changing real employee data.
                        </p>
                        <a href="admin_homepage.php" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 font-semibold text-white hover:bg-gray-800">
                            <i class="fas fa-arrow-left"></i>
                            Back to Demo Dashboard
                        </a>
                    </section>
                </div>
            </main>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}
