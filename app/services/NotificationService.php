<?php

class NotificationService
{
    private JsonStore $store;
    private string $file = 'notifications';

    public function __construct()
    {
        $this->store = new JsonStore();
    }

    public function create(string $targetRole, string $message): void
    {
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'target_role' => $targetRole,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
    }

    public function byRole(string $role): array
    {
        $rows = $this->store->all($this->file);
        return array_values(array_filter($rows, static fn ($row) => ($row['target_role'] ?? '') === $role));
    }
}
