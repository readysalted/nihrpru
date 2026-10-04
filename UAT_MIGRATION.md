# NIHR PRU initial UAT migration

Status on 2026-10-05: local export ready; UAT import **not yet performed**.

The pre-import target backup is confirmed in SiteGround Site Tools: manual backup **NIHR PRU before local import 2026-10-05**, displayed creation time 05/10/2026 00:31. It contains the target state before installing the migration ZIP or importing local data.

Source: `http://nihrpru.local/`.
Target: `https://pru.readysalteddev.co.uk/`.
The live legacy website is not a deployment target.

## Export baseline

- WordPress 7.1.2, PHP 8.2.29, PRU theme source commit `8d0215f`.
- 37 published pages, 45 published posts, 26 research projects.
- 36 people (33 team members and 3 collaborators), 125 publications, 43 resources.
- 383 attachment records; all original attachment files exist locally.
- Home: 13 ACF component rows and 20 populated image attachment IDs with files present.
- 4 existing project users, all administrators. Do not recreate them after importing the database.
- Permalinks: `/%postname%/`; `blog_public=0`.

Export file: `migration/uat-2026-10-05/nihrpru-uat-ready-2026-10-05.wpress`, relative to the Local project root (outside this theme repository and the public web root).
Size: 363,769,677 bytes (about 347 MiB).
SHA-256: `32e4f622a65102d394fc2ea397e317393f76fa8c341f69810a93e90fc1f7602b`.

The archive has a valid end marker and 5,258 entries, including `database.sql`, `package.json`, theme `vendor/autoload.php`, the Vite manifest and 1,509 upload files. No theme `.git`, `node_modules` or `tools` files are included.

The user supplied the ZIP `All-In-One-WP-Migration-With-Import-master.zip`. Its installed plugin is the old 6.77 fork, not the current official ServMask release. It completed the local export; remote import compatibility has not yet been verified. Do not leave this temporary migration plugin active after completion.

## Target deployment checklist

1. Save a recoverable backup of the existing UAT database and files before importing.
2. Install and activate the user-supplied ZIP on UAT after the required browser-installation confirmation.
3. Import the `.wpress` archive into UAT only. The import replaces UAT content/settings/accounts with the local snapshot.
4. Use an existing imported project account to sign in; do not reset user passwords as part of migration.
5. Save permalinks, verify HTTPS target URLs and confirm that no rendered assets/internal links depend on `nihrpru.local`.
6. Compare the baseline counts and confirm ACF content/image fields are populated in the admin editor.
7. Keep UAT excluded from search engines. Verify the runtime PHP compatibility and ACF license status; any license reactivation must use the owner's existing entitlement.
8. Preserve the target's SiteGround security configuration where appropriate, clear UAT caches and check important pages, filters, pagination and downloads.
9. Disable the old migration plugin and protect/remove server-side migration archives after ensuring a private recoverable copy exists.
10. Record actual deployment completion and verification results here before treating UAT as deployed.

No seed/import script rerun is necessary after a full database import. Future theme-only deployments should preserve the UAT database and Media Library.
