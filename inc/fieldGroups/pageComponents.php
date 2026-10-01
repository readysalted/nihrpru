<?php

use ACFComposer\ACFComposer;
use Flynt\Components;

add_action('Flynt/afterRegisterComponents', function (): void {
    $pageLayouts = [
        Components\BlockAnchor\getACFLayout(),
        Components\BlockImage\getACFLayout(),
        Components\BlockMailingList\getACFLayout(),
        Components\BlockImageText\getACFLayout(),
        Components\BlockImageTextHero\getACFLayout(),
        Components\BlockSpacer\getACFLayout(),
        Components\BlockStripe\getACFLayout(),
        Components\BlockFeedbackBanner\getACFLayout(),
        Components\BlockResearchCommunity\getACFLayout(),
        Components\AccordionDefault\getACFLayout(),
        Components\BlockVideoOembed\getACFLayout(),
        Components\BlockWysiwyg\getACFLayout(),
        Components\BlockWysiwygTwoCol\getACFLayout(),
        Components\BlockPostTabs\getACFLayout(),
        Components\BlockTextImageCrop\getACFLayout(),
        Components\GridImageText\getACFLayout(),
        Components\GridHomepageResearch\getACFLayout(),
        Components\GridResourcesLatest\getACFLayout(),
        Components\GridResourcesArchive\getACFLayout(),
        Components\GridPostsLatest\getACFLayout(),
        Components\GridPostsRelationship\getACFLayout(),
        Components\ListComponents\getACFLayout(),
        Components\ListLogos\getACFLayout(),
        Components\SliderImages\getACFLayout(),
        Components\SliderImagesCentered\getACFLayout(),
        Components\SliderImageGallery\getACFLayout(),
        Components\ReusableComponent\getACFLayout(),
        Components\GridImpactRelationship\getACFLayout(),
        Components\MeetTheTeam\getACFLayout(),
        Components\BlockHero\getACFLayout(),
        Components\GridPostsRepeater\getACFLayout(),
        Components\SliderTrainees\getACFLayout(),
        Components\BlockTwoPages\getACFLayout(),
        Components\LatestPublications\getACFLayout(),
        Components\InFocus\getACFLayout(),
    ];
    $researchLayouts = [
        Components\ProjectWidget\getACFLayout(),
        Components\BlockImageText\getACFLayout(),
        Components\BlockWysiwyg\getACFLayout(),
        Components\ResearchTags\getACFLayout(),
        Components\AccordionDefault\getACFLayout(),
        Components\BlockVideoOembed\getACFLayout(),
        Components\BlockMailingList\getACFLayout(),
        Components\BlockWysiwygTwoCol\getACFLayout(),
        Components\SliderImages\getACFLayout(),
        Components\SliderImagesCentered\getACFLayout(),
        Components\SliderImageGallery\getACFLayout(),
        Components\BlockTextImageCrop\getACFLayout(),
        Components\BlockPostTabs\getACFLayout(),
        Components\LatestPublications\getACFLayout(),
        Components\InFocus\getACFLayout(),
    ];
    $publicationsLayouts = [
        Components\ProjectWidget\getACFLayout(),
        Components\BlockImageText\getACFLayout(),
        Components\BlockWysiwyg\getACFLayout(),
        Components\ResearchTags\getACFLayout(),
        Components\AccordionDefault\getACFLayout(),
        Components\BlockVideoOembed\getACFLayout(),
        Components\BlockMailingList\getACFLayout(),
        Components\BlockWysiwygTwoCol\getACFLayout(),
        Components\SliderImages\getACFLayout(),
        Components\SliderImagesCentered\getACFLayout(),
        Components\SliderImageGallery\getACFLayout(),
        Components\BlockTextImageCrop\getACFLayout(),
        Components\BlockPostTabs\getACFLayout(),
        Components\LatestPublications\getACFLayout(),
        Components\InFocus\getACFLayout(),
    ];
    $resourceLayouts = [
        Components\BlockWysiwyg\getACFLayout(),
        Components\BlockImageText\getACFLayout(),
        Components\BlockWysiwygTwoCol\getACFLayout(),
        Components\BlockImage\getACFLayout(),
        Components\BlockVideoOembed\getACFLayout(),
        Components\AccordionDefault\getACFLayout(),
        Components\SliderImages\getACFLayout(),
        Components\SliderImagesCentered\getACFLayout(),
        Components\SliderImageGallery\getACFLayout(),
        Components\BlockTextImageCrop\getACFLayout(),
        Components\BlockMailingList\getACFLayout(),
    ];
    ACFComposer::registerFieldGroup([
        'name' => 'pageComponents',
        'title' => __('Page Components', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'name' => 'pageComponents',
                'label' => __('Page Components', 'flynt'),
                'type' => 'flexible_content',
                'button_label' => __('Add Component', 'flynt'),
                'layouts' => $pageLayouts,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page'
                ],
            ],
        ],
    ]);
    ACFComposer::registerFieldGroup([
        'name' => 'researchComponents',
        'title' => __('Research Components', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'name' => 'pageComponents',
                'label' => __('Research Components', 'flynt'),
                'type' => 'flexible_content',
                'button_label' => __('Add Component', 'flynt'),
                'layouts' => $researchLayouts,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'research'
                ],
            ]
        ],
    ]);
    ACFComposer::registerFieldGroup([
        'name' => 'publicationsComponents',
        'title' => __('Publications Components', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'name' => 'pageComponents',
                'label' => __('Publications Components', 'flynt'),
                'type' => 'flexible_content',
                'button_label' => __('Add Component', 'flynt'),
                'layouts' => $publicationsLayouts,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'publications'
                ],
            ]
        ],
    ]);
    ACFComposer::registerFieldGroup([
        'name' => 'resourceComponents',
        'title' => __('Additional Resource Page Content', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'name' => 'pageComponents',
                'label' => __('Additional Resource Page Content', 'flynt'),
                'type' => 'flexible_content',
                'button_label' => __('Add Component', 'flynt'),
                'layouts' => $resourceLayouts,
                'instructions' => __('Optional blocks shown below the standard Resource details and destination button.', 'flynt'),
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
    ACFComposer::registerFieldGroup([
        'name' => 'impactComponents',
        'title' => __('Impact Components', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'name' => 'pageComponents',
                'label' => __('Impact Components', 'flynt'),
                'type' => 'flexible_content',
                'button_label' => __('Add Component', 'flynt'),
                'layouts' => $pageLayouts,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'impact'
                ],
            ],
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'impact-options'
                ],
            ]
        ],
    ]);
    ACFComposer::registerFieldGroup([
        'name'   => 'researchDetails',
        'title'  => __('Research Details', 'flynt'),
        'fields' => [
            [
                'label'        => 'Project ID',
                'name'         => 'projectId',
                'type'         => 'text',
                'instructions' => __('Numeric project identifier (e.g. 756). Used to auto-link this research post from the Project Widget on publications.', 'flynt'),
                'required'     => 0,
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'research',
                ],
            ],
        ],
    ]);
});
