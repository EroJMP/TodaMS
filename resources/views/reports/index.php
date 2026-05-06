<?php
    $recentViolations = $recentViolations ?? [];
    $recentPayments = $recentPayments ?? [];
ob_start();
?>
<section class="card">
    <h2>Reports Dashboard</h2>
    <p class="muted">Consolidated system, audit, and financial summary for your current role.</p>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <h3>Total Members</h3>
        <p class="stat-value"><?php echo (int) ($summary['members_total'] ?? 0); ?></p>
        <p class="muted">Pending approval: <?php echo (int) ($summary['members_pending'] ?? 0); ?></p>
    </article>
    <article class="stat-card">
        <h3>Total Violations</h3>
        <p class="stat-value"><?php echo (int) ($summary['violations_total'] ?? 0); ?></p>
        <p class="muted">Pending pipeline: <?php echo (int) ($summary['violations_pending'] ?? 0); ?></p>
    </article>
    <article class="stat-card">
        <h3>Total Payments</h3>
        <p class="stat-value"><?php echo (int) ($summary['payments_total'] ?? 0); ?></p>
        <p class="muted">Pending verification: <?php echo (int) ($summary['payments_pending'] ?? 0); ?></p>
    </article>
    <article class="stat-card">
        <h3>Collected Amount</h3>
        <p class="stat-value">PHP <?php echo number_format((float) ($summary['paid_total_amount'] ?? 0), 2); ?></p>
        <p class="muted">From PAID transactions only</p>
    </article>
</section>

<section class="card">
    <h3>Recent Violations</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Reported</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentViolations as $row): ?>
            <tr>
                <td><?php echo (int) ($row['id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['reported_name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['violation_type'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['status'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['created_at'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Recent Payments</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Driver</th><th>Reason</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentPayments as $row): ?>
            <tr>
                <td><?php echo (int) ($row['id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['driver_name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['reason'] ?? '')); ?></td>
                <td><?php echo number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['status'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
