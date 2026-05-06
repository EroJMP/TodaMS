<?php

class RoleMiddleware
{
    public static function allow(array $roles): void
    {
        $user = $_SESSION['user'] ?? null;
        if (is_array($user) && (($user['role'] ?? '') === 'super_admin')) {
            return;
        }
        if ($user === null || !in_array($user['role'], $roles, true)) {
            $audit = new AuditService();
            $attemptedRoute = isset($_GET['route']) ? '/' . trim((string) $_GET['route'], '/') : (string) ($_SERVER['REQUEST_URI'] ?? '/');
            $audit->log(
                'ACCESS_DENIED',
                'Unauthorized route access attempt: ' . $attemptedRoute,
                is_array($user) ? $user : null
            );
            http_response_code(403);
            echo 'Forbidden: You do not have permission to access this page.';
            exit;
        }
    }
}
