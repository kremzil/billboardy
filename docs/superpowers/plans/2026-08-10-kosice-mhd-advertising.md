# Košice MHD Advertising Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a static Billboardy.sk sales page for Košice bus, tram and public-lighting advertising with every 2026 DPMK rental format priced 3% below the source price.

**Architecture:** Keep official source prices and offer metadata in one typed data module, with a pure cent-safe discount helper used by the Astro view. Render the route statically with a focused reusable offer-card component, local temporary imagery, existing layout/header/footer primitives, and no backend changes.

**Tech Stack:** Astro 6, TypeScript 5.9, Tailwind CSS 4, Node built-in test runner, Astro Icon/Lucide.

## Global Constraints

- Public route is `/reklama-na-mhd-kosice/` and must respect the existing Astro base path.
- All visible interface copy is Slovak; implementation discussion and documentation remain Russian or English as already used in `docs/`.
- Every official bus, tram, SIT and VLAJKA rental option in the supplied 2026 DPMK sources must be represented.
- Displayed rent is exactly `sourcePrice × 0.97`, rounded to two decimal places.
- Every offer shows `Polep: 100,00 € bez DPH`; displayed rent and polep prices exclude 23% DPH.
- Reuse existing header, footer, layout, typography, spacing and contact conventions; add no dependency and no backend contract.
- Store PDF-derived vehicle diagrams and the licensed hero photo locally; do not hotlink runtime assets.
- Preserve unrelated working-tree changes and stage only files named by each task.

---

## File Structure

- `map-frontend/src/data/mhdAdvertising.ts`: price types, complete source-price catalog, discount and euro-format helpers, source/attribution metadata.
- `map-frontend/src/data/mhdAdvertising.test.ts`: unit tests for discount rounding and catalog completeness.
- `map-frontend/src/components/MhdOfferCard.astro`: one accessible vehicle-format card with image, dimensions, price table, polep price and CTA.
- `map-frontend/src/pages/reklama-na-mhd-kosice.astro`: hero, benefits, bus/tram catalogs, public-lighting tables, pricing note and final CTA.
- `map-frontend/src/pages/mhdAdvertisingPage.test.ts`: built-output contract for route, copy, SEO, all formats, navigation and sitemap.
- `map-frontend/src/components/header.astro`: desktop/mobile menu link to the new page and active state.
- `map-frontend/src/pages/sitemap.xml.ts`: static route entry.
- `map-frontend/public/assets/mhd-kosice/*`: local hero and temporary PDF-derived diagrams.
- `map-frontend/public/assets/mhd-kosice/ATTRIBUTION.md`: hero author/license/source and DPMK source links.
- `map-frontend/package.json`: stable `npm test` command using the Node test runner.
- `docs/frontend.md`, `docs/current-state.md`: route and behavior documentation.

---

### Task 1: Pricing Domain and Complete Catalog

**Files:**
- Create: `map-frontend/src/data/mhdAdvertising.test.ts`
- Create: `map-frontend/src/data/mhdAdvertising.ts`
- Modify: `map-frontend/package.json`

**Interfaces:**
- Produces: `discountPrice(sourcePrice: number): number`.
- Produces: `formatEuro(price: number): string` returning Slovak decimal output such as `698,40 €`.
- Produces: `BUS_OFFERS`, `TRAM_OFFERS`, `SIT_RENTALS`, `FLAG_RENTALS`, `POLEP_PRICE`, `VAT_RATE`.
- `VehicleOffer` contains `slug`, `title`, optional `size`, `image`, `imageAlt`, optional `note`, and `prices: { period: string; sourcePrice: number }[]`.

- [ ] **Step 1: Add the test command and write the failing pricing test**

Add to `package.json`:

```json
"test": "node --test --experimental-strip-types src/data/mhdAdvertising.test.ts src/pages/mhdAdvertisingPage.test.ts"
```

Start `mhdAdvertising.test.ts` with real behavior assertions:

```ts
import test from "node:test";
import assert from "node:assert/strict";
import {
  BUS_OFFERS,
  TRAM_OFFERS,
  SIT_RENTALS,
  FLAG_RENTALS,
  discountPrice,
  formatEuro,
} from "./mhdAdvertising.ts";

test("discountPrice applies a 3% reduction and rounds half cents up", () => {
  assert.equal(discountPrice(720), 698.4);
  assert.equal(discountPrice(382.5), 371.03);
  assert.equal(discountPrice(11.5), 11.16);
});

test("formatEuro always shows two Slovak decimal places", () => {
  assert.equal(formatEuro(698.4), "698,40 €");
});

test("catalog retains every official rental surface and period", () => {
  assert.equal(BUS_OFFERS.length, 11);
  assert.equal(TRAM_OFFERS.length, 2);
  assert.equal(BUS_OFFERS.reduce((sum, item) => sum + item.prices.length, 0), 43);
  assert.equal(TRAM_OFFERS.reduce((sum, item) => sum + item.prices.length, 0), 8);
  assert.equal(SIT_RENTALS.length, 5);
  assert.equal(FLAG_RENTALS.length, 6);
});
```

- [ ] **Step 2: Run the pricing test and confirm RED**

Run: `node --test --experimental-strip-types src/data/mhdAdvertising.test.ts`

Expected: FAIL because `mhdAdvertising.ts` does not exist.

- [ ] **Step 3: Implement cent-safe pricing and source data**

Use this pricing behavior in `mhdAdvertising.ts`:

```ts
export const DISCOUNT_RATE = 0.03;
export const POLEP_PRICE = 100;
export const VAT_RATE = 23;

export function discountPrice(sourcePrice: number) {
  return Math.round((sourcePrice * (1 - DISCOUNT_RATE) + Number.EPSILON) * 100) / 100;
}

export function formatEuro(price: number) {
  return `${price.toLocaleString("sk-SK", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`;
}
```

Populate source prices exactly from the two supplied official documents. Keep the short labels `1 mesiac`, `2 mesiace`, `3 mesiace`, `6 mesiacov`, and `12 mesiacov`; do not pre-discount stored values.

- [ ] **Step 4: Run the focused test and confirm GREEN**

Run: `node --test --experimental-strip-types src/data/mhdAdvertising.test.ts`

Expected: 3 tests pass with zero failures.

- [ ] **Step 5: Commit the pricing domain**

```powershell
git add -- map-frontend/package.json map-frontend/src/data/mhdAdvertising.ts map-frontend/src/data/mhdAdvertising.test.ts
git commit -m "Add Košice MHD advertising price catalog"
```

---

### Task 2: Local Temporary Image Assets

**Files:**
- Create: `map-frontend/public/assets/mhd-kosice/hero-tram-kosice.webp`
- Create: `map-frontend/public/assets/mhd-kosice/bus-*.webp`
- Create: `map-frontend/public/assets/mhd-kosice/tram-*.webp`
- Create: `map-frontend/public/assets/mhd-kosice/public-lighting.webp`
- Create: `map-frontend/public/assets/mhd-kosice/ATTRIBUTION.md`

**Interfaces:**
- Produces local `/assets/mhd-kosice/<filename>.webp` paths consumed by `mhdAdvertising.ts` and the page hero.

- [ ] **Step 1: Download the selected licensed hero source locally**

Use the original Wikimedia Commons file `Vario LF2+ Košice č801.JPG` by Adehertogh, licensed CC BY-SA 4.0, from its file page. Save only a local working copy under `tmp/` before conversion.

- [ ] **Step 2: Render and crop the supplied DPMK PDF diagrams**

Render PDF pages 4–10 at sufficient resolution, then use Pillow to crop the vehicle drawings without DPMK price text. Export WebP at quality 86. Shared views may be reused only when the highlighted advertising surface differs visibly; otherwise export one diagram per official format for explicit mapping.

- [ ] **Step 3: Create the public-lighting illustration and attribution file**

Use the rendered first page of the official pole-price PDF as a temporary visual crop for the public-lighting section. Record the two official DPMK source URLs and the Wikimedia author, file-page URL, and CC BY-SA 4.0 license URL in `ATTRIBUTION.md`.

- [ ] **Step 4: Verify generated assets**

Run a Pillow inspection that asserts every expected WebP exists, has non-zero width/height, uses `RGB` or `RGBA`, and is no larger than 500 kB. Open the hero, one bus crop, one tram crop and the public-lighting crop with `view_image`.

- [ ] **Step 5: Commit the assets**

```powershell
git add -- map-frontend/public/assets/mhd-kosice map-frontend/src/data/mhdAdvertising.ts
git commit -m "Add temporary Košice MHD advertising imagery"
```

---

### Task 3: Static Page, Navigation and Sitemap

**Files:**
- Create: `map-frontend/src/pages/mhdAdvertisingPage.test.ts`
- Create: `map-frontend/src/components/MhdOfferCard.astro`
- Create: `map-frontend/src/pages/reklama-na-mhd-kosice.astro`
- Modify: `map-frontend/src/components/header.astro`
- Modify: `map-frontend/src/pages/sitemap.xml.ts`

**Interfaces:**
- Consumes: all exports from `src/data/mhdAdvertising.ts` and local asset paths.
- Produces: prerendered `/reklama-na-mhd-kosice/index.html`.

- [ ] **Step 1: Write a failing built-output contract**

Create a Node test that reads `dist/reklama-na-mhd-kosice/index.html` and `dist/sitemap.xml`, then asserts:

```ts
const requiredCopy = [
  "Reklama na autobusoch a električkách v Košiciach",
  "Celoplošný polep — krátky autobus",
  "Busboard XXL",
  "Zadná plocha — celá",
  "E-board XXL",
  "Smerové informačno-navádzacie tabule",
  "VLAJKA",
  "698,40 €",
  "Polep: 100,00 € bez DPH",
];
for (const copy of requiredCopy) assert.match(html, new RegExp(copy.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
assert.match(html, /<link rel="canonical" href="https:\/\/www\.billboardy\.sk\/reklama-na-mhd-kosice\/"/);
assert.match(sitemap, /reklama-na-mhd-kosice\//);
```

- [ ] **Step 2: Run the page contract and confirm RED**

Run: `npm run build; node --test --experimental-strip-types src/pages/mhdAdvertisingPage.test.ts`

Expected: FAIL because the route HTML does not exist.

- [ ] **Step 3: Implement the reusable offer card**

`MhdOfferCard.astro` accepts `{ offer: VehicleOffer }`, renders the local image with fixed dimensions and lazy loading, maps `prices` through `discountPrice` and `formatEuro`, includes `Polep: 100,00 € bez DPH`, and links to `${basePath}kontakt/?typ=<encoded offer title>` using visible copy `Vyžiadať ponuku`.

- [ ] **Step 4: Implement the page in the established type-page style**

Use `Layout`, `Image` or local `<img>` as appropriate, and `Icon`. Preserve the existing `max-w-7xl`, rounded-card and brand-red conventions. Render semantic sections in this order: breadcrumb/hero, benefit strip, anchor navigation, buses, trams, public lighting, price note, dark final CTA. Add a small visible photo credit linking to Wikimedia Commons.

- [ ] **Step 5: Wire navigation and sitemap**

Add `{ label: "MHD Košice", href: `${basePath}reklama-na-mhd-kosice/` }` to the desktop/mobile advertising menu and include the route in `staticRoutes` with `changefreq: "monthly"` and `priority: "0.8"`.

- [ ] **Step 6: Build and confirm GREEN**

Run: `npm run build`

Then: `node --test --experimental-strip-types src/pages/mhdAdvertisingPage.test.ts`

Expected: page contract passes, including route copy, canonical URL and sitemap entry.

- [ ] **Step 7: Commit the page**

```powershell
git add -- map-frontend/src/components/MhdOfferCard.astro map-frontend/src/pages/reklama-na-mhd-kosice.astro map-frontend/src/pages/mhdAdvertisingPage.test.ts map-frontend/src/components/header.astro map-frontend/src/pages/sitemap.xml.ts
git commit -m "Add Košice MHD advertising page"
```

---

### Task 4: Documentation and Final Verification

**Files:**
- Modify: `docs/frontend.md`
- Modify: `docs/current-state.md`

**Interfaces:**
- No new runtime interface; documents the route, pricing rule and local assets.

- [ ] **Step 1: Update project documentation**

Document the route under frontend pages, the typed price source, the 3% calculation, the temporary PDF-derived assets, and the hero attribution file. Update the current-state snapshot with the new static sales page.

- [ ] **Step 2: Run all automated verification fresh**

From `map-frontend` run:

```powershell
npm test
npm run check
npm run build
```

Expected: zero test failures, zero Astro/TypeScript errors, successful static build.

- [ ] **Step 3: Verify the visible page**

Start `npm run dev -- --host 127.0.0.1`, open `/reklama-na-mhd-kosice/`, and inspect at desktop and mobile widths. Verify hero crop, all category anchors, card tables, public-lighting overflow behavior, focus states, CTA links and the header menu. Capture implementation screenshots and inspect them with `view_image`.

- [ ] **Step 4: Complete the fidelity ledger**

Compare at least: page structure against `typ/[slug].astro`, typography, palette, image treatment, container/spacing model, desktop cards, mobile tables, visible Slovak copy and CTA behavior. Fix every material issue; record only intentional deviations caused by this page being a price catalog rather than a map page.

- [ ] **Step 5: Run repository checks and commit docs/final adjustments**

```powershell
git diff --check
git status --short
git add -- docs/frontend.md docs/current-state.md
git commit -m "Document Košice MHD advertising page"
```

Confirm no temporary PDF downloads or rendering intermediates are staged.
