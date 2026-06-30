<?php

namespace App\Support;

use RuntimeException;

final class DatabasePath
{
    public static function sqlite(?string $path): string
    {
        $path = trim((string) $path, " \t\n\r\0\x0B\"'");

        if ($path === '') {
            return database_path('database.sqlite');
        }

        if ($path === ':memory:') {
            return $path;
        }

        $path = DotenvEditor::normalizePath($path);

        if (self::isAbsolute($path)) {
            return $path;
        }

        if (str_starts_with($path, 'database/')) {
            return base_path($path);
        }

        return database_path($path);
    }

    public static function ensureSqliteFile(?string $path): string
    {
        $path = self::sqlite($path);

        if ($path === ':memory:') {
            return $path;
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create SQLite database directory: {$directory}");
        }

        if (!file_exists($path) && !touch($path)) {
            throw new RuntimeException("Unable to create SQLite database file: {$path}");
        }

        if (!is_readable($path) || !is_writable($path)) {
            throw new RuntimeException("SQLite database file is not readable/writable: {$path}");
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("SQLite database directory is not writable: {$directory}");
        }

        return $path;
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || preg_match('/^[a-zA-Z]:\//', $path) === 1;
    }
}
