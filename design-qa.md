# Design QA — Verejné osvetlenie street tabs

- Source visual truth: `docs/design-qa/mhd-street-tabs/reference.png`
- Implementation screenshot: `docs/design-qa/mhd-street-tabs/implementation.png`
- Side-by-side comparison: `docs/design-qa/mhd-street-tabs/comparison.png`
- Route: `http://localhost:4321/reklama-na-mhd-kosice/`
- State: desktop, category A selected; responsive and B/C interaction states checked separately.
- Requested browser viewport: 1444 × 1013. The in-app browser rendered a 1604 × 1125 CSS viewport; the focused implementation crop is 1216 × 650 px.
- Source image: 907 × 864 px. The comparison canvas is 1680 × 840 px; both sources were proportionally fitted into equal 820 × 780 regions without stretching.

## Full-view comparison evidence

The comparison confirms the requested composition: title and both price tables form the wide left column, while the street category selector occupies the tall right column. The active A tab is wider than B and C, and the street list scrolls inside the panel.

## Focused region comparison evidence

The full comparison already uses a focused crop of the complete `Verejné osvetlenie` component. Text, table headers, tab states and representative street rows are readable at this scale, so an additional detail crop was not required.

## Required fidelity surfaces

- Fonts and typography: existing Billboardy.sk typography and weights are preserved; hierarchy follows the reference with a prominent section title, compact panel title and dense table copy.
- Spacing and layout rhythm: two-column desktop layout, stacked tables and right-side panel match the reference structure. On mobile the panel stacks below the tables with no horizontal overflow.
- Colors and visual tokens: the mockup's neutral structure is retained while the implementation uses the site's established black, gray and red brand tokens. The active tab uses brand red as the selected state.
- Image quality and asset fidelity: the reference contains no required photographic or illustrative asset in this component. No placeholder or recreated image was introduced.
- Copy and content: existing Slovak table copy and prices are unchanged. Categories A, B and C expose the official DPMK street lists.

## Interaction and browser checks

- Initial state: A selected; measured tab widths 184 / 88 / 88 px.
- Click state: B selected; measured tab widths 88 / 184 / 88 px; 38 rows visible in the active panel.
- Keyboard state: Arrow Right from B selects C; measured widths 88 / 88 / 184 px.
- Mobile: panel stacks below the tables; page client width equals scroll width.
- Browser console: 0 errors.

## Findings

- P0: none.
- P1: none.
- P2: none.
- P3: the implementation intentionally applies Billboardy.sk brand styling rather than the grayscale presentation of the wireframe.

## Comparison history

- Initial coded comparison: no actionable P0/P1/P2 differences. No visual fix iteration was required after the side-by-side review.

## Final result

final result: passed
