<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use App\Support\YouTube;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminUIController extends Controller
{
    public function show()
    {
        $uiOptions = PanelOptions::ui();

        return view('admin.ui', compact('uiOptions'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'alert_activation' => 'boolean',
            'alert_scroll' => 'boolean',
            'alert_msg' => 'required|string|max:255',
            'video_activation' => 'boolean',
            'video_url' => [
                'required',
                'url',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (YouTube::videoId((string) $value) === null) {
                        $fail(__('messages.instances.errors.invalid_youtube_url'));
                    }
                },
            ],
            'splash' => 'required|string|max:255',
            'splash_author' => 'required|string|max:255',
            'accent_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        PanelOptions::ui()->update($validated);
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);

        return redirect()->route('admin.ui')->with('success', __('messages.flash.ui_updated'));
    }
}
