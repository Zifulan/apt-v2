# apt-v2

WordPress theme for the ASEAN Plus Three website (aseanplusthree.asean.org).
Version 2 restyles the site with a design system inspired by the
ASEAN-Korea Cooperation Fund site, in the official ASEAN colours.

## Install / preview

1. Zip this folder (or upload it with your host's file manager) into
   `wp-content/themes/apt-v2/`. WordPress identifies a theme by its folder,
   not its name: if this theme is already active under another folder name,
   keep that folder, because a new folder counts as a new theme and the menu
   locations would need setting again.
2. In WordPress go to **Appearance → Themes** and use **Live Preview** before
   activating. Menu locations are saved per theme, so set **Primary Menu**
   under *Menus → View All Locations* in the Customizer.
3. On staging copies add `define( 'WP_ENVIRONMENT_TYPE', 'staging' );` to
   `wp-config.php` so test visits are not sent to the live Google Analytics.

## Where things are

| Path | What |
|---|---|
| `assets/css/apt-design.css` | Design system: tokens, components, layout |
| `assets/js/apt-nav.js` | Menu drawer (native `<dialog>`, swipe to close); fallbacks for the search dialog |
| `assets/js/apt-carousel.js` | News carousel (scroll snap, looping, markers) |
| `page-frontpage.php`, `template-parts/front-*_bento.php`, `template-parts/front-topnew4.php`, `template-parts/sidebar-agenda_bento.php` | Home page sections (see below) |
| `functions.php` | Enqueues, helpers, summit count (`apt_get_summit_count`), pillar quotes (`apt_pillar_quotes`, `apt_statement_quote`) |
| `header.php`, `template-parts/search-form.php` | Search dialog (native `<dialog>`) and the shared search form |
| `search.php`, `template-parts/search-result.php` | Search results page |
| `extras/apt-pdf-search/` | Separate plugin: search inside statement PDFs (see **Search** below) |
| `extras/apt-link-checker/` | Separate plugin: lists broken links, missing files and broken PDFs (Tools → Link checker). See its README |
| `extras/apt-local-search/` | Offline page for searching PDFs in a local or OneDrive folder (not part of the site). See its README |
| `docs/content-setup.md` | Changes made in WordPress, not the theme: Contact page, pages to unpublish, how editors choose the homepage quote |
| `list-category-posts/` | Templates for the List Category Posts plugin |
| `img/flags/`, `img/summitlogo/` | Loaded by name at run time (country flags, summit logos by year) |

The theme expects the live site's content: specific category IDs
(7, 8, 9, 40, 41, 45), a page with the slug `frontpage` as the static front
page, posts tagged `front` for the hero, and the List Category Posts plugin.
The Search & Filter plugin is no longer used by the theme.

## Search

**Search dialog.** The search button in the header (and "Search the site" on
the 404 and empty pages) opens a native modal `<dialog>` with a keywords field
and a category list. It is a plain WordPress search (`?s=…&cat=…`): no plugin.
Esc, the close button or a click outside closes it.

**Results page.** The same form sits at the top, pre-filled. Each result shows
the short meeting name, title, excerpt, place and date, and categories
(pillar categories with their colour dot).

**How results are ordered.** WordPress matches each word separately, so a
search for `WELCOME constructive engagement` finds every statement that
contains all three words anywhere. With the APT PDF Search plugin active,
multi-word searches are ranked in three groups:

1. the whole phrase in the **title**;
2. the whole phrase in the **page text or its PDF**;
3. the words found separately.

Within each group WordPress's own order applies. Single-word searches are
unchanged. To match only the exact phrase, put it in quotation marks:
`"WELCOME constructive engagement"`.

**Searching inside PDFs: the APT PDF Search plugin** (`extras/apt-pdf-search/`).
WordPress search only reads a post's title, excerpt and content, so text that
appears only in a statement's PDF isn't found. The plugin fixes that:

- It is a **separate plugin**, installed on its own (zip the folder, then
  Plugins → Add New → Upload Plugin), so search keeps working if the theme
  changes. It is not uploaded with the theme.
- After activating, run **Tools → PDF search → Index PDFs** once (newest
  posts first; the page warns if a run stopped early). Statements are then
  indexed automatically whenever they are saved. WP-CLI:
  `wp apt-pdf-search index [--force]`.
- It reads PDFs **stored on this site** that a post refers to, however they
  are attached (links, shortcodes such as `[pdf-embedder url="…"]`, PDF/file
  blocks, or PDFs uploaded to the post), on this server (bundled
  smalot/pdfparser, with `pdftotext` as a fallback where allowed), and adds
  their text to the search. Results are still the statement pages.
- **Limited Access Documents stay out of search:** only published content
  without a password is read; private and protected pages, their child pages,
  and anything with "limited" in its slug, title, category, parent page or PDF
  path are skipped, and their stored text is removed.
- When a match is only inside the PDF, the theme shows it under the result as
  **"Found in the attached PDF:"** with the passage quoted and highlighted:
  the whole phrase when the PDF contains it, otherwise the first matching
  word (`apt_pdf_search_snippet_html()`; the theme checks the plugin is
  active, so it works without it).
- **A statement isn't found?** Tools → PDF search → **Check a statement**:
  paste its address and a phrase to see, step by step, whether it can be
  indexed, which PDFs were read, whether the text is stored, and whether a
  real search returns it. If it is found there but not on the site, clear the
  cache (Sucuri or a caching plugin).
- Tested on the 203 public statement PDFs on the live site (November 2026):
  193 readable, 8 scanned images (would need OCR), 2 broken links on the site.
  Details, the broken links and uninstall notes are in
  `extras/apt-pdf-search/README.md`.

## Limited Access files

Limited Access documents are stored in `wp-content/uploads/private/` (uploaded
there by hand, not through the Media Library). They only open for logged-in
visitors (`inc/limited-access-files.php`):

- The folder gets a `.htaccess` that refuses direct requests, so old or shared
  file addresses stop working (written on the next admin page load).
- Links to the folder, in any page or post, become `/?apt_file=<path>`: the
  theme checks the login, then sends the file. Visitors who are not logged in
  go to the Limited Access login page.
- **Media → Limited Access files** checks that direct access is really
  blocked, and lists links on Limited Access pages that point outside the
  folder (those files are still public).
- The PDF search add-on skips the folder.
- New Limited Access documents must go into this folder; files uploaded
  through the Media Library are public.

## Home page

| Section | File | Content |
|---|---|---|
| Hero | `front-topnew4.php` | Latest post tagged `front`, APT Highlights, quick links |
| Latest News & Activities | `front-news_bento.php` | Carousel of the 12 newest statements and news |
| About ASEAN Plus Three | `front-about_bento.php` | Years, summits and ministerial meetings (counted from site content); trade, FDI, population, GDP and visitor figures (quoted, see below) |
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

## SEO

Yoast SEO (active on the live site) owns titles, canonical URLs, robots rules,
breadcrumbs, structured data and the XML sitemap. The theme works with it
rather than duplicating it (`functions.php`, section "SEO"):

- **Titles:** WordPress/Yoast prints the `<title>` (`title-tag` support). The
  theme no longer hard-codes it.
- **Meta descriptions:** where an editor hasn't written one, the theme fills it
  in from facts already on the page:
  - **Home:** the ASEAN Secretariat's first sentence.
  - **Statement:** its title, place and date.
  - **Page:** its opening text.
  - **Category:** its description.

  A description written in Yoast always wins.
- **Link previews:** when Yoast's Open Graph output is switched off, or Yoast
  isn't installed, the theme prints Open Graph and Twitter tags itself. Pages
  without a featured image use `img/share/apt-share-1200x630.jpg`, generated
  from the site's colours and the flag line-up.
- **Not in the theme:** the theme sets no robots rules and adds no structured
  data of its own; both come from Yoast.

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
