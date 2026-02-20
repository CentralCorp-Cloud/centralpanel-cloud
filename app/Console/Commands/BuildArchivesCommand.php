<?php

namespace App\Console\Commands;

use App\Services\ArchiveBuildService;
use Illuminate\Console\Command;

/**
 * Commande Artisan pour forcer la reconstruction des archives.
 *
 * Usage : php artisan archives:build
 *
 * Note : le manifest se reconstruit aussi automatiquement via l'API
 * quand les fichiers changent. Cette commande permet de forcer
 * une reconstruction manuelle si nécessaire.
 */
class BuildArchivesCommand extends Command
{
    protected $signature = 'archives:build
        {--max-size=52428800 : Taille maximum par archive en octets (défaut: 50 Mo)}';

    protected $description = 'Force la reconstruction des archives ZIP et du manifest';

    public function handle(): int
    {
        $maxSize = (int) $this->option('max-size');
        $service = new ArchiveBuildService($maxSize);

        $this->info('🔍 Construction des archives...');

        $manifest = $service->build();

        if (!$manifest) {
            $this->error('Le dossier data n\'existe pas.');
            return 1;
        }

        $archiveCount = 0;
        foreach ($manifest['directories'] as $dir) {
            $archiveCount += count($dir['archives']);
        }

        $this->info("✅ Manifest généré : {$archiveCount} archives pour " . count($manifest['directories']) . " dossiers");

        return 0;
    }
}
