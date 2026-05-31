# MortelOS Site

Public MortelOS website and documentation app.

The docs surface is served at `/docs/0/{slug}` and reads Markdown from the `0` branch of `mortelos/docs`.

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

## Verification

```bash
php artisan route:list | rg "docs"
php artisan docs:validate 0
php artisan docs:index 0
php artisan test
npm run build
```

Open `http://127.0.0.1:8000/docs/0/index`.
