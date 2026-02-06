<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\OptionsBg;
use App\Request\AzuriomApi;

class AdminBgController extends Controller
{
    private $azuriomApi;

    public function __construct()
    {
        try {
            $this->azuriomApi = new AzuriomApi();
        } catch (\RuntimeException $e) {
            $this->azuriomApi = null;
        }
    }

    public function index()
    {
        $hasAzuriomApi = $this->azuriomApi !== null;
        $roles = $hasAzuriomApi ? $this->azuriomApi->getRoles() : [];
        $backgrounds = OptionsBg::all()->keyBy('role_id');

        return view('admin.bg', compact('roles', 'backgrounds', 'hasAzuriomApi'));
    }

    public function update(Request $request)
    {
        if (!$this->azuriomApi) {
            return back()->with('error', __('messages.flash.bg_api_error'));
        }

        $request->validate([
            'role_id' => 'required',
            'bg_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $role = collect($this->azuriomApi->getRoles())->firstWhere('id', $request->role_id);

        if (!$role) {
            return back()->with('error', __('messages.flash.role_not_found'));
        }

        $oldBg = OptionsBg::where('role_id', $request->role_id)->first();
        if ($oldBg) {
            Storage::disk('public')->delete($oldBg->image_path);
        }

        $path = $request->file('bg_image')->store('backgrounds', 'public');

        OptionsBg::updateOrCreate(
            ['role_id' => $request->role_id],
            [
                'image_path' => $path,
                'role_name' => $role['name']
            ]
        );

        return back()->with('success', __('messages.flash.bg_updated'));
    }

    public function destroy($role_id)
    {
        $background = OptionsBg::where('role_id', $role_id)->first();

        if ($background) {
            Storage::disk('public')->delete($background->image_path);
            $background->delete();
            return back()->with('success', __('messages.flash.bg_deleted'));
        }

        return back()->with('error', __('messages.flash.bg_not_found'));
    }
}
