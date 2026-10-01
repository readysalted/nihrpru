<?php

/**
 * Refresh and normalize only migrated PRU project content.
 *
 * Usage:
 * php tools/normalize-pru-projects.php [--dry-run]
 */

define('PRU_MIGRATION_LIBRARY_ONLY', true);
require_once __DIR__ . '/migrate-pru-content.php';

$dryRun = in_array('--dry-run', $argv, true);

try {
    $rows = pruFetch(
        '/wp-json/wp/v2/our-projects?per_page=100&_fields=id,slug,title,content,featured_media'
    );

    pruLog(($dryRun ? 'Auditing' : 'Normalizing') . ' PRU project content');
    foreach ($rows as $row) {
        $legacyId = (int) ($row['id'] ?? 0);
        $postId = pruFindByLegacy('research', 'our-projects', $legacyId);
        if (!$postId) {
            pruLog('  Skipped missing project: ' . ($row['slug'] ?? $legacyId));
            continue;
        }

        $fields = pruExtractProjectFields((string) ($row['content']['rendered'] ?? ''));
        $content = pruRewriteHtml($fields['content'], $postId);
        $content = pruStripFeaturedImage($content, (int) get_post_thumbnail_id($postId));
        $summary = pruRewriteHtml($fields['summary'], $postId);

        $dom = pruDom($content);
        $imageCount = (new DOMXPath($dom))->query('//img')->length;
        pruLog(sprintf(
            '  %-64s team:%s images:%d',
            get_the_title($postId),
            $fields['team'] !== '' ? $fields['team'] : '—',
            $imageCount
        ));

        if ($dryRun) {
            continue;
        }

        update_post_meta($postId, '_pru_legacy_html', $fields['content']);
        update_field('projectLead', $fields['lead'], $postId);
        update_field('projectTeam', $fields['team'], $postId);
        update_field('projectSummary', $summary, $postId);
        update_field(
            'field_researchComponents_pageComponents',
            $content !== '' ? [pruWysiwyg($content)] : [],
            $postId
        );
        wp_update_post([
            'ID' => $postId,
            'post_excerpt' => wp_trim_words(pruDecode($fields['summary'] ?: $fields['content']), 34, '…'),
        ]);
    }

    pruLog($dryRun ? 'Project audit complete' : 'Project normalization complete');
} catch (Throwable $exception) {
    fwrite(STDERR, 'Project normalization failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
