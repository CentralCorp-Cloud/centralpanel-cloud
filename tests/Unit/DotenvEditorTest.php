<?php

namespace Tests\Unit;

use App\Support\DotenvEditor;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DotenvEditorTest extends TestCase
{
    public function test_windows_sqlite_path_with_spaces_is_quoted(): void
    {
        $path = DotenvEditor::normalizePath('C:\\Users\\Gabriel\\Desktop\\Nouveau dossier (5)\\centralpanel-v2\\database\\database.sqlite');

        $this->assertSame(
            'DB_DATABASE="C:/Users/Gabriel/Desktop/Nouveau dossier (5)/centralpanel-v2/database/database.sqlite"',
            DotenvEditor::line('DB_DATABASE', $path)
        );
    }

    public function test_update_file_removes_duplicate_env_keys(): void
    {
        $path = storage_path('framework/testing/dotenv-editor.env');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode(PHP_EOL, [
            'APP_NAME="CentralCorp Panel"',
            'DB_DATABASE=old.sqlite',
            'DB_DATABASE=C:\\Users\\Gabriel\\Desktop\\Nouveau dossier (5)\\centralpanel-v2\\database\\database.sqlite',
            '',
        ]));

        DotenvEditor::updateFile($path, [
            'DB_DATABASE' => DotenvEditor::normalizePath('C:\\Users\\Gabriel\\Desktop\\Nouveau dossier (5)\\centralpanel-v2\\database\\database.sqlite'),
        ]);

        $content = File::get($path);

        $this->assertSame(1, substr_count($content, 'DB_DATABASE='));
        $this->assertStringContainsString(
            'DB_DATABASE="C:/Users/Gabriel/Desktop/Nouveau dossier (5)/centralpanel-v2/database/database.sqlite"',
            $content
        );
    }
}
