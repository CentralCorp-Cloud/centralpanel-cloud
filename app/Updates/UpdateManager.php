<?php

namespace App\Updates;

use App\Http\Controllers\InstallController;
use App\Support\PanelVersion;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

class UpdateManager
{
    private const REQUIRED_ARCHIVE_FILES = [
        'artisan',
        'composer.json',
        'VERSION',
        'vendor/autoload.php',
        'public/index.php',
    ];

    private const RUNTIME_EXCLUDE_PATTERNS = [
        '.env',
        '.env.*',
        'auth.json',
        'database/database.sqlite',
        'public/storage',
        'storage/app/*',
        'storage/framework/cache/*',
        'storage/framework/sessions/*',
        'storage/framework/testing/*',
        'storage/framework/views/*',
        'storage/installed',
        'storage/logs/*',
        'bootstrap/cache/*.php',
    ];

    private const BOOTSTRAP_CACHE_PATTERNS = [
        'bootstrap/cache/*.php',
    ];

    protected Filesystem $files;
    protected string $repository;
    protected string $apiUrl;
    protected string $assetPattern;
    protected string $currentVersion;

    public function __construct(
        Filesystem $files,
        string $currentVersion,
        ?string $repository = null,
        ?string $assetPattern = null
    ) {
        $this->files = $files;
        $this->currentVersion = PanelVersion::normalize($currentVersion);
        $this->repository = $repository ?: config('updates.repository', 'CentralCorp/centralpanel-v2');
        $this->assetPattern = $assetPattern ?: config('updates.asset_pattern', '/centralpanel.*\.zip$/i');
        $this->apiUrl = "https://api.github.com/repos/{$this->repository}/releases/latest";
    }

    public function fetchUpdateInfo(): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'CentralPanel-UpdateManager',
                'Accept' => 'application/vnd.github.v3+json',
            ])->timeout(10)->get($this->apiUrl);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $version = isset($data['tag_name']) ? PanelVersion::normalize($data['tag_name']) : null;
            $asset = $this->findZipAsset($data['assets'] ?? []);
            $hashAsset = $this->findHashAsset($data['assets'] ?? [], $asset['name'] ?? null);

            if (!$version || !$asset) {
                return null;
            }

            return [
                'version' => $version,
                'url' => $asset['browser_download_url'],
                'file' => $asset['name'],
                'hash_url' => $hashAsset['browser_download_url'] ?? null,
                'hash' => null,
                'php_version' => InstallController::MIN_PHP_VERSION,
                'release_url' => $data['html_url'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('UpdateManager fetch error: ' . $e->getMessage());
            return null;
        }
    }

    public function hasUpdate(?array $info = null): bool
    {
        $info = $info ?: $this->fetchUpdateInfo();

        if (!$info || empty($info['version'])) {
            return false;
        }

        return version_compare($info['version'], $this->currentVersion, '>');
    }

    public function downloadUpdate(array $info): string
    {
        $updatesPath = storage_path('app/updates');
        $this->ensureDirectory($updatesPath);

        $filePath = $updatesPath . DIRECTORY_SEPARATOR . basename($info['file']);

        if ($this->files->exists($filePath)) {
            $this->files->delete($filePath);
        }

        $response = Http::withHeaders([
            'User-Agent' => 'CentralPanel-UpdateManager',
        ])->timeout(120)->withOptions(['sink' => $filePath])->get($info['url']);

        if (!$response->successful()) {
            $this->files->delete($filePath);
            throw new RuntimeException('Failed to download update file.');
        }

        $expectedHash = $info['hash'] ?? $this->downloadHash($info['hash_url'] ?? null);
        $actualHash = hash_file('sha256', $filePath);

        if ($expectedHash && (!is_string($actualHash) || !hash_equals(strtolower($expectedHash), strtolower($actualHash)))) {
            $this->files->delete($filePath);
            throw new RuntimeException('Downloaded update hash mismatch.');
        }

        return $filePath;
    }

    public function installUpdate(string $zipPath): void
    {
        if (!is_writable(base_path())) {
            throw new RuntimeException('Base path is not writable.');
        }

        $this->validateArchive($zipPath);
        $this->purgeCompiledCaches();

        $stagingPath = storage_path('app/update-staging-' . uniqid('', true));
        $this->ensureDirectory($stagingPath);

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Unable to open update ZIP.');
            }

            if (!$zip->extractTo($stagingPath)) {
                $zip->close();
                throw new RuntimeException('Unable to extract update ZIP.');
            }
            $zip->close();

            $packageRoot = $this->locatePackageRoot($stagingPath);
            $this->copyPackageFiles($packageRoot, base_path());
            $this->purgeCompiledCaches();
            $this->files->delete($zipPath);

            Artisan::call('migrate', ['--force' => true]);
            $this->clearCaches();
            $this->purgeCompiledCaches();
        } finally {
            if ($this->files->exists($stagingPath)) {
                $this->files->deleteDirectory($stagingPath);
            }
        }
    }

    public function updateIfAvailable(): bool
    {
        $info = $this->fetchUpdateInfo();

        if (!$this->hasUpdate($info)) {
            return false;
        }

        $zipPath = $this->downloadUpdate($info);
        $this->installUpdate($zipPath);

        return true;
    }

    private function findZipAsset(array $assets): ?array
    {
        $fallback = null;

        foreach ($assets as $asset) {
            $name = $asset['name'] ?? '';

            if (!str_ends_with(strtolower($name), '.zip') || empty($asset['browser_download_url'])) {
                continue;
            }

            $fallback ??= $asset;

            if (@preg_match($this->assetPattern, $name)) {
                return $asset;
            }
        }

        return $fallback;
    }

    private function findHashAsset(array $assets, ?string $zipName): ?array
    {
        if (!$zipName) {
            return null;
        }

        foreach ($assets as $asset) {
            if (($asset['name'] ?? '') === $zipName . '.sha256') {
                return $asset;
            }
        }

        return null;
    }

    private function downloadHash(?string $hashUrl): ?string
    {
        if (!$hashUrl) {
            return null;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'CentralPanel-UpdateManager',
        ])->timeout(20)->get($hashUrl);

        if (!$response->successful()) {
            return null;
        }

        if (preg_match('/\b[a-f0-9]{64}\b/i', $response->body(), $matches)) {
            return strtolower($matches[0]);
        }

        return null;
    }

    private function validateArchive(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open update ZIP.');
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            $this->validateEntryPath($name);
            $entries[] = $name;
        }
        $zip->close();

        $prefix = $this->detectArchivePrefix($entries);
        foreach (self::REQUIRED_ARCHIVE_FILES as $requiredFile) {
            if (!in_array($prefix . $requiredFile, $entries, true)) {
                throw new RuntimeException("Invalid update ZIP: missing {$requiredFile}.");
            }
        }
    }

    private function validateEntryPath(string $name): void
    {
        if (
            $name === ''
            || str_starts_with($name, '/')
            || preg_match('/^[a-zA-Z]:\//', $name)
            || str_contains($name, '..\\')
            || in_array('..', explode('/', $name), true)
        ) {
            throw new RuntimeException("Invalid update ZIP path: {$name}");
        }
    }

    private function detectArchivePrefix(array $entries): string
    {
        if (in_array('artisan', $entries, true)) {
            return '';
        }

        foreach ($entries as $entry) {
            if (substr_count($entry, '/') === 1 && str_ends_with($entry, '/artisan')) {
                return substr($entry, 0, strpos($entry, '/') + 1);
            }
        }

        return '';
    }

    private function locatePackageRoot(string $stagingPath): string
    {
        if ($this->files->exists($stagingPath . DIRECTORY_SEPARATOR . 'artisan')) {
            return $stagingPath;
        }

        foreach ($this->files->directories($stagingPath) as $directory) {
            if ($this->files->exists($directory . DIRECTORY_SEPARATOR . 'artisan')) {
                return $directory;
            }
        }

        throw new RuntimeException('Invalid update package: artisan file not found.');
    }

    private function copyPackageFiles(string $source, string $destination): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($this->shouldSkipRuntimePath($relativePath)) {
                continue;
            }

            $target = $destination . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($item->isDir()) {
                $this->ensureDirectory($target);
                continue;
            }

            $this->ensureDirectory(dirname($target));
            $this->files->copy($item->getPathname(), $target);
        }
    }

    private function shouldSkipRuntimePath(string $path): bool
    {
        foreach (self::RUNTIME_EXCLUDE_PATTERNS as $pattern) {
            if (
                $path === $pattern
                || str_starts_with($path, rtrim($pattern, '/') . '/')
                || fnmatch($pattern, $path)
                || fnmatch($pattern, basename($path))
            ) {
                return true;
            }
        }

        return false;
    }

    private function clearCaches(): void
    {
        foreach (['optimize:clear', 'config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                Log::warning("Unable to run {$command} after update", ['error' => $e->getMessage()]);
            }
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    private function purgeCompiledCaches(): void
    {
        foreach (self::BOOTSTRAP_CACHE_PATTERNS as $pattern) {
            foreach (glob(base_path($pattern)) ?: [] as $file) {
                if (is_file($file)) {
                    $this->files->delete($file);
                }
            }
        }
    }
}
