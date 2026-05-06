<?php

class NotificationController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $notifications = $this->service->byRecipient((string) $user['role'], (int) ($user['id'] ?? 0));
        $pageTitle = 'Notifications';
        $currentRoute = '/notifications';
        require __DIR__ . '/../../resources/views/notifications/index.php';
    }
}
