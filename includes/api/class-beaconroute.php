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
		return Capture::preprocess( \json_decode( $request->get_body(), true ) );
	}

}
