<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePanelInstalled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ManagedProxyTest extends TestCase
{
    private string $databasePasswordFile;

    public function createApplication()
    {
        $passwordFile = tempnam(sys_get_temp_dir(), 'centralpanel-proxy-test-');
        if ($passwordFile === false) {
            throw new \RuntimeException('Unable to create managed proxy test secret.');
        }
        file_put_contents($passwordFile, 'test-database-password');
        chmod($passwordFile, 0600);
        $this->databasePasswordFile = $passwordFile;

        putenv('PANEL_MANAGED=true');
        putenv("DB_PASSWORD_FILE={$passwordFile}");

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('PANEL_MANAGED');
        putenv('DB_PASSWORD_FILE');
        @unlink($this->databasePasswordFile);
    }

    public function test_managed_panel_generates_https_urls_behind_traefik(): void
    {
        $this->withoutMiddleware(EnsurePanelInstalled::class);

        Route::get('/_managed-proxy-test', static fn (Request $request): array => [
            'secure' => $request->isSecure(),
            'page' => url('/login'),
            'asset' => asset('build/assets/app.css'),
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '172.20.0.2'])
            ->withHeaders([
                'Host' => 'ssss.panels.vexato.fr',
                'X-Forwarded-Host' => 'ssss.panels.vexato.fr',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://centralpanel/_managed-proxy-test')
            ->assertOk()
            ->assertExactJson([
                'secure' => true,
                'page' => 'https://ssss.panels.vexato.fr/login',
                'asset' => 'https://ssss.panels.vexato.fr/build/assets/app.css',
            ]);
    }
}
