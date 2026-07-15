<?php

namespace Tests\Feature;

use App\Models\Instance;
use App\Models\OptionsGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminLocalizationTest extends TestCase
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
        File::deleteDirectory(storage_path('app/public/data/localized-instance'));

        $path = storage_path('installed');
        if ($this->installedFileExisted) {
            File::put($path, $this->installedFileContent ?? '');
        } else {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_french_and_english_catalogues_have_matching_keys(): void
    {
        $this->assertSame(
            array_keys($this->flatten(require lang_path('fr/messages.php'))),
            array_keys($this->flatten(require lang_path('en/messages.php'))),
        );
        $this->assertSame(
            array_keys($this->flatten(require lang_path('fr/validation.php'))),
            array_keys($this->flatten(require lang_path('en/validation.php'))),
        );
    }

    public function test_instance_admin_pages_render_in_english(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $instance = Instance::where('is_default', true)->firstOrFail();

        $pages = [
            [route('admin.instances.index'), 'Instance Management'],
            [route('admin.instances.create'), 'Briefly introduce this instance to players'],
            [route('admin.instances.mods', $instance->id), 'Available JAR files'],
            [route('admin.instances.whitelist', $instance->id), 'Whitelisted users'],
            [route('admin.instances.bg', $instance->id), 'Image or video backgrounds by role'],
            [route('admin.instances.ignore', $instance->id), 'Folder name to ignore'],
            [route('admin.instances.files', $instance->id), 'File manager'],
        ];

        foreach ($pages as [$url, $expected]) {
            $this->actingAs($admin)
                ->withSession(['locale' => 'en'])
                ->get($url)
                ->assertOk()
                ->assertSee($expected);
        }
    }

    public function test_instance_javascript_messages_follow_the_selected_locale(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $instance = Instance::where('is_default', true)->firstOrFail();
        OptionsGeneral::query()->create([
            'auth_mode' => 'azuriom',
            'azuriom_url' => 'https://example.test',
            'azuriom_api_key' => 'test-key',
        ]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.instances.create'))
            ->assertOk()
            ->assertSee('Loading')
            ->assertSee('Delete the icon?')
            ->assertDontSee('Chargement');

        $this->actingAs($admin)
            ->withSession(['locale' => 'fr'])
            ->get(route('admin.instances.whitelist', $instance->id))
            ->assertOk()
            ->assertSee('Erreur de chargement')
            ->assertSee('Ajouter :count utilisateur');
    }

    public function test_validation_and_ajax_errors_follow_the_selected_locale(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'fr'])
            ->post(route('admin.instances.store'), [])
            ->assertSessionHasErrors(['display_name', 'name']);
        $this->assertStringContainsString('obligatoire', session('errors')->first('display_name'));

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.instances.fetchServers'))
            ->assertStatus(503)
            ->assertJson(['error' => 'API not configured']);
    }

    public function test_custom_admin_errors_are_translated(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        File::ensureDirectoryExists(storage_path('app/public/data/localized-instance'));

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.instances.store'), [
                'display_name' => 'Localized Instance',
                'name' => 'localized-instance',
                'server_port' => 25565,
                'loader_activation' => '1',
            ])
            ->assertSessionHasErrors(['name']);
        $this->assertSame(
            'A data folder already exists for this slug.',
            session('errors')->first('name'),
        );

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.settings.import'), [
                'settings_file' => UploadedFile::fake()->create('settings.json', 1, 'application/json'),
            ])
            ->assertSessionHas('error', 'The .centralcorp file is invalid or corrupted.');
    }

    private function flatten(array $values, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($values as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $flattened += $this->flatten($value, $fullKey);
            } else {
                $flattened[$fullKey] = $value;
            }
        }

        return $flattened;
    }
}
