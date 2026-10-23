# Tests

Die statischen Prüfungen laufen ohne WordPress-Installation. Die REST- und Datenbankpfade müssen zusätzlich in einer wegwerfbaren WordPress-Instanz getestet werden, niemals gegen Kundendaten.

Pflichtfälle: Empfänger A darf die Inbox von Empfänger B nicht lesen oder als gelesen markieren; Entwürfe erscheinen weder öffentlich noch persönlich; derselbe Bestellstatus erzeugt nur eine Zustellung; Gastbestellungen erzeugen keine persönliche Meldung.
