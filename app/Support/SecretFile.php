<?php

namespace App\Support;

use RuntimeException;

final class SecretFile
{
    public const DEFAULT_MAX_BYTES = 65536;

    public static function read(string $path, string $label, int $maxBytes = self::DEFAULT_MAX_BYTES): string
    {
        if ($path === '') {
            throw new RuntimeException("Le chemin du fichier {$label} est absent.");
        }

        if (!file_exists($path) && !is_link($path)) {
            throw new RuntimeException("Le fichier {$label} doit être un fichier régulier.");
        }

        $stat = lstat($path);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
            throw new RuntimeException("Le fichier {$label} doit être un fichier régulier.");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("Le fichier {$label} n’est pas lisible.");
        }

        $size = (int) $stat['size'];
        if ($size < 1) {
            throw new RuntimeException("Le fichier {$label} est vide.");
        }

        if ($size > $maxBytes) {
            throw new RuntimeException("Le fichier {$label} dépasse la taille maximale autorisée.");
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Le fichier {$label} ne peut pas être ouvert.");
        }

        try {
            $contents = stream_get_contents($handle, $maxBytes + 1);
        } finally {
            fclose($handle);
        }

        if (!is_string($contents) || strlen($contents) > $maxBytes) {
            throw new RuntimeException("Le fichier {$label} dépasse la taille maximale autorisée.");
        }

        if (trim($contents) === '') {
            throw new RuntimeException("Le fichier {$label} est vide.");
        }

        return $contents;
    }

    public static function readValue(string $path, string $label, int $maxBytes = self::DEFAULT_MAX_BYTES): string
    {
        $value = rtrim(self::read($path, $label, $maxBytes), "\r\n");

        if ($value === '') {
            throw new RuntimeException("Le fichier {$label} est vide.");
        }

        return $value;
    }
}
