<?php

/**
 * Events Archive Template
 *
 * This template handles the Events category archive.
 * The imported site stores event articles as regular posts in the Events
 * category. Most historical posts do not have an ACF event date, so the
 * archive must not require that field in order to display them.
 */

use Timber\Timber;

$context = Timber::context();

$category = get_queried_object();
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Keep the archive useful for both imported articles (post date only) and
// future events that may also have an ACF event date.
$args = [
    'post_type' => 'post',
    'posts_per_page' => 9,
    'paged' => $paged,
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field' => 'term_id',
            'terms' => $category->term_id,
            'include_children' => true,
        ],
    ],
    'orderby' => 'date',
    'order' => 'DESC',
];

$posts = Timber::get_posts($args);

$context['posts'] = $posts;
$context['category'] = $category;

Timber::render('templates/category-events.twig', $context);
