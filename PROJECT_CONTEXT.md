# NIHR PRU — living project context

Last verified: 2026-10-05 (local migration snapshot; UAT deployment not yet completed)

This is the maintained source of truth for the NIHR Policy Research Unit in Behavioural and Social Sciences WordPress theme. Update it after meaningful changes to code, content structures, dependencies, build configuration, or design decisions. Never store passwords, tokens or database credentials here.

## Project snapshot

| Item | Current state |
| --- | --- |
| Local project root | `/Users/andriikukura/Local Sites/nihrpru` |
| WordPress root | `app/public` |
| Custom theme | `app/public/wp-content/themes/pru` |
| Local URL | `http://nihrpru.local/` |
| UAT URL | `https://pru.readysalteddev.co.uk/` |
| Production/source site | `https://behscipru.nihr.ac.uk/` |
| Figma | `https://www.figma.com/design/m31oorpaV9jVgwhTMfryjH/NIHR-PRU-Behavioural-Science?node-id=904-62` |
| Repository | `git@github-readysalted:readysalted/nihrpru.git` |
| Branch | `main` |
| Architecture | Flynt 2.1.1 component theme, Timber 2, Twig 3, ACF Pro |
| Build | Vite 5; Node >=20; source assets compile to tracked `dist` output |
| WordPress | 7.1.2 locally |
| Runtime plugins | Advanced Custom Fields Pro 6.8.10, Breadcrumb NavXT 7.5.2, WP Accessibility, WP Sitemap Page 1.9.6 |
| Temporary migration plugin | User-supplied All-in-One WP Migration With Import 6.77; disable after transfer |

## Current implementation

- The site identity, package metadata, Composer package and Vite local host are NIHR PRU-specific.
- WordPress is configured with a static Home page, `/%postname%/` permalinks, Europe/London timezone and search-engine visibility disabled for development.
- The approved Figma desktop homepage is implemented as 13 editable ACF Flexible Content rows.
- The primary desktop and mobile menus contain: Our Projects, Outputs, For Policy Makers, For Researchers, For the Public, About Us and Our Collaborators. Contact is the global header CTA.
- The footer contains real page links, Newcastle University contact details, social links and NIHR funding information.
- `/wp-login.php` uses the PRU navy palette, approved hero image and the two-part NIHR Policy Research Unit identity.
- Four project users supplied through the Asana task exist locally as WordPress administrators. Credentials are intentionally not stored in the repository.
- 37 pages are published locally, including Home, audience pages, About, Collaborators, Team, Contact and required information pages (migration snapshot on 2026-10-05).

## Homepage design system

Approved tokens from Figma:

- Putty `#FAF4EB`
- Navy `#1C285E`
- Slate `#2D2D2D`
- Coral `#FC5D5D`
- Blue Flame `#0051C2`
- Glove Blue `#C7E9FF`
- Lilac `#C4C5FF`
- Twilight `#3E439C`
- Pink Quartz `#FFD1E0`

The Home page uses these component variants:

1. `BlockImageTextHero` / `homepage`
2. `BlockPostTabs` / `homepage`
3. `BlockWysiwyg` / `homepageIntro`
4. `GridImageText` / `homepageFeatures`
5. Partner introduction `BlockWysiwyg`
6. `ListLogos` / `homepagePartners`
7. Partner/funding copy `BlockWysiwyg`
8. `MeetTheTeam` / `homepageTeam`
9. `GridImageText` / `homepageProjects`
10. Public involvement `BlockImageText` / `homepageFeature`
11. Latest output `BlockImageText` / `homepageFeature`
12. COVID-19 response `BlockImageText` / `homepageFeature`
13. Policy brief guide `BlockImageText` / `homepageFeature`

All 20 editable Home image fields are populated with real WordPress attachment IDs and alt text. Theme assets may still be used for interface chrome such as the logo, search icon and carousel arrow artwork; content imagery must not depend on a code fallback.

## Content migration and seeding

Two repeatable WP-CLI scripts live in `tools/`:

- `tools/import-legacy-posts.php` imports or updates all published posts from the old PRU REST API. It imports taxonomies, featured images and inline images into the Media Library, rewrites old internal URLs and records legacy IDs/source URLs in post meta.
- `tools/seed-pru-site.php` imports approved Figma images into the Media Library, creates/updates pages, fills the Home ACF rows, builds the menu and sets header/footer options. It is idempotent and avoids reusing attachments as pages when slugs match.

Current verified local migration state:

- 45 published legacy posts imported.
- 45/45 published posts have real Featured Images.
- No old-site domain remains in imported post content or excerpts.
- 20/20 approved Home content images exist as Media Library attachments.
- The legacy import and site seed can safely be rerun after a database refresh.

Example commands (use the Local PHP binary and configuration for the `cuYP3rhSw` run):

```sh
wp eval-file app/public/wp-content/themes/pru/tools/import-legacy-posts.php --path=app/public
wp eval-file app/public/wp-content/themes/pru/tools/seed-pru-site.php --path=app/public
```

## Relevant files

| Path | Responsibility |
| --- | --- |
| `inc/fieldGroups/pageComponents.php` | Registers ACF Flexible Content layouts |
| `inc/fieldVariables.php` | Theme selector choices, including Blue Flame |
| `inc/login.php` | PRU login branding |
| `Components/BlockImageText/` | Generic image/copy block plus large Home feature variant |
| `Components/GridImageText/` | Audience and latest-project card variants |
| `Components/MeetTheTeam/` | Six-person Home team layout |
| `Components/BlockPostTabs/` | Latest News & Events carousel |
| `Components/NavigationMain/` | Desktop header identity and navigation |
| `Components/NavigationBurger/` | Mobile header identity and navigation |
| `Components/NavigationFooter/` | Footer content/options and social icons |
| `assets/images/pru/` | Approved Figma-exported PRU design assets |
| `tools/import-legacy-posts.php` | Repeatable legacy post migration |
| `tools/seed-pru-site.php` | Repeatable page, ACF, menu and option seeding |

## Verification on 2026-10-01

- `npm run build:production` — passed.
- Targeted PHPCS across changed PHP and migration files — passed.
- PHP syntax checks for both WP-CLI scripts and login branding — passed.
- HTTP checks for `/` and `/wp-login.php` — both returned 200 with no rendered PHP warning/fatal text.
- Browser QA at desktop and 390 px mobile — passed; no horizontal overflow and no browser console errors.
- WordPress data audit — 13 Home rows, 20 editable image fields, zero missing images, 45 published posts, 45 Featured Images, 18 published pages and 4 users.
- Full repository `npm run lint:styles` is not clean because the inherited MDC/Flynt codebase contains 900+ pre-existing stylelint violations. New styles use logical sizing/spacing; this baseline debt is not part of the PRU implementation.

## Deployment notes

- The repository contains the theme and repeatable migration/seed scripts, not the database or user credentials.
- The initial local-to-UAT transfer uses a complete All-in-One WP Migration `.wpress` export, including database, plugins, uploads and the PRU theme. It is not a theme-only deployment.
- Do **not** rerun migration/seed scripts after importing the complete database: the current database already contains the edited pages, menus, ACF values and user accounts. Later UAT content edits must not be overwritten by another local database import without an explicit content-freeze agreement.
- The export excludes `themes/pru/.git`, `node_modules`, development caches/editor settings and `tools`. It retains Composer `vendor` and compiled `dist`, including `dist/.vite/manifest.json`.
- The `.wpress` archive and SQL backups contain private account/license information; they must stay outside Git and must not be published as theme assets.
- See [UAT_MIGRATION.md](UAT_MIGRATION.md) for the transfer baseline and remaining deployment steps.
- Keep search-engine visibility disabled on local/UAT. Confirm the launch URL, legal copy, analytics/cookie configuration and privacy setting before production launch.
- A pre-work database snapshot exists locally at `/private/tmp/nihrpru-initial-2026-10-01.sql`; it is intentionally outside Git.

## Change log

- 2026-10-05: Installed the user-provided migration ZIP locally, saved a pre-plugin SQL backup, generated a verified 347 MiB `.wpress` export without Git/development dependencies, and recorded the content/media/user baseline. Created the SiteGround manual backup `NIHR PRU before local import 2026-10-05` on UAT. UAT import is pending plugin-installation confirmation.
- 2026-10-01: Created NIHR PRU theme identity from the NIHR MDC base; implemented the Figma Home design and new component variants; imported approved assets as real Media Library records; migrated all 45 legacy posts with featured/inline media; created pages, menus, options and users; branded the login; added reproducible migration and seed scripts; completed build and browser QA.
