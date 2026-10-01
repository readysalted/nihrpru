<?php

namespace Flynt\CustomPostTypes;

function registerResearchPostType(): void
{
    $labels = [
        'name' => _x('Projects', 'Post Type General Name', 'flynt'),
        'singular_name' => _x('Project', 'Post Type Singular Name', 'flynt'),
        'menu_name' => __('Projects', 'flynt'),
        'name_admin_bar' => __('Project', 'flynt'),
        'archives' => __('Project Archives', 'flynt'),
        'attributes' => __('Project Attributes', 'flynt'),
        'parent_item_colon' => __('Parent Project:', 'flynt'),
        'all_items' => __('All Projects', 'flynt'),
        'add_new_item' => __('Add New Project', 'flynt'),
        'add_new' => __('Add New', 'flynt'),
        'new_item' => __('New Project', 'flynt'),
        'edit_item' => __('Edit Project', 'flynt'),
        'update_item' => __('Update Project', 'flynt'),
        'view_item' => __('View Project', 'flynt'),
        'view_items' => __('View Projects', 'flynt'),
        'search_items' => __('Search Projects', 'flynt'),
        'not_found' => __('No projects found', 'flynt'),
        'not_found_in_trash' => __('No projects found in Trash', 'flynt'),
        'featured_image' => __('Project Image', 'flynt'),
        'set_featured_image' => __('Set project image', 'flynt'),
        'remove_featured_image' => __('Remove project image', 'flynt'),
        'use_featured_image' => __('Use as project image', 'flynt'),
    ];

    register_post_type('research', [
        'label' => __('Projects', 'flynt'),
        'description' => __('NIHR PRU research projects.', 'flynt'),
        'labels' => $labels,
        'supports' => ['title', 'revisions', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'],
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-portfolio',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'can_export' => true,
        // The editable /our-projects/ page owns the archive URL.
        'has_archive' => false,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'page',
        'rewrite' => [
            'slug' => 'our-projects',
            'with_front' => false,
            'hierarchical' => true,
        ],
    ]);
}

function registerResearchAreaTaxonomy(): void
{
    register_taxonomy('research_area', ['research', 'resource'], [
        'labels' => [
            'name' => __('Research Areas', 'flynt'),
            'singular_name' => __('Research Area', 'flynt'),
            'menu_name' => __('Research Areas', 'flynt'),
            'all_items' => __('All Research Areas', 'flynt'),
            'edit_item' => __('Edit Research Area', 'flynt'),
            'add_new_item' => __('Add Research Area', 'flynt'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

function registerResearchTagsTaxonomy(): void
{
    register_taxonomy('research_tag', ['research'], [
        'labels' => [
            'name' => __('Project Tags', 'flynt'),
            'singular_name' => __('Project Tag', 'flynt'),
            'menu_name' => __('Project Tags', 'flynt'),
            'all_items' => __('All Project Tags', 'flynt'),
            'edit_item' => __('Edit Project Tag', 'flynt'),
            'add_new_item' => __('Add Project Tag', 'flynt'),
        ],
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

function registerProjectStatusTaxonomy(): void
{
    register_taxonomy('project_status', ['research'], [
        'labels' => [
            'name' => __('Project Statuses', 'flynt'),
            'singular_name' => __('Project Status', 'flynt'),
            'menu_name' => __('Project Statuses', 'flynt'),
            'all_items' => __('All Project Statuses', 'flynt'),
            'edit_item' => __('Edit Project Status', 'flynt'),
            'add_new_item' => __('Add Project Status', 'flynt'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

function registerProjectTopicTaxonomy(): void
{
    register_taxonomy('project_topic', ['research'], [
        'labels' => [
            'name' => __('Project Topics', 'flynt'),
            'singular_name' => __('Project Topic', 'flynt'),
            'menu_name' => __('Project Topics', 'flynt'),
            'all_items' => __('All Project Topics', 'flynt'),
            'edit_item' => __('Edit Project Topic', 'flynt'),
            'add_new_item' => __('Add Project Topic', 'flynt'),
        ],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'query_var' => true,
        'rewrite' => false,
    ]);
}

add_action('init', function (): void {
    registerResearchPostType();
    registerResearchAreaTaxonomy();
    registerResearchTagsTaxonomy();
    registerProjectStatusTaxonomy();
    registerProjectTopicTaxonomy();
});

add_action('after_switch_theme', 'flush_rewrite_rules');
