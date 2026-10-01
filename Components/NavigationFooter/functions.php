<?php

namespace Flynt\Components\NavigationFooter;

use Flynt\Utils\Asset;
use Flynt\Utils\Options;

function getPublishedPage(string $path): ?\WP_Post
{
    if ($path === '/') {
        $pageId = (int) get_option('page_on_front');
        $page = $pageId ? get_post($pageId) : null;
    } else {
        $page = get_page_by_path(trim($path, '/'));
    }

    return $page instanceof \WP_Post && $page->post_status === 'publish' ? $page : null;
}

function getPageMenuItems(array $paths): array
{
    return array_values(array_filter(array_map(function (string $path): ?array {
        $page = getPublishedPage($path);

        if (!$page) {
            return null;
        }

        return [
            'link' => [
                'title' => get_the_title($page),
                'url' => get_permalink($page),
                'target' => '',
            ],
        ];
    }, $paths)));
}

function isPublishedPageUrl(string $url): bool
{
    if ($url === '') {
        return false;
    }

    $homeHost = wp_parse_url(home_url('/'), PHP_URL_HOST);
    $urlHost = wp_parse_url($url, PHP_URL_HOST);
    if ($urlHost && $homeHost && strtolower($urlHost) !== strtolower($homeHost)) {
        return false;
    }

    $absoluteUrl = str_starts_with($url, '/') ? home_url($url) : $url;
    if (untrailingslashit($absoluteUrl) === untrailingslashit(home_url('/'))) {
        return getPublishedPage('/') !== null;
    }

    $pageId = url_to_postid($absoluteUrl);
    if ($pageId > 0 && get_post_type($pageId) === 'page' && get_post_status($pageId) === 'publish') {
        return true;
    }

    $path = trim((string) wp_parse_url($absoluteUrl, PHP_URL_PATH), '/');
    $page = $path !== '' ? get_page_by_path($path) : null;

    return $page instanceof \WP_Post && $page->post_status === 'publish';
}

function filterPageMenuItems($items): array
{
    if (!is_array($items)) {
        return [];
    }

    return array_values(array_filter($items, function ($item): bool {
        $link = is_array($item) ? ($item['link'] ?? null) : null;

        return is_array($link)
            && !empty($link['title'])
            && isPublishedPageUrl((string) ($link['url'] ?? ''));
    }));
}

add_filter('Flynt/addComponentData?name=NavigationFooter', function (array $data): array {
    if (empty($data['contentHtml']) || stripos(wp_strip_all_tags($data['contentHtml']), 'lorem ipsum') !== false) {
        $data['contentHtml'] = sprintf(
            '<h4>%1$s</h4><p>%2$s</p><p><a href="mailto:%3$s">%3$s</a></p>',
            esc_html__('Contact us', 'flynt'),
            esc_html__('Newcastle University, Baddiley-Clark Building, Richardson Road, Newcastle upon Tyne, NE2 4AX. Telephone: 0191 208 3463', 'flynt'),
            antispambot('NIHRPRU.BehSocSci@newcastle.ac.uk')
        );
    }

    if (empty($data['column_two_title']) || stripos($data['column_two_title'], 'lorem ipsum') !== false) {
        $data['column_two_title'] = __('Explore', 'flynt');
    }

    $data['column_two'] = filterPageMenuItems($data['column_two'] ?? []);
    if (empty($data['column_two'])) {
        $data['column_two'] = getPageMenuItems([
            '/',
            '/our-projects/',
            '/outputs/',
            '/for-policy-makers/',
            '/for-researchers/',
            '/for-the-public/',
            '/about-us/',
            '/our-collaborators/',
        ]);
    }

    $data['column_three_title'] = $data['column_three_title'] ?: __('Information', 'flynt');
    $data['column_three'] = filterPageMenuItems($data['column_three'] ?? []);
    if (empty($data['column_three'])) {
        $data['column_three'] = getPageMenuItems([
            '/site-map/',
            '/privacy-policy/',
            '/cookie-policy/',
            '/terms/',
            '/accessibility/',
        ]);
    }

    $data['column_social'] = array_values(array_filter(
        is_array($data['column_social'] ?? null) ? $data['column_social'] : [],
        static fn ($item): bool => !empty($item['link']['url']) && !empty($item['link']['title'])
    ));

    $data['column_social'] = array_map(static function (array $item): array {
        $linkIdentity = strtolower(sprintf(
            '%s %s',
            (string) ($item['link']['title'] ?? ''),
            (string) ($item['link']['url'] ?? '')
        ));

        if (str_contains($linkIdentity, 'youtube')) {
            $item['image'] = [
                'src' => Asset::requireUrl('assets/images/pru/youtube.svg'),
                'alt' => __('YouTube', 'flynt'),
            ];
        } elseif (str_contains($linkIdentity, 'linkedin')) {
            $item['image'] = [
                'src' => Asset::requireUrl('assets/images/pru/linkedin.svg'),
                'alt' => __('LinkedIn', 'flynt'),
            ];
        }

        return $item;
    }, $data['column_social']);

    $data['copyright_menu'] = filterPageMenuItems($data['copyright_menu'] ?? []);
    if (empty($data['copyright_menu'])) {
        $data['copyright_menu'] = getPageMenuItems([
            '/site-map/',
            '/privacy-policy/',
            '/cookie-policy/',
            '/terms/',
            '/accessibility/',
        ]);
    }

    if (empty($data['copyright_text']) || str_contains($data['copyright_text'], '|')) {
        $data['copyright_text'] = sprintf(
            __('NIHR PRU Behavioural and Social Sciences © %s', 'flynt'),
            wp_date('Y')
        );
    }

    return $data;
});

Options::addTranslatable('NavigationFooter', [
    [
        'label'     => __('Content', 'flynt'),
        'name'      => 'contentTab',
        'type'      => 'tab',
        'placement' => 'top',
        'endpoint'  => 0
    ],
    [
        'label'    => __('Title', 'flynt'),
        'name'     => 'text',
        'type'     => 'text',
    ],
    [
        'label'    => __('Funded by Image', 'flynt'),
        'name'     => 'funded_by_logo',
        'type'     => 'image',
        'instructions' => 'This image will be shown left'
    ],
    [
        'label'    => __('Funded by', 'flynt'),
        'name'     => 'funded_by_text',
        'type'     => 'wysiwyg',
        'instructions' => 'This text will be shown right image'
    ],
    [
        'label'    => __('Copyright Text', 'flynt'),
        'name'     => 'copyright_text',
        'type'     => 'text',
        'instructions' => 'This text will be shown before copyright menu (Like first element)'
    ],
    [
        'label'        => __('Copyright menu', 'flynt'),
        'name'         => 'copyright_menu',
        'type'         => 'repeater',
        'button_label' => __('Add Item', 'flynt'),
        'max' => 8,
        'sub_fields'   => [
            [
                'label' => __('Link', 'flynt'),
                'name'  => 'link',
                'type'  => 'link',
            ],
        ],
    ],
    [
        'label' => __('Custom Link', 'flynt'),
        'name'  => 'custom_link',
        'type'  => 'link',
    ],
    [
        'label'     => __('Column One', 'flynt'),
        'name'      => 'columnOneTab',
        'type'      => 'tab',
        'placement' => 'top',
        'endpoint'  => 0
    ],
    [
        'label'        => __('Text', 'flynt'),
        'name'         => 'contentHtml',
        'type'         => 'wysiwyg',
        'delay'        => 0,
        'media_upload' => 0,
        'required'     => 1,
        'instructions' => 'Please use H4 title for this column.'
    ],
    [
        'label'     => __('Column two', 'flynt'),
        'name'      => 'columnTwoTab',
        'type'      => 'tab',
        'placement' => 'top',
        'endpoint'  => 0
    ],
    [
        'label'    => __('Column two Title', 'flynt'),
        'name'     => 'column_two_title',
        'type'     => 'text',
    ],
    [
        'label'        => __('Column two', 'flynt'),
        'name'         => 'column_two',
        'type'         => 'repeater',
        'max' => 8,
        'button_label' => __('Add Item', 'flynt'),
        'sub_fields'   => [
            [
                'label' => __('Link', 'flynt'),
                'name'  => 'link',
                'type'  => 'link',
            ],
        ],
    ],
    [
        'label'     => __('Column three', 'flynt'),
        'name'      => 'columnThreeTab',
        'type'      => 'tab',
        'placement' => 'top',
        'endpoint'  => 0
    ],
    [
        'label'    => __('Column three Title', 'flynt'),
        'name'     => 'column_three_title',
        'type'     => 'text',
    ],
    [
        'label'        => __('Column three', 'flynt'),
        'name'         => 'column_three',
        'type'         => 'repeater',
        'max' => 8,
        'button_label' => __('Add Item', 'flynt'),
        'sub_fields'   => [
            [
                'label' => __('Link', 'flynt'),
                'name'  => 'link',
                'type'  => 'link',
            ],
        ],
    ],
    [
        'label'     => __('Column Social', 'flynt'),
        'name'      => 'columnSocialTab',
        'type'      => 'tab',
        'placement' => 'top',
        'endpoint'  => 0
    ],
    [
        'label'    => __('Column Social Title', 'flynt'),
        'name'     => 'column_social_title',
        'type'     => 'text',
    ],
    [
        'label'        => __('Column Social', 'flynt'),
        'name'         => 'column_social',
        'type'         => 'repeater',
        'max' => 8,
        'button_label' => __('Add Item', 'flynt'),
        'sub_fields'   => [
            [
                'label' => __('Icon', 'flynt'),
                'name'  => 'image',
                'type'  => 'image',
                'instructions' => __('Optional. The approved SVG icon is added automatically for YouTube and LinkedIn links.', 'flynt'),
                'wrapper'      => [
                    'width' => '20'
                ],
            ],
            [
                'label' => __('Link', 'flynt'),
                'name'  => 'link',
                'type'  => 'link',
                'wrapper'      => [
                    'width' => '80'
                ],
            ],
        ],
    ],
]);
