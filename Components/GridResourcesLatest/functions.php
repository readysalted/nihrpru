<?php

namespace Flynt\Components\GridResourcesLatest;

use Flynt\CustomPostTypes;
use Flynt\FieldVariables;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=GridResourcesLatest', function (array $data): array {
    $maxPosts = max(1, min(12, (int) ($data['options']['maxPosts'] ?? 3)));
    $posts = Timber::get_posts([
        'post_type' => CustomPostTypes\RESOURCE_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $maxPosts,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);

    $data['resources'] = [];
    foreach ($posts as $post) {
        $data['resources'][] = CustomPostTypes\getResourceViewData($post);
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'gridResourcesLatest',
        'label' => __('Grid: Latest Resources', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Content', 'flynt'),
                'name' => 'contentTab',
                'type' => 'tab',
                'placement' => 'top',
                'endpoint' => 0,
            ],
            [
                'label' => __('Title', 'flynt'),
                'name' => 'title',
                'type' => 'text',
                'default_value' => __('Latest downloadable resources', 'flynt'),
                'required' => 1,
            ],
            [
                'label' => __('Button', 'flynt'),
                'name' => 'button',
                'type' => 'link',
                'return_format' => 'array',
                'instructions' => __('Usually links to the Downloadable Resources page.', 'flynt'),
            ],
            [
                'label' => __('Empty message', 'flynt'),
                'name' => 'emptyMessage',
                'type' => 'text',
                'default_value' => __('New resources will be added here as they are published.', 'flynt'),
                'instructions' => __('Shown only when no Resources are published.', 'flynt'),
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
                    FieldVariables\getTheme('pink-quartz'),
                    [
                        'label' => __('Maximum resources', 'flynt'),
                        'name' => 'maxPosts',
                        'type' => 'number',
                        'default_value' => 3,
                        'min' => 1,
                        'max' => 12,
                        'step' => 1,
                    ],
                ],
            ],
        ],
    ];
}
