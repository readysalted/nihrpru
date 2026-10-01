<?php

namespace Flynt\Components\BlockFeedbackBanner;

use Flynt\Utils\Asset;

add_filter('Flynt/addComponentData?name=BlockFeedbackBanner', function (array $data): array {
    $data['iconUrl'] = Asset::requireUrl('assets/images/home/feedback-smile.svg');

    if (empty($data['link']['url'])) {
        $data['link'] = [
            'title' => __('Click here', 'flynt'),
            'url' => home_url('/contact/'),
            'target' => '',
        ];
    }

    return $data;
});

function getACFLayout(): array
{
    return [
        'name' => 'blockFeedbackBanner',
        'label' => __('Block: Feedback Banner', 'flynt'),
        'sub_fields' => [
            [
                'label' => __('Message', 'flynt'),
                'name' => 'message',
                'type' => 'text',
                'default_value' => __('We would love to hear your feedback to help us shape our website.', 'flynt'),
                'required' => 1,
                'instructions' => __('Keep this short; the linked call to action is entered separately below.', 'flynt'),
            ],
            [
                'label' => __('Call to Action', 'flynt'),
                'name' => 'link',
                'type' => 'link',
                'return_format' => 'array',
                'required' => 0,
                'instructions' => __('The homepage design uses “Click here”.', 'flynt'),
            ],
        ],
    ];
}
