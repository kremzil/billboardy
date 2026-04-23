<?php

declare(strict_types=1);

namespace Billboardy\MapApi;

use Billboardy\MapApi\Admin\SettingsPage;
use Billboardy\MapApi\Admin\ImportPage;
use Billboardy\MapApi\Database\Schema;
use Billboardy\MapApi\Domain\AdSpaceMapper;
use Billboardy\MapApi\Repository\DatabaseAdSpaceRepository;
use Billboardy\MapApi\Repository\HybridAdSpaceRepository;
use Billboardy\MapApi\Repository\WooCommerceAdSpaceRepository;
use Billboardy\MapApi\Rest\AdSpaceApiController;
use Billboardy\MapApi\Service\AdSpaceService;

final class Plugin
{
    public const OPTION_CACHE_VERSION = 'billboardy_map_api_cache_version';

    private AdSpaceService $service;

    public function __construct()
    {
        $mapper = new AdSpaceMapper();
        $repository = new HybridAdSpaceRepository(new DatabaseAdSpaceRepository(), new WooCommerceAdSpaceRepository());
        $this->service = new AdSpaceService($repository, $mapper);
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('admin_menu', [new SettingsPage(), 'registerMenu']);
        add_action('admin_menu', [new ImportPage(), 'registerMenu']);
        add_action('admin_init', [new SettingsPage(), 'registerSettings']);
        add_action('admin_init', [$this, 'ensureSchema']);
        add_action('admin_post_billboardy_map_clear_cache', [$this, 'clearCacheAction']);
        add_action('admin_post_billboardy_map_warm_cache', [$this, 'warmCacheAction']);

        add_action('save_post_product', [$this, 'invalidateCache']);
        add_action('deleted_post', [$this, 'invalidateCache']);
        add_action('trashed_post', [$this, 'invalidateCache']);
        add_action('set_object_terms', [$this, 'invalidateCache']);
        add_action('added_post_meta', [$this, 'invalidateCache']);
        add_action('updated_post_meta', [$this, 'invalidateCache']);
        add_action('deleted_post_meta', [$this, 'invalidateCache']);
    }

    public function registerRestRoutes(): void
    {
        $controller = new AdSpaceApiController($this->service);
        $controller->registerRoutes();
    }

    public function ensureSchema(): void
    {
        if (get_option(Schema::VERSION_OPTION) !== Schema::VERSION) {
            Schema::install();
        }
    }

    /**
     * Dynamic cache keys include this version, so invalidation does not need
     * brittle SQL deletes against transient names.
     */
    public function invalidateCache(): void
    {
        $current = (int) get_option(self::OPTION_CACHE_VERSION, 1);
        update_option(self::OPTION_CACHE_VERSION, (string) ($current + 1), false);
    }

    public function clearCacheAction(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to clear this cache.', 'billboardy-map-api'));
        }

        check_admin_referer('billboardy_map_clear_cache');
        $this->invalidateCache();

        wp_safe_redirect(add_query_arg(
            'billboardy_cache_cleared',
            '1',
            admin_url('options-general.php?page=billboardy-map-api')
        ));
        exit;
    }

    public function warmCacheAction(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to warm this cache.', 'billboardy-map-api'));
        }

        check_admin_referer('billboardy_map_warm_cache');
        $count = $this->warmCommonMapCache();

        wp_safe_redirect(add_query_arg(
            'billboardy_cache_warmed',
            (string) $count,
            admin_url('options-general.php?page=billboardy-map-api')
        ));
        exit;
    }

    private function warmCommonMapCache(): int
    {
        $views = [
            ['north' => 49.7, 'south' => 47.6, 'east' => 22.8, 'west' => 16.7, 'zoom' => 7],
            ['north' => 49.7, 'south' => 47.6, 'east' => 18.8, 'west' => 16.7, 'zoom' => 8],
            ['north' => 49.7, 'south' => 47.6, 'east' => 20.8, 'west' => 18.0, 'zoom' => 8],
            ['north' => 49.7, 'south' => 47.6, 'east' => 22.8, 'west' => 20.0, 'zoom' => 8],
            ['north' => 48.5, 'south' => 47.8, 'east' => 17.6, 'west' => 16.7, 'zoom' => 10],
            ['north' => 49.1, 'south' => 48.4, 'east' => 21.7, 'west' => 20.6, 'zoom' => 10],
        ];

        foreach ($views as $params) {
            $this->service->mapPoints($params);
        }

        return count($views);
    }
}
