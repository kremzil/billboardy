# Design QA — Verejné osvetlenie

## Evidence

- Source visual truth: `docs/design-qa/mhd-street-tabs/aligned-layout-reference.png` (`843 × 734 px`) and `docs/design-qa/mhd-street-tabs/pale-red-reference.png` (`88 × 210 px`).
- Rendered implementation: `docs/design-qa/mhd-street-tabs/implementation.png` (`1278 × 889 px`).
- Combined comparison: `docs/design-qa/mhd-street-tabs/comparison.png` (`1472 × 780 px`).
- Browser viewport: `1444 × 1013 CSS px`; implementation crop: `1150 × 800 CSS px` at approximately `1.111×` capture density.
- State: desktop route `/reklama-na-mhd-kosice/`, street category `A` selected.

## Findings

No actionable P0, P1, or P2 differences remain.

- Fonts and typography: existing Plus Jakarta Sans family, weights, hierarchy, and table copy are preserved. The pale headers retain the original readable heading size and weight.
- Spacing and layout rhythm: the section title remains separate above the two-column content. Browser measurement confirms `0 px` difference between the top and bottom edges of the pricing column and the category card.
- Colors and visual tokens: all three card headers use the requested `brand/10` pale red with brand-red text. The active category tab is white with a neutral border; the red selected-state fill and red shadow are absent.
- Section navigation: the sticky `Autobusy / Električky / Verejné osvetlenie` control now uses the existing `bb-control` graphite token, white translucent chips, and a neutral shadow so it remains distinct without adding more saturated red.
- Image quality and asset fidelity: this focused region contains no raster content beyond the supplied references; the existing library icon remains sharp and correctly aligned.
- Copy and content: Slovak headings, prices, category counts, and street names are unchanged.
- Interaction: category `B` was activated and category `A` restored successfully. The list remains scrollable, keyboard behavior remains implemented, and the browser console reported no errors.

Focused-region comparison was required because the requested changes concern header tint, selected-tab treatment, and exact column height. The combined comparison shows those details at readable scale.

## Comparison history

1. P2 — the category card visually continued below the two pricing cards because the grid row was sized by the longer street list. Fixed by measuring the natural pricing-column height at desktop widths and synchronizing the category card through `ResizeObserver`. Post-fix browser evidence: top delta `0 px`, bottom delta `0 px`.
2. P2 — saturated red headers and the red selected tab created excessive visual emphasis. Fixed by applying the existing `brand/10` tint to all headers and using a white active tab with a neutral border. Post-fix computed styles confirm pale red headers and a white active tab.

## Implementation checklist

- [x] Preserve separate section heading.
- [x] Align the category card with both pricing cards.
- [x] Use the supplied pale red treatment.
- [x] Keep active-tab expansion without a saturated fill.
- [x] Preserve tab switching, scrolling, focus behavior, and mobile stacking.
- [x] Verify console, build, tests, and Astro diagnostics.

## Follow-up polish

No P3 follow-up is required for the requested region.

final result: passed
