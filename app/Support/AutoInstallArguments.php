<?php

namespace App\Support;

final class AutoInstallArguments
{
    /**
     * Symfony treats "-pass" as the short "-p" option followed by "ass".
     * Keep the documented legacy syntax working by normalizing it before
     * ArgvInput parses the command line.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public static function normalize(array $arguments): array
    {
        if (!in_array('auto:install', $arguments, true)) {
            return $arguments;
        }

        return array_map(static function (string $argument): string {
            if ($argument === '-pass') {
                return '--pass';
            }

            if (str_starts_with($argument, '-pass=')) {
                return '--pass=' . substr($argument, strlen('-pass='));
            }

            return $argument;
        }, $arguments);
    }
}
