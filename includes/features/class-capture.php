<?php
/**
 * Vibes capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin\Feature;

use Vibes\System\Device;
use Vibes\Plugin\Feature\Schema;
use Vibes\Plugin\Feature\Memory;
use Vibes\System\GeoIP;
use Vibes\System\Option;
use Vibes\System\User;
use Vibes\System\IP;

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Capture {

	/**
	 * Local time zone.
	 *
	 * @since  1.0.0
	 * @var    \Vibes\System\Timezone    $local_timezone    The local timezone.
	 */
	private static $local_timezone = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		// phpcs:ignore
		if ( (int) Option::network_get( 'sampling', 100 ) >= mt_rand( 1, 1000 ) ) {
			add_filter(
				'script_loader_tag',
				function ( $tag, $handle, $src ) {
					if ( VIBES_ANALYTICS_ID === $handle ) {
						return '<script type="module" src="' . esc_url( $src ) . '" id="' . $handle . '-js"></script>';
					}
					return $tag;

				},
				10,
				3
			);
			wp_enqueue_script( VIBES_ANALYTICS_ID );
			wp_localize_script(
				VIBES_ANALYTICS_ID,
				'analyticsSettings',
				[
					'restUrl'       => esc_url_raw( rest_url() . VIBES_REST_NAMESPACE . '/beacon' ),
					'authenticated' => ( 0 === User::get_current_user_id( 0 ) ? 0 : 1 ),
					'gAnalytics'    => true,
				]
			);
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'Capture engine started.' );
		}
	}

	/**
	 * Get a pre-filled record.
	 *
	 * @param   string  $url        The location url.
	 * @param   integer $authent    Is te call authenticated?
	 * @return  array   A pre-filled, ready to use, record.
	 * @since    1.0.0
	 */
	public static function init_record( $url, $authent ) {
		$url_parts = wp_parse_url( $url );
		$host      = '';
		if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) ) {
			$host = $url_parts['host'];
		}
		$geoip               = new GeoIP();
		$record              = Schema::init_record();
		$datetime            = new \DateTime( 'now', self::$local_timezone );
		$record['timestamp'] = $datetime->format( 'Y-m-d' );
		$record['site']      = get_current_blog_id();
		$record['authent']   = 1 === (int) $authent ? 1 : 0;
		$record['endpoint']  = self::clean_endpoint( $host, $url_parts['path'], Option::network_get( 'cut_path', 3 ) );
		$record['country']   = $geoip->get_iso3166_alpha2( IP::get_current() ) ?? '00';
		$record['device']    = Device::get_device();
		return $record;
	}

	/**
	 * Clean the endpoint.
	 *
	 * @param   string $host       The host for the request.
	 * @param   string $endpoint   The endpoint to clean.
	 * @param   int    $cut        Optional. The number of path levels to let.
	 * @return string   The cleaned endpoint.
	 * @since    1.0.0
	 */
	private static function clean_endpoint( $host, $endpoint, $cut = 3 ) {

		/**
		 * Filters the cut level.
		 *
		 * @since 1.0.0
		 *
		 * @param   int    $cut        The number of path levels to let.
		 * @param   string $host       The host for the request.
		 * @param   string $endpoint   The endpoint to clean.
		 */
		$cut = (int) apply_filters( 'vibes_path_level', $cut, $host, $endpoint );

		if ( '/' !== substr( $endpoint, 0, 1 ) ) {
			$endpoint = '/' . $endpoint;
		}
		$endpoint = str_replace( '/://', '/', $endpoint );
		while ( 0 !== substr_count( $endpoint, '//' ) ) {
			$endpoint = str_replace( '//', '/', $endpoint );
		}
		$cpt = 0;
		$ep  = '';
		while ( $cpt < $cut ) {
			if ( 0 === substr_count( $endpoint, '/' ) ) {
				break;
			}
			do {
				$ep       = $ep . substr( $endpoint, 0, 1 );
				$endpoint = substr( $endpoint, 1 );
				$length   = strlen( $endpoint );
			} while ( ( 0 < $length ) && ( '/' !== substr( $endpoint, 0, 1 ) ) );
			++$cpt;
		}
		return $ep;
	}

	/**
	 * Records an entry.
	 *
	 * @param array          $record     A record of metrics..
	 * @since    1.0.0
	 */
	public static function record( $record ) {
		Schema::store_statistics( $record );
		Memory::store_statistics( $record );
	}

}
