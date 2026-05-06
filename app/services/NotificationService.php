<?php

class NotificationService
{
    private JsonStore $store;
    private string $file = 'notifications';

    public function __construct()
    {
        $this->store = new JsonStore();
    }

    public function create(string $targetRole, string $message, ?int $targetUserId = null): void
    {
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'target_user_id' => $targetUserId,
            'target_role' => $targetRole,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
    }

    public function byRecipient(string $role, ?int $userId = null): array
    {
        $rows = $this->store->all($this->file);
        $filtered = array_values(array_filter($rows, static function ($row) use ($role, $userId) {
            $targetRole = (string) ($row['target_role'] ?? '');
            $targetUserId = isset($row['target_user_id']) ? (int) $row['target_user_id'] : null;
            if ($role === 'driver') {
                // Drivers should only see notifications explicitly targeted to their own account.
                return $targetUserId !== null && $targetUserId > 0 && $userId !== null && $targetUserId === $userId;
            }
            if ($targetUserId !== null && $targetUserId > 0) {
                return $userId !== null && $targetUserId === $userId;
            }
            return $targetRole === $role;
        }));

        usort($filtered, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $filtered;
    }
}
