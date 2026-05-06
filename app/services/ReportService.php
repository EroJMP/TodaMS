<?php

class ReportService
{
    private JsonStore $store;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->notifications = new NotificationService();
    }

    public function getSummary(): array
    {
        $members = $this->store->all('members');
        $violations = $this->store->all('violations');
        $payments = $this->store->all('payments');

        $paidAmount = 0.0;
        $pendingPayments = 0;

        foreach ($payments as $payment) {
            if (($payment['status'] ?? '') === 'PAID') {
                $paidAmount += (float) ($payment['amount'] ?? 0);
            }
            if (($payment['status'] ?? '') === 'PENDING VERIFICATION') {
                $pendingPayments++;
            }
        }

        return [
            'members_total' => count($members),
            'members_pending' => count(array_filter($members, static fn ($m) => ($m['status'] ?? '') === 'PENDING APPROVAL')),
            'violations_total' => count($violations),
            'violations_pending' => count(array_filter($violations, static fn ($v) => in_array(($v['status'] ?? ''), ['SUBMITTED', 'PENDING VALIDATION', 'PENDING APPROVAL'], true))),
            'payments_total' => count($payments),
            'payments_pending' => $pendingPayments,
            'paid_total_amount' => $paidAmount,
        ];
    }

    public function recentViolations(int $limit = 10): array
    {
        $rows = $this->store->all('violations');
        usort($rows, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return array_slice($rows, 0, $limit);
    }

    public function recentPayments(int $limit = 10): array
    {
        $rows = $this->store->all('payments');
        usort($rows, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return array_slice($rows, 0, $limit);
    }

    public function getDriverSummary(array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $driverName = strtolower(trim((string) ($user['name'] ?? '')));

        $notifications = $this->notifications->byRecipient('driver', $userId);
        $violations = $this->store->all('violations');
        $payments = $this->store->all('payments');

        $involvedViolations = array_values(array_filter($violations, static function ($row) use ($userId, $driverName) {
            $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : 0;
            $reporterName = strtolower(trim((string) ($row['reporter_name'] ?? '')));
            $reportedName = strtolower(trim((string) ($row['reported_name'] ?? '')));
            if ($userId > 0 && $reporterUserId === $userId) {
                return true;
            }
            return $driverName !== '' && ($reporterName === $driverName || $reportedName === $driverName);
        }));

        $driverPayments = array_values(array_filter($payments, static function ($row) use ($driverName) {
            $paymentDriver = strtolower(trim((string) ($row['driver_name'] ?? '')));
            return $driverName !== '' && $paymentDriver === $driverName;
        }));

        $pendingPayments = 0;
        $totalAmountToPay = 0.0;
        foreach ($driverPayments as $payment) {
            if ((string) ($payment['status'] ?? '') !== 'PENDING VERIFICATION') {
                continue;
            }
            $pendingPayments++;
            $totalAmountToPay += (float) ($payment['amount'] ?? 0);
        }

        return [
            'driver_notifications_total' => count($notifications),
            'driver_violations_involved_total' => count($involvedViolations),
            'driver_payments_pending_total' => $pendingPayments,
            'driver_total_amount_to_pay' => $totalAmountToPay,
        ];
    }
}
