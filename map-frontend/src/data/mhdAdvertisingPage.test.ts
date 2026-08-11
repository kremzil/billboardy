import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import path from "node:path";

const frontendRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");

test("built MHD page exposes the full offer and SEO contract", async () => {
  const html = await readFile(path.join(frontendRoot, "dist/reklama-na-mhd-kosice/index.html"), "utf8");
  const sitemap = await readFile(path.join(frontendRoot, "dist/sitemap.xml"), "utf8");
  const requiredCopy = [
    "Reklama na autobusoch a električkách v Košiciach",
    "Reklama v košickej MHD — prenájom plôch na vozidlách a v uliciach",
    "Kľúčové výhody",
    "Ponuka MHD reklamy",
    "Technické parametre",
    "Záujem o reklamu v MHD?",
    "Celoplošný polep — krátky autobus",
    "Busboard XXL",
    "Zadná plocha — celá",
    "E-board XXL",
    "Smerové informačno-navádzacie tabule",
    "Kategória ulíc",
    "Americká trieda",
    "Sadové stĺpy (do 6 m)",
    "VLAJKA",
    "698,40 €",
    "Polep: 100,00 € bez DPH",
    "Reklama na autobusoch v Košiciach ponúka mobilné reklamné plochy",
    "Reklama na električkách patrí medzi výrazné formáty mestskej reklamy",
    "Reklama na stĺpoch verejného osvetlenia dopĺňa mobilné formáty MHD",
  ];
  for (const copy of requiredCopy) assert.ok(html.includes(copy), `Missing built copy: ${copy}`);
  assert.equal((html.match(/Polep: 100,00 € bez DPH/g) ?? []).length, 13);
  assert.equal((html.match(/data-category-tab=/g) ?? []).length, 3);
  assert.match(html, /id="ponuka-mhd"/);
  assert.match(html, /href="#autobusy"/);
  assert.match(html, /href="#elektricky"/);
  assert.match(html, /href="#verejne-osvetlenie"/);
  assert.match(html, /data-mhd-catalog-nav[^>]*data-visible="false"[^>]*aria-hidden="true"[^>]*aria-label="Kategórie reklamy v MHD"[^>]*fixed[^>]*top-\[121px\]/);
  assert.match(html, /data-category-tab="A"[^>]*aria-selected="true"|aria-selected="true"[^>]*data-category-tab="A"/);
  assert.match(html, /street-category-list[^"\n]*auto-rows-max[^"\n]*content-start/);
  assert.doesNotMatch(html, /Informácie k cenám/);
  assert.equal((html.match(/href="mailto:obchod@kpkreklama\.sk"/g) ?? []).length, 2);
  assert.match(html, /<link rel="canonical" href="https:\/\/www\.billboardy\.sk\/reklama-na-mhd-kosice\/"/);
  assert.match(html, /<nav[^>]*aria-label="Navigačná cesta"/);
  assert.match(html, /"@type":"BreadcrumbList"/);
  assert.match(html, /"@type":"Service"[^}]*"name":"Reklama v MHD Košice"/);
  assert.match(html, /"@type":"OfferCatalog"[^}]*"name":"Reklamné plochy v MHD Košice"/);
  assert.match(html, /Cenník aktualizovaný k dátumu zostavenia: \d{1,2}\. \d{1,2}\. \d{4}/);
  assert.doesNotMatch(html, /o 3 %|3 % nižšie/i);
  assert.match(html, /href="\/reklama-na-mhd-kosice\/"[^>]*aria-current="page"/);
  assert.match(sitemap, /reklama-na-mhd-kosice\//);
});

test("built site links to the MHD landing page contextually", async () => {
  const home = await readFile(path.join(frontendRoot, "dist/index.html"), "utf8");

  assert.match(home, /href="\/reklama-na-mhd-kosice\/"[^>]*>[\s\S]*?Pozrieť reklamu v MHD Košice/);
  assert.ok(
    (home.match(/href="\/reklama-na-mhd-kosice\/"/g) ?? []).length >= 3,
    "Expected links from navigation, homepage content and footer",
  );
});
