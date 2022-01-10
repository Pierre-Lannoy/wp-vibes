<?php
/**
 * Vibes dashboard
 *
 * Handles all dashboard operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.1.0
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
 * Define the dashboard functionality.
 *
 * Handles all dashboard operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.1.0
 */
class Dashboard {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.1.0
	 */
	public function __construct() {
	}

	/**
	 * Adds Web Vitals widget.
	 *
	 * @since    1.1.0
	 */
	public static function add_webvitals() {
		if ( Option::network_get( 'capture' ) ) {
			wp_add_dashboard_widget( 'vibes_webvitals', __( 'Web Vitals', 'vibes' ) . ' (' . sprintf( __( '%d minutes', 'vibes' ), Option::network_get( 'twindow' ) / 60 ) . ')', [
				self::class,
				'widget_webvitals'
			] );
		}
	}

	/**
	 * Renders Web Vitals widget.
	 *
	 * @since    1.1.0
	 */
	public static function widget_webvitals() {
		wp_enqueue_style( VIBES_ASSETS_ID );
		$values = Cache::get( 'webvitals', true );
		$stats  = [];
		foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
			$stats[ $metric ] = [
				'counter' => 0,
				'value'   => 0,
			];
		}
		if ( ! is_array( $values ) ) {
			$values = [];
		}
		foreach ( $values as $value ) {
			if ( array_key_exists( 'metric', $value ) && array_key_exists( 'value', $value ) && array_key_exists( $value['metric'], $stats ) ) {
				$stats[ $value['metric'] ]['counter'] += 1;
				$stats[ $value['metric'] ]['value']   += $value['value'];
			}
		}
		$result = '<div class="vibes-webvital-widget-container">';
		foreach ( $stats as $metric => $stat ) {
			if ( 2 * (int) Option::network_get( 'quality', 2 ) < $stat['counter'] ) {
				$value = WebVitals::display_value( $metric, $stat['value'] / $stat['counter'] );
				$level = WebVitals::get_rate_field( $metric, $stat['value'] / $stat['counter'] );
			} else {
				$value = '-';
				$level = 'none';
			}
			$result .= '<div class="vibes-webvital-widget-text">';
			$result .= '<span class="vibes-webvital-widget-definition vibes-webvital-definition-' . $level . '">&nbsp;&nbsp;&nbsp;' . WebVitals::$metrics_names[ $metric ] . '</span><br/>';
			$result .= '<span class="vibes-webvital-widget-index vibes-webvital-index-' . $level . '">' . $value . '</span>';
			$result .= '</div>';
		}
		$result .= '</div>';
		echo wp_kses( $result, PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD );
	}


}
