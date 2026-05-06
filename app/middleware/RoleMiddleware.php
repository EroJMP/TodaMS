<?php

class RoleMiddleware
{
    public static function allow(array $roles): void
    {
        $user = $_SESSION['user'] ?? null;
        if ($user === null || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden: You do not have permission to access this page.';
            exit;
        }
    }
}
