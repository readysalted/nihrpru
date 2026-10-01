<?php

namespace Flynt\Components\BlockStripe;

use Flynt\FieldVariables;

function getACFLayout(): array
{
    return [
        'name' => 'blockStripe',
        'label' => __('Block: Stripe', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Text', 'flynt'),
                'name' => 'text',
                'type' => 'wysiwyg',
                'tabs' => 'visual,text',
                'media_upload' => 0,
                'delay' => 0,
                'required' => 1,
            ],
        ]
    ];
}
