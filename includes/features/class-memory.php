<?php
/**
 * Vibes shared memory
 *
 * Handles all shared memory operations.
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
use Vibes\System\GeoIP;
use Vibes\System\Environment;
use Vibes\System\SharedMemory;
use malkusch\lock\mutex\FlockMutex;
use Vibes\System\WebVitals;

/**
 * Define the shared memory functionality.
 *
 * Handles all shared memory operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Memory {

	/**
	 * Messages buffer.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics    The statistics buffer.
	 */
	private static $messages_buffer = [];

	/**
	 * The buffer size.
	 *
	 * @since  2.0.0
	 * @var    integer    $buffer    The number of messages in buffer.
	 */
	private static $buffer = 4000;

	/**
	 * The read index.
	 *
	 * @since  2.0.0
	 * @var    string    $index    The index for data.
	 */
	private static $index = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    2.0.0
	 */
	public static function init() {
		add_action( 'shutdown', [ 'Vibes\Plugin\Feature\Memory', 'write' ], DECALOG_MAX_SHUTDOWN_PRIORITY, 0 );
		add_action( 'shutdown', [ 'Vibes\Plugin\Feature\Memory', 'collate_metrics' ], DECALOG_MAX_SHUTDOWN_PRIORITY - 1, 0 );
	}

	/**
	 * Verify if auto-logging is enabled.
	 *
	 * @since    2.0.0
	 */
	public static function is_enabled() {
		return Option::network_get( 'livelog' );
	}

	/**
	 * Write all buffers to shared memory.
	 *
	 * @since    2.0.0
	 */
	public static function write() {
		if ( self::is_enabled() ) {
			self::write_records_to_memory();
		}
	}

	/**
	 * Get relevant ftok.
	 *
	 * @since    2.0.0
	 */
	private static function ftok() {
		if ( 1 === Environment::exec_mode() ) {
			return ftok( __FILE__, 'c' );
		} else {
			return ftok( __FILE__, 'w' );
		}
	}

	/**
	 * Effectively write the message buffer to shared memory.
	 *
	 * @since    2.0.0
	 */
	private static function write_records_to_memory() {
		$messages = self::$messages_buffer;
		// phpcs:ignore
		$mutex = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
		$ftok  = self::ftok();
		$mutex->synchronized(
			function () use ( $messages, $ftok ) {
				$sm   = new SharedMemory( $ftok );
				$data = $sm->read();
				foreach ( $messages as $key => $message ) {
					if ( is_array( $message ) ) {
						$data[ $key ] = $message;
					}
				}
				$data = array_slice( $data, -self::$buffer );
				if ( false === $sm->write( $data ) ) {
					//error_log( 'ERROR' );
				}
			}
		);
	}

	/**
	 * Read the current records.
	 *
	 * @return  array   The current records, ordered.
	 * @since    2.0.0
	 */
	public static function read(): array {
		try {
			// phpcs:ignore
			$mutex = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
			$ftok  = ftok( __FILE__, 'w' );
			$data1 = $mutex->synchronized(
				function () use ( $ftok ) {
					$log  = new SharedMemory( $ftok );
					$data = $log->read();
					return $data;
				}
			);
			$ftok  = ftok( __FILE__, 'c' );
			$data2 = $mutex->synchronized(
				function () use ( $ftok ) {
					$log  = new SharedMemory( $ftok );
					$data = $log->read();
					return $data;
				}
			);
			$data  = array_merge( $data1, $data2 );
			uksort( $data, 'strcmp' );
		} catch ( \Throwable $e ) {
			$data = [];
		}
		$result = [];
		foreach ( $data as $key => $line ) {
			if ( 0 < strcmp( $key, self::$index ) ) {
				$result[ $key ] = $line;
				self::$index    = $key;
			}
		}
		return $result;
	}

	/**
	 * Store statistics in buffer.
	 *
	 * @param   array $record     The record to bufferize.
	 * @since    2.0.0
	 */
	public static function store_statistics( $record ) {
		$date                = new \DateTime();
		$record['timestamp'] = $date->format( 'H:i:s.u' );
		self::$messages_buffer[ $date->format( 'YmdHisu' ) ] = $record;
	}

	/**
	 * Publish metrics.
	 *
	 * @since    2.3.0
	 */
	public static function collate_metrics() {
		$span   = \DecaLog\Engine::tracesLogger( VIBES_SLUG )->startSpan( 'Metrics collation', DECALOG_SPAN_SHUTDOWN );
		$values = Cache::get( 'webvitals', true );
		if ( ! is_array( $values ) ) {
			$values = [];
		} else {
			$limit = time() - Option::network_get( 'twindow' );
			$new   = [];
			foreach ( $values as $value ) {
				if ( array_key_exists( 'timestamp', $value ) && $limit < $value['timestamp'] ) {
					$new[] = $value;
				}
			}
			$values = $new;
		}
		$time = time();
		foreach ( self::$messages_buffer as $message ) {
			if ( 'webvital' === $message['type'] ) {
				foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
					if ( array_key_exists( $metric . '_sum', $message ) ) {
						$values[] = [
							'timestamp' => $time,
							'metric'    => $metric,
							'value'     => $message[ $metric . '_sum' ],
						];
					}
				}
			}
		}
		Cache::set( 'webvitals', $values, 'infinite', true );
		$stats = [];
		foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
			$stats[ $metric ] = [
				'counter' => 0,
				'value'   => 0,
			];
		}
		foreach ( $values as $value ) {
			if ( array_key_exists( 'metric', $value ) && array_key_exists( 'value', $value ) && array_key_exists( $value['metric'], $stats ) ) {
				$stats[ $value['metric'] ]['counter'] += 1;
				$stats[ $value['metric'] ]['value']   += $value['value'];
			}
		}
		if ( \DecaLog\Engine::isDecalogActivated() && Option::network_get( 'metrics' ) && Option::network_get( 'capture' ) && ! in_array( Environment::exec_mode(), [ 1, 3, 4 ], true ) ) {
			$span2 = \DecaLog\Engine::tracesLogger( VIBES_SLUG )->startSpan( 'Metrics publication', $span );
			foreach ( $stats as $metric => $stat ) {
				if ( 0 < $stat['counter'] ) {
					\DecaLog\Engine::metricsLogger( VIBES_SLUG )->setProdGauge( 'webvitals_' . strtolower( $metric ), round( $stat['value'] / ( ( 'CLS' === $metric ? 1000000 : 1000 ) * $stat['counter'] ), ( 'CLS' === $metric ? 2 : 3 ) ) );
				}
			}
			\DecaLog\Engine::tracesLogger( VIBES_SLUG )->endSpan( $span2 );
		}
		\DecaLog\Engine::tracesLogger( VIBES_SLUG )->endSpan( $span );
	}
}

if ( ! defined( 'DECALOG_MAX_SHUTDOWN_PRIORITY' ) ) {
	define( 'DECALOG_MAX_SHUTDOWN_PRIORITY', PHP_INT_MAX - 1000 );
}

Memory::init();
