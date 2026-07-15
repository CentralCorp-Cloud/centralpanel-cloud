<?php

namespace Tests\Feature;

use App\Models\OptionsGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuthenticationModeTest extends TestCase
{
    use RefreshDatabase;

    private bool $installedFileExisted = false;
    private ?string $installedFileContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $path = storage_path('installed');
        $this->installedFileExisted = File::exists($path);
        $this->installedFileContent = $this->installedFileExisted ? File::get($path) : null;
        File::put($path, 'installed');
    }

    protected function tearDown(): void
    {
        $path = storage_path('installed');
        if ($this->installedFileExisted) {
            File::put($path, $this->installedFileContent ?? '');
        } else {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_configuration_page_exposes_both_authentication_modes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/config')
            ->assertOk()
            ->assertSee('name="auth_mode"', false)
            ->assertSee('value="azuriom"', false)
            ->assertSee('value="microsoft"', false);
    }

    public function test_admin_can_switch_to_microsoft_without_azuriom_credentials(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/config', [
            'app_name' => config('app.name'),
            'auth_mode' => 'microsoft',
        ])->assertRedirect(route('admin.config'));

        $options = OptionsGeneral::firstOrFail();
        $this->assertSame('microsoft', $options->auth_mode);
        $this->assertNull($options->azuriom_url);
        $this->assertNull($options->azuriom_api_key);

        $this->getJson('/utils/api?api_version=2')
            ->assertOk()
            ->assertJsonPath('auth_mode', 'microsoft');
    }

    public function test_azuriom_mode_requires_its_url_and_api_key(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->from('/admin/config')->post('/admin/config', [
            'app_name' => config('app.name'),
            'auth_mode' => 'azuriom',
        ])->assertRedirect('/admin/config')
            ->assertSessionHasErrors(['azuriom_url', 'azuriom_api_key']);

        $this->actingAs($admin)->post('/admin/config', [
            'app_name' => config('app.name'),
            'auth_mode' => 'azuriom',
            'azuriom_url' => 'https://azuriom.example.test',
            'azuriom_api_key' => str_repeat('a', 32),
        ])->assertRedirect(route('admin.config'));

        $this->assertDatabaseHas('options_general', [
            'auth_mode' => 'azuriom',
            'azuriom_url' => 'https://azuriom.example.test',
            'azuriom_api_key' => str_repeat('a', 32),
        ]);
    }
}
