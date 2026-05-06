<?php

class AuthService
{
    public function attempt(string $username, string $password): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.username, u.password, u.role, m.name
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.username = :username AND u.is_active = 1
             ORDER BY u.id DESC
             LIMIT 1'
        );
        $stmt->execute([':username' => trim($username)]);
        $user = $stmt->fetch();
        if (!is_array($user)) {
            return null;
        }

        if ((string) $user['password'] !== $password) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'name' => (string) ($user['name'] ?? $user['username']),
            'role' => (string) $user['role'],
        ];
    }
}
