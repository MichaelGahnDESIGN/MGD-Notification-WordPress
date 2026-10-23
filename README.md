# MGD Notification Center

Ein datensparsames Notification Center für WordPress ab 6.5 und PHP ab 8.1.
Es zeigt öffentliche Neuigkeiten sowie persönliche, serverseitig geschützte Meldungen
für angemeldete Konten. Das Plugin lädt keine externen Schriften, Skripte oder Analyse-Dienste.

## Funktionen

- öffentliche Meldungen für neue veröffentlichte Inhalte und bewusst angekündigte Aktualisierungen
- persönliche Meldungen für WooCommerce-Bestellstatus und gezielte Admin-Nachrichten
- Glocke mit Ungelesen-Zähler im Menü, Footer, als Floating-Element oder via `[mgd_notification_center]`
- Entwurf, Planung, Veröffentlichung und Archivierung im WordPress-Backend
- Empfängertrennung und Lesestatus ausschließlich auf dem Server

## Installation und Prüfung

1. Den Ordner `mgd-notification-center` als ZIP paketieren und in WordPress installieren.
2. Unter **Notification Center → Einstellungen** die gewünschte Platzierung einstellen.
3. Unter **Notification Center → Meldungen** öffentliche oder persönliche Meldungen erstellen.
4. Vor einer produktiven Installation Datenbank und Dateien sichern und die Ausgabe ausgeloggt sowie mit einem Testkonto prüfen.

```bash
find mgd-notification-center -name '*.php' -exec php -l {} \;
node --check mgd-notification-center/assets/frontend.js
python3 scripts/check-docs.py
python3 scripts/build-package.py
```

Persönliche Meldungen benötigen ein WordPress-Benutzerkonto. Gastbestellungen erzeugen absichtlich keine persönliche Meldung, weil keine sichere Konto-Zuordnung besteht.
Öffentliches und persönliches Notification Center für WordPress
