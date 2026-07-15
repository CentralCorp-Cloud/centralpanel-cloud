<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use App\Support\PanelOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsSystemTest extends TestCase
{
    use RefreshDatabase;

    private bool $installedFileExisted = false;
    private ?string $installedFileContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
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

    public function test_admin_can_publish_and_delete_built_in_news(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        PanelOptions::general()->update(['news_mode' => 'builtin']);

        $this->actingAs($admin)->post('/admin/news', [
            'title' => 'Mise à jour du serveur',
            'content' => '<p>Le nouveau serveur est disponible.</p>',
            'author' => 'CentralCorp',
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'image' => UploadedFile::fake()->image('news.png', 800, 450),
        ])->assertRedirect(route('admin.news.index'));

        $article = News::firstOrFail();
        Storage::disk('public')->assertExists($article->image);

        $this->getJson('/utils/api?api_version=2')
            ->assertOk()
            ->assertJsonPath('news_mode', 'builtin')
            ->assertJsonPath('news.0.title', 'Mise à jour du serveur')
            ->assertJsonPath('news.0.author', 'CentralCorp')
            ->assertJsonPath('news.0.content', '<p>Le nouveau serveur est disponible.</p>');

        $this->actingAs($admin)
            ->delete('/admin/news/' . $article->id)
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseMissing('news', ['id' => $article->id]);
        Storage::disk('public')->assertMissing($article->image);
    }

    public function test_azuriom_news_is_only_available_with_azuriom_configuration(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $options = PanelOptions::general();

        $this->actingAs($admin)
            ->get('/admin/general')
            ->assertOk()
            ->assertDontSee('id="news_azuriom"', false);

        $this->actingAs($admin)->from('/admin/general')->post('/admin/general/update', $this->generalPayload([
            'news_mode' => 'azuriom',
        ]))->assertRedirect('/admin/general')
            ->assertSessionHasErrors('news_mode');

        $options->update([
            'auth_mode' => 'azuriom',
            'azuriom_url' => 'https://azuriom.example.test/',
            'azuriom_api_key' => str_repeat('a', 32),
        ]);

        $this->actingAs($admin)
            ->get('/admin/general')
            ->assertOk()
            ->assertSee('id="news_azuriom"', false);

        $this->actingAs($admin)->post('/admin/general/update', $this->generalPayload([
            'news_mode' => 'azuriom',
        ]))->assertRedirect(route('admin.general'));

        $this->getJson('/utils/api?api_version=2')
            ->assertOk()
            ->assertJsonPath('news_mode', 'azuriom')
            ->assertJsonPath('news_azuriom_url', 'https://azuriom.example.test/api/posts')
            ->assertJsonCount(0, 'news');
    }

    public function test_switching_to_microsoft_disables_azuriom_news(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PanelOptions::general()->update([
            'auth_mode' => 'azuriom',
            'azuriom_url' => 'https://azuriom.example.test',
            'azuriom_api_key' => str_repeat('a', 32),
            'news_mode' => 'azuriom',
        ]);

        $this->actingAs($admin)->post('/admin/config', [
            'app_name' => config('app.name'),
            'auth_mode' => 'microsoft',
        ])->assertRedirect(route('admin.config'));

        $this->assertDatabaseHas('options_general', [
            'auth_mode' => 'microsoft',
            'news_mode' => 'builtin',
            'azuriom_url' => null,
        ]);
    }

    public function test_rss_mode_requires_a_valid_feed_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->from('/admin/general')->post('/admin/general/update', $this->generalPayload([
            'news_mode' => 'rss',
            'news_rss_url' => '',
        ]))->assertRedirect('/admin/general')
            ->assertSessionHasErrors('news_rss_url');
    }

    private function generalPayload(array $overrides = []): array
    {
        return array_merge([
            'mods_enabled' => '1',
            'file_verification' => '1',
            'embedded_java' => '0',
            'game_folder_name' => 'centralcorp',
            'email_verified' => '0',
            'role_display' => '1',
            'money_display' => '0',
            'min_ram' => 2048,
            'max_ram' => 4096,
            'news_mode' => 'builtin',
            'news_rss_url' => null,
        ], $overrides);
    }
}
