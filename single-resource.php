<?php

use Flynt\CustomPostTypes;
use Timber\Timber;

$context = Timber::context();
$context['resource'] = CustomPostTypes\getResourceViewData($context['post']);
$excerpt = get_the_excerpt($context['post']->ID);
$context['heroContentHtml'] = $excerpt ? wpautop(esc_html($excerpt)) : '';
$resourcePage = get_page_by_path('resources');
$context['resourceLanding'] = [
    'title' => $resourcePage ? get_the_title($resourcePage) : __('Resources', 'flynt'),
    'url' => $resourcePage ? get_permalink($resourcePage) : home_url('/resources/'),
];

Timber::render('templates/single-resource.twig', $context);
