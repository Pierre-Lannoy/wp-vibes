<?php
/**
 * Autoload for Vibes.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'Vibes\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'Vibes\System\\' ) === 0 ) {
				$filepath = VIBES_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'Vibes\Plugin\Feature\\' ) === 0 ) {
				$filepath = VIBES_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'Vibes\Plugin\\' ) === 0 ) {
				$filepath = VIBES_INCLUDES_DIR . 'plugin/';
			} elseif ( strpos( $class, 'Vibes\Library\\' ) === 0 ) {
				$filepath = VIBES_VENDOR_DIR;
			} elseif ( strpos( $class, 'Vibes\Library\\' ) === 0 ) {
				$filepath = VIBES_VENDOR_DIR;
			} elseif ( strpos( $class, 'Vibes\Integration\\' ) === 0 ) {
				$filepath = VIBES_INCLUDES_DIR . 'integrations/';
			} elseif ( strpos( $class, 'Vibes\API\\' ) === 0 ) {
				$filepath = VIBES_INCLUDES_DIR . 'api/';
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = VIBES_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = VIBES_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
