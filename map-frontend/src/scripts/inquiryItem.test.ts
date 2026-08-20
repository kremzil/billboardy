import assert from 'node:assert/strict';
import test from 'node:test';
import { normalizeInquiryItem } from './inquiryItem.ts';

test('restores an empty title from the selected surface data', () => {
  assert.deepEqual(normalizeInquiryItem({
    id: 'db_42',
    code: '70042',
    title: '',
    locationLabel: 'Košice, Hlavná ulica',
  }), {
    id: 'db_42',
    code: '70042',
    title: '70042 - Košice, Hlavná ulica',
    mediaTypeLabel: '',
    locationLabel: 'Košice, Hlavná ulica',
    sizeLabel: '',
    imageUrl: '',
  });
});
