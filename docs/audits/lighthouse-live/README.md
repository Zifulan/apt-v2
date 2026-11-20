# Lighthouse on the live site

Reports run by the site owner in Chrome DevTools on https://aseanplusthree.asean.org/
(theme installed as `apt-new`, an earlier build of apt-v2). Open the `.html`
files in a browser; the `.json` files are the raw reports.

| Run | Performance | Accessibility | Best practices | SEO | LCP |
|---|---|---|---|---|---|
| 2026-11-20 desktop | 95 | 87 | 100 | 100 | 1.2 s |
| 2026-11-20 mobile | 70 | 100 | 100 | 100 | 5.7 s |

Fixed in the theme after these runs (commit after 2026-11-20):
- Bootstrap's script and the theme script load at the end of the page; the
  icon font loads without blocking the first paint; the social-sharing
  plugin's files are not loaded on the home page; early connection to the
  font server.
- Hero and news images: responsive sizes (phones no longer download the
  1024px image); statements without a photo get a small category cover.
- Accessibility: Mechanisms tabs (roles, ids, selected state), heading
  order, logo link name.

Outside the theme (hosting): images cached for 1 day only, "no-store" on
pages (disables instant back/forward), ~450 ms server response.
