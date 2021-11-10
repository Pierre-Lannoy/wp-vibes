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
		/*$idx = 'unkn';
		$val = 0;
		$qdx = '';
		foreach ( $metric as $key => $value ) {
			if ( false !== strpos( $key, '_sum' ) ) {
				$idx = str_replace( '_sum', '', $key );
				$val = $value;
			} else {
				if ( false !== strpos( $key, '_' ) ) {
					$qdx = substr( $key, 1 + strpos( $key, '_' ) );
				}
				if ( 'hit' === $qdx ) {
					$qdx = '';
				}
			}
		}
		$val = (string) self::get_displayable_value( $idx, $val );
		if ( array_key_exists( $idx, self::$metrics_units ) ) {
			$val .= self::$metrics_units[ $idx ];
		}
		return strtoupper( str_pad( $idx, 5 ) ) . strtoupper( str_pad( $qdx, 5 ) ) . str_pad( $val, 8, ' ', STR_PAD_LEFT ) ;*/
		return '';
	}

}