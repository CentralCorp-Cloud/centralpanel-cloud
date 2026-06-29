<?php

namespace App\Http\Controllers;

use App\Models\OptionsIgnore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminIgnoreController extends Controller
{
    public function index()
    {
        $folders = OptionsIgnore::orderBy('folder_name')->get();

        return view('admin.ignore', compact('folders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ignored_folders' => 'nullable|string|max:2000',
        ]);

        $folders = collect(explode(',', $validated['ignored_folders'] ?? ''))
            ->map(fn ($folder) => trim($folder))
            ->filter()
            ->unique();

        foreach ($folders as $folder) {
            OptionsIgnore::firstOrCreate(['folder_name' => $folder]);
        }

        $this->bumpFileManifestVersion();

        return redirect()->route('admin.ignore')->with('success', __('messages.flash.ignore_updated'));
    }

    public function destroyFolder($id)
    {
        OptionsIgnore::findOrFail($id)->delete();
        $this->bumpFileManifestVersion();

        return redirect()->route('admin.ignore')->with('success', __('messages.flash.ignore_deleted'));
    }

    private function bumpFileManifestVersion(): void
    {
        Cache::forever('launcher_files_version', ((int) Cache::get('launcher_files_version', 1)) + 1);
    }
}
