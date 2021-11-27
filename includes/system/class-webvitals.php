<?php
/**
 * Web Vitals handling
 *
 * Handles all Web Vitals operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

use Vibes\System\GeoIP;

/**
 * Define the Web Vitals functionality.
 *
 * Handles all Web Vitals operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WebVitals {

	/**
	 * The list of rated metrics.
	 *
	 * @since  1.0.0
	 * @var    array    $rated_metrics    Maintains the rated metrics list.
	 */
	public static $rated_metrics = [ 'LCP', 'FID', 'CLS', 'FCP' ];

	/**
	 * The list of unrated metrics.
	 *
	 * @since  1.0.0
	 * @var    array    $unrated_metrics    Maintains the unrated metrics list.
	 */
	public static $unrated_metrics = [ 'TTFB' ];

	/**
	 * The list of metrics ratios.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_ratios    Maintains the metrics ratios list.
	 */
	public static $metrics_ratios = [
		'CLS'  => 100000,
		'FID'  => 1,
		'LCP'  => 1,
		'FCP'  => 1,
		'TTFB' => 1,
	];

	/**
	 * The list of metrics display ratios.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_display    Maintains the metrics display ratios list.
	 */
	public static $metrics_display = [
		'CLS'  => 1,
		'FID'  => 1,
		'LCP'  => 1,
		'FCP'  => 1,
		'TTFB' => 1,
	];

	/**
	 * The list of metrics precisions.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_precisions    Maintains the metrics precisions list.
	 */
	public static $metrics_precisions = [
		'CLS'  => 2,
		'FID'  => 0,
		'LCP'  => 0,
		'FCP'  => 0,
		'TTFB' => 0,
	];

	/**
	 * The list of metrics rates.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_rates    Maintains the metrics rates list.
	 */
	public static $metrics_rates = [
		'CLS' => [ 10000, 25000 ],
		'FID' => [ 100, 300 ],
		'LCP' => [ 2500, 4000 ],
		'FCP' => [ 1800, 3000 ],
	];

	/**
	 * The list of metrics units.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_units    Maintains the metrics units list.
	 */
	public static $metrics_units = [
		'CLS'  => '',
		'FID'  => 'ms',
		'LCP'  => 'ms',
		'FCP'  => 'ms',
		'TTFB' => 'ms',
	];

	/**
	 * The list of metrics units.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_units    Maintains the metrics units list.
	 */
	public static $metrics_dunits = [
		'CLS'  => '',
		'FID'  => 'ms',
		'LCP'  => 's',
		'FCP'  => 's',
		'TTFB' => 'ms',
	];

	/**
	 * The list of metrics display ratios.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_display    Maintains the metrics display ratios list.
	 */
	public static $metrics_ddisplay = [
		'CLS'  => 1,
		'FID'  => 1,
		'LCP'  => 0.001,
		'FCP'  => 0.001,
		'TTFB' => 1,
	];

	/**
	 * The list of metrics precisions.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_precisions    Maintains the metrics precisions list.
	 */
	public static $metrics_dprecisions = [
		'CLS'  => 2,
		'FID'  => 0,
		'LCP'  => 1,
		'FCP'  => 1,
		'TTFB' => 0,
	];

	/**
	 * The list of metrics names.
	 *
	 * @since  1.0.0
	 * @var    array    $metrics_names    Maintains the metrics names list.
	 */
	public static $metrics_names = [
		'CLS'  => 'Cumulative Layout Shift (<a href="https://web.dev/cls/" target="_blank">CLS</a>)',
		'FID'  => 'First Input Delay (<a href="https://web.dev/fid/" target="_blank">FID</a>)',
		'LCP'  => 'Largest Contentful Paint (<a href="https://web.dev/lcp/" target="_blank">LCP</a>)',
		'FCP'  => 'First Contentful Paint (<a href="https://web.dev/fcp/" target="_blank">FCP</a>)',
		'TTFB' => 'Time to First Byte (<a href="https://web.dev/ttfb/" target="_blank">TTFB</a>)',
	];

	/**
	 * Get the rate field.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current storable value of the metric.
	 * @return  string  The field name.
	 * @since 1.0.0
	 */
	public static function get_rate_field( $metric, $value ) {
		if ( 0.0 === (float) $value ) {
			return 'none';
		}
		if ( in_array( $metric, self::$unrated_metrics, true ) ) {
			return 'hit';
		}
		if ( in_array( $metric, self::$rated_metrics, true ) ) {
			$result = 'poor';
			if ( $value <= self::$metrics_rates[ $metric ][0] ) {
				$result = 'good';
			} else {
				if ( $value <= self::$metrics_rates[ $metric ][1] ) {
					$result = 'impr';
				}
			}
			return $result;
		}
		return 'none';
	}

	/**
	 * Get the storable value.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current raw value of the metric.
	 * @return  integer  The storable value.
	 * @since 1.0.0
	 */
	public static function get_storable_value( $metric, $value ) {
		if ( array_key_exists( $metric, self::$metrics_ratios ) ) {
			$value *= self::$metrics_ratios[ $metric ];
		}
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
		if ( array_key_exists( $metric, self::$metrics_ratios ) ) {
			$value /= self::$metrics_ratios[ $metric ];
		}
		if ( array_key_exists( $metric, self::$metrics_display ) ) {
			$value *= self::$metrics_display[ $metric ];
		}
		$precision = 0;
		if ( array_key_exists( $metric, self::$metrics_precisions ) ) {
			$precision = self::$metrics_precisions[ $metric ];
		}
		return round( $value, $precision );
	}

	/**
	 * Get the graphable value.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current storable value of the metric.
	 * @return  null|float  The graphable value.
	 * @since 1.0.0
	 */
	public static function get_graphable_value( $metric, $value ) {
		if ( 0.0 === (float) $value ) {
			return null;
		}
		return self::get_displayable_value( $metric, $value );
	}

	/**
	 * Get the displayable value.
	 *
	 * @param   string      $metric The metric name.
	 * @param   integer     $value  The current storable value of the metric.
	 * @return  float  The displayable value.
	 * @since 1.0.0
	 */
	public static function display_value( $metric, $value ) {
		if ( 0.0 === (float) $value ) {
			return '-';
		}
		if ( array_key_exists( $metric, self::$metrics_ratios ) ) {
			$value /= self::$metrics_ratios[ $metric ];
		}
		if ( array_key_exists( $metric, self::$metrics_ddisplay ) ) {
			$value *= self::$metrics_ddisplay[ $metric ];
		}
		$precision = 0;
		if ( array_key_exists( $metric, self::$metrics_dprecisions ) ) {
			$precision = self::$metrics_dprecisions[ $metric ];
		}
		$unit = '';
		if ( array_key_exists( $metric, self::$metrics_dunits ) ) {
			$unit = '&nbsp;' . self::$metrics_dunits[ $metric ];
		}
		$val = (string) round( $value, $precision );
		if ( 0 < $precision && false === strpos( $val, '.' ) ) {
			$val .= '.';
			$val  = str_pad( $val, strlen( $val ) + $precision, '0' );
		}
		return $val . $unit;
	}

	/**
	 * Get the information line about metric and value.
	 *
	 * @param   array      $metric The metric array.
	 * @return  string  The storable value.
	 * @since 1.0.0
	 */
	public static function get_info_line( $metric ) {
		$idx = 'unkn';
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
		return strtoupper( str_pad( $idx, 5 ) ) . strtoupper( str_pad( $qdx, 7 ) ) . $val;
	}

}
