<?php

namespace App\Http\Controllers;

use App\Models\OptionsWhitelist;
use App\Models\OptionsWhitelistRole;
use App\Request\AzuriomApi;
use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminWhitelistController extends Controller
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

    public function index()
    {
        $users = OptionsWhitelist::orderBy('users')->get();
        $roles = OptionsWhitelistRole::orderBy('role')->get();
        $securityOptions = PanelOptions::security();
        $hasAzuriomApi = $this->azuriomApi !== null;

        return view('admin.whitelist', compact('users', 'roles', 'securityOptions', 'hasAzuriomApi'));
    }

    public function fetchUsers(Request $request)
    {
        if (!$this->azuriomApi) {
            return response()->json(['error' => __('messages.flash.azuriom_api_error')], 503);
        }

        $allUsers = Cache::remember('azuriom.users', now()->addMinutes(5), fn () => $this->azuriomApi->getUsers());
        $whitelistedUsers = OptionsWhitelist::pluck('users')->toArray();

        $results = collect($allUsers)
            ->filter(function ($user) use ($whitelistedUsers) {
                if (in_array($user['name'], $whitelistedUsers, true)) return false;
                if ($user['is_banned'] ?? false) return false;
                if (str_starts_with($user['name'], 'Deleted #')) return false;
                return true;
            })
            ->sortBy(fn ($user) => [($user['role']['is_admin'] ?? false) ? 0 : 1, strtolower($user['name'])])
            ->map(fn ($user) => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']['name'] ?? 'Unknown',
                'role_color' => $user['role']['color'] ?? '#666',
                'is_admin' => $user['role']['is_admin'] ?? false,
            ])
            ->values();

        return response()->json($results);
    }

    public function fetchRoles(Request $request)
    {
        if (!$this->azuriomApi) {
            return response()->json(['error' => __('messages.flash.azuriom_api_error')], 503);
        }

        $allRoles = Cache::remember('azuriom.roles', now()->addMinutes(5), fn () => $this->azuriomApi->getRoles());
        $whitelistedRoles = OptionsWhitelistRole::pluck('role')->toArray();

        $results = collect($allRoles)
            ->filter(fn ($role) => !in_array($role['name'], $whitelistedRoles, true))
            ->sortBy(fn ($role) => [($role['is_admin'] ?? false) ? 0 : 1, strtolower($role['name'])])
            ->map(fn ($role) => [
                'id' => $role['id'],
                'name' => $role['name'],
                'color' => $role['color'] ?? '#666',
                'power' => $role['power'] ?? 0,
                'is_admin' => $role['is_admin'] ?? false,
            ])
            ->values();

        return response()->json($results);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'whitelist' => 'required|boolean',
            'whitelist_users' => 'nullable|array',
            'whitelist_users.*' => 'string|max:255',
            'azuriom_roles' => 'nullable|array',
            'azuriom_roles.*' => 'string|max:255',
        ]);

        $securityOptions = PanelOptions::security();
        $securityOptions->whitelist = (bool) $validated['whitelist'];
        $securityOptions->save();

        foreach ($validated['whitelist_users'] ?? [] as $username) {
            $username = trim($username);
            if ($username !== '') {
                OptionsWhitelist::firstOrCreate(['users' => $username]);
            }
        }

        foreach ($validated['azuriom_roles'] ?? [] as $role) {
            $role = trim($role);
            if ($role !== '') {
                OptionsWhitelistRole::firstOrCreate(['role' => $role]);
            }
        }

        return redirect()->route('admin.whitelist')->with('success', __('messages.flash.whitelist_updated'));
    }

    public function destroyUser($id)
    {
        OptionsWhitelist::findOrFail($id)->delete();

        return redirect()->route('admin.whitelist')->with('success', __('messages.flash.whitelist_user_deleted'));
    }

    public function destroyRole($id)
    {
        OptionsWhitelistRole::findOrFail($id)->delete();

        return redirect()->route('admin.whitelist')->with('success', __('messages.flash.whitelist_role_deleted'));
    }
}
