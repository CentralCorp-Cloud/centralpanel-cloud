<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;

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
            'maintenance_message' => 'required|string|max:255',
        ]);

        PanelOptions::security()->update($validated);

        return redirect()->route('admin.security')->with('success', __('messages.flash.security_updated'));
    }
}
