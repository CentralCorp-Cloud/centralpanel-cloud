<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ManagedInstallationTest extends TestCase
{
    use RefreshDatabase;

    private string $bootstrapFile;

    protected function setUp(): void
    {
        parent::setUp();

        File::delete(storage_path('installed'));
        $this->bootstrapFile = storage_path('framework/testing/panel-bootstrap.json');
        File::ensureDirectoryExists(dirname($this->bootstrapFile));
        File::put($this->bootstrapFile, json_encode([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'a-long-random-password',
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        File::delete($this->bootstrapFile);
        File::delete(storage_path('installed'));
        parent::tearDown();
    }

    public function test_managed_install_creates_admin_and_is_idempotent(): void
    {
        $this->artisan('panel:install', ['--bootstrap-file' => $this->bootstrapFile, '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'owner@example.test', 'is_admin' => true]);
        $this->assertFileExists(storage_path('installed'));

        $this->artisan('panel:install', ['--bootstrap-file' => $this->bootstrapFile, '--no-interaction' => true])
            ->assertSuccessful();
        $this->assertSame(1, User::query()->count());
    }

    public function test_admin_password_can_be_rotated_from_file(): void
    {
        User::factory()->create(['email' => 'owner@example.test', 'is_admin' => true]);

        $this->artisan('panel:admin-reset', ['--bootstrap-file' => $this->bootstrapFile, '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertTrue(password_verify('a-long-random-password', User::firstOrFail()->password));
    }

    public function test_health_is_unavailable_before_installation(): void
    {
        $this->getJson('/healthz')->assertStatus(503)->assertJson(['status' => 'installing']);
    }
}
