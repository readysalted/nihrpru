<?php

namespace Flynt\Components\GridPostsLatest;

use Flynt\FieldVariables;
use Flynt\Utils\Options;
use Timber\Timber;

const DEFAULT_POST_TYPE = 'post';

add_filter('Flynt/addComponentData?name=GridPostsLatest', function (array $data): array {
    $data['uuid'] ??= wp_generate_uuid4();
    $data['taxonomies'] = $data['taxonomies'] ?: [];
    $data['options']['maxColumns'] = 3;
    $postsPerPage = $data['options']['maxPosts'] ?? 3;

    $postType = $data['options']['postType'] ?? DEFAULT_POST_TYPE;
    $taxonomyType = $postType === 'research' ? 'research_area' : 'category';

    $taxQuery = [];
    if (!empty($data['taxonomies'])) {
        $taxQuery[] = [
            'taxonomy' => $taxonomyType,
            'terms' => array_map(function ($taxonomy) {
                return $taxonomy->term_id;
            }, $data['taxonomies']),
        ];
    }

    $posts = Timber::get_posts([
        'post_status' => 'publish',
        'post_type' => $postType,
        'tax_query' => $taxQuery,
        'posts_per_page' => $postsPerPage + 1,
        'ignore_sticky_posts' => 1,
    ]);

    $data['posts'] = array_slice(array_filter($posts->to_array(), function ($post): bool {
        return $post->ID !== get_the_ID();
    }), 0, $postsPerPage);

    $isEventsLayout = $postType === DEFAULT_POST_TYPE && !empty($data['taxonomies']) && !empty(array_filter($data['taxonomies'], function ($taxonomy): bool {
        return isset($taxonomy->slug) && $taxonomy->slug === 'events';
    }));

    $data['isEventsLayout'] = $isEventsLayout;

    if ($isEventsLayout) {
        foreach ($data['posts'] as $post) {
            $eventDate = get_field('events_date', $post->ID);
            if (!$eventDate) {
                continue;
            }

            $dateObj = \DateTime::createFromFormat('F j, Y g:i a', $eventDate);
            if (!$dateObj) {
                continue;
            }

            $eventEndTime = get_field('events_end_time', $post->ID);
            $endDateObj = null;
            if ($eventEndTime) {
                $endDateObj = \DateTime::createFromFormat('Y-m-d g:i a', $dateObj->format('Y-m-d') . ' ' . $eventEndTime);
            }

            $post->eventDate = [
                'full' => $eventDate,
                'day' => $dateObj->format('d'),
                'month' => $dateObj->format('M'),
                'year' => $dateObj->format('Y'),
                'time' => $dateObj->format('g:i a'),
                'endTime' => $endDateObj ? $endDateObj->format('g:i a') : null,
                'timestamp' => $dateObj->getTimestamp(),
                'endTimestamp' => $endDateObj ? $endDateObj->getTimestamp() : null,
            ];
            $post->isPastEvent = ($endDateObj ? $endDateObj->getTimestamp() : $dateObj->getTimestamp()) < time();
        }

        $data['labels']['viewEvent'] = $data['labels']['viewEvent'] ?? __('View Event', 'flynt');
        $data['labels']['pastEvent'] = $data['labels']['pastEvent'] ?? __('Past Event', 'flynt');
    }

    if ($postType === 'research') {
        $data['labels']['allPosts'] = __('See All Research', 'flynt');
        $data['labels']['readMore'] = __('Read Research', 'flynt');
        $data['postTypeArchiveLink'] = get_post_type_archive_link('research');
    } else {
        $data['labels']['allPosts'] = __('See More Posts', 'flynt');
        $data['labels']['readMore'] = __('Read More', 'flynt');
        $data['postTypeArchiveLink'] = get_permalink(get_option('page_for_posts')) ?? get_post_type_archive_link(DEFAULT_POST_TYPE);
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'gridPostsLatest',
        'label' => __('Grid: Posts Latest', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0
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
                'label' => __('Post Type', 'flynt'),
                'name' => 'postType',
                'type' => 'select',
                'choices' => [
                    'post' => __('Post', 'flynt'),
                    'research' => __('Research', 'flynt')
                ],
                'default_value' => 'post',
                'wrapper' => [
                    'width' => 50
                ],
            ],
            [
                'label' => __('Button', 'flynt'),
                'name' => 'button',
                'type' => 'link',
                'return_format' => 'array',
                'wrapper' => [
                    'width' => 50,
                ],
            ],
            [
                'label' => __('Categories/Areas', 'flynt'),
                'instructions' => __('Select 1 or more categories/areas or leave empty to show from all posts.', 'flynt'),
                'name' => 'taxonomies',
                'type' => 'taxonomy',
                'taxonomy' => 'category', // Будет динамически меняться через условную логику
                'field_type' => 'multi_select',
                'allow_null' => 1,
                'multiple' => 1,
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'object'
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
                    FieldVariables\getTheme(),
                    [
                        'label' => __('Max Posts', 'flynt'),
                        'name' => 'maxPosts',
                        'type' => 'number',
                        'default_value' => 3,
                        'min' => 1,
                        'step' => 1
                    ]
                ]
            ],
        ]
    ];
}

add_filter('acf/load_field/name=taxonomies', function ($field) {
    $postType = $_POST['postType'] ?? 'post';

    if ($postType === 'research') {
        $field['taxonomy'] = 'research_area';
    } else {
        $field['taxonomy'] = 'category';
    }

    return $field;
});

Options::addTranslatable('GridPostsLatest', [
    [
        'label' => __('Content', 'flynt'),
        'name' => 'contentTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0
    ],
    [
        'label' => __('Title', 'flynt'),
        'instructions' => __('Want to add a headline? And a paragraph? Go ahead! Or just leave it empty and nothing will be shown.', 'flynt'),
        'name' => 'preContentHtml',
        'type' => 'wysiwyg',
        'default_value' => '<h2>' . __('Related Posts', 'flynt') . '</h2>',
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
                'label' => __('All Posts', 'flynt'),
                'name' => 'allPosts',
                'type' => 'text',
                'default_value' => __('See More Posts', 'flynt'),
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
                    'width' => 50
                ],
            ]
        ],
    ]
]);
