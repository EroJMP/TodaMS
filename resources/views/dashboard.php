<?php
ob_start();
$modules = $modules ?? [];
?>
<section class="card">
    <h2>Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
    <p class="muted">Role: <strong><?php echo htmlspecialchars($user['role'] ?? 'unknown'); ?></strong></p>
    <p>This is your Phase 3 role dashboard with module overview and reporting highlights.</p>
</section>

<section class="stats-grid">
    <?php if (($user['role'] ?? '') === 'driver'): ?>
        <article class="stat-card">
            <h3>Total Notifications</h3>
            <p class="stat-value"><?php echo (int) ($summary['driver_notifications_total'] ?? 0); ?></p>
            <p class="muted">Notifications sent to your account</p>
        </article>
        <article class="stat-card">
            <h3>Violations Involved</h3>
            <p class="stat-value"><?php echo (int) ($summary['driver_violations_involved_total'] ?? 0); ?></p>
            <p class="muted">Cases where you are reporter or reported</p>
        </article>
        <article class="stat-card">
            <h3>Pending Payments</h3>
            <p class="stat-value"><?php echo (int) ($summary['driver_payments_pending_total'] ?? 0); ?></p>
            <p class="muted">Payments awaiting your completion</p>
        </article>
        <article class="stat-card">
            <h3>Total Amount To Pay</h3>
            <p class="stat-value">PHP <?php echo number_format((float) ($summary['driver_total_amount_to_pay'] ?? 0), 2); ?></p>
            <p class="muted">Sum of your pending payment records</p>
        </article>
    <?php else: ?>
        <article class="stat-card">
            <h3>Members</h3>
            <p class="stat-value"><?php echo (int) ($summary['members_total'] ?? 0); ?></p>
            <p class="muted">Pending approval: <?php echo (int) ($summary['members_pending'] ?? 0); ?></p>
        </article>
        <article class="stat-card">
            <h3>Violations</h3>
            <p class="stat-value"><?php echo (int) ($summary['violations_total'] ?? 0); ?></p>
            <p class="muted">Pending pipeline: <?php echo (int) ($summary['violations_pending'] ?? 0); ?></p>
        </article>
        <article class="stat-card">
            <h3>Payments</h3>
            <p class="stat-value"><?php echo (int) ($summary['payments_total'] ?? 0); ?></p>
            <p class="muted">Pending verification: <?php echo (int) ($summary['payments_pending'] ?? 0); ?></p>
        </article>
        <article class="stat-card">
            <h3>Collected</h3>
            <p class="stat-value">PHP <?php echo number_format((float) ($summary['paid_total_amount'] ?? 0), 2); ?></p>
            <p class="muted">Confirmed payments only</p>
        </article>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Role Modules</h3>
    <div class="links">
        <?php foreach ($modules as $module): ?>
            <a href="<?php echo htmlspecialchars(Url::to($module['path'])); ?>">
                <?php echo htmlspecialchars($module['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
