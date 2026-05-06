<?php

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        $pageTitle = 'Login';
        require __DIR__ . '/../../resources/views/auth/login.php';
    }

    public function login(): void
    {
        $errors = Validator::required($_POST, ['username', 'password']);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Response::redirect('/login');
        }

        $user = $this->authService->attempt($_POST['username'], $_POST['password']);
        if ($user === null) {
            $_SESSION['errors'] = ['auth' => 'Invalid username or password.'];
            $_SESSION['old'] = $_POST;
            Response::redirect('/login');
        }

        $_SESSION['user'] = $user;
        Response::redirect('/dashboard');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        Response::redirect('/login');
    }
}
