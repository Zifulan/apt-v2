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

**Limits:** this is a contrast check, not a full WCAG audit. Screen reader use,
form labels and link wording were reviewed during the redesign but not audited
page by page. A full audit would need a specialist or assistive-technology
testing.

## 5. Security

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

## 6. Maintainability

- **Theme identity:** the theme is now named **apt-v2**, version 2.1.0, with a
  fresh screenshot (63e4ed6).
- **README:** it documents the homepage sections, the content rules, the
  official palette, the layout, search and all three extras.

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
