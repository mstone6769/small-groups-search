#!/usr/bin/env bash
# Build a WordPress-ready zip for manual plugin upload.
# Output: small-groups-search-<version>.zip in the repo root.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="$(basename "$PLUGIN_DIR")"
VERSION=$(grep -o "SGS_VERSION', '[0-9.]*'" "${PLUGIN_DIR}/small-groups-search.php" | grep -o "[0-9.]*")
OUTFILE="${PLUGIN_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

echo "Packaging ${PLUGIN_SLUG} v${VERSION}..."

cd "$(dirname "$PLUGIN_DIR")"
rm -f "$OUTFILE"

zip -r "$OUTFILE" "$PLUGIN_SLUG" \
  -x "${PLUGIN_SLUG}/.git/*"             \
  -x "${PLUGIN_SLUG}/.*"                 \
  -x "${PLUGIN_SLUG}/vendor/*"           \
  -x "${PLUGIN_SLUG}/tests/*"            \
  -x "${PLUGIN_SLUG}/composer.json"      \
  -x "${PLUGIN_SLUG}/composer.lock"      \
  -x "${PLUGIN_SLUG}/phpunit.xml.dist"   \
  -x "${PLUGIN_SLUG}/docker-compose.yml" \
  -x "${PLUGIN_SLUG}/bin/*"              \
  -x "*.zip"

echo "Created: $OUTFILE"
