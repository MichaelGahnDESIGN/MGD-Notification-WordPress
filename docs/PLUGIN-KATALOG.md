# Plugin-Kollektion im WordPress-Backend

Die GitHub-Ausgabe erhält einen gemeinsamen, lokal gerenderten Katalog. Die getrennten WordPress.org-Einreichungskandidaten bleiben unverändert und enthalten diesen externen Katalog nicht.

## Gestaltungsvertrag

Administratoren sollen Erweiterungen entdecken, ihren Installationsstatus erkennen und Installation oder ein von WordPress erkanntes Update bewusst starten können. Die bisherige schmale Tabelle ohne Symbole dient als Ausgangspunkt. Die neue Ansicht verwendet die eigenen Plugin-Icons, einen dunklen Kopfbereich, klar gegliederte Produktkarten und zurückhaltende Farbfelder. Die erste Bildschirmhöhe zeigt bereits echte Erweiterungen. Unterstrichene Links gibt es in diesem Bereich nicht; Links behalten sichtbare Fokuszustände.

Zielgrößen: 1440×1100 und 390×844. Suche, Status-, Preis- und Kategorienfilter bleiben lokal. Ohne JavaScript bleiben alle Karten und die Installationsformulare benutzbar, Detail-Links führen zum Repository. Ein nativer Dialog unterstützt Fokusbegrenzung, Escape und Rückgabe an den Auslöser. Reduzierte Bewegung und erzwungene Systemfarben werden berücksichtigt.

Zustände: Aktualisieren zeigt „Wird aktualisiert …“; keine Treffer bieten Filter-Rücksetzung; ein leerer Feed wird als leer angezeigt. Bei Feed-Ausfall gibt es lokale Angaben für sieben bekannte WordPress-Plugins, GitHub-Releases liefern die überprüften Downloads. Einzelne fehlende Releases bleiben sichtbar und bieten keine Installation. Erfolge und Fehler erhalten verständliche Meldungen. Fehlende Installationsrechte führen zu HTTP 403.

## Datenquelle und Sicherheit

Quelle ist `https://theme.michael-gahn.de/api/catalog/plugins.json`, nicht die Gestaltung der Seite. Der Pfad lieferte bei der Bestandsprüfung am 23.10.2026 HTTP 404. Es wurde kein Plattform- oder Kunden-Deployment ausgeführt.

Der Cache hält öffentliche Metadaten bis zu sechs Stunden. Installationsstatus und WordPress-Updateangebote werden bei jedem Seitenaufruf frisch ermittelt. Icons liegen lokal im Plugin; keine Besucher- oder Zugangsdaten werden an den Feed übertragen. Die bekannten eigenen Grafiken stammen aus den jeweiligen MGD-Plugin-Repositories.

Installation und Cache-Aktualisierung benötigen Administratorrechte, POST und Nonce. Downloads müssen aus dem angegebenen Repository des festen GitHub-Eigentümers stammen und ein ZIP-Release-Asset sein. Alle Assets werden durchsucht, nicht nur das erste. Kostenpflichtige oder unbekannte Preisarten werden nicht automatisch installiert. Es gibt keine automatische Aktivierung. Updates benutzen ausschließlich den nativen WordPress-Upgrader mit dessen eigener Rechte- und Nonce-Prüfung.

Die gemeinsame Klasse trägt eine eigene Versionskennung. Ein enger Kompatibilitätsschritt ersetzt ausschließlich alte MGD-Katalog-Callbacks, damit andere noch ältere MGD-Plugins die Ansicht nicht überlagern. Andere Admin-Hooks bleiben erhalten.

Rückfall: bisheriges Plugin-ZIP wieder installieren; der Katalog verändert keine Inhaltsdaten oder Tabellen. Vor einem produktiven Plugin-Update die übliche Website-Sicherung verwenden.

## Lokale Prüfung am 23.10.2026

Die Oberfläche wurde unter WordPress 7.1.3 / PHP 8.3 in einer isolierten Installation mit ausschließlich synthetischen Daten gerendert. Vier neue Katalog-Verbraucher waren gemeinsam aktiv; zuvor wurde auch die Kombination mit einer alten Maintenance-Katalogklasse geprüft. Die neue Ansicht erschien genau einmal, ohne alte Tabelle.

Bestanden: zwölf serverseitige Integrationsprüfungen für Feed-Ausfall, zentrale Daten, leeren Feed, Teilverfügbarkeit, kostenpflichtige Einträge, ZIP-Auswahl hinter Prüfsummen, Download-Host/Repository/Query, native Update-Nonce und Rechte-/Nonce-Abweisung. Ein echtes lokales Test-ZIP wurde über den WordPress-Upgrader installiert und blieb inaktiv; der Status aktualisierte sich unmittelbar.

Browserprüfung: Suche, Kategorien, Status und Preise, Nulltreffer mit Rücksetzung, Dialog mit Tab-Begrenzung, Escape und Fokusrückgabe, sichtbarer Fokus, reduzierte Bewegung, Betrieb ohne JavaScript, Ladezustand, Fehlermeldung und Redakteurszugriff mit HTTP 403. 1440×1100 und 390×844 wurden betrachtet; kein horizontaler Überlauf. axe im Katalogbereich: keine WCAG-A/AA-Verstöße. Eine vollständige Screenreader- oder Multisite-Abnahme wird nicht behauptet. Der ECC-Nachweischecker akzeptiert den lokalen Evidenzbericht.

Paketvertrag, PHP-/JavaScript-Syntax und Secret-Scan des neuen Katalogcodes bestanden. Die 14 gemeinsamen Laufzeitdateien sind in allen vier Projekten identisch. Vorschau-ZIPs werden separat mit SHA-256 gebaut. Die Screenshots zeigen Testdaten, keine produktive Installation und keine echten Versionsangebote.

Diese Umsetzung gehört zur GitHub-Version 0.1.3. Die WordPress.org-Kandidaten bleiben getrennt; es wird keine Kundenwebsite direkt installiert oder verändert. Die Prüfung fand vor der Veröffentlichung in der isolierten Testinstallation statt.

## Nachprüfung des WordPress-Updatecaches

Nach einem echten Update kann WordPress noch ein altes Angebot im Update-Transient halten. Seit Version 0.1.4 bietet die Karte nur dann eine Aktualisierung an, wenn die angebotene Version tatsächlich neuer als die installierte ist. Gleiche oder ältere Angebote sowie fehlende Angaben werden ausgeblendet. Sieben Regressionstests decken gleiche, ältere, neuere Patch-/Minorversionen, leere/fehlende Werte und fehlende Rechte ab.

## Kartenmotive – 24.10.2026

Jede der sieben bekannten Erweiterungen hat ein eigenes, zum Thema passendes Hintergrundbild. Die vorhandenen Icons bleiben unverändert. Motive: Fotografie, Designatelier, offene Wege, Verbindungen, Mitteilungen, Publikationen und Technik. Die Bildserie ist mit dem integrierten Imagegen-Werkzeug erzeugt; es werden keine fremden Stockbilder oder Marken verwendet. Die vollständigen Eingaben stehen in `KATALOG-BILDER-PROMPTS.json`. Die generierten Bilddateien werden als Teil des GPL-Plugin-Pakets mitgeliefert; dies ist keine Aussage über die urheberrechtliche Schutzfähigkeit rein generierter Bilder.

Die sieben WebP-Dateien liegen lokal unter `assets/plugin-catalog/backgrounds/`, jeweils 1080×360 Pixel und höchstens 80 KB. Zusammen sind es rund 310 KB. Sie laden verzögert; es gibt keine externen Bildabrufe, eingebetteten Metadaten oder Animationen. Dekorative Bilder besitzen leere Alternativtexte. Kategorie und Versionsnummer stehen auf deckenden weißen Schildern. Bei erzwungenen Systemfarben werden die Hintergrundbilder ausgeblendet. Unbekannte Erweiterungen behalten eine farbige Ersatzfläche.

Die Oberfläche verwendet die gemeinsame Klasse V4 und übersteuert alte V3-Ansichten samt deren Asset-Hook gezielt. Damit reicht bereits ein aktualisierter Katalog-Verbraucher für die neue Darstellung. Die Installations-, Update- und Berechtigungslogik bleibt erhalten. WordPress.org-Einreichungskandidaten enthalten weiterhin keinen externen Katalog.

Bestanden: alle Motive im echten lokalen WordPress bei 1440×1100, 390×844 und 360×844 betrachtet; Darstellung mit älterem V3-Verbraucher zuerst, Bildausfall, Systemfarben, Suche, Dialog und axe geprüft (0 Verstöße). Paketprüfung kontrolliert Format, Abmessungen und Größe der sieben Motive. Rückfall: vorheriges GitHub-Release installieren; keine Datenbankänderung.
