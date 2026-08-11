# Design QA — MHD overview

## Evidence

- Source visual truth:
  - `docs/design-qa/mhd-overview/type-overview-reference.png` (`1225 × 462 px`).
  - `docs/design-qa/mhd-overview/menu-cards-reference.png` (`781 × 409 px`).
  - `docs/design-qa/mhd-overview/technical-cta-reference.png` (`374 × 616 px`).
- Browser-rendered implementation:
  - `docs/design-qa/mhd-overview/implementation-overview.png` (`1586 × 1126 px`).
  - `docs/design-qa/mhd-overview/implementation-menu.png` (`1586 × 1126 px`).
- Combined full-view and focused-region evidence: `docs/design-qa/mhd-overview/comparison.png` (`1800 × 1120 px`).
- Browser viewport: `1444 × 1013 CSS px`, device pixel ratio `0.9`; the saved browser captures include the in-app browser's mirrored canvas, while the comparison uses matching central content crops.
- State: desktop route `/reklama-na-mhd-kosice/`; overview state with the catalog navigation hidden, plus the scrolled catalog state with the fixed navigation revealed.

## Findings

No actionable P0, P1, or P2 differences remain.

- Fonts and typography: the MHD overview reuses the exact Plus Jakarta Sans hierarchy, optical weights, uppercase labels, paragraph sizing, and card typography from `/typ/[slug]` pages. The longer MHD title wraps to two lines, which is an expected content-driven difference.
- Spacing and layout rhythm: the `2 / 3 + 1 / 3` desktop grid, 40 px section rhythm, benefit-card spacing, compact parameter rows, radii, borders, and shadows match the type-page pattern.
- Colors and visual tokens: the overview uses the existing `brand`, `brand/10`, `bb-control`, gray surface, and white card tokens. The red technical header and CTA match the selected references; the revealed catalog navigation remains graphite to avoid excess red.
- Image quality and asset fidelity: the new overview introduces no new raster imagery. Existing hero and vehicle assets remain unchanged, and all new UI icons use the installed icon library.
- Copy and content: all visible product copy is Slovak. The MHD-specific description, benefits, technical parameters, section labels, and CTA describe the existing offer without adding unsupported audience or performance figures.
- Affordances and interaction states: the three numbered cards link to `#autobusy`, `#elektricky`, and `#verejne-osvetlenie`. The catalog navigation is absent from the overview state, fades in only after the overview passes the header, and is removed from the keyboard tab order while hidden. Hover, focus, and CTA states use the established type-page treatments.
- Responsive structure: the grid collapses to one column, the right rail loses sticky positioning, badges hide on narrow screens, and the catalog navigation remains horizontally scrollable.

## Comparison history

First comparison pass: no actionable P0, P1, or P2 mismatch was found. The implementation intentionally substitutes MHD-specific copy and three section links for the reference page's billboard formats while preserving its hierarchy and component geometry.

Interaction follow-up: a P2 navigation issue was reported because the catalog bar occupied the normal page flow and was permanently visible. It was changed to a hidden fixed bar that appears only after scrolling beyond the overview. A second P2 overlap was found where the translated bar entered the breadcrumb row; the vertical translation was removed and the fixed offset was aligned to the breadcrumb bottom at `121 px`. Post-fix browser evidence shows the bar at `top: 121 px`, directly below the breadcrumbs, hidden again after returning to the overview.

## Browser verification

- Primary interactions tested: overview → catalog scroll reveal, catalog → overview reverse-scroll hide, and hidden-state keyboard exclusion.
- Fixed menu verified below the breadcrumb row at `top: 121px` after the overview.
- Hidden state verified with `opacity: 0`, `aria-hidden="true"`, and `tabindex="-1"` on all three links; revealed state restores the links to the tab order.
- CTA destinations verified: `tel:+421917930494` and `/kontakt/`.
- Browser console errors: none.
- Build, Node tests, and Astro diagnostics: passed.

## Implementation checklist

- [x] Add general MHD type description.
- [x] Add `Kľúčové výhody` using the shared type-page card pattern.
- [x] Replace `Formáty` with three functional section-link cards.
- [x] Add MHD technical parameters and CTA in the right rail.
- [x] Reveal the fixed section navigation only after the overview and keep it below the breadcrumbs.
- [x] Preserve all detailed offer sections and their interactions.

## Follow-up polish

No P3 follow-up is required for the requested desktop composition.

final result: passed
