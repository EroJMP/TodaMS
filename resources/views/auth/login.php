<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Sign In</h2>
    <p class="muted">Use your role account to access TodaMS.</p>

    <?php if (!empty($errors['auth'])): ?>
        <div class="alert"><?php echo htmlspecialchars($errors['auth']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/login')); ?>" class="form">
        <label>
            Username
            <input type="text" name="username" value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>" required>
            <?php if (!empty($errors['username'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['username']); ?></span>
            <?php endif; ?>
        </label>

        <label>
            Password
            <input type="password" name="password" required>
            <?php if (!empty($errors['password'])): ?>
                <span class="error"><?php echo htmlspecialchars($errors['password']); ?></span>
            <?php endif; ?>
        </label>

        <button type="submit" class="btn">Login</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
