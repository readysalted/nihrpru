<?php

namespace Flynt\Components\ResearchTags;

use Timber\Timber;

add_filter('Flynt/addComponentData?name=ResearchTags', function ($data) {
    $data['tags'] = get_terms([
        'taxonomy' => 'research_tag',
        'hide_empty' => true
    ]);

    // Get current tag if we're on a tag archive
    $data['current_tag'] = get_queried_object();

    // Get post tags if we're on a single research post
    if (is_singular('research')) {
        $data['post_tags'] = get_the_terms(get_the_ID(), 'research_tag');
    }

    return $data;
});

function getACFLayout()
{
    return [
        'name' => 'researchTags',
        'label' => 'Research Tags',
        'sub_fields' => [
            [
                'label' => 'Title',
                'name' => 'title',
                'type' => 'text',
                'default_value' => 'Research Tags',
                'required' => 0,
            ],
            [
                'label' => 'Show Tag Count',
                'name' => 'showCount',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1
            ],
        ]
    ];
}
