<?php

namespace Tests\Unit;

use App\Support\SecretFile;
use App\Support\StrictJsonFile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SecretFileTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->paths) as $path) {
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                rmdir($path);
            }
        }

        parent::tearDown();
    }

    public function test_it_reads_a_secret_value_without_its_trailing_newline(): void
    {
        $path = $this->temporaryFile("secret-value\n");

        $this->assertSame('secret-value', SecretFile::readValue($path, 'test'));
    }

    public function test_it_rejects_missing_empty_oversized_and_non_regular_files(): void
    {
        foreach ([
            '/tmp/centralpanel-definitely-missing-secret' => 'régulier',
            $this->temporaryFile('') => 'vide',
            $this->temporaryFile(str_repeat('a', 33)) => 'taille maximale',
            $this->temporaryDirectory() => 'régulier',
        ] as $path => $message) {
            try {
                SecretFile::read($path, 'test', 32);
                $this->fail("{$path} aurait dû être refusé.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function test_it_rejects_symbolic_links(): void
    {
        $target = $this->temporaryFile('secret');
        $link = tempnam(sys_get_temp_dir(), 'centralpanel-link-');
        unlink($link);
        symlink($target, $link);
        $this->paths[] = $link;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fichier régulier');

        SecretFile::read($link, 'test');
    }

    public function test_strict_json_accepts_only_the_expected_object_fields(): void
    {
        $valid = $this->temporaryFile('{"name":"Admin","email":"admin@example.com","password":"safe-value"}');

        $this->assertSame('Admin', StrictJsonFile::read($valid, ['name', 'email', 'password'])['name']);

        foreach ([
            '{invalid',
            '[]',
            '{"name":"Admin","email":"admin@example.com","password":"safe-value","role":"root"}',
            '{"name":"Admin","email":"admin@example.com"}',
        ] as $json) {
            $path = $this->temporaryFile($json);

            try {
                StrictJsonFile::read($path, ['name', 'email', 'password']);
                $this->fail('Le JSON invalide aurait dû être refusé.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_strict_json_rejects_an_oversized_or_non_regular_bootstrap(): void
    {
        $oversized = $this->temporaryFile(str_repeat(' ', StrictJsonFile::MAX_BYTES + 1));

        foreach ([$oversized, $this->temporaryDirectory()] as $path) {
            try {
                StrictJsonFile::read($path, ['name', 'email', 'password']);
                $this->fail('Le bootstrap aurait dû être refusé.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'centralpanel-secret-');
        file_put_contents($path, $contents);
        $this->paths[] = $path;

        return $path;
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir() . '/centralpanel-secret-dir-' . bin2hex(random_bytes(6));
        mkdir($path);
        $this->paths[] = $path;

        return $path;
    }
}
