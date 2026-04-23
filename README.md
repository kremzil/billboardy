# Billboardy.sk Interactive Map Module

This workspace contains two separated parts:

- `billboardy-map-api`: WordPress plugin that exposes a clean REST API backed by WooCommerce products or the dedicated imported map table.
- `map-frontend`: standalone Astro + Tailwind frontend created with the official Astro scaffold.

The frontend is intentionally not bundled into the WordPress plugin. This keeps the direction open for a future Astro frontend over a WordPress backend.

## Backend

Install `billboardy-map-api` into `wp-content/plugins/` and activate it.

REST base:

```text
/wp-json/billboardy/v1
```

Main endpoints:

- `GET /ad-spaces`
- `GET /map-points`
- `GET /ad-spaces/{id}`
- `GET /filters`

Plugin settings are available in WordPress under **Settings -> Billboardy Map API**.

Map data import is available under **Settings -> Billboardy Map Import**. The importer writes to the dedicated `wp_billboardy_ad_spaces` table and currently supports:

- normalized KMZ CSV (`kmz-relevant-adspaces.csv`)
- WooCommerce CSV export
- Knosic BLB XLSX
- Knosic CLV XLSX

Run a dry import first, then import with **Replace records from this source** when refreshing a source file. When the imported table has rows, the API reads from it; otherwise it falls back to WooCommerce products.

## Frontend

The Astro app lives in `map-frontend`.

```bash
cd map-frontend
npm install
npm run dev
npm run check
npm run build
```

Configure it with `.env` based on `.env.example`.

If the Astro app is hosted on a different origin than WordPress, add that origin in the plugin settings.
