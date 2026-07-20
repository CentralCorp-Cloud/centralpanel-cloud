<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ManagedConfigurationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $originalEnvironment = [];

    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $name => $value) {
            if ($value === null) {
                unset($_ENV[$name], $_SERVER[$name]);
                putenv($name);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }

        foreach ($this->paths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_managed_database_reads_db_password_file_and_pg_variables(): void
    {
        $passwordPath = $this->temporarySecret('postgres-file-password');
        $this->setEnvironment([
            'PANEL_MANAGED' => 'true',
            'DB_PASSWORD_FILE' => $passwordPath,
            'PGPASSWORD_FILE' => null,
            'PGHOST' => 'postgres.internal',
            'PGPORT' => '5544',
            'PGDATABASE' => 'centralpanel',
            'PGUSER' => 'panel_user',
        ]);

        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $this->assertSame('pgsql', $config['default']);
        $this->assertSame('postgres.internal', $config['connections']['pgsql']['host']);
        $this->assertSame('5544', $config['connections']['pgsql']['port']);
        $this->assertSame('centralpanel', $config['connections']['pgsql']['database']);
        $this->assertSame('panel_user', $config['connections']['pgsql']['username']);
        $this->assertSame('postgres-file-password', $config['connections']['pgsql']['password']);
        $this->assertSame('prefer', $config['connections']['pgsql']['sslmode']);
    }

    public function test_app_key_is_read_from_file(): void
    {
        $key = 'base64:' . base64_encode(str_repeat('k', 32));
        $keyPath = $this->temporarySecret($key . "\n");
        $this->setEnvironment(['APP_KEY_FILE' => $keyPath]);

        $config = require dirname(__DIR__, 2) . '/config/app.php';

        $this->assertSame($key, $config['key']);
    }

    /** @param array<string, string|null> $values */
    private function setEnvironment(array $values): void
    {
        foreach ($values as $name => $value) {
            if (!array_key_exists($name, $this->originalEnvironment)) {
                $this->originalEnvironment[$name] = $_ENV[$name] ?? getenv($name) ?: null;
            }

            if ($value === null) {
                unset($_ENV[$name], $_SERVER[$name]);
                putenv($name);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }

    private function temporarySecret(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'centralpanel-config-');
        file_put_contents($path, $contents);
        $this->paths[] = $path;

        return $path;
    }
}
