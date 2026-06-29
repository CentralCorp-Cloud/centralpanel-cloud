<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'mods_enabled' => 'boolean',
            'file_verification' => 'boolean',
            'embedded_java' => 'boolean',
            'game_folder_name' => 'required|string|max:100',
            'email_verified' => 'boolean',
            'role_display' => 'nullable|integer',
            'money_display' => 'nullable|integer',
            'min_ram' => 'required|integer|min:512|max:65536',
            'max_ram' => 'required|integer|min:512|max:65536|gte:min_ram',
        ]);

        PanelOptions::general()->update($validated);

        return redirect()->route('admin.general')->with('success', __('messages.flash.options_updated'));
    }

    public function general()
    {
        $options = PanelOptions::general();

        return view('admin.general', compact('options'));
    }
}
