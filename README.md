# DEVIL EX · Online APK Update Control System

Remote update-dialog control for Android apps. PHP 8.2+, MySQL/MariaDB, no Composer, no frameworks, built for InfinityFree shared hosting.

You manage apps in an admin panel. Your Android app calls one JSON endpoint and shows a native dialog (optional update, mandatory update, or maintenance).

## What is inside

| Path | Purpose |
|---|---|
| `index.php`, `status.php`, `docs.php` | Public website, public update status, Android guide |
| `login.php`, `logout.php`, `install.php` | Authentication and one-time installer |
| `dashboard.php`, `applications.php`, `add-application.php`, `edit-application.php` | Dashboard and app management |
| `update-manager.php` | Update dialog control (the main feature) |
| `update-history.php`, `api-management.php`, `settings.php`, `profile.php` | History, API tools, settings, admin profile |
| `api/` | `check_update.php`, `get_app_config.php`, `report_download.php`, `report_update.php` |
| `config/`, `includes/` | Configuration and shared PHP (web access denied by `.htaccess`) |
| `assets/` | CSS, JS, images |
| `uploads/app-icons/` | Uploaded icons (PHP execution blocked) |
| `docs/UpdateChecker.kt` | Ready-to-use Kotlin integration |
| `database.sql` | Full MySQL schema |

## Install on InfinityFree

1. **Create the database.** InfinityFree panel → *MySQL Databases* → create a database. Note the host (like `sql123.infinityfree.com`), database name, user and password.
2. **Edit `config/database.php`.** Put those four values into `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. **Upload the files.** Using the File Manager or FTP, upload everything inside the `DEVIL-EX-UPDATE` folder into `htdocs` (or a subfolder such as `htdocs/update`). Keep the folder structure and the hidden `.htaccess` files.
4. **Run the installer.** Open `https://your-domain/install.php` (add your subfolder if you used one).
   - Step 1: click **Create tables** (imports `database.sql`). If it fails, import `database.sql` with phpMyAdmin instead, then reload.
   - Step 2: create your admin account (username, email, password of 10+ characters).
5. **Delete `install.php` and `database.sql`** from the server.
6. **Sign in** at `/login.php`.

There is no default password. The admin account is created by you in step 4 and stored with `password_hash()`.

## Using the panel

1. **Add New Application**: name, package name, versions, optional icon. A unique 32-character *Application ID* is generated.
2. **Update Dialog Manager**: pick the app, set *Enable update dialog*, type, latest version and version code, minimum supported code, texts, download URL, theme, and maintenance mode. A live preview shows the dialog. Saving changes the API output immediately.
3. **Update History**: every save is stored with a full snapshot. View any entry and restore it.
4. **API Management**: copy endpoints and Application IDs, see request counts and logs, regenerate an ID, delete old logs.

## API summary

```
GET  /api/check_update.php?app_id=APP_ID&version_code=100
GET  /api/get_app_config.php?app_id=APP_ID
POST /api/report_download.php   app_id, version_code, [target_version_code]
POST /api/report_update.php     app_id, from_version_code, to_version_code
```

Decision rules for `check_update.php`:

- `update_available` is true when the dialog is enabled and `version_code < latest_version_code`.
- `mandatory` is true when an update is available and (type is Mandatory **or** `version_code < min_supported_version_code`).
- `maintenance_mode` is independent: apps should show the maintenance dialog first.

Status codes: 200, 400 (bad or missing parameter), 403 (app disabled), 404 (unknown app ID), 405, 429 (rate limit), 500. Errors return `{"success":false,"error":"..."}`.

Full Android guide: open `docs.php` on your site, or read `docs/UpdateChecker.kt`.

## Security notes

- PDO prepared statements everywhere, output escaped with `htmlspecialchars`.
- CSRF token on every POST form, including logout.
- Passwords hashed with `password_hash()` and checked with `password_verify()`.
- Login lockout per IP and per username, API rate limit per IP (both adjustable in Settings), session cookie flags HttpOnly, SameSite and Secure on HTTPS, idle timeout of 30 minutes.
- Icon uploads are checked with `getimagesize()`, limited to JPG, PNG and WEBP up to 1 MB, saved under random names, and PHP execution is disabled in `uploads/`.
- `config/` and `includes/` deny direct web access; `.sql`, `.md` and `.log` files are blocked at the root.
- Use HTTPS. InfinityFree provides free SSL; enable it for your domain.
- Behind Cloudflare, IPs may all look the same. Turn on *Trust Cloudflare IP header* in Settings only if all traffic really comes through Cloudflare.

## Troubleshooting

- **"Could not connect to the database"**: the host is not `localhost` on InfinityFree; copy it from the panel.
- **Blank page**: set `DEBUG` to `true` in `config/config.php` temporarily, or check the error log, then set it back to `false`.
- **Icon upload fails**: make sure `uploads/app-icons/` exists and is writable.
- **HTTP 429 from the API**: raise the rate limit in Settings.
- Free hosts may show an interstitial for some non-browser clients. If your app receives HTML instead of JSON, test the endpoint URL from the device and consider a custom domain or paid hosting for production traffic.
