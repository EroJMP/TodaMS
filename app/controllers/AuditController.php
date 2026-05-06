<?php

class AuditController
{
    private AuditService $service;

    public function __construct()
    {
        $this->service = new AuditService();
    }

    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::allow(['super_admin', 'compliance_officer']);

        $user = $_SESSION['user'];
        $logs = $this->service->all();
        $pageTitle = 'Audit Logs';
        $currentRoute = '/audit-logs';
        require __DIR__ . '/../../resources/views/audit/index.php';
    }
}
