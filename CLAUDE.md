# CLAUDE.md

Project context and architectural decisions for Claude Code sessions.

## What this is

A WordPress plugin that lets admins upload a CSV export of small groups, stores it as a versioned snapshot, and renders a filterable search UI via the `[small-group-search-v2]` shortcode. Built for a single church installation; the plugin itself must stay generic (no church-specific references in source, comments, or commit messages).

## Commands

```bash
# Run the test suite (inside Docker — PHP/Composer are not on the host)
docker compose exec wordpress bash -c \
  "cd /var/www/html/wp-content/plugins/small-groups-search && vendor/bin/phpunit"

# Build a WordPress-ready zip for manual upload
bash bin/package.sh
```

Local dev: `docker compose up -d` → WordPress at `http://localhost:8080`.

## File map

```
small-groups-search.php          # Plugin bootstrap, constants (SGS_DIR, SGS_URL, SGS_VERSION)
includes/
  class-csv-validator.php        # Validates CSV headers; returns warning strings
  class-csv-parser.php           # Parses CSV into array-of-group-arrays
  class-snapshot-cpt.php         # CPT + static API: save/activate/deactivate/delete/all/get_active_groups
  class-shortcode.php            # Registers [small-group-search-v2] and enqueues assets
admin/
  class-admin-page.php           # Admin menu, upload/activate/deactivate/delete handlers
  views/page.php                 # Admin HTML (upload form + snapshot history table)
  admin.css
assets/
  small-groups.js                # Alpine.js component: smallGroupSearch()
  small-groups.css
templates/search.php             # Shortcode HTML (Alpine x-data root)
tests/
  bootstrap.php
  ValidatorTest.php              # 15 tests — pure PHP, no mocking
  CsvParserTest.php              # 13 tests — fputcsv temp files
  SnapshotCptTest.php            # 6 tests — Brain Monkey WP function stubs
bin/package.sh                   # Builds production zip
```

## Architecture decisions (non-obvious — read before changing)

### Snapshot storage: PHP array, not JSON string

`update_post_meta($id, '_sgs_groups', $groups)` stores the PHP array directly (WordPress PHP-serializes it). Do **not** pass `wp_json_encode($groups)` as the value. WordPress's `update_post_meta` internally calls `wp_unslash()` on string values, which strips backslashes and corrupts any JSON with escaped quotes (`\"`). Storing a PHP array bypasses this entirely.

### Inline `<script>window.sgsData` instead of `wp_localize_script`

`wp_localize_script` is silently swallowed by the block-theme script pipeline — the variable never appears in the page source. The shortcode renderer echoes `<script>window.sgsData = {...};</script>` directly into the shortcode HTML output instead. `JSON_HEX_TAG` is used to prevent `</script>` injection.

### Alpine.js must be deferred

Alpine initializes on `DOMContentLoaded`. The `smallGroupSearch()` component function is defined in `small-groups.js`, which is enqueued in the footer. If Alpine loads before that function exists, it throws. The `add_alpine_defer` filter adds `defer` to the Alpine `<script>` tag so both scripts race to DOMContentLoaded rather than Alpine winning at parse time.

### CSV alias columns

Some CSV columns appear under a short name or a verbose long name (e.g. `Demographic Filter` vs `Demographic (HOW OLD ARE THE PEOPLE?)`). The parser maps both to the same output key. When iterating alias pairs, always guard with `array_key_exists` before reading the row value — a missing column should produce no output, not a `null` overwrite of the short-form value that was already set.

## Constraints

- No church-specific or hosting-provider-specific references in source, comments, commit messages, or documentation — keep all language generic
- PHP 8.1+ minimum; use typed properties and union return types freely
- WordPress coding standards for hooks and sanitization; no raw SQL
- Tests must pass before any commit (`vendor/bin/phpunit` — 34 tests, all green)
