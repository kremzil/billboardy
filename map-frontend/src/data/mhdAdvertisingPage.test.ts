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
  ];
  for (const copy of requiredCopy) assert.ok(html.includes(copy), `Missing built copy: ${copy}`);
  assert.equal((html.match(/data-category-tab=/g) ?? []).length, 3);
  assert.match(html, /data-category-tab="A"[^>]*aria-selected="true"|aria-selected="true"[^>]*data-category-tab="A"/);
  assert.doesNotMatch(html, /Informácie k cenám/);
  assert.equal((html.match(/href="mailto:obchod@kpkreklama\.sk"/g) ?? []).length, 2);
  assert.match(html, /<link rel="canonical" href="https:\/\/www\.billboardy\.sk\/reklama-na-mhd-kosice\/"/);
  assert.match(html, /href="\/reklama-na-mhd-kosice\/"[^>]*aria-current="page"/);
  assert.match(sitemap, /reklama-na-mhd-kosice\//);
});
