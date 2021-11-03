<?php
/**
 * Initialization of globals.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

define( 'VIBES_PRODUCT_NAME', 'Vibes' );
define( 'VIBES_PRODUCT_URL', 'https://github.com/Pierre-Lannoy/wp-vibes' );
define( 'VIBES_PRODUCT_SHORTNAME', 'Vibes' );
define( 'VIBES_PRODUCT_ABBREVIATION', 'vibes' );
define( 'VIBES_SLUG', 'vibes' );
define( 'VIBES_VERSION', '0.0.1-dev0' );
define( 'VIBES_API_VERSION', '1' );
define( 'VIBES_CODENAME', '"-"' );

define( 'VIBES_CDN_AVAILABLE', true );

global $timestart;

if ( ! defined( 'VIBES_INBOUND_CHRONO' ) ) {
	if ( defined( 'POWP_START_TIMESTAMP' ) ) {
		define( 'VIBES_INBOUND_CHRONO', POWP_START_TIMESTAMP );
	} elseif ( isset( $timestart ) && is_numeric( $timestart ) ) {
		define( 'VIBES_INBOUND_CHRONO', $timestart );
	} else {
		define( 'VIBES_INBOUND_CHRONO', microtime( true ) );
	}
}
