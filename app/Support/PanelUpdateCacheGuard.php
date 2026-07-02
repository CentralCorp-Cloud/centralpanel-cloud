<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class PanelUpdateCacheGuard
{
    private const VERSION_MARKER = 'app/panel-runtime-version';

    public static function ensureFreshForCurrentVersion(): array
    {
        return self::ensureFreshForVersion(PanelVersion::current());
    }

    public static function ensureFreshForVersion(string $version): array
    {
        $version = PanelVersion::normalize($version);

        if ($version === '' || self::storedVersion() === $version) {
            return [];
        }

        $cleared = PanelCache::clearCompiledViews();
        self::storeVersion($version);

        return $cleared;
    }

    private static function storedVersion(): ?string
    {
        $path = self::markerPath();

        if (!File::exists($path)) {
            return null;
        }

        $version = trim((string) File::get($path));

        return $version !== '' ? PanelVersion::normalize($version) : null;
    }

    private static function storeVersion(string $version): void
    {
        $path = self::markerPath();

        try {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $version);
        } catch (\Throwable $e) {
            Log::warning('Unable to write panel runtime version marker', ['error' => $e->getMessage()]);
        }
    }

    private static function markerPath(): string
    {
        return storage_path(self::VERSION_MARKER);
    }
}
