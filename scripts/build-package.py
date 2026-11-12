# Paket bauen: Version aus dem Plugin-Kopf, feste Rechte (Ordner 755, Dateien 644)
import re, stat, zipfile
from pathlib import Path
root = Path('mgd-notification-center')
version = re.search(r'^ \* Version:\s*([0-9.]+)', (root / 'mgd-notification-center.php').read_text(encoding='utf-8'), re.M).group(1)
target = Path('dist') / f'mgd-notification-center-{version}.zip'
target.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(target, 'w') as archive:
    for path in sorted(root.rglob('*')):
        if path.is_symlink() or path.suffix == '.log' or any(part.startswith('.') for part in path.relative_to(root).parts):
            continue
        directory = path.is_dir()
        entry = zipfile.ZipInfo(path.as_posix() + ('/' if directory else ''), (2026, 1, 1, 0, 0, 0))
        entry.create_system = 3
        entry.external_attr = ((stat.S_IFDIR | 0o755) if directory else (stat.S_IFREG | 0o644)) << 16
        entry.compress_type = zipfile.ZIP_STORED if directory else zipfile.ZIP_DEFLATED
        archive.writestr(entry, b'' if directory else path.read_bytes())
print(target)
