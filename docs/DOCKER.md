# Image Docker et CentralCloud

## Image, tags et digest

Image officielle :

```text
ghcr.io/centralcorp-cloud/centralpanel-cloud
```

Le workflow publie `linux/amd64` et `linux/arm64` avec :

- les tags de version `1.2.3`, `1.2`, `1` et `latest` pour un tag Git `v1.2.3` ;
- le nom de branche et `sha-XXXXXXX` pour les branches publiées.

Pour obtenir puis épingler le digest exact publié :

```bash
docker buildx imagetools inspect ghcr.io/centralcorp-cloud/centralpanel-cloud:1.2.3
docker pull ghcr.io/centralcorp-cloud/centralpanel-cloud@sha256:DIGEST
```

Le digest apparaît aussi dans le résumé du workflow GitHub Actions. Un build local n’est pas un digest publié et ne doit pas être présenté comme tel.

## Architecture du runtime

Le code et les liens symboliques sont immuables dans `/var/www/html`. Le seul stockage persistant du panel est `/app/storage` :

```text
/app/storage/
├── bootstrap-cache/          cache bootstrap Laravel
├── database/                 base SQLite autonome
├── laravel-storage/          storage Laravel, logs, sessions et fichiers publics
└── runtime/
    ├── .env                  configuration autonome, sans secrets CentralCloud
    └── auto-install.lock     verrou d’installation
```

Liens construits dans l’image :

```text
/var/www/html/storage          -> /app/storage/laravel-storage
/var/www/html/bootstrap/cache  -> /app/storage/bootstrap-cache
/var/www/html/.env             -> /app/storage/runtime/.env
/var/www/html/public/storage   -> /app/storage/laravel-storage/app/public
/var/www/html/database/database.sqlite -> /app/storage/database/database.sqlite
```

L’entrypoint crée seulement les sous-répertoires manquants sous `/app/storage`, `/tmp` et `/run`. Il ne fait aucun `chown`, ne recrée aucun lien et n’écrase jamais `.env`.

Apache écoute sans privilèges sur `8080`, écrit son PID et ses verrous dans `/run`, et envoie les accès/erreurs vers stdout/stderr. L’image déclare un `HEALTHCHECK` sur `http://127.0.0.1:8080/up`.

## Contrat exact du CentralCloud Node Agent

Le Node Agent peut lancer l’image avec :

```bash
docker run -d \
  --read-only \
  --user 10001:10001 \
  --cap-drop ALL \
  --security-opt no-new-privileges \
  --tmpfs /tmp:mode=1777 \
  --tmpfs /run:mode=1777 \
  --mount type=volume,src=PANEL_DATA,dst=/app/storage \
  ghcr.io/centralcorp-cloud/centralpanel-cloud:TAG
```

L’agent ajoute ses montages de secrets en lecture seule sous `/run/secrets` et un réseau privé vers PostgreSQL. Il ne publie aucun port hôte ; le proxy contacte le port interne `8080`.

Le volume `/app/storage` doit être inscriptible par `10001:10001`, et les fichiers de `/run/secrets` doivent être lisibles par cet UID tout en restant en lecture seule. L’image ne tente volontairement aucun changement de propriétaire ou de permissions au démarrage.

Variables non secrètes acceptées :

```text
APP_ENV
APP_URL
CENTRALPANEL_MODE
CLOUD_PROJECT_ID
PANEL_MANAGED=true
PGHOST
PGPORT
PGDATABASE
PGUSER
```

Variables contenant uniquement des chemins de fichiers secrets :

```text
DB_PASSWORD_FILE=/run/secrets/postgres_password
PGPASSWORD_FILE=/run/secrets/postgres_password
APP_KEY_FILE=/run/secrets/app_key
PANEL_BOOTSTRAP_FILE=/run/secrets/panel_bootstrap.json
CENTRALCLOUD_INTERNAL_SECRET_FILE=/run/secrets/internal_secret
```

`DB_PASSWORD_FILE` est prioritaire, avec repli sur `PGPASSWORD_FILE`. En mode managé, la connexion est forcée vers PostgreSQL avec `sslmode=prefer` et les variables `PG*`. Un fichier absent, irrégulier, illisible, vide ou trop grand provoque un échec propre. La valeur d’un secret n’est jamais loguée.

Après le healthcheck, l’agent lance :

```bash
php artisan auto:install --bootstrap-file=/run/secrets/panel_bootstrap.json --no-interaction
```

Pour un reset administrateur :

```bash
php artisan panel:admin-reset --bootstrap-file=/run/secrets/panel_admin_reset.json --no-interaction
```

Les secrets PostgreSQL, `APP_KEY`, secret interne et mot de passe administrateur ne sont jamais copiés vers `.env`, placés dans des variables Docker contenant leur valeur, transmis comme arguments ou affichés. Les variables `*_FILE` et les options `--bootstrap-file` ne contiennent que des chemins.

`config:cache` et `optimize` sont refusés en mode managé, car un cache Laravel sérialiserait les valeurs résolues des fichiers secrets. Au démarrage managé, l’entrypoint retire seulement un éventuel ancien `bootstrap-cache/config.php` ; il ne touche ni aux données, ni à `.env`, ni au marqueur d’installation.

L’installateur web, `install:reset` et les mises à jour de code depuis l’interface sont désactivés en mode managé. Une mise à jour CentralCloud remplace l’image en conservant `/app/storage` et PostgreSQL.

## Mode autonome avec Compose

Le fichier `compose.yml` applique les mêmes protections et monte un volume unique `panel_data` sur `/app/storage`. Il publie par commodité `PANEL_PORT` vers le port interne `8080` :

```bash
docker compose pull
docker compose up -d
docker compose ps
```

Pour l’installation automatique, suivez [AUTO_INSTALL.md](AUTO_INSTALL.md). SQLite est le défaut autonome ; MySQL/MariaDB restent utilisables avec `DB_CONNECTION` et les variables `DB_*`.

## Migration depuis les trois anciens volumes

L’ancien Compose utilisait séparément `panel_database`, `panel_storage` et `panel_runtime`. Arrêtez l’ancien conteneur et sauvegardez les trois volumes avant toute copie. Créez ensuite le nouveau volume vide, puis copiez :

```text
ancien panel_database/database.sqlite -> nouveau database/database.sqlite
ancien panel_storage/*                 -> nouveau laravel-storage/*
ancien panel_runtime/.env              -> nouveau runtime/.env
```

Exemple avec les noms Compose par défaut, à adapter au préfixe réel visible via `docker volume ls` :

```bash
docker volume create centralpanel-v2_panel_data
docker run --rm --user 0 \
  -v centralpanel-v2_panel_data:/new \
  -v centralpanel-v2_panel_database:/old-database:ro \
  -v centralpanel-v2_panel_storage:/old-storage:ro \
  -v centralpanel-v2_panel_runtime:/old-runtime:ro \
  alpine:3.22 sh -c '
    mkdir -p /new/database /new/laravel-storage /new/runtime /new/bootstrap-cache
    cp -a /old-database/database.sqlite /new/database/database.sqlite
    cp -a /old-storage/. /new/laravel-storage/
    cp -a /old-runtime/.env /new/runtime/.env
    chown -R 10001:10001 /new
  '
```

Le helper de migration utilise root uniquement pour copier les anciens volumes ; l’image CentralPanel reste non-root. Vérifiez la copie avant de supprimer les anciens volumes. Pour migrer vers CentralCloud managé, conservez les fichiers utilisateurs nécessaires mais laissez l’agent fournir un nouvel `.env` non secret et restaurez la base via PostgreSQL plutôt que de copier SQLite aveuglément.

## Sauvegarde et restauration

Sauvegardez ensemble :

- le volume `/app/storage` ;
- la base PostgreSQL avec `pg_dump` en mode CentralCloud.

Ne sauvegardez pas `/run/secrets` dans l’archive applicative. Lors d’une restauration, remontez le même stockage, restaurez PostgreSQL puis laissez l’agent recréer le conteneur avec les secrets actifs.

## Validation locale

```bash
docker build -t centralpanel-cloud:agent-test .
docker compose config
composer test:docker
```

Le test d’intégration utilise PostgreSQL sans port hôte, les contraintes de sécurité de l’agent, des secrets fichier, deux installations concurrentes et un redémarrage avec le même stockage.
