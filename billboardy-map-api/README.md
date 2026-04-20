# Billboardy Map API

WordPress plugin that exposes a clean advertising-space API for a standalone Astro frontend.

## Install

1. Copy `billboardy-map-api` into `wp-content/plugins/`.
2. Activate **Billboardy Map API** in WordPress.
3. Confirm WooCommerce is active and advertising spaces are WooCommerce products.
4. Optional: set placeholder image URL, cache TTL and allowed frontend origins in **Settings -> Billboardy Map API**.

## REST API

Base namespace:

```text
/wp-json/billboardy/v1
```

Endpoints:

- `GET /ad-spaces?page=1&per_page=100&media_type=billboard&city=Bratislava&search=4781`
- `GET /map-points?media_type=billboard&city=Bratislava&search=Predstaničné`
- `GET /ad-spaces/wc_1413`
- `GET /filters`

The frontend must consume only this API contract, never WooCommerce REST or raw WordPress tables.

