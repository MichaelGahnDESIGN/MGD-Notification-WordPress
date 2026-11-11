<?php
defined( 'ABSPATH' ) || exit;

/** Glocken-Icons lokal als SVG im Plugin, kein Emoji, keine externen Dateien. */
final class MGD_Notification_Icons {
    // Linien-Icons 24x24, Farbe kommt von currentColor
    private const ICONS = array(
        'bell'       => array( 'Glocke', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>' ),
        'bell-ring'  => array( 'Glocke mit Klang', '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><path d="M4 2C2.8 3.7 2 5.7 2 8"/><path d="M22 8c0-2.3-.8-4.3-2-6"/>' ),
        'bell-dot'   => array( 'Glocke mit Punkt', '<path d="M19.4 14.9C20.2 16.4 21 17 21 17H3s3-2 3-9c0-3.3 2.7-6 6-6 .7 0 1.3.1 1.9.3"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><circle cx="18" cy="8" r="3"/>' ),
        'megaphone'  => array( 'Megafon', '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>' ),
        'inbox'      => array( 'Posteingang', '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>' ),
        'mail'       => array( 'Brief', '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>' ),
        'message'    => array( 'Sprechblase', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' ),
        'info'       => array( 'Info', '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>' ),
        'star'       => array( 'Stern', '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>' ),
        'sparkles'   => array( 'Neuigkeiten', '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/>' ),
    );

    /** Schlüssel => Name fürs Backend */
    public static function choices(): array {
        return array_map( static fn( array $icon ): string => $icon[0], self::ICONS );
    }

    public static function valid( string $key ): string {
        return isset( self::ICONS[ $key ] ) ? $key : 'bell';
    }

    public static function svg( string $key, string $class = 'mgd-notification-center__icon' ): string {
        $key = self::valid( $key );
        return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . self::ICONS[ $key ][1] . '</svg>';
    }
}
