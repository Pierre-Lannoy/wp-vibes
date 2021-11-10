<?php
/**
 * Browser performance handling
 *
 * Handles all browser performance operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

use Vibes\System\GeoIP;
use Vibes\System\Conversion;

/**
 * Define the browser performance functionality.
 *
 * Handles all browser performance operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class BrowserPerformance {

	/**
	 * The list of spans.
	 *
	 * @since  1.0.0
	 * @var    array    $spans    Maintains the spans list.
	 */
	public static $spans = [ 'redirect', 'dns', 'tcp', 'ssl', 'wait', 'download' ];

	/**
	 * The list of unrated metrics.
	 *
	 * @since  1.0.0
	 * @var    array    $unrated_metrics    Maintains the unrated metrics list.
	 */
	public static $unrated_metrics = [ 'load', 'redirects', 'size', 'cache' ];

	/**
	 * Get the storable value.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current raw value of the metric.
	 * @return  integer  The storable value.
	 * @since 1.0.0
	 */
	public static function get_storable_value( $metric, $value ) {
		return (int) round( $value, 2 );
	}

	/**
	 * Get the displayable value.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current storable value of the metric.
	 * @return  float  The displayable value.
	 * @since 1.0.0
	 */
	public static function get_displayable_value( $metric, $value ) {
		return (int) round( $value, 0 );
	}

	/**
	 * Get the information line about metric and value.
	 *
	 * @param   array      $metric The metric array.
	 * @return  string  The storable value.
	 * @since 1.0.0
	 */
	public static function get_info_line( $metric ) {
		$initiator = '';
		if ( array_key_exists( 'cache_sum', $metric ) ) {
			$size = 'local cache';
		} else {
			$size = Conversion::data_shorten( $metric['size_sum'] ?? 0 );
		}
		if ( 'navigation' === $metric['type'] ) {
			$host = $metric['endpoint'];
		}
		if ( 'resource' === $metric['type'] ) {
			$host      = $metric['endpoint'];
			$initiator = $metric['initiator'];
		}
		$host .= ' (' . $size . ')';
		if ( 'resource' === $metric['type'] ) {
			$host .= ' from ' . $metric['authority'];
		}
		$cnx = 0;
		foreach ( [ 'redirect', 'dns', 'tcp', 'ssl' ] as $span ) {
			$field = 'span_' . $span . '_duration';
			if ( array_key_exists( $field, $metric ) ) {
				$cnx += $metric[ $field ];
			}
		}
		$span = 'cnct:' . self::get_displayable_value( '', $cnx ) . 'ms ';
		if ( array_key_exists( 'span_wait_duration', $metric ) ) {
			$span .= 'wait:' . self::get_displayable_value( '', $metric['span_wait_duration'] ) . 'ms ';
		}
		if ( array_key_exists( 'span_download_duration', $metric ) ) {
			$span .= 'dwld:' . self::get_displayable_value( '', $metric['span_download_duration'] ) . 'ms ';
		}
		return 'SPAN ' . strtoupper( str_pad( $initiator, 7 ) ) . $span . $host;
	}

}