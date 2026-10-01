<?php

namespace Flynt\Components\MeetTheTeam;

function getACFLayout(): array
{
    return [
        'name' => 'meetTheTeam',
        'label' => __('MeetTheTeam: Default', 'flynt'),
        'sub_fields' => [
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
                'label' => __('Background Color', 'flynt'),
                'name' => 'backgroundColor',
                'type' => 'color_picker',
                'default_value' => '#FCCF78',
                'enable_opacity' => true,
                'return_format' => 'string',
            ],
            [
                'label' => __('Team Members', 'flynt'),
                'name' => 'teamMembers',
                'type' => 'repeater',
                'layout' => 'row',
                'min' => 1,
                'button_label' => __('Add Team Member', 'flynt'),
                'sub_fields' => [
                    [
                        'label' => __('Image', 'flynt'),
                        'name' => 'image',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'thumbnail',
                        'library' => 'all',
                        'mime_types' => 'jpg,jpeg,png,svg,webp',
                        'required' => 1,
                    ],
                    [
                        'label' => __('Name', 'flynt'),
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'label' => __('Position', 'flynt'),
                        'name' => 'position',
                        'type' => 'textarea',
                        'required' => 0,
                        'new_lines' => 'wpautop',
                    ],
                    [
                        'label' => __('Link', 'flynt'),
                        'name' => 'link',
                        'type' => 'link',
                    ],
                ],
            ],
            [
                'label' => __('Button', 'flynt'),
                'name' => 'button',
                'type' => 'link',
                'return_format' => 'array',
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
                'layout' => 'row',
                'sub_fields' => [
                    [
                        'label' => __('Display Style', 'flynt'),
                        'name' => 'displayStyle',
                        'type' => 'select',
                        'choices' => [
                            'default' => __('Default', 'flynt'),
                            'homepageTeam' => __('Homepage Team', 'flynt'),
                            'capacityDevelopment' => __('Capacity Development Opportunities', 'flynt'),
                        ],
                        'default_value' => 'default',
                    ],
                ],
            ],
        ],
    ];
}
