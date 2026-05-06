<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isset($_SESSION['user'])) {
            Response::redirect('/login');
        }
    }
}
