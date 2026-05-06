<?php

declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../app/helpers/Url.php';
require_once __DIR__ . '/../app/helpers/Response.php';
require_once __DIR__ . '/../app/helpers/Validator.php';

require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/JsonStore.php';
require_once __DIR__ . '/../app/services/NotificationService.php';
require_once __DIR__ . '/../app/services/MemberService.php';
require_once __DIR__ . '/../app/services/ViolationService.php';
require_once __DIR__ . '/../app/services/PaymentService.php';

require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/MemberController.php';
require_once __DIR__ . '/../app/controllers/ViolationController.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../app/controllers/NotificationController.php';

require_once __DIR__ . '/../routes/web.php';
