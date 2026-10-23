from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
root = Path('mgd-notification-center')
target = Path('dist') / 'mgd-notification-center-0.1.1.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(root.rglob('*')):
        if path.is_file() and path.suffix not in {'.log'}:
            archive.write(path, path.as_posix())
print(target)
