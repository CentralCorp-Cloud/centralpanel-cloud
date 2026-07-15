<?php

namespace App\Http\Controllers;

use App\Models\OptionsServer;
use App\Request\AzuriomApi;
use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminServerController extends Controller
{
    private ?AzuriomApi $azuriomApi = null;

    public function __construct()
    {
        try {
            $this->azuriomApi = new AzuriomApi();
        } catch (\RuntimeException) {
            $this->azuriomApi = null;
        }
    }

    public function show()
    {
        $options = PanelOptions::general();
        $servers = OptionsServer::query()->orderByDesc('is_default')->orderBy('server_name')->get();

        $defaultServers = $servers->pluck('is_default', 'server_id')->toArray();
        $serversArray = $servers->map(fn (OptionsServer $server) => [
            'id' => $server->server_id,
            'name' => $server->server_name,
            'address' => $server->server_ip,
            'port' => $server->server_port,
            'type' => $server->type ?? 'minecraft',
            'icon' => $server->icon,
            'icon_local' => $server->icon_local,
            'icon_url' => $server->icon_url,
        ])->toArray();

        return view('admin.server', [
            'servers' => $serversArray,
            'options' => $options,
            'error' => null,
            'defaultServers' => $defaultServers,
        ]);
    }

    public function sync()
    {
        $options = PanelOptions::general();

        if (!$options->azuriom_url) {
            return redirect()->route('admin.server')->with('error', __('messages.server.config_error'));
        }

        try {
            $api = $this->azuriomApi ?: new AzuriomApi();
            $serversResponse = $api->getServers();
            if (!$serversResponse->successful()) {
                throw new \RuntimeException(__('messages.instances.errors.api_unreachable'));
            }

            $servers = $serversResponse->json();
            $syncedCount = 0;
            $hasDefaultServer = OptionsServer::where('is_default', true)->exists();

            DB::transaction(function () use ($servers, &$syncedCount, &$hasDefaultServer) {
                foreach ($servers as $server) {
                    $iconPath = $this->normalizeIconPath($server['icon'] ?? null);
                    $serverModel = OptionsServer::updateOrCreate(
                        ['server_id' => $server['id']],
                        [
                            'server_name' => $server['name'],
                            'server_ip' => $server['address'],
                            'server_port' => (string) $server['port'],
                            'icon' => $iconPath,
                            'type' => $server['type'] ?? 'minecraft',
                        ]
                    );

                    if (!$hasDefaultServer) {
                        $serverModel->forceFill(['is_default' => true])->save();
                        $hasDefaultServer = true;
                    }

                    $syncedCount++;
                }
            });

            return redirect()->route('admin.server')->with('success', __('messages.flash.server_sync_success', ['count' => $syncedCount]));
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.server')->with('error', __('messages.flash.server_sync_error') . ' ' . $e->getMessage());
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'server_name' => 'required|string|max:255',
            'server_ip' => 'required|string|max:255',
            'server_port' => 'required|string|max:10',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $serverOptions = OptionsServer::firstOrFail();

        if ($request->hasFile('icon')) {
            if ($serverOptions->icon) {
                Storage::disk('uploads')->delete($serverOptions->icon);
            }
            $serverOptions->icon = $request->file('icon')->store('server_icon', 'uploads');
        }

        $serverOptions->fill(collect($validated)->except('icon')->all())->save();

        return redirect()->route('admin.server')->with('success', __('messages.flash.server_updated'));
    }

    public function setDefaultServer(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|integer|exists:options_server,server_id',
        ]);

        $server = DB::transaction(function () use ($validated) {
            OptionsServer::where('is_default', true)->update(['is_default' => false]);
            $server = OptionsServer::where('server_id', $validated['server_id'])->firstOrFail();
            $server->forceFill(['is_default' => true])->save();

            return $server;
        });

        return redirect()->route('admin.server')->with('success', __('messages.flash.server_set_default', ['name' => $server->server_name]));
    }

    public function updateIcon(Request $request, $serverId)
    {
        $request->validate([
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $server = OptionsServer::where('server_id', $serverId)->first();
        if (!$server) {
            return redirect()->route('admin.server')->with('error', __('messages.flash.server_not_found'));
        }

        if ($server->icon_local) {
            Storage::disk('public')->delete($server->icon_local);
        }

        $server->icon_local = $request->file('icon')->store('server_icons', 'public');
        $server->save();

        return redirect()->route('admin.server')->with('success', __('messages.flash.server_icon_updated', ['name' => $server->server_name]));
    }

    public function deleteIcon($serverId)
    {
        $server = OptionsServer::where('server_id', $serverId)->first();
        if (!$server) {
            return redirect()->route('admin.server')->with('error', __('messages.flash.server_not_found'));
        }

        if ($server->icon_local) {
            Storage::disk('public')->delete($server->icon_local);
            $server->forceFill(['icon_local' => null])->save();
        }

        return redirect()->route('admin.server')->with('success', __('messages.flash.server_icon_deleted', ['name' => $server->server_name]));
    }

    private function normalizeIconPath(?string $iconPath): ?string
    {
        if (!$iconPath) {
            return null;
        }

        $iconPath = ltrim($iconPath, '/');

        return str_starts_with($iconPath, 'storage/') ? substr($iconPath, 8) : $iconPath;
    }
}
