<?php

namespace Flynt\Components\GridEventsArchive;

use Flynt\Utils\Options;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=GridEventsArchive', function (array $data): array {
    $data['uuid'] ??= wp_generate_uuid4();

    $queriedObject = get_queried_object();

    $data['title'] = get_the_archive_title();
    $data['description'] = get_the_archive_description();

    // Process posts to add formatted event date
    if (!empty($data['posts'])) {
        foreach ($data['posts'] as $post) {
            $eventDate = get_field('events_date', $post->ID);
            if ($eventDate) {
                // Parse the date (ACF returns format: F j, Y g:i a)
                $dateObj = \DateTime::createFromFormat('F j, Y g:i a', $eventDate);
                if ($dateObj) {
                    $eventEndTime = get_field('events_end_time', $post->ID);
                    $endDateObj = null;
                    if ($eventEndTime) {
                        $endDateObj = \DateTime::createFromFormat('Y-m-d g:i a', $dateObj->format('Y-m-d') . ' ' . $eventEndTime);
                    }

                    $post->eventDate = [
                        'full' => $eventDate,
                        'day' => $dateObj->format('d'),
                        'month' => $dateObj->format('M'),
                        'year' => $dateObj->format('Y'),
                        'time' => $dateObj->format('g:i a'),
                        'endTime' => $endDateObj ? $endDateObj->format('g:i a') : null,
                        'timestamp' => $dateObj->getTimestamp(),
                        'endTimestamp' => $endDateObj ? $endDateObj->getTimestamp() : null,
                    ];
                    // Check if event is in the past
                    $post->isPastEvent = ($endDateObj ? $endDateObj->getTimestamp() : $dateObj->getTimestamp()) < time();
                }
            }
        }
    }

    return $data;
});

Options::addTranslatable('GridEventsArchive', [
    [
        'label' => __('Content', 'flynt'),
        'name' => 'contentTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    ],
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
        'label' => __('Labels', 'flynt'),
        'name' => 'labelsTab',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0
    ],
    [
        'label' => '',
        'name' => 'labels',
        'type' => 'group',
        'sub_fields' => [
            [
                'label' => __('Previous', 'flynt'),
                'name' => 'previous',
                'type' => 'text',
                'default_value' => __('Prev', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Next', 'flynt'),
                'name' => 'next',
                'type' => 'text',
                'default_value' => __('Next', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('No Events Found Text', 'flynt'),
                'name' => 'noEventsFound',
                'type' => 'text',
                'default_value' => __('No events found.', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('View Event', 'flynt'),
                'name' => 'viewEvent',
                'type' => 'text',
                'default_value' => __('View Event', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
            [
                'label' => __('Past Event Label', 'flynt'),
                'name' => 'pastEvent',
                'type' => 'text',
                'default_value' => __('Past Event', 'flynt'),
                'required' => 1,
                'wrapper' => [
                    'width' => '50',
                ],
            ],
        ],
    ],
]);
