<?php

class RuleService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findPenaltyRuleByType(string $violationType): ?array
    {
        $normalized = strtolower(trim($violationType));
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->pdo->query('SELECT * FROM penalty_rules');
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            $label = strtolower((string) ($row['label'] ?? ''));
            if ($label === $normalized) {
                return $row;
            }
        }

        // Fuzzy fallback for variations in punctuation/formatting.
        $normalizedSimple = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;
        foreach ($rows as $row) {
            $label = strtolower((string) ($row['label'] ?? ''));
            $labelSimple = preg_replace('/[^a-z0-9]+/i', ' ', $label) ?? $label;
            if (str_contains($labelSimple, $normalizedSimple) || str_contains($normalizedSimple, $labelSimple)) {
                return $row;
            }
        }

        return null;
    }

    public function resolvePenaltyAmount(array $rule): float
    {
        $min = (float) ($rule['min_amount'] ?? 0);
        $max = (float) ($rule['max_amount'] ?? $min);
        $isRange = (int) ($rule['is_range'] ?? 0) === 1;

        if (!$isRange || $max <= $min) {
            return $min;
        }

        // Use midpoint for range-based penalties.
        return round(($min + $max) / 2, 2);
    }

    public function allFeeRules(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM fee_rules ORDER BY id ASC');
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function findFeeRuleByKey(string $feeKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fee_rules WHERE fee_key = :fee_key LIMIT 1');
        $stmt->execute([':fee_key' => trim($feeKey)]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function allPenaltyRules(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM penalty_rules ORDER BY id ASC');
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}
