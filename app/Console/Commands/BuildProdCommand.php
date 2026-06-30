<?php

namespace App\Console\Commands;

use App\Http\Controllers\InstallController;
use App\Support\DotenvEditor;
use App\Support\PanelVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BuildProdCommand extends Command
{
    protected $signature = 'build:prod
        {--output= : Output ZIP path}
        {--panel-version= : Version to embed in VERSION/build.json}
        {--commit= : Git commit SHA to embed in build.json}';

    protected $description = 'Build a production ZIP with vendor, compiled assets, temporary .env and automatic version metadata';

    public function handle(): int
    {
        $version = PanelVersion::normalize($this->option('panel-version') ?: PanelVersion::current());
        $commit = $this->option('commit') ?: $this->detectCommit();
        $tempDir = storage_path('build-temp');

        $this->info("Building production ZIP for version {$version}...");

        $this->validateBuildInputs();
        $this->prepareTemporaryDirectory($tempDir);

        try {
            $this->copyFiles($tempDir);
            $this->sanitizeCompiledManifests($tempDir);
            $this->createStorageStructure($tempDir);
            $this->createTemporaryEnv($tempDir);
            $this->createBuildMetadata($tempDir, $version, $commit);
            $this->createProductionComposer($tempDir);

            $zipPath = $this->createZip($tempDir, $version);

            $this->info("Production ZIP generated: {$zipPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Build failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    private function prepareTemporaryDirectory(string $tempDir): void
    {
        if (File::exists($tempDir)) {
            File::deleteDirectory($tempDir);
        }

        File::makeDirectory($tempDir, 0755, true);
    }

    private function copyFiles(string $tempDir): void
    {
        $basePath = base_path();
        $excludePatterns = [
            '.git',
            '.github',
            '.agents',
            '.codex',
            '.idea',
            '.vscode',
            '.env',
            '.env.*',
            '*.log',
            '*.zip',
            'auth.json',
            '.phpunit.result.cache',
            'bootstrap/cache/config.php',
            'bootstrap/cache/events.php',
            'bootstrap/cache/routes*.php',
            'database/database.sqlite',
            'node_modules',
            'phpunit.xml',
            'public/storage',
            'storage/app',
            'storage/build-temp',
            'storage/debugbar',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/installed',
            'storage/logs',
            'tests',
        ];

        $directory = new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator(
            $directory,
            function (\SplFileInfo $item) use ($basePath, $excludePatterns): bool {
                $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $item->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);

                return !$this->shouldExclude($relativePath, $excludePatterns);
            }
        );

        $iterator = new \RecursiveIteratorIterator(
            $filter,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($this->shouldExclude($relativePath, $excludePatterns)) {
                continue;
            }

            $target = $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ($item->isDir()) {
                File::ensureDirectoryExists($target, 0755, true);
                continue;
            }

            File::ensureDirectoryExists(dirname($target), 0755, true);
            File::copy($item->getPathname(), $target);
        }
    }

    private function shouldExclude(string $relativePath, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (
                $relativePath === $pattern
                || str_starts_with($relativePath, rtrim($pattern, '/') . '/')
                || fnmatch($pattern, $relativePath)
                || fnmatch($pattern, basename($relativePath))
            ) {
                return true;
            }
        }

        return false;
    }

    private function createStorageStructure(string $tempDir): void
    {
        foreach ([
            'storage/app/public',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ] as $directory) {
            $path = $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
            File::ensureDirectoryExists($path, 0755, true);

            if (!File::exists($path . DIRECTORY_SEPARATOR . '.gitkeep')) {
                File::put($path . DIRECTORY_SEPARATOR . '.gitkeep', '');
            }
        }
    }

    private function sanitizeCompiledManifests(string $tempDir): void
    {
        $cachePath = $tempDir . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache';
        $packagesPath = $cachePath . DIRECTORY_SEPARATOR . 'packages.php';
        $servicesPath = $cachePath . DIRECTORY_SEPARATOR . 'services.php';
        $productionPackages = $this->productionPackageNames();

        foreach (glob($cachePath . DIRECTORY_SEPARATOR . '{config,events,routes,routes-v7}.php', GLOB_BRACE) ?: [] as $compiledFile) {
            File::delete($compiledFile);
        }

        if (!$productionPackages || !File::exists($packagesPath)) {
            return;
        }

        $packages = require $packagesPath;
        if (!is_array($packages)) {
            File::delete($packagesPath);
            File::delete($servicesPath);
            return;
        }

        $removedProviders = [];
        foreach ($packages as $packageName => $definition) {
            if (isset($productionPackages[$packageName])) {
                continue;
            }

            foreach ($definition['providers'] ?? [] as $provider) {
                $removedProviders[] = $provider;
            }

            unset($packages[$packageName]);
        }

        File::put($packagesPath, $this->phpArrayFile($packages));

        if ($removedProviders && File::exists($servicesPath)) {
            $this->removeProvidersFromServicesManifest($servicesPath, array_unique($removedProviders));
        }
    }

    private function productionPackageNames(): array
    {
        $lockPath = base_path('composer.lock');

        if (!File::exists($lockPath)) {
            return [];
        }

        $lock = json_decode(File::get($lockPath), true);
        if (!is_array($lock)) {
            return [];
        }

        $names = [];
        foreach ($lock['packages'] ?? [] as $package) {
            if (isset($package['name'])) {
                $names[$package['name']] = true;
            }
        }

        return $names;
    }

    private function removeProvidersFromServicesManifest(string $servicesPath, array $removedProviders): void
    {
        $services = require $servicesPath;
        if (!is_array($services)) {
            File::delete($servicesPath);
            return;
        }

        $remove = array_flip($removedProviders);

        foreach (['providers', 'eager'] as $key) {
            if (isset($services[$key]) && is_array($services[$key])) {
                $services[$key] = array_values(array_filter(
                    $services[$key],
                    fn (string $provider): bool => !isset($remove[$provider])
                ));
            }
        }

        if (isset($services['deferred']) && is_array($services['deferred'])) {
            $services['deferred'] = array_filter(
                $services['deferred'],
                fn (string $provider): bool => !isset($remove[$provider])
            );
        }

        if (isset($services['when']) && is_array($services['when'])) {
            foreach (array_keys($services['when']) as $provider) {
                if (isset($remove[$provider])) {
                    unset($services['when'][$provider]);
                }
            }
        }

        File::put($servicesPath, $this->phpArrayFile($services));
    }

    private function phpArrayFile(array $data): string
    {
        return '<?php return ' . var_export($data, true) . ';' . PHP_EOL;
    }

    private function createTemporaryEnv(string $tempDir): void
    {
        File::put($tempDir . DIRECTORY_SEPARATOR . '.env', DotenvEditor::content([
            [
                'APP_NAME' => 'CentralCorp Panel',
                'APP_ENV' => 'production',
                'APP_KEY' => InstallController::TEMP_KEY,
                'APP_DEBUG' => false,
                'APP_URL' => 'http://localhost',
            ],
            [
                'LOG_CHANNEL' => 'stack',
                'LOG_DEPRECATIONS_CHANNEL' => 'null',
                'LOG_LEVEL' => 'error',
            ],
            [
                'DB_CONNECTION' => 'sqlite',
                'DB_HOST' => '',
                'DB_PORT' => '',
                'DB_DATABASE' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
            ],
            [
                'BROADCAST_DRIVER' => 'log',
                'CACHE_DRIVER' => 'file',
                'FILESYSTEM_DISK' => 'local',
                'QUEUE_CONNECTION' => 'sync',
                'SESSION_DRIVER' => 'file',
                'SESSION_LIFETIME' => 120,
            ],
        ]));
    }

    private function createBuildMetadata(string $tempDir, string $version, ?string $commit): void
    {
        File::put($tempDir . DIRECTORY_SEPARATOR . 'VERSION', $version . PHP_EOL);
        File::put($tempDir . DIRECTORY_SEPARATOR . 'build.json', json_encode([
            'version' => $version,
            'commit' => $commit,
            'built_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        File::put($tempDir . DIRECTORY_SEPARATOR . 'INSTALLATION.md', $this->installationReadme($version));
    }

    private function createProductionComposer(string $tempDir): void
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);
        unset($composer['require-dev']);

        $composer['config'] = array_merge($composer['config'] ?? [], [
            'optimize-autoloader' => true,
            'preferred-install' => 'dist',
            'sort-packages' => true,
        ]);

        File::put(
            $tempDir . DIRECTORY_SEPARATOR . 'composer.json',
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    private function createZip(string $tempDir, string $version): string
    {
        $finalOutputPath = $this->option('output') ?: base_path("centralpanel-v{$version}.zip");
        File::ensureDirectoryExists(dirname($finalOutputPath), 0755, true);

        $stagingZipPath = storage_path('app/panel-build-' . uniqid('', true) . '.zip');
        File::ensureDirectoryExists(dirname($stagingZipPath), 0755, true);

        $zip = new ZipArchive();

        if ($zip->open($stagingZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create ZIP: {$finalOutputPath}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace($tempDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();

        if (File::exists($finalOutputPath)) {
            File::delete($finalOutputPath);
        }

        try {
            File::move($stagingZipPath, $finalOutputPath);
        } catch (\Throwable) {
            File::copy($stagingZipPath, $finalOutputPath);
            File::delete($stagingZipPath);
        }

        return $finalOutputPath;
    }

    private function installationReadme(string $version): string
    {
        return <<<MD
# CentralCorp Panel {$version}

Cette archive est prete a extraire sur un serveur. Les dependances PHP sont incluses dans `vendor/` et les assets frontend sont deja compiles.

## Installation

1. Extraire le ZIP dans le dossier de votre site.
2. Pointer le serveur web vers le dossier `public/`.
3. Donner les droits d'ecriture a `storage/` et `bootstrap/cache/`.
4. Ouvrir le site dans le navigateur et suivre l'assistant d'installation.

Aucune commande `composer install` ou `npm install` n'est necessaire avec cette archive.

MD;
    }

    private function detectCommit(): ?string
    {
        $headPath = base_path('.git/HEAD');

        if (!File::exists($headPath)) {
            return null;
        }

        $head = trim(File::get($headPath));

        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/' . substr($head, 5));
            return File::exists($refPath) ? trim(File::get($refPath)) : null;
        }

        return preg_match('/^[a-f0-9]{40}$/i', $head) ? $head : null;
    }

    private function validateBuildInputs(): void
    {
        $requiredFiles = [
            'vendor/autoload.php' => 'Run composer install --no-dev --optimize-autoloader before packaging.',
            'public/build/manifest.json' => 'Run npm run build before packaging.',
        ];

        foreach ($requiredFiles as $file => $message) {
            if (!File::exists(base_path($file))) {
                throw new \RuntimeException("Missing {$file}. {$message}");
            }
        }
    }
}
