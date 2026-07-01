<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardReleaseNotesTest extends TestCase
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

        Cache::clear();
        Config::set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        File::put($path, 'installed');
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

    public function test_dashboard_release_notes_are_driven_by_latest_tags(): void
    {
        Http::fake([
            'https://api.github.com/repos/CentralCorp/centralpanel-v2/releases*' => Http::response([
                [
                    'tag_name' => 'v1.0.7',
                    'name' => 'v1.0.7',
                    'body' => 'Fix user edit password',
                    'published_at' => '2026-02-20T20:46:00Z',
                    'created_at' => '2026-02-20T20:46:00Z',
                    'draft' => false,
                    'html_url' => 'https://github.com/CentralCorp/centralpanel-v2/releases/tag/v1.0.7',
                    'author' => ['login' => 'centralcorp'],
                ],
            ]),
            'https://api.github.com/repos/CentralCorp/centralpanel-v2/tags*' => Http::response([
                ['name' => 'v1.0.7'],
                ['name' => 'v1.1.6'],
                ['name' => 'v1.1.5'],
            ]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin');

        $response->assertOk()
            ->assertSeeInOrder(['Version v1.1.6', 'Version v1.1.5', 'Version v1.0.7'])
            ->assertSee('Fix user edit password');
    }
}
