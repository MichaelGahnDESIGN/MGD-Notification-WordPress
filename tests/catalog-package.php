<?php
/** Paketvertrag: gemeinsame Katalogdaten, neue Klasse, Versionsangaben und lokale Grafiken. */
$root = dirname(__DIR__);
$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
$runtime = $root . '/' . $config['runtime'];
function mgd_catalog_assert(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); }
}
$main = file_get_contents($runtime . '/' . $config['main']);
mgd_catalog_assert((bool) preg_match('/Version:\s*' . preg_quote($config['version'], '/') . '\s/', $main), 'Pluginversion');
// ab 1.6.2 alte Katalogseite weg, Bilder kommen vom Baukasten
mgd_catalog_assert(!is_file($runtime . '/includes/plugin-catalog/class-provider.php') && !is_dir($runtime . '/assets/plugin-catalog'), 'Alte Katalogseite entfernt');
$bootstrap = $main;
foreach (glob($runtime . '/includes/*.php') as $file) { $bootstrap .= file_get_contents($file); }
// ab 1.6.0: Weitere Plugins über den Baukasten (Dashboard), alter GitHub-Updater nicht mehr angemeldet
mgd_catalog_assert(str_contains($bootstrap, 'Setup::tools(') && !str_contains($bootstrap, 'Setup::dashboard('), 'Weitere Plugins über MGD Tools');
mgd_catalog_assert(!str_contains($main, 'MGD_Plugin_Admin_Tools_20261023::register'), 'Alter GitHub-Updater abgeschaltet');
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/Updater.php'), 'Baukasten vorhanden');
echo "PASS Pluginversion, Baukasten statt alter Katalogseite\n";
