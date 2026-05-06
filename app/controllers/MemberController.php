<?php

class MemberController
{
    private MemberService $service;

    public function __construct()
    {
        $this->service = new MemberService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $members = $this->service->all();
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($keyword !== '') {
            $needle = strtolower($keyword);
            $members = array_values(array_filter($members, static function ($row) use ($needle) {
                $name = strtolower((string) ($row['name'] ?? ''));
                $plate = strtolower((string) ($row['plate_number'] ?? ''));
                $address = strtolower((string) ($row['address'] ?? ''));
                return str_contains($name, $needle) || str_contains($plate, $needle) || str_contains($address, $needle);
            }));
        }
        if ($status !== '') {
            $members = array_values(array_filter($members, static fn ($row) => (string) ($row['status'] ?? '') === $status));
        }
        $pageTitle = 'Members';
        $currentRoute = '/members';
        require __DIR__ . '/../../resources/views/members/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['secretary']);

        $errors = Validator::required($_POST, ['name', 'address', 'contact_number', 'license_number', 'plate_number']);
        $contact = trim((string) ($_POST['contact_number'] ?? ''));
        if ($contact !== '' && preg_match('/^[0-9+\-\s]{7,20}$/', $contact) !== 1) {
            $errors['contact_number'] = 'Invalid contact number format.';
        }
        $license = strtoupper(trim((string) ($_POST['license_number'] ?? '')));
        if ($license !== '' && preg_match('/^TRC-\d{4}$/', $license) !== 1) {
            $errors['license_number'] = 'License number must follow format TRC-5821.';
        }
        $plate = strtoupper(trim((string) ($_POST['plate_number'] ?? '')));
        if ($plate !== '' && preg_match('/^TODA-\d{4}$/', $plate) !== 1) {
            $errors['plate_number'] = 'Plate number must follow format TODA-4821.';
        }
        if ($plate !== '' && $this->service->existsByPlateNumber($plate)) {
            $errors['plate_number'] = 'Plate number already exists.';
        }
        if ($license !== '' && $this->service->existsByLicenseNumber($license)) {
            $errors['license_number'] = 'License number already exists.';
        }
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/members', 'error', 'Please complete all required member fields.');
        }

        $payload = $_POST;
        $payload['license_number'] = $license;
        $payload['plate_number'] = $plate;
        $payload['id_doc_path'] = $this->handleOptionalUpload('id_doc');
        $payload['license_doc_path'] = $this->handleOptionalUpload('license_doc');
        $payload['orcr_doc_path'] = $this->handleOptionalUpload('orcr_doc');

        $this->service->create($payload);
        Response::redirect('/members', 'success', 'Member application submitted successfully.');
    }

    public function approve(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['member' => 'Invalid member record selected.'];
            Response::redirect('/members', 'error', 'Invalid member record selected.');
        }

        $username = trim((string) ($_POST['driver_username'] ?? ''));
        $password = trim((string) ($_POST['driver_password'] ?? ''));
        $result = $this->service->approveWithCredentials($id, $username, $password);
        if (!(bool) ($result['ok'] ?? false)) {
            $_SESSION['errors']['member_approve'] = (string) ($result['error'] ?? 'Unable to approve member.');
            $_SESSION['old']['member_approve_id'] = $id;
            $_SESSION['old']['driver_username'] = $username;
            Response::redirect('/members', 'error', (string) ($result['error'] ?? 'Unable to approve member.'));
        }
        Response::redirect('/members', 'success', 'Member approved and driver account created successfully.');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['member' => 'Invalid member record selected.'];
            Response::redirect('/members', 'error', 'Invalid member record selected.');
        }

        $this->service->updateStatus($id, 'DECLINED');
        Response::redirect('/members', 'success', 'Member rejected successfully.');
    }

    private function handleOptionalUpload(string $field): ?string
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            return null;
        }
        $file = $_FILES[$field];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            return null;
        }
        $name = (string) ($file['name'] ?? '');
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'webp'], true)) {
            return null;
        }
        $dir = __DIR__ . '/../../public/uploads/member-docs';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $target = $field . '-' . time() . '-' . substr(md5($name . microtime(true)), 0, 8) . '.' . $ext;
        $absolutePath = $dir . '/' . $target;
        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $absolutePath)) {
            return null;
        }
        return 'uploads/member-docs/' . $target;
    }
}
