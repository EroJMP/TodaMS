<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Payment Management</h2>
    <p class="muted">Treasurer records and verifies payment transactions.</p>

    <?php if ($user['role'] === 'treasurer'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments')); ?>" class="form">
            <label>Driver Name <input name="driver_name" value="<?php echo htmlspecialchars($old['driver_name'] ?? ''); ?>"></label>
            <label>Reason <input name="reason" value="<?php echo htmlspecialchars($old['reason'] ?? ''); ?>"></label>
            <label>Amount <input name="amount" type="number" step="0.01" value="<?php echo htmlspecialchars($old['amount'] ?? ''); ?>"></label>
            <?php if (!empty($errors)): ?><div class="alert">Please complete all required fields.</div><?php endif; ?>
            <button class="btn" type="submit">Create Billing</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Payment Records</h3>
    <table class="table">
        <thead><tr><th>ID</th><th>Driver</th><th>Reason</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?php echo (int) $payment['id']; ?></td>
                <td><?php echo htmlspecialchars($payment['driver_name']); ?></td>
                <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                <td><?php echo number_format((float) $payment['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($payment['status']); ?></td>
                <td>
                    <?php if ($user['role'] === 'treasurer' && $payment['status'] === 'PENDING VERIFICATION'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/paid')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $payment['id']; ?>">
                            <button class="btn" type="submit">Mark Paid</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $payment['id']; ?>">
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
