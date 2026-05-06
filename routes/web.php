<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$base = Url::basePath();
$route = str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
$route = $route === '' ? '/' : $route;

$authController = new AuthController();
$dashboardController = new DashboardController();
$memberController = new MemberController();
$violationController = new ViolationController();
$paymentController = new PaymentController();
$notificationController = new NotificationController();

if ($route === '/' || $route === '/login') {
    if ($method === 'GET') {
        $authController->showLogin();
        return;
    }

    if ($method === 'POST') {
        $authController->login();
        return;
    }
}

if ($route === '/logout' && $method === 'POST') {
    $authController->logout();
    return;
}

if ($route === '/dashboard' && $method === 'GET') {
    AuthMiddleware::handle();
    $dashboardController->index();
    return;
}

if ($route === '/members' && $method === 'GET') {
    $memberController->index();
    return;
}
if ($route === '/members' && $method === 'POST') {
    $memberController->store();
    return;
}
if ($route === '/members/approve' && $method === 'POST') {
    $memberController->approve();
    return;
}
if ($route === '/members/reject' && $method === 'POST') {
    $memberController->reject();
    return;
}

if ($route === '/violations' && $method === 'GET') {
    $violationController->index();
    return;
}
if ($route === '/violations' && $method === 'POST') {
    $violationController->store();
    return;
}
if ($route === '/violations/encode' && $method === 'POST') {
    $violationController->encode();
    return;
}
if ($route === '/violations/validate' && $method === 'POST') {
    $violationController->validate();
    return;
}
if ($route === '/violations/approve' && $method === 'POST') {
    $violationController->approve();
    return;
}
if ($route === '/violations/reject' && $method === 'POST') {
    $violationController->reject();
    return;
}

if ($route === '/payments' && $method === 'GET') {
    $paymentController->index();
    return;
}
if ($route === '/payments' && $method === 'POST') {
    $paymentController->store();
    return;
}
if ($route === '/payments/paid' && $method === 'POST') {
    $paymentController->markPaid();
    return;
}
if ($route === '/payments/reject' && $method === 'POST') {
    $paymentController->reject();
    return;
}

if ($route === '/notifications' && $method === 'GET') {
    $notificationController->index();
    return;
}

http_response_code(404);
echo '404 Not Found';
