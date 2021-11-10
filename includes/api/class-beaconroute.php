<?php
/**
 * DecaLog beacon handler
 *
 * Handles all beacon operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\API;

use Vibes\Plugin\Feature\Capture;
use Vibes\Plugin\Feature\Schema;
use Vibes\System\Blog;
use Vibes\System\BrowserPerformance;
use Vibes\System\Role;
use Vibes\Plugin\Feature\Wpcli;
use Vibes\Plugin\Feature\Memory;
use Vibes\System\WebVitals;

/**
 * Define the item operations functionality.
 *
 * Handles all item operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class BeaconRoute extends \WP_REST_Controller {

	/**
	 * The acceptable types.
	 *
	 * @since  1.0.0
	 * @var    array    $types    The acceptable types.
	 */
	protected $types = [ 'webvital', 'resource', 'navigation' ];

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since  1.0.0
	 */
	public function register_routes() {
		$this->register_route_beacon();
	}

	/**
	 * Register the routes for beacon.
	 *
	 * @since  1.0.0
	 */
	public function register_route_beacon() {
		register_rest_route(
			VIBES_REST_NAMESPACE,
			'beacon',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'post_beacon' ],
					'permission_callback' => [ $this, 'post_beacon_permissions_check' ],
					'args'                => array_merge( $this->arg_schema_beacon() ),
					'schema'              => [ $this, 'get_schema' ],
				],
			]
		);
	}

	/**
	 * Get the query params for beacon.
	 *
	 * @return array    The schema fragment.
	 * @since  1.0.0
	 */
	public function arg_schema_beacon() {
		return [];
	}

	/**
	 * Check if a given request has access to post beacon
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function post_beacon_permissions_check( $request ) {
		return true;
	}

	/**
	 * Post metrics
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function post_beacon( $request ) {
		$content = \json_decode( $request->get_body(), true );
		if ( ! ( array_key_exists( 'type', $content ) && in_array( $content['type'], $this->types, true ) && array_key_exists( 'resource', $content ) && array_key_exists( 'authenticated', $content ) && array_key_exists( 'metrics', $content ) && is_array( $content['metrics'] ) ) ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Malformed beacon POST request.', [ 'code' => 400 ] );
			return new \WP_REST_Response( null, 400 );
		}
		$record = Capture::init_record( $content['resource'], $content['authenticated'], $content['type'] );
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
						}
						$record['hit'] = 1;
					}
					if ( array_key_exists( 'value', $metric ) && in_array( $metric['name'], BrowserPerformance::$unrated_metrics, true ) ) {
						$record[ $metric['name'] . '_sum' ] = BrowserPerformance::get_storable_value( $metric['name'], (float) $metric['value'] );
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
		Capture::record( $record );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'Signal received and correctly pre-processed.', [ 'code' => 202 ] );
		return new \WP_REST_Response( null, 202 );
	}

}
