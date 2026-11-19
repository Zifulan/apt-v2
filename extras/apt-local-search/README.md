# APT Statement Search (local, offline)

One HTML file, `apt-statement-search.html`, that searches inside the PDFs in a
folder **on your computer**, including OneDrive and SharePoint folders synced
to it. Nothing is installed and nothing is uploaded: the PDFs are read and
indexed inside your browser, so it is safe for restricted documents.

## Use

1. Open `apt-statement-search.html` in **Chrome or Edge** (double-click it).
2. **Choose folder** and pick the folder with the PDFs (subfolders included).
   The first scan takes a few seconds to a few minutes.
3. Type words or a "phrase". Each result shows the passage; click it to open
   the PDF **at that page**.
4. Next time, the index is already there. Chrome and Edge remember the folder
   (allow access when asked); **Rescan** reads only new or changed PDFs and
   drops deleted ones.

Firefox and Safari work too, but you choose the folder each time you open the
page.

### OneDrive / SharePoint

Works with folders synced to the computer (the OneDrive app, or "Sync" /
"Add shortcut to My files" in SharePoint). Cloud-only files (☁️) download as
they are read, so the first scan needs internet; right-click the folder →
**Always keep on this device** for a fast, offline scan. OneDrive files that
are only on the web (not synced) can't be read by a web page.

### Search

All words must appear. "Quotes" for an exact phrase, `-word` to exclude a
word. Results with the whole phrase come first. Filter by year (from the
folder or file name, else the document date) and by top-level folder.

Scanned PDFs (images of text), password-protected and damaged files are
listed under "PDFs with no searchable text".

## Build

`src/app.html` is the page; `build.py` bundles pdf.js (`vendor/`, Mozilla,
Apache-2.0, see `vendor/pdfjs-LICENSE`) into it so the file works offline:

```
python3 build.py
```

## Tested (November 2026)

With 20 real APT statements in subfolders, opened from disk (`file://`) in
Chromium: 20 PDFs (172 pages) indexed in about 1 s; "WELCOME constructive
engagement" finds the 2025 Leaders' Statement first, at page 2, and opens it
there; phrase, exclude, year and folder filters; the index survives a reload;
rescan reads only new files; scanned and broken PDFs are reported. Extracted
text matches `pdftotext` (99.6% of distinct words; the rest are hyphenated
words that pdftotext joins). Remembering the folder after a reload could not
be tested in the automated browser; check it once in Chrome or Edge.
