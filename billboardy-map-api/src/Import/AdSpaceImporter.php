<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Import;

use Billboardy\MapApi\Database\Schema;

final class AdSpaceImporter
{
    /**
     * @return array<string, int|string>
     */
    public function import(string $filePath, string $sourceType, bool $replaceSource, bool $dryRun): array
    {
        $rows = $this->readRows($filePath, $sourceType);
        $stats = [
            'read' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing_coordinates' => 0,
            'errors' => 0,
            'source' => $sourceType,
        ];

        if ($replaceSource && !$dryRun) {
            $this->deleteSource($sourceType);
        }

        foreach ($rows as $row) {
            $stats['read']++;
            $record = $this->normalizeRow($row, $sourceType);

            if ($record === null) {
                $stats['skipped']++;
                continue;
            }

            if ($record['latitude'] === null || $record['longitude'] === null) {
                $stats['missing_coordinates']++;
            }

            if ($dryRun) {
                $stats['created']++;
                continue;
            }

            $result = $this->upsert($record);

            if ($result === 'created') {
                $stats['created']++;
            } elseif ($result === 'updated') {
                $stats['updated']++;
            } else {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readRows(string $filePath, string $sourceType): array
    {
        if (preg_match('/\.xlsx$/i', $filePath) || in_array($sourceType, ['knosic_blb', 'knosic_clv'], true)) {
            return $this->readXlsx($filePath);
        }

        return $this->readCsv($filePath);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            return [];
        }

        $sample = (string) fread($handle, 4096);
        rewind($handle);
        $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimiter);

        if (!is_array($headers)) {
            fclose($handle);
            return [];
        }

        $headers = array_map(static function ($header): string {
            return trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, $headers);

        $rows = [];

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readXlsx(string $filePath): array
    {
        if (!class_exists('\ZipArchive')) {
            throw new \RuntimeException('PHP ZipArchive extension is required to import XLSX files.');
        }

        $zip = new \ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('The XLSX file could not be opened.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!is_string($sheetXml) || $sheetXml === '') {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);

        if (!$xml) {
            return [];
        }

        $matrix = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $rowIndex = max(0, ((int) $rowNode['r']) - 1);
            $matrix[$rowIndex] = $matrix[$rowIndex] ?? [];

            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $column = $this->columnIndex($ref);
                $type = (string) $cell['t'];
                $value = isset($cell->v) ? (string) $cell->v : '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }

                $matrix[$rowIndex][$column] = $value;
            }
        }

        ksort($matrix);
        $headers = array_map('trim', array_values($matrix[0] ?? []));
        $rows = [];

        foreach ($matrix as $index => $values) {
            if ($index === 0) {
                continue;
            }

            $row = [];

            foreach ($headers as $column => $header) {
                $row[$header] = $values[$column] ?? null;
            }

            if (array_filter($row, static fn($value): bool => $value !== null && trim((string) $value) !== '') !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(\ZipArchive $zip): array
    {
        $xmlText = $zip->getFromName('xl/sharedStrings.xml');

        if (!is_string($xmlText) || $xmlText === '') {
            return [];
        }

        $xml = simplexml_load_string($xmlText);

        if (!$xml) {
            return [];
        }

        $strings = [];

        foreach ($xml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/i', $cellRef, $matches);
        $letters = strtoupper($matches[1] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeRow(array $row, string $sourceType): ?array
    {
        if ($sourceType === 'knosic_blb' || $sourceType === 'knosic_clv') {
            return $this->normalizeKnosicRow($row, $sourceType);
        }

        if (isset($row['Katalógové číslo']) || isset($row['Meta: _gps'])) {
            return $this->normalizeWooCommerceCsvRow($row);
        }

        return $this->normalizeMapCsvRow($row, $sourceType);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeMapCsvRow(array $row, string $sourceType): ?array
    {
        $sourceId = $this->idText($row['id'] ?? $row['sourceId'] ?? $row['code'] ?? '');
        $code = $this->idText($row['code'] ?? $sourceId);
        $mediaType = $this->mediaType($this->text($row['mediaType'] ?? $row['mediaTypeRaw'] ?? ''));
        $mediaLabel = $this->text($row['mediaTypeLabel'] ?? $row['mediaTypeRaw'] ?? $this->mediaTypeLabel($mediaType));

        if ($sourceId === '' && $code === '') {
            return null;
        }

        return $this->baseRecord([
            'source' => $sourceType,
            'source_id' => $sourceId !== '' ? $sourceId : $code,
            'code' => $code,
            'media_type' => $mediaType,
            'media_type_label' => $mediaLabel,
            'title' => $this->text($row['title'] ?? ''),
            'location_label' => $this->text($row['locationLabel'] ?? ''),
            'city' => $this->text($row['city'] ?? ''),
            'address_text' => $this->text($row['locationLabel'] ?? ''),
            'latitude' => $this->floatOrNull($row['latitude'] ?? null),
            'longitude' => $this->floatOrNull($row['longitude'] ?? null),
            'size_label' => $this->text($row['sizeLabel'] ?? $row['rawSize'] ?? ''),
            'width_cm' => $this->intOrNull($row['widthCm'] ?? null),
            'height_cm' => $this->intOrNull($row['heightCm'] ?? null),
            'image_url' => $this->text($row['imageUrl'] ?? ''),
            'thumbnail_url' => $this->text($row['imageUrl'] ?? ''),
            'raw_payload' => wp_json_encode($row, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeWooCommerceCsvRow(array $row): ?array
    {
        $id = $this->idText($row['ID'] ?? '');
        $code = $this->idText($row['Katalógové číslo'] ?? $id);

        if ($id === '' && $code === '') {
            return null;
        }

        $gps = $this->parseGps($this->text($row['Meta: _gps'] ?? ''));
        $location = $this->cleanLocation($this->text($row['Krátky popis'] ?? ''));
        $images = array_filter(array_map('trim', explode(',', $this->text($row['Obrázky'] ?? ''))));
        $mediaLabel = $this->firstCategory($this->text($row['Kategórie'] ?? ''));

        return $this->baseRecord([
            'source' => 'woocommerce_csv',
            'source_id' => $id !== '' ? $id : $code,
            'code' => $code,
            'media_type' => $this->mediaType($mediaLabel),
            'media_type_label' => $mediaLabel,
            'title' => $this->text($row['Meno'] ?? ''),
            'location_label' => $location,
            'city' => $this->cityFromLocation($location),
            'address_text' => $location,
            'latitude' => $gps['latitude'],
            'longitude' => $gps['longitude'],
            'size_label' => $this->sizeFromText($this->text($row['Popis'] ?? '')),
            'image_url' => $images[0] ?? '',
            'thumbnail_url' => $images[0] ?? '',
            'gallery_json' => wp_json_encode(array_values($images), JSON_UNESCAPED_UNICODE),
            'description_html' => wp_kses_post((string) ($row['Popis'] ?? '')),
            'excerpt' => $location,
            'visibility' => $this->text($row['Viditeľnosť v katalógu'] ?? 'visible'),
            'raw_payload' => wp_json_encode($row, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeKnosicRow(array $row, string $sourceType): ?array
    {
        $sourceId = $this->idText($row['e_cis'] ?? '');

        if ($sourceId === '') {
            return null;
        }

        $mediaType = $sourceType === 'knosic_blb' ? 'billboard' : 'citylight';
        $mediaTypeLabel = $mediaType === 'billboard' ? 'Billboard' : 'Citylight';
        $street = $this->text($row['ul_cis'] ?? '');
        $detail = $this->text($row['ul_cis1'] ?? '');
        $city = $this->text($row['mesto'] ?? '');
        $location = trim(implode(', ', array_filter([$street, $detail, $city])));

        return $this->baseRecord([
            'source' => $sourceType,
            'source_id' => $sourceId,
            'code' => $sourceId,
            'media_type' => $mediaType,
            'media_type_label' => $mediaTypeLabel,
            'title' => trim($sourceId . ' - ' . $location),
            'location_label' => $location,
            'city' => $city,
            'region' => $this->text($row['kraj'] ?? ''),
            'district' => $this->text($row['okres'] ?? ''),
            'address_text' => $location,
            'latitude' => $this->floatOrNull($row['vgs84_n'] ?? null),
            'longitude' => $this->floatOrNull($row['vgs84_eo'] ?? null),
            'size_label' => $this->sizeLabelFromKnosic($this->text($row['velikost'] ?? ''), $mediaType),
            'raw_payload' => wp_json_encode($row, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function baseRecord(array $data): array
    {
        $now = current_time('mysql', true);
        $code = $this->text($data['code'] ?? '');
        $location = $this->text($data['location_label'] ?? '');
        $mediaLabel = $this->text($data['media_type_label'] ?? '');
        $title = $this->text($data['title'] ?? '');

        if ($title === '') {
            $title = trim(implode(' - ', array_filter([$code, $location])));
        }

        if ($title === '') {
            $title = trim($mediaLabel . ' ' . $code);
        }

        return array_merge([
            'source' => 'manual',
            'source_id' => $code,
            'code' => $code,
            'media_type' => 'unknown',
            'media_type_label' => $mediaLabel !== '' ? $mediaLabel : 'Neznáme',
            'title' => $title,
            'location_label' => $location,
            'city' => '',
            'region' => '',
            'district' => '',
            'address_text' => $location,
            'latitude' => null,
            'longitude' => null,
            'size_label' => '',
            'width_cm' => null,
            'height_cm' => null,
            'image_url' => '',
            'thumbnail_url' => '',
            'gallery_json' => '',
            'description_html' => '',
            'excerpt' => $location,
            'status' => 'active',
            'visibility' => 'visible',
            'raw_payload' => '',
            'source_updated_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function upsert(array $record): string
    {
        global $wpdb;

        $table = Schema::tableName();
        $existingId = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE source = %s AND source_id = %s LIMIT 1",
            $record['source'],
            $record['source_id']
        ));

        if ($existingId) {
            unset($record['created_at']);
            $updated = $wpdb->update($table, $record, ['id' => (int) $existingId]);

            return $updated === false ? 'error' : 'updated';
        }

        $inserted = $wpdb->insert($table, $record);

        return $inserted === false ? 'error' : 'created';
    }

    private function deleteSource(string $sourceType): void
    {
        global $wpdb;

        $table = Schema::tableName();
        $wpdb->delete($table, ['source' => $sourceType]);
    }

    private function text($value): string
    {
        return trim((string) $value);
    }

    private function idText($value): string
    {
        $text = $this->text($value);

        if (preg_match('/^\d+\.0$/', $text)) {
            return substr($text, 0, -2);
        }

        return $text;
    }

    private function floatOrNull($value): ?float
    {
        $value = str_replace(',', '.', $this->text($value));

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function intOrNull($value): ?int
    {
        $value = $this->text($value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array{latitude: float|null, longitude: float|null}
     */
    private function parseGps(string $gps): array
    {
        if (preg_match('/(-?\d+(?:[.,]\d+)?)\s*[,;]\s*(-?\d+(?:[.,]\d+)?)/u', $gps, $matches)) {
            return [
                'latitude' => $this->floatOrNull($matches[1]),
                'longitude' => $this->floatOrNull($matches[2]),
            ];
        }

        return ['latitude' => null, 'longitude' => null];
    }

    private function mediaType(string $raw): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);

        if (strpos($value, 'billboard') !== false || $value === 'blb' || $value === '24') {
            return 'billboard';
        }

        if (strpos($value, 'bigboard') !== false) {
            return 'bigboard';
        }

        if (strpos($value, 'city') !== false || preg_match('/\bcl[a-z0-9_+-]*\b/', $value)) {
            return 'citylight';
        }

        if (strpos($value, 'mega') !== false) {
            return 'mega';
        }

        if (strpos($value, 'facade') !== false || strpos($value, 'fasad') !== false || strpos($value, 'fasád') !== false) {
            return 'facade';
        }

        if (strpos($value, 'plachta') !== false || strpos($value, 'banner') !== false || strpos($value, 'mesh') !== false) {
            return 'banner';
        }

        if (strpos($value, 'bridge') !== false || preg_match('/\bmost\b/u', $value) || strpos($value, 'nadjazd') !== false || strpos($value, 'podjazd') !== false) {
            return 'bridge';
        }

        return 'unknown';
    }

    private function mediaTypeLabel(string $mediaType): string
    {
        if ($mediaType === 'billboard') {
            return 'Billboard';
        }

        if ($mediaType === 'bigboard') {
            return 'Bigboard';
        }

        if ($mediaType === 'citylight') {
            return 'Citylight';
        }

        if ($mediaType === 'bridge') {
            return 'Most';
        }

        if ($mediaType === 'banner') {
            return 'Plachta';
        }

        if ($mediaType === 'facade') {
            return 'Fasáda';
        }

        if ($mediaType === 'mega') {
            return 'Mega plocha';
        }

        return 'Neznáme';
    }

    private function cleanLocation(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/^\s*v\s+lokalite\s+/iu', '', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return trim($value);
    }

    private function cityFromLocation(string $location): string
    {
        if (preg_match('/Bratislav[a-záäčďéíĺľňóôŕšťúýž\sIVX0-9-]*$/iu', $location)) {
            return 'Bratislava';
        }

        if (preg_match('/,\s*([^,]+)\s*$/u', $location, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function firstCategory(string $categories): string
    {
        $parts = preg_split('/[>,|]/u', $categories) ?: [];

        return trim((string) ($parts[0] ?? ''));
    }

    private function sizeFromText(string $text): string
    {
        if (preg_match('/([0-9]+(?:[,.][0-9]+)?\s*[xX×]\s*[0-9]+(?:[,.][0-9]+)?\s*(?:cm)?)/u', $text, $matches)) {
            return trim(str_replace('cm', ' cm', $matches[1]));
        }

        return '';
    }

    private function sizeLabelFromKnosic(string $size, string $mediaType): string
    {
        if ($mediaType === 'billboard' && $size === '24') {
            return '510 x 240 cm';
        }

        return $size;
    }
}
