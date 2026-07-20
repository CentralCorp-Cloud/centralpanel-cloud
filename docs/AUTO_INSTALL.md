# Installation automatique

La commande `auto:install` prépare la base configurée dans `.env`, exécute les migrations, crée le compte administrateur, génère une vraie `APP_KEY`, crée le lien de stockage et marque le panel comme installé.

> La première installation exécute `migrate:fresh`. Utilisez-la uniquement sur la base dédiée au panel. Si une installation ou un utilisateur existant est détecté, la commande s’arrête sans écraser les données.

## Avec PHP Artisan

Préparez l’application, puis lancez exactement :

```bash
cp .env.example .env
composer install
php artisan auto:install -p "PSEUDO" -m "MAIL@mail.com" -pass "PASSWORD"
```

Les paramètres sont obligatoires :

- `-p` ou `--pseudo` : nom du compte administrateur ;
- `-m` ou `--mail` : adresse e-mail valide et unique ;
- `-pass` ou `--pass` : mot de passe conforme aux règles Laravel (au moins 8 caractères par défaut).

Par défaut, `.env.example` utilise SQLite. Pour MySQL/MariaDB, configurez d’abord `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD` dans `.env`. La commande utilise toujours la connexion définie dans ce fichier.

Une installation réussie affiche l’e-mail de l’administrateur. Vous pouvez ensuite ouvrir l’URL définie par `APP_URL`.

## Avec Docker

### Installation automatique au démarrage

Créez un fichier `.env.docker` qui ne doit pas être versionné :

```dotenv
PANEL_PORT=8080
APP_URL=http://localhost:8080
AUTO_INSTALL=true
AUTO_INSTALL_PSEUDO=Admin
AUTO_INSTALL_MAIL=admin@example.com
AUTO_INSTALL_PASSWORD=change-this-password
```

Téléchargez l’image publiée et démarrez le panel :

```bash
docker compose --env-file .env.docker pull
docker compose --env-file .env.docker up -d
```

Compose utilise par défaut l’image `ghcr.io/centralcorp-cloud/centralpanel-cloud:latest`. Pour bloquer une version précise, ajoutez par exemple `PANEL_VERSION=1.1.12` dans `.env.docker`.

Le point d’entrée lance `auto:install` avant Apache. Les volumes `panel_database`, `panel_storage` et `panel_runtime` conservent respectivement SQLite, les fichiers du panel et le `.env` généré. Les redémarrages sont idempotents : une installation existante n’est pas recréée.

Lorsque le conteneur est prêt, le panel est disponible sur <http://localhost:8080> (ou sur le port choisi dans `PANEL_PORT`).

### Installation manuelle dans le conteneur

Vous pouvez aussi laisser `AUTO_INSTALL=false`, démarrer le service puis exécuter la commande vous-même :

```bash
docker compose up -d
docker compose exec panel php artisan auto:install \
  -p "PSEUDO" \
  -m "MAIL@mail.com" \
  -pass "PASSWORD"
```

### Construire l’image localement

Le fichier complémentaire `compose.local.yml` remplace l’image publiée par une construction depuis le `Dockerfile` du dépôt :

```bash
docker compose -f compose.yml -f compose.local.yml up -d --build
```

### Publication de l’image

Le workflow GitHub Actions `docker-image.yml` publie automatiquement une image multi-architecture pour `linux/amd64` et `linux/arm64` :

- une publication sur `main` crée les tags `main` et `sha-XXXXXXX` ;
- un tag Git `v1.2.3` crée les tags `1.2.3`, `1.2`, `1` et `latest` ;
- aucune clé externe n’est nécessaire : le workflow utilise le `GITHUB_TOKEN` avec la permission `packages: write`.

Après la toute première publication, vérifiez dans **GitHub → Packages → centralpanel-cloud → Package settings** que la visibilité est `Public` si l’image doit pouvoir être téléchargée sans authentification. Pour une image privée, connectez Docker à GHCR avant `docker compose pull`.

### Sécurité et maintenance

- Choisissez un mot de passe fort et limitez les droits du fichier `.env.docker` (`chmod 600 .env.docker`).
- Après la première installation automatique, vous pouvez remettre `AUTO_INSTALL=false` et retirer les trois identifiants du fichier `.env.docker`.
- Sauvegardez les trois volumes Docker avant une mise à jour ou une migration.
- Pour mettre à jour l’image : `docker compose pull && docker compose up -d`.
- Pour consulter les journaux : `docker compose logs -f panel`.
