<?php
$logs = $logs ?? [];
ob_start();
?>
<section class="card">
    <h2>Audit Logs</h2>
    <p class="muted">Read-only activity trail for security and data integrity monitoring.</p>
</section>

<section class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>User ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>IP</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo (int) ($log['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['action'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['details'] ?? '')); ?></td>
                    <td><?php echo isset($log['user_id']) && $log['user_id'] !== null ? (int) $log['user_id'] : '-'; ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['role'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['ip'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($log['created_at'] ?? '')); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
