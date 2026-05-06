<?php

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function redirect(string $path, ?string $flashType = null, ?string $flashMessage = null): void
    {
        if ($flashType !== null && $flashMessage !== null) {
            $_SESSION['flash'] = [
                'type' => $flashType,
                'message' => $flashMessage,
            ];
        }
        header('Location: ' . Url::to($path));
        exit;
    }

    public static function pullFlash(): ?array
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}
