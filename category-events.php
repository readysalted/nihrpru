<?php

/**
 * Events Archive Template
 *
 * This template handles the Events category archive.
 * Posts are sorted by the ACF 'events_date' field from newest to oldest.
 */

use Timber\Timber;

$context = Timber::context();

$category = get_queried_object();
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Query events sorted by ACF date field (newest first)
$args = [
    'post_type' => 'post',
    'posts_per_page' => get_option('posts_per_page'),
    'paged' => $paged,
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $category->term_id,
            'include_children' => true,
        ],
    ],
    'meta_key' => 'events_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
];

$posts = Timber::get_posts($args);

// Process posts to add event date information
$processedPosts = [];
foreach ($posts as $post) {
    $postData = [
        'post' => $post,
        'link' => $post->link(),
        'title' => $post->title(),
        'thumbnail' => $post->thumbnail(),
    ];

    $eventDate = get_field('events_date', $post->ID);
    if ($eventDate) {
        // ACF returns format: F j, Y g:i a (e.g., "April 9, 2026 3:00 pm")
        $dateObj = DateTime::createFromFormat('F j, Y g:i a', $eventDate);
        if ($dateObj) {
            $eventEndTime = get_field('events_end_time', $post->ID);
            $endDateObj = null;
            if ($eventEndTime) {
                $endDateObj = DateTime::createFromFormat('Y-m-d g:i a', $dateObj->format('Y-m-d') . ' ' . $eventEndTime);
            }

            $postData['eventDate'] = [
                'full' => $eventDate,
                'day' => $dateObj->format('d'),
                'month' => $dateObj->format('M'),
                'year' => $dateObj->format('Y'),
                'time' => $dateObj->format('g:i a'),
                'endTime' => $endDateObj ? $endDateObj->format('g:i a') : null,
                'timestamp' => $dateObj->getTimestamp(),
                'endTimestamp' => $endDateObj ? $endDateObj->getTimestamp() : null,
            ];
            $postData['isPastEvent'] = ($endDateObj ? $endDateObj->getTimestamp() : $dateObj->getTimestamp()) < time();
        }
    }

    $processedPosts[] = $postData;
}

$context['posts'] = $posts;
$context['events'] = $processedPosts;
$context['category'] = $category;

Timber::render('templates/category-events.twig', $context);
