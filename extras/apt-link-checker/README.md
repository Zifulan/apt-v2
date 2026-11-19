# APT Link Checker (WordPress plugin)

Finds broken links, missing files and broken PDFs in published posts and
pages, and lists them for administrators under **Tools → Link checker**, with
links to edit each affected post. It never changes content.

## Install

1. Zip this folder (`apt-link-checker/`).
2. **Plugins → Add New → Upload Plugin**, choose the zip, activate.
3. **Tools → Link checker → Check all links**. It works through the site in
   small batches and continues by itself; keep the tab open until "Done".

After that it re-checks in the background (a few posts every hour, each
address at most once a week), so the list stays current.

## What it checks

| Link | How | Reported as broken when |
|---|---|---|
| Files in this site's uploads (PDFs, images) | On disk, no web request | missing, empty, or a ".pdf" that isn't a PDF |
| Other pages on this site | Looked up in WordPress (posts, pages, categories, archives); never a web request to the site itself | not published (draft, private, trashed); unknown addresses go to "Check by hand" |
| Other websites | Web request (HEAD, then GET) | 404 and similar |
| Any address | Pattern check | HTML or spaces inside the address |

"Check by hand" lists addresses that didn't answer, answered 401/403/429
(often a bot block, e.g. Flickr) or a server error: they may work in a
browser.

Links are read from `href`, `src` and shortcode `url="…"` attributes, including
block settings. Anchors, `mailto:`, `tel:` and `javascript:` links are skipped.

On the live site (November 2026 crawl) that is about 970 addresses across
834 posts and pages: 499 uploaded files, 422 links to the site's own pages,
51 links to other websites. Known problems it will report: the 4th APT EMM
Joint Statement link (HTML inside the address) and the Overview of APT
Cooperation of 6 July 2022 (a web page saved as .pdf).

## Website firewall

Bulk runs are paced for the GoDaddy/Sucuri website firewall: each admin
request works for up to 20 seconds, then the page pauses 3 seconds before
continuing. If the firewall still blocks you ("temporarily blocked by our
IDS"), wait for the block to lift or allowlist your IP in the firewall
settings before running it again.

## Uninstall

Deleting the plugin removes its stored results and background schedule.
