<?php

/**
 * Keep legacy links working after consolidating duplicate pages and content types.
 */

namespace Flynt\Redirects;

add_action('template_redirect', function (): void {
    if (is_admin() || wp_doing_ajax() || is_preview()) {
        return;
    }

    $requestPath = '/' . trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') . '/';
    $redirects = [
        '/about/' => '/about-us/',
        '/about/staff/' => '/our-team/',
        '/about/our-collaborators/' => '/our-collaborators/',
        '/privacy/' => '/privacy-policy/',
        '/terms-and-conditions/' => '/terms/',
        '/outputs/guide-to-writing-policy-briefs/' => '/guide-to-writing-policy-briefs/',
        '/shared-medical-appointments/' => '/our-projects/shared-medical-appointments/',
        '/choose-well/' => '/our-projects/choose-well-project/',
        '/letters-to-healthcare-professionals/' => '/our-projects/letters-to-healthcare-professionals/',
        '/behavioural-science-network/' => '/our-projects/behavioural-science-network/',
    ];

    if (isset($redirects[$requestPath])) {
        wp_safe_redirect(home_url($redirects[$requestPath]), 301, 'NIHR PRU legacy URL migration');
        exit;
    }

    if (preg_match('#^/collaborators/([^/]+)/$#', $requestPath, $matches)) {
        $person = get_page_by_path(sanitize_title($matches[1]), OBJECT, 'person');
        if ($person instanceof \WP_Post) {
            wp_safe_redirect(get_permalink($person), 301, 'NIHR PRU legacy collaborator URL');
            exit;
        }
    }

    if (is_404()) {
        $legacyMatches = get_posts([
            'post_type' => ['page', 'research', 'person', 'post'],
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_pru_legacy_path',
            'meta_value' => $requestPath,
            'no_found_rows' => true,
        ]);
        if ($legacyMatches) {
            wp_safe_redirect(get_permalink((int) $legacyMatches[0]), 301, 'NIHR PRU migrated content URL');
            exit;
        }
    }
});
