<?php

namespace Flynt\Components\BlockResearchCommunity;

use Flynt\FieldVariables;

function getACFLayout()
{
    return [
        'name' => 'BlockResearchCommunity',
        'label' => __('Block Research Community', 'flynt'),
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
            'label' => __('Subtitle', 'flynt'),
            'name' => 'sub_text',
            'type' => 'text',
        ],
        [
            'label' => __('Post/Category Relationship', 'flynt'),
            'name' => 'relationship',
            'type' => 'relationship',
            'required' => 1,
            'return_format' => 'object',
            'min' => '',
            'max' => '',
            'post_type' => [
                0 => 'post',
                1 => 'page',
                2 => 'research'
            ],
            'post_status' => [
                0 => 'publish',
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
