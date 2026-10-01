<?php

namespace Flynt\Components\GridResourcesArchive;

use Flynt\CustomPostTypes;
use Flynt\FieldVariables;
use Timber\Timber;

const FILTER_QUERY_ARG = 'resource_theme';
const PAGE_QUERY_ARG = 'resource_page';

add_filter('Flynt/addComponentData?name=GridResourcesArchive', function (array $data): array {
    $configuredTerms = $data['filterTerms'] ?? [];
    if ($configuredTerms instanceof \Traversable) {
        $configuredTerms = iterator_to_array($configuredTerms);
    }
    $configuredTerms = is_array($configuredTerms) ? $configuredTerms : [];
    $selectedTerms = getSelectedTerms($configuredTerms);
    $activeSlug = isset($_GET[FILTER_QUERY_ARG])
        ? sanitize_title(wp_unslash($_GET[FILTER_QUERY_ARG]))
        : '';

    $allowedSlugs = wp_list_pluck($selectedTerms, 'slug');
    if ($activeSlug && !in_array($activeSlug, $allowedSlugs, true)) {
        $activeSlug = '';
    }

    $currentPage = isset($_GET[PAGE_QUERY_ARG])
        ? max(1, absint(wp_unslash($_GET[PAGE_QUERY_ARG])))
        : 1;
    $postsPerPage = max(1, min(48, (int) ($data['options']['maxPosts'] ?? 12)));
    $queryArgs = [
        'post_type' => CustomPostTypes\RESOURCE_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $postsPerPage,
        'paged' => $currentPage,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'order' => 'ASC',
        'ignore_sticky_posts' => true,
    ];

    if ($activeSlug) {
        $queryArgs['tax_query'] = [[
            'taxonomy' => CustomPostTypes\RESOURCE_TYPE_TAXONOMY,
            'field' => 'slug',
            'terms' => [$activeSlug],
        ]];
    } elseif ($configuredTerms && $selectedTerms) {
        $queryArgs['tax_query'] = [[
            'taxonomy' => CustomPostTypes\RESOURCE_TYPE_TAXONOMY,
            'field' => 'term_id',
            'terms' => wp_list_pluck($selectedTerms, 'term_id'),
        ]];
    }

    $query = new \WP_Query($queryArgs);
    $posts = Timber::get_posts($query);
    $data['resources'] = [];
    foreach ($posts as $post) {
        $data['resources'][] = CustomPostTypes\getResourceViewData($post);
    }

    $data['terms'] = array_map(function ($term) use ($activeSlug): array {
        return [
            'name' => $term->name,
            'slug' => $term->slug,
            'isActive' => $term->slug === $activeSlug,
            'url' => buildFilterUrl($term->slug),
        ];
    }, $selectedTerms);
    $data['activeSlug'] = $activeSlug;
    $data['allUrl'] = buildFilterUrl('');
    $data['pagination'] = buildPagination($currentPage, (int) $query->max_num_pages, $activeSlug);

    return $data;
});

function getSelectedTerms(array $configuredTerms): array
{
    $allResourceIds = get_posts([
        'post_type' => CustomPostTypes\RESOURCE_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    if (!$allResourceIds) {
        return [];
    }

    $usedTerms = wp_get_object_terms($allResourceIds, CustomPostTypes\RESOURCE_TYPE_TAXONOMY, [
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    if (is_wp_error($usedTerms)) {
        return [];
    }

    if (!$configuredTerms) {
        return $usedTerms;
    }

    $configuredIds = array_map(function ($term): int {
        return is_object($term) ? (int) $term->term_id : (int) $term;
    }, $configuredTerms);
    $byId = [];
    foreach ($usedTerms as $term) {
        $byId[(int) $term->term_id] = $term;
    }

    return array_values(array_filter(array_map(function (int $termId) use ($byId) {
        return $byId[$termId] ?? null;
    }, $configuredIds)));
}
function buildFilterUrl(string $slug): string
{
    $url = remove_query_arg([FILTER_QUERY_ARG, PAGE_QUERY_ARG]);
    return $slug ? add_query_arg(FILTER_QUERY_ARG, $slug, $url) : $url;
}

function buildPagination(int $currentPage, int $totalPages, string $activeSlug): array
{
    if ($totalPages <= 1) {
        return [];
    }

    $numbers = range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2));
    $buildUrl = function (int $page) use ($activeSlug): string {
        $url = remove_query_arg([FILTER_QUERY_ARG, PAGE_QUERY_ARG]);
        if ($activeSlug) {
            $url = add_query_arg(FILTER_QUERY_ARG, $activeSlug, $url);
        }
        return add_query_arg(PAGE_QUERY_ARG, $page, $url);
    };

    return [
        'previous' => $currentPage > 1 ? $buildUrl($currentPage - 1) : '',
        'next' => $currentPage < $totalPages ? $buildUrl($currentPage + 1) : '',
        'pages' => array_map(function (int $page) use ($currentPage, $buildUrl): array {
            return [
                'number' => $page,
                'url' => $buildUrl($page),
                'isCurrent' => $page === $currentPage,
            ];
        }, $numbers),
    ];
}

function getACFLayout(): array
{
    return [
        'name' => 'gridResourcesArchive',
        'label' => __('Grid: Downloadable Resources', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'label' => __('Intro', 'flynt'),
                'name' => 'preContentHtml',
                'type' => 'wysiwyg',
                'tabs' => 'visual,text',
                'media_upload' => 0,
                'delay' => 0,
            ],
            [
                'label' => __('Filter label', 'flynt'),
                'name' => 'filterLabel',
                'type' => 'text',
                'default_value' => __('Resource type:', 'flynt'),
                'required' => 1,
                'wrapper' => ['width' => 50],
            ],
            [
                'label' => __('All resources label', 'flynt'),
                'name' => 'allLabel',
                'type' => 'text',
                'default_value' => __('All', 'flynt'),
                'required' => 1,
                'wrapper' => ['width' => 50],
            ],
            [
                'label' => __('Resource types', 'flynt'),
                'name' => 'filterTerms',
                'type' => 'taxonomy',
                'taxonomy' => CustomPostTypes\RESOURCE_TYPE_TAXONOMY,
                'field_type' => 'multi_select',
                'allow_null' => 1,
                'multiple' => 1,
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'object',
                'instructions' => __('Optional. Leave empty to show every resource type currently used by published Resources. The filter is hidden when only one type is available.', 'flynt'),
            ],
            [
                'label' => __('Options', 'flynt'),
                'name' => 'optionsTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'label' => '',
                'name' => 'options',
                'type' => 'group',
                'layout' => 'row',
                'sub_fields' => [
                    FieldVariables\getTheme('putty'),
                    [
                        'label' => __('Resources per page', 'flynt'),
                        'name' => 'maxPosts',
                        'type' => 'number',
                        'default_value' => 12,
                        'min' => 1,
                        'max' => 48,
                        'step' => 1,
                    ],
                ],
            ],
        ],
    ];
}
