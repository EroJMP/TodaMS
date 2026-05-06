<?php
ob_start();
?>
<section class="card">
    <h2>Notifications</h2>
    <p class="muted">Role-based workflow notifications.</p>
    <table class="table">
        <thead><tr><th>ID</th><th>Message</th><th>Created At</th></tr></thead>
        <tbody>
        <?php foreach ($notifications as $item): ?>
            <tr>
                <td><?php echo (int) $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['message']); ?></td>
                <td><?php echo htmlspecialchars($item['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
