<?php

namespace Flynt\Components\ListLogos;

use Flynt\FieldVariables;

add_filter('Flynt/addComponentData?name=ListLogos', function (array $data): array {
    if (($data['options']['displayStyle'] ?? '') !== 'homepagePartners' || empty($data['items'])) {
        return $data;
    }

    $data['labels'] = array_merge($data['labels'] ?? [], [
        'partnerLogos' => __('Partner logos', 'flynt'),
        'scrollPartnerLogos' => __('Scroll partner logos', 'flynt'),
    ]);

    return $data;
});

function getACFLayout()
{
    return [
        'name' => 'listLogos',
        'label' => __('List: Logos', 'flynt'),
        'sub_fields' => [
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
                'label' => __('Items', 'flynt'),
                'name' => 'items',
                'type' => 'repeater',
                'collapsed' => '',
                'min' => 1,
                'max' => 0,
                'layout' => 'block',
                'button_label' => __('Add Item', 'flynt'),
                'instructions' => __('Add as many partner logos as needed. The homepage presentation becomes horizontally scrollable when the logos exceed the available width.', 'flynt'),
                'sub_fields' => [
                    [
                        'label' => __('Link', 'flynt'),
                        'name' => 'link',
                        'type' => 'link',
                        'return_format' => 'array',
                        'wrapper' =>  [
                            'width' => '60',
                        ]
                    ],
                    [
                        'label' => __('Source', 'flynt'),
                        'instructions' => __('Image-Format: JPG, PNG, SVG, WebP. Recommended Minimum Width: 280px.', 'flynt'),
                        'name' => 'image',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'small',
                        'library' => 'all',
                        'mime_types' => 'jpg,jpeg,png,svg,webp',
                        'required' => 1,
                        'wrapper' =>  [
                            'width' => '40',
                        ]
                    ]
                ]
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
                'sub_fields' => [
                    FieldVariables\getTheme(),
                    [
                        'label' => __('Show as Card', 'flynt'),
                        'name' => 'card',
                        'type' => 'true_false',
                        'default_value' => 0,
                        'ui' => 1,
                    ],
                    [
                        'label' => __('Display Style', 'flynt'),
                        'name' => 'displayStyle',
                        'type' => 'select',
                        'choices' => [
                            'default' => __('Default', 'flynt'),
                            'homepagePartners' => __('Homepage Partner Logos', 'flynt'),
                        ],
                        'default_value' => 'default',
                        'instructions' => __('Displays partner logos in one row with the progress rule shown in the homepage design.', 'flynt'),
                    ],
                ]
            ],
        ]
    ];
}
