<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Database;

final class Schema
{
    public const VERSION_OPTION = 'billboardy_map_api_schema_version';
    public const VERSION = '1.1.0';

    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'billboardy_ad_spaces';
    }

    public static function inquiryLogTableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'billboardy_inquiry_logs';
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::tableName();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL,
            source_id VARCHAR(100) NOT NULL,
            code VARCHAR(100) NOT NULL,
            media_type VARCHAR(50) NOT NULL DEFAULT 'unknown',
            media_type_label VARCHAR(100) NOT NULL DEFAULT '',
            title VARCHAR(255) NOT NULL DEFAULT '',
            location_label TEXT NULL,
            city VARCHAR(120) NOT NULL DEFAULT '',
            region VARCHAR(120) NOT NULL DEFAULT '',
            district VARCHAR(120) NOT NULL DEFAULT '',
            address_text TEXT NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            size_label VARCHAR(100) NOT NULL DEFAULT '',
            width_cm INT UNSIGNED NULL,
            height_cm INT UNSIGNED NULL,
            image_url TEXT NULL,
            thumbnail_url TEXT NULL,
            gallery_json LONGTEXT NULL,
            description_html LONGTEXT NULL,
            excerpt TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            visibility VARCHAR(30) NOT NULL DEFAULT 'visible',
            raw_payload LONGTEXT NULL,
            source_updated_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY source_source_id (source, source_id),
            KEY code (code),
            KEY media_type (media_type),
            KEY city (city),
            KEY status (status),
            KEY lat_lng (latitude, longitude),
            KEY updated_at (updated_at)
        ) {$charset};";

        dbDelta($sql);

        $inquiryLogTable = self::inquiryLogTableName();
        $inquiryLogSql = "CREATE TABLE {$inquiryLogTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            name VARCHAR(255) NOT NULL DEFAULT '',
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(100) NOT NULL DEFAULT '',
            company VARCHAR(255) NOT NULL DEFAULT '',
            type_format VARCHAR(255) NOT NULL DEFAULT '',
            note TEXT NULL,
            details_json LONGTEXT NULL,
            items_json LONGTEXT NULL,
            recipient_email VARCHAR(255) NOT NULL DEFAULT '',
            subject VARCHAR(255) NOT NULL DEFAULT '',
            error_message TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY source (source),
            KEY email (email),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta($inquiryLogSql);
        update_option(self::VERSION_OPTION, self::VERSION, false);
    }
}
