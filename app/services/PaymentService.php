<?php

class PaymentService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private AuditService $audit;
    private PDO $pdo;
    private string $file = 'payments';

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->notifications = new NotificationService();
        $this->audit = new AuditService();
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        return $this->store->all($this->file);
    }

    public function find(int $id): ?array
    {
        $rows = $this->store->all($this->file);
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return null;
    }

    public function create(array $input): void
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'driver_name' => trim($input['driver_name']),
            'reason' => trim($input['reason']),
            'amount' => (float) $input['amount'],
            'amount_to_pay' => (float) $input['amount'],
            'amount_paid' => null,
            'status' => 'PENDING VERIFICATION',
            'due_date' => $this->resolveDueDate($input),
            'reference_no' => $this->generateReferenceNo(),
            'submitted_reference_no' => null,
            'proof_image_path' => null,
            'receipt_no' => null,
            'paid_at' => null,
            'is_flagged' => 0,
            'flag_reason' => null,
            'flagged_by' => null,
            'flagged_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $latest = end($rows);
        $targetUserId = $this->resolveDriverUserIdByName((string) ($latest['driver_name'] ?? ''));
        $this->notifications->create('driver', $this->buildPaymentNotificationMessage($latest ?: []), $targetUserId);
        $this->audit->log('PAYMENT_CREATE', 'Payment created for: ' . trim((string) $input['driver_name']), $actor);
    }

    public function verify(int $id, string $status): void
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        foreach ($rows as &$row) {
            if ((int) $row['id'] === $id) {
                $row['status'] = $status;
                if ($status === 'PAID') {
                    $row['receipt_no'] = $row['receipt_no'] ?? $this->generateReceiptNo();
                    if (!isset($row['amount_paid']) || (float) $row['amount_paid'] <= 0) {
                        $row['amount_paid'] = (float) ($row['amount_to_pay'] ?? $row['amount'] ?? 0);
                    }
                    $row['paid_at'] = date('Y-m-d H:i:s');
                }
                break;
            }
        }
        $this->store->write($this->file, $rows);
        $this->audit->log('PAYMENT_STATUS_UPDATE', 'Payment ID ' . $id . ' set to ' . $status, $actor);
    }

    public function createSystemBilling(string $driverName, string $reason, float $amount): void
    {
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'driver_name' => trim($driverName),
            'reason' => trim($reason),
            'amount' => $amount,
            'amount_to_pay' => $amount,
            'amount_paid' => null,
            'status' => 'PENDING VERIFICATION',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'reference_no' => $this->generateReferenceNo(),
            'submitted_reference_no' => null,
            'proof_image_path' => null,
            'receipt_no' => null,
            'paid_at' => null,
            'is_flagged' => 0,
            'flag_reason' => null,
            'flagged_by' => null,
            'flagged_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $latest = end($rows);
        $targetUserId = $this->resolveDriverUserIdByName((string) ($latest['driver_name'] ?? ''));
        $this->notifications->create('driver', $this->buildPaymentNotificationMessage($latest ?: []), $targetUserId);
    }

    public function submitProof(int $id, string $proofPath, string $submittedReferenceNo, float $submittedAmount): bool
    {
        $rows = $this->store->all($this->file);
        $updated = false;
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) !== $id) {
                continue;
            }
            if ((string) ($row['status'] ?? '') !== 'PENDING VERIFICATION') {
                break;
            }
            $row['proof_image_path'] = trim($proofPath);
            $row['submitted_reference_no'] = trim($submittedReferenceNo);
            $row['amount_paid'] = $submittedAmount > 0 ? $submittedAmount : null;
            $updated = true;
            break;
        }
        if ($updated) {
            $this->store->write($this->file, $rows);
        }
        return $updated;
    }

    public function flagPayment(int $id, string $reason, string $flaggedBy): bool
    {
        $rows = $this->store->all($this->file);
        $updated = false;
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) !== $id) {
                continue;
            }
            $row['is_flagged'] = 1;
            $row['flag_reason'] = trim($reason);
            $row['flagged_by'] = trim($flaggedBy);
            $row['flagged_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
        if ($updated) {
            $this->store->write($this->file, $rows);
        }
        return $updated;
    }

    public function recordCashPayment(int $id, float $amountPaid): bool
    {
        $rows = $this->store->all($this->file);
        $updated = false;
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) !== $id) {
                continue;
            }
            $row['amount_paid'] = $amountPaid;
            $row['status'] = 'PAID';
            $row['receipt_no'] = $row['receipt_no'] ?? $this->generateReceiptNo();
            $row['paid_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
        if ($updated) {
            $this->store->write($this->file, $rows);
        }
        return $updated;
    }

    private function resolveDueDate(array $input): string
    {
        $dueDate = trim((string) ($input['due_date'] ?? ''));
        if ($dueDate !== '') {
            return $dueDate;
        }
        return date('Y-m-d', strtotime('+7 days'));
    }

    private function generateReferenceNo(): string
    {
        return 'REF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    private function generateReceiptNo(): string
    {
        return 'RCPT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    private function buildPaymentNotificationMessage(array $payment): string
    {
        $amount = number_format((float) ($payment['amount'] ?? 0), 2);
        $reason = (string) ($payment['reason'] ?? 'Payment');
        $dueDate = (string) ($payment['due_date'] ?? '');
        $referenceNo = (string) ($payment['reference_no'] ?? '');
        return sprintf(
            '%s | Amount: PHP %s | Due: %s | Ref: %s',
            $reason,
            $amount,
            $dueDate !== '' ? $dueDate : 'N/A',
            $referenceNo !== '' ? $referenceNo : 'N/A'
        );
    }

    private function resolveDriverUserIdByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT u.id
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
        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
