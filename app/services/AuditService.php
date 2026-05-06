<?php

class AuditService
{
    private JsonStore $store;
    private PDO $pdo;
    private string $file = 'audit_logs';

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->pdo = Database::connection();
    }

    public function log(string $action, string $details, ?array $user = null): void
    {
        $actor = $this->resolveActor();
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'action' => $action,
            'details' => $details,
            'user_id' => $actor['id'],
            'name' => $actor['name'],
            'role' => $actor['role'],
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
    }

    public function all(): array
    {
        $rows = $this->store->all($this->file);
        $rows = $this->hydrateActorIdentity($rows);
        usort($rows, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $rows;
    }

    /**
     * @return array{id: ?int, name: string, role: string}
     */
    private function resolveActor(): array
    {
        $sessionUser = $_SESSION['user'] ?? null;
        $username = is_array($sessionUser) ? trim((string) ($sessionUser['username'] ?? '')) : '';
        if ($username !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT u.id, u.role, m.name
                 FROM users u
                 INNER JOIN members m ON m.id = u.member_id
                 WHERE u.username = :username AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return [
                    'id' => (int) $row['id'],
                    'name' => (string) ($row['name'] ?? 'Unknown'),
                    'role' => (string) ($row['role'] ?? 'guest'),
                ];
            }
        }

        return [
            'id' => null,
            'name' => 'Guest',
            'role' => 'guest',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function hydrateActorIdentity(array $rows): array
    {
        $userIds = [];
        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? 0);
            if ($id > 0) {
                $userIds[$id] = true;
            }
        }

        if ($userIds === []) {
            return $rows;
        }

        $idList = array_keys($userIds);
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT u.id AS user_id, u.role, m.name
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.id IN ({$placeholders})"
        );
        foreach ($idList as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $identityRows = $stmt->fetchAll();

        $identityMap = [];
        if (is_array($identityRows)) {
            foreach ($identityRows as $identity) {
                $uid = (int) ($identity['user_id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                $identityMap[$uid] = [
                    'name' => (string) ($identity['name'] ?? ''),
                    'role' => (string) ($identity['role'] ?? ''),
                ];
            }
        }

        foreach ($rows as &$row) {
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid > 0 && isset($identityMap[$uid])) {
                $row['name'] = $identityMap[$uid]['name'];
                $row['role'] = $identityMap[$uid]['role'];
            }
        }

        return $rows;
    }
}
