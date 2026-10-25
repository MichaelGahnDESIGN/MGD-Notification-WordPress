<div align="center">

<img src="docs/images/notification-center-title.svg" alt="MGD Notification Center – Benachrichtigungen für WordPress" width="100%">

# MGD Notification Center

**Öffentliche Neuigkeiten und persönliche Benachrichtigungen für WordPress.**

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-4CAF50)](mgd-notification-center/LICENSE)
[![Release](https://img.shields.io/github/v/release/MichaelGahnDESIGN/MGD-Notification-WordPress?label=Testversion)](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/releases/latest)

[Testversion herunterladen](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/releases/latest) · [Dokumentation](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki) · [Sicherheitsrichtlinie](SECURITY.md)

</div>

---

MGD Notification Center bringt eine zugängliche Glocke in WordPress. Sie zeigt neue Inhalte, bewusst angekündigte Aktualisierungen und persönliche Meldungen für angemeldete Konten. Alle Daten bleiben auf dem eigenen WordPress-Server: Das Plugin lädt keine externen Schriften, Skripte oder Analyse-Dienste.

> **Status:** Erste Testversion. Bitte zuerst in einer Staging- oder Testumgebung installieren.

## Auf einen Blick

| Bereich | Enthalten |
| --- | --- |
| Öffentliche Meldungen | Neue veröffentlichte Inhalte und redaktionell freigegebene Aktualisierungen |
| Persönliche Meldungen | WooCommerce-Bestellstatus und gezielte Admin-Nachrichten |
| Platzierung | Hauptmenü, Footer, vier schwebende Ecken oder Shortcode |
| Verwaltung | Entwurf, Planung, Veröffentlichung, Bearbeitung und Archivierung |
| Datenschutz | Empfänger, Lesestatus und persönliche Inhalte bleiben serverseitig getrennt |

## Installation

1. Lade [**MGD Notification Center 0.1.6**](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/releases/download/v0.1.6/mgd-notification-center-0.1.6.zip) herunter.
2. Öffne in WordPress **Plugins → Installieren → Plugin hochladen**.
3. Wähle die ZIP-Datei, installiere sie und aktiviere das Plugin.
4. Stelle unter **Notification Center → Einstellungen** die gewünschte Position ein.
5. Erstelle unter **Notification Center → Meldungen** eine öffentliche oder persönliche Testmeldung.

Eine kurze Schritt-für-Schritt-Anleitung steht in der [Wiki-Installation](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Installation).

## Funktionen

### Für Besucherinnen und Besucher

- Glocke mit Zahl für ungelesene Meldungen
- Tastaturbedienung und aussagekräftige ARIA-Beschriftungen
- Öffentliche Meldungen ohne Anmeldung
- Persönliche Meldungen nur für das zugehörige angemeldete Konto
- Lokale Speicherung gelesener **öffentlicher** Meldungen im Browser

### Für die Redaktion

- Öffentliche und persönliche Meldungen erstellen und bearbeiten
- Status **Entwurf**, **geplant**, **veröffentlicht** und **archiviert**
- Linkziel je Meldung
- Automatische öffentliche Meldung beim ersten Veröffentlichen eines Inhalts
- Optionaler Hinweis für eine bewusst angekündigte Inhaltsaktualisierung
- Automatische persönliche Meldung bei WooCommerce-Bestellstatusänderungen, wenn die Bestellung einem WordPress-Konto zugeordnet ist

## Platzierung

Die automatische Position stellst du im Backend ein. Für eine frei gewählte Stelle im Theme oder Page Builder steht dieser Shortcode bereit:

```text
[mgd_notification_center]
```

| Einstellung | Ergebnis |
| --- | --- |
| `floating-top-left` / `floating-top-right` | Schwebende Glocke oben |
| `floating-bottom-left` / `floating-bottom-right` | Schwebende Glocke unten |
| `menu` | Ausgabe über den Theme-Menü-Hook |
| `footer` | Ausgabe im Footer |
| `none` | Keine automatische Ausgabe; nur Shortcode oder Theme-Einbindung |

## Datenschutz und Sicherheit

Persönliche Meldungen werden nie über die öffentliche Schnittstelle ausgeliefert. Jede Lese-Markierung wird auf dem Server mit dem angemeldeten WordPress-Konto abgeglichen. Gastbestellungen erhalten absichtlich keine persönliche Statusmeldung, weil es keine sichere Konto-Zuordnung gibt.

Die Details zu Datenmodell, Berechtigungen und Löschverhalten stehen in der [Wiki: Datenschutz und Sicherheit](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Datenschutz-und-Sicherheit).

## Testen und Paket erzeugen

```bash
find mgd-notification-center -name '*.php' -exec php -l {} \;
node --check mgd-notification-center/assets/frontend.js
python3 scripts/check-docs.py
python3 scripts/build-package.py
```

Das fertige ZIP liegt anschließend unter `dist/`.

## Dokumentation

- [Installation](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Installation)
- [Meldungen verwalten](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Meldungen-verwalten)
- [Datenschutz und Sicherheit](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Datenschutz-und-Sicherheit)
- [Entwicklung und Tests](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/wiki/Entwicklung-und-Tests)

## Unterstützung

Fehler, Wünsche und nachvollziehbare Verbesserungsvorschläge bitte als [GitHub Issue](https://github.com/MichaelGahnDESIGN/MGD-Notification-WordPress/issues) melden. Bitte keine Zugangsdaten, personenbezogenen Daten oder vollständigen Datenbankauszüge anhängen.

## Lizenz

Dieses Plugin steht unter [GPL-2.0-or-later](mgd-notification-center/LICENSE).

## 0.1.2 – Plugin-Verwaltung

Separate Update-Prüfung, Plugin-Links und ergänzte lokale Details. [Anleitung](docs/WORDPRESS-VERWALTUNG.md).

## 0.1.3 – Neue Plugin-Kollektion

Die bisherige Empfehlungstabelle wird zur Plugin-Kollektion mit eigenen Icons, vollständigen Beschreibungen, Suche, Kategorie-, Status- und Preisfiltern. Details öffnen sich im Dialog; Installationen bleiben ausdrücklich bestätigt und aktivieren kein Plugin automatisch. Die Übersicht funktioniert auch mit noch älteren MGD-Katalog-Verbrauchern.

![Plugin-Kollektion – lokale Vorschau mit Testdaten](docs/plugin-katalog-vorschau.png)

[Gestaltung, Datenquelle, Sicherheit und Prüfung](docs/PLUGIN-KATALOG.md). Der externe Katalog gehört zur GitHub-Ausgabe; die getrennten WordPress.org-Kandidaten bleiben unverändert.

## 0.1.4 – Updateanzeige nach der Installation

Die Übersicht vergleicht zwischengespeicherte WordPress-Updateangebote zusätzlich mit der tatsächlich installierten Version. Gleiche, ältere oder fehlende Versionsangaben zeigen keine erneute Updateaktion. Sieben Regressionstests sichern diesen Zustand und die Rechteprüfung ab.

## 0.1.5 – Eigene Motive in der Plugin-Kollektion

Sieben passende, lokal mitgelieferte Hintergrundbilder ergänzen die Plugin-Karten. Icons, Suche und Filter bleiben erhalten. [Gestaltung und Herkunft](docs/PLUGIN-KATALOG.md#kartenmotive--24102026).

## 0.1.6 – Redaktion übersichtlicher steuern

Der Redaktionsbereich zeigt den Status der Meldungen auf einen Blick, ist für kleine Bildschirmbreiten optimiert und unterscheidet öffentliche von persönlichen Meldungen klarer. Persönliche Meldungen lassen sich nur noch für vorhandene WordPress-Konten speichern.

## Unveröffentlicht

✅ Der Redaktionsbereich berechnet Liste und Statusübersicht wieder serverseitig. Persönliche Meldungen werden erst beim bewussten Öffnen der einzelnen Meldung als gelesen markiert. Die Glocke lässt sich zusätzlich mit Escape schließen und setzt den Fokus zurück.

⚠️ Die Änderungen sind lokal geprüft, aber noch nicht in einer echten WordPress-Testinstallation per Browser kontrolliert.
