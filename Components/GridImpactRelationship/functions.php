<?php

namespace Flynt\Components\GridImpactRelationship;

use Flynt\FieldVariables;

function getACFLayout()
{
    return [
        'name' => 'GridImpactRelationship',
        'label' => __('Grid Impact Relationship', 'flynt'),
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
