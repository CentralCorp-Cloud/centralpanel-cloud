# CentralPanel V2

CentralPanel est distribué sous forme d’image multi-architecture :

```text
ghcr.io/centralcorp-cloud/centralpanel-cloud
```

L’image écoute sur le port interne `8080`, expose `GET /up` et conserve toutes les données modifiables dans un stockage unique monté sur `/app/storage`. Elle fonctionne nativement avec l’UID/GID `10001:10001`, un rootfs en lecture seule, toutes les capabilities supprimées et `no-new-privileges`.

Deux modes sont pris en charge :

- autonome, avec SQLite par défaut et installation automatique facultative ;
- CentralCloud managé, avec PostgreSQL et tous les secrets lus depuis `/run/secrets`.

Documentation :

- [Installation automatique](docs/AUTO_INSTALL.md)
- [Image Docker et contrat CentralCloud](docs/DOCKER.md)

## Démarrage autonome

```bash
cp docker/secrets/panel_bootstrap.json.example docker/secrets/panel_bootstrap.json
# Modifier le fichier sans le versionner, puis :
AUTO_INSTALL=true docker compose up -d
```

Le panel est disponible par défaut sur <http://localhost:8080>. Pour construire le code local :

```bash
docker compose -f compose.yml -f compose.local.yml up -d --build
```

## Développement et validation

```bash
composer install
composer test
docker build -t centralpanel-cloud:agent-test .
composer test:docker
docker compose config
```

Le workflow `docker-image.yml` publie les plateformes `linux/amd64` et `linux/arm64`. Utilisez un tag immuable ou un digest en production ; la procédure est détaillée dans la documentation Docker.
