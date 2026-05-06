<?php

class MemberService
{
    private JsonStore $store;
    private NotificationService $notifications;
    private string $file = 'members';

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
            'name' => trim($input['name']),
            'address' => trim($input['address']),
            'contact_number' => trim($input['contact_number']),
            'license_number' => trim($input['license_number']),
            'plate_number' => trim($input['plate_number']),
            'status' => 'PENDING APPROVAL',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->store->write($this->file, $rows);
        $this->notifications->create('vice_president', 'New member application submitted for approval.');
    }

    public function updateStatus(int $id, string $status): void
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
