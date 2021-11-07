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
	public static $types = [ 'smartphone', 'featurephone', 'tablet', 'phablet', 'console', 'portable_media_player', 'car_browser', 'tv', 'smart_display', 'camera', 'unknown' ];

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
	 * Get device type.
	 *
	 * @since 1.0.0
	 */
	public static function get_device() {
		return 'unknown';
	}

	/**
	 * Get icon.
	 *
	 * @param   string  $type   The device type.
	 * @return  string  The icon.
	 * @since 1.0.0
	 */
	public static function get_icon( $type ) {
		switch ( $type ) {
			case 'desktop':
				return '🖥️';
			case 'mobile':
			case 'featurephone':
			case 'phablet':
			case 'tablet':
			case 'smartphone':
				return '📱️';
			case 'console':
				return '🎮️';
			case 'portable_media_player':
				return '📀️';
			case 'car_browser':
				return '🚙️';
			case 'tv':
			case 'smart_display':
				return '📺️';
			case 'camera':
				return '📸️';
			default:
				return '';
		}
	}

	/**
	 * Get id name.
	 *
	 * @param   string  $type   The device type.
	 * @return  string  The id name.
	 * @since 1.0.0
	 */
	public static function get_id_name( $type ) {
		return strtoupper( str_replace( '_', ' ', $type ) );
	}

	/**
	 * Get icon and id name.
	 *
	 * @param   string  $type   The device type.
	 * @return  string  The icon and id name.
	 * @since 1.0.0
	 */
	public static function get_icon_id_name( $type ) {
		$icon = self::get_icon( $type );
		if ( '' === $icon ) {
			$icon = '';
		}
		return $icon . ' ' . self::get_id_name( $type );
	}

}

Device::init();
