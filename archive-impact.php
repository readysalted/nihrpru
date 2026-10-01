<?php

use Timber\Timber;

$context = Timber::context();
$components = get_field('pageComponents', 'option');
$context['components'] = $components;


Timber::render('templates/impact.twig', $context);