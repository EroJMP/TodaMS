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
        $pageTitle = 'Members';
        require __DIR__ . '/../../resources/views/members/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['secretary']);

        $errors = Validator::required($_POST, ['name', 'address', 'contact_number', 'license_number', 'plate_number']);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/members');
        }

        $this->service->create($_POST);
        Response::redirect('/members');
    }

    public function approve(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $this->service->updateStatus((int) ($_POST['id'] ?? 0), 'ACTIVE');
        Response::redirect('/members');
    }

    public function reject(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['vice_president']);
        $this->service->updateStatus((int) ($_POST['id'] ?? 0), 'DECLINED');
        Response::redirect('/members');
    }
}
