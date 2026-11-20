# Interim report update: work since the first draft

Handoff for whoever updates the interim report on the apt-v2 theme
(repository Zifulan/apt-v2). The first draft covered the redesign up to
18 November 2026. That draft had six sections: brand, readability, accessibility,
mobile, broken things fixed, and maintainability/security. This file lists what
was added on 19 November 2026, grouped the same way, with the reasons.
Every item is in the commit history. Commit hashes are in brackets.

Wording rules the report should keep (agreed with the site owner):
- Write "ASEAN Plus Three" in running text. "ASEAN+3" only appears inside quotes
  and official names (e.g. "ASEAN+3 Macroeconomic Research Office").
- Write "the Republic of Korea" in full.
- Facts about APT are quoted verbatim from ASEAN Secretariat (ASEC) documents,
  not paraphrased; diplomats are sensitive to wording.

## Corrections to the first draft

- **Colours:** the first draft described the gradient with approximate colours
  (#0B3D91 blue, maroon #98294B, crimson #C9234A, indigo middle). These were
  replaced by the **official ASEAN colours** (6f76905, 0d3de7d):
  - blue #2c59a7 (Pantone 286);
  - red #c73935 (Pantone Red 032);
  - yellow #fbf315 (Process Yellow).

  The background is now a smooth blue-to-red blend with a faint yellow corner
  (option "B4"). The site owner felt the old middle tones looked purple.
  The blend is calculated so it passes through no muddy or purple middle.
- **Not used:** a blurred ASEAN flag as the background was considered and
  rejected, because ASEAN flag protocol does not allow the flag to be used as
  decoration.
- **Ministerial count:** the homepage figure was counting posts, not meetings.
  It now counts **meetings** (275 instead of 282 posts). Several posts can
  belong to one meeting, such as a statement plus a press release. The label
  is "Ministerial Level Meetings" (b6eea35).

## 1. Content and credibility (new section suggested)

### Homepage statistics, sourced and in context (f867ade → 44f0209)

Each card is now a full sentence, so a visitor knows what the number means,
and each card names its own source:

| Figure | Sentence on the card | Source |
|---|---|---|
| US$44.4 billion | FDI into ASEAN from China, Japan and the Republic of Korea (2024, preliminary) | ASEC, Overview of APT Cooperation (14 Nov 2025) |
| US$1.2 trillion | Two-way trade in goods, ASEAN with the Plus Three (2024, preliminary) | ASEC, Overview of APT Cooperation |
| 2,277.5 million | People in the APT countries, 28.1% of the world (2024) | ASEANstats, Statistical Highlights 2025 |
| US$28.6 trillion | Combined GDP, 25.9% of the world economy (2024) | ASEANstats, Statistical Highlights 2025 |
| 26.7% | Share of ASEAN's 127.1 million visitor arrivals from the Plus Three (2024) | ASEANstats, Statistical Highlights 2025 |

"Preliminary" means ASEC marks the 2024 figure as provisional; it may be
revised when final data are published.

The counters (years of cooperation, summits, ministerial meetings) are
calculated from the site's own posts and update themselves.

### "Cooperation in Practice" section (e32835b → 5280fac)

This section answers the visitor's question: what does APT actually do?
- **Programmes:** four flagship programmes, each described in ASEC's own
  words and each with a "learn more" link:
  - Chiang Mai Initiative Multilateralisation (CMIM);
  - ASEAN+3 Macroeconomic Research Office (AMRO);
  - APT Emergency Rice Reserve (APTERR);
  - Asian Bond Markets Initiative (ABMI).
- **Years:** each programme shows the year it began operating, not the year
  it was agreed. For example, APTERR shows 2012, when its agreement entered
  into force.
- **Wording:** the intro and the statistics wording were also changed to
  verbatim ASEC text.
- **Member count:** no number of member states is stated, because Timor-Leste
  will appear in the next APT cooperation documents.

### "Latest Statements by Pillar" (6b2760a, 4c588f1, 55cee1f)

- **What it shows:** APT follows ASEAN's three pillars (Political-Security,
  Economic, Socio-Cultural). The homepage shows the newest statement under
  each pillar, with a short quote from it.
- **How it updates:** it updates itself when new statements are published;
  editors do nothing extra.
- **Placement:** it sits inside the Resources panel, beside Key Documents. On
  phones, only Key Documents starts open, so the page stays short.

### Hero (d075f76, 69a014a)

The title "ASEAN Plus Three" now has a line of flags under it:
- the ASEAN member states in alphabetical order with the ASEAN flag, as the
  ASEAN Flag Guidelines (para 16) require;
- then China, Japan and the Republic of Korea.

The flags are not labelled twice. "Plus Three" is in white.

## 2. Search: find statements by the words inside their PDFs

### New search experience (75959ea, bfaf24c)

- **Search box:** it now opens in a native browser dialog. That is lighter,
  works with the keyboard, and closes with Escape or by clicking outside.
- **Results page:** it has its own search box and a cleaner list.

### APT PDF Search plugin (0f02c14 → 2b6a343, extras/apt-pdf-search)

- **The problem:** most statements are PDFs, and the old search only matched
  page titles and text. A sentence from the 2025 Leaders' Statement could not
  be found.
- **The fix:** the plugin reads the text inside each attached PDF, on the
  site's own server; nothing is sent to outside services. Search then matches
  it, and results show the matching passage under "Found in the attached PDF".
- **Ranking:** results containing the whole phrase come first.
- **Privacy:** password-protected, private and Limited Access documents are
  never indexed.
- **Admin tools:** Tools → PDF search can index existing posts and check why a
  given statement is or isn't found.
- **Not done:** scanned PDFs (images, no text) are not searchable. OCR was
  tested and dropped as too heavy for the hosting.

### Offline Statement Search (a4ddb59, extras/apt-local-search)

- **What it is:** a single HTML page for staff to search a folder of statement
  PDFs on their own computer, including OneDrive-synced folders.
- **Offline:** it works without internet, and nothing is uploaded.
- **Results:** a result opens the PDF at the matching page.

## 3. Broken things fixed (add to the first draft's list)

- **Link checker plugin (e5246e0, extras/apt-link-checker):** lists, under
  Tools → Link checker, every:
  - broken link;
  - missing file;
  - fake PDF (a web page saved as .pdf);
  - malformed address in published content.

  It rechecks every week in the background and changes nothing by itself.
- **Summit tabs:** two summits were held in 2007, because the 2006 summit was
  postponed by a typhoon. That gave two identical "2007" tabs. Tabs now show
  the year and the summit number (be401cb).
- **News menu:** the News link pointed to /news, which doesn't exist. The theme
  now sends parent menu links that lead nowhere to their first working child
  (69dc39a). The menu URL itself should still be corrected in WordPress.
- **News page:** the old card design was replaced with the homepage's cards,
  so both pages use one shared component (5297f23, d1c8135).

## 4. Accessibility: full contrast check (19 November)

The first draft said every point of the gradient gave at least 5.5:1 for white
text. That figure was for the old gradient. With the official colours (B4) the
lowest measured is **4.5:1 on a 1920px-wide screen and 4.8:1 on standard
desktop and phone**. That still passes the 4.5:1 minimum; the report should use
these figures.

**What was checked**
- **Coverage:** 29 page types, each at desktop (1360px) and phone (390px)
  width, plus the open menu and the search box. That came to **10,730 pieces
  of text**.
- **Page types:**
  - home, news, statements lists, single statement, search results (with and
    without results), Agenda, 404;
  - 18 real inner pages: About, History, Overview, Key Documents, Resources,
    each statements category, Gallery, Contact, Limited Access and others.
- **Method:** each piece of text was compared with the actual pixels behind
  it on the rendered page, not with the colour the stylesheet intends. Text
  over the gradient, photos and tinted panels is therefore measured as people
  see it.
- **Standard:** WCAG 2.2 level AA. Normal text needs 4.5:1; large text (24px,
  or 18.7px bold) needs 3:1.
- **Worst case for photos:** text over photos (the homepage's latest-meeting
  photo, gallery captions) was also measured with every photo replaced by a
  plain white image.

**What failed, and the fixes**

| Problem | Before | After |
|---|---|---|
| Homepage latest-meeting photo: title's first line sat where the dark shading had barely started; over a bright photo it was unreadable | as low as 2.3:1 | at least 5.9:1, even on a white photo |
| Gallery captions: same problem, white text over the photo | as low as 1.6:1 on bright photos | caption band always dark; readable on a white photo |
| Grey dates and locations ("text-muted") on light grey panels, e.g. the Overview page | 4.3:1 | about 8:1 (theme's secondary text colour) |

**Result:** after the fixes, every text item on every page type passes, on
desktop and on phone. The only readings still flagged were measurement
artefacts, checked by eye:
- a saved copy of the live site lacked the current header styles;
- gallery photo tiles moved while being measured.

**Earlier measurements, still valid**
- **Hero text:** small hero text was made solid white; it went from 4.3:1 to
  6.4:1.
- **Secondary text on the gradient:** it was 92% white, which dropped to
  4.07:1 in the warm corner; it is now solid white.
- **Stat cards:** given a slight dark tint, fixing 4.02:1.
- **Tap targets:** 92 too-small targets brought to 0 across all audited pages
  (first draft).

**Limits:** this section covers contrast only. The full WCAG 2.2 AA audit is
section 5.

## 5. Accessibility: WCAG 2.2 AA audit (20 November)

**Scope and method**
- **Pages:** the same 29 page types as the contrast check, at desktop
  (1360px) and phone (390px) width.
- **Automated scan:** axe-core 4.10, the engine behind most accessibility
  checkers, set to every WCAG 2.0, 2.1 and 2.2 level A and AA rule.
- **Hands-on tests:**
  - **Keyboard:** about 150 Tab presses per page on home, statement, search,
    news and Resources, on desktop and phone. For each stop I checked whether
    focus is visible and whether the sticky header hides it.
  - **Widgets:** the menu, search box, carousel and accordions, operated with
    the keyboard alone.
  - **Reflow:** every page at 320px wide (the same as 400% zoom).
  - **Text spacing:** the WCAG 1.4.12 spacing applied, checking nothing gets cut
    off.
  - **Structure:** headings, landmarks, page titles, language and link wording.
- **Pass meaning:** a criterion is marked pass when the theme meets it. Where it
  depends on what editors put into pages, the table says so.

**Fixed during the audit (commit 0e4561b)**

| Criterion | Problem | Fix |
|---|---|---|
| 1.1.1 Non-text content | 7 theme images had no alt attribute, including the photos in the Overview page's "latest statement per area" lists (16 images on that page), the APT logo on Resources, the gallery preview and a loading spinner | Decorative images get an empty alt; the gallery preview copies its thumbnail's description; the spinner says "Loading" |
| 1.3.1 Info and relationships | WordPress galleries were coded as "definition lists", so screen readers announced them wrongly | The theme switches WordPress to modern gallery and caption markup |
| 1.4.1 Use of colour | Links inside paragraphs differed from the text only by colour (History, Areas of Cooperation, APTERR, Overview, Resources) | Links in running text are underlined |
| 1.4.12 Text spacing | At 320px with wider spacing, the homepage title (then "ASEAN Plus Three at a Glance", now "About ASEAN Plus Three") could not wrap and pushed the page sideways | The title may wrap on narrow screens |
| 2.4.7 Focus visible | Bootstrap buttons (topic tags on statements, "Download the Overview" buttons) hid the keyboard focus ring | Focus ring restored on buttons, page numbers and tabs |

After the fixes, the automated scan finds **no violations on any theme-built
page type**, at either width. It still reports two items, both on saved copies
of live pages that predate the fixes; they disappear once the new theme
renders them.

**Results by WCAG 2.2 criterion (levels A and AA)**

*Perceivable*

| Criterion | Result |
|---|---|
| 1.1.1 Non-text content | Pass (theme). Editors must still describe meaningful images they add to pages |
| 1.2.1–1.2.5 Audio and video | Not applicable to the theme. Any video embedded in a page needs captions |
| 1.3.1 Info and relationships | Pass, with a recommendation: some older templates and pages skip heading levels (e.g. h1 straight to h4/h5). WCAG doesn't strictly forbid this, but it makes screen-reader navigation harder |
| 1.3.2 Meaningful sequence | Pass |
| 1.3.3 Sensory characteristics | Pass (theme) |
| 1.3.4 Orientation | Pass: works in portrait and landscape |
| 1.3.5 Identify input purpose | Not verified: the Limited Access login form comes from WordPress |
| 1.4.1 Use of colour | Pass after fix |
| 1.4.2 Audio control | Not applicable |
| 1.4.3 Contrast (minimum) | Pass (section 4) |
| 1.4.4 Resize text | Pass |
| 1.4.5 Images of text | Pass (the emblem is a logo, which is exempt) |
| 1.4.10 Reflow | Pass: no sideways scrolling at 320px on any page type |
| 1.4.11 Non-text contrast | Pass for focus rings (dark ring with white halo, ≥3:1 on any background); form field borders not measured |
| 1.4.12 Text spacing | Pass after fix |
| 1.4.13 Content on hover or focus | Pass: nothing appears on hover except the browser's own tooltips |

*Operable*

| Criterion | Result |
|---|---|
| 2.1.1 Keyboard | Pass: menu, search, carousel, accordions and all links work with the keyboard |
| 2.1.2 No keyboard trap | Pass: the menu and search close with Escape and return focus to their button |
| 2.1.4 Character key shortcuts | Not applicable |
| 2.2.1 Timing adjustable | Not applicable |
| 2.2.2 Pause, stop, hide | Pass: the news carousel never moves by itself. The background drifts very slowly (28-second cycle) and stops when "reduce motion" is on. It is decoration, not information, but a stricter reading would want it static; see recommendations |
| 2.3.1 Three flashes | Pass: nothing flashes |
| 2.4.1 Bypass blocks | Pass: "Skip to content" is the first Tab stop on every page |
| 2.4.2 Page titled | Pass |
| 2.4.3 Focus order | Pass |
| 2.4.4 Link purpose (in context) | Pass: every "Read more" says what it is about to screen readers |
| 2.4.5 Multiple ways | Pass: menu, search and section pages |
| 2.4.6 Headings and labels | Pass |
| 2.4.7 Focus visible | Pass after fix |
| 2.4.11 Focus not obscured (minimum) | Pass: across about 870 focus stops, the sticky header never covered a focused item |
| 2.5.1 Pointer gestures | Pass: swiping the carousel also has buttons |
| 2.5.2 Pointer cancellation | Pass |
| 2.5.3 Label in name | Pass |
| 2.5.4 Motion actuation | Not applicable |
| 2.5.7 Dragging movements | Pass: nothing requires dragging |
| 2.5.8 Target size (minimum) | Pass (automated rule plus the earlier tap-target audit: 92 → 0) |

*Understandable*

| Criterion | Result |
|---|---|
| 3.1.1 Language of page | Pass (English) |
| 3.1.2 Language of parts | Content: text in other languages inside pages needs its language marked by editors |
| 3.2.1 On focus / 3.2.2 On input | Pass: nothing changes until the user acts (e.g. search runs on its button) |
| 3.2.3 Consistent navigation / 3.2.4 Consistent identification | Pass: same header, menu and footer on every page |
| 3.2.6 Consistent help | Pass: contact details in the same footer on every page |
| 3.3.1–3.3.3 Errors and labels | Pass for search. The Limited Access login's error message comes from WordPress and was not tested |
| 3.3.4 Error prevention | Not applicable (no legal or financial transactions) |
| 3.3.7 Redundant entry | Not applicable |
| 3.3.8 Accessible authentication | Not verified (WordPress login; password managers are not blocked) |

*Robust*

| Criterion | Result |
|---|---|
| 4.1.2 Name, role, value | Pass: no automated violations; buttons and dialogs have names |
| 4.1.3 Status messages | Not applicable to the theme as it stands |

**Summary:** of the 55 level A and AA criteria:
- **Pass:** 40, five of them after this audit's fixes;
- **Not applicable:** 12, mostly audio/video and transactions;
- **Depends on editors:** 1 (3.1.2, language of parts). Image descriptions
  under 1.1.1 also rely on editors;
- **Not verified:** 2 (1.3.5 and 3.3.8), both on the WordPress login form.
  Its error messages under 3.3.1 were not tested either;
- **Failing in the theme:** 0 known.

**Recommendations (not failures)**
1. Make the background drift static, to remove any doubt under 2.2.2.
2. Fix heading-level skips in the older templates: statements lists, Agenda
   and the statement sidebar.
3. Mark links that open a new tab (about 8 per page, mostly the footer's
   social links), e.g. with hidden text "opens in a new tab".
4. Editors: describe meaningful images, and mark text in other languages.

**Limits**
- This is a thorough technical audit, not a certified conformance statement.
- No real screen reader (NVDA, JAWS, VoiceOver) was used; that needs a person
  and about 30 minutes on the main pages.
- The statement PDFs themselves were not audited (tagging, reading order).
- The live site was not tested directly; the theme was tested as rendered
  here, plus saved copies of live pages.

## 6. Performance: original theme vs apt-v2 (20 November)

**Method**
- **Reports:** the full Lighthouse HTML reports are in `docs/audits/lighthouse/`.
- **Tool:** Lighthouse 12, Google's standard page-speed test, on its default
  "mobile phone on a 4G connection" simulation. Each page was run 3 times and
  the median taken.
- **Before:** saved copies of the live site, which runs the original theme,
  served with the original theme's files (commit 13edb04).
- **After:** the same page types rendered by apt-v2.
- **Same conditions for both:** both were served from the same local server
  with the same compression. Content photos, plugins and analytics were left
  out of both, so the comparison measures the theme only.

**Results**

| Page | Score (0–100) | Main content appears (LCP) | First paint (FCP) | Page frozen by scripts (TBT) | Data transferred |
|---|---|---|---|---|---|
| Home | 83 → **99** | 2.5 s → **1.9 s** | 2.1 s → **1.7 s** | 500 ms → **0 ms** | 295 KB → **185 KB** (−37%) |
| Statement | 95 → 98 | 2.4 s → 2.0 s | 2.3 s → 1.9 s | 0 → 0 | 198 KB → 176 KB (−11%) |
| Statements list | 95 → 99 | 2.4 s → 1.9 s | 2.3 s → 1.8 s | 0 → 28 ms | 174 KB → 154 KB (−11%) |
| Search results | 95 → 99 | 2.4 s → 1.9 s | 2.3 s → 1.7 s | 0 → 22 ms | 194 KB → 154 KB (−21%) |
| News | 95 → 99 | 2.4 s → 1.9 s | 2.3 s → 1.8 s | 0 → 22 ms | 186 KB → 154 KB (−17%) |
| Page not found | 95 → 99 | 2.4 s → 1.8 s | 2.3 s → 1.7 s | 0 → 0 | 172 KB → 152 KB (−12%) |

Layout shift (CLS) was 0 before and after on every page.

**Why it's faster**
- **Fonts:** the original loaded four web font families (Inter, Sora,
  Fjalla One, Source Sans Pro), 127 KB on the homepage. apt-v2 loads one
  (Outfit), 33 KB. Two leftover font imports were removed during this audit.
- **No more frozen homepage:** the original homepage blocked the phone for
  half a second while its carousel library (Owl Carousel) and scripts started
  up. The new carousel uses the browser's own scrolling: 0 ms.
- **Smaller pages:** the original homepage's HTML carried about 58 KB of
  inline styles and 116 KB of inline SVG drawings. Its compressed HTML was
  65 KB; the new one is 10 KB. Part of that difference is sample versus live
  content, so the report should lean on the timing figures.
- **Theme files on the homepage** (styles, scripts, fonts): 229 KB → 144 KB
  (−37%).

**Fair caveats**
- These are lab measurements, not real visitors on the real server. Real
  load times also depend on the hosting, the firewall and the content photos,
  which are the same for both themes.
- apt-v2 sends more CSS (48 KB vs 32 KB): it still loads the old Bootstrap
  stylesheet for the older inner templates as well as its own design system.
  The homepage also loads 15 small flag images (about 22 KB in total).
- **Next steps:**
  - Remove Bootstrap once the remaining older templates are rebuilt.
  - Combine the flags into one file.
  - Measure real visitors with Google Search Console's Core Web Vitals report
    after launch.

## 7. SEO review and fixes (20 November)

**How the site handles SEO:** Yoast SEO (v28.8) on the live site already
provides canonical URLs, robots rules (search results and the 404 page
correctly "noindex"), breadcrumbs and structured data (Organization, WebSite,
Article, CollectionPage). The review checked how the theme works with it,
using the theme code and saved copies of 24 live pages.

**Problems found in the theme, all fixed (commit 6484ab0)**

| Problem | Effect | Fix |
|---|---|---|
| A hand-written "AI meta tags" block added a second robots tag to every post and page | It contradicted Yoast's own robots rules, including its `max-snippet` setting | Removed; Yoast alone sets robots rules |
| The same block gave one hard-coded description to every page mentioning "ASEAN Plus Three" | Several pages had two descriptions, and many shared the same text | Removed |
| It printed made-up tags (`faq-schema`, `citation-style`, `source-credibility`, `coverage`) | No search engine reads them; they only added noise | Removed |
| An extra "AboutPage" structured-data block | It said "Korea" rather than "the Republic of Korea", and listed China and Japan as "members of" ASEAN | Removed; Yoast's structured data remains |
| The theme hard-coded the page title | The homepage title was just "ASEAN Plus Three"; every other title gained an extra "APT " prefix, e.g. "APT CPR+3 Archives - ASEAN Plus Three" | Titles now come from Yoast |
| Most pages had no meta description | Google picks its own snippet, often menu text | Automatic description where none is written: home (ASEC's first sentence, verbatim), statements (title, place, date), pages (opening text), categories (their description). A description written in Yoast always takes priority |
| No Open Graph tags on most pages | Links shared on WhatsApp, LinkedIn or X showed no image or summary | The theme adds link-preview tags when Yoast's are off, with a default 1200×630 image in the official colours (`img/share/`) |
| Heading levels skipped (h1 straight to h4 on statement lists, h1 to h3 on the Agenda) | Weaker page outline for search engines and screen readers | Fixed, with no visual change |

**For the site owner, in WordPress (not theme code)**
1. **Yoast settings:**
   - **Social previews:** turn on Yoast's Open Graph (Settings → Site
     features → Open Graph data), so editors can set images per post. The
     theme's fallback covers it until then.
   - **Organization:** fill in the logo and the official social profiles
     (Settings → Site representation).
   - **Tagline:** set a real tagline. It is currently the same as the site
     name ("ASEAN Plus Three").
2. **Pages that probably shouldn't be public or indexed:**
   - Test and leftover pages: `testing-page`, `5417-2`, `my-front-page`,
     `tickets-checkout`, `tickets-order`, `no-access`.
   - Login-related pages: `login_ujv5g` and `limited-access-documents-3-donotcache…`.
     **Security, not only SEO:** they reveal the login address.
3. **Duplicates competing with each other in search:**
   - `gallery`, `gallery2` and `gallery3`;
   - `events-2` and `events-3`;
   - `contact` and `contact-us`;
   - `aptwg` and `aptwg-2`.

   Keep one of each and redirect the others; Yoast Premium or the free
   "Redirection" plugin can do this.
4. **Page titles:** the Gallery page is titled "Gallery3" and the News page
   "NEWS"; rename them in WordPress.
5. **Search Console:** confirm the sitemap (`/sitemap_index.xml`) is submitted,
   and watch the Core Web Vitals report after launch.

**Not tested:** the live robots.txt and sitemap could not be fetched because of
the firewall; the theme changes were tested in isolation with stand-in
WordPress functions.

## 8. Security

- **Limited Access login (d650750):**
  - a dead login handler was removed;
  - an **open redirect** was closed. Before, the failed-login redirect could
    send visitors to any outside site.
- Found and reported to the site owner, to fix in WordPress (not theme code):
  - a public page that exposes the login form;
  - a single shared Limited Access account, whose password should be
    changed;
  - the Limited Access files themselves are not protected: anyone with the
    link can open them.
- **Firewall pacing:** both plugins run their bulk jobs in short, paced
  batches, because fast bursts tripped the hosting firewall (GoDaddy/Sucuri)
  and blocked the server (7932a2b).

## 9. Maintainability

- **Theme identity:** the theme is now named **apt-v2**, version 2.1.0, with a
  fresh screenshot (63e4ed6).
- **README:** it documents the homepage sections, the content rules, the
  official palette, the layout, search and all three extras.
- **Code cleanup (20 November, d7660f4 and fbc5b93):**
  - **Unused files:** 3.5 MB of images left over from the original theme
    (newslogo, newsthumb, front-carousel), and 18 unused Bootstrap source
    files and maps.
  - **Dead styles:** 69 style rules for elements that no longer exist
    anywhere, plus commented-out styles. `style.css` went from 1,293 to 953
    lines.
  - **Commented-out code:** about 420 lines of old PHP, plus three unused
    functions.
  - **Size:** the theme is now about 3.2 MB without documentation, down from
    about 9 MB.
  - **How it was checked:**
    - every PHP file passes a syntax check;
    - a token comparison proves the executable PHP code is unchanged;
    - a pixel-by-pixel comparison of 30 page states, at desktop and phone,
      shows no visual change.

## Still open (for the "next steps" section)

- Correct the News menu URL in WordPress (/news → /news-2/).
- Unpublish the public login page and change the Limited Access password.
- Protect the Limited Access files themselves.
- Confirm para 17 of the ASEAN Flag Guidelines, which covers flags shown with
  Dialogue Partners. The flag line-up follows para 16 only.
- Test the latest theme on staging before it goes live. A crash on 19 November
  was caused by a misplaced line in functions.php. It was reverted and fixed
  the same day (4138cd6), which shows why changes should go through staging
  first.
- Add the mobile share of visitors from Google Analytics. It is still the
  strongest single argument for the redesign.
