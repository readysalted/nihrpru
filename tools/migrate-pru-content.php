<?php

/**
 * Idempotent NIHR PRU public-content migration.
 *
 * Run from the command line with WordPress' Local MySQL socket configured:
 * php -d mysqli.default_socket=/path/to/mysqld.sock tools/migrate-pru-content.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

const PRU_OLD_SITE = 'https://behscipru.nihr.ac.uk';

function pruLog(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function pruFetch(string $path, bool $json = true)
{
    $response = wp_remote_get(PRU_OLD_SITE . $path, [
        'timeout' => 90,
        'redirection' => 5,
        'user-agent' => 'NIHR-PRU-Migration/1.0',
    ]);
    if (is_wp_error($response)) {
        throw new RuntimeException($response->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        throw new RuntimeException(sprintf('HTTP %d for %s', $code, $path));
    }
    $body = wp_remote_retrieve_body($response);
    if (!$json) {
        return $body;
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON for ' . $path);
    }
    return $data;
}

function pruDecode(string $value): string
{
    return trim(html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function pruDom(string $html): DOMDocument
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML(
        '<?xml encoding="utf-8" ?><body>' . $html . '</body>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    return $dom;
}

function pruInnerHtml(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

function pruFindByLegacy(string $postType, string $sourceType, int $sourceId): int
{
    $ids = get_posts([
        'post_type' => $postType,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_pru_legacy_type', 'value' => $sourceType],
            ['key' => '_pru_legacy_id', 'value' => (string) $sourceId],
        ],
    ]);
    return $ids ? (int) $ids[0] : 0;
}

function pruUpsert(array $postData, string $sourceType, int $sourceId): int
{
    $postType = (string) $postData['post_type'];
    $postId = pruFindByLegacy($postType, $sourceType, $sourceId);
    if (!$postId && !empty($postData['post_name'])) {
        $existing = get_page_by_path($postData['post_name'], OBJECT, $postType);
        if ($existing instanceof WP_Post) {
            $postId = (int) $existing->ID;
        }
    }
    if ($postId) {
        $postData['ID'] = $postId;
        $result = wp_update_post(wp_slash($postData), true);
    } else {
        $result = wp_insert_post(wp_slash($postData), true);
    }
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
    $postId = (int) $result;
    update_post_meta($postId, '_pru_legacy_type', $sourceType);
    update_post_meta($postId, '_pru_legacy_id', $sourceId);
    return $postId;
}

function pruAttachmentByUrl(string $url): int
{
    $ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_pru_legacy_media_url',
        'meta_value' => $url,
    ]);
    return $ids ? (int) $ids[0] : 0;
}

function pruSideload(string $url, int $parentId = 0, string $alt = ''): int
{
    if ($url === '') {
        return 0;
    }
    $existing = pruAttachmentByUrl($url);
    if ($existing) {
        if ($parentId && !(int) wp_get_post_parent_id($existing)) {
            wp_update_post(['ID' => $existing, 'post_parent' => $parentId]);
        }
        return $existing;
    }

    $tmp = download_url($url, 90);
    if (is_wp_error($tmp)) {
        pruLog('  Media skipped: ' . $url . ' (' . $tmp->get_error_message() . ')');
        return 0;
    }
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    $name = sanitize_file_name(urldecode(basename($path)));
    if ($name === '' || !str_contains($name, '.')) {
        $name = 'imported-file-' . substr(md5($url), 0, 10);
    }
    $file = ['name' => $name, 'tmp_name' => $tmp];
    $attachmentId = media_handle_sideload($file, $parentId, $alt);
    if (is_wp_error($attachmentId)) {
        @unlink($tmp);
        pruLog('  Media skipped: ' . $url . ' (' . $attachmentId->get_error_message() . ')');
        return 0;
    }
    $attachmentId = (int) $attachmentId;
    update_post_meta($attachmentId, '_pru_legacy_media_url', $url);
    if ($alt !== '') {
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $alt);
    }
    return $attachmentId;
}

function pruMediaSource(int $legacyId): string
{
    if (!$legacyId) {
        return '';
    }
    try {
        $media = pruFetch('/wp-json/wp/v2/media/' . $legacyId . '?_fields=source_url');
        return (string) ($media['source_url'] ?? '');
    } catch (Throwable $exception) {
        pruLog('  Media metadata skipped: ' . $exception->getMessage());
        return '';
    }
}

function pruSetFeatured(int $postId, int $legacyMediaId, string $alt = ''): void
{
    $url = pruMediaSource($legacyMediaId);
    if ($url === '') {
        return;
    }
    $attachmentId = pruSideload($url, $postId, $alt);
    if ($attachmentId) {
        set_post_thumbnail($postId, $attachmentId);
    }
}

function pruLegacyPath(string $url): string
{
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    return '/' . trim($path, '/') . '/';
}

function pruFindTarget(string $path): int
{
    $path = '/' . trim($path, '/') . '/';
    $legacyRedirects = [
        '/about/' => '/about-us/',
        '/about/staff/' => '/our-team/',
        '/about/our-collaborators/' => '/our-collaborators/',
        '/privacy/' => '/privacy-policy/',
        '/terms-and-conditions/' => '/terms/',
        '/shared-medical-appointments/' => '/our-projects/shared-medical-appointments/',
        '/choose-well/' => '/our-projects/choose-well-project/',
        '/letters-to-healthcare-professionals/' => '/our-projects/letters-to-healthcare-professionals/',
        '/behavioural-science-network/' => '/our-projects/behavioural-science-network/',
    ];
    $path = $legacyRedirects[$path] ?? $path;
    if ($path === '/') {
        return (int) get_option('page_on_front');
    }

    $ids = get_posts([
        'post_type' => ['page', 'research', 'person', 'post', 'resource', 'publications'],
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_pru_legacy_path',
        'meta_value' => $path,
    ]);
    if ($ids) {
        return (int) $ids[0];
    }

    $trimmed = trim($path, '/');
    $page = get_page_by_path($trimmed, OBJECT, 'page');
    if ($page instanceof WP_Post) {
        return (int) $page->ID;
    }
    $slug = basename($trimmed);
    foreach (['research', 'person', 'post', 'resource', 'publications'] as $postType) {
        $post = get_page_by_path($slug, OBJECT, $postType);
        if ($post instanceof WP_Post) {
            return (int) $post->ID;
        }
    }
    return 0;
}

function pruRewriteHtml(string $html, int $parentId = 0): string
{
    if ($html === '') {
        return '';
    }
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//img[@src]') as $image) {
        $oldUrl = $image->getAttribute('src');
        if (!str_contains($oldUrl, 'behscipru.nihr.ac.uk')) {
            continue;
        }
        $attachmentId = pruSideload($oldUrl, $parentId, $image->getAttribute('alt'));
        if (!$attachmentId) {
            continue;
        }
        $image->setAttribute('src', (string) wp_get_attachment_url($attachmentId));
        $image->removeAttribute('srcset');
        $image->removeAttribute('sizes');
        $image->setAttribute('class', 'wp-image-' . $attachmentId);
    }

    foreach ($xpath->query('//a[@href]') as $anchor) {
        $href = trim($anchor->getAttribute('href'));
        $host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
        if ($host !== 'behscipru.nihr.ac.uk' && $host !== 'www.behscipru.nihr.ac.uk') {
            continue;
        }
        $path = (string) wp_parse_url($href, PHP_URL_PATH);
        if (str_contains($path, '/wp-content/uploads/')) {
            $attachmentId = pruSideload($href, $parentId, pruDecode($anchor->textContent));
            if ($attachmentId) {
                $anchor->setAttribute('href', (string) wp_get_attachment_url($attachmentId));
            }
            continue;
        }
        $targetId = pruFindTarget($path);
        $anchor->setAttribute('href', $targetId ? get_permalink($targetId) : home_url($path));
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ? pruInnerHtml($body) : $html;
}

function pruProjectNodeText(DOMNode $node): string
{
    return trim((string) preg_replace('/\s+/u', ' ', pruDecode($node->textContent)));
}

function pruProjectFieldFromText(string $text): array
{
    if (!preg_match('/^(lead researcher|research team(?: members)?)(?:\s*:)?\s*(.*)$/iu', $text, $matches)) {
        return ['', ''];
    }

    $label = strtolower(trim($matches[1]));
    $field = str_starts_with($label, 'lead researcher') ? 'lead' : 'team';
    $value = trim($matches[2]);
    if (preg_match('/^research team(?: members)?\s*:?$/iu', $value)) {
        $value = '';
    }

    return [$field, $value];
}

function pruProjectSectionLabel(DOMXPath $xpath, DOMNode $node): string
{
    $emphasis = $xpath->query('.//strong[1] | .//b[1]', $node)->item(0);
    $candidate = $emphasis ? pruProjectNodeText($emphasis) : pruProjectNodeText($node);
    return strtolower(trim(rtrim($candidate, ':')));
}

function pruLooksLikeProjectTeam(string $text): bool
{
    if ($text === '' || mb_strlen($text) > 2000) {
        return false;
    }

    if (preg_match('/[,;&]/u', $text) || preg_match('/\b(?:Dr|Prof|Professor)\b/u', $text)) {
        return true;
    }

    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return count($words) >= 2 && count($words) <= 8 && !preg_match('/[.!?]$/u', $text);
}

function pruExtractProjectFields(string $html): array
{
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $lead = '';
    $team = '';
    $summaryNodes = [];
    $stopLabels = [
        'background',
        'research questions',
        'methods',
        'what are our methods?',
        'project outline',
        'project outline / summary',
        'design and methods',
        'overview',
        'introduction',
        'outputs',
        'outputs from this work',
    ];

    $bodyNodes = [];
    foreach ($xpath->query('//body/*') as $bodyNode) {
        $bodyNodes[] = $bodyNode;
    }

    foreach ($bodyNodes as $index => $node) {
        if (!$node->parentNode) {
            continue;
        }

        $text = pruProjectNodeText($node);
        [$field, $value] = pruProjectFieldFromText($text);
        if ($field === '') {
            continue;
        }

        $nodesToRemove = [$node];
        if ($value === '') {
            for ($candidateIndex = $index + 1; $candidateIndex < count($bodyNodes); $candidateIndex++) {
                $candidate = $bodyNodes[$candidateIndex];
                if (!$candidate->parentNode) {
                    continue;
                }
                $candidateText = pruProjectNodeText($candidate);
                if ($candidateText === '') {
                    continue;
                }

                [$candidateField, $candidateValue] = pruProjectFieldFromText($candidateText);
                if ($candidateField === $field && $candidateValue === '') {
                    $nodesToRemove[] = $candidate;
                    continue;
                }

                $tagName = strtolower($candidate->nodeName);
                $sectionLabel = pruProjectSectionLabel($xpath, $candidate);
                if (preg_match('/^h[1-6]$/', $tagName) || in_array($sectionLabel, $stopLabels, true)) {
                    break;
                }

                $isUsable = $field === 'lead'
                    ? mb_strlen($candidateText) <= 300
                    : pruLooksLikeProjectTeam($candidateText);
                if ($isUsable) {
                    $value = $candidateText;
                    $nodesToRemove[] = $candidate;
                    break;
                }
            }
        }

        if ($value === '') {
            continue;
        }

        if ($field === 'lead' && $lead === '') {
            $lead = $value;
        }
        if ($field === 'team' && $team === '') {
            $team = $value;
        }
        foreach ($nodesToRemove as $nodeToRemove) {
            $nodeToRemove->parentNode?->removeChild($nodeToRemove);
        }
    }

    $collectSummary = false;
    $remainingNodes = [];
    foreach ($xpath->query('//body/*') as $bodyNode) {
        $remainingNodes[] = $bodyNode;
    }
    foreach ($remainingNodes as $node) {
        if (!$node->parentNode) {
            continue;
        }
        $label = pruProjectSectionLabel($xpath, $node);
        if ($label === 'project title') {
            $node->parentNode?->removeChild($node);
            continue;
        }
        if ($label === 'plain english summary') {
            $collectSummary = true;
            $node->parentNode?->removeChild($node);
            continue;
        }
        if ($collectSummary && in_array($label, $stopLabels, true)) {
            $collectSummary = false;
        }
        if ($collectSummary) {
            $summaryNodes[] = $dom->saveHTML($node);
            $node->parentNode?->removeChild($node);
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return [
        'lead' => $lead,
        'team' => $team,
        'summary' => implode('', $summaryNodes),
        'content' => $body ? pruInnerHtml($body) : $html,
    ];
}

function pruMediaFingerprint(string $url): string
{
    $path = urldecode((string) wp_parse_url($url, PHP_URL_PATH));
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
    $filename = (string) pathinfo($path, PATHINFO_FILENAME);
    $filename = (string) preg_replace('/-\d+x\d+(?:-\d+)?$/i', '', $filename);
    return strtolower($filename . ($extension !== '' ? '.' . $extension : ''));
}

function pruStripFeaturedImage(string $html, int $featuredId): string
{
    if ($html === '' || !$featuredId) {
        return $html;
    }

    $featuredFingerprints = [];
    $featuredUrl = (string) wp_get_attachment_url($featuredId);
    if ($featuredUrl !== '') {
        $featuredFingerprints[] = pruMediaFingerprint($featuredUrl);
    }
    $legacyUrl = (string) get_post_meta($featuredId, '_pru_legacy_media_url', true);
    if ($legacyUrl !== '') {
        $featuredFingerprints[] = pruMediaFingerprint($legacyUrl);
    }
    $featuredFingerprints = array_values(array_unique(array_filter($featuredFingerprints)));

    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $images = [];
    foreach ($xpath->query('//img[@src]') as $image) {
        $images[] = $image;
    }

    foreach ($images as $image) {
        $class = $image->getAttribute('class');
        preg_match('/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $class, $matches);
        $imageId = isset($matches[1]) ? (int) $matches[1] : 0;
        $fingerprint = pruMediaFingerprint($image->getAttribute('src'));
        if ($imageId !== $featuredId && !in_array($fingerprint, $featuredFingerprints, true)) {
            continue;
        }

        $parent = $image->parentNode;
        $nextSibling = $image->nextSibling;
        $parent?->removeChild($image);
        if ($nextSibling && strtolower($nextSibling->nodeName) === 'br') {
            $nextSibling->parentNode?->removeChild($nextSibling);
        }

        while ($parent && strtolower($parent->nodeName) === 'a' && pruProjectNodeText($parent) === '') {
            $wrapper = $parent;
            $parent = $wrapper->parentNode;
            $parent?->removeChild($wrapper);
        }
        if ($parent && $parent->nodeType === XML_ELEMENT_NODE && pruProjectNodeText($parent) === '') {
            $hasMedia = $xpath->query('.//img | .//video | .//iframe', $parent)->length > 0;
            if (!$hasMedia) {
                $parent->parentNode?->removeChild($parent);
            }
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ? pruInnerHtml($body) : $html;
}

function pruRoleMap(string $path): array
{
    $html = pruFetch($path, false);
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $roles = [];
    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " staff-item ")]') as $card) {
        $link = $xpath->query('self::a[@href] | .//a[@href]', $card)->item(0);
        $name = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " staff-item-info-name ")]', $card)->item(0);
        $role = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " staff-item-info-position ")]', $card)->item(0);
        if (!$link || !$name) {
            continue;
        }
        $slug = basename(trim((string) wp_parse_url($link->getAttribute('href'), PHP_URL_PATH), '/'));
        $roles[$slug] = [
            'name' => pruDecode($name->textContent),
            'role' => $role ? pruDecode($role->textContent) : '',
        ];
    }
    return $roles;
}

function pruPersonRoleGroupSlug(string $role, string $sourceType): string
{
    if ($sourceType === 'collaborators') {
        return 'collaborators';
    }
    if (stripos($role, 'PPIE') !== false) {
        return 'ppie';
    }
    if (preg_match('/^(?:Co-)?Director\b/i', $role)) {
        return 'leadership';
    }
    if (stripos($role, 'Co-Investigator') !== false) {
        return 'co-investigators';
    }
    if (stripos($role, 'Manager') !== false || stripos($role, 'Operations') !== false) {
        return 'operations';
    }
    return 'research-team';
}

function pruPersonRoleGroupTerms(): array
{
    $definitions = [
        'leadership' => 'Leadership',
        'co-investigators' => 'Co-Investigators',
        'operations' => 'Operations',
        'research-team' => 'Research team',
        'ppie' => 'PPIE',
        'collaborators' => 'Collaborators',
    ];
    $terms = [];
    foreach ($definitions as $slug => $name) {
        $terms[$slug] = pruTerm('person_role_group', $name, $slug);
    }
    return $terms;
}

function pruPersonDirectoryOrder(string $role, string $sourceType, int $sourcePosition): int
{
    $priority = [
        'leadership' => 1,
        'co-investigators' => 2,
        'operations' => 3,
        'research-team' => 4,
        'ppie' => 5,
        'collaborators' => 6,
    ];
    $roleGroupSlug = pruPersonRoleGroupSlug($role, $sourceType);
    return (($priority[$roleGroupSlug] ?? 9) * 1000) + ($sourcePosition * 10);
}

function pruSyncPersonDirectoryData(): int
{
    $roleGroupTerms = pruPersonRoleGroupTerms();
    $sources = [
        'staff' => '/about/staff/',
        'collaborators' => '/about/our-collaborators/',
    ];
    $updated = 0;
    foreach ($sources as $sourceType => $path) {
        $position = 0;
        foreach (pruRoleMap($path) as $slug => $personData) {
            $person = get_page_by_path($slug, OBJECT, 'person');
            if (!$person instanceof WP_Post) {
                pruLog('  Missing person: ' . $slug);
                continue;
            }
            $role = (string) ($personData['role'] ?? '');
            $roleGroupSlug = pruPersonRoleGroupSlug($role, $sourceType);
            update_field('personRole', $role, $person->ID);
            wp_set_object_terms(
                $person->ID,
                [$roleGroupTerms[$roleGroupSlug]],
                'person_role_group',
                false
            );
            wp_update_post([
                'ID' => $person->ID,
                'post_excerpt' => $role,
                'menu_order' => pruPersonDirectoryOrder($role, $sourceType, ++$position),
            ]);
            $updated++;
        }
    }
    return $updated;
}

function pruPersonMeta(string $html): array
{
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $emailNode = $xpath->query('//a[starts-with(@href, "mailto:")]')->item(0);
    $website = '';
    foreach ($xpath->query('//a[@href]') as $anchor) {
        $href = $anchor->getAttribute('href');
        if (str_starts_with($href, 'http') && !str_contains($href, 'behscipru.nihr.ac.uk')) {
            $website = $href;
            break;
        }
    }
    $firstParagraph = $xpath->query('//p[normalize-space()]')->item(0);
    $institution = $firstParagraph ? pruDecode($firstParagraph->textContent) : '';
    $looksLikeInstitution = $institution !== ''
        && mb_strlen($institution) <= 160
        && preg_match('/\b(?:University|Institute|College|School|Centre|Department)\b/iu', $institution);
    return [
        'email' => $emailNode ? sanitize_email(str_replace('mailto:', '', $emailNode->getAttribute('href'))) : '',
        'website' => $website,
        'institution' => $looksLikeInstitution ? $institution : '',
    ];
}

function pruTerm(string $taxonomy, string $name, string $slug): int
{
    $term = term_exists($slug, $taxonomy);
    if (!$term) {
        $term = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
    }
    if (is_wp_error($term)) {
        throw new RuntimeException($term->get_error_message());
    }
    return (int) (is_array($term) ? $term['term_id'] : $term);
}

function pruMigratePeople(): array
{
    pruLog('People');
    $staffRoles = pruRoleMap('/about/staff/');
    $collaboratorRoles = pruRoleMap('/about/our-collaborators/');
    $groups = [
        'team' => pruTerm('person_group', 'Team', 'team'),
        'collaborators' => pruTerm('person_group', 'Collaborators', 'collaborators'),
        'senior-leadership-team' => pruTerm('person_group', 'Senior Leadership Team', 'senior-leadership-team'),
        'ppie-strategy-group' => pruTerm('person_group', 'PPIE Strategy Group', 'ppie-strategy-group'),
        'scientific-advisory-board' => pruTerm('person_group', 'Scientific Advisory Board', 'scientific-advisory-board'),
        'unit-executive-group' => pruTerm('person_group', 'Unit Executive Group', 'unit-executive-group'),
    ];
    $sets = [
        'staff' => pruFetch('/wp-json/wp/v2/staff?per_page=100&_fields=id,slug,link,title,content,featured_media,profile_types'),
        'collaborators' => pruFetch('/wp-json/wp/v2/collaborators?per_page=100&_fields=id,slug,link,title,content,featured_media,profile_types'),
    ];
    $roleGroupTerms = pruPersonRoleGroupTerms();
    $ids = [];
    $ppie = ['stu-edwards-2', 'kate-hawley', 'bill-wilson', 'debbie-smith'];
    foreach ($sets as $sourceType => $rows) {
        $roleMap = $sourceType === 'staff' ? $staffRoles : $collaboratorRoles;
        $rolePositions = array_flip(array_keys($roleMap));
        foreach ($rows as $row) {
            $title = pruDecode($row['title']['rendered'] ?? '');
            $role = $roleMap[$row['slug']]['role'] ?? '';
            $sourcePosition = isset($rolePositions[$row['slug']])
                ? ((int) $rolePositions[$row['slug']] + 1)
                : 999;
            $postId = pruUpsert([
                'post_type' => 'person',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => sanitize_title($row['slug']),
                'post_content' => (string) ($row['content']['rendered'] ?? ''),
                'post_excerpt' => $role,
                'menu_order' => pruPersonDirectoryOrder($role, $sourceType, $sourcePosition),
            ], $sourceType, (int) $row['id']);
            $ids[$sourceType . ':' . $row['id']] = $postId;
            update_post_meta($postId, '_pru_legacy_path', pruLegacyPath((string) $row['link']));
            update_field('legacyUrl', (string) $row['link'], $postId);
            update_field('personRole', $role, $postId);
            $details = pruPersonMeta((string) ($row['content']['rendered'] ?? ''));
            update_field('personEmail', $details['email'], $postId);
            update_field('personWebsite', $details['website'], $postId);
            update_field('personInstitution', $details['institution'], $postId);
            $personGroups = [$sourceType === 'staff' ? $groups['team'] : $groups['collaborators']];
            if (in_array(25, $row['profile_types'] ?? [], true)) {
                $personGroups[] = $groups['senior-leadership-team'];
            }
            if (in_array($row['slug'], $ppie, true)) {
                $personGroups[] = $groups['ppie-strategy-group'];
            }
            wp_set_object_terms($postId, array_values(array_unique($personGroups)), 'person_group');
            $roleGroupSlug = pruPersonRoleGroupSlug($role, $sourceType);
            wp_set_object_terms(
                $postId,
                [$roleGroupTerms[$roleGroupSlug]],
                'person_role_group',
                false
            );
            pruSetFeatured($postId, (int) ($row['featured_media'] ?? 0), $title);
            pruLog('  ' . $title);
        }
    }
    return $groups;
}

function pruMigrateProjects(): array
{
    pruLog('Projects');
    $rows = pruFetch('/wp-json/wp/v2/our-projects?per_page=100&_fields=id,parent,slug,link,title,content,featured_media,menu_order,date,modified');
    $current = pruTerm('project_status', 'Current', 'current');
    $completed = pruTerm('project_status', 'Completed', 'completed');
    $covid = pruTerm('project_topic', 'COVID-19 response', 'covid-19-response');
    $completedSlugs = [
        'behavioural-science-network',
        'patient-safety-understanding-health-professionals-responses-to-patient-complaints',
        'use-of-evidence-in-policymaking',
        'shared-medical-appointments',
    ];
    $covidSlugs = [
        'covid-19-response',
        'vaccine-hesitancy-study-1',
        'comparison-seasonal-flu-and-covid-19-vaccine',
        'covid-19-booster-vaccine',
        'antibody-testing',
        'communication-strategies',
        'decoy-study-investigating-covid-19-vaccine-intention',
        'covid-19-testing-behaviour',
        'local-and-ethnic-inequalities-in-covid-19-vaccine-uptake',
    ];
    $ids = [];
    foreach ($rows as $row) {
        $title = pruDecode($row['title']['rendered'] ?? '');
        $fields = pruExtractProjectFields((string) ($row['content']['rendered'] ?? ''));
        $excerpt = wp_trim_words(pruDecode($fields['summary'] ?: $fields['content']), 34, '…');
        $postId = pruUpsert([
            'post_type' => 'research',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => sanitize_title($row['slug']),
            'post_excerpt' => $excerpt,
            'post_parent' => 0,
            'menu_order' => (int) ($row['menu_order'] ?? 0),
            'post_date' => (string) ($row['date'] ?? current_time('mysql')),
            'post_modified' => (string) ($row['modified'] ?? current_time('mysql')),
        ], 'our-projects', (int) $row['id']);
        $ids[(int) $row['id']] = $postId;
        update_post_meta($postId, '_pru_legacy_path', pruLegacyPath((string) $row['link']));
        update_post_meta($postId, '_pru_legacy_html', $fields['content']);
        update_field('legacyUrl', (string) $row['link'], $postId);
        update_field('projectLead', $fields['lead'], $postId);
        update_field('projectTeam', $fields['team'], $postId);
        update_field('projectSummary', $fields['summary'], $postId);
        wp_set_object_terms($postId, [in_array($row['slug'], $completedSlugs, true) ? $completed : $current], 'project_status');
        wp_set_object_terms($postId, in_array($row['slug'], $covidSlugs, true) ? [$covid] : [], 'project_topic');
        pruSetFeatured($postId, (int) ($row['featured_media'] ?? 0), $title);
        pruLog('  ' . $title);
    }
    foreach ($rows as $row) {
        $parentId = (int) ($row['parent'] ?? 0);
        if ($parentId && isset($ids[$parentId], $ids[(int) $row['id']])) {
            wp_update_post(['ID' => $ids[(int) $row['id']], 'post_parent' => $ids[$parentId]]);
        }
    }
    return ['ids' => $ids, 'status' => ['current' => $current, 'completed' => $completed], 'topic' => ['covid' => $covid]];
}

function pruPageMain(string $url): string
{
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    $html = pruFetch($path ?: '/', false);
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $main = $xpath->query('//main[contains(concat(" ", normalize-space(@class), " "), " content ")]')->item(0);
    return $main ? trim(pruInnerHtml($main)) : '';
}

function pruFindByMeta(string $postType, string $key, string $value): int
{
    $ids = get_posts([
        'post_type' => $postType,
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => $key,
        'meta_value' => $value,
    ]);
    return $ids ? (int) $ids[0] : 0;
}

function pruResourceDescription(DOMNode $anchor): string
{
    $parent = $anchor->parentNode;
    if (!$parent) {
        return '';
    }
    $text = pruDecode($parent->textContent);
    $linkText = pruDecode($anchor->textContent);
    $description = trim(str_replace($linkText, '', $text), " \t\n\r\0\x0B:-");
    return $description !== '' ? '<p>' . esc_html($description) . '</p>' : '';
}

function pruMigrateResources(array $pages): array
{
    pruLog('Resources');
    $pageTypes = [
        'policy-briefings' => ['Policy Briefing', 'policy-briefings'],
        'reports' => ['Report', 'reports'],
        'project-posters' => ['Project Poster', 'project-posters'],
    ];
    $termIds = [];
    $ids = [];
    $resourceOrder = 0;
    foreach ($pageTypes as $slug => [$termName, $termSlug]) {
        $termIds[$slug] = pruTerm('resource_type', $termName, $termSlug);
        $page = null;
        foreach ($pages as $candidate) {
            if (($candidate['slug'] ?? '') === $slug) {
                $page = $candidate;
                break;
            }
        }
        if (!$page) {
            continue;
        }
        $html = pruPageMain((string) $page['link']);
        $dom = pruDom($html);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            $path = strtolower((string) wp_parse_url($href, PHP_URL_PATH));
            if (!preg_match('/\.(pdf|docx?|xlsx?|pptx?|zip)$/i', $path)) {
                continue;
            }
            $title = pruDecode($anchor->textContent);
            if ($title === '') {
                $title = pathinfo(urldecode(basename($path)), PATHINFO_FILENAME);
            }
            $hash = md5($href);
            $postId = pruFindByMeta('resource', '_pru_resource_hash', $hash);
            $postData = [
                'post_type' => 'resource',
                'post_status' => 'publish',
                'post_title' => mb_substr($title, 0, 240),
                'post_name' => sanitize_title($title . '-' . substr($hash, 0, 8)),
                'post_excerpt' => pruDecode(pruResourceDescription($anchor)),
                'menu_order' => $resourceOrder++,
            ];
            if ($postId) {
                $postData['ID'] = $postId;
                $saved = wp_update_post(wp_slash($postData), true);
            } else {
                $saved = wp_insert_post(wp_slash($postData), true);
            }
            if (is_wp_error($saved)) {
                throw new RuntimeException($saved->get_error_message());
            }
            $postId = (int) $saved;
            update_post_meta($postId, '_pru_resource_hash', $hash);
            update_post_meta($postId, '_pru_legacy_path', pruLegacyPath($href));
            update_field('legacyUrl', $href, $postId);
            update_field('resourceCardBehavior', 'direct', $postId);
            update_field('resourceOpenNewTab', 1, $postId);
            update_field('resourceDescription', pruResourceDescription($anchor), $postId);
            $host = strtolower((string) wp_parse_url($href, PHP_URL_HOST));
            if (in_array($host, ['behscipru.nihr.ac.uk', 'www.behscipru.nihr.ac.uk'], true)) {
                $attachmentId = pruSideload($href, $postId, $title);
                if (!$attachmentId) {
                    wp_update_post(['ID' => $postId, 'post_status' => 'draft']);
                    continue;
                }
                update_field('resourceDestinationType', 'file', $postId);
                update_field('resourceFile', $attachmentId, $postId);
                update_field('resourceExternalUrl', '', $postId);
                update_field('resourceCtaLabel', 'Download resource', $postId);
            } else {
                update_field('resourceDestinationType', 'external', $postId);
                update_field('resourceExternalUrl', esc_url_raw($href), $postId);
                update_field('resourceFile', '', $postId);
                update_field('resourceCtaLabel', 'View resource', $postId);
            }
            wp_set_object_terms($postId, [$termIds[$slug]], 'resource_type');
            $ids[] = $postId;
            pruLog('  ' . $title);
        }
    }
    return ['ids' => array_values(array_unique($ids)), 'terms' => $termIds];
}

function pruMigratePublications(array $pages): array
{
    pruLog('Publications');
    $page = null;
    foreach ($pages as $candidate) {
        if (($candidate['slug'] ?? '') === 'publications') {
            $page = $candidate;
            break;
        }
    }
    if (!$page) {
        return [];
    }

    $html = pruPageMain((string) $page['link']);
    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $section = 'Publications';
    $ids = [];
    foreach ($xpath->query('//body//*[self::h2 or self::h3 or self::p or self::li]') as $node) {
        if (in_array(strtolower($node->nodeName), ['h2', 'h3'], true)) {
            $section = pruDecode($node->textContent) ?: $section;
            continue;
        }
        if ($node->parentNode && strtolower($node->parentNode->nodeName) === 'li') {
            continue;
        }
        $citation = trim(preg_replace('/\s+/u', ' ', pruDecode($node->textContent)));
        if (mb_strlen($citation) < 45) {
            continue;
        }
        $link = $xpath->query('.//a[@href]', $node)->item(0);
        $linkText = $link ? pruDecode($link->textContent) : '';
        $title = mb_strlen($linkText) >= 20 ? $linkText : wp_trim_words($citation, 18, '…');
        $hash = md5(mb_strtolower($citation));
        $postId = pruFindByMeta('publications', '_pru_publication_hash', $hash);
        $postData = [
            'post_type' => 'publications',
            'post_status' => 'publish',
            'post_title' => mb_substr($title, 0, 240),
            'post_name' => sanitize_title($title . '-' . substr($hash, 0, 8)),
            'post_excerpt' => $citation,
        ];
        if ($postId) {
            $postData['ID'] = $postId;
            $saved = wp_update_post(wp_slash($postData), true);
        } else {
            $saved = wp_insert_post(wp_slash($postData), true);
        }
        if (is_wp_error($saved)) {
            throw new RuntimeException($saved->get_error_message());
        }
        $postId = (int) $saved;
        update_post_meta($postId, '_pru_publication_hash', $hash);
        update_field('publication_citation', $citation, $postId);
        update_field('publication_url', '', $postId);
        update_field('publication_doi', '', $postId);
        if ($link) {
            update_field('publication_url', esc_url_raw($link->getAttribute('href')), $postId);
        }
        if (preg_match('#10\.\d{4,9}/[-._;()/:A-Z0-9]+#iu', ($link ? $link->getAttribute('href') . ' ' : '') . $citation, $matches)) {
            update_field('publication_doi', rtrim($matches[0], '.,;)'), $postId);
        }
        $termId = pruTerm('publication_tag', $section, sanitize_title($section));
        wp_set_object_terms($postId, [$termId], 'publication_tag');
        $ids[] = $postId;
    }
    pruLog('  Imported ' . count($ids) . ' publication records');
    return array_values(array_unique($ids));
}

function pruWysiwyg(string $html): array
{
    return [
        'acf_fc_layout' => 'blockWysiwyg',
        'contentHtml' => $html,
        'options' => [
            'theme' => 'white',
            'size' => 'medium',
            'alignment' => 'left',
            'textAlignment' => 'left',
            'displayStyle' => 'default',
        ],
    ];
}

function pruHero(string $title): array
{
    return [
        'acf_fc_layout' => 'BlockHero',
        'title' => $title,
        'contentHtml' => '',
        'image' => 0,
    ];
}

function pruPortraitImageText(string $html): ?array
{
    if ($html === '') {
        return null;
    }

    $dom = pruDom($html);
    $xpath = new DOMXPath($dom);
    $image = $xpath->query('//img[@src][1]')->item(0);
    if (!$image instanceof DOMElement) {
        return null;
    }

    $attachmentId = 0;
    if (preg_match('/(?:^|\s)wp-image-(\d+)(?:\s|$)/', $image->getAttribute('class'), $matches)) {
        $attachmentId = (int) $matches[1];
    }
    if (!$attachmentId) {
        $attachmentId = (int) attachment_url_to_postid($image->getAttribute('src'));
    }
    if (!$attachmentId || get_post_type($attachmentId) !== 'attachment') {
        return null;
    }

    $image->parentNode?->removeChild($image);
    $body = $dom->getElementsByTagName('body')->item(0);
    $contentHtml = $body ? trim(pruInnerHtml($body)) : '';
    if ($contentHtml === '') {
        return null;
    }

    return [
        'acf_fc_layout' => 'blockImageText',
        'imagePosition' => 'left',
        'image' => $attachmentId,
        'contentHtml' => $contentHtml,
        'options' => [
            'theme' => 'white',
            'withoutPadding' => 0,
            'displayStyle' => 'portraitFeature',
        ],
    ];
}

function pruMigratePages(array $rows, array $peopleGroups, array $projectData, array $resourceData): array
{
    pruLog('Pages');
    $slugMap = [
        'about' => 'about-us',
        'staff' => 'our-team',
        'our-collaborators' => 'our-collaborators',
        'privacy' => 'privacy-policy',
        'terms-and-conditions' => 'terms',
        'guide-to-writing-policy-briefs' => 'guide-to-writing-policy-briefs',
    ];
    $skip = [
        'home', 'publications', 'blog', 'news-and-activities', 'our-impact',
        'case-studies', 'video-content-interviews', 'case-studies-and-videos',
        'behavioural-science-network', 'letters-to-healthcare-professionals',
        'shared-medical-appointments', 'choose-well', 'cauti', 'mmbrace',
    ];
    $forceRoot = [
        'about', 'staff', 'our-collaborators', 'privacy', 'terms-and-conditions',
        'guide-to-writing-policy-briefs',
    ];
    $ids = [];
    $bySlug = [];

    foreach ($rows as $row) {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug === '' || in_array($slug, $skip, true)) {
            continue;
        }
        $targetSlug = $slugMap[$slug] ?? $slug;
        $title = pruDecode($row['title']['rendered'] ?? '');
        $legacyPageId = pruFindByLegacy('page', 'page', (int) $row['id']);
        $existing = $legacyPageId ? get_post($legacyPageId) : null;
        if (!$existing instanceof WP_Post) {
            $existing = get_page_by_path($targetSlug, OBJECT, 'page');
        }
        if (!$existing) {
            $existing = get_page_by_path($targetSlug, OBJECT, ['page']);
        }
        $postData = [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_name' => $targetSlug,
            'post_parent' => 0,
            'menu_order' => (int) ($row['menu_order'] ?? 0),
        ];
        if ($existing instanceof WP_Post) {
            $postData['ID'] = $existing->ID;
            $saved = wp_update_post(wp_slash($postData), true);
        } else {
            $saved = wp_insert_post(wp_slash($postData), true);
        }
        if (is_wp_error($saved)) {
            throw new RuntimeException($saved->get_error_message());
        }
        $pageId = (int) $saved;
        $ids[(int) $row['id']] = $pageId;
        $bySlug[$slug] = $pageId;
        update_post_meta($pageId, '_pru_legacy_type', 'page');
        update_post_meta($pageId, '_pru_legacy_id', (int) $row['id']);
        update_post_meta($pageId, '_pru_legacy_path', pruLegacyPath((string) $row['link']));
        update_post_meta($pageId, '_pru_legacy_url', (string) $row['link']);
    }

    foreach ($rows as $row) {
        $legacyId = (int) ($row['id'] ?? 0);
        if (!isset($ids[$legacyId])) {
            continue;
        }
        $slug = (string) $row['slug'];
        $parentLegacyId = (int) ($row['parent'] ?? 0);
        $parentId = (!in_array($slug, $forceRoot, true) && isset($ids[$parentLegacyId])) ? $ids[$parentLegacyId] : 0;
        wp_update_post(['ID' => $ids[$legacyId], 'post_parent' => $parentId]);
    }

    foreach ($rows as $row) {
        $slug = (string) ($row['slug'] ?? '');
        $legacyId = (int) ($row['id'] ?? 0);
        if (!isset($ids[$legacyId])) {
            continue;
        }
        $pageId = $ids[$legacyId];
        $title = get_the_title($pageId);
        $content = pruRewriteHtml(pruPageMain((string) $row['link']), $pageId);
        update_post_meta($pageId, '_pru_legacy_html', $content);
        wp_update_post(['ID' => $pageId, 'post_content' => wp_slash($content)]);
        $components = [pruHero($title)];
        $gridOnlySlugs = [
            'staff',
            'our-collaborators',
            'our-ppie-strategy-group-members',
            'our-projects',
            'completed-projects',
            'policy-briefings',
            'reports',
            'project-posters',
            'ppie-activities',
            'news-and-events',
        ];
        if ($content !== '' && !in_array($slug, $gridOnlySlugs, true)) {
            $portraitSlugs = ['our-objectives', 'our-research', 'ppie-strategic-group-training'];
            $portraitComponent = in_array($slug, $portraitSlugs, true) ? pruPortraitImageText($content) : null;
            if ($slug === 'ppie-strategic-group-training' && $portraitComponent) {
                $portraitComponent['contentHtml'] = preg_replace(
                    '/<p>\s*(We have a dedicated training programme.*?)<\/p>/is',
                    '<p><strong class="blue-bold-text">$1</strong></p>',
                    $portraitComponent['contentHtml'],
                    1
                );
                $portraitComponent['contentHtml'] = preg_replace(
                    '/<p>\s*Some of our previous offerings include:\s*<\/p>/i',
                    '<h2>Previous training topics</h2>',
                    $portraitComponent['contentHtml'],
                    1
                );
            }
            $components[] = $portraitComponent ?? pruWysiwyg($content);
        }

        if ($slug === 'staff') {
            $components[] = [
                'acf_fc_layout' => 'gridPeople',
                'preContentHtml' => '',
                'groups' => [$peopleGroups['team']],
                'options' => ['theme' => 'white'],
            ];
        } elseif ($slug === 'our-collaborators') {
            $components[] = [
                'acf_fc_layout' => 'gridPeople',
                'preContentHtml' => '',
                'groups' => [$peopleGroups['collaborators']],
                'options' => ['theme' => 'white'],
            ];
        } elseif ($slug === 'our-ppie-strategy-group-members') {
            $components[] = [
                'acf_fc_layout' => 'gridPeople',
                'preContentHtml' => '',
                'groups' => [$peopleGroups['ppie-strategy-group']],
                'options' => ['theme' => 'white'],
            ];
        } elseif ($slug === 'our-projects') {
            $components[] = [
                'acf_fc_layout' => 'gridProjects',
                'preContentHtml' => '',
                'showFilters' => 1,
                'defaultStatus' => '',
                'defaultTopic' => '',
                'options' => ['theme' => 'white'],
            ];
        } elseif ($slug === 'completed-projects') {
            $components[] = [
                'acf_fc_layout' => 'gridProjects',
                'preContentHtml' => '',
                'showFilters' => 0,
                'defaultStatus' => $projectData['status']['completed'],
                'defaultTopic' => '',
                'options' => ['theme' => 'white'],
            ];
        } elseif ($slug === 'ppie-activities') {
            $ppieActivities = get_term_by('slug', 'ppie-activities', 'category');
            if ($ppieActivities instanceof WP_Term) {
                $components[] = [
                    'acf_fc_layout' => 'gridPostsLatest',
                    'preContentHtml' => '<h2>Latest PPIE activities</h2>',
                    'postType' => 'post',
                    'button' => null,
                    'taxonomies' => [(int) $ppieActivities->term_id],
                    'options' => ['theme' => 'white', 'maxPosts' => 0],
                ];
            }
        } elseif (isset($resourceData['terms'][$slug])) {
            $components[] = [
                'acf_fc_layout' => 'gridResourcesArchive',
                'preContentHtml' => '',
                'filterLabel' => 'Resource type:',
                'allLabel' => 'All',
                'filterTerms' => [$resourceData['terms'][$slug]],
                'options' => ['theme' => 'white', 'maxPosts' => 24],
            ];
        }
        update_field('field_pageComponents_pageComponents', $components, $pageId);
        pruLog('  ' . $title);
    }

    if (!empty($bySlug['news-and-events'])) {
        update_option('page_for_posts', $bySlug['news-and-events']);
        update_option('show_on_front', 'page');
    }

    return ['ids' => $ids, 'slugs' => $bySlug];
}

function pruFinalizeProjectContent(): void
{
    pruLog('Project content and internal links');
    $projects = get_posts([
        'post_type' => 'research',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ]);
    foreach ($projects as $project) {
        $content = pruRewriteHtml((string) get_post_meta($project->ID, '_pru_legacy_html', true), $project->ID);
        $content = pruStripFeaturedImage($content, (int) get_post_thumbnail_id($project->ID));
        $summary = pruRewriteHtml((string) get_field('projectSummary', $project->ID), $project->ID);
        wp_update_post(['ID' => $project->ID, 'post_content' => wp_slash($content)]);
        update_field('projectSummary', $summary, $project->ID);
        update_field(
            'field_researchComponents_pageComponents',
            $content !== '' ? [pruWysiwyg($content)] : [],
            $project->ID
        );
    }

    $people = get_posts([
        'post_type' => 'person',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ]);
    foreach ($people as $person) {
        $content = pruRewriteHtml($person->post_content, $person->ID);
        wp_update_post(['ID' => $person->ID, 'post_content' => wp_slash($content)]);
    }
}

function pruMenuPageId(array $pages, string $legacySlug): int
{
    if (!empty($pages['slugs'][$legacySlug])) {
        return (int) $pages['slugs'][$legacySlug];
    }
    $localMap = [
        'home' => 'home',
        'about' => 'about-us',
        'staff' => 'our-team',
        'our-collaborators' => 'our-collaborators',
        'privacy' => 'privacy-policy',
        'terms-and-conditions' => 'terms',
        'contact' => 'contact',
        'site-map' => 'site-map',
        'cookie-policy' => 'cookie-policy',
        'accessibility' => 'accessibility',
    ];
    $page = get_page_by_path($localMap[$legacySlug] ?? $legacySlug, OBJECT, 'page');
    return $page instanceof WP_Post ? (int) $page->ID : 0;
}

function pruMenuPostItem(int $menuId, int $postId, int $parentId = 0, string $title = ''): int
{
    if (!$postId) {
        return 0;
    }
    $itemId = wp_update_nav_menu_item($menuId, 0, [
        'menu-item-object-id' => $postId,
        'menu-item-object' => get_post_type($postId),
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-parent-id' => $parentId,
        'menu-item-title' => $title ?: get_the_title($postId),
    ]);
    if (is_wp_error($itemId)) {
        throw new RuntimeException($itemId->get_error_message());
    }
    return (int) $itemId;
}

function pruMenuCustomItem(int $menuId, string $title, string $url, int $parentId = 0): int
{
    $itemId = wp_update_nav_menu_item($menuId, 0, [
        'menu-item-type' => 'custom',
        'menu-item-status' => 'publish',
        'menu-item-parent-id' => $parentId,
        'menu-item-title' => $title,
        'menu-item-url' => $url,
    ]);
    if (is_wp_error($itemId)) {
        throw new RuntimeException($itemId->get_error_message());
    }
    return (int) $itemId;
}

function pruBuildNavigation(array $pages): void
{
    pruLog('Navigation');
    $menu = wp_get_nav_menu_object('Primary Navigation');
    if (!$menu) {
        $created = wp_create_nav_menu('Primary Navigation');
        if (is_wp_error($created)) {
            throw new RuntimeException($created->get_error_message());
        }
        $menuId = (int) $created;
    } else {
        $menuId = (int) $menu->term_id;
    }
    foreach (wp_get_nav_menu_items($menuId) ?: [] as $item) {
        wp_delete_post((int) $item->ID, true);
    }

    pruMenuPostItem($menuId, (int) get_option('page_on_front'));
    $about = pruMenuPostItem($menuId, pruMenuPageId($pages, 'about'));
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'staff'), $about);
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'our-collaborators'), $about);
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'our-objectives'), $about);
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'our-research'), $about);
    $governance = pruMenuPostItem($menuId, pruMenuPageId($pages, 'governance'), $about);
    foreach (['dhsc-oversight-group', 'scientific-advisory-board', 'management-board', 'declaration-of-interests', 'privacy-notice-for-research'] as $slug) {
        pruMenuPostItem($menuId, pruMenuPageId($pages, $slug), $governance);
    }

    $projects = pruMenuPostItem($menuId, pruMenuPageId($pages, 'our-projects'));
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'completed-projects'), $projects);

    $outputs = pruMenuPostItem($menuId, pruMenuPageId($pages, 'outputs'));
    foreach (['policy-briefings', 'reports', 'project-posters', 'guide-to-writing-policy-briefs'] as $slug) {
        pruMenuPostItem($menuId, pruMenuPageId($pages, $slug), $outputs);
    }
    pruMenuCustomItem($menuId, 'Publications', get_post_type_archive_link('publications') ?: home_url('/publications/'), $outputs);

    pruMenuPostItem($menuId, pruMenuPageId($pages, 'for-policy-makers'));
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'for-researchers'));
    $public = pruMenuPostItem($menuId, pruMenuPageId($pages, 'for-the-public'));
    foreach (['patient-and-public-involvement-strategy', 'ppie-strategic-group-terms-of-reference', 'our-ppie-strategy-group-members', 'ppie-activities', 'ppie-strategic-group-training'] as $slug) {
        pruMenuPostItem($menuId, pruMenuPageId($pages, $slug), $public);
    }
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'news-and-events'));
    pruMenuPostItem($menuId, pruMenuPageId($pages, 'contact'));

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations['navigation_main'] = $menuId;
    $locations['navigation_burger'] = $menuId;
    set_theme_mod('nav_menu_locations', $locations);
}

function pruLink(string $title, int $postId): array
{
    return ['link' => ['title' => $title, 'url' => get_permalink($postId), 'target' => '']];
}

function pruUpdateThemeOptions(array $pages): void
{
    pruLog('Theme options');
    $explore = [];
    foreach (['home', 'about', 'our-projects', 'outputs', 'for-policy-makers', 'for-researchers', 'for-the-public', 'news-and-events', 'contact'] as $slug) {
        $id = $slug === 'home' ? (int) get_option('page_on_front') : pruMenuPageId($pages, $slug);
        if ($id) {
            $explore[] = pruLink(get_the_title($id), $id);
        }
    }
    $information = [];
    foreach (['site-map', 'privacy', 'cookie-policy', 'terms-and-conditions', 'accessibility'] as $slug) {
        $id = pruMenuPageId($pages, $slug);
        if ($id) {
            $information[] = pruLink(get_the_title($id), $id);
        }
    }
    $blueskyIcon = pruSideload(
        PRU_OLD_SITE . '/wp-content/themes/sass/img/social/icon_socials_blusky.png',
        0,
        'Bluesky'
    );
    update_field('field_translatable_NavigationFooter_text', 'NIHR PRU Behavioural and Social Sciences', 'option');
    update_field('field_translatable_NavigationFooter_contentHtml', '<h4>Contact us</h4><p>Newcastle University, Baddiley-Clark Building, Richardson Road, Newcastle upon Tyne, NE2 4AX.<br>Telephone: <a href="tel:+441912083463">0191 208 3463</a></p><p><a href="mailto:NIHRPRU.BehSocSci@newcastle.ac.uk">NIHRPRU.BehSocSci@newcastle.ac.uk</a></p>', 'option');
    update_field('field_translatable_NavigationFooter_column_two_title', 'Explore', 'option');
    update_field('field_translatable_NavigationFooter_column_two', $explore, 'option');
    update_field('field_translatable_NavigationFooter_column_three_title', 'Information', 'option');
    update_field('field_translatable_NavigationFooter_column_three', $information, 'option');
    update_field('field_translatable_NavigationFooter_column_social_title', 'Follow us', 'option');
    update_field('field_translatable_NavigationFooter_column_social', [
        ['image' => 0, 'link' => ['title' => 'YouTube', 'url' => 'https://youtube.com/channel/UCH9aqlK0rTi_fuTyQRRs7XQ', 'target' => '_blank']],
        ['image' => 0, 'link' => ['title' => 'LinkedIn', 'url' => 'https://linkedin.com/company/nihr-pru-behavioural-and-social-sciences', 'target' => '_blank']],
        ['image' => $blueskyIcon, 'link' => ['title' => 'Bluesky', 'url' => 'https://bsky.app/profile/nihr-pru-bass.bsky.social', 'target' => '_blank']],
    ], 'option');
    update_field('field_translatable_NavigationFooter_copyright_text', 'NIHR PRU Behavioural and Social Sciences © ' . wp_date('Y'), 'option');
    update_field('field_translatable_NavigationFooter_copyright_menu', $information, 'option');
}

function pruVerifyMigration(): void
{
    $types = ['post', 'page', 'research', 'person', 'resource', 'publications', 'attachment'];
    foreach ($types as $postType) {
        $counts = wp_count_posts($postType);
        $publicCount = $postType === 'attachment'
            ? (int) ($counts->inherit ?? 0)
            : (int) ($counts->publish ?? 0);
        pruLog(sprintf('  %-13s %d available', $postType, $publicCount));
    }
}

if (!defined('PRU_MIGRATION_LIBRARY_ONLY')) {
    try {
        pruLog('NIHR PRU migration started');
        $pages = pruFetch('/wp-json/wp/v2/pages?per_page=100&_fields=id,parent,slug,link,title,featured_media,menu_order,date,modified');
        $peopleGroups = pruMigratePeople();
        $projectData = pruMigrateProjects();
        $resourceData = pruMigrateResources($pages);
        pruMigratePublications($pages);
        $pageData = pruMigratePages($pages, $peopleGroups, $projectData, $resourceData);
        pruFinalizeProjectContent();
        pruBuildNavigation($pageData);
        pruUpdateThemeOptions($pageData);
        flush_rewrite_rules(false);
        pruVerifyMigration();
        pruLog('NIHR PRU migration complete');
    } catch (Throwable $exception) {
        fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}
