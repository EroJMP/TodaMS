<?php
$account = $account ?? [];
ob_start();
?>
<section class="card">
    <h2>Account Settings</h2>
    <p class="muted">Manage your account information and password.</p>

    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/settings/profile')); ?>" class="form form-grid-2">
        <label>Full Name
            <input name="name" value="<?php echo htmlspecialchars((string) ($account['name'] ?? '')); ?>" required>
        </label>
        <label>Username
            <input name="username" value="<?php echo htmlspecialchars((string) ($account['username'] ?? '')); ?>" required>
        </label>
        <label>Address
            <input name="address" value="<?php echo htmlspecialchars((string) ($account['address'] ?? '')); ?>" placeholder="Address">
        </label>
        <label>Contact Number
            <input name="contact_number" value="<?php echo htmlspecialchars((string) ($account['contact_number'] ?? '')); ?>" placeholder="Contact number">
        </label>
        <label>Role
            <input value="<?php echo htmlspecialchars(str_replace('_', ' ', strtoupper((string) ($account['role'] ?? '')))); ?>" readonly>
        </label>
        <label>License Number
            <input value="<?php echo htmlspecialchars((string) ($account['license_number'] ?? '')); ?>" readonly>
        </label>
        <label>Plate Number
            <input value="<?php echo htmlspecialchars((string) ($account['plate_number'] ?? '')); ?>" readonly>
        </label>
        <button class="btn" type="submit">Update Information</button>
    </form>
</section>

<section class="card">
    <h3>Change Password</h3>
    <form method="POST" action="<?php echo htmlspecialchars(Url::to('/settings/password')); ?>" class="form form-grid-2">
        <label>Current Password
            <div class="password-field">
                <input id="settings-current-password" name="current_password" type="password" required>
                <button
                    type="button"
                    class="toggle-password"
                    data-target="settings-current-password"
                    aria-label="Show password"
                    title="Show password"
                >
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </label>
        <label>New Password
            <div class="password-field">
                <input id="settings-new-password" name="new_password" type="password" required>
                <button
                    type="button"
                    class="toggle-password"
                    data-target="settings-new-password"
                    aria-label="Show password"
                    title="Show password"
                >
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </label>
        <label>Confirm New Password
            <div class="password-field">
                <input id="settings-confirm-password" name="confirm_password" type="password" required>
                <button
                    type="button"
                    class="toggle-password"
                    data-target="settings-confirm-password"
                    aria-label="Show password"
                    title="Show password"
                >
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </label>
        <div></div>
        <button class="btn" type="submit">Update Password</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
