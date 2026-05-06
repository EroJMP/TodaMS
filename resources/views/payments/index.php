<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$user = $user ?? ['role' => ''];
$payments = $payments ?? [];
$members = $members ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Payment Management</h2>
    <p class="muted">Treasurer records and verifies payment transactions.</p>
    <?php if (!empty($errors['payment'])): ?><div class="alert"><?php echo htmlspecialchars($errors['payment']); ?></div><?php endif; ?>

    <?php if ($user['role'] === 'treasurer'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments')); ?>" class="form form-grid-2">
            <label>Driver Name
                <input name="driver_name" list="member-driver-options" value="<?php echo htmlspecialchars($old['driver_name'] ?? ''); ?>" placeholder="Type to search member/driver">
                <datalist id="member-driver-options">
                    <?php foreach ($members as $member): ?>
                        <?php $memberName = trim((string) ($member['name'] ?? '')); ?>
                        <?php if ($memberName === '') { continue; } ?>
                        <option value="<?php echo htmlspecialchars($memberName); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>Reason <input name="reason" value="<?php echo htmlspecialchars($old['reason'] ?? ''); ?>"></label>
            <label>Due Date <input name="due_date" type="date" value="<?php echo htmlspecialchars($old['due_date'] ?? ''); ?>"></label>
            <label>
                Amount
                <input name="amount" type="number" step="0.01" value="<?php echo htmlspecialchars($old['amount'] ?? ''); ?>">
                <?php if (!empty($errors['amount'])): ?>
                    <span class="error"><?php echo htmlspecialchars($errors['amount']); ?></span>
                <?php endif; ?>
            </label>
            <?php if (!empty($errors['driver_name']) || !empty($errors['reason']) || (!empty($errors['amount']) && ($old['amount'] ?? '') === '')): ?>
                <div class="alert form-span-2">Please complete all required fields.</div>
            <?php endif; ?>
            <button class="btn form-span-2" type="submit">Create Billing</button>
        </form>

        <hr style="border:0;border-top:1px solid #ead3d8;margin:16px 0;">
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/activity-fee')); ?>" class="form form-grid-2">
            <label class="form-span-2">
                Activity Name (for contribution billing)
                <input name="activity_name" placeholder="e.g. Monthly Meeting, Seminar, Assembly">
            </label>
            <button class="btn form-span-2" type="submit">Generate Activity Fee Billing for All Active Drivers</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Payment Records</h3>
    <form method="GET" action="<?php echo htmlspecialchars(Url::to('/payments')); ?>" class="form js-live-filter-form filter-line" style="margin-bottom: 14px;">
        <input type="hidden" name="route" value="payments">
        <label>Search
            <input name="q" value="<?php echo htmlspecialchars((string) ($_GET['q'] ?? '')); ?>" placeholder="Driver name or reason">
        </label>
        <div class="filter-controls">
            <label>Status
                <?php $statusValue = (string) ($_GET['status'] ?? ''); ?>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="PENDING VERIFICATION" <?php echo $statusValue === 'PENDING VERIFICATION' ? 'selected' : ''; ?>>PENDING VERIFICATION</option>
                    <option value="PAID" <?php echo $statusValue === 'PAID' ? 'selected' : ''; ?>>PAID</option>
                    <option value="REJECTED" <?php echo $statusValue === 'REJECTED' ? 'selected' : ''; ?>>REJECTED</option>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Refresh</button>
    </form>
    <table class="table">
        <thead><tr><th>ID</th><th>Driver</th><th>Reason</th><th>Amount To Pay</th><th>Amount Paid</th><th>Due Date</th><th>Reference</th><th>Submitted Ref</th><th>Status</th><th>Flag</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <?php
            $amountToPay = (float) ($payment['amount_to_pay'] ?? $payment['amount'] ?? 0);
            $amountPaid = (float) ($payment['amount_paid'] ?? 0);
            $hasAmountMismatch = $amountPaid > 0 && abs($amountPaid - $amountToPay) > 0.009;
            $submittedRef = trim((string) ($payment['submitted_reference_no'] ?? ''));
            $expectedRef = trim((string) ($payment['reference_no'] ?? ''));
            $hasRefMismatch = $submittedRef !== '' && $expectedRef !== '' && strcasecmp($submittedRef, $expectedRef) !== 0;
            $hasMismatch = $hasAmountMismatch || $hasRefMismatch;
            ?>
            <tr>
                <td><?php echo (int) $payment['id']; ?></td>
                <td><?php echo htmlspecialchars($payment['driver_name']); ?></td>
                <td><?php echo htmlspecialchars($payment['reason']); ?></td>
                <td><?php echo number_format($amountToPay, 2); ?></td>
                <td><?php echo $amountPaid > 0 ? number_format($amountPaid, 2) : '-'; ?></td>
                <td><?php echo htmlspecialchars((string) ($payment['due_date'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($payment['reference_no'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($payment['submitted_reference_no'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($payment['status']); ?></td>
                <td>
                    <?php if ((int) ($payment['is_flagged'] ?? 0) === 1): ?>
                        <span style="color:#991b1b;font-weight:700;">FLAGGED</span>
                        <div class="muted"><?php echo htmlspecialchars((string) ($payment['flag_reason'] ?? '')); ?></div>
                    <?php elseif ($hasMismatch): ?>
                        <span style="color:#b45309;font-weight:700;">POTENTIAL MISMATCH</span>
                    <?php else: ?>
                        <span class="muted">-</span>
                    <?php endif; ?>
                </td>
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
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/cash-pay')); ?>" style="display:inline;" data-no-confirm="1" class="js-cash-pay-form">
                            <input type="hidden" name="id" value="<?php echo (int) $payment['id']; ?>">
                            <input type="hidden" name="cash_amount_paid" value="">
                            <button class="btn js-cash-pay-btn" type="button">Cash Pay</button>
                        </form>
                    <?php elseif ($user['role'] === 'driver' && $payment['status'] === 'PENDING VERIFICATION' && strtolower((string) $payment['driver_name']) === strtolower((string) ($user['name'] ?? ''))): ?>
                        <button
                            type="button"
                            class="btn js-open-proof-modal"
                            data-payment-id="<?php echo (int) $payment['id']; ?>"
                            data-reference-no="<?php echo htmlspecialchars((string) ($payment['reference_no'] ?? ''), ENT_QUOTES); ?>"
                            data-amount-to-pay="<?php echo htmlspecialchars((string) $amountToPay, ENT_QUOTES); ?>"
                        >
                            Upload Proof
                        </button>
                    <?php elseif ($user['role'] === 'compliance_officer' && (int) ($payment['is_flagged'] ?? 0) === 0 && $hasMismatch): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/flag')); ?>" class="form" style="display:inline-grid;gap:6px;min-width:220px;">
                            <input type="hidden" name="id" value="<?php echo (int) $payment['id']; ?>">
                            <input name="flag_reason" placeholder="Flag reason (reference/amount mismatch)">
                            <button class="btn btn-secondary" type="submit">Flag Suspicious</button>
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

<?php if ($user['role'] === 'driver'): ?>
<div id="proof-modal" class="proof-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1200;">
    <div style="background:#fff;max-width:520px;margin:8% auto;padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Upload Payment Proof</h3>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/payments/submit-proof')); ?>" enctype="multipart/form-data" class="form" data-no-confirm="1">
            <input type="hidden" id="proof-payment-id" name="id" value="">
            <label>Expected Reference No.
                <input id="proof-reference-preview" type="text" value="" readonly>
            </label>
            <label>Amount To Pay
                <input id="proof-amount-preview" type="text" value="" readonly>
            </label>
            <label>Upload Proof File (image/video)
                <input name="proof_file" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi" required>
            </label>
            <label>Reference Number
                <input name="submitted_reference_no" placeholder="Enter the billing reference number" required>
            </label>
            <label>Amount Paid
                <input name="submitted_amount" type="number" step="0.01" placeholder="Enter amount paid" required>
            </label>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" id="proof-modal-cancel">Cancel</button>
                <button type="submit" class="btn">Confirm Submission</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
