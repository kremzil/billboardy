# Reklama na MHD v Košiciach — návrh stránky

## Cieľ

Pridať na Billboardy.sk samostatnú predajnú stránku pre prenájom reklamných plôch na autobusoch a električkách v Košiciach a pre prenájom tabúľ na stĺpoch verejného osvetlenia a trakčného vedenia.

## Umiestnenie a navigácia

- Verejná cesta: `/reklama-na-mhd-kosice/` v rámci existujúceho Astro `base` path.
- Stránka bude dostupná z rozbaľovacieho menu `reklamné plochy` na desktope aj mobile.
- Vzhľad, šírka kontajnera, typografia, navigácia, pätička a kontaktné prvky budú vychádzať z existujúcich stránok `typ/[slug].astro`.
- Stránka nebude obsahovať mapu, pretože ponuka je katalóg typov vozidlových a stĺpových plôch, nie zoznam geolokovaných billboardov.

## Obsah stránky

1. Hero s lokálnou fotografiou košickej MHD, nadpisom `Reklama na autobusoch a električkách v Košiciach`, krátkym popisom a CTA na dopyt.
2. Stručný benefitový pás s lokalitou, cenami bez DPH a kompletným servisom.
3. Katalóg `Autobusy` so všetkými plochami z cenníka DPMK:
   - Celoplošný polep — krátky autobus
   - Celoplošný polep — dlhý autobus
   - Busboard XXL
   - Busboard XL
   - Busboard L
   - Doorboard
   - Bočná samolepka 220 × 60 cm
   - Bočná samolepka 150 × 30 cm
   - Bočná samolepka 130 × 30 cm
   - Zadná plocha — okno
   - Zadná plocha — celá
4. Katalóg `Električky`:
   - Celoplošný polep
   - E-board XXL 400 × 200 cm
5. Sekcia `Verejné osvetlenie`:
   - Smerové informačno-navádzacie tabule (SIT) podľa kategórie stĺpa a ulice
   - Nosiče VLAJKA podľa kategórie a obdobia
6. Vysvetlenie cien a DPH.
7. Kontaktné CTA s telefónom, e-mailom a odkazom na existujúcu kontaktnú stránku/formulár.

## Ceny

- Zdrojom sú oficiálne cenníky DPMK platné v roku 2026.
- Každá cena prenájmu sa vypočíta ako `cena DPMK × 0,97`.
- Výsledok sa zobrazí na dve desatinné miesta v eurách.
- Všetky ceny budú označené ako ceny bez DPH.
- Pri každom reklamnom formáte sa zobrazí dočasná cena `Polep: 100,00 € bez DPH`.
- Text upozorní, že dostupnosť konkrétneho vozidla alebo umiestnenia a konečná ponuka sa potvrdia individuálne.
- DPH sa nebude pripočítavať do zobrazenej sumy; stránka uvedie aktuálnu sadzbu 23 %.

## Dáta a komponenty

- Obsah a pôvodné ceny budú v samostatnom typovanom dátovom module.
- Čistá pomocná funkcia vykoná zľavu 3 % a zaokrúhlenie na centy.
- Stránka bude renderovaná staticky v Astro bez nového backend endpointu.
- Karty budú používať lokálne obrázky pripravené zo schém v PDF DPMK. Dočasný hero obrázok bude stiahnutý lokálne z licenčne použiteľného zdroja a nebude hotlinkovaný.
- Prepínanie alebo kotvy medzi kategóriami musia fungovať bez závislosti od JavaScriptu; prípadné progresívne vylepšenie nesmie skryť obsah.

## Responzivita a prístupnosť

- Desktop: dvoj- až trojstĺpcový katalóg podľa dostupnej šírky.
- Mobil: jedna karta na riadok a horizontálne posúvateľná tabuľka iba tam, kde sa ceny nedajú čitateľne zalomiť.
- Obrázky budú mať popisné slovenské `alt` texty.
- CTA, odkazy a prípadné ovládanie kategórií budú dostupné klávesnicou a budú mať viditeľný focus stav.
- Všetok používateľský text bude v slovenčine.

## SEO

- Vlastný title, description, canonical URL a Open Graph obrázok.
- Stránka bude pridaná do sitemap.
- Štruktúrované dáta budú používať existujúce site/layout mechanizmy; nepridá sa nový verejný API kontrakt.

## Overenie

- Test výpočtu zľavy a zaokrúhlenia sa napíše pred implementáciou funkcie.
- `npm run check` a `npm run build` musia prejsť.
- Výsledná stránka sa vizuálne skontroluje na desktopovej a mobilnej šírke vrátane hero, kariet, tabuliek, CTA a navigácie.
- Skontroluje sa, že všetky formáty a obdobia z oboch zdrojových cenníkov sú prítomné a že zobrazené ceny zodpovedajú presne 97 % zdrojovej ceny.
