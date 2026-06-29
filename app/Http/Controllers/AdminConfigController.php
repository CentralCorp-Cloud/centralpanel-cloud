<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'azuriom_url' => 'required|url|max:255',
            'azuriom_api_key' => 'required|string|max:255',
        ]);

        $options = PanelOptions::general();
        $options->fill([
            'azuriom_url' => $validated['azuriom_url'],
            'azuriom_api_key' => $validated['azuriom_api_key'],
        ])->save();

        if ($validated['app_name'] !== config('app.name')) {
            $this->updateEnvValue('APP_NAME', '"' . str_replace('"', '\"', $validated['app_name']) . '"');
            Artisan::call('config:clear');
        }

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
