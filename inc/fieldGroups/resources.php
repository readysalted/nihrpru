<?php

use ACFComposer\ACFComposer;

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'resourceDetails',
        'title' => __('Resource Details', 'flynt'),
        'fields' => [
            [
                'label' => __('Card behaviour', 'flynt'),
                'name' => 'resourceCardBehavior',
                'type' => 'radio',
                'choices' => [
                    'direct' => __('Open the resource directly', 'flynt'),
                    'single' => __('Open a Resource page first', 'flynt'),
                ],
                'default_value' => 'direct',
                'layout' => 'vertical',
                'required' => 1,
                'instructions' => __('Direct cards open the file or external URL immediately. Resource page cards open the standard detail page, whose button then opens the same destination.', 'flynt'),
            ],
            [
                'label' => __('Destination type', 'flynt'),
                'name' => 'resourceDestinationType',
                'type' => 'button_group',
                'choices' => [
                    'file' => __('Media Library file', 'flynt'),
                    'external' => __('External URL', 'flynt'),
                ],
                'default_value' => 'file',
                'required' => 1,
            ],
            [
                'label' => __('Resource file', 'flynt'),
                'name' => 'resourceFile',
                'type' => 'file',
                'return_format' => 'array',
                'library' => 'all',
                'required' => 1,
                'conditional_logic' => [
                    [
                        [
                            'fieldPath' => 'resourceDestinationType',
                            'operator' => '==',
                            'value' => 'file',
                        ],
                    ],
                ],
            ],
            [
                'label' => __('External URL', 'flynt'),
                'name' => 'resourceExternalUrl',
                'type' => 'url',
                'required' => 1,
                'placeholder' => 'https://',
                'conditional_logic' => [
                    [
                        [
                            'fieldPath' => 'resourceDestinationType',
                            'operator' => '==',
                            'value' => 'external',
                        ],
                    ],
                ],
            ],
            [
                'label' => __('CTA label', 'flynt'),
                'name' => 'resourceCtaLabel',
                'type' => 'text',
                'instructions' => __('Optional. Defaults to “Download resource” for files or “View resource” for external links.', 'flynt'),
                'placeholder' => __('Download resource', 'flynt'),
            ],
            [
                'label' => __('Open destination in a new tab', 'flynt'),
                'name' => 'resourceOpenNewTab',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 1,
                'instructions' => __('Applies to the direct card destination and to the CTA on the Resource page. Resource detail pages always open in the same tab.', 'flynt'),
            ],
            [
                'label' => __('Resource page description', 'flynt'),
                'name' => 'resourceDescription',
                'type' => 'wysiwyg',
                'tabs' => 'visual,text',
                'media_upload' => 1,
                'delay' => 0,
                'instructions' => __('Optional main copy for the Resource page. Additional flexible content blocks can be added below.', 'flynt'),
                'conditional_logic' => [
                    [
                        [
                            'fieldPath' => 'resourceCardBehavior',
                            'operator' => '==',
                            'value' => 'single',
                        ],
                    ],
                ],
            ],
            [
                'label' => __('Related projects', 'flynt'),
                'name' => 'resourceProjects',
                'type' => 'relationship',
                'post_type' => ['research'],
                'post_status' => ['publish'],
                'return_format' => 'object',
            ],
            [
                'label' => __('Original resource URL', 'flynt'),
                'name' => 'legacyUrl',
                'type' => 'url',
                'readonly' => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'resource',
                ],
            ],
        ],
    ]);
});
