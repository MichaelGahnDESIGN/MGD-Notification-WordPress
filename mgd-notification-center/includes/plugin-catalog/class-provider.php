<?php
defined( 'ABSPATH' ) || exit;

/** Öffentliche Metadaten im Cache; lokale Installation und Rechte immer frisch ermitteln. */
final class MGD_Catalog_V4_Provider {
    private const OWNER = 'MichaelGahnDESIGN';
    private const FEED = 'https://theme.michael-gahn.de/api/v1/recommendations/wordpress.json';
    private const CACHE = 'mgd_wordpress_catalog_v4';

    public function catalog( bool $refresh = false ): array {
        $cached = get_transient( self::CACHE );
        if ( ! $refresh && is_array( $cached ) && isset( $cached['items'], $cached['source'], $cached['checked_at'] ) ) { return $cached; }
        $defaults = json_decode( file_get_contents( __DIR__ . '/plugins.json' ), true );
        $by_slug = array();
        foreach ( (array) $defaults as $metadata ) {
            if ( is_array( $metadata ) && isset( $metadata['slug'] ) && is_string( $metadata['slug'] ) ) {
                $by_slug[ $metadata['slug'] ] = $metadata;
            }
        }
        $response = wp_remote_get( self::FEED, array( 'timeout' => 4, 'redirection' => 0, 'limit_response_size' => 524288, 'headers' => array( 'Accept' => 'application/json' ) ) );
        $feed = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;
        $central = is_array( $feed ) && 1 === ( $feed['schema'] ?? null ) && 'wordpress' === ( $feed['platform'] ?? null ) && is_array( $feed['items'] ?? null ) && $this->details_url( $feed['catalog_url'] ?? '' );
        $entries = $central ? $feed['items'] : array();
        $items = array();
        foreach ( array_slice( $entries, 0, 80 ) as $entry ) {
            if ( ! is_array( $entry ) || 'wordpress' !== ( $entry['platform'] ?? '' ) || ! $this->slug( $entry['slug'] ?? null ) ) { continue; }
            $slug = $entry['slug'];
            $local = $by_slug[ $slug ] ?? array();
            // Lokale Metadaten ergänzen nur Darstellung und Installations-Erkennung. Inhalt und Sichtbarkeit kommen zentral.
            $repository = $local['repository'] ?? $slug;
            $items[ $slug ] = array(
                'slug' => $slug,
                'repository' => $repository,
                'name' => sanitize_text_field( $entry['name'] ?? $local['name'] ?? $slug ),
                'description' => sanitize_text_field( $entry['description'] ?? $local['description'] ?? 'Erweiterung aus der MGD Plugin-Kollektion.' ),
                'category' => sanitize_text_field( $local['category'] ?? 'Weitere' ),
                'icon' => $local['icon'] ?? 'generic.svg',
                'tone' => $local['tone'] ?? 'mint',
                'features' => $local['features'] ?? array( 'Für WordPress entwickelt', 'Von Michael Gahn DESIGN' ),
                'basenames' => $local['basenames'] ?? array(),
                'tier' => 'free',
                'version' => sanitize_text_field( $entry['version'] ?? '' ),
                'download' => '',
                'url' => $this->details_url( $feed['catalog_url'] ),
                'details_url' => $this->details_url( $feed['catalog_url'] ),
            );
        }
        // Eine leere Zentralantwort ist ein echter Leerzustand; bei Ausfall bleibt ein letzter Stand nutzbar.
        if ( ! $central && is_array( $cached ) && ! empty( $cached['items'] ) ) {
            $items = $cached['items'];
        }
        $catalog = array( 'items' => array_values( $items ), 'source' => $central ? 'central' : 'fallback', 'checked_at' => time() );
        set_transient( self::CACHE, $catalog, 6 * HOUR_IN_SECONDS );
        return $catalog;
    }

    private function slug( $slug ): bool {
        return is_string( $slug ) && preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug ) && strlen( $slug ) <= 80;
    }

    private function details_url( $url ): string {
        return is_string( $url ) && preg_match( '~^https://theme\.michael-gahn\.de/downloads\?plattform=wordpress$~D', $url ) ? esc_url_raw( $url ) : '';
    }

    public function valid_download( $url, string $repo ): bool {
        if ( ! is_string( $url ) || ! str_starts_with( $url, 'https://github.com/' . self::OWNER . '/' . $repo . '/releases/download/' ) || ! preg_match( '~^https://github\.com/[^?#\s]+\.zip$~iD', $url ) ) { return false; }
        foreach ( explode( '/', rawurldecode( wp_parse_url( $url, PHP_URL_PATH ) ) ) as $part ) {
            if ( in_array( $part, array( '.', '..' ), true ) || preg_match( '/[\x00-\x1f\x7f]/', $part ) ) { return false; }
        }
        return true;
    }

    public function decorate( array $items ): array {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugins = get_plugins();
        $updates = get_site_transient( 'update_plugins' );
        foreach ( $items as &$item ) {
            $item['installed'] = false;
            $item['active'] = false;
            $item['update_url'] = '';
            $item['installed_version'] = '';
            foreach ( $plugins as $file => $plugin ) {
                if ( ! in_array( $file, $item['basenames'], true ) && ! in_array( $item['url'], array( untrailingslashit( $plugin['UpdateURI'] ?? '' ), untrailingslashit( $plugin['PluginURI'] ?? '' ) ), true ) ) { continue; }
                $item['installed'] = true;
                $item['active'] = is_plugin_active( $file );
                $item['installed_version'] = $plugin['Version'];
                $offered_version = $updates->response[ $file ]->new_version ?? '';
                // WordPress kann direkt nach dem Update noch das vorige Angebot im Cache halten.
                if ( current_user_can( 'update_plugins' ) && is_string( $offered_version ) && version_compare( $offered_version, $plugin['Version'], '>' ) ) {
                    $item['update_url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $file ) ), 'upgrade-plugin_' . $file );
                }
                break;
            }
            $item['state'] = $item['update_url'] ? 'update' : ( $item['installed'] ? 'installed' : 'available' );
        }
        unset( $item );
        return $items;
    }
}
