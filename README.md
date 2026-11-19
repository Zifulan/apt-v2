# AKSEKI APT theme

WordPress theme for the ASEAN Plus Three website (aseanplusthree.asean.org).
Version 2 restyles the site with a design system inspired by the
ASEAN-Korea Cooperation Fund site, in the official ASEAN colours.

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
| `page-frontpage.php`, `template-parts/front-*_bento.php`, `template-parts/front-topnew4.php`, `template-parts/sidebar-agenda_bento.php` | Home page sections (see below) |
| `functions.php` | Enqueues, helpers, summit count (`apt_get_summit_count`), pillar quotes (`apt_pillar_quotes`, `apt_statement_quote`) |
| `docs/content-setup.md` | Changes made in WordPress, not the theme: Contact page, pages to unpublish, how editors choose the homepage quote |
| `list-category-posts/` | Templates for the List Category Posts plugin |
| `img/flags/`, `img/summitlogo/` | Loaded by name at run time (country flags, summit logos by year) |

The theme expects the live site's content: specific category IDs
(7, 8, 9, 40, 41, 45), a page with the slug `frontpage` as the static front
page, posts tagged `front` for the hero, and the List Category Posts and
Search & Filter plugins.

## Home page

| Section | File | Content |
|---|---|---|
| Hero | `front-topnew4.php` | Latest post tagged `front`, APT Highlights, quick links |
| Latest News & Activities | `front-news_bento.php` | Carousel of the 12 newest statements and news |
| ASEAN Plus Three at a Glance | `front-about_bento.php` | Years, summits and ministerial meetings (counted from site content); trade, FDI, population, GDP and visitor figures (quoted, see below) |
| Cooperation in Practice | `front-cooperation_bento.php` | CMIM, AMRO, APTERR and ABMI, each quoted from an official source, with a link to its page on this site and its official site |
| Resources | `front-resources_bento.php` | Key Documents; **Latest Statements by Pillar** (newest statement per pillar, quoted); Mechanisms. On phones only Key Documents starts open |
| Upcoming Agenda | `sidebar-agenda_bento.php` | Scheduled meetings, or the 5 most recent under "Recent Meetings" when none are scheduled |

### Content rules

- **Quote, don't paraphrase.** Every fact on the home page is quoted word for
  word from an ASEAN Secretariat or official APT source, and cited on the page:
  the ASEC *Overview of ASEAN Plus Three Cooperation* (as of 14 November 2025),
  ASEANstats *ASEAN Statistical Highlights 2025*, the APT site's own CMIM, AMRO
  and ABMI pages, and the ASEC APTERR factsheet. Each template lists its
  sources in a comment at the top. When a source is updated (the Overview is
  due in December 2026), replace the quote; don't reword it.
- **Naming.** Theme text says "ASEAN Plus Three". "ASEAN+3" stays only inside
  quotes and official names (e.g. the ASEAN+3 Macroeconomic Research Office).
- **Pillar quotes.** "Latest Statements by Pillar" takes the newest statement in
  each pillar's categories (`political-cooperations*`, `economic-cooperations*`,
  `socio-cultural-cooperations*`, at every level). The quote is the post's
  Excerpt if it appears word for word in the statement, otherwise its opening
  sentences (whole sentences only). Editors see a notice when an Excerpt isn't
  word for word. Cached until a post is saved. Editor steps are in
  `docs/content-setup.md`.

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

`asean-flag.webp` is the ASEAN flag (3:2) made from the same artwork: the
emblem without its wordmark, stalks centred, on a field of the emblem blue.
`img/flags/asean.svg` wraps a small copy of it and is shown as the round flag
for "Online Conference" meetings.

## Palette

Official ASEAN colours. Pantone values are from the Guidelines on the Use of
the ASEAN Emblem (Annex) and the 2011 Guidelines on the Use of the ASEAN Flag;
web (RGB) values are from the ASEAN Secretariat's ASEAN Day 57 Branding
Guideline (2024).

| Token | Colour | Source |
|---|---|---|
| `--apt-blue` | `#2C59A7` | Pantone 286 |
| `--apt-red` | `#C73935` | Pantone Red 032 |
| `--apt-yellow` | `#FBF315` | Pantone Process Yellow |
| `--apt-maroon` | `#98294B` | Original APT burgundy: non-pillar tags, the "Other Documents" heading, agenda markers |
| `--apt-crimson` | `#C9234A` | Previous APT navbar: defined but no longer used |

Pillars use the three official colours: Political-Security = blue,
Economic = yellow, Socio-Cultural = red. ASEAN does not assign colours to
pillars, so this mapping is the site's own. Yellow is too light for text or
thin lines on white: pillar dots carry a thin outline, and yellow is not used
for text on white.

**Background.** Official blue to official red with a short blend mixed in Oklab
(so the middle stays muted, not purple) and a 10% yellow glow in the
bottom-right corner, on a fixed layer that drifts slowly (off with reduced
motion). It is tuned for contrast: white text stays at least 4.52:1 everywhere
on it, so text on the gradient is full white (`--on-grad-2`) and translucent
panels on it use a dark tint, not a white one. Stronger yellow or white tints
drop text below 4.5:1. The ASEAN flag itself is not used as a background: the
flag guidelines require it to be "treated with respect" and it is the
"reserved copyright of ASEAN".

## Layout

`--page-max` (1280px) and `--page-gutter` (16–48px) set one fluid `.container`
for every page, and the frameless home hero uses the same edges, so all
sections line up at every width. This replaces Bootstrap's stepped container
widths.
