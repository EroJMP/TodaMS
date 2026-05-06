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

        return $base . $cleanPath;
    }
}
