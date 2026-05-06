<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'TodaMS'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(Url::to('/assets/css/base.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php $isAuthenticated = !empty($_SESSION['user']); ?>
<?php $flash = Response::pullFlash(); ?>
<body class="<?php echo $isAuthenticated ? 'auth-page' : 'guest-page'; ?>">
    <header class="topbar">
        <div class="brand-block">
            <h1>TodaMS</h1>
            <?php if ($isAuthenticated): ?>
                <p class="brand-subtitle">Operations Management Platform</p>
            <?php endif; ?>
        </div>
        <?php if ($isAuthenticated): ?>
            <div class="topbar-actions">
                <span class="role-tag">
                    <?php echo htmlspecialchars(str_replace('_', ' ', strtoupper((string) ($_SESSION['user']['role'] ?? '')))); ?>
                </span>
            </div>
        <?php endif; ?>
    </header>
    <div class="app-shell<?php echo $isAuthenticated ? ' with-sidebar' : ''; ?>">
        <?php if ($isAuthenticated): ?>
            <?php
            $role = (string) ($_SESSION['user']['role'] ?? '');
            $sidebarName = (string) ($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? $role);
            $navigationItems = Navigation::itemsForRole($role);
            $activeRoute = $currentRoute ?? '';
            ?>
            <aside class="sidebar">
                <p class="sidebar-role"><?php echo htmlspecialchars($sidebarName); ?></p>
                <nav class="sidebar-nav">
                    <?php foreach ($navigationItems as $item): ?>
                        <?php $isActive = $activeRoute === $item['path']; ?>
                        <a
                            class="sidebar-link<?php echo $isActive ? ' is-active' : ''; ?>"
                            href="<?php echo htmlspecialchars(Url::to($item['path'])); ?>"
                        >
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <a
                        href="<?php echo htmlspecialchars(Url::to('/settings')); ?>"
                        class="btn btn-outline sidebar-settings<?php echo $activeRoute === '/settings' ? ' is-active' : ''; ?>"
                    >
                        Settings
                    </a>
                    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/logout')); ?>">
                        <button type="submit" class="btn btn-secondary sidebar-logout">Logout</button>
                    </form>
                </div>
            </aside>
        <?php endif; ?>

        <main class="container">
            <?php echo $content ?? ''; ?>
        </main>
    </div>
    <?php if (is_array($flash)): ?>
        <script>
            window.APP_FLASH = {
                type: <?php echo json_encode((string) ($flash['type'] ?? 'info')); ?>,
                message: <?php echo json_encode((string) ($flash['message'] ?? '')); ?>
            };
        </script>
    <?php endif; ?>
    <script src="<?php echo htmlspecialchars(Url::to('/assets/js/app.js')); ?>"></script>
</body>
</html>
