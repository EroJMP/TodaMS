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
        $notifications = $this->service->byRole($user['role']);
        $pageTitle = 'Notifications';
        require __DIR__ . '/../../resources/views/notifications/index.php';
    }
}
