<?php

/**
 * Structure the Policy Makers and Researchers page media as editable ACF
 * components instead of relying on raw WYSIWYG styles and iframe markup.
 *
 * Run with the Local MySQL socket configured:
 * php -d mysqli.default_socket=/path/to/mysqld.sock tools/fix-audience-pages.php
 */

define('PRU_MIGRATION_LIBRARY_ONLY', true);
require_once __DIR__ . '/migrate-pru-content.php';

const PRU_GUIDE_VIDEO_URL = 'https://www.youtube.com/watch?v=XJIYrK5hw6o';
const PRU_GUIDE_VIDEO_POSTER_URL = 'https://i.ytimg.com/vi/XJIYrK5hw6o/maxresdefault.jpg';

function pruAudiencePage(string $slug): WP_Post
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    if (!$page instanceof WP_Post) {
        throw new RuntimeException(sprintf('Page "%s" was not found.', $slug));
    }
    return $page;
}

function pruAudienceImageId(string $html, string $fileFragment): int
{
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//img[@src]') as $image) {
        if (!$image instanceof DOMElement || !str_contains($image->getAttribute('src'), $fileFragment)) {
            continue;
        }
        if (preg_match('/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $image->getAttribute('class'), $matches)) {
            return (int) $matches[1];
        }
        return (int) attachment_url_to_postid($image->getAttribute('src'));
    }
    return 0;
}

function pruAudienceMediaId($media): int
{
    if (is_object($media) && isset($media->ID)) {
        return (int) $media->ID;
    }
    if (is_array($media)) {
        return (int) ($media['ID'] ?? $media['id'] ?? 0);
    }
    return (int) $media;
}

function pruAudienceUpdatePolicyPage(): void
{
    $page = pruAudiencePage('for-policy-makers');
    $components = get_field('pageComponents', $page->ID);
    if (!is_array($components)) {
        throw new RuntimeException('For policy makers has no editable components.');
    }

    add_post_meta($page->ID, '_pru_audience_pages_backup_20261001', $components, true);

    $wysiwygIndex = null;
    $desktopImageId = 0;
    $mobileImageId = 0;
    foreach ($components as $index => $component) {
        if (($component['acf_fc_layout'] ?? '') !== 'blockWysiwyg') {
            continue;
        }
        $html = (string) ($component['contentHtml'] ?? '');
        if (!str_contains($html, 'Rapid Response Facility') || !str_contains($html, 'AskPRU')) {
            continue;
        }
        $wysiwygIndex = $index;
        $desktopImageId = pruAudienceImageId($html, 'Enquiry_desktop');
        $mobileImageId = pruAudienceImageId($html, 'Enquiry_Mobile');
        break;
    }

    if ($wysiwygIndex === null || !$desktopImageId || !$mobileImageId) {
        foreach ($components as $component) {
            if (($component['acf_fc_layout'] ?? '') !== 'blockResponsiveImage') {
                continue;
            }
            $desktopImageId = pruAudienceMediaId($component['desktopImage'] ?? 0);
            $mobileImageId = pruAudienceMediaId($component['mobileImage'] ?? 0);
        }
    }
    if ($wysiwygIndex === null || !$desktopImageId || !$mobileImageId) {
        throw new RuntimeException('Policy page text or responsive images could not be identified.');
    }

    update_post_meta($desktopImageId, '_wp_attachment_image_alt', 'NIHR PRU enquiry process');
    update_post_meta($mobileImageId, '_wp_attachment_image_alt', 'NIHR PRU enquiry process');

    $contactUrl = esc_url(get_permalink(pruAudiencePage('contact')));
    $components[$wysiwygIndex]['contentHtml'] = sprintf(
        '<p>The PRU regularly engages with both public representatives and policy makers. We have a dedicated public involvement group and a rapid response service for policy makers.</p>' .
        '<h2>Rapid Response Facility</h2>' .
        '<p>Through our Rapid Response Facility (RRF), we develop timely policy responses and respond to calls for evidence from the Department for Health and Social Care (DHSC). This facility enables us to work in close collaboration with our policy colleagues. Our model is based on AskFuse led by our collaborators in the NIHR School for Public Health Research.</p>' .
        '<h2>AskPRU</h2>' .
        '<p>In November 2019 we launched our policy advice service, AskPRU.</p>' .
        '<p>This service enables policy stakeholders to contact the Unit for advice and an initial discussion on any areas of behavioural science. Bookable dates and timeslots are identified in advance and PRU staff will provide a written report of the meeting, with an option for a follow-up call.</p>' .
        '<div class="wysiwygActions"><a class="button" href="%s">Contact AskPRU</a></div>',
        $contactUrl
    );

    $responsiveImage = [
        'acf_fc_layout' => 'blockResponsiveImage',
        'desktopImage' => $desktopImageId,
        'mobileImage' => $mobileImageId,
        'options' => [
            'theme' => 'white',
            'size' => 'medium',
        ],
    ];

    $responsiveIndex = null;
    foreach ($components as $index => $component) {
        if (($component['acf_fc_layout'] ?? '') === 'blockResponsiveImage') {
            $responsiveIndex = $index;
            break;
        }
    }
    if ($responsiveIndex === null) {
        array_splice($components, $wysiwygIndex + 1, 0, [$responsiveImage]);
    } else {
        $components[$responsiveIndex] = $responsiveImage;
    }

    update_field('field_pageComponents_pageComponents', $components, $page->ID);
    clean_post_cache($page->ID);
    pruLog(sprintf('For policy makers updated for page %d.', $page->ID));
}

function pruAudienceUpdateResearchersPage(): void
{
    $page = pruAudiencePage('for-researchers');
    $components = get_field('pageComponents', $page->ID);
    if (!is_array($components)) {
        throw new RuntimeException('For Researchers has no editable components.');
    }

    add_post_meta($page->ID, '_pru_audience_pages_backup_20261001', $components, true);

    $wysiwygIndex = null;
    $pdfUrl = '';
    foreach ($components as $index => $component) {
        if (($component['acf_fc_layout'] ?? '') !== 'blockWysiwyg') {
            continue;
        }
        $html = (string) ($component['contentHtml'] ?? '');
        if (!str_contains($html, 'Guide to writing policy briefs')) {
            continue;
        }
        $wysiwygIndex = $index;
        if (preg_match('#https?://[^"\']+\.pdf#i', $html, $matches)) {
            $pdfUrl = $matches[0];
        }
        break;
    }
    if ($wysiwygIndex === null) {
        throw new RuntimeException('Researchers guide content could not be identified.');
    }
    if ($pdfUrl === '') {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            's' => '11Oct2021 A practical guide to writing policy briefs',
        ]);
        if ($attachments) {
            $pdfUrl = (string) wp_get_attachment_url($attachments[0]->ID);
        }
    }
    if ($pdfUrl === '') {
        throw new RuntimeException('The locally imported policy briefs PDF was not found.');
    }

    $components[$wysiwygIndex]['contentHtml'] = sprintf(
        '<h2>Guide to writing policy briefs</h2>' .
        '<h3>Research engagement with policy makers: a practical guide to writing policy briefs</h3>' .
        '<p>Policy briefs are short evidence summaries written by researchers to inform the development or implementation of policy. This guide has been developed to support researchers to write effective policy briefs. It is jointly produced by the NIHR Policy Research Unit in Behavioural and Social Sciences (BehSocSciPRU) and the UCL Centre for Behaviour Change (CBC). It has been written in consultation with policy advisers and synthesises current evidence and expert opinion on what makes an effective policy brief.</p>' .
        '<p>It is for any researcher who wishes to increase the impact of their work by activity that may influence the process of policy formation, implementation or evaluation. Whilst the guide has been written primarily for a UK audience, it is hoped that it will be useful to researchers in other countries.</p>' .
        '<p>This guide has been downloaded from Open Science Framework over 11,000 times.</p>' .
        '<div class="wysiwygActions"><a class="button" href="%1$s">Download the policy brief guide (PDF)</a><a class="button button--outlined" href="https://osf.io/m25qp/" target="_blank" rel="noopener">View on Open Science Framework</a></div>',
        esc_url($pdfUrl)
    );

    $posterId = pruSideload(
        PRU_GUIDE_VIDEO_POSTER_URL,
        $page->ID,
        'Policy briefs guide launch video'
    );
    if (!$posterId) {
        throw new RuntimeException('Unable to import the policy briefs video poster.');
    }
    wp_update_post([
        'ID' => $posterId,
        'post_title' => 'Policy briefs guide launch video',
    ]);

    $video = [
        'acf_fc_layout' => 'blockVideoOembed',
        'heading' => 'Watch the launch',
        'posterImage' => $posterId,
        'oembed' => PRU_GUIDE_VIDEO_URL,
        'button' => '',
        'options' => [
            'theme' => 'white',
            'size' => 'medium',
            'displayStyle' => 'default',
        ],
    ];

    $videoIndex = null;
    foreach ($components as $index => $component) {
        if (($component['acf_fc_layout'] ?? '') === 'blockVideoOembed') {
            $videoIndex = $index;
            break;
        }
    }
    if ($videoIndex === null) {
        array_splice($components, $wysiwygIndex + 1, 0, [$video]);
    } else {
        $components[$videoIndex] = $video;
    }

    update_field('field_pageComponents_pageComponents', $components, $page->ID);
    clean_post_cache($page->ID);
    pruLog(sprintf('For Researchers updated for page %d.', $page->ID));
}

try {
    pruAudienceUpdatePolicyPage();
    pruAudienceUpdateResearchersPage();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Audience pages update failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
