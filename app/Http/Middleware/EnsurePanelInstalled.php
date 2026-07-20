<?php

namespace App\Http\Middleware;

use App\Support\PanelInstallation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request) || $this->isInstalled()) {
            return $next($request);
        }

        return redirect()->route('install.database');
    }

    private function isInstalled(): bool
    {
        return PanelInstallation::ensureInstalledState();
    }

    private function shouldBypass(Request $request): bool
    {
        return $request->is('install')
            || $request->is('install/*')
            || $request->is('up')
            || $request->expectsJson();
    }
}
