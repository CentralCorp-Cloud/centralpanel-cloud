<?php

namespace App\Support;

final class PanelVersion
{
    public const FALLBACK_VERSION = '0.0.0-dev';

    public static function current(): string
    {
        $envVersion = self::envVersion();

        if ($envVersion !== null) {
            return $envVersion;
        }

        $version = self::readVersionFile(base_path('VERSION'));

        return $version !== null ? $version : self::FALLBACK_VERSION;
    }

    public static function buildInfo(): array
    {
        $path = base_path('build.json');

        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    public static function normalize(string $version): string
    {
        $version = trim($version);

        return ltrim($version, 'vV');
    }

    private static function readVersionFile(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $version = trim((string) file_get_contents($path));

        return $version !== '' ? self::normalize($version) : null;
    }

    private static function envVersion(): ?string
    {
        $version = getenv('PANEL_VERSION');

        if ($version === false || trim((string) $version) === '') {
            $version = $_ENV['PANEL_VERSION'] ?? $_SERVER['PANEL_VERSION'] ?? null;
        }

        return is_string($version) && trim($version) !== '' ? self::normalize($version) : null;
    }
}
