<?php

namespace Flynt\Components\BlockPostTabs;

use Flynt\FieldVariables;
use Flynt\Utils\Asset;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=BlockPostTabs', function ($data) {
    $data['contentType'] = $data['contentType'] ?? 1;
    $data['homepageCtaIcon'] = Asset::requireUrl('assets/images/pru/news-link-art.png');

    if (
        $data['contentType'] == 1
        && !empty($data['pullLatestFromCategory'])
    ) {
        $postsPerPage = !empty($data['latestPostsCount']) ? (int) $data['latestPostsCount'] : 3;
        $minimumPosts = ($data['options']['displayStyle'] ?? '') === 'homepage' ? 2 : 1;
        $postsPerPage = max($minimumPosts, $postsPerPage);
        $pinnedPostId = 0;

        if (!empty($data['pinnedPost'])) {
            $pinnedPostId = (int) ($data['pinnedPost']->ID ?? $data['pinnedPost']->id ?? 0);
        }

        $query = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, $postsPerPage - ($pinnedPostId ? 1 : 0)),
            'ignore_sticky_posts' => 1,
        ];

        if (!empty($data['selectedCategory']->term_id)) {
            $query['cat'] = (int) $data['selectedCategory']->term_id;
        }

        if ($pinnedPostId) {
            $query['post__not_in'] = [$pinnedPostId];
        }

        $posts = Timber::get_posts($query)->to_array();

        if ($pinnedPostId && get_post_status($pinnedPostId) === 'publish') {
            $pinnedPost = Timber::get_post($pinnedPostId);
            if ($pinnedPost) {
                array_unshift($posts, $pinnedPost);
            }
        }

        $data['relationship'] = array_slice($posts, 0, $postsPerPage);
    }

    // Clean up data based on content type
    if ($data['contentType'] == 1) {
        unset($data['quotes']);
    } else {
        unset($data['relationship']);
    }

    return $data;
});

function getACFLayout()
{
    return [
        'name' => 'BlockPostTabs',
        'label' => __('Block Post Tabs', 'flynt'),
        'sub_fields' => getACFFields()
    ];
}

function getACFFields()
{
    return [
        [
            'label' => __('Content', 'flynt'),
            'name' => 'Tab',
            'type' => 'tab',
            'placement' => 'top',
            'endpoint' => 0
        ],
        [
            'label' => __('Title', 'flynt'),
            'name' => 'text',
            'type' => 'text',
        ],
        [
            'label' => __('Content Type', 'flynt'),
            'name' => 'contentType',
            'type' => 'true_false',
            'message' => '',
            'default_value' => 1,
            'ui' => 1,
            'ui_on_text' => __('Posts', 'flynt'),
            'ui_off_text' => __('Quotes', 'flynt'),
        ],
        [
            'label' => __('Post/Category Relationship', 'flynt'),
            'name' => 'relationship',
            'type' => 'relationship',
            'return_format' => 'object',
            'min' => '',
            'max' => '',
            'post_type' => [
                0 => 'post',
                1 => 'page',
            ],
            'post_status' => [
                0 => 'publish',
            ],
            'instructions' => __('Carousel images come from each selected post or page Featured Image.', 'flynt'),
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'fieldPath' => 'pullLatestFromCategory',
                        'operator' => '!=',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Pull Latest Posts from Category', 'flynt'),
            'name' => 'pullLatestFromCategory',
            'type' => 'true_false',
            'message' => '',
            'default_value' => 0,
            'ui' => 1,
            'ui_on_text' => __('Auto', 'flynt'),
            'ui_off_text' => __('Manual', 'flynt'),
            'instructions' => __('Automatic slides use each post Featured Image. Edit the post itself to replace its carousel image.', 'flynt'),
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Category', 'flynt'),
            'name' => 'selectedCategory',
            'type' => 'taxonomy',
            'taxonomy' => 'category',
            'field_type' => 'select',
            'allow_null' => 1,
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'return_format' => 'object',
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'fieldPath' => 'pullLatestFromCategory',
                        'operator' => '==',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Pinned First Post', 'flynt'),
            'name' => 'pinnedPost',
            'type' => 'post_object',
            'post_type' => ['post'],
            'post_status' => ['publish'],
            'return_format' => 'object',
            'allow_null' => 1,
            'instructions' => __('Optional. This post always appears first; the remaining slides are filled with the latest posts.', 'flynt'),
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'fieldPath' => 'pullLatestFromCategory',
                        'operator' => '==',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Number of Latest Posts', 'flynt'),
            'name' => 'latestPostsCount',
            'type' => 'number',
            'default_value' => 3,
            'min' => 1,
            'max' => 12,
            'step' => 1,
            'instructions' => __('This is the total number of carousel slides, including the pinned post. The Homepage News Carousel always requests at least 2 published posts so it can rotate.', 'flynt'),
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ],
                    [
                        'fieldPath' => 'pullLatestFromCategory',
                        'operator' => '==',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Post Button Label', 'flynt'),
            'name' => 'buttonLabel',
            'type' => 'text',
            'instructions' => __('Shown as the visible HTML link label on every slide in this component. Use a neutral label such as “Read more” if the feed mixes news and events.', 'flynt'),
            'default_value' => __('Read the full news story', 'flynt'),
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '==',
                        'value' => '1',
                    ]
                ]
            ],
        ],
        [
            'label' => __('Quotes', 'flynt'),
            'name' => 'quotes',
            'type' => 'repeater',
            'layout' => 'block',
            'button_label' => __('Add Quote', 'flynt'),
            'min' => 1,
            'sub_fields' => [
                [
                    'label' => __('Quote', 'flynt'),
                    'name' => 'quoteHtml',
                    'type' => 'wysiwyg',
                    'tabs' => 'visual,text',
                    'media_upload' => 0,
                    'delay' => 0,
                    'required' => 1,
                ]
            ],
            'conditional_logic' => [
                [
                    [
                        'fieldPath' => 'contentType',
                        'operator' => '!=',
                        'value' => '1',
                    ]
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
                FieldVariables\getTheme(),
                [
                    'label' => __('Display Style', 'flynt'),
                    'name' => 'displayStyle',
                    'type' => 'select',
                    'choices' => [
                        'default' => __('Default', 'flynt'),
                        'homepage' => __('Homepage News Carousel', 'flynt'),
                    ],
                    'default_value' => 'default',
                ],
            ]
        ]
    ];
}
