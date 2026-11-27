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
| `extras/apt-mail/` | Separate plugin: sends the site's email through Gmail; new-post notification recipients (Settings → Email). See its README |
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

## Content check

**Tools → Content check** (`inc/content-check.php`) lists what needs an
editor's attention, read only, with links to where each is fixed: pages,
categories and the image the code relies on by ID; meeting categories without
a Theme or Description; theme pages with a missing pillar or unknown slugs;
statements of the last 12 months without a photo; mistyped special tags;
Limited Access links outside the protected folder; and shortcodes: which are
used where (pages, posts, widgets, the theme's templates) and which plugin
provides each, any showing as raw text, and plugins whose shortcodes nothing
uses (candidates for removal).

## Statement settings

The post edit screen has a **Statement settings** box
(`inc/statement-settings.php`) for the special tags the theme reads:
short name (`SM_…`), date of an upcoming meeting (`SC_month`, `SC_year`,
`SC_nodate`), and `hasnocontent`, `hyperlinked`, `noshorttitle`, `marker`.
It saves the same tags, so typing them in the Tags box still works; the box
only changes what was changed in it.

## Themes of meeting categories

Each meeting category belongs to one theme page (a child page of "Themes").
Set it in **Posts → Categories → edit a category → Theme**; the categories
list has a Theme column (`inc/category-themes.php`). Behind it, each theme
page keeps a `tpc_topics` custom field (comma-separated category slugs),
which the dropdown edits for you; renaming a category's slug updates it too.
The theme also needs `tpc_pillar` (e.g. `economic-cooperations`) on new theme
pages.

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
  folder (those files are still public). Its button moves them into the
  folder (`private/2021/11/…`, keeping the year/month folders) and updates
  their links and Media Library items; files also used on public content
  are skipped and listed. **Undo the last move** puts everything back.
- The PDF search add-on skips the folder.
- New Limited Access documents must go into this folder; files uploaded
  through the Media Library are public.
- **The documents page** keeps its plain content (a heading, then a bullet
  list of links, for each group). The theme shows it with a bar of shortcuts
  to each heading (with how many documents it lists) and the log-out button,
  the documents as a grid of tiles, newest first (editors keep adding new
  documents at the bottom of a list), a box to find a document as you type,
  and the title without "Private:". A new heading and list appear in the
  same style; nothing else is needed.

## Home page content (Appearance → Home page content)

`inc/home-content.php`: the parts of the home page, the Contact page and the
footer that are not taken from posts, edited on one screen by Editors and
Administrators, in four tabs:

- **About:** the intro paragraph.
- **Figures:** the facts-and-figures cards (value, text, year, source, link,
  "As of" date, icon). Add, remove and drag to reorder.
- **Cooperation cards:** the section intro, the cards (name, text, links,
  where the text is quoted from, "Last checked" date) and the source line.
- **Contact:** the ASEAN Secretariat's address, phones, email and map
  (Contact page and footer).

A tab that was never saved, or whose data is empty or broken, shows the
original text (`apt_home_content_defaults()`); **Restore original text**
puts it back. Figures older than 12 months (by "As of"), cards not checked
for 12 months, and anything without a date are listed at the top of the
screen and under Tools → Content check (section 8). Saving clears WP Super
Cache so the change shows at once.

## Source watch (Posts → Source watch)

`inc/source-watch.php`: finds new ASEAN Plus Three items on other sites,
twice a day (WP-Cron), from RSS feeds. Default feed: asean.org's search for
"ASEAN Plus Three", newest first. Add any other feed; for sites without one,
a Google Alert set to "Deliver to: RSS feed".

- **In title / Mention:** one of the words (default "ASEAN Plus Three,
  ASEAN+3, APT") is in the title, or only in the text.
- **Already on site:** same title (ignoring capitals and punctuation) or the
  same address in a post's `source` field.
- **Words to edit out** (default "Secretary-General"): such items get a tag;
  their drafts show a "Before publishing" box listing the title and every
  sentence that still contains the words. Nothing is deleted automatically.
- **Email:** one per check that finds new items, through `wp_mail` (APT Mail).
- **Create draft:** title, date, the item's text (no photos or galleries, no
  "appeared first on" line) and the source address in the `source` field.
  Links lose their tracking parameters; Google Alerts links are unwrapped.

Options: `apt_sw_settings`, `apt_sw_items`, `apt_sw_feeds`, `apt_sw_last`,
`apt_sw_new_count` (the menu badge, so admin pages don't load the whole list).

## Redirects (Tools → Redirects)

`inc/redirects.php`: send old or duplicate addresses to the right page.

- **Types:** 301 (moved for good; Google moves the page's standing to the new
  address), 302 (temporary), 410 (gone for good, no replacement).
- Runs before WordPress shows the page, so it also works for pages that still
  exist (duplicates such as /gallery2/). Capitals and the trailing slash
  don't matter; the query string (?page=2) is kept.
- When a published **page's** address changes (slug or parent), a 301 from
  the old address is added automatically (WordPress does this for posts only).
- **Not found (404) log:** the most requested missing addresses (no files,
  admin or scanner noise), with "Add redirect" and "Forget".
- Chains and loops are flagged; the admin area and the home page can't be
  redirected. Saving clears WP Super Cache.
- Counters are written at most once a minute per address, and the 404 log at
  most every 15 seconds overall, so bots can't cause a database write per
  request (counts are "minutes with requests").

Options: `apt_redirects`, `apt_404_log`.

## Import agenda (Posts → Import agenda)

`inc/agenda-import.php`: turns the ASEAN notional calendar (Excel) into
agenda entries.

- Upload the .xlsx (or a CSV). The visible sheet is read (older revisions are
  hidden sheets); the heading row is found by its "Meeting" and "Date" columns.
- Only ASEAN Plus Three meetings are kept ("ASEAN+3", "ASEAN Plus Three",
  "Plus 3", "APT", "…+3").
- Dates: Excel dates and "5-9 October 2026" → that day; "March 2026" →
  month only (SC_month); "Q4" → year only (SC_year); "TBC" or empty → to be
  decided (SC_nodate, dated 31 December).
- The meeting series (category) is guessed from the categories' names,
  descriptions and slugs; a corrected series is remembered (`apt_ai_map`).
- Statuses: New, Date changed (importing updates the existing entry), Already
  in the agenda, Already published, Past, Cancelled (from Remarks).
- Ticked rows become **Pending Review** posts: meeting date as the post date,
  series as the category, city as the Location, SC_ tag when needed.

**Locations and flags:** a city's country and flag come from
`get_the_country()` in functions.php (Dili/Timor-Leste, Hokkaido, Hakone,
Boracay, Laoag, Republic of Korea/ROK and Uzbekistan were added; the
Timor-Leste flag is `img/flags/tl.svg`). For any other city, Posts →
Locations has a **Country** field; the importer fills it from the calendar's
venue ("Hakone, Japan") when it creates a new city.

## News page

`page-news-2.php` with `inc/news-page.php`: statements and news, newest
first, 12 per page, as a list grouped by month (the home page keeps its card
carousel).

- **Filters:** Pillar (Political-Security, Economic, Socio-Cultural), Level
  (Summit, Ministerial, Others) and Year. They read the existing categories,
  so editors do nothing new. Pillar follows the same rule as the pillar tag
  on the cards (the meeting category's parent slug contains "political",
  "economic" or "socio"); Summit and Ministerial are categories 41 and 43
  with their sub-categories; Others is everything else.
- The filters are plain links (`/news-2/?pillar=ec&level=summit&yr=2025`), so
  they work without JavaScript and crawlers can follow them. Filtered lists
  are marked `noindex, follow` so they are not indexed as duplicates of the
  full list. Pagination keeps the filters.
- Each row: photo, tag (Summit, or the pillar), level and topic, short name,
  full title, place and date. The whole row is clickable.

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
- **Thin pages kept out of Google** (`inc/indexing.php`, from the Search
  Console exports of 27 November 2026):
  - tag archives (the special SM_/SC_ tags), author archives and the
    `/category-descriptors/` pages: `noindex, follow`, and left out of the
    sitemap;
  - Locations (`mec_location`) have no public pages any more (their old
    addresses return 404);
  - tag, category, author and comment feeds are no longer advertised in the
    page head, and shortlinks (`?p=123`) are removed.
- **Not in the theme:** the theme adds no structured data of its own; it
  comes from Yoast.

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
