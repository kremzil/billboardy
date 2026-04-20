<?php

declare(strict_types=1);

namespace Billboardy\MapApi;

use Billboardy\MapApi\Admin\SettingsPage;
use Billboardy\MapApi\Domain\AdSpaceMapper;
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
        $repository = new WooCommerceAdSpaceRepository();
        $this->service = new AdSpaceService($repository, $mapper);
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('admin_menu', [new SettingsPage(), 'registerMenu']);
        add_action('admin_init', [new SettingsPage(), 'registerSettings']);

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

    /**
     * Dynamic cache keys include this version, so invalidation does not need
     * brittle SQL deletes against transient names.
     */
    public function invalidateCache(): void
    {
        $current = (int) get_option(self::OPTION_CACHE_VERSION, 1);
        update_option(self::OPTION_CACHE_VERSION, (string) ($current + 1), false);
    }
}

