export type InquiryItem = {
  id: string;
  code: string;
  title: string;
  mediaTypeLabel: string;
  locationLabel: string;
  sizeLabel: string;
  imageUrl: string;
};

export function normalizeInquiryItem(value: unknown): InquiryItem | null {
  if (!value || typeof value !== 'object') {
    return null;
  }

  const item = value as Record<string, unknown>;
  const id = stringValue(item.id).trim();

  if (id === '') {
    return null;
  }

  const code = stringValue(item.code).trim();
  const locationLabel = stringValue(item.locationLabel).trim();
  const title = stringValue(item.title).trim()
    || [code, locationLabel].filter(Boolean).join(' - ')
    || id;

  return {
    id,
    code,
    title,
    mediaTypeLabel: stringValue(item.mediaTypeLabel).trim(),
    locationLabel,
    sizeLabel: stringValue(item.sizeLabel).trim(),
    imageUrl: stringValue(item.imageUrl).trim(),
  };
}

function stringValue(value: unknown): string {
  return typeof value === 'string' ? value : '';
}
