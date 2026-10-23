from pathlib import Path
required = [Path('README.md'), Path('SECURITY.md'), Path('mgd-notification-center/mgd-notification-center.php')]
missing = [str(path) for path in required if not path.is_file()]
if missing:
    raise SystemExit('Fehlende Dokumentation oder Einstiegspunkt: ' + ', '.join(missing))
print('Dokumentation geprüft.')
