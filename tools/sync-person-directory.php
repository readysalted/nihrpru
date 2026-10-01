<?php

/**
 * Refresh editable person roles, role groups and directory order from the legacy site.
 *
 * Run with the Local MySQL socket configured:
 * php -d mysqli.default_socket=/path/to/mysqld.sock tools/sync-person-directory.php
 */

define('PRU_MIGRATION_LIBRARY_ONLY', true);
require_once __DIR__ . '/migrate-pru-content.php';

try {
    pruLog('Person directory sync started');
    $updated = pruSyncPersonDirectoryData();
    pruLog(sprintf('Person directory sync complete: %d profiles updated', $updated));
} catch (Throwable $exception) {
    fwrite(STDERR, 'Person directory sync failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
