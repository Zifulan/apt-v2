# APT PDF Search (WordPress plugin)

Lets the site search find statements by the text **inside their attached
PDFs**. Results are still the statement pages; when the match is only in the
PDF, the theme shows the passage under "Found in the attached PDF:".

It is a separate plugin, not part of the theme, so search keeps working if the
theme changes.

## Install

1. Zip this folder (`apt-pdf-search/`, including `vendor/`).
2. WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, activate.
3. **Tools → PDF search → Index PDFs** to index existing content (it runs in
   small batches and continues by itself; keep the tab open until "Done").
   With WP-CLI instead: `wp apt-pdf-search index`.

After that, posts are indexed automatically whenever they are saved. Use
**Re-read all PDFs** (or `wp apt-pdf-search index --force`) after replacing
PDF files under the same name.

## What is (and isn't) indexed

- Only PDFs stored in this site's uploads folder and linked from the post's
  content. PDFs on other sites are ignored.
- Only **published** posts and pages **without a password**. Private, draft
  and password-protected content, children of protected pages, and anything
  whose slug, title, category, parent page or PDF path contains "limited" are
  skipped, and their stored text is removed. This keeps the Limited Access
  Documents out of search. Change the keyword list with the
  `apt_pdf_search_excluded_keywords` filter.
- Files are read on this server; nothing is sent to an outside service.
- Scanned PDFs (images of text) have no text to read and would need OCR.
  Tools → PDF search lists them.

Tested against the 203 public statement PDFs on the live site (November
2026): 193 readable (about 13,000 characters each, under 10 seconds for all),
8 scanned images, 2 broken links (see below).

Text is extracted with [smalot/pdfparser](https://github.com/smalot/pdfparser)
(LGPL-3.0, bundled in `vendor/`). If the server has poppler's `pdftotext`
and allows `exec`, it is used as a fallback for PDFs the parser can't read.

## Broken PDF links found on the live site

- 4th APT EMM Joint Statement: the link URL contains escaped HTML
  (`…4<sup&gt;th</sup&gt;…`) and downloads an empty file.
- Overview of APT Cooperation, 6 July 2022
  (`/wp-content/uploads/2022/07/Overview-of-APT-Cooperation-6-July-2022.pdf`):
  returns a web page instead of the PDF.

## Uninstall

Deleting the plugin removes the stored text (`_apt_pdf_text`,
`_apt_pdf_state` post meta).
