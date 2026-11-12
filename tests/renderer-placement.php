<?php
// Test Glocke Platzierung + Anzahl, ohne WordPress (Stubs)
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('MGD_NOTIFICATION_DIR', __DIR__ . '/../mgd-notification-center/');
define('MGD_NOTIFICATION_URL', 'https://example.test/');
define('MGD_NOTIFICATION_VERSION', 'test');
$GLOBALS['opt'] = [];
$GLOBALS['filters'] = [];
function add_action(...$a) {}
function add_shortcode(...$a) {}
function add_filter(string $h, $cb) { $GLOBALS['filters'][$h] = $cb; }
function apply_filters(string $h, $v) { return isset($GLOBALS['filters'][$h]) && $h !== 'wp_nav_menu_items' ? ($GLOBALS['filters'][$h])($v) : $v; }
function get_option($k, $d = false) { return $GLOBALS['opt'][$k] ?? $d; }
function wp_parse_args($a, $d) { return array_merge($d, (array) $a); }
function is_admin() { return false; }
function wp_enqueue_style() {} function wp_enqueue_script() {}
function wp_unique_id($p) { return $p . '1'; }
function esc_attr($s) { return htmlspecialchars((string) $s); }
function esc_attr_e($s) { echo esc_attr($s); } function esc_html_e($s) { echo htmlspecialchars($s); }
function shortcode_atts($d, $a) { return array_merge($d, $a); }
function sanitize_key($s) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $s)); }
class MGD_Notification_Repository {}
require MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-icons.php';
require MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-plugin.php';
require MGD_NOTIFICATION_DIR . 'includes/class-mgd-notification-renderer.php';
$fails = 0; $n = 0;
function ok(string $name, bool $c): void { global $fails, $n; $n++; echo ($c ? 'OK   ' : 'FAIL ') . $name . "\n"; if (!$c) $fails++; }
function footer(MGD_Notification_Renderer $r): string { ob_start(); $r->automatic_output(); return (string) ob_get_clean(); }

// Menü ohne theme_location (Divi 5) bekommt die Glocke
$GLOBALS['opt']['mgd_notification_settings'] = ['placement' => 'menu'];
$r = new MGD_Notification_Renderer(new MGD_Notification_Repository());
$out = $r->menu_output('<li>A</li>', (object) ['theme_location' => '']);
ok('Menü ohne theme_location: Glocke drin', str_contains($out, 'mgd-notification-menu-item'));
ok('zweites Menü: keine doppelte Glocke', $r->menu_output('<li>B</li>', (object) ['theme_location' => 'primary']) === '<li>B</li>');
ok('Footer: kein Rückfall, wenn Menü da war', footer($r) === '');
// Menü gewählt, aber kein Menü auf der Seite > schwebend
$r = new MGD_Notification_Renderer(new MGD_Notification_Repository());
$f = footer($r);
ok('ohne Menü: Rückfall schwebend unten rechts', str_contains($f, 'mgd-notification-center--floating-bottom-right'));
// Theme übernimmt (Filter none) > nichts automatisch
$GLOBALS['filters']['mgd_notification_center_auto_placement'] = fn() => 'none';
$r = new MGD_Notification_Renderer(new MGD_Notification_Repository());
ok('Filter none: kein Menü', $r->menu_output('<li>A</li>', (object) []) === '<li>A</li>');
ok('Filter none: kein Footer', footer($r) === '');
unset($GLOBALS['filters']['mgd_notification_center_auto_placement']);
// Anzahl begrenzt
$GLOBALS['opt']['mgd_notification_settings'] = ['visible_limit' => 99];
ok('Anzahl max 30', MGD_Notification_Plugin::settings()['visible_limit'] === 30);
$GLOBALS['opt']['mgd_notification_settings'] = ['visible_limit' => 0];
ok('Anzahl min 1', MGD_Notification_Plugin::settings()['visible_limit'] === 1);
$GLOBALS['opt']['mgd_notification_settings'] = [];
ok('Anzahl Standard 5', MGD_Notification_Plugin::settings()['visible_limit'] === 5);
// Shortcode-Markup hat Alle anzeigen + Alle gelesen
$html = (new MGD_Notification_Renderer(new MGD_Notification_Repository()))->shortcode([]);
ok('Markup: Alle anzeigen', str_contains($html, 'mgd-notification-center__show-all'));
ok('Markup: Alle gelesen', str_contains($html, '>Alle gelesen<'));
// Icon statt Emoji
ok('Markup: SVG-Icon, kein Emoji', str_contains($html, '<svg') && !str_contains($html, "\u{1F514}"));
$GLOBALS['opt']['mgd_notification_settings'] = ['icon' => 'megaphone'];
ok('Icon wählbar', MGD_Notification_Plugin::settings()['icon'] === 'megaphone');
$GLOBALS['opt']['mgd_notification_settings'] = ['icon' => '<script>'];
ok('falsches Icon > Glocke', MGD_Notification_Plugin::settings()['icon'] === 'bell');
ok('10 Icons zur Auswahl', count(MGD_Notification_Icons::choices()) === 10);
echo "\n$n Prüfungen, $fails Fehler\n";
exit($fails ? 1 : 0);
