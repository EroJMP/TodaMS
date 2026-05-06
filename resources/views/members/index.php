<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Member Management</h2>
    <p class="muted">Secretary encodes members. Vice President approves or rejects pending entries.</p>

    <?php if ($user['role'] === 'secretary'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/members')); ?>" class="form">
            <label>Name <input name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"></label>
            <label>Address <input name="address" value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>"></label>
            <label>Contact Number <input name="contact_number" value="<?php echo htmlspecialchars($old['contact_number'] ?? ''); ?>"></label>
            <label>License Number <input name="license_number" value="<?php echo htmlspecialchars($old['license_number'] ?? ''); ?>"></label>
            <label>Plate Number <input name="plate_number" value="<?php echo htmlspecialchars($old['plate_number'] ?? ''); ?>"></label>
            <?php if (!empty($errors)): ?><div class="alert">Please complete all required fields.</div><?php endif; ?>
            <button class="btn" type="submit">Submit for Approval</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Members List</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Plate</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <tr>
                <td><?php echo (int) $member['id']; ?></td>
                <td><?php echo htmlspecialchars($member['name']); ?></td>
                <td><?php echo htmlspecialchars($member['plate_number']); ?></td>
                <td><?php echo htmlspecialchars($member['status']); ?></td>
                <td>
                    <?php if ($user['role'] === 'vice_president' && $member['status'] === 'PENDING APPROVAL'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/members/approve')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $member['id']; ?>">
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/members/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $member['id']; ?>">
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
