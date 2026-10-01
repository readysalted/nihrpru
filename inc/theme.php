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

    if ($type === 'person' && $taxonomy === 'person_group') {
        $personGroups = get_the_terms($id, 'person_group');
        if (is_array($personGroups)) {
            $priority = [
                'collaborators',
                'ppie-strategy-group',
                'team',
                'senior-leadership-team',
                'scientific-advisory-board',
                'unit-executive-group',
            ];
            foreach ($priority as $slug) {
                foreach ($personGroups as $personGroup) {
                    if ($personGroup->slug === $slug) {
                        return $personGroup;
                    }
                }
            }
        }
    }

    return $term;
}, 10, 4);

function getPersonGroupLandingPage($term): ?\WP_Post
{
    if (is_numeric($term)) {
        $term = get_term((int) $term, 'person_group');
    }
    if (!$term instanceof \WP_Term || $term->taxonomy !== 'person_group') {
        return null;
    }

    $pageSlugs = [
        'collaborators' => 'our-collaborators',
        'ppie-strategy-group' => 'our-ppie-strategy-group-members',
        'team' => 'our-team',
        'senior-leadership-team' => 'our-team',
        'scientific-advisory-board' => 'scientific-advisory-board',
        'unit-executive-group' => 'management-board',
    ];
    if (!isset($pageSlugs[$term->slug])) {
        return null;
    }

    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'name' => $pageSlugs[$term->slug],
    ]);
    return $pages[0] ?? null;
}

function getPersonGroupBreadcrumbPage(array $type, $id): ?\WP_Post
{
    if (!in_array('taxonomy', $type, true) || !in_array('person_group', $type, true)) {
        return null;
    }
    return getPersonGroupLandingPage($id);
}

add_filter('bcn_breadcrumb_url', function ($url, $type, $id) {
    $page = is_array($type) ? getPersonGroupBreadcrumbPage($type, $id) : null;
    return $page ? get_permalink($page) : $url;
}, 10, 3);

add_filter('bcn_breadcrumb_title', function ($title, $type, $id) {
    if (is_array($type) && in_array('home', $type, true)) {
        return __('Home', 'flynt');
    }

    $page = is_array($type) ? getPersonGroupBreadcrumbPage($type, $id) : null;
    if ($page) {
        return get_the_title($page);
    }

    return $title;
}, 10, 3);

add_action('template_redirect', function (): void {
    if (!is_tax('person_group')) {
        return;
    }

    $page = getPersonGroupLandingPage(get_queried_object());
    if ($page) {
        wp_safe_redirect(get_permalink($page), 301, 'NIHR PRU');
        exit;
    }
}, 1);

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
