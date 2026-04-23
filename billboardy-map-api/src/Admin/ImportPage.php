<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Admin;

use Billboardy\MapApi\Database\Schema;
use Billboardy\MapApi\Import\AdSpaceImporter;
use Billboardy\MapApi\Plugin;

final class ImportPage
{
    private const MENU_SLUG = 'billboardy-map-import';

    public function registerMenu(): void
    {
        add_options_page(
            __('Billboardy Map Import', 'billboardy-map-api'),
            __('Billboardy Map Import', 'billboardy-map-api'),
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

        Schema::install();
        $result = null;
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billboardy_map_import_nonce'])) {
            [$result, $error] = $this->handleSubmit();
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Billboardy Map Import', 'billboardy-map-api'); ?></h1>
            <p><?php echo esc_html__('Import normalized advertising-space data into the dedicated map table.', 'billboardy-map-api'); ?></p>

            <?php if ($error !== '') : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <?php if (is_array($result)) : ?>
                <div class="notice notice-success">
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            'Read: %d. Created: %d. Updated: %d. Skipped: %d. Missing coordinates: %d. Errors: %d.',
                            (int) $result['read'],
                            (int) $result['created'],
                            (int) $result['updated'],
                            (int) $result['skipped'],
                            (int) $result['missing_coordinates'],
                            (int) $result['errors']
                        ));
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('billboardy_map_import', 'billboardy_map_import_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="billboardy-import-source"><?php echo esc_html__('Source type', 'billboardy-map-api'); ?></label>
                        </th>
                        <td>
                            <select id="billboardy-import-source" name="source_type">
                                <option value="kmz_csv"><?php echo esc_html__('KMZ normalized CSV', 'billboardy-map-api'); ?></option>
                                <option value="woocommerce_csv"><?php echo esc_html__('WooCommerce CSV', 'billboardy-map-api'); ?></option>
                                <option value="knosic_blb"><?php echo esc_html__('Knosic BLB XLSX', 'billboardy-map-api'); ?></option>
                                <option value="knosic_clv"><?php echo esc_html__('Knosic CLV XLSX', 'billboardy-map-api'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Choose the format/source of the uploaded file.', 'billboardy-map-api'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="billboardy-import-file"><?php echo esc_html__('Import file', 'billboardy-map-api'); ?></label>
                        </th>
                        <td>
                            <input id="billboardy-import-file" type="file" name="import_file" accept=".csv,.xlsx" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Options', 'billboardy-map-api'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="dry_run" value="1" checked />
                                <?php echo esc_html__('Dry run: parse and validate without writing to database.', 'billboardy-map-api'); ?>
                            </label>
                            <br />
                            <label>
                                <input type="checkbox" name="replace_source" value="1" />
                                <?php echo esc_html__('Replace records from this source before import.', 'billboardy-map-api'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Import', 'billboardy-map-api')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @return array{0: array<string, int|string>|null, 1: string}
     */
    private function handleSubmit(): array
    {
        if (!wp_verify_nonce((string) $_POST['billboardy_map_import_nonce'], 'billboardy_map_import')) {
            return [null, __('Invalid import request.', 'billboardy-map-api')];
        }

        if (!isset($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
            return [null, __('No import file was uploaded.', 'billboardy-map-api')];
        }

        $file = $_FILES['import_file'];

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, __('The upload failed.', 'billboardy-map-api')];
        }

        $sourceType = sanitize_key((string) ($_POST['source_type'] ?? 'kmz_csv'));
        $allowed = ['kmz_csv', 'woocommerce_csv', 'knosic_blb', 'knosic_clv'];

        if (!in_array($sourceType, $allowed, true)) {
            return [null, __('Unsupported source type.', 'billboardy-map-api')];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [null, __('The uploaded file is not readable.', 'billboardy-map-api')];
        }

        $dryRun = !empty($_POST['dry_run']);
        $replaceSource = !empty($_POST['replace_source']);
        try {
            $result = (new AdSpaceImporter())->import($tmpName, $sourceType, $replaceSource, $dryRun);
        } catch (\Throwable $error) {
            return [null, $error->getMessage()];
        }

        if (!$dryRun) {
            $current = (int) get_option(Plugin::OPTION_CACHE_VERSION, 1);
            update_option(Plugin::OPTION_CACHE_VERSION, (string) ($current + 1), false);
        }

        return [$result, ''];
    }
}
