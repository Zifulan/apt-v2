# AKSEKI APT theme

WordPress theme for the ASEAN Plus Three website (aseanplusthree.asean.org).
Version 2 restyles the site with a design system inspired by the
ASEAN-Korea Cooperation Fund site, in APT/ASEAN colours.

## Install / preview

1. Zip this folder (or upload it with your host's file manager) into
   `wp-content/themes/`.
2. In WordPress go to **Appearance → Themes** and use **Live Preview** before
   activating. Menu locations are saved per theme, so set **Primary Menu**
   under *Menus → View All Locations* in the Customizer.
3. On staging copies add `define( 'WP_ENVIRONMENT_TYPE', 'staging' );` to
   `wp-config.php` so test visits are not sent to the live Google Analytics.

## Where things are

| Path | What |
|---|---|
| `assets/css/apt-design.css` | Design system: tokens, components, layout |
| `assets/js/apt-nav.js` | Menu drawer (native `<dialog>`, swipe to close) |
| `assets/js/apt-carousel.js` | News carousel (scroll snap, looping, markers) |
| `page-frontpage.php`, `template-parts/front-*_bento.php`, `template-parts/front-topnew4.php`, `template-parts/sidebar-agenda_bento.php` | Home page sections |
| `functions.php` | Enqueues, helpers, summit count (`apt_get_summit_count`) |
| `list-category-posts/` | Templates for the List Category Posts plugin |
| `img/flags/`, `img/summitlogo/` | Loaded by name at run time (country flags, summit logos by year) |

The theme expects the live site's content: specific category IDs
(7, 8, 9, 40, 41, 45), a page with the slug `frontpage` as the static front
page, posts tagged `front` for the hero, and the List Category Posts and
Search & Filter plugins.

## ASEAN emblem

`img/asean/` holds the updated emblem (11 stalks, after Timor-Leste joined)
and its lockups, all with transparent backgrounds:

| File | Use |
|---|---|
| `asean-emblem-128.webp`, `asean-emblem-256.webp` | Header and footer (via `load_logo_svg()`) |
| `asean-emblem.webp` | Emblem master (1906px) |
| `asean-logo-horizontal.webp`, `asean-logo-vertical.webp` | With "Association of Southeast Asian Nations" |
| `asean-logo-tagline.webp` | With "one vision, one identity, one community" |

The lockup text is ASEAN blue, so use the lockups on light backgrounds only.
`img/flags/asean.svg` (shown for "Online Conference" meetings) wraps the same
emblem.

## Palette

| Token | Colour | Source |
|---|---|---|
| `--apt-blue` | `#0B3D91` | ASEAN emblem blue |
| `--apt-red` | `#CE1126` | ASEAN emblem red |
| `--apt-yellow` | `#FFCD00` | ASEAN yellow, softened for UI |
| `--apt-maroon` | `#98294B` | Original APT burgundy |
| `--apt-crimson` | `#C9234A` | Previous APT navbar |

Pillars: Political-Security = blue, Economic = amber `#B57F00`,
Socio-Cultural = red.
