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

test("catalog retains every official rental surface, period, and source price", () => {
  assert.deepEqual(
    BUS_OFFERS.map(({ slug, prices }) => ({ slug, prices })),
    [
      { slug: "celoplosny-polep-kratky-autobus", prices: [{ period: "1 mesiac", sourcePrice: 720 }, { period: "3 mesiace", sourcePrice: 612 }, { period: "6 mesiacov", sourcePrice: 576 }, { period: "12 mesiacov", sourcePrice: 540 }] },
      { slug: "celoplosny-polep-dlhy-autobus", prices: [{ period: "1 mesiac", sourcePrice: 820 }, { period: "3 mesiace", sourcePrice: 697 }, { period: "6 mesiacov", sourcePrice: 656 }, { period: "12 mesiacov", sourcePrice: 615 }] },
      { slug: "busboard-xxl", prices: [{ period: "1 mesiac", sourcePrice: 450 }, { period: "3 mesiace", sourcePrice: 382.5 }, { period: "6 mesiacov", sourcePrice: 360 }, { period: "12 mesiacov", sourcePrice: 337.5 }] },
      { slug: "busboard-xl", prices: [{ period: "1 mesiac", sourcePrice: 370 }, { period: "3 mesiace", sourcePrice: 314.5 }, { period: "6 mesiacov", sourcePrice: 296 }, { period: "12 mesiacov", sourcePrice: 277.5 }] },
      { slug: "busboard-l", prices: [{ period: "1 mesiac", sourcePrice: 180 }, { period: "3 mesiace", sourcePrice: 153 }, { period: "6 mesiacov", sourcePrice: 144 }, { period: "12 mesiacov", sourcePrice: 135 }] },
      { slug: "doorboard", prices: [{ period: "1 mesiac", sourcePrice: 180 }, { period: "3 mesiace", sourcePrice: 153 }, { period: "6 mesiacov", sourcePrice: 144 }, { period: "12 mesiacov", sourcePrice: 135 }] },
      { slug: "bocna-samolepka-220x60", prices: [{ period: "1 mesiac", sourcePrice: 100 }, { period: "2 mesiace", sourcePrice: 95 }, { period: "3 mesiace", sourcePrice: 90 }, { period: "6 mesiacov", sourcePrice: 85 }, { period: "12 mesiacov", sourcePrice: 80 }] },
      { slug: "bocna-samolepka-150x30", prices: [{ period: "1 mesiac", sourcePrice: 60 }, { period: "2 mesiace", sourcePrice: 57 }, { period: "3 mesiace", sourcePrice: 54 }, { period: "6 mesiacov", sourcePrice: 51 }, { period: "12 mesiacov", sourcePrice: 48 }] },
      { slug: "bocna-samolepka-130x30", prices: [{ period: "1 mesiac", sourcePrice: 30 }] },
      { slug: "zadna-plocha-okno", prices: [{ period: "1 mesiac", sourcePrice: 100 }, { period: "3 mesiace", sourcePrice: 90 }, { period: "6 mesiacov", sourcePrice: 85 }, { period: "12 mesiacov", sourcePrice: 80 }] },
      { slug: "zadna-plocha-cela", prices: [{ period: "1 mesiac", sourcePrice: 200 }, { period: "3 mesiace", sourcePrice: 180 }, { period: "6 mesiacov", sourcePrice: 170 }, { period: "12 mesiacov", sourcePrice: 160 }] },
    ],
  );

  assert.deepEqual(
    TRAM_OFFERS.map(({ slug, prices }) => ({ slug, prices })),
    [
      { slug: "celoplosny-polep-elektricka", prices: [{ period: "1 mesiac", sourcePrice: 920 }, { period: "3 mesiace", sourcePrice: 782 }, { period: "6 mesiacov", sourcePrice: 736 }, { period: "12 mesiacov", sourcePrice: 690 }] },
      { slug: "e-board-xxl", prices: [{ period: "1 mesiac", sourcePrice: 550 }, { period: "3 mesiace", sourcePrice: 467.5 }, { period: "6 mesiacov", sourcePrice: 440 }, { period: "12 mesiacov", sourcePrice: 412.5 }] },
    ],
  );

  assert.deepEqual(SIT_RENTALS, [
    { title: "TV A", period: "1 mesiac", sourcePrice: 31 },
    { title: "TV B", period: "1 mesiac", sourcePrice: 28.5 },
    { title: "VO A", period: "1 mesiac", sourcePrice: 21.5 },
    { title: "VO B", period: "1 mesiac", sourcePrice: 20.5 },
    { title: "VO C", period: "1 mesiac", sourcePrice: 11.5 },
  ]);

  assert.deepEqual(FLAG_RENTALS, [
    { title: "VO A", period: "1 mesiac", sourcePrice: 42.5 },
    { title: "VO B", period: "1 mesiac", sourcePrice: 31 },
    { title: "VO A", period: "6 mesiacov", sourcePrice: 215 },
    { title: "VO B", period: "6 mesiacov", sourcePrice: 155 },
    { title: "VO A", period: "12 mesiacov", sourcePrice: 375 },
    { title: "VO B", period: "12 mesiacov", sourcePrice: 275 },
  ]);
});
