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
	private static $categories = [ 'application', 'image', 'model', 'text', 'video', 'audio', 'chemical', 'font', 'message', 'x-conference' ];

	/**
	 * The available subcategories.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $subcategories   The available subcategories.
	 */
	private static $subcategories = [ 'binary', 'css', 'der', 'fastinfoset', 'html', 'script', 'json', 'vrml', 'wbxml', 'xml', 'yaml', 'zip' ];

	/**
	 * The available special categories.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $specialcategories   The available special categories.
	 */
	private static $specialcategories = [
		'text/css'               => 'css',
		'text/html'              => 'html',
		'text/jsx'               => 'script',
		'application/node'       => 'script',
		'application/javascript' => 'script',
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
		require_once VIBES_ASSETS_DIR . 'mimes-types.php';
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
			return strtolower( $matches[0] );
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
		if ( '' === $mime ) {
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
		foreach ( self::$subcategories as $subcat ) {
			if ( 0 < strpos( $mime, '+' . $subcat ) ) {
				$result = $subcat;
				break;
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

		return self::$unknown;
	}

}

Mime::init();
