<?php

class PaymentController
{
    private PaymentService $service;
    private BillingAutomationService $automation;
    private MemberService $members;

    public function __construct()
    {
        $this->service = new PaymentService();
        $this->automation = new BillingAutomationService();
        $this->members = new MemberService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $payments = $this->service->all();
        $members = $this->members->all();
        if ((string) ($user['role'] ?? '') === 'driver') {
            $driverName = strtolower(trim((string) ($user['name'] ?? '')));
            $payments = array_values(array_filter($payments, static function ($row) use ($driverName) {
                $paymentDriver = strtolower(trim((string) ($row['driver_name'] ?? '')));
                return $driverName !== '' && $paymentDriver === $driverName;
            }));
        }
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($keyword !== '') {
            $needle = strtolower($keyword);
            $payments = array_values(array_filter($payments, static function ($row) use ($needle) {
                $driver = strtolower((string) ($row['driver_name'] ?? ''));
                $reason = strtolower((string) ($row['reason'] ?? ''));
                return str_contains($driver, $needle) || str_contains($reason, $needle);
            }));
        }
        if ($status !== '') {
            $payments = array_values(array_filter($payments, static fn ($row) => (string) ($row['status'] ?? '') === $status));
        }
        $pageTitle = 'Payments';
        $currentRoute = '/payments';
        require __DIR__ . '/../../resources/views/payments/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $errors = Validator::required($_POST, ['driver_name', 'reason', 'amount']);
        $amount = (float) ($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $errors['amount'] = 'Amount must be greater than zero.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/payments', 'error', 'Please complete all required payment fields.');
        }

        $this->service->create($_POST);
        Response::redirect('/payments', 'success', 'Billing record created successfully.');
    }

    public function submitProof(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['driver']);
        $id = (int) ($_POST['id'] ?? 0);
        $submittedReferenceNo = trim((string) ($_POST['submitted_reference_no'] ?? ''));
        $submittedAmount = (float) ($_POST['submitted_amount'] ?? 0);
        if ($id <= 0 || $submittedReferenceNo === '' || $submittedAmount <= 0) {
            Response::redirect('/payments', 'error', 'Payment proof information is invalid.');
        }

        $user = $_SESSION['user'] ?? [];
        $payment = $this->service->find($id);
        if (!is_array($payment)) {
            Response::redirect('/payments', 'error', 'Payment record not found.');
        }
        $driverName = strtolower(trim((string) ($user['name'] ?? '')));
        $ownerName = strtolower(trim((string) ($payment['driver_name'] ?? '')));
        if ($driverName === '' || $ownerName !== $driverName) {
            Response::redirect('/payments', 'error', 'You can only submit proof for your own payment records.');
        }

        if (!isset($_FILES['proof_file']) || !is_array($_FILES['proof_file'])) {
            Response::redirect('/payments', 'error', 'Please select a payment proof file.');
        }
        $proofFile = $_FILES['proof_file'];
        if (($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::redirect('/payments', 'error', 'Unable to upload payment proof file.');
        }
        $originalName = (string) ($proofFile['name'] ?? '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'avi'], true)) {
            Response::redirect('/payments', 'error', 'Invalid proof file type. Use image/video only.');
        }
        $uploadDir = __DIR__ . '/../../public/uploads/proofs';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $targetName = 'proof-' . $id . '-' . time() . '.' . $extension;
        $targetPath = $uploadDir . '/' . $targetName;
        if (!move_uploaded_file((string) ($proofFile['tmp_name'] ?? ''), $targetPath)) {
            Response::redirect('/payments', 'error', 'Failed to save payment proof file.');
        }
        $proofRelativePath = 'uploads/proofs/' . $targetName;

        $ok = $this->service->submitProof($id, $proofRelativePath, $submittedReferenceNo, $submittedAmount);
        if (!$ok) {
            Response::redirect('/payments', 'error', 'Unable to submit payment proof for this record.');
        }
        Response::redirect('/payments', 'success', 'Payment proof submitted. Awaiting treasurer verification.');
    }

    public function markPaid(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['payment' => 'Invalid payment record selected.'];
            Response::redirect('/payments', 'error', 'Invalid payment record selected.');
        }

        $payment = $this->service->find($id);
        if (!is_array($payment) || trim((string) ($payment['proof_image_path'] ?? '')) === '') {
            Response::redirect('/payments', 'error', 'Cannot mark as paid without submitted payment proof.');
        }

        $this->service->verify($id, 'PAID');
        Response::redirect('/payments', 'success', 'Payment marked as paid.');
    }

    public function cashPay(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $id = (int) ($_POST['id'] ?? 0);
        $amountPaid = (float) ($_POST['cash_amount_paid'] ?? 0);
        if ($id <= 0 || $amountPaid <= 0) {
            Response::redirect('/payments', 'error', 'Cash payment details are invalid.');
        }

        $ok = $this->service->recordCashPayment($id, $amountPaid);
        if (!$ok) {
            Response::redirect('/payments', 'error', 'Unable to record cash payment.');
        }
        Response::redirect('/payments', 'success', 'Cash payment recorded successfully.');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['errors'] = ['payment' => 'Invalid payment record selected.'];
            Response::redirect('/payments', 'error', 'Invalid payment record selected.');
        }

        $this->service->verify($id, 'REJECTED');
        Response::redirect('/payments', 'success', 'Payment marked as rejected.');
    }

    public function flag(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['compliance_officer']);
        $id = (int) ($_POST['id'] ?? 0);
        $reason = trim((string) ($_POST['flag_reason'] ?? ''));
        if ($id <= 0 || $reason === '') {
            Response::redirect('/payments', 'error', 'Flag reason is required.');
        }

        $user = $_SESSION['user'] ?? [];
        $flaggedBy = (string) ($user['name'] ?? 'Compliance Officer');
        $ok = $this->service->flagPayment($id, $reason, $flaggedBy);
        if (!$ok) {
            Response::redirect('/payments', 'error', 'Unable to flag this payment.');
        }
        Response::redirect('/payments', 'success', 'Payment flagged for compliance review.');
    }

    public function generateActivityFee(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $activityName = trim((string) ($_POST['activity_name'] ?? ''));
        if ($activityName === '') {
            Response::redirect('/payments', 'error', 'Activity name is required.');
        }

        $created = $this->automation->generateActivityBilling($activityName);
        if ($created <= 0) {
            Response::redirect('/payments', 'info', 'No new activity billing created (already generated or no active drivers).');
        }

        Response::redirect('/payments', 'success', 'Activity billing generated for ' . $created . ' driver(s).');
    }
}
