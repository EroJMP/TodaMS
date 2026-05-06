<?php

class SystemController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $user = $_SESSION['user'];
        $pageTitle = 'System Tools';
        $currentRoute = '/system-tools';
        $backups = $this->listBackups();
        require __DIR__ . '/../../resources/views/system/index.php';
    }

    public function backup(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $pdo = Database::connection();
        $tables = ['users', 'members', 'violations', 'payments', 'notifications', 'audit_logs', 'fee_rules', 'penalty_rules'];
        $payload = [];
        foreach ($tables as $table) {
            $stmt = $pdo->query('SELECT * FROM ' . $table . ' ORDER BY id ASC');
            $payload[$table] = $stmt->fetchAll();
        }

        $backupDir = __DIR__ . '/../../storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $filename = 'todams-backup-' . date('Ymd-His') . '.json';
        file_put_contents($backupDir . '/' . $filename, json_encode($payload, JSON_PRETTY_PRINT));
        Response::redirect('/system-tools', 'success', 'Backup created: ' . $filename);
    }

    private function listBackups(): array
    {
        $backupDir = __DIR__ . '/../../storage/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.json');
        if (!is_array($files)) {
            return [];
        }

        rsort($files);
        return array_map(static fn ($file) => basename($file), $files);
    }
}
