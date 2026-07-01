<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalled() || $this->shouldBypass($request)) {
            return $next($request);
        }

        return redirect()->route('install.database');
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'))
            && config('app.key') !== InstallController::TEMP_KEY;
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('install')
            || $request->is('install/*')
            || $request->is('up')
            || $request->expectsJson();
    }
}
