<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instance;
use App\Models\OptionsMods;
use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use App\Models\OptionsIgnore;
use App\Models\OptionsBg;
use App\Models\OptionsGeneral;
use App\Support\YouTube;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminInstanceController extends Controller
{
    /**
     * List all instances
     */
    public function index()
    {
        $instances = Instance::orderBy('is_default', 'desc')->orderBy('display_name')->get();
        return view('admin.instances.index', compact('instances'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $authMode = OptionsGeneral::first()?->auth_mode ?? 'azuriom';
        return view('admin.instances.edit', ['instance' => null, 'authMode' => $authMode]);
    }

    /**
     * Store a new instance
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'name' => 'required|string|max:100|unique:instances,name|alpha_dash',
            'server_ip' => 'nullable|string',
            'server_port' => 'nullable|integer|between:1,65535',
            'server_name' => 'nullable|string',
            'minecraft_version' => 'nullable|string|max:50',
            'server_icon_url' => 'nullable|url|max:2048',
            'server_icon_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'background_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'loader_type' => ['nullable', Rule::in(['forge', 'neoforge', 'fabric', 'legacyfabric', 'quilt'])],
            'loader_build_version' => 'nullable|string|max:100',
            'loader_activation' => 'boolean',
            'rpc_details_override' => 'nullable|string|max:255',
        ]);

        $data = collect($validated)->only([
            'display_name',
            'description',
            'name',
            'server_ip',
            'server_port',
            'server_name',
            'server_icon_url',
            'minecraft_version',
            'loader_type',
            'loader_build_version',
            'rpc_details_override',
        ])->all();
        $data['loader_activation'] = $request->boolean('loader_activation');

        $newDataPath = storage_path('app/public/data/' . $data['name']);
        if (File::exists($newDataPath)) {
            return back()->withInput()->withErrors(['name' => __('messages.instances.errors.data_folder_exists')]);
        }

        // Server details are passed directly from the frontend (populated via API fetch)

        // Handle icon upload
        if ($request->hasFile('server_icon_file')) {
            $data['server_icon'] = $request->file('server_icon_file')->store('instance_icons', 'public');
        }

        // Handle background upload
        if ($request->hasFile('background_file')) {
            $data['background_default'] = $request->file('background_file')->store('instance_backgrounds', 'public');
        }

        $instance = Instance::create($data);

        // Create the instance data folder
        File::ensureDirectoryExists($this->instanceDataPath($instance));
        $this->touchLauncherCache(true);

        return redirect()->route('admin.instances.index')->with('success', __('messages.flash.instance_created'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $instance = Instance::with(['mods', 'whitelist', 'whitelistRoles', 'ignoredFolders', 'backgrounds'])->findOrFail($id);
        $authMode = OptionsGeneral::first()?->auth_mode ?? 'azuriom';
        return view('admin.instances.edit', compact('instance', 'authMode'));
    }

    /**
     * Update instance
     */
    public function update(Request $request, $id)
    {
        $instance = Instance::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'name' => 'required|string|max:100|alpha_dash|unique:instances,name,' . $id,
            'server_ip' => 'nullable|string',
            'server_port' => 'nullable|integer|between:1,65535',
            'server_name' => 'nullable|string',
            'minecraft_version' => 'nullable|string|max:50',
            'server_icon_url' => 'nullable|url|max:2048',
            'server_icon_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'background_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'loader_type' => ['nullable', Rule::in(['forge', 'neoforge', 'fabric', 'legacyfabric', 'quilt'])],
            'loader_build_version' => 'nullable|string|max:100',
            'loader_activation' => 'boolean',
            'rpc_details_override' => 'nullable|string|max:255',
        ]);

        $data = collect($validated)->only([
            'display_name',
            'description',
            'name',
            'server_ip',
            'server_port',
            'server_name',
            'server_icon_url',
            'minecraft_version',
            'loader_type',
            'loader_build_version',
            'rpc_details_override',
        ])->all();
        $data['loader_activation'] = $request->boolean('loader_activation');

        $oldDataPath = $this->instanceDataPath($instance);
        $newDataPath = storage_path('app/public/data/' . $data['name']);
        if ($oldDataPath !== $newDataPath && File::exists($newDataPath)) {
            return back()->withInput()->withErrors(['name' => __('messages.instances.errors.data_folder_exists')]);
        }

        // Server details are passed directly from the frontend (populated via API fetch)

        // Handle icon upload
        if ($request->hasFile('server_icon_file')) {
            if ($instance->server_icon && Storage::disk('public')->exists($instance->server_icon)) {
                Storage::disk('public')->delete($instance->server_icon);
            }
            $data['server_icon'] = $request->file('server_icon_file')->store('instance_icons', 'public');
        }

        // Handle background upload
        if ($request->hasFile('background_file')) {
            if ($instance->background_default && Storage::disk('public')->exists($instance->background_default)) {
                Storage::disk('public')->delete($instance->background_default);
            }
            $data['background_default'] = $request->file('background_file')->store('instance_backgrounds', 'public');
        }

        if ($oldDataPath !== $newDataPath && File::isDirectory($oldDataPath)) {
            File::moveDirectory($oldDataPath, $newDataPath);
        }

        $instance->update($data);
        File::ensureDirectoryExists($newDataPath);
        $this->touchLauncherCache(true);

        return redirect()->route('admin.instances.edit', $id)->with('success', __('messages.flash.instance_updated'));
    }

    /**
     * Delete instance
     */
    public function destroy($id)
    {
        $instance = Instance::findOrFail($id);

        if ($instance->is_default) {
            return redirect()->back()->with('error', __('messages.flash.cannot_delete_default_instance'));
        }

        // Clean up files
        if ($instance->server_icon && Storage::disk('public')->exists($instance->server_icon)) {
            Storage::disk('public')->delete($instance->server_icon);
        }
        if ($instance->background_default && Storage::disk('public')->exists($instance->background_default)) {
            Storage::disk('public')->delete($instance->background_default);
        }

        foreach ($instance->mods()->whereNotNull('icon')->pluck('icon') as $icon) {
            Storage::disk('public')->delete($icon);
        }
        foreach ($instance->backgrounds()->whereNotNull('image_path')->pluck('image_path') as $background) {
            Storage::disk('public')->delete($background);
        }

        File::deleteDirectory($this->instanceDataPath($instance));

        $instance->delete(); // cascade deletes mods, whitelist, etc.
        $this->touchLauncherCache(true);

        return redirect()->route('admin.instances.index')->with('success', __('messages.flash.instance_deleted'));
    }

    /**
     * Set instance as default
     */
    public function setDefault($id)
    {
        $instance = Instance::findOrFail($id);
        DB::transaction(function () use ($instance) {
            Instance::query()->update(['is_default' => false]);
            $instance->update(['is_default' => true]);
        });
        $this->touchLauncherCache();

        return redirect()->back()->with('success', __('messages.flash.default_instance_set'));
    }

    /**
     * Delete server icon
     */
    public function deleteIcon($id)
    {
        $instance = Instance::findOrFail($id);
        if ($instance->server_icon && Storage::disk('public')->exists($instance->server_icon)) {
            Storage::disk('public')->delete($instance->server_icon);
        }
        $instance->update(['server_icon' => null]);
        $this->touchLauncherCache();

        return redirect()->back()->with('success', __('messages.flash.icon_deleted'));
    }

    // ==========================================
    // Loader helper methods
    // ==========================================

    public function getForgeBuilds(Request $request)
    {
        $validated = $request->validate(['mc_version' => ['required', 'regex:/^[0-9A-Za-z._-]+$/', 'max:50']]);
        $mcVersion = $validated['mc_version'];
        $url = "https://files.minecraftforge.net/net/minecraftforge/forge/index_$mcVersion.html";
        $response = Http::timeout(10)->get($url);
        $builds = [];

        if ($response->successful()) {
            $html = $response->body();
            $dom = new \DOMDocument;

            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//a[contains(@href, "maven.minecraftforge.net/net/minecraftforge/forge/")]');

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (preg_match('/forge\/([\d\.\-]+)\/forge-\1-/', $href, $matches)) {
                    $version = $matches[1];
                    if (!in_array($version, $builds)) {
                        $builds[] = $version;
                    }
                }
            }
        }

        return response()->json(['builds' => $builds]);
    }

    public function getFabricVersions()
    {
        $url = 'https://meta.fabricmc.net/v2/versions/loader';
        $response = Http::timeout(10)->get($url);
        $versions = [];

        if ($response->successful()) {
            $versions = $response->json();
        }

        return response()->json(['versions' => $versions]);
    }

    /**
     * Fetch servers from Azuriom API (AJAX)
     */
    public function fetchServers()
    {
        try {
            $api = new \App\Request\AzuriomApi();
        } catch (\RuntimeException $e) {
            return response()->json(['error' => __('messages.instances.errors.api_not_configured')], 503);
        }

        $response = $api->getServers();
        if (!$response->successful()) {
            return response()->json(['error' => __('messages.instances.errors.api_unreachable')], 502);
        }

        $azuriomUrl = OptionsGeneral::value('azuriom_url');
        $servers = collect($response->json())->map(function ($srv) use ($azuriomUrl) {
            $icon = $srv['icon'] ?? null;
            if ($icon && !filter_var($icon, FILTER_VALIDATE_URL) && $azuriomUrl) {
                $icon = rtrim($azuriomUrl, '/') . '/storage/' . ltrim(str_replace('storage/', '', $icon), '/');
            }

            return [
                'id' => $srv['id'] ?? null,
                'name' => $srv['name'] ?? 'Unknown',
                'ip' => $srv['address'] ?? $srv['ip'] ?? '',
                'port' => (string) ($srv['port'] ?? '25565'),
                'icon' => $icon,
                'type' => $srv['type'] ?? '',
            ];
        })->values();

        return response()->json($servers);
    }

    // ==========================================
    // Instance-scoped sub-resources
    // ==========================================

    /**
     * Whitelist management for an instance
     */
    public function whitelistIndex($instanceId)
    {
        $instance = Instance::findOrFail($instanceId);
        $users = OptionsWhitelist::where('instance_id', $instanceId)->get();
        $roles = OptionsWhitelistRole::where('instance_id', $instanceId)->get();
        $authMode = OptionsGeneral::first()?->auth_mode ?? 'azuriom';
        $hasAzuriomApi = false;

        if ($authMode === 'azuriom') {
            try {
                $api = new \App\Request\AzuriomApi();
                $hasAzuriomApi = true;
            } catch (\RuntimeException $e) {
                $hasAzuriomApi = false;
            }
        }

        return view('admin.instances.whitelist', compact('instance', 'users', 'roles', 'authMode', 'hasAzuriomApi'));
    }

    /**
     * Fetch users from Azuriom API (AJAX) for whitelist
     */
    public function whitelistFetchUsers($instanceId)
    {
        Instance::findOrFail($instanceId);
        try {
            $api = new \App\Request\AzuriomApi();
        } catch (\RuntimeException $e) {
            return response()->json(['error' => __('messages.instances.errors.api_not_configured')], 503);
        }

        $allUsers = $api->getUsers();
        $whitelistedUsers = OptionsWhitelist::where('instance_id', $instanceId)->pluck('users')->toArray();

        $results = collect($allUsers)
            ->filter(function ($user) use ($whitelistedUsers) {
                if (in_array($user['name'], $whitelistedUsers))
                    return false;
                if ($user['is_banned'] ?? false)
                    return false;
                if (str_starts_with($user['name'], 'Deleted #'))
                    return false;
                return true;
            })
            ->sortBy(function ($user) {
                return [($user['role']['is_admin'] ?? false) ? 0 : 1, strtolower($user['name'])];
            })
            ->map(function ($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']['name'] ?? 'Unknown',
                    'role_color' => $user['role']['color'] ?? '#666',
                    'is_admin' => $user['role']['is_admin'] ?? false,
                ];
            })
            ->values();

        return response()->json($results);
    }

    /**
     * Fetch roles from Azuriom API (AJAX) for whitelist
     */
    public function whitelistFetchRoles($instanceId)
    {
        Instance::findOrFail($instanceId);
        try {
            $api = new \App\Request\AzuriomApi();
        } catch (\RuntimeException $e) {
            return response()->json(['error' => __('messages.instances.errors.api_not_configured')], 503);
        }

        $allRoles = $api->getRoles();
        $whitelistedRoles = OptionsWhitelistRole::where('instance_id', $instanceId)->pluck('role')->toArray();

        $results = collect($allRoles)
            ->filter(function ($role) use ($whitelistedRoles) {
                return !in_array($role['name'], $whitelistedRoles);
            })
            ->sortBy(function ($role) {
                return [($role['is_admin'] ?? false) ? 0 : 1, strtolower($role['name'])];
            })
            ->map(function ($role) {
                return [
                    'id' => $role['id'],
                    'name' => $role['name'],
                    'color' => $role['color'] ?? '#666',
                    'power' => $role['power'] ?? 0,
                    'is_admin' => $role['is_admin'] ?? false,
                ];
            })
            ->values();

        return response()->json($results);
    }

    public function whitelistStore(Request $request, $instanceId)
    {
        Instance::findOrFail($instanceId);
        $validated = $request->validate([
            'username' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'whitelist_users' => 'nullable|array|max:500',
            'whitelist_users.*' => 'string|max:255',
            'azuriom_roles' => 'nullable|array|max:100',
            'azuriom_roles.*' => 'string|max:255',
        ]);

        // Single username (Microsoft mode manual input)
        if ($request->filled('username')) {
            $existing = OptionsWhitelist::where('instance_id', $instanceId)
                ->where('users', $request->username)->first();
            if (!$existing) {
                OptionsWhitelist::create([
                    'users' => $request->username,
                    'instance_id' => $instanceId,
                ]);
            }
        }

        // Bulk users from Azuriom API checkboxes
        if ($validated['whitelist_users'] ?? null) {
            foreach ($validated['whitelist_users'] as $username) {
                if (trim($username) !== '') {
                    OptionsWhitelist::firstOrCreate([
                        'users' => trim($username),
                        'instance_id' => $instanceId,
                    ]);
                }
            }
        }

        // Single role (legacy)
        if ($request->filled('role')) {
            $existing = OptionsWhitelistRole::where('instance_id', $instanceId)
                ->where('role', $request->role)->first();
            if (!$existing) {
                OptionsWhitelistRole::create([
                    'role' => $request->role,
                    'instance_id' => $instanceId,
                ]);
            }
        }

        // Bulk roles from Azuriom API checkboxes
        if ($validated['azuriom_roles'] ?? null) {
            foreach ($validated['azuriom_roles'] as $role) {
                if (trim($role) !== '') {
                    OptionsWhitelistRole::firstOrCreate([
                        'role' => trim($role),
                        'instance_id' => $instanceId,
                    ]);
                }
            }
        }

        $this->touchLauncherCache();
        return redirect()->back()->with('success', __('messages.flash.whitelist_updated'));
    }

    public function whitelistDestroyUser($instanceId, $id)
    {
        Instance::findOrFail($instanceId);
        OptionsWhitelist::where('instance_id', $instanceId)->where('id', $id)->delete();
        $this->touchLauncherCache();
        return redirect()->back()->with('success', __('messages.flash.whitelist_user_deleted'));
    }

    public function whitelistDestroyRole($instanceId, $id)
    {
        Instance::findOrFail($instanceId);
        OptionsWhitelistRole::where('instance_id', $instanceId)->where('id', $id)->delete();
        $this->touchLauncherCache();
        return redirect()->back()->with('success', __('messages.flash.whitelist_role_deleted'));
    }

    /**
     * Ignored folders management for an instance
     */
    public function ignoreIndex($instanceId)
    {
        $instance = Instance::findOrFail($instanceId);
        $folders = OptionsIgnore::where('instance_id', $instanceId)->get();
        return view('admin.instances.ignore', compact('instance', 'folders'));
    }

    public function ignoreStore(Request $request, $instanceId)
    {
        Instance::findOrFail($instanceId);
        $validated = $request->validate([
            'folder_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\\/-]+$/', 'not_regex:/(^|\\/)\.\.(\\/|$)/'],
        ]);
        $folderName = trim(str_replace('\\', '/', $validated['folder_name']), '/');
        $existing = OptionsIgnore::where('instance_id', $instanceId)
            ->where('folder_name', $folderName)->first();
        if (!$existing) {
            OptionsIgnore::create([
                'folder_name' => $folderName,
                'instance_id' => $instanceId,
            ]);
        }
        $this->touchLauncherCache(true);
        return redirect()->back()->with('success', __('messages.flash.ignored_folder_added'));
    }

    public function ignoreDestroy($instanceId, $id)
    {
        Instance::findOrFail($instanceId);
        OptionsIgnore::where('instance_id', $instanceId)->where('id', $id)->delete();
        $this->touchLauncherCache(true);
        return redirect()->back()->with('success', __('messages.flash.ignored_folder_deleted'));
    }

    /**
     * Mods management for an instance
     */
    public function modsIndex(Request $request, $instanceId)
    {
        $instance = Instance::findOrFail($instanceId);
        $modsDir = storage_path('app/public/data/' . $instance->name . '/mods');

        $jarFiles = [];
        if (is_dir($modsDir)) {
            $jarFiles = glob($modsDir . '/*.jar') ?: [];
        }

        $modsData = [];
        foreach ($jarFiles as $jarFile) {
            $modsData[] = [
                'file' => basename($jarFile),
                'name' => basename($jarFile),
                'description' => '',
                'icon' => '',
                'optional' => 0,
            ];
        }

        $optionalMods = OptionsMods::where('instance_id', $instanceId)->where('optional', 1)->get();
        $selectedModId = $request->input('selectedMod', null);

        return view('admin.instances.mods', compact('instance', 'modsData', 'optionalMods', 'selectedModId'));
    }

    public function modsAdd(Request $request, $instanceId)
    {
        $instance = Instance::findOrFail($instanceId);
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:255', 'regex:/^[^\\/]+\.jar$/i'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'recommended' => 'nullable|boolean',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        $modPath = $this->instanceDataPath($instance) . DIRECTORY_SEPARATOR . 'mods' . DIRECTORY_SEPARATOR . $validated['file'];
        abort_unless(File::isFile($modPath), 422, __('messages.instances.errors.mod_file_missing'));

        $mod = new OptionsMods();
        $mod->file = $validated['file'];
        $mod->name = $validated['name'];
        $mod->description = $validated['description'] ?? '';
        $mod->optional = 1;
        $mod->recommended = $request->has('recommended') ? 1 : 0;
        $mod->instance_id = $instanceId;

        if ($request->hasFile('icon_file')) {
            $mod->icon = $request->file('icon_file')->store('mod_icons', 'public');
        }

        $mod->save();
        $this->touchLauncherCache();

        return redirect()->back()->with('success', __('messages.flash.mod_added'));
    }

    public function modsUpdate(Request $request, $instanceId, $modId)
    {
        Instance::findOrFail($instanceId);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'recommended' => 'nullable|boolean',
            'icon_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        $mod = OptionsMods::where('instance_id', $instanceId)->findOrFail($modId);

        $mod->name = $validated['name'];
        $mod->description = $validated['description'] ?? '';
        $mod->recommended = $request->has('recommended') ? 1 : 0;

        if ($request->hasFile('icon_file')) {
            // Delete old icon
            if ($mod->icon && Storage::disk('public')->exists($mod->icon)) {
                Storage::disk('public')->delete($mod->icon);
            }
            $mod->icon = $request->file('icon_file')->store('mod_icons', 'public');
        }

        $mod->save();
        $this->touchLauncherCache();

        return redirect()->back()->with('success', __('messages.flash.mod_updated'));
    }


    public function modsDelete($instanceId, $id)
    {
        Instance::findOrFail($instanceId);
        $mod = OptionsMods::where('instance_id', $instanceId)->findOrFail($id);
        if ($mod->icon) {
            Storage::disk('public')->delete($mod->icon);
        }
        $mod->delete();
        $this->touchLauncherCache();
        return redirect()->back()->with('success', __('messages.flash.mod_deleted'));
    }

    /**
     * Role backgrounds for an instance
     */
    public function bgIndex($instanceId)
    {
        $instance = Instance::findOrFail($instanceId);
        $roles = OptionsBg::where('instance_id', $instanceId)->get();
        $authMode = OptionsGeneral::first()?->auth_mode ?? 'azuriom';
        $hasAzuriomApi = false;
        if ($authMode === 'azuriom') {
            try {
                new \App\Request\AzuriomApi();
                $hasAzuriomApi = true;
            } catch (\RuntimeException $e) {
                $hasAzuriomApi = false;
            }
        }
        return view('admin.instances.bg', compact('instance', 'roles', 'authMode', 'hasAzuriomApi'));
    }

    /**
     * Fetch roles from Azuriom API for background assignment (AJAX)
     */
    public function bgFetchRoles($instanceId)
    {
        Instance::findOrFail($instanceId);
        try {
            $api = new \App\Request\AzuriomApi();
        } catch (\RuntimeException $e) {
            return response()->json(['error' => __('messages.instances.errors.api_not_configured')], 503);
        }

        $allRoles = $api->getRoles();
        $existingRoles = OptionsBg::where('instance_id', $instanceId)->pluck('role_name')->toArray();

        $results = collect($allRoles)
            ->filter(function ($role) use ($existingRoles) {
                return !in_array($role['name'], $existingRoles);
            })
            ->sortBy(function ($role) {
                return [($role['is_admin'] ?? false) ? 0 : 1, strtolower($role['name'])];
            })
            ->map(function ($role) {
                return [
                    'id' => $role['id'],
                    'name' => $role['name'],
                    'color' => $role['color'] ?? '#666',
                    'power' => $role['power'] ?? 0,
                    'is_admin' => $role['is_admin'] ?? false,
                ];
            })
            ->values();

        return response()->json($results);
    }

    public function bgUpdate(Request $request, $instanceId)
    {
        Instance::findOrFail($instanceId);
        $validated = $request->validate([
            'role_name' => 'nullable|string|max:255',
            'role_id' => 'nullable|string|max:255',
            'role_background' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'role_video_url' => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value && YouTube::videoId((string) $value) === null) {
                        $fail(__('messages.instances.errors.invalid_youtube_url'));
                    }
                },
            ],
            'azuriom_bg_roles' => 'nullable|array|max:100',
            'azuriom_bg_roles.*' => 'string|max:255',
            'azuriom_bg_role_ids' => 'nullable|array|max:100',
            'azuriom_bg_role_ids.*' => 'string|max:255',
        ]);

        if ($request->hasFile('role_background') && !empty($validated['role_video_url'])) {
            return back()->withInput()->withErrors([
                'role_background' => __('messages.instances.errors.choose_one_media'),
            ]);
        }

        // Single role name (manual or from Azuriom selection)
        if (!empty($validated['role_name'])) {
            $bg = OptionsBg::where('instance_id', $instanceId)
                ->where('role_name', $validated['role_name'])->first();

            if (!$bg) {
                if (!$request->hasFile('role_background') && empty($validated['role_video_url'])) {
                    return back()->withInput()->withErrors([
                        'role_background' => __('messages.instances.errors.media_required'),
                    ]);
                }
                $bg = new OptionsBg();
                $bg->instance_id = $instanceId;
                $bg->role_name = $validated['role_name'];
                $bg->role_id = $validated['role_id'] ?? $validated['role_name'];
                $bg->image_path = '';
            }

            if ($request->hasFile('role_background')) {
                if ($bg->image_path && Storage::disk('public')->exists($bg->image_path)) {
                    Storage::disk('public')->delete($bg->image_path);
                }
                $bg->image_path = $request->file('role_background')->store('role_backgrounds', 'public');
                $bg->video_url = null;
            } elseif (!empty($validated['role_video_url'])) {
                if ($bg->image_path && Storage::disk('public')->exists($bg->image_path)) {
                    Storage::disk('public')->delete($bg->image_path);
                }
                $bg->image_path = '';
                $bg->video_url = $validated['role_video_url'];
            }

            $bg->save();
        }

        // Bulk roles from Azuriom API checkboxes (creates entries without image, to be uploaded later)
        if ($validated['azuriom_bg_roles'] ?? null) {
            $roleIds = $validated['azuriom_bg_role_ids'] ?? [];
            foreach ($validated['azuriom_bg_roles'] as $index => $roleName) {
                if (trim($roleName) !== '') {
                    OptionsBg::firstOrCreate([
                        'role_name' => trim($roleName),
                        'instance_id' => $instanceId,
                    ], [
                        'role_id' => $roleIds[$index] ?? trim($roleName),
                        'image_path' => '',
                        'video_url' => null,
                    ]);
                }
            }
        }

        $this->touchLauncherCache();
        return redirect()->back()->with('success', __('messages.flash.bg_updated'));
    }

    public function bgDestroy($instanceId, $roleId)
    {
        Instance::findOrFail($instanceId);
        $bg = OptionsBg::where('instance_id', $instanceId)->where('id', $roleId)->first();
        if ($bg) {
            if ($bg->image_path && Storage::disk('public')->exists($bg->image_path)) {
                Storage::disk('public')->delete($bg->image_path);
            }
            $bg->delete();
            $this->touchLauncherCache();
        }
        return redirect()->back()->with('success', __('messages.flash.bg_deleted'));
    }

    /**
     * Per-instance file manager
     */
    public function fileManager($instanceId)
    {
        $instance = Instance::findOrFail($instanceId);

        // Store instance name in session so the FileManagerInstanceScope middleware
        // can dynamically register the scoped disk for all file-manager AJAX requests
        session(['file_manager_instance' => $instance->name]);

        // Ensure the directory exists
        $instancePath = storage_path('app/public/data/' . $instance->name);
        File::ensureDirectoryExists($instancePath);

        return view('admin.instances.file-manager', compact('instance'));
    }

    private function instanceDataPath(Instance $instance): string
    {
        return storage_path('app/public/data/' . $instance->name);
    }

    private function touchLauncherCache(bool $files = false): void
    {
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);
        if ($files) {
            Cache::forever('launcher_files_version', (int) Cache::get('launcher_files_version', 1) + 1);
        }
    }
}
