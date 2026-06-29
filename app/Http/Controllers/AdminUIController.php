<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;

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
            'video_url' => 'required|url|max:255',
            'splash' => 'required|string|max:255',
            'splash_author' => 'required|string|max:255',
            'accent_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        PanelOptions::ui()->update($validated);

        return redirect()->route('admin.ui')->with('success', __('messages.flash.ui_updated'));
    }
}
