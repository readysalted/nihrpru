<?php

use Timber\Timber;

$context = Timber::context();
$context['searchTerm'] = get_search_query();

Timber::render('templates/search.twig', $context);
