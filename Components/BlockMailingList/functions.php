<?php

namespace Flynt\Components\BlockMailingList;

use Flynt\FieldVariables;
use Flynt\Utils\Asset;
use Flynt\Utils\Options;

add_filter('Flynt/addComponentData?name=BlockMailingList', function (array $data): array {
    if (($data['options']['displayStyle'] ?? '') === 'homepage' && empty($data['image'])) {
        $data['image'] = [
            'src' => Asset::requireUrl('assets/images/home/newsletter-phone.png'),
            'alt' => __('NIHR Policy Research Unit newsletter shown on a mobile phone', 'flynt'),
            'isThemeAsset' => true,
        ];
    }

    if (!empty($data['isGlobal'])) {
        if (empty($data['image'])) {
            $data['image'] = [
                'src' => Asset::requireUrl('assets/images/home/newsletter-phone.png'),
                'alt' => __('NIHR Policy Research Unit newsletter shown on a mobile phone', 'flynt'),
                'isThemeAsset' => true,
            ];
        }

        if (empty(trim(wp_strip_all_tags($data['contentHtml'] ?? '')))) {
            $data['contentHtml'] = sprintf(
                '<h2>%1$s</h2><p>%2$s</p><p><a class="button" href="%3$s">%4$s</a></p>',
                esc_html__('Sign up to our newsletter', 'flynt'),
                esc_html__('Sign up to receive updates, opportunities and news from the NIHR Policy Research Unit in Behavioural and Social Sciences.', 'flynt'),
                esc_url(home_url('/contact/')),
                esc_html__('Click here to sign up', 'flynt')
            );
        }

        if (empty($data['imagePosition'])) {
            $data['imagePosition'] = 'right';
        }

        if (empty($data['backgroundColor'])) {
            $data['backgroundColor'] = '#c4c5ff';
        }
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'blockMailingList',
        'label' => __('Block: Mailing List', 'flynt'),
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
                'label' => __('Image', 'flynt'),
                'instructions' => __('Image-Format: JPG, PNG, SVG, WebP.', 'flynt'),
                'name' => 'image',
                'type' => 'image',
                'preview_size' => 'medium',
                'required' => 0,
                'mime_types' => 'jpg,jpeg,png,svg,webp',
            ],
            [
                'label' => __('Text', 'flynt'),
                'name' => 'contentHtml',
                'type' => 'wysiwyg',
                'delay' => 0,
                'media_upload' => 0,
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
                    [
                        'label' => __('Background Color', 'flynt'),
                        'name' => 'backgroundColor',
                        'type' => 'color_picker',
                        'default_value' => '#5B5CA0',
                        'enable_opacity' => true,
                        'return_format' => 'string'
                    ],
                    FieldVariables\getTheme(),
                    [
                        'label' => __('Display Style', 'flynt'),
                        'name' => 'displayStyle',
                        'type' => 'select',
                        'choices' => [
                            'default' => __('Default', 'flynt'),
                            'homepage' => __('Homepage Newsletter', 'flynt'),
                        ],
                        'default_value' => 'default',
                    ],
                ]
            ]
        ]
    ];
}

Options::addGlobal('BlockMailingList', [
    [
        'label' => __('Content', 'flynt'),
        'name' => 'contentTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    ],
    [
        'label' => __('Image Position', 'flynt'),
        'name' => 'globalImagePosition',
        'type' => 'button_group',
        'choices' => [
            'left' => sprintf('<i class=\'dashicons dashicons-align-left\' title=\'%1$s\'></i>', __('Image on the left', 'flynt')),
            'right' => sprintf('<i class=\'dashicons dashicons-align-right\' title=\'%1$s\'></i>', __('Image on the right', 'flynt'))
        ]
    ],
    [
        'label' => __('Image', 'flynt'),
        'instructions' => __('Image-Format: JPG, PNG, SVG, WebP.', 'flynt'),
        'name' => 'globalImage',
        'type' => 'image',
        'preview_size' => 'medium',
        'required' => 1,
        'mime_types' => 'jpg,jpeg,png,svg,webp',
    ],
    [
        'label' => __('Text', 'flynt'),
        'name' => 'globalContentHtml',
        'type' => 'wysiwyg',
        'delay' => 0,
        'media_upload' => 0,
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
        'name' => 'globalMailingOptions',
        'type' => 'group',
        'layout' => 'row',
        'sub_fields' => [
            [
                'label' => __('Background Color', 'flynt'),
                'name' => 'backgroundColor',
                'type' => 'color_picker',
                'default_value' => '#5B5CA0',
                'enable_opacity' => true,
                'return_format' => 'string'
            ],
            FieldVariables\getTheme()
        ]
    ]
]);
