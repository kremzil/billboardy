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
