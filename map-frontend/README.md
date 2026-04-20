# Billboardy Map Frontend

Standalone Astro + Tailwind frontend for the Billboardy.sk advertising-space map.

The project was scaffolded with the official Astro CLI and Tailwind was added through `astro add tailwind`. It consumes the WordPress plugin API and does not depend on WooCommerce REST, WordPress tables, or plugin-bundled assets.

## Configure

Copy `.env.example` to `.env` and set:

```text
PUBLIC_BILLBOARDY_API_BASE=https://www.billboardy.sk/wp-json/billboardy/v1
PUBLIC_GOOGLE_MAPS_API_KEY=...
PUBLIC_CONTACT_URL=https://www.billboardy.sk/kontaktujte-nas/
PUBLIC_PLACEHOLDER_IMAGE_URL=https://www.billboardy.sk/path/to/placeholder.jpg
```

If this app is hosted on a different origin than WordPress, add that origin in WordPress under **Settings -> Billboardy Map API -> Allowed frontend origins**.

## Commands

```bash
npm install
npm run dev
npm run check
npm run build
npm run preview
```

## API usage

The app calls only:

- `/wp-json/billboardy/v1/filters`
- `/wp-json/billboardy/v1/map-points`
- `/wp-json/billboardy/v1/ad-spaces/{id}`

All user-facing copy is Slovak.
