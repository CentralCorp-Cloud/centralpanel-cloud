<?php

namespace App\Services;

/**
 * MerkleTreeService — Calcul côté serveur du Merkle Tree pour la vérification d'intégrité.
 *
 * Utilise SHA-256 pour correspondre au client minecraft-java-core (MerkleTree.ts).
 * Les fichiers sont triés par chemin relatif pour un arbre déterministe.
 */
class MerkleTreeService
{
    /**
     * Construit le hash racine du Merkle Tree à partir d'une liste de fichiers.
     *
     * @param array $files Tableau de ['path' => string, 'hash' => string]
     * @return string Hash racine SHA-256
     */
    public static function computeRoot(array $files): string
    {
        if (empty($files)) {
            return hash('sha256', 'empty');
        }

        // Trier par chemin pour un arbre déterministe (identique au client TS)
        usort($files, fn($a, $b) => strcmp($a['path'], $b['path']));

        // Créer les feuilles (hash de chaque fichier)
        $nodes = array_map(fn($f) => $f['hash'], $files);

        // Construire l'arbre bottom-up
        return self::buildLevel($nodes);
    }

    /**
     * Combine les nœuds par paires jusqu'à obtenir un seul hash racine.
     */
    private static function buildLevel(array $nodes): string
    {
        if (count($nodes) === 1) {
            return $nodes[0];
        }

        $parents = [];
        for ($i = 0; $i < count($nodes); $i += 2) {
            $left = $nodes[$i];

            if ($i + 1 < count($nodes)) {
                $right = $nodes[$i + 1];
                $parents[] = hash('sha256', $left . $right);
            } else {
                // Nœud impair sans paire (identique au comportement TS)
                $parents[] = $nodes[$i];
            }
        }

        return self::buildLevel($parents);
    }

    /**
     * Calcule le hash SHA-256 d'un fichier.
     */
    public static function hashFile(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }
}
