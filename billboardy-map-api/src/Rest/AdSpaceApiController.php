<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Rest;

use Billboardy\MapApi\Admin\SettingsPage;
use Billboardy\MapApi\Service\AdSpaceService;

final class AdSpaceApiController
{
    private const NAMESPACE = 'billboardy/v1';

    private AdSpaceService $service;

    public function __construct(AdSpaceService $service)
    {
        $this->service = $service;
    }

    public function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/ad-spaces', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'adSpaces'],
            'permission_callback' => '__return_true',
            'args' => $this->collectionArgs(),
        ]);

        register_rest_route(self::NAMESPACE, '/map-points', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'mapPoints'],
            'permission_callback' => '__return_true',
            'args' => $this->filterArgs(),
        ]);

        register_rest_route(self::NAMESPACE, '/ad-spaces/(?P<id>[a-zA-Z0-9_\\-]+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'adSpace'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/filters', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'filters'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/inquiries', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'inquiry'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function adSpaces(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $this->service->adSpaces($this->requestParams($request));

        return $this->response($payload);
    }

    public function mapPoints(\WP_REST_Request $request): \WP_REST_Response
    {
        return $this->response($this->service->mapPoints($this->requestParams($request)), 300);
    }

    public function adSpace(\WP_REST_Request $request)
    {
        $adSpace = $this->service->adSpace((string) $request->get_param('id'));

        if ($adSpace === null) {
            return new \WP_Error(
                'billboardy_ad_space_not_found',
                __('Advertising space was not found.', 'billboardy-map-api'),
                ['status' => 404]
            );
        }

        return $this->response([
            'data' => $adSpace,
        ]);
    }

    public function filters(): \WP_REST_Response
    {
        return $this->response([
            'data' => $this->service->filters(),
        ], 300);
    }

    public function inquiry(\WP_REST_Request $request)
    {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = $request->get_body_params();
        }

        if (!is_array($payload)) {
            $payload = [];
        }

        if (trim((string) ($payload['website'] ?? '')) !== '') {
            return $this->response(['data' => ['sent' => true]]);
        }

        $name = sanitize_text_field((string) ($payload['name'] ?? ''));
        $email = sanitize_email((string) ($payload['email'] ?? ''));
        $phone = sanitize_text_field((string) ($payload['phone'] ?? ''));
        $company = sanitize_text_field((string) ($payload['company'] ?? ''));
        $note = sanitize_textarea_field((string) ($payload['note'] ?? ''));
        $source = sanitize_key((string) ($payload['source'] ?? 'map'));
        $adType = sanitize_text_field((string) ($payload['adType'] ?? ''));
        $region = sanitize_text_field((string) ($payload['region'] ?? ''));
        $budget = sanitize_text_field((string) ($payload['budget'] ?? ''));
        $startDate = sanitize_text_field((string) ($payload['startDate'] ?? ''));
        $message = sanitize_textarea_field((string) ($payload['message'] ?? ''));
        $items = $this->sanitizeInquiryItems($payload['items'] ?? []);

        if (!in_array($source, ['map', 'contact', 'quick'], true)) {
            $source = 'map';
        }

        $isMapInquiry = $source === 'map';

        if (
            $email === ''
            || !is_email($email)
            || ($source !== 'quick' && $name === '')
            || ($isMapInquiry && ($phone === '' || $items === []))
        ) {
            return new \WP_Error(
                'billboardy_invalid_inquiry',
                __('Inquiry is missing required fields.', 'billboardy-map-api'),
                ['status' => 400]
            );
        }

        $recipient = sanitize_email((string) SettingsPage::get()['inquiry_recipient_email']);

        if ($recipient === '' || !is_email($recipient)) {
            $recipient = sanitize_email((string) get_option('admin_email'));
        }

        $subject = $this->inquirySubject($source, count($items));
        $body = $this->inquiryEmailBody($source, $name, $email, $phone, $company, $note, $items, [
            'adType' => $adType,
            'region' => $region,
            'budget' => $budget,
            'startDate' => $startDate,
            'message' => $message,
        ]);
        $replyName = $name !== '' ? $name : 'Billboardy.sk dopyt';
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $replyName . ' <' . $email . '>',
        ];

        if (!wp_mail($recipient, $subject, $body, $headers)) {
            return new \WP_Error(
                'billboardy_inquiry_not_sent',
                __('Inquiry could not be sent.', 'billboardy-map-api'),
                ['status' => 500]
            );
        }

        return $this->response([
            'data' => [
                'sent' => true,
                'count' => count($items),
            ],
        ]);
    }

    private function response(array $payload, int $publicMaxAge = 0): \WP_REST_Response
    {
        $response = new \WP_REST_Response($payload);
        $origin = $this->allowedOrigin();

        if ($publicMaxAge > 0) {
            $response->header('Cache-Control', 'public, max-age=' . $publicMaxAge . ', stale-while-revalidate=60');
        }

        if ($origin !== '') {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Vary', 'Origin');
        }

        return $response;
    }

    private function allowedOrigin(): string
    {
        $requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw((string) $_SERVER['HTTP_ORIGIN']) : '';

        if ($requestOrigin === '') {
            return '';
        }

        $origins = array_filter(array_map('trim', explode("\n", (string) SettingsPage::get()['allowed_frontend_origins'])));

        return in_array($requestOrigin, $origins, true) ? $requestOrigin : '';
    }

    /**
     * @param mixed $items
     * @return array<int, array<string, string>>
     */
    private function sanitizeInquiryItems($items): array
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
            $title = sanitize_text_field((string) ($item['title'] ?? ''));

            if ($id === '' || $title === '') {
                continue;
            }

            $sanitized[] = [
                'id' => $id,
                'code' => sanitize_text_field((string) ($item['code'] ?? '')),
                'title' => $title,
                'mediaTypeLabel' => sanitize_text_field((string) ($item['mediaTypeLabel'] ?? '')),
                'locationLabel' => sanitize_text_field((string) ($item['locationLabel'] ?? '')),
                'sizeLabel' => sanitize_text_field((string) ($item['sizeLabel'] ?? '')),
            ];
        }

        return $sanitized;
    }

    /**
     * @param array<int, array<string, string>> $items
     */
    private function inquirySubject(string $source, int $itemCount): string
    {
        if ($source === 'quick') {
            return '[Billboardy.sk] Rýchly dopyt z pätičky';
        }

        if ($source === 'contact') {
            return '[Billboardy.sk] Nový kontaktný dopyt';
        }

        return sprintf('[Billboardy.sk] Nový dopyt na %d plôch', $itemCount);
    }

    /**
     * @param array<int, array<string, string>> $items
     * @param array<string, string> $details
     */
    private function inquiryEmailBody(string $source, string $name, string $email, string $phone, string $company, string $note, array $items, array $details): string
    {
        $lines = [
            $source === 'quick' ? 'Rýchly dopyt' : 'Nový dopyt na cenovú ponuku',
            '',
            'Zdroj: ' . $source,
            'Meno: ' . ($name !== '' ? $name : '-'),
            'E-mail: ' . $email,
            'Telefón: ' . ($phone !== '' ? $phone : '-'),
            'Spoločnosť: ' . ($company !== '' ? $company : '-'),
        ];

        if ($source === 'contact') {
            $lines[] = 'Typ nositeľa: ' . ($details['adType'] !== '' ? $details['adType'] : '-');
            $lines[] = 'Kraj / oblasť: ' . ($details['region'] !== '' ? $details['region'] : '-');
            $lines[] = 'Mesačný rozpočet: ' . ($details['budget'] !== '' ? $details['budget'] : '-');
            $lines[] = 'Plánovaný začiatok: ' . ($details['startDate'] !== '' ? $details['startDate'] : '-');
            $lines[] = 'Správa: ' . ($details['message'] !== '' ? $details['message'] : '-');
        }

        $lines[] = 'Poznámka: ' . ($note !== '' ? $note : '-');

        if ($items === []) {
            return implode("\n", $lines);
        }

        $lines[] = '';
        $lines[] = 'Vybrané plochy:';

        foreach ($items as $index => $item) {
            $lines[] = sprintf(
                '%d. %s%s',
                $index + 1,
                $item['title'],
                $item['code'] !== '' ? ' (' . $item['code'] . ')' : ''
            );
            $lines[] = '   Typ: ' . ($item['mediaTypeLabel'] !== '' ? $item['mediaTypeLabel'] : '-');
            $lines[] = '   Lokalita: ' . ($item['locationLabel'] !== '' ? $item['locationLabel'] : '-');
            $lines[] = '   Rozmer: ' . ($item['sizeLabel'] !== '' ? $item['sizeLabel'] : '-');
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collectionArgs(): array
    {
        return array_merge($this->filterArgs(), [
            'page' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 100,
                'minimum' => 1,
                'maximum' => 200,
                'sanitize_callback' => 'absint',
            ],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function filterArgs(): array
    {
        return [
            'media_type' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'city' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'search' => [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'north' => [
                'type' => 'number',
                'required' => false,
                'sanitize_callback' => [$this, 'sanitizeFloat'],
            ],
            'south' => [
                'type' => 'number',
                'required' => false,
                'sanitize_callback' => [$this, 'sanitizeFloat'],
            ],
            'east' => [
                'type' => 'number',
                'required' => false,
                'sanitize_callback' => [$this, 'sanitizeFloat'],
            ],
            'west' => [
                'type' => 'number',
                'required' => false,
                'sanitize_callback' => [$this, 'sanitizeFloat'],
            ],
            'zoom' => [
                'type' => 'integer',
                'default' => 12,
                'minimum' => 1,
                'maximum' => 21,
                'sanitize_callback' => 'absint',
            ],
        ];
    }

    /**
     * WordPress passes value, request and param name to sanitize callbacks.
     *
     * @param mixed $value
     */
    public function sanitizeFloat($value): float
    {
        return (float) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestParams(\WP_REST_Request $request): array
    {
        return [
            'page' => (int) $request->get_param('page'),
            'per_page' => (int) $request->get_param('per_page'),
            'media_type' => (string) $request->get_param('media_type'),
            'city' => (string) $request->get_param('city'),
            'search' => (string) $request->get_param('search'),
            'north' => $request->get_param('north'),
            'south' => $request->get_param('south'),
            'east' => $request->get_param('east'),
            'west' => $request->get_param('west'),
            'zoom' => (int) $request->get_param('zoom'),
        ];
    }
}
