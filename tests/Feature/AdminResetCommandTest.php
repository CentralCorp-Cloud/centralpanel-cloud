<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_resets_the_oldest_admin_without_exposing_the_password_and_can_be_replayed(): void
    {
        $oldest = User::factory()->create(['is_admin' => true, 'created_at' => now()->subDay()]);
        $other = User::factory()->create(['is_admin' => true]);
        $password = 'Reset-password-123!';
        $file = $this->jsonFile(['email' => 'new-admin@example.com', 'password' => $password]);

        $this->runCommand($file, $password, 0);
        $this->runCommand($file, $password, 0);
        $this->assertSame('new-admin@example.com', $oldest->refresh()->email);
        $this->assertTrue(Hash::check($password, $oldest->password));
        $this->assertNotSame('new-admin@example.com', $other->refresh()->email);
        $this->assertSame(2, User::query()->where('is_admin', true)->count());
    }

    public function test_it_fails_when_no_admin_exists(): void
    {
        User::factory()->create(['is_admin' => false]);
        $file = $this->jsonFile([
            'email' => 'admin@example.com',
            'password' => 'Reset-password-123!',
        ]);

        $this->artisan('panel:admin-reset', ['--bootstrap-file' => $file, '--no-interaction' => true])
            ->expectsOutputToContain('Aucun administrateur')
            ->assertFailed();
        $this->assertSame(1, User::query()->count());
    }

    public function test_it_refuses_an_email_used_by_another_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'old@example.com']);
        User::factory()->create(['email' => 'used@example.com']);
        $file = $this->jsonFile([
            'email' => 'used@example.com',
            'password' => 'Reset-password-123!',
        ]);

        $this->artisan('panel:admin-reset', ['--bootstrap-file' => $file, '--no-interaction' => true])
            ->expectsOutputToContain('déjà utilisée')
            ->assertFailed();
        $this->assertSame('old@example.com', $admin->refresh()->email);
    }

    private function runCommand(string $file, string $password, int $exitCode): void
    {
        $this->artisan('panel:admin-reset', ['--bootstrap-file' => $file, '--no-interaction' => true])
            ->doesntExpectOutputToContain($password)
            ->assertExitCode($exitCode);
    }

    /** @param array<string, string> $payload */
    private function jsonFile(array $payload): string
    {
        $path = tempnam(sys_get_temp_dir(), 'centralpanel-admin-reset-');
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->paths[] = $path;

        return $path;
    }
}
