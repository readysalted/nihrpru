<?php

namespace Flynt\Components\GridHomepageResearch;

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
            'required' => 1,
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
