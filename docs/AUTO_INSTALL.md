# Installation automatique

`auto:install` applique les migrations sans supprimer les tables, crée un unique administrateur, prépare une vraie `APP_KEY` en mode autonome et écrit le marqueur d’installation dans le stockage Laravel persistant.

La commande acquiert un verrou dans `/app/storage/runtime` dans l’image Docker, puis revérifie le marqueur et la table `users`. Une installation existante n’est jamais écrasée. Une base non vide qui n’est pas reconnue comme une base CentralPanel est refusée ; `migrate:fresh` n’est jamais exécuté.

## CentralCloud : fichier bootstrap obligatoire

CentralCloud monte `/run/secrets/panel_bootstrap.json` en lecture seule :

```json
{
  "name": "Nom administrateur",
  "email": "admin@example.com",
  "password": "mot-de-passe"
}
```

Lancez ensuite :

```bash
php artisan auto:install \
  --bootstrap-file=/run/secrets/panel_bootstrap.json \
  --no-interaction
```

Le fichier doit être régulier, lisible, non vide, inférieur ou égal à 16 Kio et contenir un objet JSON strict avec exactement `name`, `email` et `password`. Les valeurs suivent les règles Laravel : nom requis (255 caractères au maximum), e-mail valide et mot de passe conforme à `Password::default()`.

Le mot de passe n’est ni affiché, ni transmis à une commande enfant, ni écrit dans `.env`. Seul son hash est enregistré en base.

## Mode autonome historique

La syntaxe existante reste disponible hors du mode managé :

```bash
cp .env.example .env
composer install
php artisan auto:install \
  -p "PSEUDO" \
  -m "MAIL@mail.com" \
  -pass "PASSWORD"
```

`-pass` est conservé pour compatibilité, tout comme `--pass`. Cette forme rend cependant le mot de passe visible dans les arguments du processus pendant l’exécution. Préférez donc aussi un fichier protégé en mode autonome :

```bash
php artisan auto:install --bootstrap-file=/chemin/protege/panel_bootstrap.json --no-interaction
```

Sans `APP_KEY_FILE`, une vraie clé est générée et persistée dans `.env`. MySQL/MariaDB et SQLite restent disponibles via les variables `DB_*` habituelles.

## Installation automatique avec Compose

Copiez le modèle de secret, modifiez-le et limitez ses permissions :

```bash
cp docker/secrets/panel_bootstrap.json.example docker/secrets/panel_bootstrap.json
chmod 600 docker/secrets/panel_bootstrap.json
AUTO_INSTALL=true docker compose up -d
```

Compose monte `docker/secrets` en lecture seule et positionne `AUTO_INSTALL_BOOTSTRAP_FILE=/run/secrets/panel_bootstrap.json`. Les anciennes variables `AUTO_INSTALL_PSEUDO`, `AUTO_INSTALL_MAIL` et `AUTO_INSTALL_PASSWORD` restent reconnues par l’entrypoint pour les déploiements autonomes existants, mais ne doivent jamais être utilisées avec CentralCloud ni dans un fichier Compose versionné.

En mode `PANEL_MANAGED=true`, l’entrypoint ne déclenche jamais l’installation : le Node Agent exécute explicitement la commande après le démarrage.

## Reset de l’administrateur principal

Le fichier `/run/secrets/panel_admin_reset.json` doit contenir exactement :

```json
{
  "email": "admin@example.com",
  "password": "nouveau-mot-de-passe"
}
```

Commande :

```bash
php artisan panel:admin-reset \
  --bootstrap-file=/run/secrets/panel_admin_reset.json \
  --no-interaction
```

La commande modifie le plus ancien utilisateur `is_admin=true`. Elle refuse l’absence d’administrateur et un e-mail appartenant à un autre utilisateur. Elle ne crée jamais de compte et peut être rejouée sans corruption ni affichage du mot de passe.
