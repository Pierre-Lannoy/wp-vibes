<?php
/**
 * Initialization of globals.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

define( 'VIBES_PRODUCT_NAME', 'Vibes' );
define( 'VIBES_PRODUCT_URL', 'https://github.com/Pierre-Lannoy/wp-vibes' );
define( 'VIBES_PRODUCT_SHORTNAME', 'Vibes' );
define( 'VIBES_PRODUCT_ABBREVIATION', 'vibes' );
define( 'VIBES_SLUG', 'vibes' );
define( 'VIBES_VERSION', '1.6.0' );
define( 'VIBES_API_VERSION', '1' );
define( 'VIBES_CODENAME', '"-"' );

define( 'VIBES_CDN_AVAILABLE', true );


if ( ! defined( 'PERFOO_ALLOWED_HTML_FOR_DASHBOARD' ) ) {
	global $allowedposttags;
	$allowed        = $allowedposttags;
	$allowed['img'] = array_merge(
		$allowed['img'],
		[
			'style' => true,
		]
	);
	$extra          = [
		'script' => [],
		'option' => [
			'class'    => true,
			'style'    => true,
			'name'     => true,
			'id'       => true,
			'value'    => true,
			'selected' => true,
			'disabled' => true,
		],
		'select' => [
			'class'       => true,
			'style'       => true,
			'name'        => true,
			'id'          => true,
			'data'        => true,
			'placeholder' => true,
			'disabled'    => true,
		],
		'input'  => [
			'class'       => true,
			'style'       => true,
			'name'        => true,
			'id'          => true,
			'value'       => true,
			'data'        => true,
			'placeholder' => true,
			'disabled'    => true,
			'type'        => true,
			'checked'     => true,
			'step'        => true,
			'min'         => true,
			'max'         => true,

		],
	];
	add_filter( 'safe_style_css',
		function( $allowed ) {
			return array_merge( $allowed, [ 'opacity' ] );
		});
	define( 'PERFOO_ALLOWED_HTML_FOR_DASHBOARD', array_merge( $allowed, $extra ) );
}

if ( ! defined( 'PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD' ) ) {
	define( 'PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD', array_merge( wp_allowed_protocols(), [ 'data' ] ) );
}
