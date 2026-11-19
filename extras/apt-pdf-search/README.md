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

After that, posts are indexed automatically whenever they are saved. The
newest posts are indexed first, and the page warns you if a run stopped early. Use
**Re-read all PDFs** (or `wp apt-pdf-search index --force`) after replacing
PDF files under the same name.

## A statement isn't found?

**Tools → PDF search → Check a statement**: paste the statement's address and
a phrase from its PDF. It shows, step by step, whether the post can be indexed
(and why not), each PDF link and whether it was read, the stored text, whether
the phrase is in it, and whether a real search returns the post, with a button
to re-index that post. If the check says the post *is* found but the site
doesn't show it, clear the site's cache (Sucuri, caching plugins).

## What is (and isn't) indexed

- Only PDFs stored in this site's uploads folder that the post refers to,
  however they are attached: plain links, shortcodes such as
  `[pdf-embedder url="…"]`, PDF/file blocks (by address or attachment ID),
  and PDFs uploaded to the post in the Media Library. PDFs on other sites are
  ignored.
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
