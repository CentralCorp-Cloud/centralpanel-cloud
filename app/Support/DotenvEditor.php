<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class DotenvEditor
{
    private const BOOLEAN_KEYS = [
        'APP_DEBUG',
        'DB_FOREIGN_KEYS',
        'SESSION_ENCRYPT',
    ];

    private const NULL_LITERAL_KEYS = [
        'LOG_DEPRECATIONS_CHANNEL',
        'MAIL_ENCRYPTION',
        'MAIL_PASSWORD',
        'MAIL_USERNAME',
        'REDIS_PASSWORD',
        'SESSION_DOMAIN',
    ];

    private const NUMERIC_KEYS = [
        'DB_PORT',
        'MAIL_PORT',
        'REDIS_PORT',
        'SESSION_LIFETIME',
    ];

    public static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    public static function line(string $key, mixed $value): string
    {
        return $key . '=' . self::formatValue($key, $value);
    }

    public static function content(array $sections): string
    {
        $lines = [];

        foreach ($sections as $section) {
            if ($lines !== []) {
                $lines[] = '';
            }

            foreach ($section as $key => $value) {
                $lines[] = self::line($key, $value);
            }
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    public static function updateFile(string $path, array $values): void
    {
        $content = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $line = self::line($key, $value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            $replaced = false;

            $content = preg_replace_callback($pattern, function () use (&$replaced, $line) {
                if (!$replaced) {
                    $replaced = true;
                    return $line;
                }

                return '';
            }, $content);

            if (!$replaced) {
                $content = rtrim($content, "\r\n") . PHP_EOL . $line . PHP_EOL;
            }

            $content = preg_replace("/(\r?\n){3,}/", PHP_EOL . PHP_EOL, $content);
        }

        File::put($path, rtrim($content, "\r\n") . PHP_EOL);
    }

    private static function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;
        $lowerValue = strtolower($value);

        if (in_array($key, self::BOOLEAN_KEYS, true) && in_array($lowerValue, ['true', 'false'], true)) {
            return $lowerValue;
        }

        if (in_array($key, self::NULL_LITERAL_KEYS, true) && $lowerValue === 'null') {
            return 'null';
        }

        if (in_array($key, self::NUMERIC_KEYS, true) && preg_match('/^-?\d+$/', $value)) {
            return $value;
        }

        $escaped = str_replace(
            ["\\", '"', '$', "\n", "\r"],
            ["\\\\", '\\"', '\\$', '\\n', ''],
            $value
        );

        return '"' . $escaped . '"';
    }
}
