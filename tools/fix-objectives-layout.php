<?php

/**
 * Move the Our objectives illustration out of the WYSIWYG field and into an
 * editable BlockImageText image field.
 *
 * Run with the Local MySQL socket configured:
 * php -d mysqli.default_socket=/path/to/mysqld.sock tools/fix-objectives-layout.php
 */

define('PRU_MIGRATION_LIBRARY_ONLY', true);
require_once __DIR__ . '/migrate-pru-content.php';

try {
    $page = get_page_by_path('about-us/our-objectives', OBJECT, 'page');
    if (!$page instanceof WP_Post) {
        throw new RuntimeException('Our objectives page was not found.');
    }

    $components = get_field('pageComponents', $page->ID);
    if (!is_array($components)) {
        throw new RuntimeException('Our objectives page has no editable components.');
    }

    $updated = false;
    foreach ($components as $index => $component) {
        if (($component['acf_fc_layout'] ?? '') !== 'blockWysiwyg') {
            continue;
        }

        $replacement = pruPortraitImageText((string) ($component['contentHtml'] ?? ''));
        if (!$replacement) {
            continue;
        }

        $components[$index] = $replacement;
        $updated = true;
        break;
    }

    if (!$updated) {
        $alreadyUpdated = false;
        foreach ($components as $component) {
            if (
                ($component['acf_fc_layout'] ?? '') === 'blockImageText'
                && ($component['options']['displayStyle'] ?? '') === 'portraitFeature'
            ) {
                $alreadyUpdated = true;
                break;
            }
        }
        if (!$alreadyUpdated) {
            throw new RuntimeException('No objectives WYSIWYG block with an image was found.');
        }
        pruLog('Our objectives layout is already up to date.');
        exit(0);
    }

    if (!update_field('field_pageComponents_pageComponents', $components, $page->ID)) {
        throw new RuntimeException('Unable to save the Our objectives components.');
    }

    clean_post_cache($page->ID);
    pruLog(sprintf('Our objectives layout updated for page %d.', $page->ID));
} catch (Throwable $exception) {
    fwrite(STDERR, 'Our objectives layout update failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
