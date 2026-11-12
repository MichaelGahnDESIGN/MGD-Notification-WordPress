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
            register_rest_route( 'mgd-notification/v1', '/me/read-all', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'mark_all_read' ), 'permission_callback' => fn(): bool => is_user_logged_in() ) );
            register_rest_route( 'mgd-notification/v1', '/me/dismiss-read', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'dismiss_read' ), 'permission_callback' => fn(): bool => is_user_logged_in() ) );
            register_rest_route( 'mgd-notification/v1', '/me/(?P<id>\\d+)/dismiss', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'dismiss' ), 'permission_callback' => fn(): bool => is_user_logged_in() ) );
        } );
    }

    public function public_feed(): WP_REST_Response {
        return new WP_REST_Response( array( 'items' => $this->repository->public_feed( MGD_Notification_Plugin::FEED_LIMIT ) ) );
    }

    public function personal_feed(): WP_REST_Response {
        return new WP_REST_Response( array( 'items' => $this->repository->personal_feed( get_current_user_id() ) ) );
    }

    public function mark_read( WP_REST_Request $request ): WP_REST_Response {
        $ok = $this->repository->mark_read( (int) $request['id'], get_current_user_id() );
        return new WP_REST_Response( array( 'success' => $ok ), $ok ? 200 : 404 );
    }

    public function mark_all_read(): WP_REST_Response {
        return $this->bulk_response( $this->repository->mark_all_read( get_current_user_id() ) );
    }

    public function dismiss_read(): WP_REST_Response {
        return $this->bulk_response( $this->repository->dismiss_read( get_current_user_id() ) );
    }

    // DB-Fehler nicht als Erfolg melden
    private function bulk_response( ?int $count ): WP_REST_Response {
        return null === $count ? new WP_REST_Response( array( 'success' => false ), 500 ) : new WP_REST_Response( array( 'success' => true, 'count' => $count ) );
    }

    public function dismiss( WP_REST_Request $request ): WP_REST_Response {
        $ok = $this->repository->dismiss( (int) $request['id'], get_current_user_id() );
        return new WP_REST_Response( array( 'success' => $ok ), $ok ? 200 : 404 );
    }
}
