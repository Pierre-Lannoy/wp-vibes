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

use Vibes\System\BrowserPerformance;
use Vibes\System\Device;
use Vibes\Plugin\Feature\Schema;
use Vibes\Plugin\Feature\Memory;
use Vibes\System\GeoIP;
use Vibes\System\Mime;
use Vibes\System\Option;
use Vibes\System\User;
use Vibes\System\IP;
use Vibes\System\Http;
use Vibes\System\WebVitals;

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
	 * The acceptable types.
	 *
	 * @since  1.0.0
	 * @var    array    $types    The acceptable types.
	 */
	protected static $types = [ 'webvital', 'resource', 'navigation' ];

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
		if ( ( Option::network_get( 'capture', false ) || Option::network_get( 'rcapture', false ) ) && ( (int) Option::network_get( 'sampling' ) >= mt_rand( 1, 1000 ) ) ) {
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
					'authenticated' => ( 0 < (int) User::get_current_user_id( 0 ) ? 1 : 0 ),
					'sampling'      => (int) Option::network_get( 'resource_sampling' ),
					'smartFilter'   => Option::network_get( 'smart_filter' ) ? 1 : 0,
					'multiMetrics'  => Option::network_get( 'buffer' ) ? 1 : 0,
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
	 * @param   string  $type       The metrics type.
	 * @param   string  $initiator  Optional. The metrics type.
	 * @return  array   A pre-filled, ready to use, record.
	 * @since    1.0.0
	 */
	public static function init_record( $url, $authent, $type, $initiator = '' ) {
		$url_parts       = wp_parse_url( $url );
		$host            = '(self)';
		$cleaned_enpoint = self::clean_endpoint( $host, $url_parts['path'], 50, false );
		if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) && '' !== $url_parts['host'] ) {
			$host = $url_parts['host'];
		}
		$geoip               = new GeoIP();
		$record              = Schema::init_record( $type );
		$datetime            = new \DateTime( 'now', self::$local_timezone );
		$record['timestamp'] = $datetime->format( 'Y-m-d' );
		$record['site']      = get_current_blog_id();
		$record['endpoint']  = substr( self::clean_endpoint( $host, $url_parts['path'], 'resource' === $type ? Option::network_get( 'rcut_path' ) : Option::network_get( 'cut_path' ) ), 0, 250 );
		$record['country']   = $geoip->get_iso3166_alpha2( IP::get_current() ) ?? '00';
		$record['device']    = Device::get_device();
		$record['class']     = Device::get_class();
		$record['type']      = $type;
		$record['authent']   = 1 === (int) $authent ? 1 : 0;
		$record['id']        = substr( Http::top_domain( $host, false ), 0, 40 );
		if ( array_key_exists( 'scheme', $url_parts ) && isset( $url_parts['scheme'] ) ) {
			if ( in_array( (string) $url_parts['scheme'], Http::$extended_schemes, true ) ) {
				$record['scheme'] = $url_parts['scheme'];
			} else {
				$record['scheme']    = 'inline';
				$record['size_sum']  = mb_strlen( $cleaned_enpoint );
				$record['cache_sum'] = 0;
			}
		} else {
			$record['scheme'] = 'inline';
		}
		if ( array_key_exists( 'user', $url_parts ) && array_key_exists( 'pass', $url_parts ) && isset( $url_parts['user'] ) && isset( $url_parts['pass'] ) && '(self)' !== $host ) {
			$record['authority'] = substr( $url_parts['user'] . ':' . $url_parts['pass'] . '@' . $host, 0, 250 );
		} else {
			$record['authority'] = substr( $host, 0, 250 );
		}
		if ( 'resource' === $type ) {
			switch ( strtolower( $initiator ) ) {
				case 'xmlhttprequest':
				case 'beacon':
					$record['mime'] = 'application/json';
					break;
				case 'iframe':
					$record['mime'] = 'text/html';
					break;
				default:
					$record['mime'] = Mime::guess_type( $cleaned_enpoint );
					break;
			}
			$record['category'] = Mime::get_category( $record['mime'] );
			if ( 'img' === strtolower( $initiator ) && 'unknown' === $record['category'] ) {
				$record['category'] = 'image';
			}
			$record['mime'] = substr( $record['mime'], 0, 90 );
		}
		return $record;
	}

	/**
	 * Clean the endpoint.
	 *
	 * @param   string  $host       The host for the request.
	 * @param   string  $endpoint   The endpoint to clean.
	 * @param   int     $cut        Optional. The number of path levels to let.
	 * @param   boolean $filter     Optional. Accepts filtering.
	 * @return string   The cleaned endpoint.
	 * @since    1.0.0
	 */
	public static function clean_endpoint( $host, $endpoint, $cut = 3, $filter = true ) {

		if ( $filter ) {
			/**
			 * Filters the cut level.
			 *
			 * @since 1.0.0
			 *
			 * @param   int    $cut        The number of path levels to keep.
			 * @param   string $host       The host for the request.
			 * @param   string $endpoint   The endpoint to clean.
			 */
			$cut = (int) apply_filters( 'vibes_path_level', $cut, $host, $endpoint );
		}

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
	 * @param array          $record     A record of metrics.
	 * @since    1.0.0
	 */
	public static function record( $record ) {
		if ( ( 'resource' === $record['type'] && Option::network_get( 'rcapture', false ) ) || ( 'resource' !== $record['type'] && Option::network_get( 'capture', false ) ) ) {
			Schema::store_statistics( $record );
		}
		Memory::store_statistics( $record );
	}

	/**
	 * Pre-process an entry.
	 *
	 * @param array          $content     The body content of the request.
	 * @return \WP_REST_Response
	 * @since    1.0.0
	 */
	public static function preprocess( $content ) {
		if ( ( array_key_exists( 'type', $content ) && in_array( $content['type'], self::$types, true ) && array_key_exists( 'resource', $content ) && array_key_exists( 'authenticated', $content ) && array_key_exists( 'metrics', $content ) && is_array( $content['metrics'] ) ) ) {
			self::single_preprocess( $content );
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'Signal received and correctly pre-processed.', [ 'code' => 202 ] );
			return new \WP_REST_Response( null, 202 );
		}
		if ( array_key_exists( 'type', $content ) && 'multi' === $content['type'] && array_key_exists( 'metrics', $content ) && is_array( $content['metrics'] ) ) {
			foreach ( $content['metrics'] as $metric ) {
				$result = self::single_preprocess( $metric );
				if ( true !== $result ) {
					return $result;
				}
			}
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( _n( '%d signal received and correctly pre-processed.', '%d signals received and correctly pre-processed.', count( $content['metrics'] ), 'vibes' ), count( $content['metrics'] ) ), [ 'code' => 202 ] );
			return new \WP_REST_Response( null, 202 );
		}
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Malformed beacon POST request.', [ 'code' => 400 ] );
		return new \WP_REST_Response( null, 400 );
	}

	/**
	 * Pre-process a single entry.
	 *
	 * @param array          $content     A record of metrics.
	 * @return true|\WP_REST_Response   True if all goes OK, a rest response otherwise.
	 * @since    1.0.0
	 */
	private static function single_preprocess( $content ) {
		$record = self::init_record( $content['resource'], $content['authenticated'], $content['type'], $content['initiator'] ?? '' );
		foreach ( $content['metrics'] as $metric ) {
			if ( ! ( is_array( $metric ) && array_key_exists( 'name', $metric ) ) ) {
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Malformed beacon POST request.', [ 'code' => 400 ] );
				return new \WP_REST_Response( null, 400 );
			}
			switch ( $content['type'] ) {
				case 'webvital':
					if ( array_key_exists( 'value', $metric ) && in_array( $metric['name'], array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ), true ) ) {
						$storable_value = WebVitals::get_storable_value( (string) $metric['name'], (float) $metric['value'] );
						$rate_field     = WebVitals::get_rate_field( (string) $metric['name'], $storable_value );
						if ( 'none' !== $rate_field ) {
							$record[ $metric['name'] . '_sum' ]            = $storable_value;
							$record[ $metric['name'] . '_' . $rate_field ] = 1;

						}
					}
					break;
				case 'resource':
				case 'navigation':
					if ( array_key_exists( 'start', $metric ) && array_key_exists( 'duration', $metric ) && in_array( $metric['name'], BrowserPerformance::$spans, true ) ) {
						foreach ( [ 'start', 'duration' ] as $field ) {
							$record[ 'span_' . $metric['name'] . '_' . $field ] = BrowserPerformance::get_storable_value( $metric['name'], (float) $metric[ $field ] );
							if ( 0 > $record[ 'span_' . $metric['name'] . '_' . $field ] ) {
								return true;
							}
						}
						$record['hit'] = 1;
					}
					if ( array_key_exists( 'value', $metric ) && in_array( $metric['name'], BrowserPerformance::$unrated_metrics, true ) ) {
						if ( ! array_key_exists( $metric['name'] . '_sum', $record ) ) {
							$record[ $metric['name'] . '_sum' ] = BrowserPerformance::get_storable_value( $metric['name'], (float) $metric['value'] );
						}
					}
					if ( array_key_exists( 'initiator', $content ) ) {
						if ( 'xmlhttprequest' === $content['initiator'] ) {
							$content['initiator'] = 'xhr';
						}
						$record['initiator'] = substr( $content['initiator'], 0, 6 );
					}
					break;
			}
		}
		self::record( $record );
		return true;
	}

}
