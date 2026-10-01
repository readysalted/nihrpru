<?php

namespace Flynt\Theme;

use Flynt\Utils\Options;

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');

    /*
     * Remove type attribute from link and script tags.
     */
    add_theme_support('html5', ['script', 'style']);
});

add_filter('big_image_size_threshold', '__return_false');

add_filter('timber/context', function (array $context): array {
    $queriedObjectId = get_queried_object_id();

    $context['theme']->labels = Options::getTranslatable('Theme')['labels'] ?? [];
    $context['siteUrl'] = get_site_url();
    $context['show_block'] = $queriedObjectId > 0
        ? (bool) get_field('show_block', $queriedObjectId)
        : false;
    $context['globalMailingList'] = Options::getGlobal('BlockMailingList');
    $context['googleAnalytics'] = Options::getGlobal('googleAnalytics')['googleScript'] ?? null;
    $showHeaderCta = (bool) get_field('show_header_cta', 'option');
    $headerCta = get_field('header_cta', 'option');
    $context['ctaLink'] = $showHeaderCta && $headerCta ? $headerCta : null;
    return $context;
});

add_filter('bcn_pick_post_term', function ($term, $id, $type, $taxonomy) {
    $primary_term_id = get_post_meta($id, '_yoast_wpseo_primary_' . $taxonomy, true);

    if ($primary_term_id) {
      // get term against the primary category ID.
        $primary_term = get_term($primary_term_id, $taxonomy);

      // Check if it's not an WP_Error, and return the term.
        if (! is_wp_error($primary_term)) {
            return $primary_term;
        }
    }

    return $term;
}, 10, 4);

add_filter('bcn_breadcrumb_title', function ($title, $type) {
    if (is_array($type) && in_array('home', $type, true)) {
        return __('Home', 'flynt');
    }

    return $title;
}, 10, 2);

// add_action('pre_get_posts', function ($query) {
//     if (!is_admin() && $query->is_main_query()) {
//         if (is_home() || is_archive() || is_search()) {
//             $query->set('category__not_in', [264, 265, 266]);
//         }
//     }
// });

add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query()) {
        $current_cat = get_queried_object();

        if (
            (is_home() || is_archive() || is_search())
            && (!is_category([264, 265, 266]))
        ) {
            $query->set('category__not_in', [264, 265, 266]);
        }
    }
});

add_filter('posts_search', function (string $search, \WP_Query $query): string {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return $search;
    }

    $searchTerm = trim((string) $query->get('s'));
    if ($searchTerm === '') {
        return $search;
    }

    global $wpdb;

    $like = '%' . $wpdb->esc_like($searchTerm) . '%';
    $projectIdCondition = $wpdb->prepare(
        "({$wpdb->posts}.post_type = %s AND EXISTS (
            SELECT 1
            FROM {$wpdb->postmeta} spcr_project_meta
            WHERE spcr_project_meta.post_id = {$wpdb->posts}.ID
                AND spcr_project_meta.meta_key = %s
                AND spcr_project_meta.meta_value LIKE %s
        ))",
        'research',
        'projectId',
        $like
    );

    // Keep default password-protected post behavior for logged-out users.
    if (!is_user_logged_in()) {
        $projectIdCondition .= " AND ({$wpdb->posts}.post_password = '')";
    }

    if ($search !== '') {
        $defaultSearchClause = preg_replace('/^\s*AND\s*/i', '', $search, 1);
        return " AND (({$defaultSearchClause}) OR ({$projectIdCondition}))";
    }

    return " AND ({$projectIdCondition})";
}, 10, 2);


Options::addTranslatable('Theme', [
    [
        'label' => __('Labels', 'flynt'),
        'name' => 'labels',
        'type' => 'group',
        'sub_fields' => [
            [
                'label' => __('Feed', 'flynt'),
                'instructions' => __('%s is placeholder for site title.', 'flynt'),
                'name' => 'feed',
                'type' => 'text',
                'default_value' => __('%s Feed', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Skip to main content', 'flynt'),
                'name' => 'skipToMainContent',
                'type' => 'text',
                'default_value' => __('Skip to main content', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Main Content – Aria Label', 'flynt'),
                'name' => 'mainContentAriaLabel',
                'type' => 'text',
                'default_value' => __('Content', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
        ],
    ],
]);
