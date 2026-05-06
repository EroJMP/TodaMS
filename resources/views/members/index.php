<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$user = $user ?? ['role' => ''];
$members = $members ?? [];
$memberApproveError = (string) ($errors['member_approve'] ?? '');
$memberApproveOldId = (int) ($old['member_approve_id'] ?? 0);
$memberApproveOldUsername = (string) ($old['driver_username'] ?? '');
$shouldOpenApproveModal = $memberApproveError !== '' && ($user['role'] ?? '') === 'vice_president';
$memberApproveOldName = '';
if ($shouldOpenApproveModal && $memberApproveOldId > 0) {
    foreach ($members as $memberRow) {
        if ((int) ($memberRow['id'] ?? 0) === $memberApproveOldId) {
            $memberApproveOldName = (string) ($memberRow['name'] ?? '');
            break;
        }
    }
}
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Member Management</h2>
    <p class="muted">Secretary encodes members. Vice President approves or rejects pending entries.</p>
    <?php if (!empty($errors['member'])): ?><div class="alert"><?php echo htmlspecialchars($errors['member']); ?></div><?php endif; ?>

    <?php if ($user['role'] === 'secretary'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/members')); ?>" class="form form-grid-2" enctype="multipart/form-data">
            <label>
                Name
                <input name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
                <?php if (!empty($errors['name'])): ?><span class="error"><?php echo htmlspecialchars((string) $errors['name']); ?></span><?php endif; ?>
            </label>
            <label>
                Address
                <input name="address" value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>">
                <?php if (!empty($errors['address'])): ?><span class="error"><?php echo htmlspecialchars((string) $errors['address']); ?></span><?php endif; ?>
            </label>
            <label>
                Contact Number
                <input name="contact_number" value="<?php echo htmlspecialchars($old['contact_number'] ?? ''); ?>" placeholder="e.g. 0917-123-4567 or +63 917 123 4567">
                <?php if (!empty($errors['contact_number'])): ?><span class="error"><?php echo htmlspecialchars((string) $errors['contact_number']); ?></span><?php endif; ?>
            </label>
            <label>
                License Number
                <input name="license_number" value="<?php echo htmlspecialchars($old['license_number'] ?? ''); ?>" placeholder="e.g. TRC-5821">
                <?php if (!empty($errors['license_number'])): ?><span class="error"><?php echo htmlspecialchars((string) $errors['license_number']); ?></span><?php endif; ?>
            </label>
            <label>
                Plate Number
                <input name="plate_number" value="<?php echo htmlspecialchars($old['plate_number'] ?? ''); ?>" placeholder="e.g. TODA-4821">
                <?php if (!empty($errors['plate_number'])): ?><span class="error"><?php echo htmlspecialchars((string) $errors['plate_number']); ?></span><?php endif; ?>
            </label>
            <label>ID Document (optional)
                <input type="file" name="id_doc" accept=".jpg,.jpeg,.png,.pdf,.webp">
            </label>
            <label>License Document (optional)
                <input type="file" name="license_doc" accept=".jpg,.jpeg,.png,.pdf,.webp">
            </label>
            <label>OR/CR Document (optional)
                <input type="file" name="orcr_doc" accept=".jpg,.jpeg,.png,.pdf,.webp">
            </label>
            <?php if (!empty($errors)): ?>
                <div class="alert form-span-2">
                    Please fix the following fields:
                    <?php echo htmlspecialchars(implode(' | ', array_values($errors))); ?>
                </div>
            <?php endif; ?>
            <button class="btn form-span-2" type="submit">Submit for Approval</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Members List</h3>
    <form method="GET" action="<?php echo htmlspecialchars(Url::to('/members')); ?>" class="form js-live-filter-form filter-line" style="margin-bottom: 14px;">
        <input type="hidden" name="route" value="members">
        <label>Search
            <input name="q" value="<?php echo htmlspecialchars((string) ($_GET['q'] ?? '')); ?>" placeholder="Name, plate, address">
        </label>
        <div class="filter-controls">
            <label>Status
                <?php $statusValue = (string) ($_GET['status'] ?? ''); ?>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" <?php echo $statusValue === 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE</option>
                    <option value="PENDING APPROVAL" <?php echo $statusValue === 'PENDING APPROVAL' ? 'selected' : ''; ?>>PENDING APPROVAL</option>
                    <option value="DECLINED" <?php echo $statusValue === 'DECLINED' ? 'selected' : ''; ?>>DECLINED</option>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Refresh</button>
    </form>
    <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Plate</th><th>ID Doc</th><th>License Doc</th><th>OR/CR</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <tr>
                <td><?php echo (int) $member['id']; ?></td>
                <td><?php echo htmlspecialchars($member['name']); ?></td>
                <td><?php echo htmlspecialchars($member['plate_number']); ?></td>
                <td><?php echo !empty($member['id_doc_path']) ? htmlspecialchars((string) $member['id_doc_path']) : '-'; ?></td>
                <td><?php echo !empty($member['license_doc_path']) ? htmlspecialchars((string) $member['license_doc_path']) : '-'; ?></td>
                <td><?php echo !empty($member['orcr_doc_path']) ? htmlspecialchars((string) $member['orcr_doc_path']) : '-'; ?></td>
                <td><?php echo htmlspecialchars($member['status']); ?></td>
                <td>
                    <?php if ($user['role'] === 'vice_president' && $member['status'] === 'PENDING APPROVAL'): ?>
                        <button
                            type="button"
                            class="btn js-open-member-approve-modal"
                            data-member-id="<?php echo (int) $member['id']; ?>"
                            data-member-name="<?php echo htmlspecialchars((string) ($member['name'] ?? ''), ENT_QUOTES); ?>"
                        >
                            Approve
                        </button>
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

<?php if ($user['role'] === 'vice_president'): ?>
<div id="member-approve-modal" style="display:<?php echo $shouldOpenApproveModal ? 'block' : 'none'; ?>;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1200;">
    <div style="background:#fff;max-width:520px;margin:8% auto;padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Approve Member &amp; Create Driver Account</h3>
        <p class="muted" id="member-approve-modal-name" style="margin-top:0;"><?php echo $memberApproveOldName !== '' ? htmlspecialchars('Member: ' . $memberApproveOldName) : ''; ?></p>
        <?php if ($memberApproveError !== ''): ?>
            <div class="alert"><?php echo htmlspecialchars($memberApproveError); ?></div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/members/approve')); ?>" class="form" data-no-confirm="1">
            <input type="hidden" id="member-approve-id" name="id" value="<?php echo $memberApproveOldId > 0 ? (int) $memberApproveOldId : ''; ?>">
            <label>Driver Username
                <input name="driver_username" placeholder="e.g. juan.delacruz" value="<?php echo htmlspecialchars($memberApproveOldUsername); ?>" required>
            </label>
            <label>Driver Password
                <div class="password-field">
                    <input id="member-approve-password" name="driver_password" type="password" placeholder="Enter password" required>
                    <button type="button" class="toggle-password" data-target="member-approve-password" aria-label="Show password" title="Show password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </label>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" id="member-approve-cancel">Cancel</button>
                <button type="submit" class="btn">Approve + Create Account</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
