<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function updateGeneral(Request $request)
    {
        $options = PanelOptions::general();
        $newsModes = ['rss', 'builtin'];
        if ($this->azuriomNewsAvailable($options)) {
            $newsModes[] = 'azuriom';
        }

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
            'news_mode' => ['required', Rule::in($newsModes)],
            'news_rss_url' => 'nullable|required_if:news_mode,rss|url|max:500',
        ]);

        $options->update($validated);
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);

        return redirect()->route('admin.general')->with('success', __('messages.flash.options_updated'));
    }

    public function general()
    {
        $options = PanelOptions::general();
        $azuriomNewsAvailable = $this->azuriomNewsAvailable($options);

        return view('admin.general', compact('options', 'azuriomNewsAvailable'));
    }

    private function azuriomNewsAvailable($options): bool
    {
        return $options->auth_mode === 'azuriom'
            && filled($options->azuriom_url)
            && filled($options->azuriom_api_key);
    }
}
