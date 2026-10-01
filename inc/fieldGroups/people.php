<?php

use ACFComposer\ACFComposer;

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'personDetails',
        'title' => __('Person Details', 'flynt'),
        'fields' => [
            [
                'label' => __('Role / Position', 'flynt'),
                'name' => 'personRole',
                'type' => 'text',
                'required' => 0,
            ],
            [
                'label' => __('Role Group', 'flynt'),
                'name' => 'personRoleGroup',
                'type' => 'taxonomy',
                'taxonomy' => 'person_role_group',
                'field_type' => 'radio',
                'allow_null' => 1,
                'add_term' => 0,
                'save_terms' => 1,
                'load_terms' => 1,
                'return_format' => 'object',
                'instructions' => __('Used for filtering and ordering the people directory.', 'flynt'),
            ],
            [
                'label' => __('Institution', 'flynt'),
                'name' => 'personInstitution',
                'type' => 'text',
                'required' => 0,
            ],
            [
                'label' => __('Email', 'flynt'),
                'name' => 'personEmail',
                'type' => 'email',
                'required' => 0,
            ],
            [
                'label' => __('External profile', 'flynt'),
                'name' => 'personWebsite',
                'type' => 'url',
                'required' => 0,
            ],
            [
                'label' => __('Original profile URL', 'flynt'),
                'name' => 'legacyUrl',
                'type' => 'url',
                'required' => 0,
                'readonly' => 1,
            ],
        ],
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'person',
        ]]],
    ]);
});
