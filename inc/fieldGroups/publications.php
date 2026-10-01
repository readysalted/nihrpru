<?php

use ACFComposer\ACFComposer;

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'publicationMetadata',
        'title' => __('Publication Details', 'flynt'),
        'fields' => [
            [
                'label' => __('Full citation', 'flynt'),
                'name' => 'publication_citation',
                'type' => 'textarea',
                'rows' => 5,
                'required' => 1,
            ],
            [
                'label' => __('Publication URL', 'flynt'),
                'name' => 'publication_url',
                'type' => 'url',
            ],
            [
                'label' => __('DOI', 'flynt'),
                'name' => 'publication_doi',
                'type' => 'text',
                'instructions' => __('Enter only the DOI identifier, for example 10.1000/example.', 'flynt'),
            ],
        ],
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'publications',
        ]]],
    ]);
});
