<?php

class SettingsService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                u.id,
                u.username,
                u.password,
                u.role,
                u.member_id,
                m.name,
                m.address,
                m.contact_number,
                m.license_number,
                m.plate_number
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateProfile(int $userId, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $contactNumber = trim((string) ($input['contact_number'] ?? ''));

        if ($name === '' || $username === '') {
            return ['ok' => false, 'error' => 'Name and username are required.'];
        }

        $checkStmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1');
        $checkStmt->execute([
            ':username' => $username,
            ':id' => $userId,
        ]);
        if (is_array($checkStmt->fetch())) {
            return ['ok' => false, 'error' => 'Username is already taken.'];
        }

        $account = $this->getByUserId($userId);
        if (!is_array($account)) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }

        $memberId = (int) ($account['member_id'] ?? 0);
        if ($memberId <= 0) {
            return ['ok' => false, 'error' => 'Member profile is missing.'];
        }

        $this->pdo->beginTransaction();
        try {
            $userStmt = $this->pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
            $userStmt->execute([
                ':username' => $username,
                ':id' => $userId,
            ]);

            $memberStmt = $this->pdo->prepare(
                'UPDATE members
                 SET name = :name, address = :address, contact_number = :contact_number
                 WHERE id = :id'
            );
            $memberStmt->execute([
                ':name' => $name,
                ':address' => $address,
                ':contact_number' => $contactNumber,
                ':id' => $memberId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'Unable to update account information.'];
        }

        return ['ok' => true];
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $currentPassword = trim($currentPassword);
        $newPassword = trim($newPassword);
        $confirmPassword = trim($confirmPassword);

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return ['ok' => false, 'error' => 'All password fields are required.'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['ok' => false, 'error' => 'New password and confirmation do not match.'];
        }
        if (strlen($newPassword) < 6) {
            return ['ok' => false, 'error' => 'New password must be at least 6 characters long.'];
        }

        $account = $this->getByUserId($userId);
        if (!is_array($account)) {
            return ['ok' => false, 'error' => 'Account not found.'];
        }
        if ((string) ($account['password'] ?? '') !== $currentPassword) {
            return ['ok' => false, 'error' => 'Current password is incorrect.'];
        }

        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute([
            ':password' => $newPassword,
            ':id' => $userId,
        ]);

        return ['ok' => true];
    }
}
