<?php
/**
 * Mime types handling
 *
 * Handles all mime types operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

/**
 * Define the mime types functionality.
 *
 * Handles all mime types operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Mime {

	/**
	 * The unknown type.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $unknown    The unknown type.
	 */
	private static $unknown = 'unknown';

	/**
	 * The available categories.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $categories   The available categories.
	 */
	public static $categories = [ 'application', 'image', 'model', 'text', 'video', 'audio', 'chemical', 'font', 'message', 'x-conference' ];

	/**
	 * The available subcategories.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $subcategories   The available subcategories.
	 */
	public static $subcategories = [ 'binary', 'css', 'der', 'fastinfoset', 'html', 'script', 'json', 'vrml', 'wbxml', 'xml', 'yaml', 'zip' ];

	/**
	 * The available special categories.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $specialcategories   The available special categories.
	 */
	private static $specialcategories = [
		'text/css'                => 'css',
		'text/html'               => 'html',
		'text/jsx'                => 'script',
		'application/node'        => 'script',
		'application/javascript'  => 'script',
		'application/json'        => 'json',
		'application/x-httpd-php' => 'html',
		'application/x-perl'      => 'html',
		'application/x-font-woff' => 'font',
	];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Verify if nags are allowed and if yes, load the nags.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		require_once VIBES_ASSETS_DIR . 'mime-types.php';
	}

	/**
	 * Get a mime type.
	 *
	 * @param   string  $resource   The resource to guess mime type.
	 * @return  string  The mime type.
	 * @since   1.0.0
	 */
	public static function guess_type( $resource ) {
		if ( '' === $resource ) {
			return self::$unknown;
		}
		if ( preg_match( '/^\/([\w\-]+\/[\w\d\.\-\+]+);/iu', $resource, $matches ) ) {
			if ( 1 < count( $matches ) ) {
				return strtolower( $matches[1] );
			}
		}
		if ( preg_match( '/\bhttps?:\/\/[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/))/iu', urldecode( $resource ), $matches ) ) {
			return 'image/jpeg';
		}
		$ext = pathinfo( $resource, PATHINFO_EXTENSION );
		if ( array_key_exists( $ext, VIBES_MIME_TYPES ) ) {
			return VIBES_MIME_TYPES[ $ext ];
		}
		return self::$unknown;
	}

	/**
	 * Get a mime category.
	 *
	 * @param   string  $mime   The mime type to get mime category.
	 * @return  string  The mime category.
	 * @since   1.0.0
	 */
	public static function get_category( $mime ) {
		if ( '' === $mime || self::$unknown === $mime ) {
			return self::$unknown;
		}
		foreach ( self::$specialcategories as $type => $cat ) {
			if ( $type === $mime ) {
				return $cat;
			}
		}
		$result = self::$unknown;
		foreach ( self::$categories as $cat ) {
			if ( 0 === strpos( $mime, $cat . '/' ) ) {
				$result = $cat;
				break;
			}
		}
		if ( 'image' !== $result ) {
			foreach ( self::$subcategories as $subcat ) {
				if ( 0 < strpos( $mime, '+' . $subcat ) ) {
					$result = $subcat;
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Get a mime category name.
	 *
	 * @param   string  $category   The mime category to get mime category name.
	 * @return  string  The mime category name.
	 * @since   1.0.0
	 */
	public static function get_category_name( $category ) {
		switch ( $category ) {
			case 'application':
				/* translators: represents the content for the corresponding file type */
				return __( 'Application specific', 'vibes' );
			case 'image':
				/* translators: represents the content for the corresponding file type */
				return __( 'Image', 'vibes' );
			case 'model':
				/* translators: represents the content for the corresponding file type */
				return __( 'Model', 'vibes' );
			case 'text':
				/* translators: represents the content for the corresponding file type */
				return __( 'Text', 'vibes' );
			case 'video':
				/* translators: represents the content for the corresponding file type */
				return __( 'Video', 'vibes' );
			case 'audio':
				/* translators: represents the content for the corresponding file type */
				return __( 'Audio', 'vibes' );
			case 'chemical':
				/* translators: represents the content for the corresponding file type */
				return __( 'Chemical', 'vibes' );
			case 'font':
				/* translators: represents the content for the corresponding file type */
				return __( 'Font', 'vibes' );
			case 'message':
				/* translators: represents the content for the corresponding file type */
				return __( 'Message', 'vibes' );
			case 'x-conference':
				/* translators: represents the content for the corresponding file type */
				return __( 'Conference', 'vibes' );
			case 'binary':
				/* translators: represents the content for the corresponding file type */
				return __( 'Binary', 'vibes' );
			case 'css':
				/* translators: represents the content for the corresponding file type */
				return __( 'Style sheet', 'vibes' );
			case 'der':
				/* translators: represents the content for the corresponding file type */
				return __( 'Certificate', 'vibes' );
			case 'fastinfoset':
				/* translators: represents the content for the corresponding file type */
				return __( 'Fast Infoset', 'vibes' );
			case 'html':
				/* translators: represents the content for the corresponding file type */
				return __( 'HTML', 'vibes' );
			case 'script':
				/* translators: represents the content for the corresponding file type */
				return __( 'Script', 'vibes' );
			case 'json':
				/* translators: represents the content for the corresponding file type */
				return __( 'JSON', 'vibes' );
			case 'vrml':
			case 'wbxml':
				/* translators: represents the content for the corresponding file type */
				return __( 'VRML', 'vibes' );
			case 'xml':
				/* translators: represents the content for the corresponding file type */
				return __( 'XML', 'vibes' );
			case 'yaml':
				/* translators: represents the content for the corresponding file type */
				return __( 'YAML', 'vibes' );
			case 'zip':
				/* translators: represents the content for the corresponding file type */
				return __( 'Compressed', 'vibes' );
			default:
				return __( 'unknown', 'vibes' );
		}
	}

}

Mime::init();
