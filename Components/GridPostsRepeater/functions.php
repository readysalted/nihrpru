<?php

namespace Flynt\Components\GridPostsRepeater;

use Flynt\FieldVariables;

function getACFLayout()
{
    return [
        'name' => 'GridPostsRepeater',
        'label' => __('Grid Posts Repeater', 'flynt'),
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
            'label' => __('Items', 'flynt'),
            'name' => 'items',
            'type' => 'repeater',
            'collapsed' => '',
            'layout' => 'block',
            'button_label' => __('Add Item', 'flynt'),
            'sub_fields' => [
                [
                    'label' => __('Title', 'flynt'),
                    'name' => 'title',
                    'type' => 'text',
                    'wrapper' => [
                        'width' => 33
                    ],
                ],
                [
                    'label' => __('Link', 'flynt'),
                    'name' => 'link',
                    'type' => 'link',
                    'wrapper' => [
                        'width' => 33
                    ],
                ],
                [
                    'label' => __('Image', 'flynt'),
                    'instructions' => __('Image-Format: JPG, PNG, SVG, WebP. Aspect Ratio: 3:2.', 'flynt'),
                    'name' => 'image',
                    'type' => 'image',
                    'preview_size' => 'medium',
                    'mime_types' => 'jpg,jpeg,png,svg,webp',
                    'wrapper' => [
                        'width' => 33
                    ],
                ],
            ]
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
            ]
        ]
    ];
}
