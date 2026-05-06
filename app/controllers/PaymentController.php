<?php

class PaymentController
{
    private PaymentService $service;

    public function __construct()
    {
        $this->service = new PaymentService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $payments = $this->service->all();
        $pageTitle = 'Payments';
        require __DIR__ . '/../../resources/views/payments/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $errors = Validator::required($_POST, ['driver_name', 'reason', 'amount']);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/payments');
        }

        $this->service->create($_POST);
        Response::redirect('/payments');
    }

    public function markPaid(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $this->service->verify((int) ($_POST['id'] ?? 0), 'PAID');
        Response::redirect('/payments');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['treasurer']);
        $this->service->verify((int) ($_POST['id'] ?? 0), 'REJECTED');
        Response::redirect('/payments');
    }
}
