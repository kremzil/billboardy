<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Admin;

use Billboardy\MapApi\Repository\InquiryLogRepository;

final class InquiryLogPage
{
    private const MENU_SLUG = 'billboardy-inquiries';
    private const PER_PAGE = 30;

    private InquiryLogRepository $repository;

    public function __construct(InquiryLogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerMenu(): void
    {
        add_options_page(
            __('Dopyty Billboardy', 'billboardy-map-api'),
            __('Dopyty Billboardy', 'billboardy-map-api'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        [$search, $status, $source, $page] = $this->filters();
        $result = $this->repository->search($search, $status, $source, $page, self::PER_PAGE);
        $totalPages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        $exportUrl = wp_nonce_url(add_query_arg([
            'action' => 'billboardy_export_inquiries',
            's' => $search,
            'status' => $status,
            'source' => $source,
        ], admin_url('admin-post.php')), 'billboardy_export_inquiries');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Dopyty Billboardy', 'billboardy-map-api'); ?></h1>
            <p><?php echo esc_html__('Záznamy z kontaktných formulárov sa uchovávajú 180 dní.', 'billboardy-map-api'); ?></p>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::MENU_SLUG); ?>" />
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Meno, e-mail, telefón...', 'billboardy-map-api'); ?>" />
                <select name="status">
                    <?php $this->option('', $status, __('Všetky stavy', 'billboardy-map-api')); ?>
                    <?php $this->option('sent', $status, __('Odoslané', 'billboardy-map-api')); ?>
                    <?php $this->option('failed', $status, __('Neodoslané', 'billboardy-map-api')); ?>
                    <?php $this->option('pending', $status, __('Spracováva sa', 'billboardy-map-api')); ?>
                </select>
                <select name="source">
                    <?php $this->option('', $source, __('Všetky formuláre', 'billboardy-map-api')); ?>
                    <?php $this->option('contact', $source, __('Kontaktný formulár', 'billboardy-map-api')); ?>
                    <?php $this->option('map', $source, __('Dopyt z mapy', 'billboardy-map-api')); ?>
                    <?php $this->option('quick', $source, __('Rýchly dopyt', 'billboardy-map-api')); ?>
                </select>
                <?php submit_button(__('Filtrovať', 'billboardy-map-api'), 'secondary', '', false); ?>
                <a class="button" href="<?php echo esc_url($exportUrl); ?>"><?php echo esc_html__('Exportovať CSV', 'billboardy-map-api'); ?></a>
            </form>

            <p><strong><?php echo esc_html(sprintf(__('Počet záznamov: %d', 'billboardy-map-api'), $result['total'])); ?></strong></p>
            <table class="widefat fixed striped">
                <thead><tr>
                    <th><?php echo esc_html__('Dátum', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Stav', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Formulár', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Meno', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('E-mail', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Telefón', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Spoločnosť', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Typ / formát', 'billboardy-map-api'); ?></th>
                    <th><?php echo esc_html__('Detaily', 'billboardy-map-api'); ?></th>
                </tr></thead>
                <tbody>
                <?php if ($result['rows'] === []) : ?>
                    <tr><td colspan="9"><?php echo esc_html__('Zatiaľ nie sú uložené žiadne dopyty.', 'billboardy-map-api'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($result['rows'] as $row) : ?>
                        <tr>
                            <td><?php echo esc_html((string) $row['created_at']); ?></td>
                            <td><?php echo esc_html($this->statusLabel((string) $row['status'])); ?></td>
                            <td><?php echo esc_html($this->sourceLabel((string) $row['source'])); ?></td>
                            <td><?php echo esc_html((string) $row['name']); ?></td>
                            <td><a href="mailto:<?php echo esc_attr((string) $row['email']); ?>"><?php echo esc_html((string) $row['email']); ?></a></td>
                            <td><?php echo esc_html((string) $row['phone']); ?></td>
                            <td><?php echo esc_html((string) $row['company']); ?></td>
                            <td title="<?php echo esc_attr((string) $row['subject']); ?>"><?php echo esc_html((string) $row['type_format']); ?></td>
                            <td>
                                <?php $details = $this->detailLines($row); ?>
                                <?php if ($details === []) : ?>
                                    —
                                <?php else : ?>
                                    <details>
                                        <summary><?php echo esc_html__('Zobraziť', 'billboardy-map-api'); ?></summary>
                                        <?php foreach ($details as $detail) : ?>
                                            <div><?php echo esc_html($detail); ?></div>
                                        <?php endforeach; ?>
                                    </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1) : ?>
                <div class="tablenav"><div class="tablenav-pages">
                    <?php echo wp_kses_post(paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => min($page, $totalPages),
                        'total' => $totalPages,
                    ])); ?>
                </div></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemáte oprávnenie exportovať dopyty.', 'billboardy-map-api'));
        }

        check_admin_referer('billboardy_export_inquiries');
        [$search, $status, $source] = $this->filters();
        $rows = $this->repository->exportRows($search, $status, $source);

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="billboardy-dopyty-' . gmdate('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'wb');

        if ($output === false) {
            wp_die(esc_html__('CSV export sa nepodarilo vytvoriť.', 'billboardy-map-api'));
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Dátum', 'Stav', 'Formulár', 'Meno', 'E-mail', 'Telefón', 'Spoločnosť', 'Typ / formát', 'Detaily', 'Predmet', 'Chyba'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['created_at'],
                $this->statusLabel((string) $row['status']),
                $this->sourceLabel((string) $row['source']),
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['company'],
                $row['type_format'],
                implode(' | ', $this->detailLines($row)),
                $row['subject'],
                $row['error_message'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: int}
     */
    private function filters(): array
    {
        return [
            sanitize_text_field((string) ($_GET['s'] ?? '')),
            sanitize_key((string) ($_GET['status'] ?? '')),
            sanitize_key((string) ($_GET['source'] ?? '')),
            max(1, absint($_GET['paged'] ?? 1)),
        ];
    }

    private function option(string $value, string $current, string $label): void
    {
        printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($current, $value, false), esc_html($label));
    }

    private function statusLabel(string $status): string
    {
        if ($status === 'sent') {
            return __('Odoslané', 'billboardy-map-api');
        }

        if ($status === 'failed') {
            return __('Neodoslané', 'billboardy-map-api');
        }

        return __('Spracováva sa', 'billboardy-map-api');
    }

    private function sourceLabel(string $source): string
    {
        if ($source === 'contact') {
            return __('Kontaktný formulár', 'billboardy-map-api');
        }

        if ($source === 'quick') {
            return __('Rýchly dopyt', 'billboardy-map-api');
        }

        return __('Dopyt z mapy', 'billboardy-map-api');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function detailLines(array $row): array
    {
        $lines = [];
        $note = trim((string) ($row['note'] ?? ''));

        if ($note !== '') {
            $lines[] = __('Poznámka:', 'billboardy-map-api') . ' ' . $note;
        }

        $details = json_decode((string) ($row['details_json'] ?? ''), true);

        if (is_array($details)) {
            $labels = [
                'region' => __('Región:', 'billboardy-map-api'),
                'budget' => __('Rozpočet:', 'billboardy-map-api'),
                'startDate' => __('Začiatok:', 'billboardy-map-api'),
                'message' => __('Správa:', 'billboardy-map-api'),
            ];

            foreach ($labels as $key => $label) {
                $value = trim((string) ($details[$key] ?? ''));

                if ($value !== '' && ($key !== 'message' || $value !== $note)) {
                    $lines[] = $label . ' ' . $value;
                }
            }
        }

        $items = json_decode((string) ($row['items_json'] ?? ''), true);

        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $title = trim((string) ($item['title'] ?? ''));
                $code = trim((string) ($item['code'] ?? ''));

                if ($title !== '') {
                    $lines[] = __('Plocha:', 'billboardy-map-api') . ' ' . $title . ($code !== '' ? ' (' . $code . ')' : '');
                }
            }
        }

        return $lines;
    }
}
