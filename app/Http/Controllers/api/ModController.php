<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\OptionsMods;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ModController extends Controller
{
    public function getMods(): JsonResponse
    {
        $defaultInstanceId = Instance::query()->where('is_default', true)->value('id')
            ?? Instance::query()->value('id');
        $cacheVersion = Cache::get('launcher_options_version', 1);
        $output = Cache::remember('launcher_optional_mods:' . $cacheVersion . ':' . ($defaultInstanceId ?? 'none'), now()->addMinutes(5), function () use ($defaultInstanceId) {
            $modsData = [];
            $optionalMods = [];

            foreach (OptionsMods::query()->where('instance_id', $defaultInstanceId)->orderBy('name')->get() as $mod) {
                $modsFile = basename($mod->file);
                $modsData[$modsFile] = [
                    'name' => $mod->name,
                    'description' => $mod->description,
                    'icon' => $mod->icon ? asset('storage/' . $mod->icon) : '',
                    'recommanded' => (bool) $mod->recommended,
                ];

                if ($mod->optional) {
                    $optionalMods[] = $modsFile;
                }
            }

            return [
                'optionalMods' => $optionalMods,
                'mods' => $modsData,
            ];
        });

        return response()->json($output, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
