# Plugin Check – nachgezogene Warnungen

Kandidat 0.1.4. Lokaler offizieller Plugin Check 2.1.0 auf WordPress 7.1.3/PHP 8.3 mit Runtime-Bootstrap: **0 Fehler, 0 statische Warnungen**. Der vollständige lokale Bericht steht in `plugin-check.txt`. Der zusätzliche wp-env-Lauf in GitHub Actions kann funktional notwendige Enqueue-Scope-Hinweise ausgeben; sein vollständiger Bericht gehört zur Paketübergabe.

## Tatsächliche Änderungen

Wiederholte Feed- und Verwaltungsabfragen verwenden den WordPress Object Cache ausschließlich für die laufende Anfrage. Die Cache-Gruppe ist ausdrücklich nicht persistent; Schlüssel enthalten Zweck, Tabelle und Empfänger bzw. Meldungs-ID. Schreibaktionen invalidieren alle bereits gelesenen Anfrageansichten. Zeitabhängige Feeds berücksichtigen die aktuelle Sekunde, damit Terminierung und Ablauf nicht über eine Zeitgrenze hinweg veraltet bleiben. Eigene Tabellen brauchen weiter vorbereitete Direktabfragen; dafür gibt es einzelne begründete Code-Ausnahmen.

## Umgang mit Prüfregeln

Keine Prüfgruppe und kein Laufzeitcheck wird ausgeschaltet. Technisch notwendige Direktabfragen, rein lesende URL-Auswahl, signierte öffentliche Tokens und die feste Cache-Konstante sind nach Einzelprüfung mit dem jeweils engsten PHPCS-Code und einer Begründung an der konkreten Stelle gekennzeichnet. Diese statischen Einzel-Ausnahmen sind **keine zusätzlichen Sicherheitsprüfungen** und werden ausdrücklich offengelegt. Verzeichnis: `GEPRUEFTE-AUSNAHMEN.md`. Keine Zusage einer WordPress.org-Zulassung.

## Nachweise

41 Integrationstests bestanden: ursprüngliche 22 Schutzgrenzen-/Datenprüfungen plus 19 Cache-, Invalidierungs-, Empfänger- und AI-Seiten-ID-Prüfungen. Browser: keine Giveaway-Ressourcen ohne Formular, normale und nach wp_head gerenderte Shortcodes, Modal/Escape, 390px sowie echter CSV-Multipart-Import. Ungültige Upload-Nonce und falsche Endung werden abgewiesen. PHP-Syntax aller 86 Laufzeitdateien unter PHP 8.1 und der 15 MCP-Dateien zusätzlich unter PHP 7.4 geprüft. Keine produktiven Daten, keine Live-Einreichung.

## Abschluss der erweiterten Laufzeitprüfung (24.10.2026)

Zusätzlich sind fünf Schema-Lebenszyklustests für Notification bestanden: neuer Site-Präfix, bestätigter Aufbau, keine wiederholte Installation, erkannter Aufbaufehler und keine falsche Erfolgsmarkierung. Damit sind 46 Integrationsprüfungen dokumentiert. Acht Parser-Regressionen sichern die vollständige Rohberichtauswertung ab.

Ein grüner Plugin-Check-Wrapper allein genügt nicht mehr: Die CI verwirft auch rohe PHP-/Datenbankfehler, unbekannte Präfix-Ausgaben und unvollständiges JSON. Reguläre Warnungen bleiben sichtbar. Der Parser wird in jeder CI-Ausführung vorab getestet.
