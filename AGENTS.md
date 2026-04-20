# AGENTS.md

## Project Overview

This workspace contains the interactive advertising-space map module for `billboardy.sk`.

There are two intentionally separated parts:

- `billboardy-map-api/`: WordPress plugin that exposes a clean REST API backed by WooCommerce products.
- `map-frontend/`: standalone Astro + Tailwind frontend that consumes the WordPress REST API.

Do not merge the Astro app into the WordPress plugin. The target architecture is WordPress as backend and Astro as an independent frontend, with room to later migrate more of the site to Astro.

## Communication

- Communicate with the project owner in Russian.
- Keep product UI copy for the map in Slovak.
- Do not confuse conversation language with interface language: implementation notes and chat can be Russian, but visible frontend strings must remain Slovak.

## Core Architecture Rules

- The frontend must never call WooCommerce REST directly as its stable contract.
- The frontend must never depend on `wp_posts`, `postmeta`, WooCommerce taxonomies, or raw WooCommerce field names.
- The WordPress plugin owns all WooCommerce reading, normalization, caching, and API response shape.
- The frontend works only with the clean `AdSpace` API exposed under `/wp-json/billboardy/v1`.
- Do not use Google My Maps iframe/embed.
- Use Google Maps JavaScript API for the interactive map.
- User-facing map UI copy must be Slovak.

## Backend: `billboardy-map-api`

The plugin structure is layered:

- Bootstrap/plugin entry: `billboardy-map-api.php`
- Admin settings: `src/Admin/`
- Domain mapping: `src/Domain/`
- Data access: `src/Repository/`
- REST controllers: `src/Rest/`
- Application service layer: `src/Service/`

Main API endpoints:

- `GET /wp-json/billboardy/v1/ad-spaces`
- `GET /wp-json/billboardy/v1/map-points`
- `GET /wp-json/billboardy/v1/ad-spaces/{id}`
- `GET /wp-json/billboardy/v1/filters`

Backend implementation rules:

- Keep the public API contract stable and domain-oriented.
- Keep WooCommerce-specific logic inside repository and mapper layers.
- Return safe fallback objects where possible.
- Exclude invalid-coordinate items from `/map-points`, but keep them available in `/ad-spaces`.
- Keep cache invalidation versioned through the plugin cache version option unless there is a strong reason to replace it.
- Do not add checkout/sales behavior unless explicitly requested.
- Do not generate SEO pages per individual advertising space for the MVP.

Useful backend check:

```powershell
Get-ChildItem -Path billboardy-map-api -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Frontend: `map-frontend`

The frontend is an official Astro project with Tailwind added through Astro tooling.

Important files:

- `src/pages/index.astro`
- `src/scripts/map.ts`
- `src/styles/global.css`
- `.env.example`

Frontend implementation rules:

- Keep all visible UI strings in Slovak.
- Read configuration through public Astro env variables or `window.BILLBOARDY_MAP_CONFIG`.
- Use only these backend API calls:
  - `/filters`
  - `/map-points`
  - `/ad-spaces/{id}`
- Keep map payloads light; use `/map-points` for marker rendering and fetch full detail only on click.
- Keep clustering enabled for the current ~2000 point dataset.
- Do not assume the frontend and WordPress are on the same origin; preserve CORS-friendly API usage.

Frontend commands:

```powershell
cd map-frontend
npm install
npm run dev
npm run check
npm run build
```

Expected verification:

- `npm run check` should have no errors.
- `npm run build` should complete and write `map-frontend/dist/`.
- TypeScript may currently show non-blocking hints that `google.maps.Marker` is deprecated. Do not migrate to `AdvancedMarkerElement` unless the task explicitly includes that refactor.

## Data Mapping Expectations

The source WooCommerce data is imperfect. Preserve these mapping behaviors:

- `id`: stable API ID in the form `wc_{productId}`.
- `sourceId`: WooCommerce product ID.
- `code`: SKU/catalog number first, fallback from title like `4781 - BILLBOARD`.
- `mediaType`: normalized machine value such as `billboard`, `bigboard`, `citylight`, or `unknown`.
- `mediaTypeLabel`: Slovak-facing display label.
- `locationLabel`: clean short location text, without leading `v lokalite`.
- `city`: normalize Bratislava variants to `Bratislava`.
- `latitude` and `longitude`: primary source `_gps`, fallback from description.
- `sizeLabel`, `widthCm`, `heightCm`: parse from `Rozmer` in description when possible.
- `descriptionHtml`: sanitized description only; do not use it as the primary source for structured fields.
- Missing image: use configured placeholder when available.

## Current Workspace Notes

- `wc-adsPlaces.csv` is an export/reference file, not the runtime source for the plugin.
- The runtime source remains WooCommerce products in WordPress.
- The current export suggests all records are `Billboard`, mostly `visible`, mostly in Bratislava, and almost all have GPS.

## Editing Guidelines

- Keep backend and frontend changes scoped to their folders.
- Prefer small, explicit changes over broad rewrites.
- Do not delete or overwrite generated dependency folders unless the task requires reinstalling or rebuilding dependencies.
- Do not commit secrets. Google Maps API keys belong in environment/config, not source files.
- Keep docs updated when changing setup, endpoints, or deployment assumptions.
