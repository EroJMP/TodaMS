<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$users = $users ?? [];
$members = $members ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>User Management</h2>
    <p class="muted">Super Admin can create, activate, or deactivate user accounts and role assignments.</p>
    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/users')); ?>" class="form">
        <label>Username <input name="username" value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"></label>
        <label>
            Member
            <select name="member_id">
                <option value="">Select member</option>
                <?php foreach ($members as $member): ?>
                    <?php $selected = ((string) ($old['member_id'] ?? '')) === (string) ($member['id'] ?? ''); ?>
                    <option value="<?php echo (int) ($member['id'] ?? 0); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) ($member['name'] ?? '')); ?> (ID: <?php echo (int) ($member['id'] ?? 0); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Password <input name="password" type="password"></label>
        <label>
            Role
            <input name="role" value="<?php echo htmlspecialchars($old['role'] ?? ''); ?>" placeholder="super_admin / secretary / treasurer">
        </label>
        <?php if (!empty($errors)): ?><div class="alert">Please complete all required fields.</div><?php endif; ?>
        <button class="btn" type="submit">Create User</button>
    </form>
</section>

<section class="card">
    <h3>Users</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Username</th><th>Member ID</th><th>Member Name</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?php echo (int) $row['id']; ?></td>
                <td><?php echo htmlspecialchars((string) $row['username']); ?></td>
                <td><?php echo (int) ($row['member_id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['member_name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) $row['role']); ?></td>
                <td><?php echo ((int) ($row['is_active'] ?? 0) === 1) ? 'ACTIVE' : 'INACTIVE'; ?></td>
                <td>
                    <?php if ((int) ($row['is_active'] ?? 0) === 1): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/users/deactivate')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn btn-secondary" type="submit">Deactivate</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/users/activate')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Activate</button>
                        </form>
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
