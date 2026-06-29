<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionsGeneral;
use App\Models\OptionsIgnore;
use App\Models\OptionsLoader;
use App\Models\OptionsMods;
use App\Models\OptionsSecurity;
use App\Models\OptionsServer;
use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $userCount = User::count();
        $stats = $this->getStats($userCount);
        $releases = $this->getReleases();

        return view('admin.index', compact('userCount', 'stats', 'releases'));
    }

    private function getStats(int $userCount): array
    {
        $general = OptionsGeneral::first();
        $security = OptionsSecurity::first();
        $loader = OptionsLoader::first();

        return [
            'counts' => [
                [
                    'label' => __('messages.dashboard.stats_optional_mods'),
                    'value' => OptionsMods::where('optional', true)->count(),
                    'icon' => 'bi-box-seam',
                ],
                [
                    'label' => __('messages.dashboard.stats_recommended_mods'),
                    'value' => OptionsMods::where('recommended', true)->count(),
                    'icon' => 'bi-star',
                ],
                [
                    'label' => __('messages.dashboard.stats_whitelist_entries'),
                    'value' => OptionsWhitelist::count() + OptionsWhitelistRole::count(),
                    'icon' => 'bi-list-check',
                ],
                [
                    'label' => __('messages.dashboard.stats_servers'),
                    'value' => OptionsServer::count(),
                    'icon' => 'bi-hdd-network',
                ],
            ],
            'status' => [
                [
                    'label' => __('messages.dashboard.status_mods'),
                    'enabled' => (bool) ($general?->mods_enabled),
                ],
                [
                    'label' => __('messages.dashboard.status_file_verification'),
                    'enabled' => (bool) ($general?->file_verification),
                ],
                [
                    'label' => __('messages.dashboard.status_whitelist'),
                    'enabled' => (bool) ($security?->whitelist),
                ],
                [
                    'label' => __('messages.dashboard.status_loader'),
                    'enabled' => (bool) ($loader?->loader_activation),
                ],
            ],
        ];
    }

    private function getReleases(): array
    {
        return Cache::remember('centralpanel.github_releases', now()->addMinutes(30), function () {
            try {
                $response = Http::timeout(8)->get('https://github.com/CentralCorp/centralpanel-v2/releases.atom');

                if (!$response->successful()) {
                    return [];
                }

                $xml = simplexml_load_string($response->body());
                if (!$xml || !isset($xml->entry)) {
                    return [];
                }

                $releases = [];
                foreach ($xml->entry as $entry) {
                    $releases[] = (object) [
                        'title' => (string) $entry->title,
                        'description' => strip_tags((string) $entry->content),
                        'date' => date('d/m/Y H:i', strtotime((string) $entry->updated)),
                        'author' => (string) $entry->author->name,
                        'link' => (string) $entry->link['href'],
                    ];
                }

                return $releases;
            } catch (\Throwable $e) {
                Log::warning('Unable to fetch GitHub releases', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
}
