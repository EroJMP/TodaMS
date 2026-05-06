<?php

class ReportController
{
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        $user = $_SESSION['user'];
        $summary = $this->service->getSummary();
        $recentViolations = $this->service->recentViolations(8);
        $recentPayments = $this->service->recentPayments(8);
        $pageTitle = 'Reports';
        $currentRoute = '/reports';
        require __DIR__ . '/../../resources/views/reports/index.php';
    }
}
