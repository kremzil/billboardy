# Design QA: Verejné osvetlenie

## Sources of visual truth

- `aligned-layout-reference.png` — požadované rozloženie so samostatným nadpisom a zarovnanými kartami.
- `type-heading-card-reference.png` — typografia nadpisu a štandardná karta s farebným záhlavím.
- `format-hover-reference.png` — neutrálny hover tieň kariet formátov.
- `reference.png` — pôvodný návrh dvojstĺpcového bloku.

## Verified implementation

- Nadpis a úvodný text sú samostatne nad dvojstĺpcovým obsahom.
- Prvá cenová tabuľka a karta `Kategória ulíc` majú na desktope rovnakú hornú hranu (nameraný rozdiel `0 px`).
- Karta kategórií používa červené záhlavie, biele telo a jemný neutrálny tieň podľa kariet technických parametrov.
- Aktívny tab sa rozšíri na pomer `2.5 : 1 : 1`; neaktívne taby zostávajú užšie.
- Hover používa neutrálny `shadow-md`; pôvodný červený tieň bol odstránený a focus ring je sivý.
- Zoznamy A, B a C zostávajú rolovateľné a ovládateľné myšou aj klávesnicou.

## Comparison

- `implementation.png` — aktuálny výrez lokálnej stránky.
- `comparison.png` — referenčný návrh a implementácia vedľa seba.

## Result

`passed` — bez otvorených P0, P1 alebo P2 vizuálnych problémov.
