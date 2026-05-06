<?php

class ViolationController
{
    private ViolationService $service;
    private MemberService $members;
    private RuleService $rules;

    public function __construct()
    {
        $this->service = new ViolationService();
        $this->members = new MemberService();
        $this->rules = new RuleService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $violations = $this->service->all();
        $members = $this->members->all();
        $penaltyRules = $this->rules->allPenaltyRules();
        $feeRules = $this->rules->allFeeRules();
        $violationTypeOptions = [];
        foreach ($penaltyRules as $rule) {
            $label = trim((string) ($rule['label'] ?? ''));
            if ($label !== '') {
                $min = (float) ($rule['min_amount'] ?? 0);
                $max = (float) ($rule['max_amount'] ?? $min);
                $isRange = (int) ($rule['is_range'] ?? 0) === 1;
                $amountText = $isRange && $max > $min
                    ? sprintf('PHP %s - %s', number_format($min, 2), number_format($max, 2))
                    : sprintf('PHP %s', number_format($min, 2));
                $violationTypeOptions[] = [
                    'value' => $label,
                    'label' => $label . ' — ' . $amountText,
                ];
            }
        }
        foreach ($feeRules as $rule) {
            $label = trim((string) ($rule['label'] ?? ''));
            if ($label !== '') {
                $amount = (float) ($rule['amount'] ?? 0);
                $violationTypeOptions[] = [
                    'value' => $label,
                    'label' => sprintf('%s — PHP %s', $label, number_format($amount, 2)),
                ];
            }
        }
        $uniqueTypeOptions = [];
        foreach ($violationTypeOptions as $option) {
            $value = (string) ($option['value'] ?? '');
            if ($value === '' || isset($uniqueTypeOptions[$value])) {
                continue;
            }
            $uniqueTypeOptions[$value] = $option;
        }
        $violationTypeOptions = array_values($uniqueTypeOptions);
        $pdo = Database::connection();
        $driverOptionsStmt = $pdo->query(
            "SELECT m.name
             FROM users u
             INNER JOIN members m ON m.id = u.member_id
             WHERE u.role = 'driver' AND u.is_active = 1
             ORDER BY m.name ASC"
        );
        $driverOptions = $driverOptionsStmt->fetchAll();
        if ((string) ($user['role'] ?? '') === 'driver') {
            $driverUserId = (int) ($user['id'] ?? 0);
            $driverName = strtolower(trim((string) ($user['name'] ?? '')));
            $violations = array_values(array_filter($violations, static function ($row) use ($driverUserId, $driverName) {
                $reporterUserId = isset($row['reporter_user_id']) ? (int) $row['reporter_user_id'] : 0;
                $reporterName = strtolower(trim((string) ($row['reporter_name'] ?? '')));
                $reportedName = strtolower(trim((string) ($row['reported_name'] ?? '')));
                if ($driverUserId > 0 && $reporterUserId === $driverUserId) {
                    return true;
                }
                return $driverName !== '' && ($reporterName === $driverName || $reportedName === $driverName);
            }));
        }
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));
        if ($keyword !== '') {
            $needle = strtolower($keyword);
            $violations = array_values(array_filter($violations, static function ($row) use ($needle) {
                $reporter = strtolower((string) ($row['reporter_name'] ?? ''));
                $reported = strtolower((string) ($row['reported_name'] ?? ''));
                $description = strtolower((string) ($row['description'] ?? ''));
                return str_contains($reporter, $needle) || str_contains($reported, $needle) || str_contains($description, $needle);
            }));
        }
        if ($status !== '') {
            $violations = array_values(array_filter($violations, static fn ($row) => (string) ($row['status'] ?? '') === $status));
        }
        if ($type !== '') {
            $needleType = strtolower($type);
            $violations = array_values(array_filter($violations, static fn ($row) => str_contains(strtolower((string) ($row['violation_type'] ?? '')), $needleType)));
        }
        $pageTitle = 'Violations';
        $currentRoute = '/violations';
        require __DIR__ . '/../../resources/views/violations/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['driver']);
        $errors = Validator::required($_POST, [
            'reported_name',
            'reported_plate',
            'violation_type',
            'description',
            'incident_datetime',
            'incident_location',
        ]);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/violations', 'error', 'Please complete all required violation fields.');
        }

        $incidentDateTime = (string) ($_POST['incident_datetime'] ?? '');
        if (strtotime($incidentDateTime) === false) {
            Response::redirect('/violations', 'error', 'Invalid incident date/time format.');
        }

        if (!isset($_FILES['evidence_file']) || !is_array($_FILES['evidence_file'])) {
            Response::redirect('/violations', 'error', 'Evidence file is required.');
        }
        $evidenceFile = $_FILES['evidence_file'];
        if (($evidenceFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::redirect('/violations', 'error', 'Unable to upload evidence file.');
        }
        $originalName = (string) ($evidenceFile['name'] ?? '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'avi'], true)) {
            Response::redirect('/violations', 'error', 'Evidence must be an image/video file.');
        }
        $uploadDir = __DIR__ . '/../../public/uploads/evidence';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $targetName = 'evidence-' . time() . '-' . substr(md5($originalName . microtime(true)), 0, 8) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $targetName;
        if (!move_uploaded_file((string) ($evidenceFile['tmp_name'] ?? ''), $targetPath)) {
            Response::redirect('/violations', 'error', 'Failed to save evidence file.');
        }

        $payload = $_POST;
        $payload['reporter_name'] = (string) (($_SESSION['user']['name'] ?? ''));
        $payload['evidence_path'] = 'uploads/evidence/' . $targetName;
        $result = $this->service->create($payload);
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/violations', 'error', (string) ($result['error'] ?? 'Unable to submit violation report.'));
        }
        Response::redirect('/violations', 'success', 'Violation report submitted successfully.');
    }

    public function encode(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['secretary']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['violation' => 'Invalid violation record selected.'];
            Response::redirect('/violations', 'error', 'Invalid violation record selected.');
        }

        $result = $this->service->transition($id, 'PENDING VALIDATION');
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/violations', 'error', (string) ($result['error'] ?? 'Unable to encode violation.'));
        }
        Response::redirect('/violations', 'success', 'Violation moved to pending validation.');
    }

    public function validate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['compliance_officer']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['violation' => 'Invalid violation record selected.'];
            Response::redirect('/violations', 'error', 'Invalid violation record selected.');
        }

        $result = $this->service->transition($id, 'PENDING APPROVAL');
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/violations', 'error', (string) ($result['error'] ?? 'Unable to validate violation.'));
        }
        Response::redirect('/violations', 'success', 'Violation validated and forwarded for approval.');
    }

    public function approve(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['violation' => 'Invalid violation record selected.'];
            Response::redirect('/violations', 'error', 'Invalid violation record selected.');
        }

        $result = $this->service->transition($id, 'APPROVED');
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/violations', 'error', (string) ($result['error'] ?? 'Unable to approve violation.'));
        }
        Response::redirect('/violations', 'success', 'Violation approved successfully.');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['secretary', 'vice_president', 'compliance_officer']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['violation' => 'Invalid violation record selected.'];
            Response::redirect('/violations', 'error', 'Invalid violation record selected.');
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        $result = $this->service->transition($id, 'REJECTED', $notes);
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/violations', 'error', (string) ($result['error'] ?? 'Unable to reject violation.'));
        }
        Response::redirect('/violations', 'success', 'Violation rejected successfully.');
    }
}
