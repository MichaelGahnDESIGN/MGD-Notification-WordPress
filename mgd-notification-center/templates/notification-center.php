<?php defined( 'ABSPATH' ) || exit; $panel_id = wp_unique_id( 'mgd-notification-panel-' ); ?>
<div class="mgd-notification-center mgd-notification-center--<?php echo esc_attr( $placement ); ?>" data-mgd-notification-center>
    <button class="mgd-notification-center__button" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-label="<?php esc_attr_e( 'Benachrichtigungen öffnen', 'mgd-notification-center' ); ?>">
        <?php echo MGD_Notification_Icons::svg( MGD_Notification_Plugin::settings()['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- feste SVGs aus dem Plugin ?><span class="mgd-notification-center__count" hidden>0</span>
    </button>
    <section id="<?php echo esc_attr( $panel_id ); ?>" class="mgd-notification-center__panel" aria-label="<?php esc_attr_e( 'Benachrichtigungen', 'mgd-notification-center' ); ?>" hidden>
        <div class="mgd-notification-center__head">
            <strong><?php esc_html_e( 'Benachrichtigungen', 'mgd-notification-center' ); ?></strong>
            <span class="mgd-notification-center__tools">
                <button type="button" class="mgd-notification-center__read-all" disabled><?php esc_html_e( 'Alle gelesen', 'mgd-notification-center' ); ?></button>
                <button type="button" class="mgd-notification-center__hide-read" disabled><?php esc_html_e( 'Gelesene ausblenden', 'mgd-notification-center' ); ?></button>
            </span>
        </div>
        <p class="mgd-notification-center__status" role="status" aria-live="polite"></p>
        <p class="mgd-notification-center__empty"><?php esc_html_e( 'Keine neuen Benachrichtigungen.', 'mgd-notification-center' ); ?></p>
        <ul class="mgd-notification-center__list"></ul>
        <button type="button" class="mgd-notification-center__show-all" aria-haspopup="dialog" hidden><?php esc_html_e( 'Alle anzeigen', 'mgd-notification-center' ); ?></button>
    </section>
</div>
