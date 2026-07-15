<?php

namespace Tests\Feature;

use App\Models\Instance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstanceAdminTest extends TestCase
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
        File::deleteDirectory(storage_path('app/public/data/test-instance'));
        File::deleteDirectory(storage_path('app/public/data/renamed-instance'));

        $path = storage_path('installed');
        if ($this->installedFileExisted) {
            File::put($path, $this->installedFileContent ?? '');
        } else {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_admin_can_create_rename_and_delete_an_instance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/instances', [
            'display_name' => 'Test Instance',
            'description' => 'Description initiale',
            'name' => 'test-instance',
            'server_name' => 'Test Server',
            'server_ip' => '127.0.0.1',
            'server_port' => 25565,
            'minecraft_version' => '1.20.1',
            'loader_type' => 'fabric',
            'loader_build_version' => '0.16.9',
            'loader_activation' => '1',
        ])->assertRedirect(route('admin.instances.index'));

        $instance = Instance::where('name', 'test-instance')->firstOrFail();
        $this->assertDirectoryExists(storage_path('app/public/data/test-instance'));
        File::put(storage_path('app/public/data/test-instance/marker.txt'), 'preserved');

        $this->actingAs($admin)->put('/admin/instances/' . $instance->id, [
            'display_name' => 'Renamed Instance',
            'description' => 'Description mise à jour',
            'name' => 'renamed-instance',
            'server_name' => 'Test Server',
            'server_ip' => '127.0.0.1',
            'server_port' => 25565,
            'minecraft_version' => '1.20.1',
            'loader_type' => 'fabric',
            'loader_build_version' => '0.16.9',
            'loader_activation' => '1',
        ])->assertRedirect(route('admin.instances.edit', $instance->id));

        $this->assertDatabaseHas('instances', [
            'id' => $instance->id,
            'name' => 'renamed-instance',
            'description' => 'Description mise à jour',
        ]);
        $this->assertFileExists(storage_path('app/public/data/renamed-instance/marker.txt'));
        $this->assertDirectoryDoesNotExist(storage_path('app/public/data/test-instance'));

        $this->actingAs($admin)
            ->delete('/admin/instances/' . $instance->id)
            ->assertRedirect(route('admin.instances.index'));

        $this->assertDatabaseMissing('instances', ['id' => $instance->id]);
        $this->assertDirectoryDoesNotExist(storage_path('app/public/data/renamed-instance'));
    }

    public function test_default_instance_cannot_be_deleted_and_default_switch_is_atomic(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $default = Instance::where('is_default', true)->firstOrFail();
        $second = Instance::create([
            'name' => 'test-instance',
            'display_name' => 'Test Instance',
            'is_default' => false,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/instances/' . $default->id)
            ->assertSessionHas('error');
        $this->assertDatabaseHas('instances', ['id' => $default->id]);

        $this->actingAs($admin)
            ->post('/admin/instances/' . $second->id . '/set-default')
            ->assertSessionHas('success');

        $this->assertSame(1, Instance::where('is_default', true)->count());
        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($default->fresh()->is_default);
    }

    public function test_admin_can_assign_a_youtube_background_to_an_instance_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $instance = Instance::where('is_default', true)->firstOrFail();

        $this->actingAs($admin)->post('/admin/instances/' . $instance->id . '/bg/update', [
            'role_id' => '12',
            'role_name' => 'VIP',
            'role_video_url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        ])->assertRedirect();

        $this->assertDatabaseHas('options_bgs', [
            'instance_id' => $instance->id,
            'role_id' => '12',
            'role_name' => 'VIP',
            'image_path' => '',
            'video_url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        ]);
    }
}
