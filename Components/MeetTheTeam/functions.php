<?php

namespace Flynt\Components\MeetTheTeam;

use Flynt\Utils\Asset;

function getCapacityDevelopmentItems(): array
{
    return [
        ['title' => __('Emerging Leads', 'flynt'), 'image' => 'emerging-leads.webp'],
        ['title' => __('Pre-application support fund awardees', 'flynt'), 'image' => 'pre-application-support.webp'],
        ['title' => __('PhD studentships', 'flynt'), 'image' => 'phd-studentships.webp'],
        ['title' => __('Doctoral fellowships', 'flynt'), 'image' => 'emerging-leads.webp'],
        ['title' => __('Post-doctoral fellows', 'flynt'), 'image' => 'post-doctoral-fellows.webp'],
        ['title' => __('Transdisciplinary placements', 'flynt'), 'image' => 'transdisciplinary-placements.webp'],
        ['title' => __('Embedded researchers', 'flynt'), 'image' => 'emerging-leads.webp'],
        ['title' => __('Community practitioner awards', 'flynt'), 'image' => 'community-practitioner-awards.webp'],
    ];
}

add_filter('Flynt/addComponentData?name=MeetTheTeam', function (array $data): array {
    if (($data['options']['displayStyle'] ?? 'default') !== 'capacityDevelopment') {
        return $data;
    }

    $defaults = getCapacityDevelopmentItems();
    $members = is_array($data['teamMembers'] ?? null) ? $data['teamMembers'] : [];

    if (empty($members)) {
        $members = array_fill(0, count($defaults), []);
    }

    foreach ($members as $index => &$member) {
        $default = $defaults[$index] ?? null;
        if (!$default) {
            continue;
        }

        if (empty($member['title'])) {
            $member['title'] = $default['title'];
        }

        if (empty($member['image'])) {
            $member['image'] = [
                'src' => Asset::requireUrl('assets/images/capacity-development/' . $default['image']),
                'alt' => $member['title'],
                'isThemeAsset' => true,
            ];
        }
    }
    unset($member);

    $data['teamMembers'] = $members;
    $data['backgroundColor'] = $data['backgroundColor'] ?: '#c7e9ff';

    return $data;
});

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
                        'required' => 0,
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
