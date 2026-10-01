<?php

namespace Flynt\CustomPostTypes;

function registerResearchPostType(): void
{
    $labels = [
        'name'                  => _x('Research', 'Post Type General Name', 'flynt'),
        'singular_name'         => _x('Research', 'Post Type Singular Name', 'flynt'),
        'menu_name'             => __('Research', 'flynt'),
        'name_admin_bar'        => __('Research', 'flynt'),
        'archives'              => __('Research Archives', 'flynt'),
        'attributes'            => __('Research Attributes', 'flynt'),
        'parent_item_colon'     => __('Parent Item:', 'flynt'),
        'all_items'             => __('All Research', 'flynt'),
        'add_new_item'          => __('Add New Research', 'flynt'),
        'add_new'               => __('Add New', 'flynt'),
        'new_item'              => __('New Research', 'flynt'),
        'edit_item'             => __('Edit Research', 'flynt'),
        'update_item'           => __('Update Research', 'flynt'),
        'view_item'             => __('View Research', 'flynt'),
        'view_items'            => __('View Researches', 'flynt'),
        'search_items'          => __('Search Research', 'flynt'),
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
        'label'               => __('Research', 'flynt'),
        'description'         => __('Research Description', 'flynt'),
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
            'slug' => 'research',
            'with_front' => false
        ],
    ];

    register_post_type('research', $args);
}

function registerResearchAreaTaxonomy(): void
{
    $labels = [
        'name'              => _x('Research Areas', 'taxonomy general name', 'flynt'),
        'singular_name'     => _x('Research Area', 'taxonomy singular name', 'flynt'),
        'search_items'      => __('Search Research Areas', 'flynt'),
        'all_items'         => __('All Research Areas', 'flynt'),
        'parent_item'       => __('Parent Research Area', 'flynt'),
        'parent_item_colon' => __('Parent Research Area:', 'flynt'),
        'edit_item'         => __('Edit Research Area', 'flynt'),
        'update_item'       => __('Update Research Area', 'flynt'),
        'add_new_item'      => __('Add New Research Area', 'flynt'),
        'new_item_name'     => __('New Research Area Name', 'flynt'),
        'menu_name'         => __('Research Areas', 'flynt'),
    ];

    $args = [
        'hierarchical'      => true,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => [
            'slug' => 'research',
            'with_front' => false,
            'hierarchical' => true
        ],
    ];

    register_taxonomy('research_area', ['research', 'resource'], $args);
}

function registerResearchTagsTaxonomy(): void
{
    $labels = [
        'name'              => _x('Research Tags', 'taxonomy general name', 'flynt'),
        'singular_name'     => _x('Research Tag', 'taxonomy singular name', 'flynt'),
        'search_items'      => __('Search Research Tags', 'flynt'),
        'all_items'         => __('All Research Tags', 'flynt'),
        'edit_item'         => __('Edit Research Tag', 'flynt'),
        'update_item'       => __('Update Research Tag', 'flynt'),
        'add_new_item'      => __('Add New Research Tag', 'flynt'),
        'new_item_name'     => __('New Research Tag Name', 'flynt'),
        'menu_name'         => __('Research Tags', 'flynt'),
    ];

    $args = [
        'hierarchical'      => false, // This makes it act like tags instead of categories
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'        => true,
        'rewrite'          => [
            'slug' => 'research-tag',
            'with_front' => false
        ],
    ];

    register_taxonomy('research_tag', ['research'], $args);
}

add_action('init', function (): void {
    registerResearchPostType();
    registerResearchAreaTaxonomy();
    registerResearchTagsTaxonomy();
});

add_action('init', function () {
    // Rule for paginated taxonomy pages (e.g., /research/biology/page/2/)
    add_rewrite_rule(
        'research/([^/]+)/page/([0-9]+)/?$',
        'index.php?research_area=$matches[1]&paged=$matches[2]',
        'top'
    );

    // Rule for single research posts (e.g., /research/biology/post-name/)
    add_rewrite_rule(
        'research/([^/]+)/([^/]+)/?$',
        'index.php?research_area=$matches[1]&research=$matches[2]',
        'top'
    );

    // Rule for taxonomy archive (e.g., /research/biology/)
    add_rewrite_rule(
        'research/([^/]+)/?$',
        'index.php?research_area=$matches[1]',
        'top'
    );
}, 11);


add_filter('post_type_link', function ($post_link, $post) {
    if ($post->post_type === 'research') {
        $terms = get_the_terms($post, 'research_area');
        if ($terms && !is_wp_error($terms)) {
            $term = $terms[0];
            return home_url("research/{$term->slug}/{$post->post_name}/");
        }
        // If no term is assigned, fallback to default research archive
        return home_url("research/{$post->post_name}/");
    }
    return $post_link;
}, 10, 2);

add_filter('term_link', function ($termlink, $term, $taxonomy) {
    if ($taxonomy === 'research_area') {
        return home_url("research/{$term->slug}/");
    }
    return $termlink;
}, 10, 3);

register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
