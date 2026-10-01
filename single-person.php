<?php

use Timber\Timber;

$context = Timber::context();
$context['personGroups'] = get_the_terms(get_the_ID(), 'person_group') ?: [];

Timber::render('templates/single-person.twig', $context);
