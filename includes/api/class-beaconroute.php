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
	 * The acceptable levels.
	 *
	 * @since  1.0.0
	 * @var    array    $bounds    The acceptable levels.
	 */
	protected $bounds = [ 'both', 'inbound', 'outbound' ];

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
		if ( ! ( array_key_exists( 'locationUrl', $content ) && array_key_exists( 'authenticated', $content ) && array_key_exists( 'metrics', $content ) && is_array( $content['metrics'] ) ) ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Malformed beacon POST request.', [ 'code' => 400 ] );
			return new \WP_REST_Response( null, 400 );
		}
		$record = Capture::init_record( $content['locationUrl'], $content['authenticated'] );
		foreach ( $content['metrics'] as $metric ) {
			if ( ! ( is_array( $metric ) && array_key_exists( 'name', $metric ) && array_key_exists( 'value', $metric ) ) ) {
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Malformed beacon POST request.', [ 'code' => 400 ] );
				return new \WP_REST_Response( null, 400 );
			}
			if ( in_array( $metric['name'], array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ), true ) ) {
				$storable_value = WebVitals::get_storable_value( (string) $metric['name'], (float) $metric['value'] );
				$rate_field     = WebVitals::get_rate_field( (string) $metric['name'], $storable_value );
				if ( 'none' !== $rate_field ) {
					$record[ $metric['name'] . '_sum' ]            = $storable_value;
					$record[ $metric['name'] . '_' . $rate_field ] = 1;
				}
			} else {
				switch ( $metric['name'] ) {
					case 'CLS':
				}
			}
		}
		Capture::record( $record );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( 'Signal received from %s and correctly pre-processed.', $record['endpoint'] ), [ 'code' => 202 ] );
		return new \WP_REST_Response( null, 202 );
	}

}