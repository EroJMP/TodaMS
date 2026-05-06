<?php

class AuthController
{
    private AuthService $authService;
    private AuditService $auditService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->auditService = new AuditService();
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
            $this->auditService->log('LOGIN_FAILED', 'Invalid credentials for username: ' . trim((string) ($_POST['username'] ?? '')));
            $_SESSION['errors'] = ['auth' => 'Invalid username or password.'];
            $_SESSION['old'] = $_POST;
            Response::redirect('/login', 'error', 'Invalid username or password.');
        }

        $_SESSION['user'] = $user;
        $this->auditService->log('LOGIN_SUCCESS', 'User logged in successfully.', $user);
        Response::redirect('/dashboard', 'success', 'Welcome back, ' . ($user['name'] ?? 'User') . '!');
    }

    public function logout(): void
    {
        $user = $_SESSION['user'] ?? null;
        $this->auditService->log('LOGOUT', 'User logged out.', is_array($user) ? $user : null);
        $_SESSION = [];
        session_destroy();
        Response::redirect('/login', 'success', 'You have logged out successfully.');
    }
}
