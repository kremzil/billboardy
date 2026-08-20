<?php

declare(strict_types=1);

use Billboardy\MapApi\Service\InquiryItemSanitizer;

function sanitize_text_field(string $value): string
{
    return trim(strip_tags($value));
}

require_once dirname(__DIR__) . '/src/Service/InquiryItemSanitizer.php';

$sanitizer = new InquiryItemSanitizer();

$items = $sanitizer->sanitize([[
    'id' => 'db_42',
    'code' => '70042',
    'title' => '',
    'locationLabel' => 'Košice, Hlavná ulica',
]]);

assertSame('70042 - Košice, Hlavná ulica', $items[0]['title'] ?? null, 'empty title fallback');

echo "InquiryItemSanitizerTest: OK\n";

function assertSame(string $expected, ?string $actual, string $case): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf('%s: expected %s, got %s', $case, $expected, (string) $actual));
    }
}
