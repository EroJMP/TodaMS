<?php

class ViolationController
{
    private ViolationService $service;

    public function __construct()
    {
        $this->service = new ViolationService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $violations = $this->service->all();
        $pageTitle = 'Violations';
        require __DIR__ . '/../../resources/views/violations/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['driver']);
        $errors = Validator::required($_POST, ['reporter_name', 'reported_name', 'violation_type', 'description']);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/violations');
        }

        $this->service->create($_POST);
        Response::redirect('/violations');
    }

    public function encode(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['secretary']);
        $this->service->transition((int) ($_POST['id'] ?? 0), 'PENDING VALIDATION');
        Response::redirect('/violations');
    }

    public function validate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['compliance_officer']);
        $this->service->transition((int) ($_POST['id'] ?? 0), 'PENDING APPROVAL');
        Response::redirect('/violations');
    }

    public function approve(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $this->service->transition((int) ($_POST['id'] ?? 0), 'APPROVED');
        Response::redirect('/violations');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president', 'compliance_officer']);
        $this->service->transition((int) ($_POST['id'] ?? 0), 'REJECTED');
        Response::redirect('/violations');
    }
}
