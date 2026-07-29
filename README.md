# MortelOS Site

Public MortelOS website and documentation app.

The docs surface is served at `/docs/0/{slug}` and reads Markdown from `mortelos/docs`. Each docs version maps to a branch through `docs.version_branches` in `config/docs.php`; version `0` tracks `main` until a v1 ships and version `0` is frozen on its own branch. Versions without an entry read the branch of the same name.

## Stack Decisions

Installed now:

1. Laravel 13
2. Livewire 4 page components
3. Tailwind 4 through Vite
4. League CommonMark
5. PHPUnit feature tests

Explicitly planned:

1. Flux components when the public site needs reusable interactive controls beyond this docs surface.
2. Pest if the wider site test suite moves to Pest conventions.
3. Playwright as a committed app dependency when browser regression tests become part of CI. The v0 launch screenshots were captured outside the app dependency tree.

## Local Setup

```bash
composer install
npm install
npm run build
php artisan serve
```

For local content development, point the app at a checkout of `mortelos/docs`:

```env
DOCS_CONTENT_PATH=/Users/uteq/Sites/mortelos-docs/mortelos/docs
```

Without `DOCS_CONTENT_PATH`, the app clones `https://github.com/mortelos/docs.git` into `storage/app/docs` and checks out immutable worktrees by commit SHA.

## Deployment

The Forge deploy script is a bootstrap that calls into this repository:

```bash
$CREATE_RELEASE()
set -e
cd $FORGE_RELEASE_DIRECTORY
bash .forge/build.sh
$ACTIVATE_RELEASE()
$RESTART_QUEUES()
bash .forge/verify.sh
```

Everything else lives in `.forge/`, under version control:

1. `.forge/build.sh` runs before activation, so anything failing there keeps the release from going live — including `docs:validate 0` against the published docs repository.
2. `.forge/verify.sh` runs after activation and fails the deploy when the site does not serve a 200. Point `SMOKE_URL` at a failing URL to confirm the check can still turn red.

Two Forge details decide that split:

1. `$CREATE_RELEASE()`, `$ACTIVATE_RELEASE()` and `$RESTART_QUEUES()` are textual substitutions Forge applies to its own script field. They are syntax errors in an ordinary shell file, so they cannot move into this repository.
2. `FORGE_PHP`, `FORGE_COMPOSER`, `FORGE_RELEASE_DIRECTORY` and the other `FORGE_*` values are real exported environment variables, so scripts in `.forge/` read them without any plumbing.

Forge cannot source its deploy script from a repository, so that bootstrap is the one part that has to be edited in Forge itself. Quick deploy is off: pushing does not deploy.

## Verification

```bash
php artisan route:list | rg "docs"
php artisan docs:validate 0
php artisan docs:index 0
php artisan test
npm run build
```

Open `http://127.0.0.1:8000/docs/0/index`.
