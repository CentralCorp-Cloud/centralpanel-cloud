<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class PanelCache
{
    public static function clearAll(): array
    {
        return array_merge(
            self::runCommands(['optimize:clear', 'config:clear', 'route:clear', 'view:clear', 'cache:clear']),
            self::clearRuntimeFiles()
        );
    }

    public static function clearApplication(): array
    {
        return self::runCommands(['cache:clear']);
    }

    public static function clearBootstrap(): array
    {
        return self::deletePatterns([
            base_path('bootstrap/cache/*.php'),
        ]);
    }

    public static function clearViews(): array
    {
        return array_merge(
            self::runCommands(['view:clear']),
            self::clearCompiledViews()
        );
    }

    public static function clearRuntimeFiles(): array
    {
        return self::deletePatterns([
            base_path('bootstrap/cache/*.php'),
            storage_path('framework/views/*.php'),
        ]);
    }

    public static function clearCompiledViews(): array
    {
        return self::deletePatterns([
            storage_path('framework/views/*.php'),
        ]);
    }

    private static function runCommands(array $commands): array
    {
        $results = [];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $results[] = "artisan {$command}";
            } catch (\Throwable $e) {
                Log::warning("Unable to run {$command}", ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    private static function deletePatterns(array $patterns): array
    {
        $deleted = [];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                if (!is_file($file)) {
                    continue;
                }

                File::delete($file);
                $deleted[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
            }
        }

        return $deleted;
    }
}
