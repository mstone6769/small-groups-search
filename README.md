# Small Groups Search

A WordPress plugin that provides a CSV-driven small group search experience. Admins upload a CSV export from Google Sheets; the plugin stores it as a versioned snapshot and renders a live, filterable search UI via a shortcode.

## Features

- **Admin CSV upload** — import a groups spreadsheet and optionally activate it immediately
- **Snapshot history** — every upload is stored; activate any previous snapshot or delete old ones
- **Schema validation** — warns on missing or renamed columns without blocking the import
- **`[small-group-search-v2]` shortcode** — Alpine.js 3.x powered UI with filter dropdowns and keyword search
- **Safe rollback** — the active snapshot stays live until you explicitly activate a new one

## Requirements

- PHP 8.1+
- WordPress 6.0+

## Installation

1. Copy or clone this repository into `wp-content/plugins/small-groups-search/`
2. Activate the plugin in **WP Admin → Plugins**
3. Go to **WP Admin → Small Groups** to upload your first CSV

## Usage

### Uploading groups

1. Export your groups Google Sheet as CSV
2. Go to **WP Admin → Small Groups → Upload CSV**
3. Choose the file and check **Activate immediately** to make it live right away, or leave it unchecked to review warnings first
4. Use the **Activate** button in the Snapshot History table to switch the live data to any snapshot

### Shortcode

Add `[small-group-search-v2]` to any page or post. The shortcode renders the full search UI with filters for day, demographic, category, type, childcare, and online availability.

### CSV column reference

The plugin expects the following columns (column order does not matter):

| Column | Output field |
|---|---|
| `LifeGroup Name` | Group name |
| `Name` | Leader(s) |
| `Display Email` | Contact email |
| `Display Phone` | Contact phone |
| `Description` | Description |
| `Meeting Days` | Meeting schedule |
| `Location of LifeGroup` | Location |
| `Form Link` | Sign-up URL |
| `Filter Days` | Day filter values (comma-separated) |
| `Childcare\nCheckbox` | Childcare available (`Yes`/`No`) |
| `Online/Zoom Checkbox` | Online group (`Yes`/`No`) |
| `Hidden` | Set to `Yes` to exclude from results |

These columns may appear under either the short or long form name:

| Short form | Long form |
|---|---|
| `Demographic Filter` | `Demographic (HOW OLD ARE THE PEOPLE?)` |
| `Category` | `Category (WHO GATHERS TOGETHER)` |
| `Target \| Gray Text` | `Target \| Gray Text (WHO SHOULD SIGN UP)` |
| `Type Filter` | `Group Type (WHAT HAPPENS IN GROUP)` |

## Testing

[PHPUnit](https://phpunit.de) + [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) (WordPress function stubs). No database or WordPress install required.

```bash
# First time: install dependencies
composer install

# Run the suite
composer test
```

Tests live in `tests/`. The suite covers `SGS_CSV_Validator`, `SGS_CSV_Parser`, and `SGS_Snapshot_CPT`.

If you don't have PHP/Composer locally, run via the Docker container:

```bash
docker compose exec wordpress bash -c \
  "cd /var/www/html/wp-content/plugins/small-groups-search && vendor/bin/phpunit"
```

## Local development

Docker is required. The compose file starts a WordPress + MySQL environment matching the production PHP version.

```bash
docker compose up -d
```

WordPress will be available at `http://localhost:8080`. Complete the five-minute install, then activate the plugin and upload a CSV from **WP Admin → Small Groups**.

The plugin directory is bind-mounted into the container, so file changes take effect immediately without restarting.

## License

GPL-2.0-or-later
