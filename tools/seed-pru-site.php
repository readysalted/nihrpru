<?php

/**
 * Seed the NIHR PRU site structure, Figma homepage content, navigation and options.
 *
 * The script is deliberately repeatable. Every image used by an editable content
 * field is copied into the WordPress Media Library and stored as an attachment ID.
 *
 * Run with WP-CLI:
 * wp eval-file tools/seed-pru-site.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "This script must be run through WordPress.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Print a status line in WP-CLI and browser contexts.
 */
function pru_seed_log(string $message): void
{
    if (class_exists('WP_CLI')) {
        \WP_CLI::log($message);
        return;
    }

    echo esc_html($message) . "\n";
}

/**
 * Return a standard ACF link value.
 */
function pru_seed_link(string $title, string $url, string $target = ''): array
{
    return [
        'title' => $title,
        'url' => $url,
        'target' => $target,
    ];
}

/**
 * Create or update a public page by path.
 */
function pru_seed_page(string $title, string $slug, string $lead, string $body = ''): int
{
    // get_page_by_path() can return an attachment when asked for pages. Query the
    // page post type directly so an attachment with the same slug is never reused.
    $existingPages = get_posts([
        'post_type' => 'page',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'name' => $slug,
        'no_found_rows' => true,
    ]);
    $pageId = !empty($existingPages[0]) ? (int) $existingPages[0]->ID : 0;

    $savedId = wp_insert_post([
        'ID' => $pageId,
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => '',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
    ], true);

    if (is_wp_error($savedId)) {
        throw new RuntimeException($savedId->get_error_message());
    }

    $pageId = (int) $savedId;
    update_post_meta($pageId, '_pru_seed_page', $slug);
    delete_post_meta($pageId, '_pru_seed_asset');
    delete_post_meta($pageId, '_wp_attached_file');
    delete_post_meta($pageId, '_wp_attachment_metadata');

    $components = [
        [
            'acf_fc_layout' => 'BlockHero',
            'title' => $title,
            'contentHtml' => $lead !== '' ? '<p>' . esc_html($lead) . '</p>' : '',
            'image' => 0,
        ],
    ];

    if ($body !== '') {
        $components[] = [
            'acf_fc_layout' => 'blockWysiwyg',
            'contentHtml' => wp_kses_post($body),
            'options' => [
                'theme' => 'white',
                'size' => 'medium',
                'alignment' => 'left',
                'textAlignment' => 'left',
                'displayStyle' => 'default',
            ],
        ];
    }

    update_field('field_pageComponents_pageComponents', $components, $pageId);

    return $pageId;
}

/**
 * Copy one approved Figma asset into the Media Library and return its ID.
 */
function pru_seed_attachment(string $filename, string $title, string $alt): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_pru_seed_asset',
        'meta_value' => $filename,
        'no_found_rows' => true,
    ]);

    if (!empty($existing[0])) {
        $attachmentId = (int) $existing[0];
        wp_update_post([
            'ID' => $attachmentId,
            'post_title' => $title,
        ]);
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $alt);
        return $attachmentId;
    }

    $sourcePath = get_stylesheet_directory() . '/assets/images/pru/' . $filename;
    if (!is_readable($sourcePath)) {
        throw new RuntimeException(sprintf('Missing PRU image asset: %s', $filename));
    }

    $contents = file_get_contents($sourcePath);
    if ($contents === false) {
        throw new RuntimeException(sprintf('Unable to read PRU image asset: %s', $filename));
    }

    $upload = wp_upload_bits($filename, null, $contents);
    if (!empty($upload['error'])) {
        throw new RuntimeException((string) $upload['error']);
    }

    $filetype = wp_check_filetype($filename);
    $attachmentId = wp_insert_attachment([
        'post_mime_type' => (string) ($filetype['type'] ?? 'application/octet-stream'),
        'post_title' => $title,
        'post_content' => '',
        'post_status' => 'inherit',
    ], (string) $upload['file']);

    if (is_wp_error($attachmentId)) {
        throw new RuntimeException($attachmentId->get_error_message());
    }

    $attachmentId = (int) $attachmentId;
    $metadata = wp_generate_attachment_metadata($attachmentId, (string) $upload['file']);
    if (is_array($metadata)) {
        wp_update_attachment_metadata($attachmentId, $metadata);
    }

    update_post_meta($attachmentId, '_wp_attachment_image_alt', $alt);
    update_post_meta($attachmentId, '_pru_seed_asset', $filename);

    return $attachmentId;
}

/**
 * Replace a named menu and assign it to one or more locations.
 */
function pru_seed_menu(string $name, array $pageIds, array $locations): int
{
    $menu = wp_get_nav_menu_object($name);
    $menuId = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    if ($menuId <= 0) {
        throw new RuntimeException(sprintf('Unable to create menu: %s', $name));
    }

    foreach (wp_get_nav_menu_items($menuId) ?: [] as $item) {
        wp_delete_post((int) $item->ID, true);
    }

    foreach ($pageIds as $pageId) {
        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-object-id' => (int) $pageId,
            'menu-item-object' => 'page',
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-title' => get_the_title((int) $pageId),
        ]);
    }

    $assigned = get_theme_mod('nav_menu_locations', []);
    foreach ($locations as $location) {
        $assigned[$location] = $menuId;
    }
    set_theme_mod('nav_menu_locations', $assigned);

    return $menuId;
}

$assetDefinitions = [
    'hero' => ['hero.jpg', 'NIHR PRU homepage hero', 'People taking part in an NIHR PRU workshop'],
    'audience-policy' => ['audience-policy-makers.jpg', 'For policy makers', 'People collaborating around a table'],
    'audience-research' => ['audience-researchers.jpg', 'For researchers', 'Researcher working at a desk'],
    'partner-newcastle' => ['partner-logo-newcastle.png', 'Newcastle University', 'Newcastle University'],
    'partner-ucl' => ['partner-logo-ucl.png', 'UCL', 'University College London'],
    'partner-warwick' => ['partner-logo-warwick.png', 'University of Warwick', 'University of Warwick'],
    'partner-lshtm' => ['partner-logo-lshtm.png', 'London School of Hygiene and Tropical Medicine', 'London School of Hygiene and Tropical Medicine'],
    'partner-nihr' => ['partner-logo-nihr.png', 'National Institute for Health and Care Research', 'National Institute for Health and Care Research'],
    'team-emily' => ['team-emily-oliver.png', 'Professor Emily Oliver', 'Professor Emily Oliver'],
    'team-falko' => ['team-falko-sniehotta.png', 'Professor Falko Sniehotta', 'Professor Falko Sniehotta'],
    'team-ivo' => ['team-ivo-vlaev.png', 'Professor Ivo Vlaev', 'Professor Ivo Vlaev'],
    'team-chris' => ['team-chris-bonell.png', 'Professor Chris Bonell', 'Professor Chris Bonell'],
    'team-mike' => ['team-mike-kelly.png', 'Professor Mike Kelly', 'Professor Mike Kelly'],
    'team-angel' => ['team-angel-chater.png', 'Professor Angel Chater', 'Professor Angel Chater'],
    'project-regist-vac' => ['project-regist-vac.png', 'Regist Vac project', 'Hands holding a vaccine information card'],
    'project-medical-tourism' => ['project-bubbles-coal.png', 'Medical tourism project', 'Abstract bubbles representing behavioural and social insights'],
    'public-involvement' => ['public-involvement-graphic.jpg', 'Public involvement', 'People contributing ideas during a group workshop'],
    'latest-output' => ['public-involvement.png', 'Latest output', 'Cover of the latest NIHR PRU research output'],
    'covid-response' => ['covid-response.png', 'COVID-19 response', 'Illustration of the COVID-19 response programme'],
    'policy-guide' => ['policy-brief-guide.png', 'Guide to writing policy briefs', 'Cover of the guide to writing policy briefs'],
];

$assets = [];
foreach ($assetDefinitions as $key => [$filename, $title, $alt]) {
    $assets[$key] = pru_seed_attachment($filename, $title, $alt);
}
pru_seed_log(sprintf('Media Library: %d approved design assets ready.', count($assets)));

$pages = [];
$pages['projects'] = pru_seed_page(
    'Our Projects',
    'our-projects',
    'Explore the research projects delivered by the NIHR Policy Research Unit in Behavioural and Social Sciences.',
    '<h2>Current and completed research</h2><p>Our work applies behavioural and social science evidence, theory and methods to questions identified with policy makers, patients and the public.</p>'
);
$pages['outputs'] = pru_seed_page(
    'Outputs',
    'outputs',
    'Read publications, reports, briefings and practical resources produced by the Unit.',
    '<h2>Evidence for policy and practice</h2><p>We share findings in formats designed to support timely and informed decision-making.</p>'
);
$pages['policy-makers'] = pru_seed_page(
    'For Policy Makers',
    'for-policy-makers',
    'We work closely with policy makers to provide relevant behavioural and social science evidence.',
    '<h2>Working with policy</h2><p>Our team can support policy design, development, evaluation and implementation with rapid and robust evidence.</p>'
);
$pages['researchers'] = pru_seed_page(
    'For Researchers',
    'for-researchers',
    'Find opportunities, methods and resources for behavioural and social science research.',
    '<h2>Research collaboration</h2><p>We bring together researchers from different disciplines and institutions to address health and social care priorities.</p>'
);
$pages['public'] = pru_seed_page(
    'For the Public',
    'for-the-public',
    'Patients and members of the public help shape our research at every stage.',
    '<h2>Public involvement and engagement</h2><p>Our dedicated patient and public involvement and engagement strategy group advises on project protocols, evidence reviews and how findings are shared.</p>'
);
$pages['about'] = pru_seed_page(
    'About Us',
    'about-us',
    'We are the NIHR Policy Research Unit in Behavioural and Social Sciences.',
    '<h2>About the Unit</h2><p>Established in 2019 and funded for a further five years from 2024, the Unit is hosted by Newcastle University and works with leading partner institutions across the UK.</p>'
);
$pages['collaborators'] = pru_seed_page(
    'Our Collaborators',
    'our-collaborators',
    'Our collaborative network brings together academic, policy, public and patient expertise.',
    '<h2>Working in partnership</h2><p>We collaborate across Newcastle University, UCL, the University of Warwick, the London School of Hygiene and Tropical Medicine, NIHR and a wider network of experts.</p>'
);
$pages['team'] = pru_seed_page(
    'Our Team',
    'our-team',
    'Meet the researchers, professional staff and public contributors working across the Unit.',
    '<h2>A multidisciplinary team</h2><p>Our team combines expertise across behavioural science, public health, sociology, psychology, health services research and public involvement.</p>'
);
$pages['contact'] = pru_seed_page(
    'Contact',
    'contact',
    'Get in touch with the NIHR Policy Research Unit in Behavioural and Social Sciences.',
    '<h2>Contact us</h2><p>NIHR PRU Behavioural and Social Sciences<br>Newcastle University<br>Baddiley-Clark Building<br>Richardson Road<br>Newcastle upon Tyne<br>NE2 4AX</p><p>Telephone: 0191 208 3463<br>Email: <a href="mailto:NIHRPRU.BehSocSci@newcastle.ac.uk">NIHRPRU.BehSocSci@newcastle.ac.uk</a></p>'
);
$pages['public-involvement'] = pru_seed_page(
    'How We Work With the Public',
    'how-we-work-with-the-public',
    'Public involvement is embedded throughout the Unit and its research.',
    '<h2>Patient and public involvement and engagement</h2><p>A dedicated group of external patient and public representatives advises the Unit and helps shape research from planning through to communication.</p>'
);
$pages['covid'] = pru_seed_page(
    'COVID-19 Response',
    'covid-19-response',
    'Our response projects supported vaccination, antibody testing and public health messaging.',
    '<p>The PRU undertook seven projects in response to the COVID-19 pandemic, including research relating to the vaccination rollout programme, antibody testing and public health messaging.</p>'
);
$pages['policy-guide'] = pru_seed_page(
    'Guide to Writing Policy Briefs',
    'guide-to-writing-policy-briefs',
    'A practical guide to writing clear, useful policy briefs.',
    '<p>Policy briefs are short evidence summaries written by researchers to inform the development or implementation of policy. This guide supports researchers to write effective policy briefs.</p>'
);
$pages['site-map'] = pru_seed_page('Site Map', 'site-map', 'Browse all public pages and posts on this website.', '[wp_sitemap_page]');
$pages['privacy'] = pru_seed_page('Privacy Policy', 'privacy-policy', 'How this website handles personal information.', '<p>This page explains how personal information is handled when you use this website. The final policy will be reviewed with the site owner before launch.</p>');
$pages['cookies'] = pru_seed_page('Cookie Policy', 'cookie-policy', 'Information about cookies used on this website.', '<p>This page describes the cookies used by the website and how visitors can manage their preferences. The final cookie list will be confirmed before launch.</p>');
$pages['terms'] = pru_seed_page('Terms of Use', 'terms', 'Terms that apply when using this website.', '<p>The final terms of use will be reviewed with the site owner before launch.</p>');
$pages['accessibility'] = pru_seed_page('Accessibility', 'accessibility', 'Our approach to making this website accessible.', '<p>We are committed to providing a website that is accessible to as many people as possible. A full accessibility statement will be published following pre-launch testing.</p>');
pru_seed_log(sprintf('Pages: %d published pages ready.', count($pages)));

$home = get_page_by_path('home', OBJECT, 'page');
$homeId = wp_insert_post([
    'ID' => $home instanceof WP_Post ? (int) $home->ID : 0,
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_title' => 'Home',
    'post_name' => 'home',
    'post_content' => '',
    'comment_status' => 'closed',
    'ping_status' => 'closed',
], true);

if (is_wp_error($homeId)) {
    throw new RuntimeException($homeId->get_error_message());
}
$homeId = (int) $homeId;
update_post_meta($homeId, '_pru_seed_page', 'home');

$pinned = get_posts([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_key' => '_pru_legacy_post_id',
    'meta_value' => 3260,
    'no_found_rows' => true,
]);
if (empty($pinned[0])) {
    $pinned = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ]);
}
$pinnedId = (int) ($pinned[0] ?? 0);

$homeComponents = [
    [
        'acf_fc_layout' => 'BlockImageTextHero',
        'contentHtml' => '<h1>Informing health and social care policy</h1><p>We use behavioural and social sciences evidence and expertise to inform health and social care policy</p>',
        'image' => $assets['hero'],
        'button' => [],
        'options' => [
            'theme' => 'navy',
            'displayStyle' => 'homepage',
        ],
    ],
    [
        'acf_fc_layout' => 'BlockPostTabs',
        'text' => 'Latest News & Events',
        'contentType' => 1,
        'relationship' => [],
        'pullLatestFromCategory' => 1,
        'selectedCategory' => null,
        'pinnedPost' => $pinnedId,
        'latestPostsCount' => 3,
        'buttonLabel' => 'Read the full news story',
        'options' => [
            'theme' => 'putty',
            'displayStyle' => 'homepage',
        ],
    ],
    [
        'acf_fc_layout' => 'blockWysiwyg',
        'contentHtml' => '<h2>National Institute for Health and Care Research (NIHR) Policy Research Unit (PRU) in Behavioural and Social Sciences</h2><p>The aim of the NIHR Policy Research Unit (PRU) in Behavioural and Social Sciences is to inform government policy on health, preventing ill-health and health systems. We use behavioural and social science evidence, theory and methods to support decision-making.</p>',
        'options' => [
            'theme' => 'white',
            'size' => 'large',
            'alignment' => 'center',
            'textAlignment' => 'center',
            'displayStyle' => 'homepageIntro',
        ],
    ],
    [
        'acf_fc_layout' => 'gridImageText',
        'titleAlignment' => 'left',
        'preContentHtml' => '',
        'items' => [
            [
                'image' => $assets['audience-policy'],
                'link' => pru_seed_link('For policy makers', get_permalink($pages['policy-makers'])),
                'imageMobile' => 0,
                'contentHtml' => '<h2>For policy makers</h2><p>Access behavioural and social science evidence to support policy design, development, evaluation and implementation.</p>',
            ],
            [
                'image' => $assets['audience-research'],
                'link' => pru_seed_link('For researchers', get_permalink($pages['researchers'])),
                'imageMobile' => 0,
                'contentHtml' => '<h2>For researchers</h2><p>Explore our research, methods, collaboration opportunities and resources for engaging with policy.</p>',
            ],
        ],
        'button' => [],
        'options' => [
            'theme' => 'white',
            'maxColumns' => 2,
            'card' => 0,
            'displayStyle' => 'homepageFeatures',
        ],
    ],
    [
        'acf_fc_layout' => 'blockWysiwyg',
        'contentHtml' => '<h2>Working together to support better policy</h2><p>Established initially in 2019 and funded in 2024 for a further five years, the NIHR Policy Research Unit in Behavioural and Social Sciences is hosted by Newcastle University in collaboration with:</p>',
        'options' => [
            'theme' => 'white',
            'size' => 'large',
            'alignment' => 'center',
            'textAlignment' => 'center',
            'displayStyle' => 'homepageIntro',
        ],
    ],
    [
        'acf_fc_layout' => 'listLogos',
        'preContentHtml' => '',
        'items' => [
            ['link' => pru_seed_link('Newcastle University', 'https://www.ncl.ac.uk/', '_blank'), 'image' => $assets['partner-newcastle']],
            ['link' => pru_seed_link('UCL', 'https://www.ucl.ac.uk/', '_blank'), 'image' => $assets['partner-ucl']],
            ['link' => pru_seed_link('University of Warwick', 'https://warwick.ac.uk/', '_blank'), 'image' => $assets['partner-warwick']],
            ['link' => pru_seed_link('London School of Hygiene and Tropical Medicine', 'https://www.lshtm.ac.uk/', '_blank'), 'image' => $assets['partner-lshtm']],
            ['link' => pru_seed_link('NIHR', 'https://www.nihr.ac.uk/', '_blank'), 'image' => $assets['partner-nihr']],
        ],
        'options' => [
            'theme' => 'white',
            'card' => 0,
            'displayStyle' => 'homepagePartners',
        ],
    ],
    [
        'acf_fc_layout' => 'blockWysiwyg',
        'contentHtml' => '<p>We also have a collaborating network of experts in public health and public and patient involvement and engagement (PPIE). We bring together researchers with a range of disciplinary backgrounds and expertise, to support policy makers in policy design, development, evaluation and implementation. We work closely with the Department of Health and Social Care and in collaboration with other Policy Research Units. This enables us to provide the best evidence and advice, in a timely way, for the benefit of the public and patients.</p><p>This project is funded by the National Institute for Health and Care Research (NIHR) [Policy Research Unit in Behavioural and Social Sciences (project reference NIHR206124)]. The views expressed are those of the author(s) and not necessarily those of the NIHR or the Department of Health and Social Care.</p>',
        'options' => [
            'theme' => 'white',
            'size' => 'medium',
            'alignment' => 'center',
            'textAlignment' => 'left',
            'displayStyle' => 'default',
        ],
    ],
    [
        'acf_fc_layout' => 'meetTheTeam',
        'preContentHtml' => '<h2>Our team</h2>',
        'backgroundColor' => '#C4C5FF',
        'teamMembers' => [
            ['image' => $assets['team-emily'], 'title' => 'Professor Emily Oliver', 'position' => 'Director', 'link' => []],
            ['image' => $assets['team-falko'], 'title' => 'Professor Falko Sniehotta', 'position' => 'Co Director', 'link' => []],
            ['image' => $assets['team-ivo'], 'title' => 'Professor Ivo Vlaev', 'position' => 'Co Investigator', 'link' => []],
            ['image' => $assets['team-chris'], 'title' => 'Professor Chris Bonell', 'position' => 'Co Investigator', 'link' => []],
            ['image' => $assets['team-mike'], 'title' => 'Professor Mike Kelly', 'position' => 'Co Investigator', 'link' => []],
            ['image' => $assets['team-angel'], 'title' => 'Professor Angel Chater', 'position' => 'Co Director', 'link' => []],
        ],
        'button' => pru_seed_link('See the full team', get_permalink($pages['team'])),
        'options' => ['displayStyle' => 'homepageTeam'],
    ],
    [
        'acf_fc_layout' => 'gridImageText',
        'titleAlignment' => 'left',
        'preContentHtml' => '<h2>Latest Projects</h2>',
        'items' => [
            [
                'image' => $assets['project-regist-vac'],
                'link' => pru_seed_link('Regist Vac', get_permalink($pages['projects'])),
                'imageMobile' => 0,
                'contentHtml' => '<h3>Regist Vac: A rapid evidence synthesis and behavioural insights approach to understanding vaccine registration</h3>',
            ],
            [
                'image' => $assets['project-medical-tourism'],
                'link' => pru_seed_link('Behavioural and Social Insights into Medical Tourism', get_permalink($pages['projects'])),
                'imageMobile' => 0,
                'contentHtml' => '<h3>Behavioural and Social Insights into Medical Tourism</h3>',
            ],
        ],
        'button' => pru_seed_link('See all projects', get_permalink($pages['projects'])),
        'options' => [
            'theme' => 'putty',
            'maxColumns' => 2,
            'card' => 0,
            'displayStyle' => 'homepageProjects',
        ],
    ],
    [
        'acf_fc_layout' => 'blockImageText',
        'imagePosition' => 'right',
        'image' => $assets['public-involvement'],
        'contentHtml' => '<h2>How we work with the public</h2><p>Within the Unit we have a dedicated team of external patient and public representatives, who form our patient and public involvement and engagement (PPIE) strategy group. This group advise on the involvement of patients and the public at each stage of our research, including reviewing project protocols and evidence reviews. Two members of this group attend our management meetings and all members are invited to take part in staff training and collaboration events.</p><p><a class="button" href="' . esc_url(get_permalink($pages['public-involvement'])) . '">Find out more</a></p>',
        'options' => ['theme' => 'pink-quartz', 'withoutPadding' => 0, 'displayStyle' => 'homepageFeature'],
    ],
    [
        'acf_fc_layout' => 'blockImageText',
        'imagePosition' => 'right',
        'image' => $assets['latest-output'],
        'contentHtml' => '<p><strong>Latest outputs</strong></p><h2>Exploring health professionals’ responses to patient-raised complaints: A qualitative interview study and theoretical analysis using the Theoretical Domains Framework and COM-B</h2><p>June 2024</p><p><a class="button" href="' . esc_url(get_permalink($pages['outputs'])) . '">Click here to download</a></p>',
        'options' => ['theme' => 'navy', 'withoutPadding' => 0, 'displayStyle' => 'homepageFeature'],
    ],
    [
        'acf_fc_layout' => 'blockImageText',
        'imagePosition' => 'right',
        'image' => $assets['covid-response'],
        'contentHtml' => '<h2>Covid-19 response</h2><p>The PRU has undertaken a further seven projects in response to the COVID-19 pandemic. These relate to the vaccination rollout programme, antibody testing and public health messaging.</p><p><a class="button" href="' . esc_url(get_permalink($pages['covid'])) . '">Find out more</a></p>',
        'options' => ['theme' => 'white', 'withoutPadding' => 0, 'displayStyle' => 'homepageFeature'],
    ],
    [
        'acf_fc_layout' => 'blockImageText',
        'imagePosition' => 'right',
        'image' => $assets['policy-guide'],
        'contentHtml' => '<h2>Guide to writing policy briefs</h2><p>Research engagement with policy makers: a practical guide to writing policy briefs. Policy briefs are short evidence summaries written by researchers to inform the development or implementation of policy. This guide has been developed to support researchers to write effective policy briefs.</p><p><a class="button" href="' . esc_url(get_permalink($pages['policy-guide'])) . '">Find out more</a></p>',
        'options' => ['theme' => 'blue-flame', 'withoutPadding' => 0, 'displayStyle' => 'homepageFeature'],
    ],
];

if (!update_field('field_pageComponents_pageComponents', $homeComponents, $homeId)) {
    $savedComponents = get_field('pageComponents', $homeId);
    if (!is_array($savedComponents) || count($savedComponents) !== count($homeComponents)) {
        throw new RuntimeException('Unable to save the Home page component data.');
    }
}

update_option('show_on_front', 'page');
update_option('page_on_front', $homeId);
update_option('page_for_posts', 0);
pru_seed_log(sprintf('Home: %d editable components saved.', count($homeComponents)));

$primaryPageIds = [
    $pages['projects'],
    $pages['outputs'],
    $pages['policy-makers'],
    $pages['researchers'],
    $pages['public'],
    $pages['about'],
    $pages['collaborators'],
];
pru_seed_menu('Primary Navigation', $primaryPageIds, ['navigation_main', 'navigation_burger']);
pru_seed_log('Navigation: primary desktop and mobile menus assigned.');

update_field('field_68d27f8c4f501', 1, 'option');
update_field('field_699ff21fbe234', pru_seed_link('Contact', get_permalink($pages['contact'])), 'option');

$footerExplore = array_map(
    static fn (int $pageId): array => ['link' => pru_seed_link(get_the_title($pageId), get_permalink($pageId))],
    array_merge([$homeId], $primaryPageIds)
);
$footerInformationIds = [$pages['site-map'], $pages['privacy'], $pages['cookies'], $pages['terms'], $pages['accessibility']];
$footerInformation = array_map(
    static fn (int $pageId): array => ['link' => pru_seed_link(get_the_title($pageId), get_permalink($pageId))],
    $footerInformationIds
);

update_field('field_translatable_NavigationFooter_text', 'NIHR PRU Behavioural and Social Sciences', 'option');
update_field('field_translatable_NavigationFooter_funded_by_logo', $assets['partner-nihr'], 'option');
update_field('field_translatable_NavigationFooter_funded_by_text', '<p>This project is funded by the National Institute for Health and Care Research (NIHR) [Policy Research Unit in Behavioural and Social Sciences (project reference NIHR206124)].</p>', 'option');
update_field('field_translatable_NavigationFooter_contentHtml', '<h4>Contact us</h4><p>Newcastle University, Baddiley-Clark Building, Richardson Road, Newcastle upon Tyne, NE2 4AX.<br>Telephone: 0191 208 3463</p><p><a href="mailto:NIHRPRU.BehSocSci@newcastle.ac.uk">NIHRPRU.BehSocSci@newcastle.ac.uk</a></p>', 'option');
update_field('field_translatable_NavigationFooter_column_two_title', 'Explore', 'option');
update_field('field_translatable_NavigationFooter_column_two', $footerExplore, 'option');
update_field('field_translatable_NavigationFooter_column_three_title', 'Information', 'option');
update_field('field_translatable_NavigationFooter_column_three', $footerInformation, 'option');
update_field('field_translatable_NavigationFooter_column_social_title', 'Follow us', 'option');
update_field('field_translatable_NavigationFooter_column_social', [
    ['image' => 0, 'link' => pru_seed_link('X', 'https://x.com/NIHR_PRUBaSS', '_blank')],
    ['image' => 0, 'link' => pru_seed_link('YouTube', 'https://youtube.com/channel/UCH9aqlK0rTi_fuTyQRRs7XQ', '_blank')],
    ['image' => 0, 'link' => pru_seed_link('LinkedIn', 'https://linkedin.com/company/nihr-pru-behavioural-and-social-sciences', '_blank')],
], 'option');
update_field('field_translatable_NavigationFooter_copyright_text', 'NIHR PRU Behavioural and Social Sciences © ' . wp_date('Y'), 'option');
update_field('field_translatable_NavigationFooter_copyright_menu', $footerInformation, 'option');
pru_seed_log('Theme options: header CTA and complete footer content saved.');

foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $postType) {
    $defaultPost = get_page_by_path($slug, OBJECT, $postType);
    if (
        $defaultPost instanceof WP_Post
        && !get_post_meta((int) $defaultPost->ID, '_pru_legacy_post_id', true)
        && !get_post_meta((int) $defaultPost->ID, '_pru_seed_page', true)
    ) {
        wp_delete_post((int) $defaultPost->ID, true);
    }
}

$oldPrivacy = get_page_by_path('privacy-policy-2', OBJECT, 'page');
if ($oldPrivacy instanceof WP_Post && !get_post_meta((int) $oldPrivacy->ID, '_pru_seed_page', true)) {
    wp_delete_post((int) $oldPrivacy->ID, true);
}

flush_rewrite_rules(false);
pru_seed_log('NIHR PRU site seed completed successfully.');
