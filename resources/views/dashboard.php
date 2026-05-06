<?php
ob_start();
?>
<section class="card">
    <h2>Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
    <p class="muted">Role: <strong><?php echo htmlspecialchars($user['role'] ?? 'unknown'); ?></strong></p>
    <p>This is the Phase 2 dashboard with core module navigation.</p>
</section>

<section class="card">
    <h3>Quick Access</h3>
    <div class="links">
        <a href="<?php echo htmlspecialchars(Url::to('/members')); ?>">Members</a>
        <a href="<?php echo htmlspecialchars(Url::to('/violations')); ?>">Violations</a>
        <a href="<?php echo htmlspecialchars(Url::to('/payments')); ?>">Payments</a>
        <a href="<?php echo htmlspecialchars(Url::to('/notifications')); ?>">Notifications</a>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
