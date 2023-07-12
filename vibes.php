<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Vibes
 * Plugin URI:        https://perfops.one/vibes
 * Description:       Truthful user experience and browsing performances monitoring.
 * Version:           1.6.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       vibes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-wpcli.php';
require_once __DIR__ . '/includes/features/class-memory.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function vibes_activate() {
	Vibes\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function vibes_deactivate() {
	Vibes\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function vibes_uninstall() {
	Vibes\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function vibes_run() {
	\DecaLog\Engine::initPlugin( VIBES_SLUG, VIBES_PRODUCT_NAME, VIBES_VERSION, \Vibes\Plugin\Core::get_base64_logo() );
	$plugin = new Vibes\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'vibes_activate' );
register_deactivation_hook( __FILE__, 'vibes_deactivate' );
register_uninstall_hook( __FILE__, 'vibes_uninstall' );
vibes_run();
