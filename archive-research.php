<?php

use Timber\Timber;

$context = Timber::context();
$context['research_areas'] = Timber::get_terms([
    'taxonomy'   => 'research_area',
    'hide_empty' => false, // Change to true if you only want terms with posts
]);
$context['show_block'] = null;
$context['footer_cta_text'] = get_field('footer_cta_text', 'options') ?: '';
$context['footer_cta_img'] = get_field('footer_cta_img', 'options') ?: '';
$context['footer_cta_bg'] = get_field('footer_cta_bg', 'options') ?: '';
$context['hero_research_text'] = get_field('hero_research_text', 'options') ?: '';
$context['intro_text_research'] = get_field('intro_text_research_archive', 'options') ?: '';
$context['research_tiles'] = get_field('research_tiles', 'options') ?: [];

Timber::render('templates/research.twig', $context);
