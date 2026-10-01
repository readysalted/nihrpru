<?php

namespace Flynt\CustomPostTypes;

function registerPublicationsPostType(): void
{
    $labels = [
        'name'                  => _x('Publications', 'Post Type General Name', 'flynt'),
        'singular_name'         => _x('Publication', 'Post Type Singular Name', 'flynt'),
        'menu_name'             => __('Publications', 'flynt'),
        'name_admin_bar'        => __('Publication', 'flynt'),
        'archives'              => __('Publication Archives', 'flynt'),
        'attributes'            => __('Publication Attributes', 'flynt'),
        'parent_item_colon'     => __('Parent Publication:', 'flynt'),
        'all_items'             => __('All Publications', 'flynt'),
        'add_new_item'          => __('Add New Publication', 'flynt'),
        'add_new'               => __('Add New', 'flynt'),
        'new_item'              => __('New Publication', 'flynt'),
        'edit_item'             => __('Edit Publication', 'flynt'),
        'update_item'           => __('Update Publication', 'flynt'),
        'view_item'             => __('View Publication', 'flynt'),
        'view_items'            => __('View Publications', 'flynt'),
        'search_items'          => __('Search Publications', 'flynt'),
        'not_found'             => __('Not found', 'flynt'),
        'not_found_in_trash'    => __('Not found in Trash', 'flynt'),
        'featured_image'        => __('Featured Image', 'flynt'),
        'set_featured_image'    => __('Set featured image', 'flynt'),
        'remove_featured_image' => __('Remove featured image', 'flynt'),
        'use_featured_image'    => __('Use as featured image', 'flynt'),
        'insert_into_item'      => __('Insert into item', 'flynt'),
        'uploaded_to_this_item' => __('Uploaded to this item', 'flynt'),
        'items_list'            => __('Items list', 'flynt'),
        'items_list_navigation' => __('Items list navigation', 'flynt'),
        'filter_items_list'     => __('Filter items list', 'flynt'),
    ];

    $args = [
        'label'               => __('Publications', 'flynt'),
        'description'         => __('Publications Description', 'flynt'),
        'labels'             => $labels,
        'supports'           => ['title', 'revisions', 'thumbnail', 'excerpt', 'custom-fields'],
        'hierarchical'       => false,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 5,
        'show_in_admin_bar'  => true,
        'show_in_nav_menus'  => true,
        'can_export'         => true,
        'has_archive'        => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type'    => 'page',
        'rewrite'           => [
            'slug' => 'publications',
            'with_front' => false
        ],
    ];

    register_post_type('publications', $args);
}

function registerPublicationsAreaTaxonomy(): void
{
    $labels = [
        'name'              => _x('Publication Areas', 'taxonomy general name', 'flynt'),
        'singular_name'     => _x('Publication Area', 'taxonomy singular name', 'flynt'),
        'search_items'      => __('Search Publication Areas', 'flynt'),
        'all_items'         => __('All Publication Areas', 'flynt'),
        'parent_item'       => __('Parent Publication Area', 'flynt'),
        'parent_item_colon' => __('Parent Publication Area:', 'flynt'),
        'edit_item'         => __('Edit Publication Area', 'flynt'),
        'update_item'       => __('Update Publication Area', 'flynt'),
        'add_new_item'      => __('Add New Publication Area', 'flynt'),
        'new_item_name'     => __('New Publication Area Name', 'flynt'),
        'menu_name'         => __('Publication Areas', 'flynt'),
    ];

    $args = [
        'hierarchical'      => true,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => false,
    ];

    register_taxonomy('publication_area', ['publications'], $args);
}

function registerPublicationTagsTaxonomy(): void
{
    $labels = [
        'name'              => _x('Publication Tags', 'taxonomy general name', 'flynt'),
        'singular_name'     => _x('Publication Tag', 'taxonomy singular name', 'flynt'),
        'search_items'      => __('Search Publication Tags', 'flynt'),
        'all_items'         => __('All Publication Tags', 'flynt'),
        'edit_item'         => __('Edit Publication Tag', 'flynt'),
        'update_item'       => __('Update Publication Tag', 'flynt'),
        'add_new_item'      => __('Add New Publication Tag', 'flynt'),
        'new_item_name'     => __('New Publication Tag Name', 'flynt'),
        'menu_name'         => __('Publication Tags', 'flynt'),
    ];

    $args = [
        'hierarchical'      => false, // This makes it act like tags instead of categories
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => [
            'slug' => 'publication-tag',
            'with_front' => false
        ],
    ];

    register_taxonomy('publication_tag', ['publications'], $args);
}

add_action('init', function (): void {
    registerPublicationsPostType();
    registerPublicationsAreaTaxonomy();
    registerPublicationTagsTaxonomy();
});

add_action('init', function () {
    // Rule for paginated taxonomy pages (e.g., /publications/biology/page/2/)
    add_rewrite_rule(
        'publications/([^/]+)/page/([0-9]+)/?$',
        'index.php?publication_area=$matches[1]&paged=$matches[2]',
        'top'
    );

    // Rule for single publication posts (e.g., /publications/biology/post-name/)
    add_rewrite_rule(
        'publications/([^/]+)/([^/]+)/?$',
        'index.php?post_type=publications&name=$matches[2]',
        'top'
    );

    // Rule for taxonomy archive (e.g., /publications/biology/)
    add_rewrite_rule(
        'publications/([^/]+)/?$',
        'index.php?publication_area=$matches[1]',
        'top'
    );
}, 11);


add_filter('request', function (array $query_vars): array {
    if (isset($query_vars['publication_area']) && !isset($query_vars['name'])) {
        $term = get_term_by('slug', $query_vars['publication_area'], 'publication_area');
        if (!$term) {
            $query_vars['post_type'] = 'publications';
            $query_vars['name']      = $query_vars['publication_area'];
            unset($query_vars['publication_area']);
        }
    }
    return $query_vars;
});

add_filter('post_type_link', function ($post_link, $post) {
    if ($post->post_type === 'publications') {
        $terms = get_the_terms($post, 'publication_area');
        if ($terms && !is_wp_error($terms)) {
            $term = $terms[0];
            return home_url("publications/{$term->slug}/{$post->post_name}/");
        }
        // If no term is assigned, fallback to default publications archive
        return home_url("publications/{$post->post_name}/");
    }
    return $post_link;
}, 10, 2);

add_filter('term_link', function($termlink, $term, $taxonomy) {
    if ($taxonomy === 'publication_area') {
        return home_url("publications/{$term->slug}/");
    }
    return $termlink;
}, 10, 3);

add_action('after_switch_theme', 'flush_rewrite_rules');
add_action('switch_theme', 'flush_rewrite_rules');
