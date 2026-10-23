<?php
defined( 'ABSPATH' ) || exit;

/** Erstellt Meldungen nur für freigegebene, veröffentlichte Ereignisse. */
final class MGD_Notification_Events {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'transition_post_status', array( $this, 'on_publish' ), 10, 3 );
        add_action( 'save_post', array( $this, 'on_announced_update' ), 20, 3 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status' ), 10, 4 );
    }

    public function on_publish( string $new_status, string $old_status, WP_Post $post ): void {
        if ( 'publish' !== $new_status || 'publish' === $old_status || wp_is_post_revision( $post->ID ) || ! $this->can_announce( $post ) ) {
            return;
        }
        $this->repository->create_once( array(
            'scope' => 'public', 'event_type' => 'content_published',
            'source_key' => "content:{$post->ID}:published", 'title' => get_the_title( $post ),
            'message' => sprintf( 'Neu veröffentlicht: %s', get_the_title( $post ) ),
            'target_url' => get_permalink( $post ), 'status' => 'published',
        ) );
    }

    public function on_announced_update( int $post_id, WP_Post $post, bool $updated ): void {
        if ( ! $updated || ! $this->can_announce( $post ) || empty( $_POST['mgd_notification_announce_update'] ) ) {
            return;
        }
        $nonce = sanitize_text_field( wp_unslash( $_POST['mgd_notification_update_nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'mgd_notification_update' ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $revision = gmdate( 'YmdHis', strtotime( $post->post_modified_gmt ) );
        $this->repository->create_once( array(
            'scope' => 'public', 'event_type' => 'content_updated',
            'source_key' => "content:{$post_id}:updated:{$revision}", 'title' => get_the_title( $post ),
            'message' => sprintf( 'Aktualisiert: %s', get_the_title( $post ) ),
            'target_url' => get_permalink( $post ), 'status' => 'published',
        ) );
    }

    public function on_order_status( int $order_id, string $from, string $to, WC_Order $order ): void {
        $user_id = (int) $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }
        $labels = wc_get_order_statuses();
        $status = $labels[ 'wc-' . $to ] ?? $to;
        $this->repository->create_once( array(
            'scope' => 'personal', 'event_type' => 'order_status',
            'source_key' => "order:{$order_id}:status:{$to}",
            'title' => sprintf( 'Bestellung #%s', $order->get_order_number() ),
            'message' => sprintf( 'Der Status deiner Bestellung lautet jetzt: %s.', $status ),
            'target_url' => $order->get_view_order_url(), 'status' => 'published',
        ), array( $user_id ) );
    }

    private function can_announce( WP_Post $post ): bool {
        $settings = MGD_Notification_Plugin::settings();
        return ! in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item' ), true ) && in_array( $post->post_type, $settings['announce_post_types'], true );
    }
}
