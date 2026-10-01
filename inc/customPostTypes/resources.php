<?php

namespace Flynt\CustomPostTypes;

use Timber\Attachment;
use Timber\Post;

const RESOURCE_POST_TYPE = 'resource';
const RESOURCE_TAXONOMY = 'research_area';
const RESOURCE_TYPE_TAXONOMY = 'resource_type';

function registerResourcePostType(): void
{
    $labels = [
        'name'                  => _x('Resources', 'Post Type General Name', 'flynt'),
        'singular_name'         => _x('Resource', 'Post Type Singular Name', 'flynt'),
        'menu_name'             => __('Resources', 'flynt'),
        'name_admin_bar'        => __('Resource', 'flynt'),
        'archives'              => __('Resource Archives', 'flynt'),
        'attributes'            => __('Resource Attributes', 'flynt'),
        'all_items'             => __('All Resources', 'flynt'),
        'add_new_item'          => __('Add New Resource', 'flynt'),
        'add_new'               => __('Add New', 'flynt'),
        'new_item'              => __('New Resource', 'flynt'),
        'edit_item'             => __('Edit Resource', 'flynt'),
        'update_item'           => __('Update Resource', 'flynt'),
        'view_item'             => __('View Resource', 'flynt'),
        'view_items'            => __('View Resources', 'flynt'),
        'search_items'          => __('Search Resources', 'flynt'),
        'not_found'             => __('No resources found', 'flynt'),
        'not_found_in_trash'    => __('No resources found in Trash', 'flynt'),
        'featured_image'        => __('Card Image', 'flynt'),
        'set_featured_image'    => __('Set card image', 'flynt'),
        'remove_featured_image' => __('Remove card image', 'flynt'),
        'use_featured_image'    => __('Use as card image', 'flynt'),
        'insert_into_item'      => __('Insert into resource', 'flynt'),
        'uploaded_to_this_item' => __('Uploaded to this resource', 'flynt'),
        'items_list'            => __('Resources list', 'flynt'),
        'items_list_navigation' => __('Resources list navigation', 'flynt'),
        'filter_items_list'     => __('Filter resources list', 'flynt'),
    ];

    register_post_type(RESOURCE_POST_TYPE, [
        'label'               => __('Resources', 'flynt'),
        'description'         => __('Downloadable files and externally hosted resources.', 'flynt'),
        'labels'              => $labels,
        'supports'            => ['title', 'revisions', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'],
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-media-document',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => true,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'rewrite'             => [
            'slug'       => 'outputs/resource',
            'with_front' => false,
        ],
    ]);

    register_taxonomy_for_object_type(RESOURCE_TAXONOMY, RESOURCE_POST_TYPE);
    register_taxonomy(RESOURCE_TYPE_TAXONOMY, [RESOURCE_POST_TYPE], [
        'labels' => [
            'name' => __('Resource Types', 'flynt'),
            'singular_name' => __('Resource Type', 'flynt'),
            'menu_name' => __('Resource Types', 'flynt'),
            'all_items' => __('All Resource Types', 'flynt'),
            'edit_item' => __('Edit Resource Type', 'flynt'),
            'add_new_item' => __('Add Resource Type', 'flynt'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

add_action('init', __NAMESPACE__ . '\\registerResourcePostType', 11);

/**
 * Return the file or external destination configured for a Resource.
 */
function getResourceDestination(int $postId): array
{
    $destinationType = get_field('resourceDestinationType', $postId) ?: 'file';
    $openNewTab = (bool) get_field('resourceOpenNewTab', $postId);
    $label = trim((string) get_field('resourceCtaLabel', $postId));
    $destination = [
        'type' => $destinationType,
        'url' => '',
        'target' => $openNewTab ? '_blank' : '_self',
        'rel' => $openNewTab ? 'noopener noreferrer' : '',
        'label' => $label,
        'fileName' => '',
        'fileSize' => '',
        'mimeType' => '',
    ];

    if ($destinationType === 'external') {
        $destination['url'] = (string) get_field('resourceExternalUrl', $postId);
        $destination['label'] = $label ?: __('View resource', 'flynt');
        return $destination;
    }

    $file = get_field('resourceFile', $postId);
    if ($file instanceof Attachment) {
        $destination['url'] = $file->src();
        $destination['fileName'] = basename($file->file());
        $destination['mimeType'] = (string) get_post_mime_type($file->ID);
        $fileSize = (int) $file->size();
        $destination['fileSize'] = $fileSize > 0 ? size_format($fileSize, 1) : '';
    } elseif (is_numeric($file)) {
        $file = wp_prepare_attachment_for_js((int) $file);
    }

    if (is_array($file)) {
        $destination['url'] = (string) ($file['url'] ?? '');
        $destination['fileName'] = (string) ($file['filename'] ?? basename($destination['url']));
        $destination['mimeType'] = (string) ($file['mime_type'] ?? '');
        $fileSize = (int) ($file['filesize'] ?? 0);
        $destination['fileSize'] = $fileSize > 0 ? size_format($fileSize, 1) : '';
    }

    $destination['label'] = $label ?: __('Download resource', 'flynt');

    return $destination;
}

/**
 * Build all view data shared by Resource cards and the Resource single.
 */
function getResourceViewData(Post $resource): array
{
    $postId = (int) $resource->ID;
    $cardBehavior = get_field('resourceCardBehavior', $postId) ?: 'direct';
    $destination = getResourceDestination($postId);
    $cardUsesSingle = $cardBehavior === 'single' || empty($destination['url']);
    $terms = get_the_terms($postId, RESOURCE_TAXONOMY);
    $resourceTypes = get_the_terms($postId, RESOURCE_TYPE_TAXONOMY);

    return [
        'post' => $resource,
        'destination' => $destination,
        'cardBehavior' => $cardBehavior,
        'cardLink' => [
            'url' => $cardUsesSingle ? $resource->link() : $destination['url'],
            'target' => $cardUsesSingle ? '_self' : $destination['target'],
            'rel' => $cardUsesSingle ? '' : $destination['rel'],
        ],
        'researchAreas' => is_array($terms) ? $terms : [],
        'resourceTypes' => is_array($resourceTypes) ? $resourceTypes : [],
        'descriptionHtml' => (string) get_field('resourceDescription', $postId),
    ];
}

add_filter('admin_post_thumbnail_html', function (string $content, int $postId): string {
    if (get_post_type($postId) !== RESOURCE_POST_TYPE) {
        return $content;
    }

    return $content . '<p>' . esc_html__('Used as the image on Resource cards and the optional Resource detail page.', 'flynt') . '</p>';
}, 10, 2);
add_filter('manage_resource_posts_columns', function (array $columns): array {
    $columns['resource_card_behavior'] = __('Card opens', 'flynt');
    $columns['resource_destination'] = __('Destination', 'flynt');
    return $columns;
});

add_action('manage_resource_posts_custom_column', function (string $column, int $postId): void {
    if ($column === 'resource_card_behavior') {
        $behavior = get_field('resourceCardBehavior', $postId) ?: 'direct';
        echo esc_html($behavior === 'single' ? __('Resource page', 'flynt') : __('Destination directly', 'flynt'));
    }

    if ($column === 'resource_destination') {
        $type = get_field('resourceDestinationType', $postId) ?: 'file';
        echo esc_html($type === 'external' ? __('External URL', 'flynt') : __('Media Library file', 'flynt'));
    }
}, 10, 2);

add_action('restrict_manage_posts', function (string $postType): void {
    if ($postType !== RESOURCE_POST_TYPE) {
        return;
    }

    $selected = isset($_GET[RESOURCE_TAXONOMY])
        ? sanitize_title(wp_unslash($_GET[RESOURCE_TAXONOMY]))
        : '';

    wp_dropdown_categories([
        'show_option_all' => __('All research areas', 'flynt'),
        'taxonomy' => RESOURCE_TAXONOMY,
        'name' => RESOURCE_TAXONOMY,
        'orderby' => 'name',
        'selected' => $selected,
        'hierarchical' => true,
        'hide_empty' => false,
        'value_field' => 'slug',
    ]);
});
