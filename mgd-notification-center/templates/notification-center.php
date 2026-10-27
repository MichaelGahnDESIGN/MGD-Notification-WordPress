<?php defined( 'ABSPATH' ) || exit; $panel_id = wp_unique_id( 'mgd-notification-panel-' ); ?>
<div class="mgd-notification-center mgd-notification-center--<?php echo esc_attr( $placement ); ?>" data-mgd-notification-center>
    <button class="mgd-notification-center__button" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-label="<?php esc_attr_e( 'Benachrichtigungen öffnen', 'mgd-notification-center' ); ?>">
        <span aria-hidden="true">🔔</span><span class="mgd-notification-center__count" hidden>0</span>
    </button>
    <section id="<?php echo esc_attr( $panel_id ); ?>" class="mgd-notification-center__panel" aria-label="<?php esc_attr_e( 'Benachrichtigungen', 'mgd-notification-center' ); ?>" hidden>
        <button type="button" class="mgd-notification-center__read-all" disabled>Alles gelesen</button><p class="mgd-notification-center__status" role="status" aria-live="polite"></p>
        <p class="mgd-notification-center__empty"><?php esc_html_e( 'Keine neuen Benachrichtigungen.', 'mgd-notification-center' ); ?></p>
        <ul class="mgd-notification-center__list"></ul>
    </section>
</div>
