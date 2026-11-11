<?php
defined( 'ABSPATH' ) || exit;

/** Backend für autorisierte Redakteure. Empfängerwahl bleibt auf WordPress-Konten begrenzt. */
final class MGD_Notification_Admin {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_mgd_notification_save', array( $this, 'save' ) );
        add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );
        ( new MGD_WordPress_Plugin_Catalog_V4() )->register( 'mgd-notification-center' );
    }

    /** Lädt das ruhige, eigenständige Backend-Design nur im Notification Center. */
    public function assets( string $hook ): void {
        if ( ! str_contains( $hook, 'mgd-notification' ) && ! str_contains( $hook, 'toplevel_page_mgd-notification-center' ) ) {
            return;
        }
        wp_enqueue_style( 'mgd-notification-admin', MGD_NOTIFICATION_URL . 'assets/admin.css', array(), MGD_NOTIFICATION_VERSION );
    }

    public function menu(): void {
        add_menu_page( __( 'Notification Center', 'mgd-notification-center' ), __( 'Notification Center', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-center', array( $this, 'notifications_page' ), 'dashicons-bell', 58 );
        add_submenu_page( 'mgd-notification-center', __( 'Einstellungen', 'mgd-notification-center' ), __( 'Einstellungen', 'mgd-notification-center' ), 'manage_options', 'mgd-notification-settings', array( $this, 'settings_page' ) );
    }

    /** Status mit deutschen Namen */
    private const STATUSES = array( 'draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'scheduled' => 'Geplant', 'archived' => 'Archiviert' );

    public function notifications_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        $edit_id = absint( $_GET['edit'] ?? 0 );
        $item = $edit_id ? $this->repository->get_for_admin( $edit_id ) : null;
        $item = $item ?: array( 'id' => 0, 'title' => '', 'message' => '', 'target_url' => '', 'scope' => 'public', 'status' => 'draft', 'published_at' => '', 'recipient_ids' => '' );
        $date_value = ! empty( $item['published_at'] ) ? gmdate( 'Y-m-d\\TH:i', strtotime( $item['published_at'] ) ) : '';
        $rows = $this->repository->list_for_admin();
        $counts = array_count_values( array_column( $rows, 'status' ) );
        $icon = MGD_Notification_Plugin::settings()['icon'];
        ?>
        <div class="wrap mgd-notification-admin">
        <header class="mgd-notification-admin__hero"><div class="mgd-notification-admin__hero-icon"><?php echo MGD_Notification_Icons::svg( $icon, 'mgd-notification-admin__hero-svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="mgd-notification-admin__hero-text"><p class="mgd-notification-admin__kicker">MGD · NOTIFICATION CENTER</p><h1><?php esc_html_e( 'Meldungen', 'mgd-notification-center' ); ?></h1><p>Öffentliche Hinweise für alle Besucher und persönliche Nachrichten für einzelne Konten.</p></div><a class="button mgd-notification-admin__hero-button" href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-notification-settings' ) ); ?>">Einstellungen</a></header><hr class="wp-header-end">
        <?php if ( ! empty( $_GET['created'] ) || ! empty( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Meldung gespeichert.', 'mgd-notification-center' ); ?></p></div><?php endif; ?>
        <section class="mgd-notification-admin__stats" aria-label="Meldungsübersicht"><?php foreach ( self::STATUSES as $key => $label ) : ?><div class="mgd-notification-admin__stat mgd-notification-admin__stat--<?php echo esc_attr( $key ); ?>"><span><?php echo esc_html( $label ); ?></span><strong><?php echo (int) ( $counts[ $key ] ?? 0 ); ?></strong></div><?php endforeach; ?></section>
        <div class="mgd-notification-admin__grid">
        <form id="mgd-notification-form" class="mgd-notification-admin__card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <div class="mgd-notification-admin__card-head"><h2><?php echo $item['id'] ? esc_html__( 'Meldung bearbeiten', 'mgd-notification-center' ) : esc_html__( 'Neue Meldung', 'mgd-notification-center' ); ?></h2><?php if ( $item['id'] ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-notification-center' ) ); ?>">+ Neue Meldung</a><?php endif; ?></div>
            <?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save"><input type="hidden" name="notification_id" value="<?php echo esc_attr( $item['id'] ); ?>">
            <div class="mgd-notification-admin__field"><label for="mgd-title">Titel</label><input id="mgd-title" name="title" value="<?php echo esc_attr( $item['title'] ); ?>" required></div>
            <div class="mgd-notification-admin__field"><label for="mgd-message">Text</label><textarea id="mgd-message" rows="6" name="message" required><?php echo esc_textarea( $item['message'] ); ?></textarea><p class="description">Links und einfache Formatierung (fett, Listen) sind erlaubt und erscheinen im Fenster „Alle anzeigen“.</p></div>
            <div class="mgd-notification-admin__field"><label for="mgd-url">Link (optional)</label><input id="mgd-url" name="target_url" type="url" placeholder="https://" value="<?php echo esc_attr( $item['target_url'] ); ?>"></div>
            <div class="mgd-notification-admin__field mgd-notification-admin__field--check"><label><input type="checkbox" name="open_in_modal" value="1" <?php checked( ! empty( $item['open_in_modal'] ) ); ?>> Beim Klick den ganzen Inhalt im Fenster öffnen</label></div>
            <fieldset class="mgd-notification-admin__field mgd-notification-admin__segmented"><legend>Bereich</legend><label><input type="radio" name="scope" value="public" <?php checked( $item['scope'], 'public' ); ?>><span>Öffentlich</span></label><label><input type="radio" name="scope" value="personal" <?php checked( $item['scope'], 'personal' ); ?>><span>Persönlich</span></label></fieldset>
            <div class="mgd-notification-admin__field"><label for="mgd-recipient">Empfänger-ID</label><input id="mgd-recipient" name="recipient_id" type="number" min="1" value="<?php echo esc_attr( $item['recipient_ids'] ); ?>" aria-describedby="mgd-recipient-help"><p id="mgd-recipient-help" class="description">Nur bei „Persönlich“: die ID des WordPress-Kontos (Benutzer → Konto bearbeiten).</p></div>
            <div class="mgd-notification-admin__row">
                <div class="mgd-notification-admin__field"><label for="mgd-status">Status</label><select id="mgd-status" name="status"><?php foreach ( self::STATUSES as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $item['status'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
                <div class="mgd-notification-admin__field"><label for="mgd-publish-at">Veröffentlichen am</label><input id="mgd-publish-at" name="published_at" type="datetime-local" value="<?php echo esc_attr( $date_value ); ?>"></div>
            </div>
            <?php submit_button( __( 'Meldung speichern', 'mgd-notification-center' ), 'primary large', 'submit', false ); ?>
        </form>
        <section class="mgd-notification-admin__card mgd-notification-admin__list" aria-labelledby="mgd-list-title"><div class="mgd-notification-admin__card-head"><h2 id="mgd-list-title"><?php esc_html_e( 'Vorhandene Meldungen', 'mgd-notification-center' ); ?></h2><span class="mgd-notification-admin__muted"><?php echo count( $rows ); ?></span></div>
        <?php if ( ! $rows ) : ?><p class="mgd-notification-admin__empty">Noch keine Meldungen. Links die erste anlegen.</p><?php else : ?><ul><?php foreach ( $rows as $row ) : ?><li class="<?php echo (int) $row['id'] === (int) $item['id'] ? 'is-current' : ''; ?>"><a href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-notification-center&edit=' . absint( $row['id'] ) ) ); ?>"><strong><?php echo esc_html( $row['title'] ); ?></strong><span class="mgd-notification-admin__meta"><span class="mgd-notification-admin__scope"><?php echo 'personal' === $row['scope'] ? 'Persönlich' : 'Öffentlich'; ?></span><span class="mgd-notification-admin__status mgd-notification-admin__status--<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( self::STATUSES[ $row['status'] ] ?? $row['status'] ); ?></span><time><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $row['updated_at'] . ' UTC' ) ) ); ?></time></span></a></li><?php endforeach; ?></ul><?php endif; ?>
        </section>
        </div></div><?php
    }

    /** Platzierungen mit lesbaren Namen fürs Backend. */
    private const PLACEMENTS = array(
        'floating-bottom-right' => 'Schwebend unten rechts',
        'floating-bottom-left'  => 'Schwebend unten links',
        'floating-top-right'    => 'Schwebend oben rechts',
        'floating-top-left'     => 'Schwebend oben links',
        'menu'                  => 'Im Menü',
        'footer'                => 'Im Footer',
        'none'                  => 'Nur Shortcode',
    );

    public function settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        $settings = MGD_Notification_Plugin::settings();
        ?><div class="wrap mgd-notification-admin">
        <header class="mgd-notification-admin__hero"><div class="mgd-notification-admin__hero-icon" data-mgd-icon-preview><?php echo MGD_Notification_Icons::svg( $settings['icon'], 'mgd-notification-admin__hero-svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="mgd-notification-admin__hero-text"><p class="mgd-notification-admin__kicker">MGD · NOTIFICATION CENTER</p><h1><?php esc_html_e( 'Einstellungen', 'mgd-notification-center' ); ?></h1><p>Wo die Glocke sitzt, wie viele Meldungen sie zeigt und wie sie aussieht.</p></div><a class="button mgd-notification-admin__hero-button" href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-notification-center' ) ); ?>">Meldungen</a></header><hr class="wp-header-end">
        <?php if ( ! empty( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Einstellungen gespeichert.', 'mgd-notification-center' ); ?></p></div><?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_notification_save' ); ?><input type="hidden" name="action" value="mgd_notification_save"><input type="hidden" name="settings" value="1">
        <div class="mgd-notification-admin__grid mgd-notification-admin__grid--even">
        <section class="mgd-notification-admin__card"><div class="mgd-notification-admin__card-head"><h2>Platzierung</h2></div>
            <fieldset class="mgd-notification-admin__places"><legend class="screen-reader-text">Platzierung</legend><?php foreach ( self::PLACEMENTS as $placement => $label ) : ?><label class="mgd-notification-admin__place mgd-notification-admin__place--<?php echo esc_attr( $placement ); ?>"><input type="radio" name="placement" value="<?php echo esc_attr( $placement ); ?>" <?php checked( $settings['placement'], $placement ); ?>><span><i aria-hidden="true"></i><?php echo esc_html( $label ); ?></span></label><?php endforeach; ?></fieldset>
            <p class="description">Im Menü: Die Glocke kommt an das erste Menü der Seite, auch beim Menü-Modul von Divi. Gibt es kein Menü, erscheint sie unten rechts. „Nur Shortcode“: <code>[mgd_notification_center]</code> selbst einbauen, zum Beispiel in einem Divi-Code-Modul im Header.</p>
        </section>
        <section class="mgd-notification-admin__card"><div class="mgd-notification-admin__card-head"><h2>Anzeige</h2></div>
            <div class="mgd-notification-admin__field"><label for="mgd-visible-limit">Anzahl im Panel</label><div class="mgd-notification-admin__range"><input id="mgd-visible-limit" name="visible_limit" type="range" min="1" max="<?php echo (int) MGD_Notification_Plugin::FEED_LIMIT; ?>" value="<?php echo (int) $settings['visible_limit']; ?>" oninput="this.nextElementSibling.value=this.value"><output for="mgd-visible-limit"><?php echo (int) $settings['visible_limit']; ?></output></div><p class="description">So viele aktuelle Meldungen stehen beim Klick auf die Glocke. Alle weiteren (bis <?php echo (int) MGD_Notification_Plugin::FEED_LIMIT; ?>) zeigt „Alle anzeigen“.</p></div>
        </section>
        </div>
        <section class="mgd-notification-admin__card"><div class="mgd-notification-admin__card-head"><h2>Icon der Glocke</h2><span class="mgd-notification-admin__muted">Im Plugin eingebettet, nichts wird von außen geladen</span></div>
            <fieldset class="mgd-notification-admin__icons"><legend class="screen-reader-text">Icon der Glocke</legend><?php foreach ( MGD_Notification_Icons::choices() as $icon => $label ) : ?><label class="mgd-notification-admin__icon"><input type="radio" name="icon" value="<?php echo esc_attr( $icon ); ?>" <?php checked( $settings['icon'], $icon ); ?>><span><?php echo MGD_Notification_Icons::svg( $icon, 'mgd-notification-admin__icon-svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $label ); ?></span></label><?php endforeach; ?></fieldset>
        </section>
        <?php submit_button( __( 'Einstellungen speichern', 'mgd-notification-center' ), 'primary large' ); ?></form>
        <script>
        // Vorschau oben tauscht mit dem gewählten Icon
        document.querySelectorAll('.mgd-notification-admin__icon input').forEach(function (input) {
            input.addEventListener('change', function () { var svg = input.parentNode.querySelector('svg'), box = document.querySelector('[data-mgd-icon-preview]'); if (svg && box) { var copy = svg.cloneNode(true); copy.setAttribute('class', 'mgd-notification-admin__hero-svg'); box.replaceChildren(copy); } });
        });
        </script></div><?php
    }

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Nicht berechtigt.', 'mgd-notification-center' ) ); }
        check_admin_referer( 'mgd_notification_save' );
        if ( ! empty( $_POST['settings'] ) ) {
            $placement = sanitize_key( wp_unslash( $_POST['placement'] ?? '' ) );
            $limit = max( 1, min( MGD_Notification_Plugin::FEED_LIMIT, absint( $_POST['visible_limit'] ?? 5 ) ) );
            update_option( 'mgd_notification_settings', array_merge( MGD_Notification_Plugin::settings(), array(
                'placement'     => isset( self::PLACEMENTS[ $placement ] ) ? $placement : 'floating-bottom-right',
                'visible_limit' => $limit,
                'icon'          => MGD_Notification_Icons::valid( sanitize_key( wp_unslash( $_POST['icon'] ?? 'bell' ) ) ),
            ) ) );
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-settings&updated=1' ) ); exit;
        }
        $scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'public' ) );
        $recipient = absint( $_POST['recipient_id'] ?? 0 );
        if ( 'personal' === $scope && ( ! $recipient || ! get_user_by( 'id', $recipient ) ) ) {
            /*
             * Die Repository-Schicht blendet unbekannte Empfänger beim Ausliefern aus.
             * Diese Prüfung verhindert zusätzlich, dass eine verwaiste persönliche
             * Meldung überhaupt in der Datenbank angelegt oder gespeichert wird.
             */
            wp_die( esc_html__( 'Für eine persönliche Meldung wird ein vorhandenes WordPress-Konto benötigt.', 'mgd-notification-center' ), esc_html__( 'Empfänger prüfen', 'mgd-notification-center' ), array( 'response' => 400 ) );
        }
        $input = array(
            'open_in_modal' => isset( $_POST['open_in_modal'] ) && '1' === $_POST['open_in_modal'],
            'scope' => $scope, 'event_type' => 'manual', 'title' => wp_unslash( $_POST['title'] ?? '' ),
            'message' => wp_unslash( $_POST['message'] ?? '' ), 'target_url' => wp_unslash( $_POST['target_url'] ?? '' ),
            'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ), 'published_at' => wp_unslash( $_POST['published_at'] ?? '' ),
        );
        $notification_id = absint( $_POST['notification_id'] ?? 0 );
        if ( $notification_id ) {
            $this->repository->update_manual( $notification_id, $input, $recipient ? array( $recipient ) : array() );
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-center&updated=1' ) ); exit;
        }
        $this->repository->create( $input, $recipient ? array( $recipient ) : array() );
        wp_safe_redirect( admin_url( 'admin.php?page=mgd-notification-center&created=1' ) ); exit;
    }

    public function meta_box(): void {
        foreach ( MGD_Notification_Plugin::settings()['announce_post_types'] as $post_type ) { add_meta_box( 'mgd-notification-update', __( 'Notification Center', 'mgd-notification-center' ), array( $this, 'meta_box_html' ), $post_type, 'side' ); }
    }

    public function meta_box_html( WP_Post $post ): void {
        wp_nonce_field( 'mgd_notification_update', 'mgd_notification_update_nonce' );
        ?><label><input type="checkbox" name="mgd_notification_announce_update" value="1"> <?php esc_html_e( 'Diese Aktualisierung als Neuigkeit veröffentlichen', 'mgd-notification-center' ); ?></label><?php
    }
}
