<?php

namespace Flynt\Components\GridProjects;

use Flynt\FieldVariables;
use Timber\Timber;

const STATUS_QUERY_ARG = 'project_status';
const TOPIC_QUERY_ARG = 'project_topic';

add_filter('Flynt/addComponentData?name=GridProjects', function (array $data): array {
    $defaultStatus = $data['defaultStatus'] ?? null;
    $defaultStatusSlug = is_object($defaultStatus) ? (string) $defaultStatus->slug : '';
    $defaultTopic = $data['defaultTopic'] ?? null;
    $defaultTopicSlug = is_object($defaultTopic) ? (string) $defaultTopic->slug : '';
    $activeStatus = isset($_GET[STATUS_QUERY_ARG])
        ? sanitize_title(wp_unslash($_GET[STATUS_QUERY_ARG]))
        : $defaultStatusSlug;
    $activeTopic = isset($_GET[TOPIC_QUERY_ARG])
        ? sanitize_title(wp_unslash($_GET[TOPIC_QUERY_ARG]))
        : $defaultTopicSlug;

    $taxQuery = [];
    if ($activeStatus && term_exists($activeStatus, 'project_status')) {
        $taxQuery[] = [
            'taxonomy' => 'project_status',
            'field' => 'slug',
            'terms' => [$activeStatus],
        ];
    }
    if ($activeTopic && term_exists($activeTopic, 'project_topic')) {
        $taxQuery[] = [
            'taxonomy' => 'project_topic',
            'field' => 'slug',
            'terms' => [$activeTopic],
        ];
    }
    if (count($taxQuery) > 1) {
        $taxQuery['relation'] = 'AND';
    }

    $query = [
        'post_type' => 'research',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['date' => 'DESC', 'title' => 'ASC'],
    ];
    if ($taxQuery) {
        $query['tax_query'] = $taxQuery;
    }

    $data['projects'] = Timber::get_posts($query);
    $data['statusTerms'] = get_terms([
        'taxonomy' => 'project_status',
        'hide_empty' => true,
        'orderby' => 'term_order',
    ]);
    $data['topicTerms'] = get_terms([
        'taxonomy' => 'project_topic',
        'hide_empty' => true,
        'orderby' => 'name',
    ]);
    $data['activeStatus'] = $activeStatus;
    $data['activeTopic'] = $activeTopic;
    $data['allUrl'] = remove_query_arg([STATUS_QUERY_ARG, TOPIC_QUERY_ARG]);
    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'gridProjects',
        'label' => __('Grid: Projects', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Introduction', 'flynt'),
                'name' => 'preContentHtml',
                'type' => 'wysiwyg',
                'media_upload' => 0,
            ],
            [
                'label' => __('Show filters', 'flynt'),
                'name' => 'showFilters',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1,
            ],
            [
                'label' => __('Default status', 'flynt'),
                'name' => 'defaultStatus',
                'type' => 'taxonomy',
                'taxonomy' => 'project_status',
                'field_type' => 'select',
                'allow_null' => 1,
                'return_format' => 'object',
                'save_terms' => 0,
                'load_terms' => 0,
            ],
            [
                'label' => __('Default topic', 'flynt'),
                'name' => 'defaultTopic',
                'type' => 'taxonomy',
                'taxonomy' => 'project_topic',
                'field_type' => 'select',
                'allow_null' => 1,
                'return_format' => 'object',
                'save_terms' => 0,
                'load_terms' => 0,
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
