<?php
// Free-Lizenz Anmeldung ohne WordPress testen: php tests/install-client.php (WP-Funktionen gestubbt, kein Netz)
declare(strict_types=1);

$GLOBALS['opts'] = [];
$GLOBALS['actions'] = [];
function get_option($k, $d = false) { return $GLOBALS['opts'][$k] ?? $d; }
function update_option($k, $v, $a = null) { $GLOBALS['opts'][$k] = $v; return true; }
function home_url() { return 'https://Shop.Example.test/'; }
function wp_parse_url($u, $c = -1) { return parse_url($u, $c); }
function register_activation_hook($f, $cb) { $GLOBALS['actions']['activate'][] = $cb; }
function add_action($h, $cb) { $GLOBALS['actions'][$h][] = $cb; }
function current_user_can($c) { return true; }
function wp_doing_ajax() { return false; }
function wp_nonce_field($a, $n, $r = true, $e = true) { return '<input type="hidden" name="' . $n . '" value="N">'; }

// Autoload wie im Hauptplugin
spl_autoload_register(static function (string $class): void {
    $prefix = 'Mgd\\NotificationCenter\\Install\\';
    if (str_starts_with($class, $prefix)) {
        require_once __DIR__ . '/../mgd-notification-center/includes/Install/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

use Mgd\NotificationCenter\Install\{InstallClient, Settings};
use Mgd\NotificationCenter\Install\Platform\WordPress;

$fails = 0;
function ok(string $n, bool $c): void { global $fails; echo ($c ? 'OK   ' : 'FAIL ') . $n . "\n"; if (!$c) { $fails++; } }

$main = (string) file_get_contents(__DIR__ . '/../mgd-notification-center/mgd-notification-center.php');
preg_match("/'MGD_NOTIFICATION_INSTALL_PRODUCT', '([^']+)'/", $main, $m);
$product = $m[1] ?? '';
ok('Produktname mgd-notification-center-wordpress', $product === 'mgd-notification-center-wordpress');
ok('boot() im Hauptplugin', str_contains($main, 'WordPress::boot( __FILE__, MGD_NOTIFICATION_INSTALL_PRODUCT'));
ok('Kurztext im Plugin-Kopf', str_contains($main, 'Description: Öffentliche und persönliche Benachrichtigungen ohne externe Dienste. Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.'));

$c = WordPress::client($product, 'wordpress', '1.5.0');
ok('client() baut', $c instanceof InstallClient);
ok('ab Werk an', $c?->isEnabled() === true);
ok('falscher Name > null', WordPress::client('Falsch Name', 'wordpress', '1.5.0') === null);

WordPress::boot(__FILE__, $product, 'wordpress', '1.5.0', 'https://shop.example.test/wp-admin/admin.php?page=mgd-notification-settings');
ok('admin_init + Hinweis registriert', count($GLOBALS['actions']['admin_init'] ?? []) === 1 && count($GLOBALS['actions']['admin_notices'] ?? []) === 1);

$html = WordPress::settingsHtml($product, 'wordpress', '1.5.0');
ok('Schalter angehakt', str_contains($html, 'name="' . Settings::fieldName($c) . '" value="1" checked'));
ok('Hinweistext drin', str_contains($html, 'höchstens einmal am Tag'));
ok('Nonce drin', str_contains($html, '_mgd_install_nonce'));

$c->setEnabled(false);
ok('abschalten bleibt gespeichert', WordPress::client($product, 'wordpress', '1.5.0')?->isEnabled() === false);
ok('Option ohne Autoload-Name', isset($GLOBALS['opts']['mgd_install_mgd_notification_center_wordpress']));

$admin = (string) file_get_contents(__DIR__ . '/../mgd-notification-center/includes/class-mgd-notification-admin.php');
ok('Schalter auf der Einstellungsseite, außerhalb vom Formular', preg_match("~</form>\s*<\?php // Free-Lizenz.*settingsHtml~s", $admin) === 1);

echo $fails === 0 ? "\nalles grün\n" : "\n$fails Fehler\n";
exit($fails === 0 ? 0 : 1);
