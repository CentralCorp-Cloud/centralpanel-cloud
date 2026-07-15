<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSecurityController extends Controller
{
    public function show()
    {
        $securityOptions = PanelOptions::security();

        return view('admin.security', compact('securityOptions'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'maintenance' => 'boolean',
            'whitelist' => 'boolean',
            'maintenance_message' => 'required|string|max:255',
        ]);

        PanelOptions::security()->update($validated);
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);

        return redirect()->route('admin.security')->with('success', __('messages.flash.security_updated'));
    }
}
