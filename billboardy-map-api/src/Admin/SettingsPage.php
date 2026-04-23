<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Admin;

final class SettingsPage
{
    public const OPTION_NAME = 'billboardy_map_api_settings';

    public static function defaults(): array
    {
        return [
            'placeholder_image_url' => '',
            'cache_ttl' => 600,
            'allowed_frontend_origins' => '',
        ];
    }

    public static function get(): array
    {
        $settings = get_option(self::OPTION_NAME, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        return array_merge(self::defaults(), $settings);
    }

    public function registerMenu(): void
    {
        add_options_page(
            __('Billboardy Map API', 'billboardy-map-api'),
            __('Billboardy Map API', 'billboardy-map-api'),
            'manage_options',
            'billboardy-map-api',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('billboardy_map_api', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    public function sanitize($value): array
    {
        if (!is_array($value)) {
            $value = [];
        }

        $defaults = self::defaults();

        return [
            'placeholder_image_url' => isset($value['placeholder_image_url'])
                ? esc_url_raw((string) $value['placeholder_image_url'])
                : $defaults['placeholder_image_url'],
            'cache_ttl' => isset($value['cache_ttl'])
                ? max(60, min(86400, absint($value['cache_ttl'])))
                : $defaults['cache_ttl'],
            'allowed_frontend_origins' => isset($value['allowed_frontend_origins'])
                ? sanitize_textarea_field((string) $value['allowed_frontend_origins'])
                : $defaults['allowed_frontend_origins'],
        ];
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Billboardy Map API', 'billboardy-map-api'); ?></h1>
            <p><?php echo esc_html__('Backend REST API for the standalone Astro map frontend.', 'billboardy-map-api'); ?></p>
            <?php if (isset($_GET['billboardy_cache_cleared'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Map API cache was cleared.', 'billboardy-map-api'); ?></p>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['billboardy_cache_warmed'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        printf(
                            esc_html__('Map API cache was warmed for %d common views.', 'billboardy-map-api'),
                            absint($_GET['billboardy_cache_warmed'])
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('billboardy_map_api'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="billboardy-placeholder-image"><?php echo esc_html__('Placeholder image URL', 'billboardy-map-api'); ?></label>
                        </th>
                        <td>
                            <input
                                id="billboardy-placeholder-image"
                                class="regular-text"
                                type="url"
                                name="<?php echo esc_attr(self::OPTION_NAME); ?>[placeholder_image_url]"
                                value="<?php echo esc_attr((string) $settings['placeholder_image_url']); ?>"
                            />
                            <p class="description"><?php echo esc_html__('Used when a WooCommerce product has no main image.', 'billboardy-map-api'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="billboardy-cache-ttl"><?php echo esc_html__('Cache TTL in seconds', 'billboardy-map-api'); ?></label>
                        </th>
                        <td>
                            <input
                                id="billboardy-cache-ttl"
                                class="small-text"
                                type="number"
                                min="60"
                                max="86400"
                                name="<?php echo esc_attr(self::OPTION_NAME); ?>[cache_ttl]"
                                value="<?php echo esc_attr((string) $settings['cache_ttl']); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="billboardy-frontend-origins"><?php echo esc_html__('Allowed frontend origins', 'billboardy-map-api'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="billboardy-frontend-origins"
                                class="large-text code"
                                rows="4"
                                name="<?php echo esc_attr(self::OPTION_NAME); ?>[allowed_frontend_origins]"
                            ><?php echo esc_textarea((string) $settings['allowed_frontend_origins']); ?></textarea>
                            <p class="description"><?php echo esc_html__('Optional newline-separated origins for a separately hosted Astro frontend, for example https://mapa.billboardy.sk.', 'billboardy-map-api'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr />
            <h2><?php echo esc_html__('Cache', 'billboardy-map-api'); ?></h2>
            <p><?php echo esc_html__('Clear cached map API responses after imports or data cleanup.', 'billboardy-map-api'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="billboardy_map_clear_cache" />
                <?php wp_nonce_field('billboardy_map_clear_cache'); ?>
                <?php submit_button(__('Clear map cache', 'billboardy-map-api'), 'secondary'); ?>
            </form>
            <p><?php echo esc_html__('Warm common Slovakia map views so the first visitor does not pay the full query cost.', 'billboardy-map-api'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="billboardy_map_warm_cache" />
                <?php wp_nonce_field('billboardy_map_warm_cache'); ?>
                <?php submit_button(__('Warm map cache', 'billboardy-map-api'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }
}
