<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Service;

final class InquiryItemSanitizer
{
    /**
     * @param mixed $items
     * @return array<int, array<string, string>>
     */
    public function sanitize($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $sanitized = [];

        foreach (array_slice($items, 0, 20) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = sanitize_text_field((string) ($item['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $code = sanitize_text_field((string) ($item['code'] ?? ''));
            $locationLabel = sanitize_text_field((string) ($item['locationLabel'] ?? ''));
            $title = sanitize_text_field((string) ($item['title'] ?? ''));

            if ($title === '') {
                $title = implode(' - ', array_filter([$code, $locationLabel]));
            }

            if ($title === '') {
                $title = $id;
            }

            $sanitized[] = [
                'id' => $id,
                'code' => $code,
                'title' => $title,
                'mediaTypeLabel' => sanitize_text_field((string) ($item['mediaTypeLabel'] ?? '')),
                'locationLabel' => $locationLabel,
                'sizeLabel' => sanitize_text_field((string) ($item['sizeLabel'] ?? '')),
            ];
        }

        return $sanitized;
    }
}
