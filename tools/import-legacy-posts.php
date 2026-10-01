<?php

/**
 * Import published posts from the previous NIHR PRU WordPress REST API.
 *
 * Run with WP-CLI:
 * wp eval-file tools/import-legacy-posts.php https://behscipru.nihr.ac.uk
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "This script must be run through WordPress.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$legacyBaseUrl = untrailingslashit($args[0] ?? 'https://behscipru.nihr.ac.uk');
$legacyHost = (string) wp_parse_url($legacyBaseUrl, PHP_URL_HOST);
$apiUrl = $legacyBaseUrl . '/wp-json/wp/v2/posts?per_page=100&_embed=1&orderby=date&order=asc';

/**
 * Write an informational line without requiring WP-CLI in browser contexts.
 */
function pru_import_log(string $message): void
{
    if (class_exists('WP_CLI')) {
        \WP_CLI::log($message);
        return;
    }

    echo esc_html($message) . "\n";
}

/**
 * Return a readable REST field value.
 */
function pru_import_rendered_value($value): string
{
    if (is_array($value)) {
        return (string) ($value['rendered'] ?? $value['raw'] ?? '');
    }

    return is_scalar($value) ? (string) $value : '';
}

/**
 * Find an attachment imported from a legacy URL.
 */
function pru_import_find_attachment(string $sourceUrl): int
{
    $ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_pru_legacy_source_url',
        'meta_value' => esc_url_raw($sourceUrl),
        'no_found_rows' => true,
    ]);

    return isset($ids[0]) ? (int) $ids[0] : 0;
}

/**
 * Download a remote image into the WordPress Media Library.
 */
function pru_import_sideload_image(string $sourceUrl, int $parentId, string $alt = '', string $title = ''): int
{
    $sourceUrl = esc_url_raw($sourceUrl);
    if ($sourceUrl === '') {
        return 0;
    }

    $existingId = pru_import_find_attachment($sourceUrl);
    if ($existingId > 0) {
        return $existingId;
    }

    $tmpFile = download_url($sourceUrl, 60);
    if (is_wp_error($tmpFile)) {
        pru_import_log(sprintf('Image download failed: %s', $sourceUrl));
        return 0;
    }

    $path = (string) wp_parse_url($sourceUrl, PHP_URL_PATH);
    $filename = sanitize_file_name(wp_basename($path));
    if ($filename === '' || !str_contains($filename, '.')) {
        $filename = 'legacy-image-' . wp_generate_uuid4() . '.jpg';
    }

    $file = [
        'name' => $filename,
        'tmp_name' => $tmpFile,
    ];

    $attachmentId = media_handle_sideload($file, $parentId, $title);
    if (is_wp_error($attachmentId)) {
        @unlink($tmpFile);
        pru_import_log(sprintf('Image import failed: %s', $sourceUrl));
        return 0;
    }

    update_post_meta($attachmentId, '_pru_legacy_source_url', $sourceUrl);
    if ($alt !== '') {
        update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($alt));
    }

    return (int) $attachmentId;
}

/**
 * Localise images embedded in post HTML and remove remote responsive sources.
 */
function pru_import_localise_content_images(string $content, int $postId, string $legacyHost): string
{
    if ($content === '' || $legacyHost === '') {
        return $content;
    }

    return (string) preg_replace_callback('/<img\b[^>]*>/i', function (array $matches) use ($postId, $legacyHost): string {
        $tag = $matches[0];
        if (!preg_match('/\bsrc=(\"|\')(.*?)\1/i', $tag, $sourceMatch)) {
            return $tag;
        }

        $sourceUrl = html_entity_decode($sourceMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ((string) wp_parse_url($sourceUrl, PHP_URL_HOST) !== $legacyHost) {
            return $tag;
        }

        $alt = '';
        if (preg_match('/\balt=(\"|\')(.*?)\1/i', $tag, $altMatch)) {
            $alt = html_entity_decode($altMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $attachmentId = pru_import_sideload_image($sourceUrl, $postId, $alt);
        if ($attachmentId <= 0) {
            return $tag;
        }

        $localUrl = wp_get_attachment_url($attachmentId);
        if (!$localUrl) {
            return $tag;
        }

        $tag = preg_replace('/\bsrc=(\"|\')(.*?)\1/i', 'src="' . esc_url($localUrl) . '"', $tag, 1);
        $tag = preg_replace('/\s+srcset=(\"|\').*?\1/i', '', $tag);
        $tag = preg_replace('/\s+sizes=(\"|\').*?\1/i', '', $tag);

        return $tag;
    }, $content);
}

$response = wp_remote_get($apiUrl, [
    'timeout' => 90,
    'headers' => [
        'Accept' => 'application/json',
        'User-Agent' => 'NIHR-PRU-Migration/1.0',
    ],
]);

if (is_wp_error($response)) {
    throw new RuntimeException($response->get_error_message());
}

$statusCode = (int) wp_remote_retrieve_response_code($response);
if ($statusCode !== 200) {
    throw new RuntimeException(sprintf('Legacy REST API returned HTTP %d.', $statusCode));
}

$legacyPosts = json_decode((string) wp_remote_retrieve_body($response), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($legacyPosts)) {
    throw new RuntimeException('Legacy REST API did not return a post array.');
}

$created = 0;
$updated = 0;
$failed = 0;

foreach ($legacyPosts as $legacyPost) {
    $legacyId = (int) ($legacyPost['id'] ?? 0);
    $slug = sanitize_title((string) ($legacyPost['slug'] ?? ''));
    $title = html_entity_decode(wp_strip_all_tags(pru_import_rendered_value($legacyPost['title'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($legacyId <= 0 || $slug === '' || $title === '') {
        $failed++;
        continue;
    }

    $existingIds = get_posts([
        'post_type' => 'post',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_pru_legacy_post_id',
        'meta_value' => $legacyId,
        'no_found_rows' => true,
    ]);
    $existingPost = get_page_by_path($slug, OBJECT, 'post');
    $postId = isset($existingIds[0]) ? (int) $existingIds[0] : (int) ($existingPost->ID ?? 0);

    $postData = [
        'ID' => $postId,
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_name' => $slug,
        'post_title' => $title,
        'post_content' => pru_import_rendered_value($legacyPost['content'] ?? ''),
        'post_excerpt' => '',
        'post_author' => 1,
        'comment_status' => (string) ($legacyPost['comment_status'] ?? 'closed'),
        'ping_status' => (string) ($legacyPost['ping_status'] ?? 'closed'),
        'post_date' => get_date_from_gmt((string) ($legacyPost['date_gmt'] ?? ''), 'Y-m-d H:i:s'),
        'post_date_gmt' => (string) ($legacyPost['date_gmt'] ?? ''),
    ];

    $savedPostId = wp_insert_post($postData, true);
    if (is_wp_error($savedPostId)) {
        pru_import_log(sprintf('Post failed: %s', $title));
        $failed++;
        continue;
    }
    $postId = (int) $savedPostId;

    $content = pru_import_localise_content_images(pru_import_rendered_value($legacyPost['content'] ?? ''), $postId, $legacyHost);
    $content = str_replace(trailingslashit($legacyBaseUrl), trailingslashit(home_url('/')), $content);
    wp_update_post([
        'ID' => $postId,
        'post_content' => $content,
    ]);

    update_post_meta($postId, '_pru_legacy_post_id', $legacyId);
    update_post_meta($postId, '_pru_legacy_url', esc_url_raw((string) ($legacyPost['link'] ?? '')));

    $categoryIds = [];
    $tagIds = [];
    foreach (($legacyPost['_embedded']['wp:term'] ?? []) as $termGroup) {
        foreach ((array) $termGroup as $legacyTerm) {
            $taxonomy = (string) ($legacyTerm['taxonomy'] ?? '');
            if (!in_array($taxonomy, ['category', 'post_tag'], true)) {
                continue;
            }

            $term = term_exists((string) ($legacyTerm['slug'] ?? ''), $taxonomy);
            if (!$term) {
                $term = wp_insert_term(
                    html_entity_decode((string) ($legacyTerm['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $taxonomy,
                    ['slug' => sanitize_title((string) ($legacyTerm['slug'] ?? ''))]
                );
            }

            if (is_wp_error($term)) {
                continue;
            }

            $termId = (int) (is_array($term) ? $term['term_id'] : $term);
            if ($taxonomy === 'category') {
                $categoryIds[] = $termId;
            } else {
                $tagIds[] = $termId;
            }
        }
    }
    wp_set_post_categories($postId, array_values(array_unique($categoryIds)));
    wp_set_post_tags($postId, array_values(array_unique($tagIds)));

    $featuredMedia = $legacyPost['_embedded']['wp:featuredmedia'][0] ?? null;
    $featuredUrl = is_array($featuredMedia) ? (string) ($featuredMedia['source_url'] ?? '') : '';
    if ($featuredUrl === '' && !empty($legacyPost['featured_media']) && !empty($legacyPost['link'])) {
        $legacyPage = wp_remote_get((string) $legacyPost['link'], ['timeout' => 60]);
        if (!is_wp_error($legacyPage) && wp_remote_retrieve_response_code($legacyPage) === 200) {
            $legacyHtml = (string) wp_remote_retrieve_body($legacyPage);
            if (preg_match('/<meta[^>]+property=(\"|\')og:image\1[^>]+content=(\"|\')(.*?)\2/i', $legacyHtml, $imageMatch)) {
                $featuredUrl = html_entity_decode($imageMatch[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
    }

    if ($featuredUrl !== '') {
        $featuredId = pru_import_sideload_image(
            $featuredUrl,
            $postId,
            is_array($featuredMedia) ? (string) ($featuredMedia['alt_text'] ?? '') : '',
            is_array($featuredMedia)
                ? html_entity_decode(wp_strip_all_tags(pru_import_rendered_value($featuredMedia['title'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : $title
        );
        if ($featuredId > 0) {
            set_post_thumbnail($postId, $featuredId);
        }
    }

    if (!empty($legacyPost['format']) && $legacyPost['format'] !== 'standard') {
        set_post_format($postId, sanitize_key((string) $legacyPost['format']));
    }

    if ($postId === (int) ($existingIds[0] ?? 0) || $existingPost) {
        $updated++;
    } else {
        $created++;
    }
}

pru_import_log(sprintf(
    'Legacy posts imported: %d created, %d updated, %d failed.',
    $created,
    $updated,
    $failed
));
