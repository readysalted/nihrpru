<?php

use Timber\Timber;

$context = Timber::context();
$term_id = get_queried_object_id();
$term = get_term($term_id);
$context['name'] = $term->name ?: '';
$context['description'] = $term->description ?: '';
$context['featured_posts'] = get_field('featured_posts_research', 'research_area_' . $term_id) ?: [];

$featured_posts_ids = [];
if (!empty($context['featured_posts'])) {
    $featured_posts_ids = array_map(function ($post) {
        return $post->ID;
    }, $context['featured_posts']->to_array());
    $context['featuredPostsId'] = $featured_posts_ids;
}

$paged = get_query_var('paged') ?: 1;
$posts_per_page = get_option('posts_per_page', 10);

$query_args = [
    'post_type'      => 'research',
    'post__not_in'   => $featured_posts_ids,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => $paged,
    'posts_per_page' => $posts_per_page,
    'tax_query'      => [
        [
            'taxonomy' => 'research_area',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]
    ],
];

$wp_query = new WP_Query($query_args);
$context['posts'] = Timber::get_posts($wp_query);

// Build classic number pagination
$total_pages = $wp_query->max_num_pages;
$context['total_pages'] = $total_pages;
$context['current_page'] = $paged;

// Build pagination pages array
$pagination_pages = [];
if ($total_pages > 1) {
    // Use the current term link as the base — it already includes the term slug
    $base_url = untrailingslashit(get_term_link($term_id, 'research_area'));

    for ($i = 1; $i <= $total_pages; $i++) {
        $page_link = ($i === 1) ? $base_url : $base_url . '/page/' . $i;
        $pagination_pages[] = [
            'title'   => $i,
            'link'    => $page_link,
            'current' => ($i === (int) $paged),
        ];
    }

    $context['pagination'] = [
        'pages'   => $pagination_pages,
        'prev'    => ($paged > 1) ? ['link' => ($paged - 1 === 1) ? $base_url : $base_url . '/page/' . ($paged - 1)] : null,
        'next'    => ($paged < $total_pages) ? ['link' => $base_url . '/page/' . ($paged + 1)] : null,
    ];
} else {
    $context['pagination'] = [
        'pages' => [],
        'prev'  => null,
        'next'  => null,
    ];
}

// Get only research_tags that are actually used by posts in this term
$all_post_ids_in_term = get_posts([
    'post_type'      => 'research',
    'fields'         => 'ids',
    'posts_per_page' => -1,
    'tax_query'      => [
        [
            'taxonomy' => 'research_area',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]
    ],
]);

$term_specific_tags = [];
if (!empty($all_post_ids_in_term)) {
    $tag_terms = wp_get_object_terms($all_post_ids_in_term, 'research_tag', [
        'orderby' => 'name',
        'order'   => 'ASC',
    ]);
    if (!is_wp_error($tag_terms)) {
        // Deduplicate by term_id
        $seen = [];
        foreach ($tag_terms as $tag) {
            if (!isset($seen[$tag->term_id])) {
                $seen[$tag->term_id] = true;
                $term_specific_tags[] = $tag;
            }
        }
    }
}
$context['research_tags'] = $term_specific_tags;

$context['show_block'] = null;
$context['footer_cta_text'] = get_field('footer_cta_text', 'options') ?: '';
$context['footer_cta_img'] = get_field('footer_cta_img', 'options') ?: '';
$context['footer_cta_bg'] = get_field('footer_cta_bg', 'options') ?: '';
$context['hero_care_text'] = get_field('hero_care_text', 'options') ?: '';

$context['intro_text_research'] = get_field('intro_text_research', 'research_area_' . $term_id) ?: '';

Timber::render('templates/publications.twig', $context);
