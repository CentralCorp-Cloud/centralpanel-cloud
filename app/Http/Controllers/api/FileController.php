<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\OptionsIgnore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileController extends Controller
{
    public function getFiles(): JsonResponse
    {
        $dir = storage_path('app/public/data');
        $ignoredFolders = OptionsIgnore::pluck('folder_name')->filter()->values()->all();

        if (!is_dir($dir)) {
            return response()->json([], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $version = Cache::get('launcher_files_version', 1);
        $signature = $version . ':' . $this->buildDirectorySignature($dir, $ignoredFolders);
        $cacheKey = 'launcher_files:' . sha1($signature);

        $files = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($dir, $ignoredFolders) {
            return $this->dirToArray($dir, '', $ignoredFolders);
        });

        return response()->json($files, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function buildDirectorySignature(string $dir, array $ignoredFolders): string
    {
        $parts = [implode('|', $ignoredFolders)];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($this->isIgnored($file->getPathname(), $dir, $ignoredFolders)) {
                continue;
            }

            if ($file->isFile()) {
                $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
                $parts[] = $relativePath . ':' . $file->getMTime() . ':' . $file->getSize();
            }
        }

        sort($parts);

        return implode(';', $parts);
    }

    private function dirToArray(string $dir, string $basePath = '', array $ignoredFolders = []): array
    {
        $files = [];
        $items = scandir($dir) ?: [];

        foreach ($items as $value) {
            if (in_array($value, ['.', '..'], true) || in_array($value, $ignoredFolders, true)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $value;
            $relativePath = ltrim($basePath . '/' . $value, '/');

            if (is_dir($path)) {
                $files = array_merge($files, $this->dirToArray($path, $relativePath, $ignoredFolders));
                continue;
            }

            $files[] = [
                'path' => $relativePath,
                'size' => filesize($path),
                'hash' => hash_file('sha1', $path),
                'url' => url('storage/data/' . $relativePath),
            ];
        }

        return $files;
    }

    private function isIgnored(string $path, string $root, array $ignoredFolders): bool
    {
        $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

        foreach ($ignoredFolders as $ignoredFolder) {
            $ignoredFolder = trim(str_replace('\\', '/', $ignoredFolder), '/');
            if ($ignoredFolder !== '' && ($relative === $ignoredFolder || str_starts_with($relative, $ignoredFolder . '/'))) {
                return true;
            }
        }

        return false;
    }
}
