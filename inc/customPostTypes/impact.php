<?php
namespace Flynt\CustomPostTypes;

function registerImpactPostType(): void
{
    $labels = [
        'name'                  => _x('Impact', 'Post Type General Name', 'flynt'),
        'singular_name'         => _x('Impact', 'Post Type Singular Name', 'flynt'),
        'menu_name'             => __('Impact', 'flynt'),
        'name_admin_bar'        => __('Impact', 'flynt'),
        'archives'              => __('Impact Archives', 'flynt'),
        'attributes'            => __('Impact Attributes', 'flynt'),
        'parent_item_colon'     => __('Parent Item:', 'flynt'),
        'all_items'             => __('All Impact', 'flynt'),
        'add_new_item'          => __('Add New Impact', 'flynt'),
        'add_new'               => __('Add New', 'flynt'),
        'new_item'              => __('New Impact', 'flynt'),
        'edit_item'             => __('Edit Impact', 'flynt'),
        'update_item'           => __('Update Impact', 'flynt'),
        'view_item'             => __('View Impact', 'flynt'),
        'view_items'            => __('View Impacts', 'flynt'),
        'search_items'          => __('Search Impact', 'flynt'),
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
        'label'               => __('Impact', 'flynt'),
        'description'         => __('Impact Description', 'flynt'),
        'labels'              => $labels,
        'supports'            => ['title','thumbnail', 'excerpt', 'revisions', 'custom-fields'],
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'rewrite' => ['slug' => 'impact', 'with_front' => false], 
    ];

    register_post_type('impact', $args);
}
add_action('init', 'Flynt\CustomPostTypes\registerImpactPostType');
