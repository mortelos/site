#!/usr/bin/env bash
#
# Draait binnen de nieuwe release, vóór activatie. Alles wat hier faalt houdt de
# release tegen -- dat is het punt: een kapotte build hoort nooit live te komen.
#
# Aangeroepen vanuit het Forge deploy-script, dat alleen de macro's bevat die
# Forge tekstueel substitueert ($CREATE_RELEASE, $ACTIVATE_RELEASE,
# $RESTART_QUEUES). Die kunnen niet in dit bestand staan; de FORGE_*-variabelen
# hieronder wel, want dat zijn echte geëxporteerde env-variabelen.
set -e

export PATH="/home/forge/.nvm/versions/node/v22.12.0/bin:$PATH"
node -v
npm -v

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci --no-audit --no-fund
npm run build

mkdir -p storage/app/docs/repos storage/app/docs/worktrees storage/app/docs/search \
         storage/framework/cache storage/framework/sessions storage/framework/views \
         bootstrap/cache

# Valideert tegen de echte mortelos/docs repo: de site leest die bij elke
# request, dus kapotte content mag geen nieuwe release opleveren.
$FORGE_PHP artisan docs:validate 0
$FORGE_PHP artisan docs:index 0
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link || true
