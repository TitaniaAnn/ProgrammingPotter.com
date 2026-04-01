# Project Guidelines

## Code Style
- Keep changes minimal and consistent with existing plain PHP style (procedural page controllers + static helper classes).
- Prefer existing helpers instead of duplicating logic: `e()` for escaping, `redirect()` for redirects, `setting()` for settings reads.
- Use parameterized queries through `Database::query()` or other `Database` helpers; do not concatenate untrusted SQL input.
- Do not edit generated or third-party code under `vendor/`.

## Architecture
- Entry points are under `public/` and `public/admin/`; pages load shared setup via `includes/bootstrap.php`.
- Core boundaries:
  - `includes/Database.php`: PDO singleton and DB helpers
  - `includes/Auth.php`: GitHub OAuth + admin session checks
  - `includes/ImageUpload.php`: upload validation and thumbnail generation
  - `includes/Stripe.php`: Stripe checkout/webhook utilities
- Configuration constants are defined in `config/config.php` from environment values loaded in bootstrap.

## Build and Test
- Install dependencies: `composer install`
- Run local dev server from repo root: `php -S localhost:8000 -t public`
- Quick PHP syntax check before/after edits: `find . -path ./vendor -prune -o -name "*.php" -print | xargs -n1 php -l`
- No formal test suite is present. Validate changes with targeted page-level/manual checks.
- Database initialization/migrations are SQL-first (`sql/init.sql`, `sql/schema.sql`, `sql/*.sql`, `patches/*.php`).
- Deployment is GitHub Actions FTP sync on push to `main` (`.github/workflows/deploy.yml`).

## Conventions
- Admin pages should call `Auth::requireLogin()` near the top.
- Use flash message helpers for user feedback instead of inline ad-hoc session keys.
- Follow existing upload paths and constraints (`UPLOAD_PATH`, `MAX_IMAGE_SIZE`, thumbnail constants).
- Keep public-facing HTML escaped using `e()` unless raw HTML is explicitly intended.

## Pitfalls
- Required runtime inputs are in `.env`; missing DB/OAuth/Stripe env values can cause early failures.
- `sql/schema.sql` and `README.md` contain legacy Google OAuth references; treat `includes/Auth.php` and current config constants as source of truth (GitHub OAuth).
- `codemagic.yaml` is unrelated/stale for this repo; ignore it for normal development tasks.

## Docs
- Setup and operational details: `README.md`
- Schema and migrations: `sql/init.sql`, `sql/schema.sql`, `sql/*.sql`
- Deployment workflow: `.github/workflows/deploy.yml`