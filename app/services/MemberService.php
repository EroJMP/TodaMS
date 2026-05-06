<?php

class MemberService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private AuditService $audit;
    private PDO $pdo;
    private string $file = 'members';

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

    public function create(array $input): void
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'name' => trim($input['name']),
            'address' => trim($input['address']),
            'contact_number' => trim($input['contact_number']),
            'license_number' => trim($input['license_number']),
            'plate_number' => trim($input['plate_number']),
            'id_doc_path' => trim((string) ($input['id_doc_path'] ?? '')),
            'license_doc_path' => trim((string) ($input['license_doc_path'] ?? '')),
            'orcr_doc_path' => trim((string) ($input['orcr_doc_path'] ?? '')),
            'status' => 'PENDING APPROVAL',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $this->notifications->create('vice_president', 'New member application submitted for approval.');
        $this->audit->log('MEMBER_CREATE', 'Member submitted for approval: ' . trim((string) $input['name']), $actor);
    }

    public function existsByPlateNumber(string $plateNumber): bool
    {
        $needle = strtoupper(trim($plateNumber));
        if ($needle === '') {
            return false;
        }
        $rows = $this->store->all($this->file);
        foreach ($rows as $row) {
            $current = strtoupper(trim((string) ($row['plate_number'] ?? '')));
            if ($current === $needle) {
                return true;
            }
        }
        return false;
    }

    public function existsByLicenseNumber(string $licenseNumber): bool
    {
        $needle = strtoupper(trim($licenseNumber));
        if ($needle === '') {
            return false;
        }
        $rows = $this->store->all($this->file);
        foreach ($rows as $row) {
            $current = strtoupper(trim((string) ($row['license_number'] ?? '')));
            if ($current === $needle) {
                return true;
            }
        }
        return false;
    }

    public function approveWithCredentials(int $id, string $username, string $password): array
    {
        $username = strtolower(trim($username));
        $password = trim($password);
        if ($username === '' || $password === '') {
            return ['ok' => false, 'error' => 'Username and password are required for approval.'];
        }
        if (preg_match('/^[a-z0-9._-]{4,30}$/', $username) !== 1) {
            return ['ok' => false, 'error' => 'Username must be 4-30 chars and only use letters, numbers, dot, underscore, or dash.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Password must be at least 6 characters.'];
        }

        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        $found = false;
        foreach ($rows as &$row) {
            if ((int) ($row['id'] ?? 0) !== $id) {
                continue;
            }
            $row['status'] = 'ACTIVE';
            $found = true;
            break;
        }
        if (!$found) {
            return ['ok' => false, 'error' => 'Member record not found.'];
        }
        $this->store->write($this->file, $rows);

        $accountResult = $this->createDriverAccountForMember($id, $username, $password);
        if (!(bool) ($accountResult['ok'] ?? false)) {
            // Roll back member status if account creation fails.
            foreach ($rows as &$row) {
                if ((int) ($row['id'] ?? 0) === $id) {
                    $row['status'] = 'PENDING APPROVAL';
                    break;
                }
            }
            $this->store->write($this->file, $rows);
            return $accountResult;
        }

        $secretaryUserId = $this->resolveActiveUserIdByRole('secretary');
        $this->notifications->create('secretary', 'Member approved and account activated.', $secretaryUserId);
        $this->audit->log('MEMBER_STATUS_UPDATE', 'Member ID ' . $id . ' set to ACTIVE', $actor);
        return ['ok' => true];
    }

    public function updateStatus(int $id, string $status): void
    {
        $actor = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $rows = $this->store->all($this->file);
        foreach ($rows as &$row) {
            if ((int) $row['id'] === $id) {
                $row['status'] = $status;
                break;
            }
        }
        $this->store->write($this->file, $rows);
        if ($status === 'DECLINED') {
            $secretaryUserId = $this->resolveActiveUserIdByRole('secretary');
            $this->notifications->create('secretary', 'Member application was rejected by Vice President.', $secretaryUserId);
        }
        $this->audit->log('MEMBER_STATUS_UPDATE', 'Member ID ' . $id . ' set to ' . $status, $actor);
    }

    private function createDriverAccountForMember(int $memberId, string $username, string $password): array
    {
        $memberStmt = $this->pdo->prepare('SELECT id, name FROM members WHERE id = :id LIMIT 1');
        $memberStmt->execute([':id' => $memberId]);
        $member = $memberStmt->fetch();
        if (!is_array($member)) {
            return ['ok' => false, 'error' => 'Approved member record is invalid.'];
        }

        $existingStmt = $this->pdo->prepare('SELECT id FROM users WHERE member_id = :member_id LIMIT 1');
        $existingStmt->execute([':member_id' => $memberId]);
        if (is_array($existingStmt->fetch())) {
            return ['ok' => false, 'error' => 'This member already has an account.'];
        }

        $checkUsernameStmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $checkUsernameStmt->execute([':username' => $username]);
        if (is_array($checkUsernameStmt->fetch())) {
            return ['ok' => false, 'error' => 'Username already exists. Please choose a different one.'];
        }

        try {
            $insertStmt = $this->pdo->prepare(
                'INSERT INTO users (username, password, member_id, role, is_active, created_at)
                 VALUES (:username, :password, :member_id, :role, 1, :created_at)'
            );
            $insertStmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':member_id' => $memberId,
                ':role' => 'driver',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            $message = strtolower((string) $e->getMessage());
            if ((string) $e->getCode() === '23000' || str_contains($message, 'duplicate')) {
                if (str_contains($message, 'username')) {
                    return ['ok' => false, 'error' => 'Username already exists. Please choose a different one.'];
                }
                if (str_contains($message, 'member_id') || str_contains($message, 'uniq_users_member_id')) {
                    return ['ok' => false, 'error' => 'This member already has an account.'];
                }
                return ['ok' => false, 'error' => 'Duplicate account data detected. Please use a different username.'];
            }
            return ['ok' => false, 'error' => 'Unable to create account right now. Please try again.'];
        }
        return ['ok' => true];
    }

    private function generateBaseDriverUsername(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9]+/', '.', $normalized) ?? $normalized;
        $normalized = trim($normalized, '.');
        if ($normalized === '') {
            return 'driver';
        }
        return substr($normalized, 0, 30);
    }

    private function resolveActiveUserIdByRole(string $role): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE role = :role AND is_active = 1 ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute([':role' => trim($role)]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
