<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallRedirectTest extends TestCase
{
    private bool $installedFileExisted = false;
    private ?string $installedFileContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $path = storage_path('installed');
        $this->installedFileExisted = File::exists($path);
        $this->installedFileContent = $this->installedFileExisted ? File::get($path) : null;
    }

    protected function tearDown(): void
    {
        $path = storage_path('installed');

        if ($this->installedFileExisted) {
            File::put($path, $this->installedFileContent ?? '');
        } elseif (File::exists($path)) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_login_redirects_to_install_when_panel_is_not_installed(): void
    {
        $this->markPanelAsNotInstalled();

        $this->get('/login')
            ->assertRedirect(route('install.database'));
    }

    public function test_admin_redirects_to_install_before_auth_when_panel_is_not_installed(): void
    {
        $this->markPanelAsNotInstalled();

        $this->get('/admin')
            ->assertRedirect(route('install.database'));
    }

    public function test_install_page_stays_accessible_when_panel_is_not_installed(): void
    {
        $this->markPanelAsNotInstalled();

        $this->get('/install')
            ->assertOk();
    }

    private function markPanelAsNotInstalled(): void
    {
        File::delete(storage_path('installed'));
        Config::set('app.key', InstallController::TEMP_KEY);
    }
}
