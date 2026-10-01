<?php

namespace Flynt\Components\ProjectWidget;

use Timber\Timber;

add_filter('Flynt/addComponentData?name=ProjectWidget', function ($data) {
    $data['statusColors'] = [
        'ongoing'    => '#0A5766',
        'finalising' => '#BB5B11',
        'complete'   => '#475989',
    ];

    if (empty($data['partners'])) {
        $data['showPartners'] = false;
    } else {
        $data['showPartners'] = true;
    }

    // Format project dates
    $start = $data['startDate'] ?? '';
    $end   = $data['endDate']   ?? '';
    $data['projectDatesFormatted'] = $start
        ? $start . ' - ' . ($end ?: 'Ongoing')
        : '';

    // Auto-link project ID to matching research post
    $data['researchPostUrl'] = null;
    if (!empty($data['projectId'])) {
        $posts = get_posts([
            'post_type'      => 'research',
            'meta_key'       => 'projectId',
            'meta_value'     => sanitize_text_field($data['projectId']),
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        if (!empty($posts)) {
            $data['researchPostUrl'] = get_permalink($posts[0]);
        }
    }

    return $data;
});

function getACFLayout()
{
    return [
        'name' => 'projectWidget',
        'label' => 'Project Widget',
        'sub_fields' => [
            [
                'label' => 'Project ID',
                'name' => 'projectId',
                'type' => 'text',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
			    ),
            ],
            [
                'label' => 'Project Url',
                'name' => 'projectIdUrl',
                'type' => 'url',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
			    ),
            ],
            [
                'label' => 'Start Date',
                'name'  => 'startDate',
                'type'  => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format'  => 'd/m/Y',
                'first_day'      => 1,
                'required'       => 0,
                'wrapper'        => ['width' => '50', 'class' => '', 'id' => ''],
            ],
            [
                'label' => 'End Date',
                'name'  => 'endDate',
                'type'  => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format'  => 'd/m/Y',
                'first_day'      => 1,
                'required'       => 0,
                'instructions'   => 'Leave blank if ongoing.',
                'wrapper'        => ['width' => '50', 'class' => '', 'id' => ''],
            ],
            [
                'label'       => 'Phase',
                'name'        => 'phase',
                'type'        => 'text',
                'required'    => 0,
                'placeholder' => 'e.g. Phase 2',
            ],
            [
                'label' => 'Core Team',
                'name' => 'coreTeam',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Team Member',
                'sub_fields' => [
                    [
                        'label' => 'Name',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 0,
                    ],
                    [
                        'label' => 'Url',
                        'name' => 'link',
                        'type' => 'url',
                        'required' => 0
                    ]
                ]
            ],
            [
                'label' => 'Lead Institution',
                'name' => 'leadInstitution',
                'type' => 'group',
                'sub_fields' => [
                    [
                        'label' => 'Logo',
                        'name' => 'logo',
                        'type' => 'image',
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                    ],
                ]
            ],
            [
                'label' => 'Partners',
                'name' => 'partners',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Partner',
                'sub_fields' => [
                    [
                        'label' => 'Title',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 0,
                    ],
                    [
                        'label' => 'Subtitle',
                        'name' => 'subtitle',
                        'type' => 'text',
                        'required' => 0,
                    ],
                    [
                        'label' => 'Link',
                        'name' => 'link',
                        'type' => 'url',
                        'required' => 0,
                    ]
                ]
            ],
            // [
            //     'label' => 'Status Colors',
            //     'name' => 'statusColors',
            //     'type' => 'group',
            //     'sub_fields' => [
            //         [
            //             'label' => 'Ongoing Color',
            //             'name' => 'ongoing',
            //             'type' => 'color_picker',
            //             'default_value' => '#429F65',
            //         ],
            //         [
            //             'label' => 'Finalising Color',
            //             'name' => 'finalising',
            //             'type' => 'color_picker',
            //             'default_value' => '#BB5B11',
            //         ],
            //         [
            //             'label' => 'Complete Color',
            //             'name' => 'complete',
            //             'type' => 'color_picker',
            //             'default_value' => '#475989',
            //         ],
            //     ]
            // ]
        ]
    ];
}
