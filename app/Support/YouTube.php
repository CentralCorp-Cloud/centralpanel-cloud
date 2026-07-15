<?php

namespace App\Support;

final class YouTube
{
    public static function videoId(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $value) === 1) {
            return $value;
        }

        $parts = parse_url($value);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');
        $candidate = null;

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            $candidate = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (isset($query['v'])) {
                $candidate = $query['v'];
            } elseif (preg_match('#^(?:shorts|embed|v)/([a-zA-Z0-9_-]{11})#', $path, $matches) === 1) {
                $candidate = $matches[1];
            }
        }

        return is_string($candidate) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $candidate) === 1
            ? $candidate
            : null;
    }

    public static function type(?string $url): string
    {
        return $url && str_contains($url, 'youtube.com/shorts/') ? 'short' : 'video';
    }
}
