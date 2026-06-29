<?php

namespace App\Http\Controllers;

use App\Models\OptionsMods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminModController extends Controller
{
    public function index(Request $request)
    {
        $modsDir = storage_path('app/public/data/mods');
        $jarFiles = is_dir($modsDir) ? glob($modsDir . '/*.jar') : [];
        $modsData = [];

        foreach ($jarFiles ?: [] as $jarFile) {
            $modsData[] = [
                'file' => basename($jarFile),
                'name' => basename($jarFile),
                'description' => '',
                'icon' => '',
                'optional' => 0,
            ];
        }

        $optionalMods = OptionsMods::where('optional', true)->orderBy('name')->get();
        $selectedModId = $request->input('selectedMod');

        return view('admin.mods', compact('modsData', 'optionalMods', 'selectedModId'));
    }

    public function updateOptionalMod(Request $request)
    {
        $validated = $request->validate([
            'mod_id' => 'required|integer|exists:mods,id',
            'optional_name' => 'required|string|max:255',
            'optional_description' => 'nullable|string|max:2000',
            'optional_recommended' => 'nullable|boolean',
            'optional_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $mod = OptionsMods::findOrFail($validated['mod_id']);
        $mod->name = $validated['optional_name'];
        $mod->description = $validated['optional_description'] ?? '';
        $mod->recommended = $request->boolean('optional_recommended');

        if ($request->hasFile('optional_image')) {
            if ($mod->icon && Storage::disk('public')->exists($mod->icon)) {
                Storage::disk('public')->delete($mod->icon);
            }
            $mod->icon = $request->file('optional_image')->store('mod_icon', 'public');
        }

        $mod->save();
        Cache::forget('launcher_optional_mods');

        return redirect()->back()->with('success', __('messages.flash.mod_updated'));
    }

    public function deleteOptionalMod($id)
    {
        try {
            $mod = OptionsMods::findOrFail($id);
            if ($mod->icon && Storage::disk('public')->exists($mod->icon)) {
                Storage::disk('public')->delete($mod->icon);
            }
            $mod->delete();
            Cache::forget('launcher_optional_mods');

            return redirect()->back()->with('success', __('messages.flash.mod_deleted'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.flash.mod_delete_error') . ' ' . $e->getMessage());
        }
    }

    public function addOptionalMod(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        OptionsMods::updateOrCreate(
            ['file' => $validated['file']],
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'optional' => true,
            ]
        );

        Cache::forget('launcher_optional_mods');

        return redirect()->back()->with('success', __('messages.flash.mod_added'));
    }

    public function getOptionalModDetails($id)
    {
        $mod = OptionsMods::find($id);
        if (!$mod) {
            return response()->json(['error' => __('messages.flash.mod_not_found')], 404);
        }

        return response()->json($mod);
    }
}
