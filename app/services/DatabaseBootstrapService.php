<?php

class DatabaseBootstrapService
{
    private static bool $bootstrapped = false;

    public function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        try {
            $this->ensureDatabaseExists();
            $pdo = Database::connection();
            $this->runSchema($pdo);
            $this->seedMembersFromXlsm($pdo);
            $this->migrateMembersForPhase5($pdo);
            $this->migrateUsersToMemberReference($pdo);
            $this->ensureUniqueUsernames($pdo);
            $this->migrateAuditLogsToUserReference($pdo);
            $this->migratePaymentsForPhase5($pdo);
            $this->migrateViolationsForPhase5($pdo);
            $this->migrateNotificationsForPhase5($pdo);
            $this->removeRecurringAutoPaymentsForTesting($pdo);
            $this->seedUsers($pdo);
            $this->ensureRoleAccountProfiles($pdo);
            $this->seedDriverAccountsFromMembers($pdo);
            $this->seedViolations($pdo);
            $this->ensureViolationTestingData($pdo);
            $this->seedPayments($pdo);
            $this->seedFeeRules($pdo);
            $this->seedPenaltyRules($pdo);
            $this->seedNotifications($pdo);
            $this->seedAuditLogs($pdo);
            self::$bootstrapped = true;
        } catch (Throwable $e) {
            $this->logBootstrapError($e);
            // Keep app accessible even when DB is temporarily unavailable.
            self::$bootstrapped = true;
        }
    }

    private function ensureDatabaseExists(): void
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $config['database']) ?: 'todams';
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function runSchema(PDO $pdo): void
    {
        $schemaPath = __DIR__ . '/../../database/schema.sql';
        if (!file_exists($schemaPath)) {
            return;
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false || trim($sql) === '') {
            return;
        }

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    }

    private function seedUsers(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $users = [
            ['admin', 'admin123', 'Jane Doe', 'super_admin'],
            ['vp', 'vp123', 'Kiko Pangilinan', 'vice_president'],
            ['secretary', 'secretary123', 'Mang Politiko', 'secretary'],
            ['treasurer', 'treasurer123', 'Magna Cum', 'treasurer'],
            ['compliance', 'compliance123', 'John Doer', 'compliance_officer'],
            ['driver', 'driver', 'Juan Delacruz', 'driver'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, member_id, role, is_active, created_at)
             VALUES (:username, :password, :member_id, :role, 1, :created_at)'
        );

        $createdAt = date('Y-m-d H:i:s');
        foreach ($users as [$username, $password, $name, $role]) {
            $memberId = $this->ensureMemberRecord($pdo, $name);
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':member_id' => $memberId,
                ':role' => $role,
                ':created_at' => $createdAt,
            ]);
        }
    }

    private function seedMembersFromXlsm(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $importer = new XlsmMemberImporter();
        $filePath = __DIR__ . '/../../public/assets/data/OTODA Member List.xlsm';
        $rows = $importer->import($filePath);
        if ($rows === []) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO members (
                id, name, address, contact_number, license_number, plate_number,
                toda_id, body_number, status, created_at
            ) VALUES (
                :id, :name, :address, :contact_number, :license_number, :plate_number,
                :toda_id, :body_number, :status, :created_at
            )'
        );

        $nextId = 1;
        $createdAt = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $stmt->execute([
                ':id' => $nextId++,
                ':name' => $row['name'],
                ':address' => $row['address'],
                ':contact_number' => $row['contact_number'],
                ':license_number' => $row['toda_id'],
                ':plate_number' => $row['body_number'],
                ':toda_id' => $row['toda_id'],
                ':body_number' => $row['body_number'],
                ':status' => 'ACTIVE',
                ':created_at' => $createdAt,
            ]);
        }
    }

    private function logBootstrapError(Throwable $e): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $message = sprintf(
            "[%s] %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        file_put_contents($logDir . '/database-bootstrap.log', $message, FILE_APPEND);
    }

    private function seedViolations(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM violations')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'Jane Doe', 'Juan Delacruz', 'Overcharging Passengers', 'Reported overcharging at terminal exit.', 'PENDING APPROVAL', 'DRV-001', 'ABC-101', '2026-05-01 08:10:00', 'Main Terminal Gate', 'uploads/evidence/evidence-1.jpg'],
            [2, 'Kiko Pangilinan', 'Juan Delacruz', 'Stealing Passengers', 'Passenger diverted from queue.', 'PENDING APPROVAL', 'DRV-001', 'ABC-101', '2026-05-01 10:20:00', 'Queue Lane A', 'uploads/evidence/evidence-2.jpg'],
            [3, 'Mang Politiko', 'Roger K. Santiago', 'Violating Terminal Rules', 'Ignored assigned queue lane.', 'PENDING APPROVAL', 'DRV-010', 'XYZ-001', '2026-05-02 07:35:00', 'Terminal Bay 2', 'uploads/evidence/evidence-3.mp4'],
            [4, 'Magna Cum', 'Dennis A. Flores', 'No ID or Body Number', 'Unit has no visible body number.', 'PENDING APPROVAL', 'DRV-011', 'XYZ-002', '2026-05-02 09:55:00', 'Dispatch Point', 'uploads/evidence/evidence-4.jpg'],
            [5, 'John Doer', 'Alberto G. Reyes', 'Not Following Rotation', 'Skipped rotation order.', 'PENDING APPROVAL', 'DRV-012', 'XYZ-003', '2026-05-03 13:15:00', 'Queue Lane B', 'uploads/evidence/evidence-5.jpg'],
            [6, 'Roger K. Santiago', 'Cesar R. Bautista', 'Disobeying TODA Officers', 'Did not follow marshal instruction.', 'PENDING APPROVAL', 'DRV-013', 'XYZ-004', '2026-05-03 15:25:00', 'Terminal Exit', 'uploads/evidence/evidence-6.mp4'],
            [7, 'Dennis A. Flores', 'Edgar B. Salazar', 'Discourtesy', 'Verbal altercation with commuter.', 'PENDING VALIDATION', 'DRV-014', 'XYZ-005', '2026-05-04 11:40:00', 'Terminal Waiting Area', 'uploads/evidence/evidence-7.jpg'],
            [8, 'Alberto G. Reyes', 'Juan Delacruz', 'Trouble or Fighting in Queue', 'Driver caused disruption in queue.', 'SUBMITTED', 'DRV-015', 'XYZ-006', '2026-05-04 16:10:00', 'Queue Entrance', 'uploads/evidence/evidence-8.mp4'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO violations (
                id, reporter_name, reported_name, violation_type, description, status,
                reported_driver_id, reported_plate, incident_datetime, incident_location, evidence_path, created_at
             ) VALUES (
                :id, :reporter_name, :reported_name, :violation_type, :description, :status,
                :reported_driver_id, :reported_plate, :incident_datetime, :incident_location, :evidence_path, :created_at
             )'
        );

        foreach ($rows as [$id, $reporter, $reported, $type, $description, $status, $driverId, $plate, $incidentDateTime, $location, $evidencePath]) {
            $stmt->execute([
                ':id' => $id,
                ':reporter_name' => $reporter,
                ':reported_name' => $reported,
                ':violation_type' => $type,
                ':description' => $description,
                ':status' => $status,
                ':reported_driver_id' => $driverId,
                ':reported_plate' => $plate,
                ':incident_datetime' => $incidentDateTime,
                ':incident_location' => $location,
                ':evidence_path' => $evidencePath,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedPayments(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'Roger K. Santiago', 'Penalty - Overcharging Passengers', 300.00, 'PENDING VERIFICATION'],
            [2, 'Dennis A. Flores', 'Activity Contribution - Assembly', 100.00, 'PAID'],
            [3, 'Alberto G. Reyes', 'Penalty - Violating Terminal Rules', 500.00, 'PENDING VERIFICATION'],
            [4, 'Cesar R. Bautista', 'Penalty - Stealing Passengers', 200.00, 'REJECTED'],
            [5, 'Edgar B. Salazar', 'Activity Contribution - Seminar', 100.00, 'PENDING VERIFICATION'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO payments (id, driver_name, reason, amount, amount_to_pay, amount_paid, status, created_at)
             VALUES (:id, :driver_name, :reason, :amount, :amount_to_pay, :amount_paid, :status, :created_at)'
        );

        foreach ($rows as [$id, $driverName, $reason, $amount, $status]) {
            $stmt->execute([
                ':id' => $id,
                ':driver_name' => $driverName,
                ':reason' => $reason,
                ':amount' => $amount,
                ':amount_to_pay' => $amount,
                ':amount_paid' => $status === 'PAID' ? $amount : null,
                ':status' => $status,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function removeRecurringAutoPaymentsForTesting(PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "DELETE FROM payments
             WHERE reason LIKE 'Monthly Dues - %'
                OR reason LIKE 'Membership Renewal - %'"
        );
        $stmt->execute();
    }

    private function ensureViolationTestingData(PDO $pdo): void
    {
        $pdo->exec(
            "UPDATE violations
             SET incident_datetime = COALESCE(incident_datetime, created_at, NOW()),
                 incident_location = COALESCE(NULLIF(incident_location, ''), 'Main Terminal'),
                 evidence_path = COALESCE(NULLIF(evidence_path, ''), CONCAT('uploads/evidence/legacy-', id, '.jpg'))"
        );

        $pendingCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM violations WHERE status = 'PENDING APPROVAL'"
        )->fetchColumn();

        if ($pendingCount >= 6) {
            return;
        }

        $insert = $pdo->prepare(
            'INSERT INTO violations (
                id, reporter_name, reported_name, violation_type, description, status,
                reported_driver_id, reported_plate, incident_datetime, incident_location, evidence_path, created_at
            ) VALUES (
                :id, :reporter_name, :reported_name, :violation_type, :description, :status,
                :reported_driver_id, :reported_plate, :incident_datetime, :incident_location, :evidence_path, :created_at
            )'
        );

        $needed = 6 - $pendingCount;
        $nextId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM violations')->fetchColumn();
        for ($i = 0; $i < $needed; $i++) {
            $reporters = ['Jane Doe', 'Kiko Pangilinan', 'Mang Politiko', 'Magna Cum', 'John Doer', 'Roger K. Santiago', 'Dennis A. Flores'];
            $insert->execute([
                ':id' => $nextId++,
                ':reporter_name' => $reporters[$i % count($reporters)],
                ':reported_name' => 'Juan Delacruz',
                ':violation_type' => 'Violating Terminal Rules',
                ':description' => 'Auto-seeded pending approval case for VP testing.',
                ':status' => 'PENDING APPROVAL',
                ':reported_driver_id' => 'DRV-001',
                ':reported_plate' => 'ABC-101',
                ':incident_datetime' => date('Y-m-d H:i:s', strtotime('-' . (2 + $i) . ' hours')),
                ':incident_location' => 'Terminal Queue',
                ':evidence_path' => 'uploads/evidence/auto-pending-' . ($i + 1) . '.jpg',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedNotifications(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'vice_president', 'You have pending violations for final decision.'],
            [2, 'secretary', 'New violation reports were submitted and need encoding review.'],
            [3, 'treasurer', 'Approved violations require penalty billing updates.'],
            [4, 'driver', 'Please settle pending dues before due date.'],
            [5, 'compliance_officer', 'There are records pending validation and audit checks.'],
            [6, 'super_admin', 'Daily activity summary is now available in reports.'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO notifications (id, target_role, message, created_at)
             VALUES (:id, :target_role, :message, :created_at)'
        );

        foreach ($rows as [$id, $role, $message]) {
            $stmt->execute([
                ':id' => $id,
                ':target_role' => $role,
                ':message' => $message,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedAuditLogs(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'SYSTEM_BOOTSTRAP', 'Initial schema and seed data completed.', null, 'System', 'super_admin', '127.0.0.1'],
            [2, 'LOGIN_SUCCESS', 'Initial admin account login test passed.', 1, 'Super Admin', 'super_admin', '127.0.0.1'],
            [3, 'DATA_IMPORT', 'Imported member records from OTODA Member List.xlsm.', null, 'System', 'super_admin', '127.0.0.1'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (id, action, details, user_id, name, role, ip, created_at)
             VALUES (:id, :action, :details, :user_id, :name, :role, :ip, :created_at)'
        );

        foreach ($rows as [$id, $action, $details, $userId, $name, $role, $ip]) {
            $stmt->execute([
                ':id' => $id,
                ':action' => $action,
                ':details' => $details,
                ':user_id' => $userId,
                ':name' => $name,
                ':role' => $role,
                ':ip' => $ip,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedFeeRules(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM fee_rules')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'monthly_dues', 'Monthly dues', 200.00, 'monthly'],
            [2, 'terminal_fee', 'Terminal fee', 500.00, 'monthly'],
            [3, 'membership_initial', 'Membership initial', 1500.00, 'one_time'],
            [4, 'membership_renewal', 'Membership renewal', 200.00, 'yearly'],
            [5, 'event_contribution', 'Membership contribution', 100.00, 'per_event'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO fee_rules (id, fee_key, label, amount, period, created_at)
             VALUES (:id, :fee_key, :label, :amount, :period, :created_at)'
        );

        foreach ($rows as [$id, $key, $label, $amount, $period]) {
            $stmt->execute([
                ':id' => $id,
                ':fee_key' => $key,
                ':label' => $label,
                ':amount' => $amount,
                ':period' => $period,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedPenaltyRules(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM penalty_rules')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            [1, 'not_following_rotation', 'Not following the rotation', 100.00, 100.00, 0],
            [2, 'stealing_passengers', 'Stealing passengers', 200.00, 200.00, 0],
            [3, 'suspension', 'Suspension', 100.00, 100.00, 0],
            [4, 'no_id_body_number', 'No ID or body number on the tricycle', 200.00, 200.00, 0],
            [5, 'late_payment', 'Late payment (Monthly dues or contribution)', 50.00, 100.00, 1],
            [6, 'violating_terminal_rules', 'Violating terminal rules', 100.00, 1500.00, 1],
            [7, 'trouble_fighting_queue', 'Trouble or fighting in the queue/line', 500.00, 500.00, 0],
            [8, 'disobeying_toda_officers', 'Disobeying TODA officers', 200.00, 200.00, 0],
            [9, 'overcharging_passengers', 'Overcharging passengers', 300.00, 300.00, 0],
            [10, 'colorum', 'Colorum (Unregistered/illegal operation)', 2000.00, 2000.00, 0],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO penalty_rules (id, penalty_key, label, min_amount, max_amount, is_range, created_at)
             VALUES (:id, :penalty_key, :label, :min_amount, :max_amount, :is_range, :created_at)'
        );

        foreach ($rows as [$id, $key, $label, $min, $max, $isRange]) {
            $stmt->execute([
                ':id' => $id,
                ':penalty_key' => $key,
                ':label' => $label,
                ':min_amount' => $min,
                ':max_amount' => $max,
                ':is_range' => $isRange,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedDriverAccountsFromMembers(PDO $pdo): void
    {
        $membersStmt = $pdo->query("SELECT id, name, status FROM members WHERE status = 'ACTIVE' ORDER BY id ASC");
        $members = $membersStmt->fetchAll();
        if (!is_array($members) || $members === []) {
            return;
        }

        $checkByMemberStmt = $pdo->prepare(
            'SELECT id FROM users WHERE member_id = :member_id LIMIT 1'
        );
        $checkByUsernameStmt = $pdo->prepare(
            'SELECT id FROM users WHERE username = :username LIMIT 1'
        );
        $insertStmt = $pdo->prepare(
            'INSERT INTO users (username, password, member_id, role, is_active, created_at)
             VALUES (:username, :password, :member_id, :role, 1, :created_at)'
        );

        foreach ($members as $member) {
            $memberName = trim((string) ($member['name'] ?? ''));
            $memberId = (int) ($member['id'] ?? 0);
            if ($memberName === '' || $memberId <= 0) {
                continue;
            }

            $checkByMemberStmt->execute([':member_id' => $memberId]);
            $existing = $checkByMemberStmt->fetch();
            if (is_array($existing)) {
                continue;
            }

            $baseUsername = $this->generateBaseDriverUsername($memberName);
            $candidateUsername = $baseUsername;
            $suffix = 1;

            while (true) {
                $checkByUsernameStmt->execute([':username' => $candidateUsername]);
                $usernameTaken = $checkByUsernameStmt->fetch();
                if (!is_array($usernameTaken)) {
                    break;
                }
                $candidateUsername = $baseUsername . $suffix;
                $suffix++;
            }

            // For easier testing, default password matches generated username.
            $defaultPassword = $candidateUsername;

            $insertStmt->execute([
                ':username' => $candidateUsername,
                ':password' => $defaultPassword,
                ':member_id' => $memberId,
                ':role' => 'driver',
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function migrateUsersToMemberReference(PDO $pdo): void
    {
        $hasMemberId = $this->columnExists($pdo, 'users', 'member_id');
        if (!$hasMemberId) {
            $pdo->exec('ALTER TABLE users ADD COLUMN member_id INT NULL AFTER password');
        }

        $hasName = $this->columnExists($pdo, 'users', 'name');
        if ($hasName) {
            $rows = $pdo->query('SELECT id, name, member_id FROM users ORDER BY id ASC')->fetchAll();
            if (is_array($rows)) {
                $updateStmt = $pdo->prepare('UPDATE users SET member_id = :member_id WHERE id = :id');
                foreach ($rows as $row) {
                    $existingMemberId = (int) ($row['member_id'] ?? 0);
                    if ($existingMemberId > 0) {
                        continue;
                    }
                    $name = trim((string) ($row['name'] ?? ''));
                    if ($name === '') {
                        $name = 'User ' . (int) ($row['id'] ?? 0);
                    }
                    $memberId = $this->ensureMemberRecord($pdo, $name);
                    $updateStmt->execute([
                        ':member_id' => $memberId,
                        ':id' => (int) ($row['id'] ?? 0),
                    ]);
                }
            }

            $pdo->exec('ALTER TABLE users DROP COLUMN name');
        }

        $nullMemberCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE member_id IS NULL OR member_id = 0')->fetchColumn();
        if ($nullMemberCount > 0) {
            $rows = $pdo->query('SELECT id, username FROM users WHERE member_id IS NULL OR member_id = 0')->fetchAll();
            if (is_array($rows)) {
                $updateStmt = $pdo->prepare('UPDATE users SET member_id = :member_id WHERE id = :id');
                foreach ($rows as $row) {
                    $memberId = $this->ensureMemberRecord($pdo, (string) ($row['username'] ?? 'User'));
                    $updateStmt->execute([
                        ':member_id' => $memberId,
                        ':id' => (int) ($row['id'] ?? 0),
                    ]);
                }
            }
        }

        $pdo->exec('ALTER TABLE users MODIFY COLUMN member_id INT NOT NULL');

        if (!$this->indexExists($pdo, 'users', 'uniq_users_member_id')) {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_member_id (member_id)');
        }

        if (!$this->foreignKeyExists($pdo, 'users', 'fk_users_member')) {
            $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE RESTRICT ON UPDATE CASCADE');
        }
    }

    private function ensureUniqueUsernames(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, username FROM users ORDER BY id ASC')->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return;
        }

        $seen = [];
        $updateStmt = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $username = trim((string) ($row['username'] ?? ''));
            if ($id <= 0 || $username === '') {
                continue;
            }

            $key = strtolower($username);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                continue;
            }

            $base = preg_replace('/[^a-z0-9_.-]+/i', '.', $username) ?? $username;
            $base = trim($base, '.');
            if ($base === '') {
                $base = 'user';
            }

            $suffix = 1;
            $candidate = $base . '.' . $id;
            while (isset($seen[strtolower($candidate)])) {
                $suffix++;
                $candidate = $base . '.' . $id . '.' . $suffix;
            }

            $updateStmt->execute([
                ':username' => $candidate,
                ':id' => $id,
            ]);
            $seen[strtolower($candidate)] = true;
        }

        if (!$this->indexExists($pdo, 'users', 'uniq_users_username')) {
            $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_username (username)');
        }
    }

    private function ensureMemberRecord(PDO $pdo, string $name): int
    {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            $trimmedName = 'Unknown Member';
        }

        $findStmt = $pdo->prepare('SELECT id FROM members WHERE name = :name LIMIT 1');
        $findStmt->execute([':name' => $trimmedName]);
        $existing = $findStmt->fetch();
        if (is_array($existing)) {
            return (int) ($existing['id'] ?? 0);
        }

        $nextId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM members')->fetchColumn();
        if ($nextId <= 0) {
            $nextId = 1;
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO members (id, name, address, contact_number, license_number, plate_number, id_doc_path, license_doc_path, orcr_doc_path, toda_id, body_number, status, created_at)
             VALUES (:id, :name, :address, :contact_number, :license_number, :plate_number, :id_doc_path, :license_doc_path, :orcr_doc_path, :toda_id, :body_number, :status, :created_at)'
        );
        $insertStmt->execute([
            ':id' => $nextId,
            ':name' => $trimmedName,
            ':address' => 'System Generated',
            ':contact_number' => '',
            ':license_number' => '',
            ':plate_number' => '',
            ':id_doc_path' => null,
            ':license_doc_path' => null,
            ':orcr_doc_path' => null,
            ':toda_id' => '',
            ':body_number' => '',
            ':status' => 'ACTIVE',
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return $nextId;
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index_name'
        );
        $stmt->execute([
            ':table' => $table,
            ':index_name' => $index,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function foreignKeyExists(PDO $pdo, string $table, string $constraint): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.table_constraints
             WHERE table_schema = DATABASE() AND table_name = :table
             AND constraint_name = :constraint_name AND constraint_type = :constraint_type'
        );
        $stmt->execute([
            ':table' => $table,
            ':constraint_name' => $constraint,
            ':constraint_type' => 'FOREIGN KEY',
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function migrateAuditLogsToUserReference(PDO $pdo): void
    {
        if (!$this->columnExists($pdo, 'audit_logs', 'user_id')) {
            $pdo->exec('ALTER TABLE audit_logs ADD COLUMN user_id INT NULL AFTER details');
        }

        if (!$this->columnExists($pdo, 'audit_logs', 'name')) {
            $pdo->exec('ALTER TABLE audit_logs ADD COLUMN name VARCHAR(180) NULL AFTER user_id');
        }

        if ($this->columnExists($pdo, 'audit_logs', 'username')) {
            $rows = $pdo->query('SELECT id, username, role FROM audit_logs ORDER BY id ASC')->fetchAll();
            if (is_array($rows)) {
                $findUserStmt = $pdo->prepare(
                    'SELECT u.id, m.name
                     FROM users u
                     INNER JOIN members m ON m.id = u.member_id
                     WHERE u.username = :username
                     LIMIT 1'
                );
                $updateStmt = $pdo->prepare(
                    'UPDATE audit_logs
                     SET user_id = :user_id, name = :name
                     WHERE id = :id'
                );

                foreach ($rows as $row) {
                    $username = trim((string) ($row['username'] ?? ''));
                    $resolvedUserId = null;
                    $resolvedName = $username !== '' ? $username : 'Guest';

                    if ($username !== '') {
                        $findUserStmt->execute([':username' => $username]);
                        $user = $findUserStmt->fetch();
                        if (is_array($user)) {
                            $resolvedUserId = (int) ($user['id'] ?? 0);
                            $resolvedName = (string) ($user['name'] ?? $username);
                        }
                    }

                    $updateStmt->execute([
                        ':user_id' => $resolvedUserId,
                        ':name' => $resolvedName,
                        ':id' => (int) ($row['id'] ?? 0),
                    ]);
                }
            }

            $pdo->exec('ALTER TABLE audit_logs DROP COLUMN username');
        }

        if (!$this->foreignKeyExists($pdo, 'audit_logs', 'fk_audit_user')) {
            $pdo->exec('ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    private function migratePaymentsForPhase5(PDO $pdo): void
    {
        $columns = [
            'amount_to_pay' => 'ALTER TABLE payments ADD COLUMN amount_to_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount',
            'amount_paid' => 'ALTER TABLE payments ADD COLUMN amount_paid DECIMAL(12,2) NULL AFTER amount_to_pay',
            'due_date' => 'ALTER TABLE payments ADD COLUMN due_date DATE NULL AFTER status',
            'reference_no' => 'ALTER TABLE payments ADD COLUMN reference_no VARCHAR(120) NULL AFTER due_date',
            'submitted_reference_no' => 'ALTER TABLE payments ADD COLUMN submitted_reference_no VARCHAR(120) NULL AFTER reference_no',
            'proof_image_path' => 'ALTER TABLE payments ADD COLUMN proof_image_path VARCHAR(255) NULL AFTER submitted_reference_no',
            'receipt_no' => 'ALTER TABLE payments ADD COLUMN receipt_no VARCHAR(120) NULL AFTER proof_image_path',
            'paid_at' => 'ALTER TABLE payments ADD COLUMN paid_at DATETIME NULL AFTER receipt_no',
            'is_flagged' => 'ALTER TABLE payments ADD COLUMN is_flagged TINYINT(1) NOT NULL DEFAULT 0 AFTER paid_at',
            'flag_reason' => 'ALTER TABLE payments ADD COLUMN flag_reason VARCHAR(255) NULL AFTER is_flagged',
            'flagged_by' => 'ALTER TABLE payments ADD COLUMN flagged_by VARCHAR(180) NULL AFTER flag_reason',
            'flagged_at' => 'ALTER TABLE payments ADD COLUMN flagged_at DATETIME NULL AFTER flagged_by',
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists($pdo, 'payments', $column)) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec('UPDATE payments SET amount_to_pay = amount WHERE amount_to_pay IS NULL OR amount_to_pay = 0');
        $pdo->exec("UPDATE payments SET amount_paid = amount_to_pay WHERE status = 'PAID' AND (amount_paid IS NULL OR amount_paid = 0)");
    }

    private function migrateViolationsForPhase5(PDO $pdo): void
    {
        $columns = [
            'reporter_user_id' => 'ALTER TABLE violations ADD COLUMN reporter_user_id INT NULL AFTER id',
            'reported_driver_id' => 'ALTER TABLE violations ADD COLUMN reported_driver_id VARCHAR(120) NULL AFTER reporter_name',
            'reported_plate' => 'ALTER TABLE violations ADD COLUMN reported_plate VARCHAR(100) NULL AFTER reported_name',
            'actual_reported_plate' => 'ALTER TABLE violations ADD COLUMN actual_reported_plate VARCHAR(100) NULL AFTER reported_plate',
            'incident_datetime' => 'ALTER TABLE violations ADD COLUMN incident_datetime DATETIME NULL AFTER description',
            'incident_location' => 'ALTER TABLE violations ADD COLUMN incident_location VARCHAR(255) NULL AFTER incident_datetime',
            'evidence_path' => 'ALTER TABLE violations ADD COLUMN evidence_path VARCHAR(255) NULL AFTER incident_location',
            'review_notes' => 'ALTER TABLE violations ADD COLUMN review_notes VARCHAR(255) NULL AFTER evidence_path',
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists($pdo, 'violations', $column)) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec(
            "UPDATE violations v
             LEFT JOIN members m ON m.name = v.reported_name
             SET v.actual_reported_plate = COALESCE(NULLIF(v.actual_reported_plate, ''), m.plate_number, v.reported_plate)
             WHERE v.actual_reported_plate IS NULL OR v.actual_reported_plate = ''"
        );
    }

    private function migrateNotificationsForPhase5(PDO $pdo): void
    {
        if (!$this->columnExists($pdo, 'notifications', 'target_user_id')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN target_user_id INT NULL AFTER id');
        }
    }

    private function migrateMembersForPhase5(PDO $pdo): void
    {
        $columns = [
            'id_doc_path' => 'ALTER TABLE members ADD COLUMN id_doc_path VARCHAR(255) NULL AFTER plate_number',
            'license_doc_path' => 'ALTER TABLE members ADD COLUMN license_doc_path VARCHAR(255) NULL AFTER id_doc_path',
            'orcr_doc_path' => 'ALTER TABLE members ADD COLUMN orcr_doc_path VARCHAR(255) NULL AFTER license_doc_path',
        ];
        foreach ($columns as $column => $sql) {
            if (!$this->columnExists($pdo, 'members', $column)) {
                $pdo->exec($sql);
            }
        }
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

    private function ensureRoleAccountProfiles(PDO $pdo): void
    {
        $profiles = [
            ['username' => 'admin', 'password' => 'admin123', 'role' => 'super_admin', 'member_name' => 'Jane Doe'],
            ['username' => 'vp', 'password' => 'vp123', 'role' => 'vice_president', 'member_name' => 'Kiko Pangilinan'],
            ['username' => 'secretary', 'password' => 'secretary123', 'role' => 'secretary', 'member_name' => 'Mang Politiko'],
            ['username' => 'treasurer', 'password' => 'treasurer123', 'role' => 'treasurer', 'member_name' => 'Magna Cum'],
            ['username' => 'compliance', 'password' => 'compliance123', 'role' => 'compliance_officer', 'member_name' => 'John Doer'],
            ['username' => 'driver', 'password' => 'driver', 'role' => 'driver', 'member_name' => 'Juan Delacruz'],
        ];

        $findUser = $pdo->prepare('SELECT id, member_id FROM users WHERE username = :username LIMIT 1');
        $findUserByMember = $pdo->prepare('SELECT id, username FROM users WHERE member_id = :member_id LIMIT 1');
        $updateUser = $pdo->prepare(
            'UPDATE users SET role = :role, is_active = 1 WHERE id = :id'
        );
        $insertUser = $pdo->prepare(
            'INSERT INTO users (username, password, member_id, role, is_active, created_at)
             VALUES (:username, :password, :member_id, :role, 1, :created_at)'
        );
        $findMemberName = $pdo->prepare('SELECT name FROM members WHERE id = :id LIMIT 1');
        $updateMemberName = $pdo->prepare('UPDATE members SET name = :name WHERE id = :id');
        $deleteDriver1 = $pdo->prepare("DELETE FROM users WHERE username = 'driver1'");
        $deleteDriver1->execute();

        foreach ($profiles as $profile) {
            $memberId = $this->ensureMemberRecord($pdo, $profile['member_name']);
            $findUser->execute([':username' => $profile['username']]);
            $existing = $findUser->fetch();

            if (!is_array($existing)) {
                $findUserByMember->execute([':member_id' => $memberId]);
                $existing = $findUserByMember->fetch();
            }

            if (is_array($existing)) {
                $updateUser->execute([
                    ':role' => $profile['role'],
                    ':id' => (int) ($existing['id'] ?? 0),
                ]);
            } else {
                $insertUser->execute([
                    ':username' => $profile['username'],
                    ':password' => $profile['password'],
                    ':member_id' => $memberId,
                    ':role' => $profile['role'],
                    ':created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            // Keep initialization names only for blank/system-generated members.
            // Do not overwrite names that users have already updated in Settings.
            $findMemberName->execute([':id' => $memberId]);
            $memberRow = $findMemberName->fetch();
            $existingName = trim((string) ($memberRow['name'] ?? ''));
            if ($existingName === '' || strcasecmp($existingName, 'System Generated') === 0) {
                $updateMemberName->execute([
                    ':name' => $profile['member_name'],
                    ':id' => $memberId,
                ]);
            }
        }
    }
}
