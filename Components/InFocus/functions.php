<?php

namespace Flynt\Components\InFocus;

use Flynt\FieldVariables;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=InFocus', function (array $data): array {
    $termId = isset($data['category']) && is_object($data['category']) ? (int) $data['category']->term_id : 0;

    $query = [
        'post_status' => 'publish',
        'post_type' => 'post',
        'posts_per_page' => 1,
        'ignore_sticky_posts' => 1,
    ];

    if ($termId > 0) {
        $query['tax_query'] = [[
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $termId,
        ]];
    }

    $posts = Timber::get_posts($query);
    $data['post'] = $posts ? $posts[0] : null;

    $data['image'] = null;
    if (!empty($data['customImage'])) {
        $data['image'] = Timber::get_image((int) $data['customImage']);
    }

    if (!$data['image'] && !empty($data['post']) && !empty($data['post']->thumbnail)) {
        $data['image'] = $data['post']->thumbnail;
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'inFocus',
        'label' => __('Block: In Focus', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'label' => __('Image Position', 'flynt'),
                'name' => 'imagePosition',
                'type' => 'button_group',
                'choices' => [
                    'left' => sprintf('<i class=\'dashicons dashicons-align-left\' title=\'%1$s\'></i>', __('Image on the left', 'flynt')),
                    'right' => sprintf('<i class=\'dashicons dashicons-align-right\' title=\'%1$s\'></i>', __('Image on the right', 'flynt'))
                ]
            ],
            [
                'label' => __('Category', 'flynt'),
                'instructions' => __('Select a category to pull the latest post from it.', 'flynt'),
                'name' => 'category',
                'type' => 'taxonomy',
                'taxonomy' => 'category',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'object',
                'required' => 1,
            ],
            [
                'label' => __('Button Label', 'flynt'),
                'name' => 'buttonLabel',
                'type' => 'text',
                'default_value' => __('Read More', 'flynt'),
                'required' => 1,
            ],
            [
                'label' => __('Custom Image (optional)', 'flynt'),
                'instructions' => __('Upload an image for this block. If empty, featured image from the latest post is used.', 'flynt'),
                'name' => 'customImage',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
                'required' => 0,
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
                        'label' => __('Without Padding', 'flynt'),
                        'name' => 'withoutPadding',
                        'type' => 'true_false',
                        'ui' => 1,
                        'ui_on_text' => __('Yes', 'flynt'),
                        'ui_off_text' => __('No', 'flynt'),
                    ]
                ]
            ]
        ]
    ];
}
