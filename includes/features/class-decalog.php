<?php
/**
 * Vibes DecaLog integration
 *
 * Handles all calls logging operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Vibes\Plugin\Feature;

use Vibes\System\Blog;
use Vibes\System\Option;
use Vibes\System\Database;
use Vibes\System\Http;
use Vibes\System\Favicon;
use Vibes\System\Cache;
use Vibes\System\Conversion;

/**
 * Define the calls logging functionality.
 *
 * Handles all calls logging operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class DecaLog {

	/**
	 * Statistics filter.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics_filter    The statistics filters.
	 */
	private static $statistics_filter = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    2.0.0
	 */
	public static function init() {
		self::$statistics_filter['endpoint'] = [ '/\/livelog/iU', '/^\/server-status/iU', '/^\/server-info/iU' ];
	}

	/**
	 * Log API call.
	 *
	 * @param   array $record     The record to log.
	 * @since    2.0.0
	 */
	public static function log( $record ) {
		$record = Http::format_record( $record );
		if ( Option::network_get( 'smart_filter' ) ) {
			foreach ( self::$statistics_filter as $field => $filter ) {
				foreach ( $filter as $f ) {
					if ( preg_match( $f, $record[ $field ] ) ) {
						return;
					}
				}
			}
		}
		$level = Option::network_get( strtolower( $record['bound'] ) . '_level', 'unknown' );
		if ( ! in_array( $level, [ 'debug', 'info', 'notice', 'warning' ], true ) ) {
			$level = 'info';
			Option::network_set( strtolower( $record['bound'] ) . '_level', $level );
		}
		switch ( $record['bound'] ) {
			case 'INBOUND':
				$message = ucfirst( strtolower( $record['bound'] ) ) . ' ' . $record['verb'] . ' from ' . $record['id'];
				break;
			case 'OUTBOUND':
				$message = ucfirst( strtolower( $record['bound'] ) ) . ' ' . $record['verb'] . ' to ' . $record['id'];
				break;
			default:
				$message = '';
		}
		$message .= ' [size=' . Conversion::data_shorten( $record['size'] ) . ']';
		$message .= ' [latency=' . $record['latency'] . 'ms]';
		$message .= ' [response="' . $record['code'] . '/' . $record['message'] . '"]';
		$message .= ' [endpoint="' . $record['endpoint'] . '"]';
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->log( $level, $message, [ 'code' => $record['code'] ] );
	}
}

DecaLog::init();
