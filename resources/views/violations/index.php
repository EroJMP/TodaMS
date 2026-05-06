<?php
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$user = $user ?? ['role' => ''];
$violations = $violations ?? [];
$driverOptions = $driverOptions ?? [];
$violationTypeOptions = $violationTypeOptions ?? [];
$showCrossCheckColumns = ($user['role'] ?? '') !== 'driver';
unset($_SESSION['errors'], $_SESSION['old']);

ob_start();
?>
<section class="card">
    <h2>Violation Workflow</h2>
    <p class="muted">Driver submits report. Secretary encodes. Compliance validates. VP decides.</p>
    <?php if (!empty($errors['violation'])): ?><div class="alert"><?php echo htmlspecialchars($errors['violation']); ?></div><?php endif; ?>

    <?php if ($user['role'] === 'driver'): ?>
        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations')); ?>" class="form form-grid-2" enctype="multipart/form-data">
            <label>Reporter Name
                <input value="<?php echo htmlspecialchars((string) ($user['name'] ?? '')); ?>" readonly>
            </label>
            <label>Reported Driver
                <input name="reported_name" list="driver-name-options" value="<?php echo htmlspecialchars($old['reported_name'] ?? ''); ?>" placeholder="Type to search driver">
                <datalist id="driver-name-options">
                    <?php foreach ($driverOptions as $driver): ?>
                        <?php $driverName = trim((string) ($driver['name'] ?? '')); ?>
                        <?php if ($driverName === '') { continue; } ?>
                        <option value="<?php echo htmlspecialchars($driverName); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>Reported Plate No. <input name="reported_plate" value="<?php echo htmlspecialchars($old['reported_plate'] ?? ''); ?>"></label>
            <label>Violation Type
                <select name="violation_type">
                    <option value="">Select violation/fee type</option>
                    <?php foreach ($violationTypeOptions as $typeOption): ?>
                        <?php $typeValue = (string) ($typeOption['value'] ?? ''); ?>
                        <?php $typeLabel = (string) ($typeOption['label'] ?? $typeValue); ?>
                        <option value="<?php echo htmlspecialchars($typeValue); ?>" <?php echo (($old['violation_type'] ?? '') === $typeValue) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($typeLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Description <input name="description" value="<?php echo htmlspecialchars($old['description'] ?? ''); ?>"></label>
            <label>Incident Date &amp; Time <input name="incident_datetime" type="datetime-local" value="<?php echo htmlspecialchars($old['incident_datetime'] ?? ''); ?>"></label>
            <label>Location <input name="incident_location" value="<?php echo htmlspecialchars($old['incident_location'] ?? ''); ?>"></label>
            <label>Evidence File (image/video)
                <input name="evidence_file" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi" required>
            </label>
            <?php if (!empty($errors)): ?><div class="alert form-span-2">Please complete all required fields.</div><?php endif; ?>
            <button class="btn form-span-2" type="submit">Submit Report</button>
        </form>
    <?php endif; ?>
</section>

<section class="card">
    <h3>Violation Reports</h3>
    <form method="GET" action="<?php echo htmlspecialchars(Url::to('/violations')); ?>" class="form js-live-filter-form filter-line" style="margin-bottom: 14px;">
        <input type="hidden" name="route" value="violations">
        <label>Search
            <input name="q" value="<?php echo htmlspecialchars((string) ($_GET['q'] ?? '')); ?>" placeholder="Reporter, reported, description">
        </label>
        <div class="filter-controls">
            <label>Violation Type
                <?php $typeValue = (string) ($_GET['type'] ?? ''); ?>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach ($violationTypeOptions as $typeOption): ?>
                        <?php $filterValue = (string) ($typeOption['value'] ?? ''); ?>
                        <?php if ($filterValue === '') { continue; } ?>
                        <option value="<?php echo htmlspecialchars($filterValue); ?>" <?php echo $typeValue === $filterValue ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) ($typeOption['label'] ?? $filterValue)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status
                <?php $statusValue = (string) ($_GET['status'] ?? ''); ?>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="SUBMITTED" <?php echo $statusValue === 'SUBMITTED' ? 'selected' : ''; ?>>SUBMITTED</option>
                    <option value="RETURNED" <?php echo $statusValue === 'RETURNED' ? 'selected' : ''; ?>>RETURNED</option>
                    <option value="PENDING VALIDATION" <?php echo $statusValue === 'PENDING VALIDATION' ? 'selected' : ''; ?>>PENDING VALIDATION</option>
                    <option value="PENDING APPROVAL" <?php echo $statusValue === 'PENDING APPROVAL' ? 'selected' : ''; ?>>PENDING APPROVAL</option>
                    <option value="APPROVED" <?php echo $statusValue === 'APPROVED' ? 'selected' : ''; ?>>APPROVED</option>
                    <option value="REJECTED" <?php echo $statusValue === 'REJECTED' ? 'selected' : ''; ?>>REJECTED</option>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Refresh</button>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th><th>Reporter</th><th>Reported</th>
                <?php if ($showCrossCheckColumns): ?><th>Reported Plate</th><th>Actual Plate</th><th>Description</th><?php endif; ?>
                <th>Type</th><th>Date/Time</th><th>Location</th><th>Evidence</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($violations as $row): ?>
            <tr>
                <td><?php echo (int) $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                <td><?php echo htmlspecialchars($row['reported_name']); ?></td>
                <?php if ($showCrossCheckColumns): ?>
                    <td><?php echo htmlspecialchars((string) ($row['reported_plate'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['actual_reported_plate'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['description'] ?? '')); ?></td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($row['violation_type']); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['incident_datetime'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['incident_location'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($row['evidence_path'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <?php if ($user['role'] === 'secretary' && $row['status'] === 'SUBMITTED'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/encode')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Encode</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <input type="hidden" name="review_notes" value="Rejected by secretary during initial review.">
                            <button class="btn btn-secondary" type="submit">Reject</button>
                        </form>
                    <?php elseif ($user['role'] === 'compliance_officer' && $row['status'] === 'PENDING VALIDATION'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/validate')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Validate</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <input type="hidden" name="review_notes" value="Rejected by compliance review (invalid or false accusation).">
                            <button class="btn btn-secondary" type="submit">Reject</button>
                        </form>
                    <?php elseif ($user['role'] === 'vice_president' && $row['status'] === 'PENDING APPROVAL'): ?>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/approve')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="<?php echo htmlspecialchars(Url::to('/violations/reject')); ?>" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                            <input type="hidden" name="review_notes" value="Rejected by vice president decision.">
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
