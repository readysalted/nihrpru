<?php

namespace Flynt\Components\GridImageText;

use Flynt\FieldVariables;
use Flynt\Utils\Asset;

add_filter('Flynt/addComponentData?name=GridImageText', function ($data) {
    if (isset($data['options']['maxColumns'])) {
        $data['maxColumns'] = (string) $data['options']['maxColumns'];
    } else {
        $data['maxColumns'] = '3';
    }

    if (($data['options']['displayStyle'] ?? '') === 'homepageFeatures' && !empty($data['items'])) {
        $fallbacks = [
            ['src' => Asset::requireUrl('assets/images/home/feature-capacity.jpg'), 'alt' => __('Capacity Development', 'flynt')],
            ['src' => Asset::requireUrl('assets/images/home/feature-research.jpg'), 'alt' => __('Our Research', 'flynt')],
        ];

        foreach ($data['items'] as $index => &$item) {
            if (empty($item['image']) && isset($fallbacks[$index])) {
                $item['image'] = $fallbacks[$index];
            }

            if (empty($item['link']['url']) && $index < 2) {
                $item['link'] = [
                    'url' => home_url($index === 0 ? '/capacity-development/' : '/our-research/'),
                    'title' => $fallbacks[$index]['alt'],
                    'target' => '',
                ];
            }
        }
        unset($item);
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'gridImageText',
        'label' => __('Grid: Image Text', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0
            ],
            [
                'label' => __('Title Alignment', 'flynt'),
                'name' => 'titleAlignment',
                'type' => 'button_group',
                'choices' => [
                    'left' => sprintf('<i class="dashicons dashicons-editor-alignleft" title="%1$s"></i>', __('Align items left', 'flynt')),
                    'center' => sprintf('<i class="dashicons dashicons-editor-aligncenter" title="%1$s"></i>', __('Align items center', 'flynt'))
                ],
                'default_value' => 'left'
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
                'label' => __('Items', 'flynt'),
                'name' => 'items',
                'type' => 'repeater',
                'collapsed' => '',
                'layout' => 'block',
                'button_label' => __('Add Item', 'flynt'),
                'sub_fields' => [
                    [
                        'label' => __('Image (Desktop)', 'flynt'),
                        'instructions' => __('Image-Format: JPG, PNG, SVG, WebP. Aspect Ratio: 16:9', 'flynt'),
                        'name' => 'image',
                        'type' => 'image',
                        'preview_size' => 'medium',
                        'mime_types' => 'jpg,jpeg,png,svg,webp',
                        'required' => 0,
                    ],
                    [
                        'label' => __('Link', 'flynt'),
                        'name' => 'link',
                        'type' => 'link',
                        'return_format' => 'array',
                        'required' => 0,
                    ],
                    [
                        'label' => __('Image (Mobile)', 'flynt'),
                        'instructions' => __('Only for single column layout. Aspect Ratio: 4:3', 'flynt'),
                        'name' => 'imageMobile',
                        'type' => 'image',
                        'preview_size' => 'medium',
                        'mime_types' => 'jpg,jpeg,png,svg,webp',
                        'required' => 0,
                    ],
                    [
                        'label' => __('Text', 'flynt'),
                        'name' => 'contentHtml',
                        'type' => 'wysiwyg',
                        'delay' => 0,
                        'media_upload' => 0,
                        'required' => 1,
                        'wrapper' => [
                            'width' => 60
                        ],
                    ],
                ]
            ],
            [
                'label' => __('Button', 'flynt'),
                'name' => 'button',
                'type' => 'link',
                'return_format' => 'array',
                'instructions' => __('Optional button shown below the grid.', 'flynt'),
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
                        'label' => __('Max Columns', 'flynt'),
                        'name' => 'maxColumns',
                        'type' => 'number',
                        'default_value' => 3,
                        'min' => 1,
                        'max' => 4,
                        'step' => 1
                    ],
                    [
                        'label' => __('Show as Card', 'flynt'),
                        'name' => 'card',
                        'type' => 'true_false',
                        'default_value' => 0,
                        'ui' => 1
                    ],
                    [
                        'label' => __('Display Style', 'flynt'),
                        'name' => 'displayStyle',
                        'type' => 'select',
                        'choices' => [
                            'default' => __('Default', 'flynt'),
                            'homepageFeatures' => __('Homepage Feature Cards', 'flynt'),
                            'homepageProjects' => __('Homepage Project Cards', 'flynt'),
                            'resourceLinks' => __('Resources Link Cards', 'flynt'),
                        ],
                        'default_value' => 'default',
                        'instructions' => __('Use the named styles only for their matching Figma sections.', 'flynt'),
                    ]
                ]
            ]
        ]
    ];
}
