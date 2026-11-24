<?php
// Brückenversion: Baukasten richtig verdrahtet? php tests/kit-bridge.php (ohne WordPress)
declare(strict_types=1);

$root = __DIR__ . '/../mgd-notification-center';
$main = (string) file_get_contents($root . '/mgd-notification-center.php');
$admin = (string) file_get_contents($root . '/includes/class-mgd-notification-admin.php');
$uninstall = (string) file_get_contents($root . '/uninstall.php');
$fails = 0;
function ok(string $n, bool $c): void { global $fails; echo ($c ? 'OK   ' : 'FAIL ') . $n . "\n"; if (!$c) { $fails++; } }

preg_match('/^ \* Version:\s*([0-9.]+)/m', $main, $m);
$version = $m[1] ?? '';
ok('Version im Kopf = Konstante', $version !== '' && str_contains($main, "define( 'MGD_NOTIFICATION_VERSION', '$version' )"));
ok('Produktname bleibt mgd-notification-center-wordpress', str_contains($main, "define( 'MGD_NOTIFICATION_PRODUCT', 'mgd-notification-center-wordpress' )"));
ok('Baukasten-Updater angemeldet', str_contains($main, "Setup::updater( __FILE__, 'mgd-notification-center-wordpress', 'wordpress', MGD_NOTIFICATION_VERSION"));
ok('MGD Tools eingebunden, Lizenz aus', str_contains($main, "Setup::tools( __FILE__, 'mgd-notification-center-wordpress'") && str_contains($main, "'slug'     => 'plugin-notification-wordpress'") && str_contains($main, "'license'  => false"));
ok('alte Reste aufräumen', str_contains($main, "Setup::cleanupLegacy( MGD_NOTIFICATION_PRODUCT, MGD_NOTIFICATION_VERSION, array( 'mgd_admin_release_*' ) )"));
ok('alter GitHub-Updater abgeschaltet', !str_contains($main, 'MGD_Plugin_Admin_Tools_20261023::register'));
ok('alter Install-Client raus', !str_contains($main, 'NotificationCenter\\\\Install\\\\') && !is_dir($root . '/includes/Install'));
ok('alte Katalog-Datei wird nicht mehr geladen (ab 1.6.1)', !str_contains($main, 'class-mgd-wordpress-plugin-catalog.php'));
ok('alte Katalogseite ganz raus (ab 1.6.2)', !is_file($root . '/includes/class-mgd-wordpress-plugin-catalog.php') && !is_file($root . '/includes/plugin-catalog/class-provider.php') && !is_dir($root . '/assets/plugin-catalog'));
ok('alte Plugin-Kollektion und Admin-Tools raus (ab 1.6.2 + MGD Tools)', !is_dir($root . '/includes/plugin-catalog') && !is_file($root . '/includes/class-mgd-plugin-admin-tools.php'));
ok('kein eigenes Menü mehr', !str_contains($admin, 'add_menu_page') && !str_contains($admin, 'add_submenu_page'));
ok('Seite mit Reitern Meldungen und Einstellungen', str_contains($admin, 'public function page()') && str_contains($admin, 'tab=settings'));
ok('Icons 128 und 256 da', is_file($root . '/assets/icon-128x128.png') && is_file($root . '/assets/icon-256x256.png'));
ok('alte Katalogseite nicht mehr angemeldet', !preg_match('/new\s+MGD_WordPress_Plugin_Catalog_V4\s*\(/', $admin));
ok('Seite Info und Updates', str_contains($admin, "'mgd-notification-plugins'"));
ok('kein zweiter Install-Schalter in den Einstellungen', !str_contains($admin, 'settingsHtml'));
$pos = strpos($uninstall, "Setup::uninstall( 'mgd-notification-center-wordpress', 'wordpress' )");
ok('uninstall.php meldet ab', $pos !== false);
ok('Abmelden vor der Daten-Abfrage', $pos !== false && $pos < (int) strpos($uninstall, 'mgd_notification_delete_data_on_uninstall'));
foreach (['Platform/WordPress/Setup.php', 'Platform/WordPress/Updater.php', 'Platform/WordPress/Dashboard.php', 'Install/InstallClient.php', 'Platform/WordPress/assets/kit-admin.css', 'Platform/WordPress/CatalogInstaller.php', 'Platform/WordPress/assets/kit-catalog.css', 'Platform/WordPress/assets/kit-catalog.js'] as $f) {
    ok('Baukasten-Datei ' . $f, is_file($root . '/includes/Kit/' . $f));
}
$ns = true;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/includes/Kit', FilesystemIterator::SKIP_DOTS)) as $f) {
    if (str_ends_with((string) $f, '.php') && !str_contains((string) file_get_contents((string) $f), 'namespace Mgd\\NotificationCenter\\Kit')) { $ns = false; }
}
ok('eigener Namensraum im ganzen Baukasten', $ns);
$cfg = json_decode((string) file_get_contents(__DIR__ . '/catalog-config.json'), true);
ok('Test-Version passt', ($cfg['version'] ?? '') === $version);

echo $fails === 0 ? "\nalles grün\n" : "\n$fails Fehler\n";
exit($fails === 0 ? 0 : 1);
