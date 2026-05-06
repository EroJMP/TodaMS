<?php

class DashboardController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;
        $role = (string) ($user['role'] ?? '');
        $modules = Navigation::itemsForRole($role);
        $summary = $role === 'driver'
            ? $this->reportService->getDriverSummary(is_array($user) ? $user : [])
            : $this->reportService->getSummary();
        $pageTitle = 'Dashboard';
        $currentRoute = '/dashboard';
        require __DIR__ . '/../../resources/views/dashboard.php';
    }
}
