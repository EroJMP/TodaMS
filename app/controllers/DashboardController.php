<?php

class DashboardController
{
    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;
        $pageTitle = 'Dashboard';
        require __DIR__ . '/../../resources/views/dashboard.php';
    }
}
