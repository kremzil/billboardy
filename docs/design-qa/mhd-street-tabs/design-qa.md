# Design QA: Verejné osvetlenie

## Sources of visual truth

- `aligned-layout-reference.png` — požadované rozloženie so samostatným nadpisom a zarovnanými kartami.
- `type-heading-card-reference.png` — typografia nadpisu a štandardná karta s farebným záhlavím.
- `format-hover-reference.png` — neutrálny hover tieň kariet formátov.
- `pale-red-reference.png` — svetlý červený odtieň prevzatý z kariet výhod na stránkach typov.
- `reference.png` — pôvodný návrh dvojstĺpcového bloku.

## Verified implementation

- Nadpis a úvodný text sú samostatne nad dvojstĺpcovým obsahom.
- Ľavý stĺpec a karta `Kategória ulíc` majú na desktope rovnakú hornú aj dolnú hranu (nameraný rozdiel `0 px`).
- Záhlavia oboch tabuliek aj karty kategórií používajú svetlé pozadie `brand/10` s červeným textom.
- Aktívny tab sa rozšíri na pomer `2.5 : 1 : 1`, ale zostáva biely s neutrálnou sivou hranou; neaktívne taby sú užšie.
- Hover používa neutrálny `shadow-md`; pôvodný červený tieň bol odstránený a focus ring je sivý.
- Zoznamy A, B a C zostávajú rolovateľné a ovládateľné myšou aj klávesnicou.

## Comparison

- `implementation.png` — aktuálny výrez lokálnej stránky.
- `comparison.png` — referenčný návrh a implementácia vedľa seba.

## Result

`final result: passed` — bez otvorených P0, P1 alebo P2 vizuálnych problémov.
