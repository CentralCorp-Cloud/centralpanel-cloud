<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PanelController extends Controller
{
    public function root()
    {
        $isInstalled = File::exists(storage_path('installed'));
        $hasRealKey = config('app.key') !== InstallController::TEMP_KEY;

        if (!$isInstalled || !$hasRealKey) {
            return redirect()->route('install.database');
        }

        if (Auth::check()) {
            return redirect()->route('admin.index');
        }

        return redirect()->route('login');
    }

    public function fileManager()
    {
        return view('admin.file-manager');
    }
}
