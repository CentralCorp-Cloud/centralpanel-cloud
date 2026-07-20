<?php

namespace Tests\Unit;

use App\Support\AutoInstallArguments;
use PHPUnit\Framework\TestCase;

class AutoInstallArgumentsTest extends TestCase
{
    public function test_it_normalizes_the_documented_single_dash_password_option(): void
    {
        $arguments = AutoInstallArguments::normalize([
            'artisan',
            'auto:install',
            '-p',
            'Admin',
            '-m',
            'admin@example.com',
            '-pass',
            'secret-password',
        ]);

        $this->assertSame('--pass', $arguments[6]);
        $this->assertSame('secret-password', $arguments[7]);
    }

    public function test_it_does_not_change_arguments_for_other_commands(): void
    {
        $arguments = ['artisan', 'other:command', '-pass', 'value'];

        $this->assertSame($arguments, AutoInstallArguments::normalize($arguments));
    }
}
