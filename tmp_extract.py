from pathlib import Path
import re
path = Path('templates/admin/events.html.twig')
text = path.read_text('utf-8')
parts = re.split(r'<script[^>]*>', text, maxsplit=1)
if len(parts) < 2:
    raise SystemExit('no script tag')
code = parts[1].split('</script>', 1)[0]
lines = code.splitlines()
for i in range(max(0, 600-5), min(len(lines), 600+5)):
    print(f'{i+1}: {lines[i]!r}')
