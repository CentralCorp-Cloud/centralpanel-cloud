<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileController extends Controller
{
    public function getFiles(): JsonResponse
    {
        $apiVersion = request()->query('api_version') === '2' ? 2 : 1;
        $root = storage_path('app/public/data');

        if (!is_dir($root)) {
            return response()->json([], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($apiVersion === 2) {
            $instances = Instance::with('ignoredFolders')->get();
            $ignoredByInstance = $instances->mapWithKeys(fn (Instance $instance) => [
                $instance->name => $instance->ignoredFolders->pluck('folder_name')->filter()->values()->all(),
            ])->all();

            return $this->manifestResponse($root, '', $ignoredByInstance, 'v2');
        }

        $instance = Instance::with('ignoredFolders')->where('is_default', true)->first()
            ?? Instance::with('ignoredFolders')->first();
        if (!$instance) {
            return response()->json([], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $instanceRoot = $root . DIRECTORY_SEPARATOR . $instance->name;
        if (!is_dir($instanceRoot)) {
            return response()->json([], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $this->manifestResponse(
            $instanceRoot,
            $instance->name . '/',
            ['' => $instance->ignoredFolders->pluck('folder_name')->filter()->values()->all()],
            'legacy:' . $instance->id,
        );
    }

    private function manifestResponse(string $root, string $urlPrefix, array $ignoredByInstance, string $mode): JsonResponse
    {
        $version = Cache::get('launcher_files_version', 1);
        $signature = $version . ':' . $mode . ':' . $this->buildDirectorySignature($root, $ignoredByInstance);
        $cacheKey = 'launcher_files:' . sha1($signature);

        $files = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($root, $urlPrefix, $ignoredByInstance) {
            return $this->dirToArray($root, '', $urlPrefix, $ignoredByInstance);
        });

        return response()->json($files, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function buildDirectorySignature(string $root, array $ignoredByInstance): string
    {
        $parts = [json_encode($ignoredByInstance) ?: ''];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (!$file->isFile() || $this->isIgnored($relativePath, $ignoredByInstance)) {
                continue;
            }

            $parts[] = $relativePath . ':' . $file->getMTime() . ':' . $file->getSize();
        }

        sort($parts);
        return implode(';', $parts);
    }

    private function dirToArray(
        string $directory,
        string $basePath,
        string $urlPrefix,
        array $ignoredByInstance,
    ): array {
        $files = [];
        foreach (scandir($directory) ?: [] as $value) {
            if (in_array($value, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $value;
            $relativePath = ltrim($basePath . '/' . $value, '/');
            if ($this->isIgnored($relativePath, $ignoredByInstance)) {
                continue;
            }

            if (is_dir($path)) {
                $files = array_merge($files, $this->dirToArray($path, $relativePath, $urlPrefix, $ignoredByInstance));
                continue;
            }

            $files[] = [
                'path' => $relativePath,
                'size' => filesize($path),
                'hash' => hash_file('sha1', $path),
                'url' => url('storage/data/' . $urlPrefix . $relativePath),
            ];
        }

        return $files;
    }

    private function isIgnored(string $relativePath, array $ignoredByInstance): bool
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        $segments = explode('/', $normalized, 2);
        $instanceName = count($ignoredByInstance) === 1 && array_key_exists('', $ignoredByInstance)
            ? ''
            : ($segments[0] ?? '');
        $instancePath = $instanceName === '' ? $normalized : ($segments[1] ?? '');

        foreach ($ignoredByInstance[$instanceName] ?? [] as $ignoredFolder) {
            $ignored = trim(str_replace('\\', '/', (string) $ignoredFolder), '/');
            if ($ignored !== '' && ($instancePath === $ignored || str_starts_with($instancePath, $ignored . '/'))) {
                return true;
            }
        }

        return false;
    }
}
