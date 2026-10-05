# NIHR PRU initial UAT migration

Status on 2026-10-05: initial local-to-UAT import **completed and verified**.

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

The user supplied the ZIP `All-In-One-WP-Migration-With-Import-master.zip`. Its installed plugin is the old 6.77 fork, not the current official ServMask release. The user confirmed installation on UAT at action time. It completed both export and import and is now **inactive on local and UAT**. The original ZIP and private export have been retained for recovery.

## Completed deployment

- Imported the complete `.wpress` archive into `https://pru.readysalteddev.co.uk/`. The importer displayed “Your data has been imported successfully!”; no seed or legacy import scripts were rerun.
- Reused an imported project administrator through SiteGround's normal admin sign-in. All 4 administrators are present; no accounts were recreated and no passwords were changed.
- Saved the `/%postname%/` permalink settings twice after import.
- The importer changed the domain but initially left some UAT URLs with `http`. Set WordPress Address and Site Address to HTTPS, then used SiteGround **WordPress Search & Replace** for the exact string `http://pru.readysalteddev.co.uk` → `https://pru.readysalteddev.co.uk`. This was a protocol cleanup, not a second domain/content migration.
- Before that bulk replacement, created an additional SiteGround manual backup **NIHR PRU imported before HTTPS cleanup 2026-10-05**, displayed time 05/10/2026 00:47. The earlier pre-import backup remains available.
- Restored the existing SiteGround Security Optimizer and Speed Optimizer plugins and purged SG Cache. AI Agent and SiteGround Central remain installed but inactive; no new minification settings were introduced.
- Kept search-engine indexing discouraged. Reading Settings show Home as the static homepage, News and Events as the posts page and 9 archive posts per page.
- Host PHP Manager reports PHP 8.2.34. ACF reported automatic license activation for the changed site URL; no license key was manually entered during transfer.
- WordPress Site Health completed with **Good**, without critical issues. Remaining recommendations concern inactive plugins/themes, the intentional UAT noindex setting and opcode cache; these are not migration failures.

## Post-import verification

- Public REST totals exactly match all 7 export baseline counts above; all 45 posts have Featured Images.
- All 383 original Media Library file URLs returned successful HTTP responses.
- Checked rendered HTML for all 37 published pages plus 8 sample post/project/person/resource detail pages: no HTTP failures, rendered PHP warning/fatal messages, `nihrpru.local`/`nihrmdc.local` references or insecure target-domain URLs.
- The Home admin editor contains 13 actual ACF layouts and 20 populated image fields; all 20 previews loaded. This verifies editor data, not a theme fallback.
- Browser checks confirmed the homepage, Events filter with a persistent filter bar, working Events page 2/Prev pagination, and the team Operations filter returning one normally sized card.
- Policy Briefings links point to local UAT HTTPS PDFs; these files were covered by the media HTTP check.
- The server-side `wp-content/ai1wm-backups/` directory responds **403**. The private archive, SQL snapshot, audit JSON and screenshots stay outside the theme repository/public web root under `migration/uat-2026-10-05/` in the Local project.
- Verification report: `migration/uat-2026-10-05/uat-public-audit.json` (private local artifact). Screenshots include `uat-import-success.jpg`, `uat-https-cleanup.jpg` and `uat-homepage-complete.jpg`.

## Checklist for repeating a full migration

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

## Theme-only update: homepage team CTA (2026-10-05)

- Figma homepage node `944:273` specifies a 318 px-wide team button. The old `display: flex` block with only `min-inline-size: 318px` expanded to the full 1192 px container. Set `inline-size: 318px`, `max-inline-size: 100%` and `min-inline-size: 0`, preserving the existing centered alignment and mobile full-width rule.
- Uploaded only `Components/MeetTheTeam/_style.scss`, `dist/assets/main-1KMNVcJC.css` and `dist/.vite/manifest.json`. Existing UAT files matched the Git baseline before replacement; deployed file SHA-256 hashes match the local files. The manifest switch was atomic and the old CSS file remains available for cached pages/rollback.
- Private remote backup: `/home/customer/nihrpru-team-button-20261005.iex6xX/`, outside the public website root. `style-before.scss`, `manifest-before.json` and `main-before.css` preserve the replaced baseline. For rollback, restore the first two to their original theme paths; the old `main-Do2OrDnm.css` asset is still present, then purge the SiteGround cache.
- `npm run build:production` and stylelint of the changed homepage component variant passed. The full component still has 20 inherited lint violations outside that variant. Unrelated duplicate-entry JavaScript build/manifest ordering churn was excluded. The CSS rebuild also includes an already-committed, equivalent logical-padding declaration for the accessibility widget; no new accessibility source changes were made.
- SiteGround asset and dynamic caches purged successfully; file cache was not enabled. Browser measurements on local and UAT confirm a centered 318 px desktop CTA; at a 390 px UAT viewport it uses the available 358 px width without horizontal overflow.
- Screenshot: `migration/uat-2026-10-05/uat-team-button-fixed.jpg` (private local artifact).
- Database, ACF content, Media Library, users and the live legacy website were untouched.

## Theme-only update: button hover contrast (2026-10-05)

- Paired shared button text/background colors for hover and keyboard focus. Removed fixed Home component background overrides that left navy text on a navy/twilight button; retained the existing 318 px team CTA and all other layout dimensions. Corrected purple and outline button variants, coral people/project filter states and news/events/search pagination colors. Header contact/search and resource filter styles already had suitable contrast and were preserved.
- Audited all 37 published pages plus Events/Publications archives, five content-type detail pages and search locally. On UAT, checked 36 distinct interactive hover styles across Home, Our people, Our projects, News and Events, Policy Briefings, For researchers, Publications, Accessibility, search and a resource detail page. No measured visible button-label hover contrast was below 4.5:1. Screen-reader-only clipped labels and noninteractive pagination spans were excluded from that contrast/hover assessment.
- `npm run build:production` and `git diff --check` passed. Compared stylelint results for all 12 modified SCSS files against the Git baseline: no additional violations (351 remaining inherited warnings versus 357 before). Excluded unrelated duplicate-entry JavaScript build ordering churn.
- All 15 UAT baseline files matched commit `8bca36a` before deployment. Saved a private backup at `/home/customer/nihrpru-button-hover-20261005.V0ijz4/before.tar.gz`, then uploaded the 12 SCSS sources, `dist/assets/main-BT4mPr2U.css`, `dist/assets/editor-style-Q88b9AqX.css` and `dist/.vite/manifest.json`. Staged and deployed SHA-256 hashes match the local files; switched the manifest last. Previous main/editor CSS assets remain on UAT for cached pages and rollback.
- SiteGround asset and dynamic caches purged successfully; file cache was not enabled. Browser stylesheet inspection confirms UAT serves `main-BT4mPr2U.css`. The team CTA hover is navy text on white, measured contrast 13.87:1, width still 318 px.
- Screenshot: `migration/uat-2026-10-05/uat-button-hover-fixed.png` (private local artifact outside the theme repository). For rollback, restore the files from `before.tar.gz` to the theme paths and purge SiteGround caches.
- No database import, content/media/user changes or updates to the legacy live site were performed.
