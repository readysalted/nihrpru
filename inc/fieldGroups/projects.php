<?php

use ACFComposer\ACFComposer;

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'projectMetadata',
        'title' => __('Project Details', 'flynt'),
        'fields' => [
            [
                'label' => __('Lead researcher', 'flynt'),
                'name' => 'projectLead',
                'type' => 'text',
            ],
            [
                'label' => __('Research team', 'flynt'),
                'name' => 'projectTeam',
                'type' => 'textarea',
                'new_lines' => 'wpautop',
            ],
            [
                'label' => __('Plain English summary', 'flynt'),
                'name' => 'projectSummary',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'media_upload' => 0,
            ],
            [
                'label' => __('Start date', 'flynt'),
                'name' => 'projectStartDate',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
            ],
            [
                'label' => __('End date', 'flynt'),
                'name' => 'projectEndDate',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
            ],
            [
                'label' => __('Related people', 'flynt'),
                'name' => 'projectPeople',
                'type' => 'relationship',
                'post_type' => ['person'],
                'post_status' => ['publish'],
                'return_format' => 'object',
            ],
            [
                'label' => __('Related resources', 'flynt'),
                'name' => 'projectResources',
                'type' => 'relationship',
                'post_type' => ['resource', 'publications'],
                'post_status' => ['publish'],
                'return_format' => 'object',
            ],
            [
                'label' => __('Original project URL', 'flynt'),
                'name' => 'legacyUrl',
                'type' => 'url',
                'readonly' => 1,
            ],
        ],
        'location' => [[[
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'research',
        ]]],
    ]);
});
