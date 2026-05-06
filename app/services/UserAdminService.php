<?php

class UserAdminService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT u.id, u.username, u.member_id, m.name AS member_name, u.role, u.is_active, u.created_at
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             ORDER BY u.id ASC'
        );
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function create(array $input): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password, member_id, role, is_active, created_at)
             VALUES (:username, :password, :member_id, :role, 1, :created_at)'
        );
        $stmt->execute([
            ':username' => trim((string) $input['username']),
            ':password' => trim((string) $input['password']),
            ':member_id' => (int) ($input['member_id'] ?? 0),
            ':role' => trim((string) $input['role']),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateStatus(int $id, int $isActive): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $stmt->execute([
            ':is_active' => $isActive,
            ':id' => $id,
        ]);
    }
}
