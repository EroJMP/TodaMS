<?php
$backups = $backups ?? [];
ob_start();
?>
<section class="card">
    <h2>System Tools</h2>
    <p class="muted">Super Admin system configuration and backup/restore operations.</p>
    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/system-tools/backup')); ?>">
        <button class="btn" type="submit">Create Full Backup</button>
    </form>
</section>

<section class="card">
    <h3>Recent Backups</h3>
    <table class="table">
        <thead><tr><th>Filename</th><th>Date &amp; Time</th><th>Action</th></tr></thead>
        <tbody>
        <?php if ($backups === []): ?>
            <tr><td class="muted">No backups created yet.</td><td class="muted">-</td><td class="muted">-</td></tr>
        <?php else: ?>
            <?php foreach ($backups as $file): ?>
                <?php
                $displayDateTime = '-';
                if (preg_match('/(\d{8})-(\d{6})/', (string) $file, $parts) === 1) {
                    $rawDate = $parts[1];
                    $rawTime = $parts[2];
                    $dateTime = DateTime::createFromFormat('YmdHis', $rawDate . $rawTime);
                    if ($dateTime instanceof DateTime) {
                        $displayDateTime = $dateTime->format('M d, Y h:i:s A');
                    }
                }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td><?php echo htmlspecialchars($displayDateTime); ?></td>
                    <td>
                        <button
                            type="button"
                            class="btn js-mock-retrieve-backup"
                            data-backup-file="<?php echo htmlspecialchars($file); ?>"
                        >
                            Retrieve Backup
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
