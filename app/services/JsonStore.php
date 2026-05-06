<?php

class JsonStore
{
    private PDO $pdo;

    /**
     * @var array<string, string>
     */
    private array $tableMap = [
        'members' => 'members',
        'violations' => 'violations',
        'payments' => 'payments',
        'notifications' => 'notifications',
        'audit_logs' => 'audit_logs',
    ];

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(string $file): array
    {
        $table = $this->table($file);
        if ($table === null) {
            return [];
        }

        $stmt = $this->pdo->query("SELECT * FROM `{$table}` ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function write(string $file, array $data): void
    {
        $table = $this->table($file);
        if ($table === null) {
            return;
        }

        $columns = $this->tableColumns($table);
        if ($columns === []) {
            return;
        }

        $this->pdo->beginTransaction();
        try {
            foreach ($data as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $insertRow = [];
                foreach ($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $insertRow[$column] = $row[$column];
                    }
                }

                if ($insertRow === []) {
                    continue;
                }

                $fields = array_keys($insertRow);
                $placeholders = array_map(static fn ($field) => ':' . $field, $fields);
                $updateAssignments = array_values(array_filter(
                    array_map(static fn ($field) => $field === 'id' ? null : "`{$field}` = VALUES(`{$field}`)", $fields)
                ));
                $sql = sprintf(
                    'INSERT INTO `%s` (%s) VALUES (%s)%s',
                    $table,
                    implode(', ', array_map(static fn ($f) => "`{$f}`", $fields)),
                    implode(', ', $placeholders),
                    $updateAssignments === [] ? '' : ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateAssignments)
                );
                $stmt = $this->pdo->prepare($sql);
                foreach ($insertRow as $field => $value) {
                    $stmt->bindValue(':' . $field, $value);
                }
                $stmt->execute();
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function nextId(string $file): int
    {
        $table = $this->table($file);
        if ($table === null) {
            return 1;
        }

        $stmt = $this->pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `{$table}`");
        $nextId = (int) $stmt->fetchColumn();
        return $nextId > 0 ? $nextId : 1;
    }

    private function table(string $file): ?string
    {
        return $this->tableMap[$file] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function tableColumns(string $table): array
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$table}`");
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $columns = [];
        foreach ($rows as $row) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '') {
                $columns[] = $field;
            }
        }

        return $columns;
    }
}
