<?php

namespace App\Support;

use JsonException;
use RuntimeException;

final class StrictJsonFile
{
    public const MAX_BYTES = 16384;

    /**
     * @param  list<string>  $expectedKeys
     * @return array<string, mixed>
     */
    public static function read(string $path, array $expectedKeys, string $label = 'bootstrap'): array
    {
        $contents = SecretFile::read($path, $label, self::MAX_BYTES);

        try {
            $payload = json_decode($contents, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException("Le fichier {$label} ne contient pas un JSON valide.");
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw new RuntimeException("Le fichier {$label} doit contenir un objet JSON.");
        }

        $actualKeys = array_keys($payload);
        sort($actualKeys);
        $requiredKeys = $expectedKeys;
        sort($requiredKeys);

        if ($actualKeys !== $requiredKeys) {
            throw new RuntimeException("Le fichier {$label} contient des champs absents ou inconnus.");
        }

        return $payload;
    }
}
