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

use Vibes\System\UserAgent;
use Vibes\System\Environment;

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
		$ua = UserAgent::get();
		if ( $ua->class_is_desktop ) {
			return 'desktop';
		}
		if ( $ua->class_is_mobile ) {
			if ( $ua->device_is_smartphone ) {
				return 'smartphone';
			}
			if ( $ua->device_is_featurephone ) {
				return 'featurephone';
			}
			if ( $ua->device_is_tablet ) {
				return 'tablet';
			}
			if ( $ua->device_is_phablet ) {
				return 'phablet';
			}
			if ( $ua->device_is_console ) {
				return 'console';
			}
			if ( $ua->device_is_portable_media_player ) {
				return 'portable_media_player';
			}
			if ( $ua->device_is_car_browser ) {
				return 'car_browser';
			}
			if ( $ua->device_is_tv ) {
				return 'tv';
			}
			if ( $ua->device_is_smart_display ) {
				return 'smart_display';
			}
			if ( $ua->device_is_camera ) {
				return 'camera';
			}
		}
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
				$result = '🖥️';
				break;
			case 'mobile':
			case 'featurephone':
			case 'phablet':
			case 'tablet':
			case 'smartphone':
				$result = '📱️';
				break;
			case 'console':
				$result = '🎮️';
				break;
			case 'portable_media_player':
				$result = '📀️';
				break;
			case 'car_browser':
				$result = '🚙️';
				break;
			case 'tv':
			case 'smart_display':
				$result = '📺️';
				break;
			case 'camera':
				$result = '📸️';
				break;
			default:
				$result = '🥷';
		}
		if ( '🖥️' === $result && 1 === Environment::exec_mode() ) {
			$result = $result . ' ';
		}
		return $result;
	}

	/**
	 * Get id name.
	 *
	 * @param   string  $type   The device type.
	 * @return  string  The id name.
	 * @since 1.0.0
	 */
	public static function get_id_name( $type ) {
		return ucwords( str_replace( '_', ' ', $type ) );
	}

	/**
	 * Get icon and id name.
	 *
	 * @param   string  $type   The device type.
	 * @return  string  The icon and id name.
	 * @since 1.0.0
	 */
	public static function get_icon_id_name( $type ) {
		return self::get_icon( $type ) . ' ' . self::get_id_name( $type );
	}

}

Device::init();
