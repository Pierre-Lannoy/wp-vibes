<?php
/**
 * Device handling
 *
 * Handles all device operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

use Vibes\System\GeoIP;

/**
 * Define the device functionality.
 *
 * Handles all device operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Device {

	/**
	 * The list of available classes.
	 *
	 * @since  1.0.0
	 * @var    array    $verbs    Maintains the classes list.
	 */
	public static $classes = [ 'bot', 'mobile', 'desktop', 'unknown' ];

	/**
	 * The list of available types.
	 *
	 * @since  1.0.0
	 * @var    array    $verbs    Maintains the types list.
	 */
	public static $types = [ 'smartphone', 'featurephone', 'tablet', 'phablet', 'phablet', 'console', 'portable_media_player', 'car_browser', 'tv', 'smart_display', 'camera', 'unknown' ];

	/**
	 * The list of observable devices.
	 *
	 * @since  1.0.0
	 * @var    array    $verbs    Maintains the observable devices list.
	 */
	public static $observable = [];

	/**
	 * Defines all needed globals.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		$removable        = [ 'bot', 'unknown' ];
		self::$observable = array_merge( [ 'unknown' ], array_diff( self::$classes, $removable ), array_diff( self::$types, $removable ) );
	}

	/**
	 * Defines all needed globals.
	 *
	 * @since 1.0.0
	 */
	public static function get_device() {
		return 'unknown';
	}

}

Device::init();
