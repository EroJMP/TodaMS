<?php

class Url
{
    public static function basePath(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $scriptDir = rtrim($scriptDir, '/');
        return $scriptDir === '' ? '' : $scriptDir;
    }

    public static function to(string $path = '/'): string
    {
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $base = self::basePath();
        $cleanPath = '/' . ltrim($path, '/');

        // Assets should be served directly.
        if (str_starts_with($cleanPath, '/assets/')) {
            return $base . $cleanPath;
        }

        // No rewrite required: route through index.php query parameter.
        if ($cleanPath === '/') {
            return $base . '/index.php';
        }

        return $base . '/index.php?route=' . rawurlencode(ltrim($cleanPath, '/'));
    }
}
