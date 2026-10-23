<?php
defined( 'ABSPATH' ) || exit;

/** REST trennt die anonyme öffentliche Liste von der kontogebundenen Inbox. */
final class MGD_Notification_REST {
    public function __construct( private MGD_Notification_Repository $repository ) {}

    public function register(): void {
        add_action( 'rest_api_init', function (): void {
            register_rest_route( 'mgd-notification/v1', '/public', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'public_feed' ), 'permission_callback' => '__return_true' ) );
            register_rest_route( 'mgd-notification/v1', '/me', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'personal_feed' ), 'permission_callback' => fn(): bool => is_user_logged_in() ) );
            register_rest_route( 'mgd-notification/v1', '/me/(?P<id>\\d+)/read', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'mark_read' ), 'permission_callback' => fn(): bool => is_user_logged_in() ) );
        } );
    }

    public function public_feed(): WP_REST_Response {
        $settings = MGD_Notification_Plugin::settings();
        return new WP_REST_Response( array( 'items' => $this->repository->public_feed( (int) $settings['public_limit'] ) ) );
    }

    public function personal_feed(): WP_REST_Response {
        return new WP_REST_Response( array( 'items' => $this->repository->personal_feed( get_current_user_id() ) ) );
    }

    public function mark_read( WP_REST_Request $request ): WP_REST_Response {
        $ok = $this->repository->mark_read( (int) $request['id'], get_current_user_id() );
        return new WP_REST_Response( array( 'success' => $ok ), $ok ? 200 : 404 );
    }
}
