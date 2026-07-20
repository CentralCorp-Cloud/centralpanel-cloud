<?php

namespace Tests\Feature;

use App\Http\Controllers\InstallController;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutoInstallCommandTest extends TestCase
{
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

        File::delete($installedPath);
    }

    protected function tearDown(): void
    {
        $installedPath = storage_path('installed');
        if ($this->installedFileExisted) {
            File::put($installedPath, $this->installedFileContent ?? '');
        } else {
            File::delete($installedPath);
        }

        $envPath = base_path('.env');
        if ($this->envFileExisted) {
            File::put($envPath, $this->envFileContent ?? '');
        } else {
            File::delete($envPath);
        }

        parent::tearDown();
    }

    public function test_it_installs_the_panel_and_creates_an_admin(): void
    {
        $this->artisan('auto:install', [
            '--pseudo' => 'CentralAdmin',
            '--mail' => 'admin@example.com',
            '--pass' => 'Strong-password-123!',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('CentralAdmin', $user->name);
        $this->assertTrue($user->isAdmin());
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Strong-password-123!', $user->password));
        $this->assertFileExists(storage_path('installed'));
        $this->assertStringNotContainsString(
            InstallController::TEMP_KEY,
            File::get(base_path('.env')),
        );
    }

    public function test_it_rejects_invalid_credentials_without_installing(): void
    {
        $this->artisan('auto:install', [
            '--pseudo' => 'Admin',
            '--mail' => 'not-an-email',
            '--pass' => 'short',
        ])->assertFailed();

        $this->assertFalse(File::exists(storage_path('installed')));
        $this->assertFalse(Schema::hasTable('users') && User::query()->exists());
    }

    public function test_it_does_not_overwrite_an_existing_installation(): void
    {
        $this->artisan('auto:install', [
            '--pseudo' => 'FirstAdmin',
            '--mail' => 'first@example.com',
            '--pass' => 'Strong-password-123!',
        ])->assertSuccessful();

        $this->artisan('auto:install', [
            '--pseudo' => 'SecondAdmin',
            '--mail' => 'second@example.com',
            '--pass' => 'Another-password-123!',
        ])
            ->expectsOutput('Le panel est déjà installé. Aucune donnée n’a été modifiée.')
            ->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $this->assertTrue(User::query()->where('email', 'first@example.com')->exists());
        $this->assertFalse(User::query()->where('email', 'second@example.com')->exists());
    }

    public function test_it_installs_from_a_strict_bootstrap_file_without_exposing_the_password(): void
    {
        $password = 'Bootstrap-password-123!';
        $path = storage_path('framework/testing/panel-bootstrap.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'name' => 'Bootstrap Admin',
            'email' => 'bootstrap@example.com',
            'password' => $password,
        ], JSON_THROW_ON_ERROR));

        try {
            $this->artisan('auto:install', ['--bootstrap-file' => $path, '--no-interaction' => true])
                ->doesntExpectOutputToContain($password)
                ->assertSuccessful();
            $this->assertTrue(User::query()->where('email', 'bootstrap@example.com')->exists());
        } finally {
            File::delete($path);
        }
    }

    public function test_it_rejects_unknown_bootstrap_fields_without_exposing_values(): void
    {
        $password = 'Unknown-field-password-123!';
        $path = storage_path('framework/testing/panel-bootstrap-invalid.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'is_admin' => true,
        ], JSON_THROW_ON_ERROR));

        try {
            $this->artisan('auto:install', ['--bootstrap-file' => $path, '--no-interaction' => true])
                ->doesntExpectOutputToContain($password)
                ->assertFailed();
            $this->assertFalse(Schema::hasTable('users') && User::query()->exists());
        } finally {
            File::delete($path);
        }
    }

    public function test_existing_user_without_marker_is_never_overwritten(): void
    {
        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        User::query()->create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'Existing-password-123!',
            'is_admin' => true,
        ]);
        File::delete(storage_path('installed'));

        $this->artisan('auto:install', [
            '--pseudo' => 'Replacement',
            '--mail' => 'replacement@example.com',
            '--pass' => 'Replacement-password-123!',
        ])->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $this->assertTrue(User::query()->where('email', 'existing@example.com')->exists());
    }
}
