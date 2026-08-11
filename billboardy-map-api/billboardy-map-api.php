<?php
/**
 * Plugin Name: Billboardy Map API
 * Description: Clean REST API for billboard advertising spaces sourced from WooCommerce products.
 * Version: 0.3.0
 * Author: Billboardy.sk
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Text Domain: billboardy-map-api
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('BILLBOARDY_MAP_API_VERSION', '0.3.0');
define('BILLBOARDY_MAP_API_FILE', __FILE__);
define('BILLBOARDY_MAP_API_PATH', plugin_dir_path(__FILE__));
define('BILLBOARDY_MAP_API_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Billboardy\\MapApi\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BILLBOARDY_MAP_API_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

add_action('plugins_loaded', static function (): void {
    $plugin = new Billboardy\MapApi\Plugin();
    $plugin->register();
});

register_activation_hook(__FILE__, static function (): void {
    Billboardy\MapApi\Database\Schema::install();
});
