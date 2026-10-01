<?php

use Timber\Timber;
use Timber\PostQuery;

$context = Timber::context();
$term_id = get_queried_object_id();
$term = get_term($term_id);
$context['name'] = $term->name ?: '';
$context['description'] = $term->description ?: '';
$context['featured_posts'] = get_field('featured_posts_research', 'research_area_' . $term_id ) ?: [];
if (!empty($context['featured_posts'])) {

$featured_posts_ids = array_map(function ($post) {
    return $post->ID;
}, $context['featured_posts']->to_array());

$context['featuredPostsId'] = $featured_posts_ids;

$context['posts'] = Timber::get_posts([
    'post_type'      => 'research',
    'post__not_in'   => $featured_posts_ids,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => get_query_var('paged') ?: 1,
    'tax_query'      => [
        [
            'taxonomy' => 'research_area',
            'field'    => 'term_id',      
            'terms'    => $term_id,      
        ]
    ],
]);
}

$context['show_block'] = null;
$context['footer_cta_text'] = get_field('footer_cta_text', 'options') ?: '';
$context['footer_cta_img'] = get_field('footer_cta_img', 'options') ?: '';
$context['footer_cta_bg'] = get_field('footer_cta_bg', 'options') ?: '';
$context['hero_care_text'] = get_field('hero_care_text', 'options') ?: '';

$context['intro_text_research'] = get_field('intro_text_research', 'research_area_' . $term_id) ?: '';

$context['research_tags'] = get_terms([
    'taxonomy'   => 'research_tag',
    'hide_empty' => true,
]) ?: [];

Timber::render('templates/research-area.twig', $context);
