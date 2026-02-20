<?php

namespace App\Services;

use App\Models\OptionsIgnore;
use ZipArchive;

/**
 * ArchiveBuildService — Construit automatiquement les archives ZIP et le manifest.
 *
 * Peut être appelé depuis le contrôleur (auto-build) ou depuis la commande Artisan.
 * Vérifie la fraîcheur du manifest via le timestamp du dossier data.
 */
class ArchiveBuildService
{
    private string $dataDir;
    private string $archivesDir;
    private string $manifestPath;
    private int $maxSize;

    public function __construct(int $maxSize = 52428800) // 50 Mo par défaut
    {
        $this->dataDir = storage_path('app/public/data');
        $this->archivesDir = storage_path('app/public/archives');
        $this->manifestPath = storage_path('app/archive-manifest.json');
        $this->maxSize = $maxSize;
    }

    /**
     * Retourne le manifest. Le reconstruit automatiquement s'il est absent ou périmé.
     */
    public function getManifest(): ?array
    {
        if (!is_dir($this->dataDir)) {
            return null;
        }

        // Si le manifest existe et n'est pas périmé, le retourner directement
        if (file_exists($this->manifestPath) && !$this->isStale()) {
            $manifest = json_decode(file_get_contents($this->manifestPath), true);
            if ($manifest) {
                return $manifest;
            }
        }

        // Sinon, reconstruire
        return $this->build();
    }

    /**
     * Vérifie si le manifest est périmé (le dossier data a été modifié après le manifest).
     */
    public function isStale(): bool
    {
        if (!file_exists($this->manifestPath)) {
            return true;
        }

        $manifestTime = filemtime($this->manifestPath);
        $latestFileTime = $this->getLatestModificationTime($this->dataDir);

        return $latestFileTime > $manifestTime;
    }

    /**
     * Force la reconstruction du manifest et des archives.
     */
    public function build(): array
    {
        if (!is_dir($this->archivesDir)) {
            mkdir($this->archivesDir, 0755, true);
        }

        $ignoredFolders = OptionsIgnore::pluck('folder_name')->toArray();

        // Scanner les fichiers
        $allFiles = $this->scanDirectory($this->dataDir, '', $ignoredFolders);

        // Grouper par dossier de premier niveau
        $groups = $this->groupByTopDirectory($allFiles);

        // Nettoyer les anciennes archives
        $this->cleanArchives();

        $manifest = [
            'version' => '1.0',
            'directories' => [],
        ];

        foreach ($groups as $dirName => $files) {
            $manifest['directories'][$dirName] = $this->buildDirectoryManifest($dirName, $files);
        }

        // Sauvegarder le manifest en cache
        file_put_contents($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest;
    }

    /**
     * Récupère le timestamp de modification le plus récent dans un dossier (récursif).
     */
    private function getLatestModificationTime(string $dir): int
    {
        $latest = filemtime($dir);
        $items = scandir($dir);

        foreach ($items as $item) {
            if (in_array($item, ['.', '..']))
                continue;

            $path = $dir . '/' . $item;
            $mtime = is_dir($path) ? $this->getLatestModificationTime($path) : filemtime($path);

            if ($mtime > $latest) {
                $latest = $mtime;
            }
        }

        return $latest;
    }

    /**
     * Scanne récursivement un dossier et retourne les fichiers avec hash SHA-256.
     */
    private function scanDirectory(string $dir, string $basePath, array $ignoredFolders): array
    {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if (in_array($item, ['.', '..']) || in_array($item, $ignoredFolders)) {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            $relativePath = ltrim($basePath . '/' . $item, '/');

            if (is_dir($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($fullPath, $relativePath, $ignoredFolders));
            } else {
                $files[] = [
                    'path' => $relativePath,
                    'hash' => MerkleTreeService::hashFile($fullPath),
                    'size' => filesize($fullPath),
                    'fullPath' => $fullPath,
                ];
            }
        }

        return $files;
    }

    /**
     * Regroupe les fichiers par leur dossier de premier niveau.
     */
    private function groupByTopDirectory(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $parts = explode('/', $file['path']);
            $topDir = count($parts) > 1 ? $parts[0] : '__root__';
            $groups[$topDir][] = $file;
        }

        return $groups;
    }

    /**
     * Construit le manifest d'un dossier : Merkle root + archives ZIP.
     */
    private function buildDirectoryManifest(string $dirName, array $files): array
    {
        $merkleFiles = array_map(fn($f) => ['path' => $f['path'], 'hash' => $f['hash']], $files);
        $merkleRoot = MerkleTreeService::computeRoot($merkleFiles);

        $parts = $this->splitIntoParts($files);
        $archives = [];

        foreach ($parts as $partIndex => $partFiles) {
            $archiveName = count($parts) > 1
                ? "{$dirName}-part" . ($partIndex + 1) . ".zip"
                : "{$dirName}.zip";

            $archivePath = $this->archivesDir . '/' . $archiveName;
            $this->createZip($archivePath, $partFiles);

            $archives[] = [
                'url' => url('storage/archives/' . $archiveName),
                'hash' => MerkleTreeService::hashFile($archivePath),
                'size' => filesize($archivePath),
                'name' => $archiveName,
                'files' => array_map(fn($f) => [
                    'path' => $f['path'],
                    'hash' => $f['hash'],
                    'size' => $f['size'],
                ], $partFiles),
            ];
        }

        return [
            'merkleRoot' => $merkleRoot,
            'archives' => $archives,
        ];
    }

    private function splitIntoParts(array $files): array
    {
        $parts = [];
        $currentPart = [];
        $currentSize = 0;

        foreach ($files as $file) {
            if ($currentSize + $file['size'] > $this->maxSize && !empty($currentPart)) {
                $parts[] = $currentPart;
                $currentPart = [];
                $currentSize = 0;
            }

            $currentPart[] = $file;
            $currentSize += $file['size'];
        }

        if (!empty($currentPart)) {
            $parts[] = $currentPart;
        }

        return $parts;
    }

    private function createZip(string $zipPath, array $files): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return;
        }

        foreach ($files as $file) {
            $zip->addFile($file['fullPath'], $file['path']);
        }

        $zip->close();
    }

    private function cleanArchives(): void
    {
        $files = glob($this->archivesDir . '/*.zip');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
