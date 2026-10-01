<?php

namespace Flynt\Components\GridPeople;

use Flynt\CustomPostTypes;
use Flynt\FieldVariables;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=GridPeople', function (array $data): array {
    $selectedGroups = $data['groups'] ?? [];
    if ($selectedGroups instanceof \Traversable) {
        $selectedGroups = iterator_to_array($selectedGroups);
    }

    $query = [
        'post_type' => CustomPostTypes\PERSON_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'order' => 'ASC',
    ];

    if (is_array($selectedGroups) && $selectedGroups) {
        $termIds = array_values(array_filter(array_map(function ($term): int {
            return is_object($term) ? (int) $term->term_id : (int) $term;
        }, $selectedGroups)));
        if ($termIds) {
            $query['tax_query'] = [[
                'taxonomy' => CustomPostTypes\PERSON_GROUP_TAXONOMY,
                'field' => 'term_id',
                'terms' => $termIds,
            ]];
        }
    }

    $data['people'] = Timber::get_posts($query);
    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'gridPeople',
        'label' => __('Grid: People', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Introduction', 'flynt'),
                'name' => 'preContentHtml',
                'type' => 'wysiwyg',
                'media_upload' => 0,
            ],
            [
                'label' => __('People Groups', 'flynt'),
                'name' => 'groups',
                'type' => 'taxonomy',
                'taxonomy' => CustomPostTypes\PERSON_GROUP_TAXONOMY,
                'field_type' => 'multi_select',
                'allow_null' => 1,
                'multiple' => 1,
                'add_term' => 0,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'object',
                'instructions' => __('Leave empty to show all people.', 'flynt'),
            ],
            [
                'label' => __('Options', 'flynt'),
                'name' => 'options',
                'type' => 'group',
                'sub_fields' => [FieldVariables\getTheme('white')],
            ],
        ],
    ];
}
