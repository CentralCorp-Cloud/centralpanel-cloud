<?php

namespace Tests\Unit;

use App\Support\PanelCache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PanelCacheTest extends TestCase
{
    public function test_runtime_cache_files_are_deleted(): void
    {
        $bootstrapCache = base_path('bootstrap/cache/codex-test.php');
        $compiledView = storage_path('framework/views/codex-test.php');

        File::ensureDirectoryExists(dirname($bootstrapCache));
        File::ensureDirectoryExists(dirname($compiledView));
        File::put($bootstrapCache, '<?php return [];');
        File::put($compiledView, '<?php echo "test";');

        PanelCache::clearRuntimeFiles();

        $this->assertFileDoesNotExist($bootstrapCache);
        $this->assertFileDoesNotExist($compiledView);
    }
}
