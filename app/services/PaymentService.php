<?php

class PaymentService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private string $file = 'payments';

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->notifications = new NotificationService();
    }

    public function all(): array
    {
        return $this->store->all($this->file);
    }

    public function create(array $input): void
    {
        $rows = $this->store->all($this->file);
        $rows[] = [
            'id' => $this->store->nextId($this->file),
            'driver_name' => trim($input['driver_name']),
            'reason' => trim($input['reason']),
            'amount' => (float) $input['amount'],
            'status' => 'PENDING VERIFICATION',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $this->notifications->create('driver', 'New payment record created. Please settle dues.');
    }

    public function verify(int $id, string $status): void
    {
        $rows = $this->store->all($this->file);
        foreach ($rows as &$row) {
            if ((int) $row['id'] === $id) {
                $row['status'] = $status;
                break;
            }
        }
        $this->store->write($this->file, $rows);
    }
}
