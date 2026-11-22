"""Extract text from every PDF under reference/ into reference/extracted/*.txt.

Usage (from the plugin folder):  python tools/extract_pdfs.py reference reference/extracted
Requires: pip install pdfplumber
"""
import sys, re, pathlib, pdfplumber
src, out = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
out.mkdir(exist_ok=True)
for p in sorted(src.rglob('*.pdf')):
    with pdfplumber.open(p) as pdf:
        t = '\n'.join((pg.extract_text(x_tolerance=2, y_tolerance=4) or '') for pg in pdf.pages)
    t = t.replace('m\ufffd', 'm²').replace('\ufffdC', '°C').replace('\ufffd', "'")
    t = re.sub(r'\(cid:\d+\)', '', t)
    name = re.sub(r'^\d+-', '', p.stem)
    (out / f'{p.parent.name} - {name}.txt').write_text(f'SOURCE: {p.relative_to(src)}\n\n{t}', encoding='utf-8')
    print(len(t), name)
