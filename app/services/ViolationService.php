<?php

class ViolationService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private AuditService $audit;
    private PaymentService $payments;
    private RuleService $rules;
    private PDO $pdo;
    private string $file = 'violations';

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->notifications = new NotificationService();
        $this->audit = new AuditService();
        $this->payments = new PaymentService();
        $this->rules = new RuleService();
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        return $this->store->all($this->file);
    }

    public function create(array $input): array
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $reporterName = trim((string) ($actor['name'] ?? $input['reporter_name'] ?? ''));
        if ($reporterName === '') {
            return ['ok' => false, 'error' => 'Reporter identity is missing. Please log in again.'];
        }
        $rows = $this->store->all($this->file);
        $duplicate = $this->findDuplicate($rows, $input);
        if ($duplicate !== null) {
            return ['ok' => false, 'error' => 'Possible duplicate report detected for this incident.'];
        }

        $reporterUserId = isset($actor['id']) ? (int) $actor['id'] : null;
        $reportedDriver = $this->resolveDriverByName(trim((string) $input['reported_name']));
        if (!is_array($reportedDriver)) {
            return ['ok' => false, 'error' => 'Please select a valid reported driver from the list.'];
        }
        $reportedUserId = (int) ($reportedDriver['user_id'] ?? 0);
        $incidentDatetimeRaw = trim((string) ($input['incident_datetime'] ?? ''));
        $incidentDatetime = $incidentDatetimeRaw !== '' ? date('Y-m-d H:i:s', strtotime($incidentDatetimeRaw)) : '';
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'reporter_user_id' => $reporterUserId,
            'reporter_name' => $reporterName,
            'reported_driver_id' => (string) ($reportedDriver['driver_id'] ?? ''),
            'reported_name' => trim($input['reported_name']),
            'reported_plate' => trim((string) ($input['reported_plate'] ?? '')),
            'actual_reported_plate' => (string) ($reportedDriver['plate_number'] ?? ''),
            'violation_type' => trim($input['violation_type']),
            'description' => trim($input['description']),
            'incident_datetime' => $incidentDatetime,
            'incident_location' => trim((string) ($input['incident_location'] ?? '')),
            'evidence_path' => trim((string) ($input['evidence_path'] ?? '')),
            'review_notes' => null,
            'status' => 'SUBMITTED',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $this->notifications->create('secretary', 'New violation report submitted.');
        if ($reporterUserId !== null && $reporterUserId > 0) {
            $this->notifications->create('driver', 'Your report has been submitted successfully.', $reporterUserId);
        }
        if ($reportedUserId > 0) {
            $this->notifications->create('driver', 'A violation report has been filed against your account and is under review.', $reportedUserId);
        }
        $this->audit->log('VIOLATION_CREATE', 'Violation report submitted by: ' . $reporterName, $actor);
        return ['ok' => true];
    }

    public function transition(int $id, string $status, ?string $notes = null): array
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        $result = ['ok' => false, 'error' => 'Violation record not found.'];
        foreach ($rows as &$row) {
            if ((int) $row['id'] !== $id) {
                continue;
            }

            if ($status === 'PENDING VALIDATION') {
                $missing = $this->missingRequiredFields($row);
                if ($missing !== []) {
                    $row['status'] = 'RETURNED';
                    $row['review_notes'] = 'Returned: incomplete report (' . implode(', ', $missing) . ').';
                    $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : null;
                    if ($reporterUserId !== null && $reporterUserId > 0) {
                        $this->notifications->create('driver', 'Your report was returned: please complete missing details/evidence.', $reporterUserId);
                    }
                    $result = ['ok' => false, 'error' => 'Report is incomplete and has been returned to the driver.'];
                    break;
                }
            }

            if ($status === 'PENDING_APPROVAL' || $status === 'PENDING APPROVAL') {
                $status = 'PENDING APPROVAL';
                if ($this->isDuplicateAtValidation($rows, $row)) {
                    $row['status'] = 'REJECTED';
                    $row['review_notes'] = 'Rejected: duplicate report detected during validation.';
                    $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : null;
                    if ($reporterUserId !== null && $reporterUserId > 0) {
                        $this->notifications->create('driver', 'Your report was rejected due to duplicate/invalid validation findings.', $reporterUserId);
                    }
                    $result = ['ok' => false, 'error' => 'Duplicate report detected and rejected by compliance validation.'];
                    break;
                }
            }

            $row['status'] = $status;
            if ($notes !== null && trim($notes) !== '') {
                $row['review_notes'] = trim($notes);
            }
            if ($status === 'PENDING VALIDATION') {
                $this->notifications->create('compliance_officer', 'Violation report is ready for validation.');
            }
            if ($status === 'PENDING APPROVAL') {
                $this->notifications->create('vice_president', 'Validated violation report is ready for decision.');
            }
            if ($status === 'APPROVED') {
                $this->notifications->create('treasurer', 'Approved violation requires penalty billing.');
                $this->createPenaltyBillingForApprovedViolation($row);
                $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : null;
                if ($reporterUserId !== null && $reporterUserId > 0) {
                    $this->notifications->create('driver', 'Your report has been approved.', $reporterUserId);
                }
                $reportedUserId = $this->resolveDriverUserIdByName((string) ($row['reported_name'] ?? ''));
                if ($reportedUserId !== null && $reportedUserId > 0) {
                    $this->notifications->create('driver', 'Your violation has been approved and penalty details are now available.', $reportedUserId);
                }
            }
            if ($status === 'REJECTED') {
                $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : null;
                if ($reporterUserId !== null && $reporterUserId > 0) {
                    $this->notifications->create('driver', 'Your report was rejected.', $reporterUserId);
                }
            }
            $result = ['ok' => true];
            break;
        }
        $this->store->write($this->file, $rows);
        $this->audit->log('VIOLATION_STATUS_UPDATE', 'Violation ID ' . $id . ' set to ' . $status, $actor);
        return $result;
    }

    private function createPenaltyBillingForApprovedViolation(array $violation): void
    {
        $reportedDriver = trim((string) ($violation['reported_name'] ?? ''));
        $violationType = trim((string) ($violation['violation_type'] ?? ''));
        if ($reportedDriver === '' || $violationType === '') {
            return;
        }

        $rule = $this->rules->findPenaltyRuleByType($violationType);
        if (!is_array($rule)) {
            return;
        }

        $amount = $this->rules->resolvePenaltyAmount($rule);
        if ($amount <= 0) {
            return;
        }

        $isRange = (int) ($rule['is_range'] ?? 0) === 1;
        $min = (float) ($rule['min_amount'] ?? $amount);
        $max = (float) ($rule['max_amount'] ?? $amount);
        $reason = 'Penalty - ' . $violationType;
        if ($isRange && $max > $min) {
            $reason .= sprintf(' (policy range %.2f-%.2f, auto midpoint)', $min, $max);
        }

        $this->payments->createSystemBilling($reportedDriver, $reason, $amount);
    }

    private function resolveDriverUserIdByName(string $name): ?int
    {
        $driver = $this->resolveDriverByName($name);
        if (!is_array($driver)) {
            return null;
        }
        return (int) ($driver['user_id'] ?? 0);
    }

    /**
     * @return array{user_id:int,driver_id:string,plate_number:string}|null
     */
    private function resolveDriverByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT u.id, m.plate_number
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.role = 'driver' AND (m.name = :name OR u.username = :username)
             LIMIT 1"
        );
        $stmt->execute([
            ':name' => $name,
            ':username' => strtolower($name),
        ]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        $userId = (int) ($row['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        return [
            'user_id' => $userId,
            'driver_id' => 'DRV-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT),
            'plate_number' => trim((string) ($row['plate_number'] ?? '')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function findDuplicate(array $rows, array $input): ?array
    {
        $reportedName = strtolower(trim((string) ($input['reported_name'] ?? '')));
        $type = strtolower(trim((string) ($input['violation_type'] ?? '')));
        $rawIncident = trim((string) ($input['incident_datetime'] ?? ''));
        $incident = $rawIncident !== '' ? strtolower(date('Y-m-d H:i:s', strtotime($rawIncident))) : '';
        $location = strtolower(trim((string) ($input['incident_location'] ?? '')));
        foreach ($rows as $row) {
            if (strtolower((string) ($row['reported_name'] ?? '')) !== $reportedName) {
                continue;
            }
            if (strtolower((string) ($row['violation_type'] ?? '')) !== $type) {
                continue;
            }
            if (strtolower((string) ($row['incident_datetime'] ?? '')) !== $incident) {
                continue;
            }
            if (strtolower((string) ($row['incident_location'] ?? '')) !== $location) {
                continue;
            }
            return $row;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function missingRequiredFields(array $row): array
    {
        $required = [
            'reported_name' => 'reported driver',
            'reported_driver_id' => 'reported driver id',
            'reported_plate' => 'reported plate',
            'violation_type' => 'violation type',
            'description' => 'description',
            'incident_datetime' => 'incident date/time',
            'incident_location' => 'incident location',
            'evidence_path' => 'evidence',
        ];

        $missing = [];
        foreach ($required as $key => $label) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value === '') {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $current
     */
    private function isDuplicateAtValidation(array $rows, array $current): bool
    {
        $currentId = (int) ($current['id'] ?? 0);
        $reportedName = strtolower(trim((string) ($current['reported_name'] ?? '')));
        $type = strtolower(trim((string) ($current['violation_type'] ?? '')));
        $incident = strtolower(trim((string) ($current['incident_datetime'] ?? '')));
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id === $currentId) {
                continue;
            }
            $status = (string) ($row['status'] ?? '');
            if (!in_array($status, ['PENDING APPROVAL', 'APPROVED'], true)) {
                continue;
            }
            if (strtolower(trim((string) ($row['reported_name'] ?? ''))) !== $reportedName) {
                continue;
            }
            if (strtolower(trim((string) ($row['violation_type'] ?? ''))) !== $type) {
                continue;
            }
            if (strtolower(trim((string) ($row['incident_datetime'] ?? ''))) !== $incident) {
                continue;
            }
            return true;
        }
        return false;
    }
}
