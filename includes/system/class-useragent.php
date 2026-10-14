<?php
/**
 * UserAgent handling
 *
 * Handles all UserAgent operations and detection (via Device Detector).
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

use Vibes\System\Favicon;
use Feather;

/**
 * Define the UserAgent functionality.
 *
 * Handles all UserAgent operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class UserAgent {

	/**
	 * Initialize a device and return it.
	 *
	 * @param string $ua    Optional. The user-agent string.
	 * @return object The device object.
	 * @since    1.0.0
	 */
	public static function get( $ua = '' ) {
		if ( class_exists( 'PODeviceDetector\API\Device' ) && '-' !== $ua ) {
			return \PODeviceDetector\API\Device::get( $ua );
		} else {
			return new static();
		}
	}

	/**
	 * Get the url.
	 *
	 * @param   array   $fields Optional. The args to add.
	 * @param   boolean $escape  Optional. Forces url escaping.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	public static function get_analytics_url( $fields = [], $escape = true ) {
		if ( ! class_exists( 'PODeviceDetector\API\Device' ) ) {
			return '';
		}
		$params = [];
		foreach ( $fields as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=podd-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . rawurlencode( $arg );
			}
		}
		$url = str_replace( '"', '\'\'', $url );
		if ( $escape ) {
			$url = esc_url( $url );
		}
		return $url;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->class_is_mobile  = wp_is_mobile();
		$this->class_is_desktop = ! $this->class_is_mobile;
	}

	/**
	 * Get the brand icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function brand_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the brand icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function brand_icon_image() {
		return '<img class="podd-brand-icon podd-brand-icon-none" style="width:16px;vertical-align:top;" src="' . $this->brand_icon_base64() . '" />';
	}

	/**
	 * Get the os icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function os_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the os icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function os_icon_image() {
		return '<img class="podd-os-icon podd-os-icon-none" style="width:16px;vertical-align:top;" src="' . $this->os_icon_base64() . '" />';
	}

	/**
	 * Get the browser icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function browser_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the browser icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function browser_icon_image() {
		return '<img class="podd-browser-icon podd-browser-icon-none" style="width:16px;vertical-align:top;" src="' . $this->browser_icon_base64() . '" />';
	}

	/**
	 * Get the bot icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function bot_icon_base64() {
		return Favicon::get_base64();
	}

	/**
	 * Get the bot icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function bot_icon_image() {
		return '<img class="podd-bot-icon podd-bot-icon-none" style="width:16px;vertical-align:top;" src="' . $this->bot_icon_base64() . '" />';
	}

	/**
	 * True if it's a bot, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $class_is_bot = false;

	/**
	 * True if it's a desktop, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $class_is_desktop = false;

	/**
	 * True if it's a mobile, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $class_is_mobile = false;

	/**
	 * The name of the class translated if translation exists, else in english.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $class_full_type = '';

	/**
	 * True if it's a smartphone, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_smartphone = false;

	/**
	 * True if it's a featurephone, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_featurephone = false;

	/**
	 * True if it's a tablet, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_tablet = false;

	/**
	 * True if it's a phablet, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_phablet = false;

	/**
	 * True if it's a console, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_console = false;

	/**
	 * True if it's a portable media player, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_portable_media_player = false;

	/**
	 * True if it's a car browser, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_car_browser = false;

	/**
	 * True if it's a tv, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_tv = false;

	/**
	 * True if it's a smart display, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_smart_display = false;

	/**
	 * True if it's a smart speaker, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_smart_speaker = false;

	/**
	 * True if it's a wearable, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_wearable = false;

	/**
	 * True if it's a peripheral, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_peripheral = false;

	/**
	 * True if it's a camera, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $device_is_camera = false;

	/**
	 * The name of the device type translated if translation exists, else in english.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $device_full_type = '';

	/**
	 * True if it's a browser, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_browser = false;

	/**
	 * True if it's a feed reader, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_feed_reader = false;

	/**
	 * True if it's a mobile app, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_mobile_app = false;

	/**
	 * True if it's a PIM, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_pim = false;

	/**
	 * True if it's a library, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_library = false;

	/**
	 * True if it's a media player, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $client_is_media_player = false;

	/**
	 * The name of the client type translated if translation exists, else in english.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_full_type = '';

	/**
	 * True if device has touch enabled, false otherwise.
	 *
	 * @var boolean
	 * @since 1.0.0
	 */
	public $has_touch_enabled = false;

	/**
	 * The OS name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $os_name = '';

	/**
	 * The OS short name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $os_short_name = '';

	/**
	 * The OS version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $os_version = '';

	/**
	 * The OS platform.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $os_platform = '';

	/**
	 * The client type.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_type = '';

	/**
	 * The client name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_name = '';

	/**
	 * The client short name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_short_name = '';

	/**
	 * The client version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_version = '';

	/**
	 * The client engine.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_engine = '';

	/**
	 * The client engine version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $client_engine_version = '';

	/**
	 * The brand name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $brand_name = '';

	/**
	 * The brand short name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $brand_short_name = '';

	/**
	 * The model name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $model_name = '';

	/**
	 * The bot name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_name = '';

	/**
	 * The bot category.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_category = '';

	/**
	 * The bot category translated if translation exists, else in english.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_full_category = '';

	/**
	 * The bot url.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_url = '';

	/**
	 * The bot producer name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_producer_name = '';

	/**
	 * The bot producer url.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $bot_producer_url = '';

}