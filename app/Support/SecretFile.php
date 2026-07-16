<?php

namespace App\Support;

final class SecretFile
{
    public static function read(?string $path, ?string $fallback = null): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return $fallback;
        }

        $value = @file_get_contents($path);

        return $value === false ? $fallback : trim($value);
    }
}
