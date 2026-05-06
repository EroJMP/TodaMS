<?php

class BillingAutomationService
{
    private RuleService $rules;
    private PaymentService $payments;
    private PDO $pdo;

    public function __construct()
    {
        $this->rules = new RuleService();
        $this->payments = new PaymentService();
        $this->pdo = Database::connection();
    }

    public function runRecurringBilling(): void
    {
        $drivers = $this->activeDrivers();
        if ($drivers === []) {
            return;
        }

        $monthLabel = date('F Y');
        $yearLabel = date('Y');

        $monthlyDues = $this->rules->findFeeRuleByKey('monthly_dues');
        if (is_array($monthlyDues)) {
            $reason = 'Monthly Dues - ' . $monthLabel;
            $this->createFeeForDrivers($drivers, $reason, (float) ($monthlyDues['amount'] ?? 0));
        }

        $terminalFee = $this->rules->findFeeRuleByKey('terminal_fee');
        if (is_array($terminalFee)) {
            $reason = 'Terminal Fee - ' . $monthLabel;
            $this->createFeeForDrivers($drivers, $reason, (float) ($terminalFee['amount'] ?? 0));
        }

        $renewal = $this->rules->findFeeRuleByKey('membership_renewal');
        if (is_array($renewal)) {
            $reason = 'Membership Renewal - ' . $yearLabel;
            $this->createFeeForDrivers($drivers, $reason, (float) ($renewal['amount'] ?? 0));
        }
    }

    public function generateActivityBilling(string $activityName): int
    {
        $activityName = trim($activityName);
        if ($activityName === '') {
            return 0;
        }

        $rule = $this->rules->findFeeRuleByKey('event_contribution');
        if (!is_array($rule)) {
            return 0;
        }

        $amount = (float) ($rule['amount'] ?? 0);
        if ($amount <= 0) {
            return 0;
        }

        $drivers = $this->activeDrivers();
        if ($drivers === []) {
            return 0;
        }

        $created = 0;
        $reason = 'Activity Fee - ' . $activityName . ' - ' . date('Y-m-d');
        foreach ($drivers as $driverName) {
            if ($this->paymentExists($driverName, $reason)) {
                continue;
            }
            $this->payments->createSystemBilling($driverName, $reason, $amount);
            $created++;
        }

        return $created;
    }

    private function createFeeForDrivers(array $drivers, string $reason, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        foreach ($drivers as $driverName) {
            if ($this->paymentExists($driverName, $reason)) {
                continue;
            }
            $this->payments->createSystemBilling($driverName, $reason, $amount);
        }
    }

    private function paymentExists(string $driverName, string $reason): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM payments WHERE driver_name = :driver_name AND reason = :reason LIMIT 1'
        );
        $stmt->execute([
            ':driver_name' => $driverName,
            ':reason' => $reason,
        ]);
        return is_array($stmt->fetch());
    }

    /**
     * @return array<int, string>
     */
    private function activeDrivers(): array
    {
        $stmt = $this->pdo->query(
            "SELECT m.name
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.role = 'driver' AND u.is_active = 1 AND m.status = 'ACTIVE'
             ORDER BY m.name ASC"
        );
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $drivers = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name !== '') {
                $drivers[] = $name;
            }
        }
        return $drivers;
    }
}
