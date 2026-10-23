# Geprüfte statische Einzel-Ausnahmen

Die nachfolgende Liste zeigt alle PHPCS-Einzel-Ausnahmen im tatsächlich gebauten Paket, einschließlich schon zuvor vorhandener Stellen. Kein phpcs:disable und keine globale Abschaltung von Plugin-Check-Prüfgruppen. Die konkreten Gründe stehen direkt am betroffenen Zugriff. Die Code-Ausnahmen müssen bei künftigen Änderungen erneut geprüft werden; sie sind keine pauschale Freigabe ähnlicher neuer Zugriffe.

- `includes/class-mgd-notification-admin.php:21` – WordPress.Security.NonceVerification.Recommended -- Nur lesender Editor-Aufruf nach manage_options; Speichern besitzt eine eigene Nonce.
- `includes/class-mgd-notification-renderer.php:34` – WordPress.Security.EscapeOutput.OutputNotEscaped -- intern erzeugtes Markup.
- `includes/class-mgd-notification-repository.php:85` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Eigene Tabelle, typisierte Eingaben; Anfragecache oben verworfen.
- `includes/class-mgd-notification-repository.php:90` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; Cache bereits vor der übergeordneten Schreibaktion verworfen.
- `includes/class-mgd-notification-repository.php:106` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
- `includes/class-mgd-notification-repository.php:117` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
- `includes/class-mgd-notification-repository.php:129` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
- `includes/class-mgd-notification-repository.php:138` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Empfängertabelle; Cache direkt davor invalidiert.
- `includes/class-mgd-notification-repository.php:148` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
- `includes/class-mgd-notification-repository.php:159` – WordPress.DB.DirectDatabaseQuery.DirectQuery -- Vorbereitete Abfrage der eigenen Plugin-Tabellen.
- `includes/class-mgd-notification-repository.php:176` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Tabelle; bisherige Feeds direkt davor invalidiert.
- `includes/class-mgd-notification-repository.php:189` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; update_manual invalidiert den Anfragecache.
- `includes/class-mgd-notification-repository.php:195` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Eigene Zuordnungstabelle; Cache bereits vor der übergeordneten Schreibaktion verworfen.
- `uninstall.php:5` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Nur nach ausdrücklicher Datenlöschoption bei Deinstallation; kein Lese-Cache.
- `uninstall.php:7` – WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Nur nach ausdrücklicher Datenlöschoption bei Deinstallation; kein Lese-Cache.
