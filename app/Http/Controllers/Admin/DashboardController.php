<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\OptionsGeneral;
use App\Models\OptionsIgnore;
use App\Models\OptionsMods;
use App\Models\OptionsSecurity;
use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private const GITHUB_API_BASE = 'https://api.github.com/repos/CentralCorp/centralpanel-v2';
    private const RELEASE_CACHE_KEY = 'centralpanel.github_releases.v2';
    private const GITHUB_FETCH_LIMIT = 50;
    private const RELEASE_LIMIT = 12;

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
        $defaultInstance = Instance::where('is_default', true)->first() ?? Instance::first();

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
                    'value' => Instance::count(),
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
                    'enabled' => (bool) ($defaultInstance?->loader_activation),
                ],
            ],
        ];
    }

    private function getReleases(): array
    {
        return Cache::remember(self::RELEASE_CACHE_KEY, now()->addMinutes(15), function () {
            try {
                $releaseMap = $this->fetchGitHubReleaseMap();
                $taggedReleases = $this->fetchGitHubTags($releaseMap);

                return $taggedReleases !== [] ? $taggedReleases : array_values($releaseMap);
            } catch (\Throwable $e) {
                Log::warning('Unable to fetch GitHub releases', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    private function fetchGitHubReleaseMap(): array
    {
        $response = Http::withHeaders($this->githubHeaders())
            ->timeout(8)
            ->get(self::GITHUB_API_BASE . '/releases', ['per_page' => self::GITHUB_FETCH_LIMIT]);

        if (!$response->successful() || !is_array($response->json())) {
            return [];
        }

        return collect($response->json())
            ->filter(fn (array $release) => empty($release['draft']))
            ->take(self::RELEASE_LIMIT)
            ->mapWithKeys(function (array $release) {
                $tag = (string) (($release['tag_name'] ?? '') ?: ($release['name'] ?? ''));

                if ($tag === '') {
                    return [];
                }

                return [$tag => (object) [
                    'title' => $tag,
                    'description' => $this->formatReleaseDescription($release['body'] ?? null),
                    'date' => $this->formatReleaseDate($release['published_at'] ?? $release['created_at'] ?? null),
                    'author' => (string) ($release['author']['login'] ?? ''),
                    'link' => (string) ($release['html_url'] ?? 'https://github.com/CentralCorp/centralpanel-v2/releases'),
                ]];
            })
            ->all();
    }

    private function fetchGitHubTags(array $releaseMap): array
    {
        $response = Http::withHeaders($this->githubHeaders())
            ->timeout(8)
            ->get(self::GITHUB_API_BASE . '/tags', ['per_page' => self::GITHUB_FETCH_LIMIT]);

        if (!$response->successful() || !is_array($response->json())) {
            return [];
        }

        $tags = collect($response->json())
            ->filter(fn (array $tag) => isset($tag['name']) && $tag['name'] !== '')
            ->values()
            ->all();

        usort($tags, fn (array $left, array $right) => version_compare(
            $this->normalizeVersionTag((string) $right['name']),
            $this->normalizeVersionTag((string) $left['name'])
        ));

        return collect($tags)
            ->take(self::RELEASE_LIMIT)
            ->map(function (array $tag) use ($releaseMap) {
                $tagName = (string) $tag['name'];
                $release = $releaseMap[$tagName] ?? null;

                return (object) [
                    'title' => $tagName,
                    'description' => $release?->description ?? 'No release note published for this tag.',
                    'date' => $release?->date ?? '',
                    'author' => $release?->author ?? '',
                    'link' => $release?->link ?? 'https://github.com/CentralCorp/centralpanel-v2/releases/tag/' . rawurlencode($tagName),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeVersionTag(string $tag): string
    {
        return preg_replace('/^[vV]/', '', $tag) ?: $tag;
    }

    private function githubHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'CentralPanel-Dashboard',
        ];
    }

    private function formatReleaseDescription(?string $description): string
    {
        $description = trim(strip_tags((string) $description));

        return $description !== '' ? $description : 'No content.';
    }

    private function formatReleaseDate(?string $date): string
    {
        if (!$date) {
            return '';
        }

        $timestamp = strtotime($date);

        return $timestamp ? date('d/m/Y H:i', $timestamp) : '';
    }
}
