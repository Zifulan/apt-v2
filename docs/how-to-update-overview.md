# How to update the Overview of ASEAN Plus Three Cooperation

The page: <https://aseanplusthree.asean.org/overview-of-asean-plus-three-cooperation/>

The same steps appear in WordPress, in a box called **How to update this page**.
It is in the sidebar on the right (Page tab, near the bottom) when you edit that page.

## Steps

1. **Ask for the Word file** of the new Overview, not only the PDF. Text pasted
   from Word keeps its paragraphs and links; text copied from a PDF does not.
2. **Keep the text exactly as written** by the ASEAN Secretariat. Do not reword
   or correct it.
3. **Replace the paragraphs** one section at a time. Keep the four section
   headings worded as they are now:
   - Political-Security Cooperation
   - Economic and Finance Cooperation
   - Socio-Cultural Cooperation
   - Track 1.5 and Track 2 Cooperation

   The site finds the sections by these words.
4. **Leave the shortcodes** (text in square brackets, such as
   `[akseki_get_recent_posts …]` and `[akseki_cat_children …]`) where they are.
   They show the Summit and mechanism cards and update by themselves.
5. **Add the links** to the related statements: search the site for the
   meeting, copy its address, select the words in the text and add the link.
6. **New PDF:** upload it under Media, then change the link of the
   *Download Overview in PDF* button to the new file.
7. **Preview**, check each section shortcut at the top jumps to its section,
   then **Update**.

If something looks wrong, use **Revisions** (in the Page panel) to go back to
the previous version.

## What updates by itself

- **Summit cards:** always the latest three APT Summits.
- **Mechanism cards:** each shows the latest photo from its meetings.
- **Section shortcuts and layout:** the theme recognises the four section
  headings by their wording and gives them the IDs that the shortcuts point to
  (`subsectionPolitical`, `subsectionEconomic`, `subsectionSocio`,
  `subsectionTracks`). This works at any heading level, so headings pasted
  from Word don't need any extra settings.

## For developers

- **Section IDs:** `apt_overview_section_ids()` in `functions.php` (a
  `the_content` filter, Overview page only, found by its slug).
- **Help box:** `apt_overview_help_box()` in `functions.php`. If you change
  the steps here, change them there too.
- **Layout:** the "Overview page layout" block in
  `assets/css/apt-design.css`.
