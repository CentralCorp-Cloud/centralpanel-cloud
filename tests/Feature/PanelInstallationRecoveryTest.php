<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use App\Models\User;
use App\Support\PanelInstallation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PanelInstallationRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private bool $installedFileExisted = false;
    private ?string $installedFileContent = null;
    private bool $envFileExisted = false;
    private ?string $envFileContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $installedPath = storage_path('installed');
        $this->installedFileExisted = File::exists($installedPath);
        $this->installedFileContent = $this->installedFileExisted ? File::get($installedPath) : null;

        $envPath = base_path('.env');
        $this->envFileExisted = File::exists($envPath);
        $this->envFileContent = $this->envFileExisted ? File::get($envPath) : null;
    }

    protected function tearDown(): void
    {
        $installedPath = storage_path('installed');
        if ($this->installedFileExisted) {
            File::put($installedPath, $this->installedFileContent ?? '');
        } elseif (File::exists($installedPath)) {
            File::delete($installedPath);
        }

        $envPath = base_path('.env');
        if ($this->envFileExisted) {
            File::put($envPath, $this->envFileContent ?? '');
        } elseif (File::exists($envPath)) {
            File::delete($envPath);
        }

        parent::tearDown();
    }

    public function test_missing_installed_marker_is_recovered_from_existing_users(): void
    {
        User::factory()->create();
        File::delete(storage_path('installed'));
        Config::set('app.key', 'base64:' . base64_encode(str_repeat('b', 32)));

        $this->get('/login')->assertOk();

        $this->assertFileExists(storage_path('installed'));
    }

    public function test_temporary_app_key_is_repaired_when_database_is_installed(): void
    {
        User::factory()->create();
        File::delete(storage_path('installed'));
        File::put(base_path('.env'), 'APP_KEY=' . InstallController::TEMP_KEY . PHP_EOL);
        Config::set('app.key', InstallController::TEMP_KEY);

        $this->assertTrue(PanelInstallation::ensureInstalledState());
        $this->assertFileExists(storage_path('installed'));
        $this->assertNotSame(InstallController::TEMP_KEY, config('app.key'));
        $this->assertStringNotContainsString(InstallController::TEMP_KEY, File::get(base_path('.env')));
    }
}
