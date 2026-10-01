<?php

namespace Flynt\Components\GridPublicationArchive;

use Flynt\FieldVariables;
use Flynt\Utils\Options;
use Timber\Timber;

const POST_TYPE = 'publications';
const FILTER_BY_TAXONOMY = 'publication_tag';

add_filter('Flynt/addComponentData?name=GridPublicationArchive', function (array $data): array {
    $data['uuid'] ??= wp_generate_uuid4();
    $postType = POST_TYPE;
    $taxonomy = FILTER_BY_TAXONOMY;

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ]);
    $queriedObject = get_queried_object();
    if (count($terms) > 1) {
        $data['terms'] = array_map(function ($term) use ($queriedObject) {
            $timberTerm = Timber::get_term($term);
            if ($queriedObject->taxonomy ?? null) {
                $timberTerm->isActive = $queriedObject->taxonomy === $term->taxonomy && $queriedObject->term_id === $term->term_id;
            }

            return $timberTerm;
        }, $terms);
    }

    if (is_home()) {
        $data['isHome'] = true;
        $data['title'] = $queriedObject->post_title ?? get_bloginfo('name');
    } else {
        $data['title'] =  'Publications';
        $data['description'] = get_the_archive_description();
    }

    $allPostsArgs = [
        'post_type'      => POST_TYPE,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if (!empty($queriedObject->taxonomy) && !empty($queriedObject->term_id)) {
        $allPostsArgs['tax_query'] = [[
            'taxonomy' => $queriedObject->taxonomy,
            'field'    => 'term_id',
            'terms'    => $queriedObject->term_id,
        ]];
    }

    $allPosts = Timber::get_posts($allPostsArgs);
    $allPostsSorted = is_array($allPosts) ? $allPosts : iterator_to_array($allPosts);
    $perPage     = 10;
    $currentPage = max(1, (int) get_query_var('paged'));
    $totalPosts  = count($allPostsSorted);
    $totalPages  = (int) ceil($totalPosts / $perPage);
    $offset      = ($currentPage - 1) * $perPage;

    $data['posts']      = array_slice($allPostsSorted, $offset, $perPage);
    $data['pagination'] = buildPagination($currentPage, $totalPages);

    return $data;
});

function buildPagination(int $currentPage, int $totalPages): array
{
    if ($totalPages <= 1) {
        return [];
    }
    $delta   = 2;
    $range   = range(max(2, $currentPage - $delta), min($totalPages - 1, $currentPage + $delta));
    $include = array_unique(array_merge([1], $range, [$totalPages]));
    sort($include);

    $pages = [];
    $prev  = null;
    foreach ($include as $num) {
        if ($prev !== null && $num - $prev > 1) {
            $pages[] = ['title' => '...', 'link' => null, 'current' => false];
        }
        $pages[] = [
            'title'   => (string) $num,
            'link'    => $num !== $currentPage ? get_pagenum_link($num) : null,
            'current' => $num === $currentPage,
        ];
        $prev = $num;
    }

    return [
        'pages' => $pages,
        'prev'  => $currentPage > 1 ? ['link' => get_pagenum_link($currentPage - 1)] : null,
        'next'  => $currentPage < $totalPages ? ['link' => get_pagenum_link($currentPage + 1)] : null,
    ];
}

function getACFLayout(): array
{
    return [
        'name' => 'gridPublicationArchive',
        'label' => __('Grid: Publications', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Introduction', 'flynt'),
                'name' => 'preContentHtml',
                'type' => 'wysiwyg',
                'media_upload' => 0,
            ],
            [
                'label' => __('Options', 'flynt'),
                'name' => 'options',
                'type' => 'group',
                'sub_fields' => [FieldVariables\getTheme('white')],
            ],
        ],
    ];
}

Options::addGlobal('GridPublicationArchive', [
    [
        'label' => __('Load More Button?', 'flynt'),
        'name' => 'loadMore',
        'type' => 'true_false',
        'default_value' => 0,
        'ui' => 1
    ],
]);

Options::addTranslatable('GridPublicationArchive', [
    [
        'label' => __('Content', 'flynt'),
        'name' => 'contentTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    ],
    [
        'label' => __('Title', 'flynt'),
        'instructions' => __('Want to add a headline? And a paragraph? Go ahead! Or just leave it empty and nothing will be shown.', 'flynt'),
        'name' => 'preContentHtml',
        'type' => 'wysiwyg',
        'tabs' => 'visual,text',
        'media_upload' => 0,
        'delay' => 0,
    ],
    [
        'label' => __('Labels', 'flynt'),
        'name' => 'labelsTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0
    ],
    [
        'label' => '',
        'name' => 'labels',
        'type' => 'group',
        'sub_fields' => [
            [
                'label' => __('Filter by', 'flynt'),
                'name' => 'filterBy',
                'type' => 'text',
                'default_value' => __('Filter by', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Previous', 'flynt'),
                'name' => 'previous',
                'type' => 'text',
                'default_value' => __('Prev', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Next', 'flynt'),
                'name' => 'next',
                'type' => 'text',
                'default_value' => __('Next', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Load More', 'flynt'),
                'name' => 'loadMore',
                'type' => 'text',
                'default_value' => __('Load More', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('No Posts Found Text', 'flynt'),
                'name' => 'noPostsFound',
                'type' => 'text',
                'default_value' => __('No post found.', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('All Posts', 'flynt'),
                'name' => 'allPosts',
                'type' => 'text',
                'default_value' => __('All', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Reading Time - (20) min read', 'flynt'),
                'instructions' => __('%d is placeholder for number of minutes', 'flynt'),
                'name' => 'readingTime',
                'type' => 'text',
                'default_value' => __('%d min read', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => 50
                ],
            ],
            [
                'label' => __('Read More', 'flynt'),
                'name' => 'readMore',
                'type' => 'text',
                'default_value' => __('Read More', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ]
        ],
    ],
    [
        'label' => __('Options', 'flynt'),
        'name' => 'optionsTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0
    ],
    [
        'label' => '',
        'name' => 'options',
        'type' => 'group',
        'layout' => 'row',
        'sub_fields' => [
            FieldVariables\getTheme()
        ]
    ],
]);
