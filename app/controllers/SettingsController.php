<?php

class SettingsController
{
    private SettingsService $service;
    private AuditService $auditService;

    public function __construct()
    {
        $this->service = new SettingsService();
        $this->auditService = new AuditService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            Response::redirect('/login', 'error', 'Please sign in again.');
        }

        $account = $this->service->getByUserId($userId);
        if (!is_array($account)) {
            Response::redirect('/dashboard', 'error', 'Unable to load account settings.');
        }

        $pageTitle = 'Settings';
        $currentRoute = '/settings';
        require __DIR__ . '/../../resources/views/settings/index.php';
    }

    public function updateProfile(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            Response::redirect('/login', 'error', 'Please sign in again.');
        }

        $result = $this->service->updateProfile($userId, $_POST);
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/settings', 'error', (string) ($result['error'] ?? 'Unable to update profile.'));
        }

        $fresh = $this->service->getByUserId($userId);
        if (is_array($fresh)) {
            $_SESSION['user']['username'] = (string) ($fresh['username'] ?? ($_SESSION['user']['username'] ?? ''));
            $_SESSION['user']['name'] = (string) ($fresh['name'] ?? ($_SESSION['user']['name'] ?? ''));
        }

        $this->auditService->log('PROFILE_UPDATED', 'User updated account profile information.', $_SESSION['user']);
        Response::redirect('/settings', 'success', 'Account information updated successfully.');
    }

    public function updatePassword(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            Response::redirect('/login', 'error', 'Please sign in again.');
        }

        $result = $this->service->updatePassword(
            $userId,
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? '')
        );
        if (!(bool) ($result['ok'] ?? false)) {
            Response::redirect('/settings', 'error', (string) ($result['error'] ?? 'Unable to update password.'));
        }

        $this->auditService->log('PASSWORD_UPDATED', 'User updated account password.', $_SESSION['user']);
        Response::redirect('/settings', 'success', 'Password updated successfully.');
    }
}
