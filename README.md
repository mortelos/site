# MortelOS Site

Public MortelOS website and documentation app.

The docs surface is served at `/docs/0/{slug}` and reads Markdown from the `0` branch of `mortelos/docs`.

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
php artisan test
npm run build
```

Open `http://127.0.0.1:8000/docs/0/index`.
