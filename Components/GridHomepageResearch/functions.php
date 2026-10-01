<?php

namespace Flynt\Components\GridHomepageResearch;

use Flynt\Utils\Asset;

add_filter('Flynt/addComponentData?name=GridHomepageResearch', function (array $data): array {
    $fallbacks = [
        'themes' => [
            'research-theme-preconception.png',
            'research-theme-pregnancy.png',
            'research-theme-neonatal.png',
        ],
        'workstreams' => [
            'workstream-access.jpg',
            'workstream-racism.jpg',
            'workstream-community.jpg',
            'workstream-delta.jpg',
        ],
    ];

    foreach ($fallbacks as $group => $files) {
        if (empty($data[$group])) {
            continue;
        }

        foreach ($data[$group] as $index => &$item) {
            if (empty($item['image']) && isset($files[$index])) {
                $item['image'] = [
                    'src' => Asset::requireUrl('assets/images/home/' . $files[$index]),
                    'alt' => $item['title'] ?? '',
                ];
            }

            if (empty($item['link']['url'])) {
                $item['link'] = [
                    'url' => home_url('/our-research/'),
                    'title' => $item['title'] ?? __('Our Research', 'flynt'),
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
    $cardFields = [
        [
            'label' => __('Title', 'flynt'),
            'name' => 'title',
            'type' => 'text',
            'required' => 1,
        ],
        [
            'label' => __('Image', 'flynt'),
            'name' => 'image',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'medium',
            'mime_types' => 'jpg,jpeg,png,webp',
            'required' => 0,
        ],
        [
            'label' => __('Link', 'flynt'),
            'name' => 'link',
            'type' => 'link',
            'return_format' => 'array',
            'required' => 0,
        ],
    ];

    return [
        'name' => 'gridHomepageResearch',
        'label' => __('Grid: Homepage Research', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Research Themes', 'flynt'),
                'name' => 'themesTab',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'label' => __('Heading', 'flynt'),
                'name' => 'themesHeading',
                'type' => 'text',
                'default_value' => __('Our Research Themes', 'flynt'),
                'required' => 1,
            ],
            [
                'label' => __('Theme Cards', 'flynt'),
                'name' => 'themes',
                'type' => 'repeater',
                'layout' => 'block',
                'min' => 1,
                'max' => 6,
                'button_label' => __('Add Theme', 'flynt'),
                'sub_fields' => $cardFields,
            ],
            [
                'label' => __('Core Functions & Workstreams', 'flynt'),
                'name' => 'workstreamsTab',
                'type' => 'tab',
                'placement' => 'top',
            ],
            [
                'label' => __('Heading', 'flynt'),
                'name' => 'workstreamsHeading',
                'type' => 'text',
                'default_value' => __('Our Core functions & Workstreams', 'flynt'),
                'required' => 1,
            ],
            [
                'label' => __('Workstream Cards', 'flynt'),
                'name' => 'workstreams',
                'type' => 'repeater',
                'layout' => 'block',
                'min' => 1,
                'max' => 8,
                'button_label' => __('Add Workstream', 'flynt'),
                'sub_fields' => $cardFields,
            ],
        ],
    ];
}
