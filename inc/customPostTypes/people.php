<?php

namespace Flynt\CustomPostTypes;

const PERSON_POST_TYPE = 'person';
const PERSON_GROUP_TAXONOMY = 'person_group';
const PERSON_ROLE_GROUP_TAXONOMY = 'person_role_group';

add_action('init', function (): void {
    register_post_type(PERSON_POST_TYPE, [
        'labels' => [
            'name' => __('People', 'flynt'),
            'singular_name' => __('Person', 'flynt'),
            'menu_name' => __('People', 'flynt'),
            'all_items' => __('All People', 'flynt'),
            'add_new_item' => __('Add Person', 'flynt'),
            'edit_item' => __('Edit Person', 'flynt'),
            'view_item' => __('View Person', 'flynt'),
            'search_items' => __('Search People', 'flynt'),
            'not_found' => __('No people found', 'flynt'),
            'featured_image' => __('Portrait', 'flynt'),
            'set_featured_image' => __('Set portrait', 'flynt'),
            'remove_featured_image' => __('Remove portrait', 'flynt'),
        ],
        'description' => __('Team members, collaborators and advisory group members.', 'flynt'),
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-groups',
        'show_in_rest' => true,
        'show_in_nav_menus' => true,
        'has_archive' => false,
        'rewrite' => [
            // Preserve the existing staff profile URLs for the majority of profiles.
            'slug' => 'staff',
            'with_front' => false,
        ],
    ]);

    register_taxonomy(PERSON_GROUP_TAXONOMY, [PERSON_POST_TYPE], [
        'labels' => [
            'name' => __('People Groups', 'flynt'),
            'singular_name' => __('People Group', 'flynt'),
            'menu_name' => __('People Groups', 'flynt'),
            'all_items' => __('All People Groups', 'flynt'),
            'edit_item' => __('Edit People Group', 'flynt'),
            'add_new_item' => __('Add People Group', 'flynt'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);

    register_taxonomy(PERSON_ROLE_GROUP_TAXONOMY, [PERSON_POST_TYPE], [
        'labels' => [
            'name' => __('Role Groups', 'flynt'),
            'singular_name' => __('Role Group', 'flynt'),
            'menu_name' => __('Role Groups', 'flynt'),
            'all_items' => __('All Role Groups', 'flynt'),
            'edit_item' => __('Edit Role Group', 'flynt'),
            'add_new_item' => __('Add Role Group', 'flynt'),
        ],
        'description' => __('Broad role categories used to filter the people directory.', 'flynt'),
        'hierarchical' => true,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'meta_box_cb' => false,
        'query_var' => false,
        'rewrite' => false,
    ]);
});
