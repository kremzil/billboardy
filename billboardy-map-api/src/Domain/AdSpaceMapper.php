<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Domain;

use Billboardy\MapApi\Admin\SettingsPage;

final class AdSpaceMapper
{
    private const MEDIA_TYPES = [
        'billboard' => 'Billboard',
        'bigboard' => 'Bigboard',
        'citylight' => 'Citylight',
    ];

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    public function map(array $source): array
    {
        if (!empty($source['ad_space_model'])) {
            return $this->normalizeAdSpaceModel($source);
        }

        $sourceId = (int) ($source['source_id'] ?? 0);
        $rawName = (string) ($source['name'] ?? '');
        $code = $this->extractCode((string) ($source['sku'] ?? ''), $rawName);
        $rawMediaType = $this->extractRawMediaType($source, $rawName);
        $mediaType = $this->normalizeMediaType($rawMediaType);
        $mediaTypeLabel = self::MEDIA_TYPES[$mediaType] ?? $this->titleCase($rawMediaType ?: 'Neznáme');
        $locationLabel = $this->cleanLocation((string) ($source['excerpt'] ?? ''));
        $city = $this->extractCity($locationLabel);
        $coordinates = $this->extractCoordinates((string) ($source['gps'] ?? ''), (string) ($source['description'] ?? ''));
        $size = $this->extractSize((string) ($source['description'] ?? ''));
        $placeholderImageUrl = (string) SettingsPage::get()['placeholder_image_url'];
        $imageUrl = (string) ($source['image_url'] ?? '');

        if ($imageUrl === '') {
            $imageUrl = $placeholderImageUrl;
        }

        return [
            'id' => 'wc_' . $sourceId,
            'sourceId' => $sourceId,
            'code' => $code,
            'title' => $this->buildTitle($code, $locationLabel, $mediaTypeLabel),
            'mediaType' => $mediaType,
            'mediaTypeLabel' => $mediaTypeLabel,
            'locationLabel' => $locationLabel,
            'city' => $city,
            'addressText' => $locationLabel,
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'sizeLabel' => $size['label'],
            'widthCm' => $size['width'],
            'heightCm' => $size['height'],
            'descriptionHtml' => $this->sanitizeDescription((string) ($source['description'] ?? '')),
            'imageUrl' => $imageUrl,
            'status' => $this->status((string) ($source['status'] ?? ''), (string) ($source['visibility'] ?? '')),
            'visibility' => (string) ($source['visibility'] ?? 'visible'),
            'mapPinType' => $mediaType,
            'detailUrl' => null,
            'source' => 'woocommerce',
            'excerpt' => $locationLabel,
            'gallery' => array_values((array) ($source['gallery'] ?? [])),
            'seoText' => null,
            'rawGps' => (string) ($source['gps'] ?? ''),
            'rawSize' => $size['raw'],
            'rawMediaType' => $rawMediaType,
            'isFeatured' => (bool) ($source['featured'] ?? false),
            'updatedAt' => $source['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function normalizeAdSpaceModel(array $source): array
    {
        $placeholderImageUrl = (string) SettingsPage::get()['placeholder_image_url'];
        $imageUrl = (string) ($source['imageUrl'] ?? '');

        if ($imageUrl === '') {
            $imageUrl = $placeholderImageUrl;
        }

        $mediaType = (string) ($source['mediaType'] ?? 'unknown');

        return [
            'id' => (string) ($source['id'] ?? ''),
            'sourceId' => (int) ($source['sourceId'] ?? 0),
            'code' => (string) ($source['code'] ?? ''),
            'title' => (string) ($source['title'] ?? ''),
            'mediaType' => $mediaType,
            'mediaTypeLabel' => (string) ($source['mediaTypeLabel'] ?? (self::MEDIA_TYPES[$mediaType] ?? 'Neznáme')),
            'locationLabel' => (string) ($source['locationLabel'] ?? ''),
            'city' => (string) ($source['city'] ?? ''),
            'addressText' => (string) ($source['addressText'] ?? ''),
            'latitude' => is_float($source['latitude'] ?? null) ? $source['latitude'] : (($source['latitude'] ?? null) === null ? null : (float) $source['latitude']),
            'longitude' => is_float($source['longitude'] ?? null) ? $source['longitude'] : (($source['longitude'] ?? null) === null ? null : (float) $source['longitude']),
            'sizeLabel' => (string) ($source['sizeLabel'] ?? ''),
            'widthCm' => ($source['widthCm'] ?? null) === null ? null : (int) $source['widthCm'],
            'heightCm' => ($source['heightCm'] ?? null) === null ? null : (int) $source['heightCm'],
            'descriptionHtml' => $this->sanitizeDescription((string) ($source['descriptionHtml'] ?? '')),
            'imageUrl' => $imageUrl,
            'status' => (string) ($source['status'] ?? 'active'),
            'visibility' => (string) ($source['visibility'] ?? 'visible'),
            'mapPinType' => (string) ($source['mapPinType'] ?? $mediaType),
            'detailUrl' => $source['detailUrl'] ?? null,
            'source' => (string) ($source['source'] ?? 'database'),
            'excerpt' => (string) ($source['excerpt'] ?? ''),
            'gallery' => array_values((array) ($source['gallery'] ?? [])),
            'seoText' => $source['seoText'] ?? null,
            'rawGps' => (string) ($source['rawGps'] ?? ''),
            'rawSize' => (string) ($source['rawSize'] ?? ''),
            'rawMediaType' => (string) ($source['rawMediaType'] ?? ''),
            'isFeatured' => (bool) ($source['isFeatured'] ?? false),
            'updatedAt' => $source['updatedAt'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $adSpace
     * @return array<string, mixed>|null
     */
    public function mapPoint(array $adSpace): ?array
    {
        if (!is_float($adSpace['latitude']) || !is_float($adSpace['longitude'])) {
            return null;
        }

        return [
            'id' => $adSpace['id'],
            'code' => $adSpace['code'],
            'title' => $adSpace['title'],
            'mediaType' => $adSpace['mediaType'],
            'latitude' => $adSpace['latitude'],
            'longitude' => $adSpace['longitude'],
            'imageUrl' => $adSpace['imageUrl'],
            'locationLabel' => $adSpace['locationLabel'],
            'sizeLabel' => $adSpace['sizeLabel'],
        ];
    }

    private function extractCode(string $sku, string $name): string
    {
        $sku = trim($sku);

        if ($sku !== '') {
            return $sku;
        }

        if (preg_match('/^\s*(\d{2,})\b/u', $name, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractRawMediaType(array $source, string $name): string
    {
        $categories = array_values((array) ($source['categories'] ?? []));

        if (isset($categories[0]) && trim((string) $categories[0]) !== '') {
            return trim((string) $categories[0]);
        }

        if (preg_match('/-\s*([[:alpha:]\s]+)$/u', $name, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function normalizeMediaType(string $raw): string
    {
        $value = $this->lower($raw);

        if (strpos($value, 'billboard') !== false) {
            return 'billboard';
        }

        if (strpos($value, 'bigboard') !== false) {
            return 'bigboard';
        }

        if (strpos($value, 'citylight') !== false || strpos($value, 'city light') !== false || preg_match('/\bcl[a-z0-9_+-]*\b/', $value)) {
            return 'citylight';
        }

        return 'unknown';
    }

    private function cleanLocation(string $excerpt): string
    {
        $text = $this->plainText($excerpt);
        $text = preg_replace('/^\s*v\s+lokalite\s+/iu', '', $text) ?: $text;

        return trim($text);
    }

    private function extractCity(string $location): string
    {
        if (preg_match('/Bratislav[a-záäčďéíĺľňóôŕšťúýž\sIVX0-9]*$/iu', $location)) {
            return 'Bratislava';
        }

        if (preg_match('/,\s*([^,]+)\s*$/u', $location, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * @return array{latitude: float|null, longitude: float|null}
     */
    private function extractCoordinates(string $rawGps, string $description): array
    {
        $source = trim($rawGps);

        if ($source === '' && preg_match('/GPS:\s*(?:<\/strong>)?\s*([-0-9.,\s]+)/iu', $description, $matches)) {
            $source = trim($matches[1]);
        }

        if (preg_match('/(-?\d+(?:[.]\d+)?)\s*[,;]\s*(-?\d+(?:[.]\d+)?)/u', $source, $matches)) {
            return [
                'latitude' => (float) $matches[1],
                'longitude' => (float) $matches[2],
            ];
        }

        return [
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * @return array{label: string, width: int|null, height: int|null, raw: string}
     */
    private function extractSize(string $description): array
    {
        $raw = '';

        if (preg_match('/Rozmer:\s*(?:<\/strong>)?\s*([0-9]+(?:[,.][0-9]+)?\s*[xX×]\s*[0-9]+(?:[,.][0-9]+)?\s*(?:cm)?)/iu', $description, $matches)) {
            $raw = trim($this->plainText($matches[1]));
        }

        if ($raw !== '' && preg_match('/([0-9]+)\s*[xX×]\s*([0-9]+)/u', $raw, $matches)) {
            $width = (int) $matches[1];
            $height = (int) $matches[2];

            return [
                'label' => $width . ' x ' . $height . ' cm',
                'width' => $width,
                'height' => $height,
                'raw' => $raw,
            ];
        }

        return [
            'label' => $raw,
            'width' => null,
            'height' => null,
            'raw' => $raw,
        ];
    }

    private function buildTitle(string $code, string $locationLabel, string $mediaTypeLabel): string
    {
        if ($code !== '' && $locationLabel !== '') {
            return $code . ' - ' . $locationLabel;
        }

        if ($code !== '') {
            return $mediaTypeLabel . ' ' . $code;
        }

        return $locationLabel !== '' ? $locationLabel : $mediaTypeLabel;
    }

    private function sanitizeDescription(string $html): string
    {
        if (function_exists('wp_kses_post')) {
            return wp_kses_post($html);
        }

        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html) ?: '';
    }

    private function status(string $postStatus, string $visibility): string
    {
        return $postStatus === 'publish' && $visibility !== 'hidden' ? 'active' : 'inactive';
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(['\\n', "\r", "\n", "\t"], ' ', $text);
        $text = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($text) : strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?: $text;

        return trim($text);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function titleCase(string $value): string
    {
        $lower = $this->lower(trim($value));

        return function_exists('mb_convert_case') ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8') : ucwords($lower);
    }
}
