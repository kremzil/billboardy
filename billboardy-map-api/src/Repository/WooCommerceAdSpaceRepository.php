<?php

declare(strict_types=1);

namespace Billboardy\MapApi\Repository;

final class WooCommerceAdSpaceRepository implements AdSpaceRepositoryInterface
{
    public function all(): array
    {
        if (!post_type_exists('product')) {
            return [];
        }

        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => ['publish'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ]);

        $items = [];

        foreach ($query->posts as $productId) {
            $source = $this->find((int) $productId);

            if ($source !== null) {
                $items[] = $source;
            }
        }

        return $items;
    }

    public function find(int $sourceId): ?array
    {
        $post = get_post($sourceId);

        if (!$post instanceof \WP_Post || $post->post_type !== 'product' || $post->post_status !== 'publish') {
            return null;
        }

        $product = function_exists('wc_get_product') ? wc_get_product($sourceId) : null;

        return [
            'source_id' => $sourceId,
            'sku' => $product && method_exists($product, 'get_sku') ? (string) $product->get_sku() : '',
            'name' => $product && method_exists($product, 'get_name') ? (string) $product->get_name() : get_the_title($sourceId),
            'status' => $post->post_status,
            'excerpt' => $post->post_excerpt,
            'description' => $post->post_content,
            'visibility' => $product && method_exists($product, 'get_catalog_visibility') ? (string) $product->get_catalog_visibility() : 'visible',
            'categories' => $this->categories($sourceId),
            'image_url' => $this->mainImageUrl($sourceId, $product),
            'gallery' => $this->galleryUrls($product),
            'gps' => (string) get_post_meta($sourceId, '_gps', true),
            'featured' => $product && method_exists($product, 'get_featured') ? (bool) $product->get_featured() : false,
            'updated_at' => get_post_modified_time(DATE_ATOM, true, $sourceId) ?: null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function categories(int $productId): array
    {
        $terms = get_the_terms($productId, 'product_cat');

        if (!is_array($terms)) {
            return [];
        }

        return array_values(array_map(static function ($term): string {
            return (string) $term->name;
        }, $terms));
    }

    private function mainImageUrl(int $productId, $product): string
    {
        $imageId = $product && method_exists($product, 'get_image_id') ? (int) $product->get_image_id() : (int) get_post_thumbnail_id($productId);

        if ($imageId <= 0) {
            return '';
        }

        $url = wp_get_attachment_image_url($imageId, 'medium_large');

        return $url ? (string) $url : '';
    }

    /**
     * @return array<int, string>
     */
    private function galleryUrls($product): array
    {
        if (!$product || !method_exists($product, 'get_gallery_image_ids')) {
            return [];
        }

        $urls = [];

        foreach ($product->get_gallery_image_ids() as $imageId) {
            $url = wp_get_attachment_image_url((int) $imageId, 'medium_large');

            if ($url) {
                $urls[] = (string) $url;
            }
        }

        return $urls;
    }
}

