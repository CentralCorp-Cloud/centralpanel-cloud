<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\News;
use App\Models\OptionsGeneral;
use App\Models\OptionsRPC;
use App\Models\OptionsSecurity;
use App\Models\OptionsUI;
use App\Support\YouTube;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    public function getOptions(): JsonResponse
    {
        $baseUrl = request()->getSchemeAndHttpHost();
        $apiVersion = request()->query('api_version') === '2' ? 2 : 1;
        $cacheVersion = Cache::get('launcher_options_version', 1);
        $cacheKey = 'launcher_options:' . $cacheVersion . ':v' . $apiVersion . ':' . sha1($baseUrl);

        $data = Cache::remember($cacheKey, now()->addMinute(), function () use ($baseUrl, $apiVersion) {
            $general = OptionsGeneral::first();
            $security = OptionsSecurity::first();
            $ui = OptionsUI::first();
            $rpc = OptionsRPC::first();
            $instances = Instance::with(['mods', 'whitelist', 'whitelistRoles', 'ignoredFolders', 'backgrounds'])
                ->orderByDesc('is_default')
                ->orderBy('display_name')
                ->get();

            $instancesData = $instances
                ->map(fn (Instance $instance) => $this->serializeInstance($instance, $baseUrl, $general->azuriom_url ?? null))
                ->values()
                ->all();
            $defaultInstance = collect($instancesData)->firstWhere('is_default', true) ?? ($instancesData[0] ?? null);
            $newsMode = $general->news_mode ?? 'rss';
            $azuriomNewsUrl = ($general->auth_mode ?? 'azuriom') === 'azuriom'
                && filled($general->azuriom_url ?? null)
                ? rtrim($general->azuriom_url, '/') . '/api/posts'
                : '';
            if ($newsMode === 'azuriom' && $azuriomNewsUrl === '') {
                $newsMode = 'builtin';
            }

            $news = $newsMode === 'builtin'
                ? News::query()
                    ->where(function ($query) {
                        $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                    })
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get()
                    ->map(fn (News $article) => [
                        'id' => $article->id,
                        'title' => $article->title,
                        'content' => $article->content,
                        'author' => $article->author ?? '',
                        'image' => $article->image
                            ? $baseUrl . '/storage/' . ltrim($article->image, '/')
                            : '',
                        'published_at' => ($article->published_at ?? $article->created_at)?->toIso8601String() ?? '',
                        'url' => $baseUrl,
                    ])
                    ->values()
                    ->all()
                : [];

            $output = [
                'auth_mode' => $general->auth_mode ?? 'azuriom',
                'news_mode' => $newsMode,
                'news_rss_url' => $general->news_rss_url ?? '',
                'news_azuriom_url' => $azuriomNewsUrl,
                'news' => $news,
                'maintenance' => (bool) ($security->maintenance ?? false),
                'maintenance_message' => $security->maintenance_message ?? 'Please define a maintenance message',
                'client_id' => '',
                'verify' => (bool) ($general->file_verification ?? true),
                'modde' => (bool) ($general->mods_enabled ?? true),
                'java' => (bool) ($general->embedded_java ?? false),
                'dataDirectory' => $general->game_folder_name ?? 'centralcorp',
                'ram_min' => ($general->min_ram ?? 2048) / 1024,
                'ram_max' => ($general->max_ram ?? 4096) / 1024,
                'online' => 'true',
                'game_args' => [],
                'money' => (bool) ($general->money_display ?? false),
                'role' => (bool) ($general->role_display ?? true),
                'splash' => $ui->splash ?? '',
                'splash_author' => $ui->splash_author ?? '',
                'accent_color' => $ui->accent_color ?? '#FFA500',
                'azauth' => $general->azuriom_url ?? null,
                'rpc_activation' => (bool) ($rpc->rpc_activation ?? false),
                'rpc_id' => $rpc->rpc_id ?? '',
                'rpc_details' => $rpc->rpc_details ?? '',
                'rpc_state' => $rpc->rpc_state ?? '',
                'rpc_large_image' => 'small',
                'rpc_large_text' => $rpc->rpc_large_text ?? '',
                'rpc_small_image' => 'large',
                'rpc_small_text' => $rpc->rpc_small_text ?? '',
                'rpc_button1' => $rpc->rpc_button1 ?? '',
                'rpc_button1_url' => $rpc->rpc_button1_url ?? '',
                'rpc_button2' => $rpc->rpc_button2 ?? '',
                'rpc_button2_url' => $rpc->rpc_button2_url ?? '',
                'whitelist_activate' => (bool) ($security->whitelist ?? false),
                'alert_activate' => (bool) ($ui->alert_activation ?? false),
                'alert_scroll' => (bool) ($ui->alert_scroll ?? false),
                'alert_msg' => $ui->alert_msg ?? '',
                'video_activate' => (bool) ($ui->video_activation ?? false),
                'video_url' => YouTube::videoId($ui->video_url ?? null) ?? '',
                'video_type' => YouTube::type($ui->video_url ?? null),
                'email_verified' => (bool) ($general->email_verified ?? false),
            ];

            if ($apiVersion === 2) {
                $output['instances'] = $instancesData;
                return $output;
            }

            return array_merge($output, $this->legacyInstanceFields($defaultInstance));
        });

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function serializeInstance(Instance $instance, string $baseUrl, ?string $azuriomUrl): array
    {
        $roleData = [];
        foreach ($instance->backgrounds as $background) {
            $roleData['role' . $background->role_id] = [
                'name' => $background->role_name,
                'background' => $background->image_path
                    ? $baseUrl . '/storage/' . ltrim($background->image_path, '/')
                    : '',
                'video_url' => YouTube::videoId($background->video_url) ?? '',
                'video_type' => YouTube::type($background->video_url),
            ];
        }

        $serverIcon = null;
        if ($instance->server_icon) {
            $serverIcon = $baseUrl . '/storage/' . ltrim($instance->server_icon, '/');
        } elseif ($instance->server_icon_url) {
            $serverIcon = filter_var($instance->server_icon_url, FILTER_VALIDATE_URL)
                ? $instance->server_icon_url
                : ($azuriomUrl
                    ? rtrim($azuriomUrl, '/') . '/storage/' . ltrim(str_replace('storage/', '', $instance->server_icon_url), '/')
                    : null);
        }

        return [
            'name' => $instance->name,
            'display_name' => $instance->display_name,
            'description' => $instance->description ?? '',
            'is_default' => (bool) $instance->is_default,
            'game_version' => $instance->minecraft_version ?? '',
            'status' => [
                'nameServer' => $instance->server_name ?? '',
                'ip' => $instance->server_ip ?? '',
                'port' => (int) ($instance->server_port ?? 0),
            ],
            'server_icon' => $serverIcon,
            'loader' => [
                'type' => $instance->loader_type ?? '',
                'build' => $instance->loader_build_version ?? '',
                'enable' => (bool) $instance->loader_activation,
            ],
            'background_default' => $instance->background_default
                ? $baseUrl . '/storage/' . ltrim($instance->background_default, '/')
                : null,
            'rpc_details_override' => $instance->rpc_details_override,
            'whitelist' => $instance->whitelist->pluck('users')->filter()->values()->all(),
            'whitelist_roles' => $instance->whitelistRoles->pluck('role')->filter()->values()->all(),
            'ignored' => $instance->ignoredFolders->pluck('folder_name')->filter()->values()->all(),
            'role_data' => $roleData,
            'mods' => $instance->mods->map(function ($mod) use ($baseUrl) {
                return [
                    'id' => $mod->id,
                    'file' => $mod->file,
                    'name' => $mod->name,
                    'description' => $mod->description ?? '',
                    'icon' => $mod->icon ? $baseUrl . '/storage/' . ltrim($mod->icon, '/') : '',
                    'optional' => (bool) $mod->optional,
                    'recommended' => (bool) $mod->recommended,
                ];
            })->values()->all(),
        ];
    }

    private function legacyInstanceFields(?array $instance): array
    {
        if ($instance === null) {
            return [
                'game_version' => '',
                'status' => ['nameServer' => '', 'ip' => '', 'port' => 0],
                'loader' => ['type' => '', 'build' => '', 'enable' => false],
                'server_icon' => null,
                'role_data' => [],
                'ignored' => [],
                'whitelist' => [],
                'whitelist_roles' => [],
            ];
        }

        return [
            'game_version' => $instance['game_version'],
            'status' => $instance['status'],
            'loader' => $instance['loader'],
            'server_icon' => $instance['server_icon'],
            'role_data' => $instance['role_data'],
            'ignored' => $instance['ignored'],
            'whitelist' => $instance['whitelist'],
            'whitelist_roles' => $instance['whitelist_roles'],
        ];
    }

}
