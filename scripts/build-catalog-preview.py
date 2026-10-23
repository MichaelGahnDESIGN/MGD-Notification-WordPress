"""Installierbaren Katalog-Kandidaten ohne Entwicklungs- und Zugangsdaten bauen."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
from hashlib import sha256
import json

root = Path(__file__).resolve().parents[1]
config = json.loads((root / 'tests/catalog-config.json').read_text())
runtime = root / config['runtime']
entries = ['assets', 'includes', 'views', config['main'], 'README.md', 'CHANGELOG.md', 'SECURITY.md', 'LICENSE'] if runtime == root else ['.']
allowed = {'.php', '.css', '.js', '.json', '.svg', '.png', '.gif', '.webp', '.jpg', '.jpeg', '.txt', '.md', '.po', '.mo', '.pot'}
output = root / 'artifacts/catalog-preview'
output.mkdir(parents=True, exist_ok=True)
archive = output / (config['slug'] + '-' + config['version'] + '.zip')
files = []
for name in entries:
    path = runtime / name
    if not path.exists():
        continue
    files.extend(path.rglob('*') if path.is_dir() else [path])
with ZipFile(archive, 'w', ZIP_DEFLATED) as bundle:
    for file in sorted(files):
        if not file.is_file():
            continue
        relative = file.relative_to(runtime)
        assert not file.is_symlink() and not any(part.startswith('.') for part in relative.parts), file
        assert file.suffix.lower() in allowed or file.name == 'LICENSE', file
        info = ZipInfo(config['slug'] + '/' + relative.as_posix(), date_time=(2026, 1, 1, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        bundle.writestr(info, file.read_bytes())
with ZipFile(archive) as bundle:
    assert bundle.testzip() is None
    assert config['slug'] + '/includes/plugin-catalog/view.php' in bundle.namelist()
    assert config['slug'] + '/assets/plugin-catalog/catalog.js' in bundle.namelist()
(output / (archive.name + '.sha256')).write_text(sha256(archive.read_bytes()).hexdigest() + '  ' + archive.name + '\n')
print(archive)
