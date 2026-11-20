# Lighthouse reports: original theme vs apt-v2 (20 November 2026)

Open any `.html` file in a browser. Each pair compares the same page type:
`-1-original` is the theme the live site runs (commit 13edb04 of the akseski
repository); `-2-apt-v2` is this theme.

How they were made
- Lighthouse 12.2.1, performance category, default mobile simulation
  (mid-range phone, throttled 4G).
- Three runs per page; each file is the median run (by Largest Contentful Paint).
- Both themes were served from the same local test server with the same gzip
  compression, which is why the address at the top reads `localhost:8401/before/…`
  or `/after/…`. The site itself was not tested, to avoid the website firewall.
- "Before" pages are saved copies of the live site; "after" pages are rendered
  by apt-v2 with sample content.
- Content photos, plugins (social sharing, events, search filter) and analytics
  were left out of **both**, so the comparison measures the theme only. Real
  page loads also include those, equally for both themes.

| Page | Original | apt-v2 |
|---|---|---|
| Home | 83, LCP 2.5 s | 99, LCP 1.9 s |
| Statement | 95, LCP 2.4 s | 98, LCP 2.0 s |
| Statements list | 95, LCP 2.4 s | 99, LCP 1.9 s |
| Search results | 95, LCP 2.4 s | 99, LCP 1.9 s |
| News | 95, LCP 2.4 s | 99, LCP 1.9 s |
| Page not found | 95, LCP 2.4 s | 99, LCP 1.8 s |
