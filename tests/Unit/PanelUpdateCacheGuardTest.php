<?php

namespace Tests\Unit;

use App\Support\PanelUpdateCacheGuard;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PanelUpdateCacheGuardTest extends TestCase
{
    public function test_version_change_clears_compiled_views_and_records_marker(): void
    {
        $marker = storage_path('app/panel-runtime-version');
        $compiledView = storage_path('framework/views/codex-runtime-view.php');

        try {
            File::ensureDirectoryExists(dirname($marker));
            File::ensureDirectoryExists(dirname($compiledView));
            File::put($marker, '1.0.0');
            File::put($compiledView, '<?php echo "stale";');

            PanelUpdateCacheGuard::ensureFreshForVersion('v1.1.0');

            $this->assertFileDoesNotExist($compiledView);
            $this->assertSame('1.1.0', trim((string) File::get($marker)));

            File::put($compiledView, '<?php echo "current";');

            PanelUpdateCacheGuard::ensureFreshForVersion('1.1.0');

            $this->assertFileExists($compiledView);
        } finally {
            File::delete($marker);
            File::delete($compiledView);
        }
    }
}
