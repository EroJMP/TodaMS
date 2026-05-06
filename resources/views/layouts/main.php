<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'TodaMS'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(Url::to('/assets/css/base.css')); ?>">
</head>
<body>
    <header class="topbar">
        <h1>TodaMS</h1>
        <?php if (!empty($_SESSION['user'])): ?>
            <form method="POST" action="<?php echo htmlspecialchars(Url::to('/logout')); ?>">
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        <?php endif; ?>
    </header>
    <main class="container">
        <?php echo $content ?? ''; ?>
    </main>
    <script src="<?php echo htmlspecialchars(Url::to('/assets/js/app.js')); ?>"></script>
</body>
</html>
