<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdminLoaderController extends Controller
{
    public function index()
    {
        $row = PanelOptions::loader();

        return view('admin.loader', compact('row'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'minecraft_version' => 'required|string|max:50',
            'loader_activation' => 'required|boolean',
            'loader_type' => 'required|string|in:forge,fabric,legacyfabric,neoForge,quilt',
            'loader_forge_version' => 'nullable|string|max:50',
            'loader_build_version' => 'nullable|string|max:50',
        ]);

        PanelOptions::loader()->update($validated);

        return redirect()->back()->with('success', __('messages.flash.loader_updated'));
    }

    public function getForgeBuilds(Request $request)
    {
        $validated = $request->validate([
            'mc_version' => 'required|string|max:50',
        ]);

        $mcVersion = $validated['mc_version'];
        $url = "https://files.minecraftforge.net/net/minecraftforge/forge/index_{$mcVersion}.html";

        $builds = Cache::remember("forge_builds:{$mcVersion}", now()->addHours(6), function () use ($url) {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                return [];
            }

            $dom = new \DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($response->body());
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//a[contains(@href, "maven.minecraftforge.net/net/minecraftforge/forge/")]');
            $builds = [];

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (preg_match('/forge\/([\d\.\-]+)\/forge-\1-/', $href, $matches)) {
                    $builds[] = $matches[1];
                }
            }

            return array_values(array_unique($builds));
        });

        return response()->json(['builds' => $builds]);
    }

    public function getFabricVersions()
    {
        $versions = Cache::remember('fabric_loader_versions', now()->addHours(6), function () {
            $response = Http::timeout(10)->get('https://meta.fabricmc.net/v2/versions/loader');

            return $response->successful() ? $response->json() : [];
        });

        return response()->json(['versions' => $versions]);
    }
}
