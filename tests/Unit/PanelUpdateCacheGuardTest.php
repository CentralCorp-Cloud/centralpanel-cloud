<?php

namespace Tests\Unit;

use App\Support\PanelUpdateCacheGuard;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PanelUpdateCacheGuardTest extends TestCase
{
    public function test_version_change_clears_runtime_caches_and_records_marker(): void
    {
        $marker = storage_path('app/panel-runtime-version');
        $compiledView = storage_path('framework/views/codex-runtime-view.php');
        $bootstrapCache = base_path('bootstrap/cache/codex-runtime-route.php');

        try {
            File::ensureDirectoryExists(dirname($marker));
            File::ensureDirectoryExists(dirname($compiledView));
            File::ensureDirectoryExists(dirname($bootstrapCache));
            File::put($marker, '1.0.0');
            File::put($compiledView, '<?php echo "stale";');
            File::put($bootstrapCache, '<?php return [];');

            PanelUpdateCacheGuard::ensureFreshForVersion('v1.1.0');

            $this->assertFileDoesNotExist($compiledView);
            $this->assertFileDoesNotExist($bootstrapCache);
            $this->assertSame('1.1.0', trim((string) File::get($marker)));

            File::put($compiledView, '<?php echo "current";');
            File::put($bootstrapCache, '<?php return [];');

            PanelUpdateCacheGuard::ensureFreshForVersion('1.1.0');

            $this->assertFileExists($compiledView);
            $this->assertFileExists($bootstrapCache);
        } finally {
            File::delete($marker);
            File::delete($compiledView);
            File::delete($bootstrapCache);
        }
    }
}
