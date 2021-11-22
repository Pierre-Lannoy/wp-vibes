<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin;

use Vibes\Plugin\Feature\Analytics;
use Vibes\Plugin\Feature\AnalyticsFactory;
use Vibes\System\Assets;

use Vibes\System\Role;
use Vibes\System\Option;
use Vibes\System\Form;
use Vibes\System\Blog;
use Vibes\System\Date;
use Vibes\System\Timezone;
use Vibes\System\GeoIP;
use Vibes\System\Environment;
use PerfOpsOne\Menus;
use Vibes\System\SharedMemory;
use Vibes\Plugin\Feature\Memory;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Vibes_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( VIBES_ASSETS_ID, VIBES_ADMIN_URL, 'css/vibes.min.css' );
		$this->assets->register_style( VIBES_LIVELOG_ID, VIBES_ADMIN_URL, 'css/livelog.min.css' );
		$this->assets->register_style( 'vibes-daterangepicker', VIBES_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'vibes-switchery', VIBES_ADMIN_URL, 'css/switchery.min.css' );
		$this->assets->register_style( 'vibes-tooltip', VIBES_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'vibes-chartist', VIBES_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'vibes-chartist-tooltip', VIBES_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );
		$this->assets->register_style( 'vibes-jvectormap', VIBES_ADMIN_URL, 'css/jquery-jvectormap-2.0.3.min.css' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( VIBES_ASSETS_ID, VIBES_ADMIN_URL, 'js/vibes.min.js', [ 'jquery' ] );
		$this->assets->register_script( VIBES_ANALYTICS_ID, VIBES_PUBLIC_URL, 'js/vibes-analytics.min.js', [ 'jquery' ] );
		$this->assets->register_script( VIBES_LIVELOG_ID, VIBES_ADMIN_URL, 'js/livelog.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-moment-with-locale', VIBES_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-daterangepicker', VIBES_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-switchery', VIBES_ADMIN_URL, 'js/switchery.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-chartist', VIBES_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-chartist-tooltip', VIBES_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'vibes-chartist' ] );
		$this->assets->register_script( 'vibes-jvectormap', VIBES_ADMIN_URL, 'js/jquery-jvectormap-2.0.3.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'vibes-jvectormap-world', VIBES_ADMIN_URL, 'js/jquery-jvectormap-world-mill.min.js', [ 'jquery' ] );
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  2.0.0
	 */
	public function disable_wp_emojis() {
		if ( 'vibes-console' === filter_input( INPUT_GET, 'page' ) ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfopsone_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => VIBES_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \Vibes\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'vibes-settings',
				/* translators: as in the sentence "Vibes Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'vibes' ), VIBES_PRODUCT_NAME ),
				'menu_title'    => VIBES_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'plugin'        => VIBES_SLUG,
				'version'       => VIBES_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Vibes\System\Statistics', 'sc_get_raw' ],
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'Resources', 'vibes' ),
				/* translators: as in the sentence "Find out and explore resources needed by the pages of your network." or "Find out and explore resources needed by the pages of your website." */
				'description'   => sprintf( esc_html__( 'Find out and explore resources needed by the pages of your %s.', 'vibes' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'vibes' ) : esc_html__( 'website', 'vibes' ) ),
				'icon_callback' => [ \Vibes\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'vibes-resource-viewer',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( esc_html__( 'Resources', 'vibes' ), VIBES_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'Resources', 'vibes' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_resources_viewer_page' ],
				'plugin'        => VIBES_SLUG,
				'activated'     => Option::network_get( 'rcapture' ),
				'remedy'        => esc_url( admin_url( 'admin.php?page=vibes-settings' ) ),
			];
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'Web Vitals', 'vibes' ),
				/* translators: as in the sentence "View and analyze Web Vitals measured in the field for all visited pages of your network." or "View and analyze Web Vitals measured in the field for all visited pages of your website." */
				'description'   => sprintf( esc_html__( 'View and analyze Web Vitals measured in the field for all the visited pages of your %s.', 'vibes' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'vibes' ) : esc_html__( 'website', 'vibes' ) ),
				'icon_callback' => [ \Vibes\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'vibes-webvital-viewer',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( esc_html__( 'Web Vitals', 'vibes' ), VIBES_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'Web Vitals', 'vibes' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_webvitals_viewer_page' ],
				'plugin'        => VIBES_SLUG,
				'activated'     => Option::network_get( 'capture' ),
				'remedy'        => esc_url( admin_url( 'admin.php?page=vibes-settings' ) ),
			];
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'API Vibes', 'vibes' ),
				/* translators: as in the sentence "Find out inbound and outbound API calls made to/from your network." or "Find out inbound and outbound API calls made to/from your website." */
				'description'   => sprintf( esc_html__( 'Find out inbound and outbound API calls made to/from your %s.', 'vibes' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'vibes' ) : esc_html__( 'website', 'vibes' ) ),
				'icon_callback' => [ \Vibes\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'vibes-navigation-viewer',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( esc_html__( 'API Vibes', 'vibes' ), VIBES_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'API Vibes', 'vibes' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'plugin'        => VIBES_SLUG,
				'activated'     => Option::network_get( 'outbound_capture' ) || Option::network_get( 'inbound_capture' ),
				'remedy'        => esc_url( admin_url( 'admin.php?page=vibes-settings' ) ),
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['consoles'][] = [
				'name'          => esc_html__( 'Vibes Signals', 'vibes' ),
				/* translators: as in the sentence "Check the events that occurred on your network." or "Check the events that occurred on your website." */
				'description'   => sprintf( esc_html__( 'Displays %1$s performance signals as soon as they are received by your %2$s.', 'vibes' ), VIBES_PRODUCT_NAME, Environment::is_wordpress_multisite() ? esc_html__( 'network', 'vibes' ) : esc_html__( 'website', 'vibes' ) ),
				'icon_callback' => [ \Vibes\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'vibes-console',
				/* translators: as in the sentence "Vibes Viewer" */
				'page_title'    => sprintf( esc_html__( '%s Live Performance Signals', 'vibes' ), VIBES_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'Vibes Signals', 'vibes' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_console_page' ],
				'plugin'        => VIBES_SLUG,
				'activated'     => SharedMemory::$available,
				'remedy'        => esc_url( admin_url( 'admin.php?page=vibes&tab=misc' ) ),
			];
		}
		return $perfops;
	}

	/**
	 * Dispatch the items in the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function finalize_admin_menus() {
		Menus::finalize();
	}

	/**
	 * Removes unneeded items from the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function normalize_admin_menus() {
		Menus::normalize();
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfopsone_admin_menus', [ $this, 'init_perfopsone_admin_menus' ] );
		Menus::initialize();
	}

	/**
	 * Get actions links for myblogs_blog_actions hook.
	 *
	 * @param string $actions   The HTML site link markup.
	 * @param object $user_blog An object containing the site data.
	 * @return string   The action string.
	 * @since 1.2.0
	 */
	public function blog_action( $actions, $user_blog ) {
		if ( ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) && Option::network_get( 'outbound_capture' ) || Option::network_get( 'inbound_capture' ) ) {
			$actions .= " | <a href='" . esc_url( admin_url( 'admin.php?page=vibes-viewer&site=' . $user_blog->userblog_id ) ) . "'>" . __( 'API vibes', 'vibes' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Get actions for manage_sites_action_links hook.
	 *
	 * @param string[] $actions  An array of action links to be displayed.
	 * @param int      $blog_id  The site ID.
	 * @param string   $blogname Site path, formatted depending on whether it is a sub-domain
	 *                           or subdirectory multisite installation.
	 * @return array   The actions.
	 * @since 1.2.0
	 */
	public function site_action( $actions, $blog_id, $blogname ) {
		if ( ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) && Option::network_get( 'outbound_capture' ) || Option::network_get( 'inbound_capture' ) ) {
			$actions['api_usage'] = "<a href='" . esc_url( admin_url( 'admin.php?page=vibes-viewer&site=' . $blog_id ) ) . "' rel='bookmark'>" . __( 'API vibes', 'vibes' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'vibes_inbound_options_section', esc_html__( 'Inbound APIs', 'vibes' ), [ $this, 'inbound_options_section_callback' ], 'vibes_inbound_options_section' );
		add_settings_section( 'vibes_outbound_options_section', esc_html__( 'Outbound APIs', 'vibes' ), [ $this, 'outbound_options_section_callback' ], 'vibes_outbound_options_section' );
		add_settings_section( 'vibes_plugin_features_section', esc_html__( 'Plugin features', 'vibes' ), [ $this, 'plugin_features_section_callback' ], 'vibes_plugin_features_section' );
		add_settings_section( 'vibes_plugin_options_section', esc_html__( 'Plugin options', 'vibes' ), [ $this, 'plugin_options_section_callback' ], 'vibes_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=vibes-settings' ) ), esc_html__( 'Settings', 'vibes' ) );
		if ( Option::network_get( 'outbound_capture' ) || Option::network_get( 'inbound_capture' ) ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=vibes-viewer' ) ), esc_html__( 'Statistics', 'vibes' ) );
		}
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, VIBES_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . VIBES_SLUG . '/">' . __( 'Support', 'vibes' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics( false, 'navigation' );
		include VIBES_ADMIN_DIR . 'partials/vibes-admin-view-analytics.php';
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_resources_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics( false, 'resource' );
		include VIBES_ADMIN_DIR . 'partials/vibes-admin-view-resources.php';
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_webvitals_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics( false, 'webvital' );
		include VIBES_ADMIN_DIR . 'partials/vibes-admin-view-webvitals.php';
	}

	/**
	 * Get the content of the console page.
	 *
	 * @since 2.0.0
	 */
	public function get_console_page() {
		if ( isset( $this->current_view ) ) {
			$this->current_view->get();
		} else {
			include VIBES_ADMIN_DIR . 'partials/vibes-admin-view-console.php';
		}
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include VIBES_ADMIN_DIR . 'partials/vibes-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'vibes-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'vibes_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_options_usecdn' ) : false );
				Option::network_set( 'download_favicons', array_key_exists( 'vibes_plugin_options_favicons', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_options_favicons' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'vibes_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_options_nag' ) : false );
				Option::network_set( 'smart_filter', array_key_exists( 'vibes_plugin_features_smart_filter', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_features_smart_filter' ) : false );
				Option::network_set( 'livelog', array_key_exists( 'vibes_plugin_features_livelog', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_features_livelog' ) : false );
				Option::network_set( 'metrics', array_key_exists( 'vibes_plugin_features_metrics', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_plugin_features_metrics' ) : false );
				Option::network_set( 'inbound_capture', array_key_exists( 'vibes_inbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_inbound_options_capture' ) : false );
				Option::network_set( 'outbound_capture', array_key_exists( 'vibes_outbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'vibes_outbound_options_capture' ) : false );
				Option::network_set( 'inbound_cut_path', array_key_exists( 'vibes_inbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'vibes_inbound_options_cut_path' ) : Option::network_get( 'vibes_inbound_options_cut_path' ) );
				Option::network_set( 'outbound_cut_path', array_key_exists( 'vibes_outbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'vibes_outbound_options_cut_path' ) : Option::network_get( 'vibes_outbound_options_cut_path' ) );
				Option::network_set( 'inbound_level', array_key_exists( 'vibes_inbound_options_level', $_POST ) ? (string) filter_input( INPUT_POST, 'vibes_inbound_options_level' ) : Option::network_get( 'vibes_inbound_options_level' ) );
				Option::network_set( 'outbound_level', array_key_exists( 'vibes_outbound_options_level', $_POST ) ? (string) filter_input( INPUT_POST, 'vibes_outbound_options_level' ) : Option::network_get( 'vibes_outbound_options_level' ) );
				Option::network_set( 'history', array_key_exists( 'vibes_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'vibes_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				$message = esc_html__( 'Plugin settings have been saved.', 'vibes' );
				$code    = 0;
				add_settings_error( 'vibes_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'vibes' );
				$code    = 2;
				add_settings_error( 'vibes_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'vibes-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'vibes' );
				$code    = 0;
				add_settings_error( 'vibes_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->info( 'Plugin settings reset to defaults.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'vibes' );
				$code    = 2;
				add_settings_error( 'vibes_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( 'Plugin settings not reset to defaults.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'vibes_plugin_options_favicons',
			__( 'Favicons', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text'        => esc_html__( 'Download and display', 'vibes' ),
				'id'          => 'vibes_plugin_options_favicons',
				'checked'     => Option::network_get( 'download_favicons' ),
				'description' => esc_html__( 'If checked, Vibes will download favicons of websites to display them in reports.', 'vibes' ) . '<br/>' . esc_html__( 'Note: This feature uses the (free) Google Favicon Service.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_favicons' );
		$geo_ip = new GeoIP();
		if ( $geo_ip->is_installed() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'vibes' ), '<em>' . $geo_ip->get_full_name() . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any IP geographic information plugin. To take advantage of the geographical distribution of calls in Vibes, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'vibes' ), '<a href="https://wordpress.org/plugins/ip-locator/">IP Locator</a>' );
		}
		add_settings_field(
			'vibes_plugin_options_geoip',
			__( 'IP information', 'vibes' ),
			[ $form, 'echo_field_simple_text' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_geoip' );

		if ( \DecaLog\Engine::isDecalogActivated() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'vibes' ), '<em>' . \DecaLog\Engine::getVersionString() . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any logging plugin. To log all events triggered in Vibes, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'vibes' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'vibes_plugin_options_logger',
			__( 'Logging', 'vibes' ),
			[ $form, 'echo_field_simple_text' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_logger' );
		if ( SharedMemory::$available ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= esc_html__( 'Shared memory is available on your server: you can use live console.', 'vibes' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Shared memory is not available on your server. To use live console you must activate %s PHP module.', 'vibes' ), '<code>shmop</code>' );
		}
		add_settings_field(
			'vibes_plugin_options_shmop',
			__( 'Shared memory', 'vibes' ),
			[ $form, 'echo_field_simple_text' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_shmop' );
		add_settings_field(
			'vibes_plugin_options_usecdn',
			__( 'Resources', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'vibes' ),
				'id'          => 'vibes_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, Vibes will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_usecdn' );
		add_settings_field(
			'vibes_plugin_options_nag',
			__( 'Admin notices', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_plugin_options_section',
			'vibes_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'vibes' ),
				'id'          => 'vibes_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows Vibes to display admin notices throughout the admin dashboard.', 'vibes' ) . '<br/>' . esc_html__( 'Note: Vibes respects DISABLE_NAG_NOTICES flag.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_plugin_options_section', 'vibes_plugin_options_nag' );
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'vibes_plugin_features_history',
			esc_html__( 'Historical data', 'vibes' ),
			[ $form, 'echo_field_select' ],
			'vibes_plugin_features_section',
			'vibes_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'vibes_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_plugin_features_section', 'vibes_plugin_features_history' );
		add_settings_field(
			'vibes_plugin_features_metrics',
			esc_html__( 'Metrics', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_plugin_features_section',
			'vibes_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'vibes' ),
				'id'          => 'vibes_plugin_features_metrics',
				'checked'     => \DecaLog\Engine::isDecalogActivated() ? Option::network_get( 'metrics' ) : false,
				'description' => esc_html__( 'If checked, Vibes will collate and publish API metrics.', 'vibes' ) . ( \DecaLog\Engine::isDecalogActivated() ? '' : '<br/>' . esc_html__( 'Note: for this to work, you must install DecaLog.', 'vibes' ) ),
				'full_width'  => false,
				'enabled'     => \DecaLog\Engine::isDecalogActivated(),
			]
		);
		register_setting( 'vibes_plugin_features_section', 'vibes_plugin_features_metrics' );
		if ( SharedMemory::$available ) {
			add_settings_field(
				'vibes_plugin_features_livelog',
				__( 'Live console', 'vibes' ),
				[ $form, 'echo_field_checkbox' ],
				'vibes_plugin_features_section',
				'vibes_plugin_features_section',
				[
					'text'        => esc_html__( 'Activate monitoring', 'vibes' ),
					'id'          => 'vibes_plugin_features_livelog',
					'checked'     => Memory::is_enabled(),
					'description' => esc_html__( 'If checked, Vibes will silently start the features needed by live console.', 'vibes' ),
					'full_width'  => false,
					'enabled'     => true,
				]
			);
			register_setting( 'vibes_plugin_features_section', 'vibes_plugin_features_livelog' );
		}
		add_settings_field(
			'vibes_plugin_features_smart_filter',
			__( 'Smart filter', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_plugin_features_section',
			'vibes_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'vibes' ),
				'id'          => 'vibes_plugin_features_smart_filter',
				'checked'     => Option::network_get( 'smart_filter' ),
				'description' => esc_html__( 'If checked, Vibes will not take into account the calls that generate "noise" in monitoring.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_plugin_features_section', 'vibes_plugin_features_smart_filter' );
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  1.0.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'vibes' ), $i ) ) ];
		}
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 365 * $i ), esc_html( sprintf( _n( '%d year', '%d years', $i, 'vibes' ), $i ) ) ];
		}
		return $result;
	}
	/**
	 * Get the available levels.
	 *
	 * @return array An array containing the levels.
	 * @since  2.0.0
	 */
	protected function get_levels_array() {
		$result      = [];
		$log_enabled = defined( 'DECALOG_VERSION' ) && class_exists( '\Decalog\Logger' );
		foreach ( [ 'debug', 'info', 'notice', 'warning' ] as $level ) {
			if ( $log_enabled ) {
				$result[] = [ $level, strtoupper( $level ) ];
			} else {
				$result[] = [ $level, 'N/A' ];
			}
		}
		return $result;
	}

	/**
	 * Callback for inbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function inbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'vibes_inbound_options_capture',
			__( 'Analytics', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_inbound_options_section',
			'vibes_inbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'vibes' ),
				'id'          => 'vibes_inbound_options_capture',
				'checked'     => Option::network_get( 'inbound_capture' ),
				'description' => esc_html__( 'If checked, Vibes will analyze inbound API calls (the calls made by external sites or apps to your site).', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_inbound_options_section', 'vibes_inbound_options_capture' );
		$log_enabled = defined( 'DECALOG_VERSION' ) && class_exists( '\Decalog\Logger' );
		$sup         = '';
		if ( ! $log_enabled ) {
			$sup = '<br/>' . sprintf( esc_html__( 'Note: you need to install %s to use this feature.', 'vibes' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'vibes_inbound_options_level',
			esc_html__( 'Logging', 'vibes' ),
			[ $form, 'echo_field_select' ],
			'vibes_inbound_options_section',
			'vibes_inbound_options_section',
			[
				'list'        => $this->get_levels_array(),
				'id'          => 'vibes_inbound_options_level',
				'value'       => Option::network_get( 'inbound_level' ),
				'description' => esc_html__( 'The level at which inbound API calls are logged.', 'vibes' ) . $sup,
				'full_width'  => false,
				'enabled'     => $log_enabled,
			]
		);
		register_setting( 'vibes_inbound_options_section', 'vibes_inbound_options_level' );
		add_settings_field(
			'vibes_inbound_options_cut_path',
			__( 'Path cut', 'vibes' ),
			[ $form, 'echo_field_input_integer' ],
			'vibes_inbound_options_section',
			'vibes_inbound_options_section',
			[
				'id'          => 'vibes_inbound_options_cut_path',
				'value'       => Option::network_get( 'inbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_inbound_options_section', 'vibes_inbound_options_cut_path' );
	}

	/**
	 * Callback for outbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function outbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'vibes_outbound_options_capture',
			__( 'Analytics', 'vibes' ),
			[ $form, 'echo_field_checkbox' ],
			'vibes_outbound_options_section',
			'vibes_outbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'vibes' ),
				'id'          => 'vibes_outbound_options_capture',
				'checked'     => Option::network_get( 'outbound_capture' ),
				'description' => esc_html__( 'If checked, Vibes will analyze outbound API calls (the calls made by your site to external services).', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_outbound_options_section', 'vibes_outbound_options_capture' );
		$log_enabled = defined( 'DECALOG_VERSION' ) && class_exists( '\Decalog\Logger' );
		$sup         = '';
		if ( ! $log_enabled ) {
			$sup = '<br/>' . sprintf( esc_html__( 'Note: you need to install %s to use this feature.', 'vibes' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'vibes_outbound_options_level',
			esc_html__( 'Logging', 'vibes' ),
			[ $form, 'echo_field_select' ],
			'vibes_outbound_options_section',
			'vibes_outbound_options_section',
			[
				'list'        => $this->get_levels_array(),
				'id'          => 'vibes_outbound_options_level',
				'value'       => Option::network_get( 'outbound_level' ),
				'description' => esc_html__( 'The level at which outbound API calls are logged.', 'vibes' ) . $sup,
				'full_width'  => false,
				'enabled'     => $log_enabled,
			]
		);
		register_setting( 'vibes_outbound_options_section', 'vibes_outbound_options_level' );
		add_settings_field(
			'vibes_outbound_options_cut_path',
			__( 'Path cut', 'vibes' ),
			[ $form, 'echo_field_input_integer' ],
			'vibes_outbound_options_section',
			'vibes_outbound_options_section',
			[
				'id'          => 'vibes_outbound_options_cut_path',
				'value'       => Option::network_get( 'outbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'vibes' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'vibes_outbound_options_section', 'vibes_outbound_options_cut_path' );
	}

}
