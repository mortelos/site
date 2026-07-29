#!/usr/bin/env bash
#
# Draait ná activatie. Een groene deploy moet betekenen dat de site serveert,
# niet alleen dat het script uitliep -- precies dat verschil liet een release
# met een falende docs-validatie ooit ongemerkt live gaan.
#
# Faalt hier: de release staat dan al live, maar de deploy kleurt rood zodat je
# het weet in plaats van het van een bezoeker te horen.
set -e

# Override met SMOKE_URL om te controleren dat deze check ook echt rood wordt;
# een check die je niet kunt laten falen bewijst niets.
URL="${SMOKE_URL:-https://mortelos.nl/docs/0/index}"

# Retry omdat PHP-FPM na activatie nog even kan bijtrekken; zonder marge krijg
# je valse rode deploys.
for attempt in 1 2 3; do
    STATUS=$(curl -sS -o /dev/null -w '%{http_code}' "$URL" || true)
    if [ "$STATUS" = "200" ]; then break; fi
    sleep 3
done

if [ "$STATUS" != "200" ]; then
    echo "Smoke test failed: $URL returned $STATUS"
    exit 1
fi

echo "Smoke test passed: $URL returned 200"
