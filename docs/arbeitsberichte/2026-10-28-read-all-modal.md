# Alles gelesen und Inhaltsmodal

Version 0.2.0 ergänzt eine explizite Aktion für alle aktuell geladenen Meldungen. Öffnen der Glocke allein markiert keine Meldung. Öffentliche Lesemarken liegen revisionsbezogen und begrenzt im Browser, persönliche Lesemarken werden ausschließlich über die bestehende authentifizierte Empfänger-Route gespeichert. Erfolglos bestätigte persönliche Anfragen werden nicht optimistisch als gelesen angezeigt. Mehrfachklicks auf dieselbe laufende Anfrage werden zusammengeführt.

Der Meldungseditor enthält die standardmäßig ausgeschaltete Option „Inhalt als Modal öffnen“. Das Frontend zeigt vollständigen Klartext und einen optionalen sicheren HTTP-/HTTPS-Link. Keine eingebetteten fremden Seiten, Bilder oder Skripte. Native Dialogbedienung, Escape, Fokusbegrenzung, Rückkehr zur Glocke und 44-Pixel-Bedienflächen sind vorgesehen. Die Panelplatzierung wurde für untere und linke Varianten sowie schmale Fenster korrigiert.

Die Anzeigeoption wird pro Meldung als nicht automatisch geladene WordPress-Option gespeichert. Es ist keine Tabellenmigration erforderlich. Öffentliche und persönliche Feed-Abfragen behalten ihre Sichtbarkeits-/Empfängerbedingungen. Beim ausdrücklich aktivierten vollständigen Deinstallations-Löschen werden auch diese Anzeigeoptionen entfernt.

PHP- und JavaScript-Syntaxprüfungen bestanden. GitHub-Prüfungen umfassen Paketbau und Katalogtests. Ein lokaler Browser-Vertragstest mit ausschließlich erfundenen Meldungen bestätigte: Öffnen lässt zwei Meldungen ungelesen; absichtlich fehlschlagende persönliche Leseanfrage lässt eine Meldung ungelesen und zeigt eine Fehlermeldung; erfolgreiche Wiederholung und Neuladen erhalten den gelesenen Zustand. Öffnen und Schließen des persönlichen Inhaltsdialogs funktionieren. Der Test verwendet einen simulierten lokalen Server und ersetzt keine Installation des eigenständigen Plugins in einer echten WordPress-Instanz.

Kein Release-ZIP veröffentlicht und keine automatische Installation auf anderen Websites vorgenommen. Der Quellstand und eine konkrete Live-Installation sind getrennte Zustände.
