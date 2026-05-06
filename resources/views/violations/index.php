<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Violation Workflow</h2>
    <p class="muted">Driver submits report. Secretary encodes. Compliance validates. VP decides.</p>

    <?php if ($user['role'] === 'driver'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations')); ?>" class="form">
            <label>Reporter Name <input name="reporter_name" value="<?php echo htmlspecialchars($old['reporter_name'] ?? ''); ?>"></label>
            <label>Reported Driver <input name="reported_name" value="<?php echo htmlspecialchars($old['reported_name'] ?? ''); ?>"></label>
            <label>Violation Type <input name="violation_type" value="<?php echo htmlspecialchars($old['violation_type'] ?? ''); ?>"></label>
            <label>Description <input name="description" value="<?php echo htmlspecialchars($old['description'] ?? ''); ?>"></label>
            <?php if (!empty($errors)): ?><div class="alert">Please complete all required fields.</div><?php endif; ?>
            <button class="btn" type="submit">Submit Report</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Violation Reports</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Reporter</th><th>Reported</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($violations as $row): ?>
            <tr>
                <td><?php echo (int) $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                <td><?php echo htmlspecialchars($row['reported_name']); ?></td>
                <td><?php echo htmlspecialchars($row['violation_type']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <?php if ($user['role'] === 'secretary' && $row['status'] === 'SUBMITTED'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/encode')); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Encode</button>
                        </form>
                    <?php elseif ($user['role'] === 'compliance_officer' && $row['status'] === 'PENDING VALIDATION'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/validate')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Validate</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn btn-secondary" type="submit">Reject</button>
                        </form>
                    <?php elseif ($user['role'] === 'vice_president' && $row['status'] === 'PENDING APPROVAL'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/approve')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn btn-secondary" type="submit">Reject</button>
                        </form>
                    <?php else: ?>
                        <span class="muted">No action</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
