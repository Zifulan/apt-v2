#!/usr/bin/env python3
"""Bundle src/app.html + vendor/pdf.js into one offline file: apt-statement-search.html"""
import pathlib, re
root = pathlib.Path(__file__).parent
html = (root / 'src/app.html').read_text(encoding='utf-8')
def safe(js):  # a literal "</script" would end the inline script early
    return re.sub(r'</(script)', r'<\\/\1', js, flags=re.I)
lib = (root / 'vendor/pdf.min.js').read_text(encoding='utf-8')
worker = (root / 'vendor/pdf.worker.min.js').read_text(encoding='utf-8')
html = html.replace('<!--PDFJS_LIB-->', '<script>/* pdf.js 3.11.174, Apache-2.0, Mozilla */\n' + safe(lib) + '\n</script>', 1)
html = html.replace('<!--PDFJS_WORKER-->', safe(worker), 1)
out = root / 'apt-statement-search.html'
out.write_text(html, encoding='utf-8')
print(f'{out.name}: {out.stat().st_size/1024/1024:.2f} MB')
