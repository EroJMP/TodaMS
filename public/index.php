<?php

declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../app/helpers/Url.php';
require_once __DIR__ . '/../app/helpers/Response.php';
require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/helpers/Navigation.php';

require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/services/XlsmMemberImporter.php';
require_once __DIR__ . '/../app/services/DatabaseBootstrapService.php';
require_once __DIR__ . '/../app/services/JsonStore.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/MemberService.php';
require_once __DIR__ . '/../app/services/ViolationService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/services/ReportService.php';
require_once __DIR__ . '/../app/services/AuditService.php';
require_once __DIR__ . '/../app/services/RuleService.php';
require_once __DIR__ . '/../app/services/UserAdminService.php';
require_once __DIR__ . '/../app/services/BillingAutomationService.php';
require_once __DIR__ . '/../app/services/SettingsService.php';

require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/MemberController.php';
require_once __DIR__ . '/../app/controllers/ViolationController.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../app/controllers/NotificationController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/AuditController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/SystemController.php';
require_once __DIR__ . '/../app/controllers/SettingsController.php';

$databaseBootstrap = new DatabaseBootstrapService();
$databaseBootstrap->bootstrap();

require_once __DIR__ . '/../routes/web.php';
