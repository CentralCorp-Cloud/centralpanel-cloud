<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class AdminConfigController extends Controller
{
    public function show()
    {
        $options = PanelOptions::general();

        return view('admin.config', compact('options'));
    }

    public function update(Request $request)
    {
        $rules = [
            'app_name' => 'required|string|max:255',
            'auth_mode' => 'required|in:azuriom,microsoft',
        ];

        if ($request->input('auth_mode') === 'azuriom') {
            $rules['azuriom_url'] = 'required|url|max:255';
            $rules['azuriom_api_key'] = 'required|string|min:32|max:255';
        } else {
            $rules['azuriom_url'] = 'nullable|url|max:255';
            $rules['azuriom_api_key'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        $options = PanelOptions::general();
        $optionValues = [
            'auth_mode' => $validated['auth_mode'],
            'azuriom_url' => $validated['azuriom_url'] ?? null,
            'azuriom_api_key' => $validated['azuriom_api_key'] ?? null,
        ];
        if ($validated['auth_mode'] === 'microsoft' && $options->news_mode === 'azuriom') {
            $optionValues['news_mode'] = 'builtin';
        }
        $options->fill($optionValues)->save();

        if ($validated['app_name'] !== config('app.name')) {
            $this->updateEnvValue('APP_NAME', '"' . str_replace('"', '\"', $validated['app_name']) . '"');
            Artisan::call('config:clear');
        }
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);

        return redirect()->route('admin.config')->with('success', __('messages.flash.config_updated'));
    }

    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);
        $line = "{$key}={$value}";

        $envContent = preg_match("/^{$key}=.*/m", $envContent)
            ? preg_replace("/^{$key}=.*/m", $line, $envContent)
            : rtrim($envContent) . PHP_EOL . $line . PHP_EOL;

        File::put($envPath, $envContent);
    }
}
