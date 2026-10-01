<?php

namespace Flynt\Components\BlockWysiwyg;

use Flynt\FieldVariables;

add_filter('Flynt/addComponentData?name=BlockWysiwyg', function (array $data): array {
    if (!empty($data['contentHtml']) && has_shortcode($data['contentHtml'], 'wp_sitemap_page')) {
        $data['contentHtml'] = do_shortcode($data['contentHtml']);
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'blockWysiwyg',
        'label' => __('Block: Wysiwyg', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'label' => __('Text', 'flynt'),
                'name' => 'contentHtml',
                'type' => 'wysiwyg',
                'delay' => 0,
                'media_upload' => 1,
                'required' => 1,
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
                    FieldVariables\getSize(),
                    FieldVariables\getAlignment(),
                    FieldVariables\getTextAlignment(),
                    [
                        'label' => __('Display Style', 'flynt'),
                        'name' => 'displayStyle',
                        'type' => 'select',
                        'choices' => [
                            'default' => __('Default', 'flynt'),
                            'homepageIntro' => __('Homepage Introduction', 'flynt'),
                        ],
                        'default_value' => 'default',
                    ],
                ]
            ]
        ]
    ];
}
