<?php

namespace Flynt\Components\BlockTwoPages;


function getACFLayout()
{
    return [
        'name' => 'BlockTwoPages',
        'label' => __('Grid Two Pages', 'flynt'),
        'sub_fields' => getACFFields()
    ];
}

function getACFFields()
{
    return [
        [
            'label' => __('Title', 'flynt'),
            'name' => 'text',
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
                2 => 'research',
                3 => 'impact'
            ],
            'post_status' => [
                0 => 'publish',
            ]
        ],
    ];
}
