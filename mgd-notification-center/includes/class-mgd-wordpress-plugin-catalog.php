<?php
defined( 'ABSPATH' ) || exit;

/** Zentraler Katalog im Backend; Installation nur nach Admin-Klick und WordPress-Nonce. */
if ( ! class_exists( 'MGD_WordPress_Plugin_Catalog' ) ) {
    final class MGD_WordPress_Plugin_Catalog {
        private const OWNER = 'MichaelGahnDESIGN';
        private const FEED = 'https://theme.michael-gahn.de/api/catalog/plugins.json';
        private const CACHE_KEY = 'mgd_wordpress_plugin_catalog_v2';

        public function register( string $parent_slug ): void {
            add_action( 'admin_menu', static function () use ( $parent_slug ): void { add_submenu_page( $parent_slug, 'Weitere Plugins', 'Weitere Plugins', 'install_plugins', 'mgd-wordpress-plugin-catalog', array( new self(), 'page' ) ); }, 99 );
            add_action( 'admin_post_mgd_wordpress_plugin_install', array( $this, 'install' ) );
        }

        /** @return list<array<string,mixed>> */
        public function items( bool $refresh = false ): array {
            $cached = $refresh ? false : get_transient( self::CACHE_KEY );
            if ( is_array( $cached ) ) { return $cached; }
            $items = $this->central_items();
            if ( null === $items ) { $items = $this->github_items(); }
            set_transient( self::CACHE_KEY, $items, 6 * HOUR_IN_SECONDS );
            return $items;
        }

        /** @return list<array<string,mixed>>|null */
        private function central_items(): ?array {
            $response = wp_remote_get( self::FEED, array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/json' ) ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return null; }
            $feed = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! is_array( $feed ) || 1 !== (int) ( $feed['schemaVersion'] ?? 0 ) || ! is_array( $feed['items'] ?? null ) ) { return null; }
            $items = array();
            foreach ( $feed['items'] as $entry ) {
                if ( ! is_array( $entry ) || 'wordpress' !== ( $entry['platform'] ?? '' ) || 'live' !== ( $entry['status'] ?? '' ) ) { continue; }
                $repository = $this->repository_from_url( (string) ( $entry['repositoryUrl'] ?? '' ) );
                $item = null === $repository ? null : $this->release_item( $repository, (string) ( $entry['description'] ?? '' ) );
                if ( $item ) { $items[] = $item; }
            }
            return $items;
        }

        /** @return list<array<string,mixed>> */
        private function github_items(): array {
            $response = wp_remote_get( 'https://api.github.com/users/' . self::OWNER . '/repos?type=owner&per_page=100&sort=updated', array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-WordPress-Plugin-Catalog' ) ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return array(); }
            $items = array();
            foreach ( (array) json_decode( wp_remote_retrieve_body( $response ), true ) as $repository ) {
                $name = (string) ( $repository['name'] ?? '' );
                if ( empty( $repository['private'] ) && 1 === preg_match( '/^MGD-.+-WordPress$/', $name ) ) {
                    $item = $this->release_item( $name, (string) ( $repository['description'] ?? '' ) );
                    if ( $item ) { $items[] = $item; }
                }
            }
            return $items;
        }

        /** @return array<string,mixed>|null */
        private function release_item( string $repository, string $description ): ?array {
            if ( 1 !== preg_match( '/^MGD-[A-Za-z0-9._-]+-WordPress$/', $repository ) ) { return null; }
            $key = 'mgd_release_' . md5( $repository ); $release = get_transient( $key );
            if ( ! is_array( $release ) ) {
                $response = wp_remote_get( 'https://api.github.com/repos/' . self::OWNER . '/' . rawurlencode( $repository ) . '/releases/latest', array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-WordPress-Plugin-Catalog' ) ) );
                if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return null; }
                $release = json_decode( wp_remote_retrieve_body( $response ), true ); set_transient( $key, $release, 6 * HOUR_IN_SECONDS );
            }
            $download = esc_url_raw( (string) ( $release['assets'][0]['browser_download_url'] ?? '' ) );
            if ( ! str_starts_with( $download, 'https://github.com/' . self::OWNER . '/' . $repository . '/releases/download/' ) || ! str_ends_with( strtolower( $download ), '.zip' ) ) { return null; }
            return array( 'repository' => $repository, 'description' => $description, 'version' => sanitize_text_field( (string) ( $release['tag_name'] ?? '' ) ), 'download' => $download, 'installed' => $this->installed( $repository ) );
        }

        private function repository_from_url( string $url ): ?string {
            $prefix = 'https://github.com/' . self::OWNER . '/';
            if ( ! str_starts_with( $url, $prefix ) ) { return null; }
            $repository = substr( $url, strlen( $prefix ) );
            return 1 === preg_match( '/^MGD-[A-Za-z0-9._-]+-WordPress$/', $repository ) ? $repository : null;
        }

        private function installed( string $repository ): bool {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            foreach ( get_plugins() as $plugin ) { if ( 'https://github.com/' . self::OWNER . '/' . $repository === untrailingslashit( (string) ( $plugin['UpdateURI'] ?? $plugin['PluginURI'] ?? '' ) ) ) { return true; } }
            return false;
        }

        public function page(): void {
            if ( ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.' ); }
            $items = $this->items( ! empty( $_GET['refresh'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nur Cache-Aktualisierung.
            ?><div class="wrap"><h1>Weitere MGD WordPress-Plugins</h1><p>Die Liste kommt von <code>theme.michael-gahn.de</code> und wird spätestens nach sechs Stunden aktualisiert. Installation und Updates erfolgen nur nach deinem Klick.</p><p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mgd-wordpress-plugin-catalog', 'refresh' => 1 ), admin_url( 'admin.php' ) ) ); ?>">Liste aktualisieren</a></p><?php if ( ! $items ) : ?><p>Der Katalog ist gerade nicht erreichbar oder enthält keine installierbaren Releases.</p><?php else : ?><table class="widefat striped"><thead><tr><th>Plugin</th><th>Version</th><th>Beschreibung</th><th>Status</th><th>Aktion</th></tr></thead><tbody><?php foreach ( $items as $item ) : ?><tr><td><strong><?php echo esc_html( $item['repository'] ); ?></strong></td><td><?php echo esc_html( $item['version'] ); ?></td><td><?php echo esc_html( $item['description'] ); ?></td><td><?php echo $item['installed'] ? 'Installiert' : 'Nicht installiert'; ?></td><td><?php if ( ! $item['installed'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_plugin_catalog_install_' . $item['repository'] ); ?><input type="hidden" name="action" value="mgd_wordpress_plugin_install"><input type="hidden" name="repository" value="<?php echo esc_attr( $item['repository'] ); ?>"><button class="button button-primary">Installieren</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div><?php
        }

        public function install(): void {
            if ( ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.' ); }
            $repository = sanitize_text_field( wp_unslash( $_POST['repository'] ?? '' ) ); check_admin_referer( 'mgd_plugin_catalog_install_' . $repository );
            foreach ( $this->items() as $item ) { if ( hash_equals( (string) $item['repository'], $repository ) && ! $item['installed'] ) { require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; ( new Plugin_Upgrader( new Automatic_Upgrader_Skin() ) )->install( $item['download'] ); break; } }
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-wordpress-plugin-catalog' ) ); exit;
        }
    }
}
