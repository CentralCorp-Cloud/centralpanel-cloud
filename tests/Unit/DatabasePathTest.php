<?php

namespace Tests\Unit;

use App\Support\DatabasePath;
use Tests\TestCase;

class DatabasePathTest extends TestCase
{
    public function test_empty_sqlite_path_uses_default_database_file(): void
    {
        $this->assertSame(
            database_path('database.sqlite'),
            DatabasePath::sqlite('')
        );
    }

    public function test_relative_database_path_is_resolved_from_base_path(): void
    {
        $this->assertSame(
            base_path('database/database.sqlite'),
            DatabasePath::sqlite('database/database.sqlite')
        );
    }

    public function test_linux_absolute_path_is_kept(): void
    {
        $this->assertSame(
            '/home/site/database/database.sqlite',
            DatabasePath::sqlite('/home/site/database/database.sqlite')
        );
    }

    public function test_windows_absolute_path_is_kept_and_normalized(): void
    {
        $this->assertSame(
            'C:/panel/database/database.sqlite',
            DatabasePath::sqlite('C:\\panel\\database\\database.sqlite')
        );
    }
}
