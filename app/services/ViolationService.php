<?php

class ViolationService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private string $file = 'violations';

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
            'reporter_name' => trim($input['reporter_name']),
            'reported_name' => trim($input['reported_name']),
            'violation_type' => trim($input['violation_type']),
            'description' => trim($input['description']),
            'status' => 'SUBMITTED',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $this->notifications->create('secretary', 'New violation report submitted.');
    }

    public function transition(int $id, string $status): void
    {
        $rows = $this->store->all($this->file);
        foreach ($rows as &$row) {
            if ((int) $row['id'] !== $id) {
                continue;
            }

            $row['status'] = $status;
            if ($status === 'PENDING VALIDATION') {
                $this->notifications->create('compliance_officer', 'Violation report is ready for validation.');
            }
            if ($status === 'PENDING APPROVAL') {
                $this->notifications->create('vice_president', 'Validated violation report is ready for decision.');
            }
            if ($status === 'APPROVED') {
                $this->notifications->create('treasurer', 'Approved violation requires penalty billing.');
            }
            break;
        }
        $this->store->write($this->file, $rows);
    }
}
