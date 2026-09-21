# EmprendimientoOS — IIS Production Deployment

This runbook deploys one Laravel release to the declared GoDaddy Windows Hosting target: IIS 10, PHP 8.3, and MySQL-compatible Percona Server 8.4. Build the release ZIP before upload. Production does not run Node.js, Vite, Docker, or a persistent application server.

## Release and IIS layout

1. Build and verify a release ZIP with `scripts/build-release.sh` and `scripts/verify-release.sh`.
2. Keep each release in its own versioned directory, for example `releases/emprendimientoos-f016f3cd6f0a4c461b481913c830d6021a346aa/`.
3. Extract the ZIP into that directory without merging it into an older release.
4. Set the IIS site/application physical path to that release's `public/` directory. The repository root is not the document root.
5. Ensure IIS URL Rewrite is installed and enabled so the packaged `public/web.config` can route Laravel requests to `index.php`.
6. PHP 8.3 must be configured by the hosting environment, including the extensions required by Laravel and the MySQL connection.
7. Grant the PHP/IIS identity write access to `storage/` and `bootstrap/cache/`, including the writable directories listed below.

The package's writable directory skeleton is intentionally empty:

- `storage/app/private`
- `storage/app/public`
- `storage/framework/cache/data`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

These are runtime boundaries, not release content. Do not replace their persistent contents with a fresh release.

## Persistent environment

The package contains no `.env` file. Supply production configuration outside the release package through the hosting environment or an operator-managed file that is not inside the release archive. Never commit or document secret values.

Required and critical settings include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=<stable value preserved across releases>
APP_URL=https://<correct-production-host>

DB_CONNECTION=mysql
DB_HOST=<Percona host>
DB_PORT=3306
DB_DATABASE=<production database>
DB_USERNAME=<production username>
DB_PASSWORD=<secret supplied outside the package>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
FILESYSTEM_DISK=local
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

Use the actual hosting/database values for the placeholders. Preserve the same `APP_KEY` across releases; changing it invalidates encrypted values and sessions. Use the correct HTTPS `APP_URL`, secure cookies under HTTPS, and a production-appropriate log channel and level. Do not use default production credentials.

## Google access and first administrator

Create a Google Cloud OAuth client of type **Web application**. Under **Authorized redirect URIs**, register exactly:

```text
https://<correct-production-host>/auth/google/callback
```

The host, scheme, path, and trailing slash must match the public IIS URL exactly. Production must use HTTPS. Supply these values outside the release ZIP, through the hosting environment or an operator-managed file that is not versioned:

```dotenv
GOOGLE_CLIENT_ID=<Google web client id>
GOOGLE_CLIENT_SECRET=<Google web client secret>
GOOGLE_REDIRECT_URI=https://<correct-production-host>/auth/google/callback
EMPRENDIMIENTOOS_BOOTSTRAP_SECRET=<one-time random bootstrap secret>
```

`GOOGLE_REDIRECT_URI` is optional when the relative default is sufficient, but setting the exact HTTPS URI explicitly is recommended for IIS. The bootstrap secret is read only from environment-backed configuration. It is entered by POST on `/setup`; it is never accepted in a URL, never persisted, and never displayed after submission. Failed attempts are rate-limited.

After the release is serving HTTPS:

1. Open `https://<correct-production-host>/setup` in a browser.
2. Enter the bootstrap secret and continue with Google using a verified account for the first administrator.
3. Confirm that the browser reaches Home and that `/setup` is no longer available.
4. Remove `EMPRENDIMIENTOOS_BOOTSTRAP_SECRET` from the production environment and recycle the IIS application if the hosting control panel requires it.

The bootstrap state is stored in the database and remains complete even after the environment value is removed. Do not repeat this flow or create the first user through Artisan/SSH.

## Invitation administration

Once the first administrator exists, open **Accesos** from Home. Create an invitation for the exact normalized email, copy the one-time link, and send it through WhatsApp or another operator-controlled channel. The recipient opens the link and chooses **Continuar con Google** with that same verified email. The application consumes the invitation transactionally and creates or safely links the account.

The administrator can revoke pending invitations, activate or deactivate other users, and set a personal recovery password from the same surface. No SMTP setup, Artisan command, SSH session, or console access is required for user onboarding. The application does not provide public registration, password creation for invited users, user deletion, or administrator promotion UI.

## Persistent private files

Recipe images live under `storage/app/private` through Laravel's `local` filesystem disk. Preserve this directory and its contents across releases, and verify its permissions after extraction. The release ZIP never contains private recipe images.

Do not use `php artisan storage:link` as a substitute for private recipe-image delivery. Private images are authenticated application responses and must remain outside the IIS public document root.

If `storage/app/public` is used for a future public asset, it is also persistent application data and must be wired separately before deployment; it is not packaged or overwritten by a release.

## Deploy sequence

After extracting the release, supplying the environment, and wiring/preserving the persistent storage directories:

```sh
php artisan migrate --force
php artisan optimize
```

Run those commands from the extracted release directory with PHP 8.3. Then:

1. Verify that `storage/` and `bootstrap/cache/` are writable by the PHP/IIS identity.
2. Confirm `storage/app/private` still contains the preserved recipe images and that the release did not place files there.
3. Run the read-only smoke checks from an operator machine:

   ```sh
   bash scripts/smoke-release.sh https://<production-host>
   ```

4. Confirm `/up`, `/login`, the guest protection redirect, the manifest, service worker, favicon, and PWA icons.

No Node command is required on the server. Do not run `npm run dev`, start Vite, or expose the repository root through IIS.

## Rollback

1. Preserve the previous release directory; do not delete it during the deployment.
2. Switch the IIS site/application physical path back to the previous release's `public/` directory.
3. Keep the same `APP_KEY`, production environment, database connection, and persistent `storage/app/private` directory.
4. Database migration rollback is not automatic. Back up the production database before `migrate --force`; treat any schema rollback as a separate, explicitly reviewed operation.

A rollback changes the IIS code path only. It must not overwrite persistent recipe images, sessions, logs, cache data, or database data.
