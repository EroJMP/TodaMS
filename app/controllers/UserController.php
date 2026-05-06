<?php

class UserController
{
    private UserAdminService $service;
    private MemberService $members;

    public function __construct()
    {
        $this->service = new UserAdminService();
        $this->members = new MemberService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $user = $_SESSION['user'];
        $users = $this->service->all();
        $members = $this->members->all();
        $pageTitle = 'User Management';
        $currentRoute = '/users';
        require __DIR__ . '/../../resources/views/users/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $errors = Validator::required($_POST, ['username', 'password', 'member_id', 'role']);
        if ((int) ($_POST['member_id'] ?? 0) <= 0) {
            $errors['member_id'] = 'Member is required.';
        }
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/users', 'error', 'Please complete all required user fields.');
        }
        $this->service->create($_POST);
        Response::redirect('/users', 'success', 'User account created successfully.');
    }

    public function activate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->service->updateStatus($id, 1);
        }
        Response::redirect('/users', 'success', 'User activated successfully.');
    }

    public function deactivate(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->service->updateStatus($id, 0);
        }
        Response::redirect('/users', 'success', 'User deactivated successfully.');
    }
}
