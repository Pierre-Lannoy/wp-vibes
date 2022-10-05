<?php
/**
 * WP-CLI for Vibes.
 *
 * Adds WP-CLI commands to Vibes
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin\Feature;

use Vibes\Plugin\Feature\Memory;
use Vibes\System\BrowserPerformance;
use Vibes\System\Cache;
use Vibes\System\Conversion;
use Vibes\System\WebVitals;
use Vibes\System\Date;
use Vibes\System\Device;
use Vibes\System\EmojiFlag;
use Vibes\System\Environment;
use Vibes\System\Markdown;
use Vibes\System\Option;
use Vibes\System\GeoIP;
use Vibes\System\Timezone;
use Vibes\System\UUID;
use Vibes\System\Http;
use Vibes\System\SharedMemory;
use Vibes\System\Mime;

/**
 * Manages Vibes and view current and past performance signals.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Wpcli {

	/**
	 * List of color format per bound.
	 *
	 * @since    1.0.0
	 * @var array $level_color Level colors.
	 */
	private $level_color = [
		'standard' =>
			[
				'webvital'   => '%4%c',
				'navigation' => '%2%k',
				'resource'   => '%5%w',
			],
		'soft'     =>
			[
				'webvital'   => '%0%c',
				'navigation' => '%0%g',
				'resource'   => '%0%m',
			],
	];

	/**
	 * List of exit codes.
	 *
	 * @since    1.0.0
	 * @var array $exit_codes Exit codes.
	 */
	private $exit_codes = [
		0   => 'operation successful.',
		1   => 'unrecognized setting.',
		2   => 'unrecognized action.',
		255 => 'unknown error.',
	];

	/**
	 * Flush output without warnings.
	 *
	 * @since    1.0.0
	 */
	private function flush() {
		// phpcs:ignore
		set_error_handler( null );
		// phpcs:ignore
		@ob_flush();
		// phpcs:ignore
		restore_error_handler();
	}

	/**
	 * Write ids as clean stdout.
	 *
	 * @param   array   $ids   The ids.
	 * @param   string  $field  Optional. The field to output.
	 * @since   1.0.0
	 */
	private function write_ids( $ids, $field = '' ) {
		$result = '';
		$last   = end( $ids );
		foreach ( $ids as $key => $id ) {
			if ( '' === $field ) {
				$result .= $key;
			} else {
				$result .= $id[ $field ];
			}
			if ( $id !== $last ) {
				$result .= ' ';
			}
		}
		// phpcs:ignore
		fwrite( STDOUT, $result );
	}

	/**
	 * Write an error.
	 *
	 * @param   integer  $code      Optional. The error code.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function error( $code = 255, $stdout = false ) {
		$msg = '[' . VIBES_PRODUCT_NAME . '] ' . ucfirst( $this->exit_codes[ $code ] );
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( $code );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, $msg );
			// phpcs:ignore
			exit( $code );
		} else {
			\WP_CLI::error( $msg );
		}
	}

	/**
	 * Write a warning.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function warning( $msg, $result = '', $stdout = false ) {
		$msg = '[' . VIBES_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::warning( $msg );
		}
	}

	/**
	 * Write a success.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function success( $msg, $result = '', $stdout = false ) {
		$msg = '[' . VIBES_PRODUCT_NAME . '] ' . ucfirst( $msg );
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::success( $msg );
		}
	}

	/**
	 * Write a wimple line.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function line( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::line( $msg );
		}
	}

	/**
	 * Write a wimple log line.
	 *
	 * @param   string   $msg       The message.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   1.0.0
	 */
	private function log( $msg, $stdout = false ) {
		if ( ! \WP_CLI\Utils\isPiped() && ! $stdout ) {
			\WP_CLI::log( $msg );
		}
	}

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   1.0.0
	 */
	private function get_params( $args ) {
		$result = '';
		if ( array_key_exists( 'settings', $args ) ) {
			$result = \json_decode( $args['settings'], true );
		}
		if ( ! $result || ! is_array( $result ) ) {
			$result = [];
		}
		return $result;
	}

	/**
	 * Filters records.
	 *
	 * @param array $records The records to filter.
	 * @param array $filters Optional. The filter to apply.
	 * @param string $index Optional. The starting index.
	 *
	 * @return  array   The filtered records.
	 * @since   1.0.0
	 */
	public static function records_filter( $records, $filters = [], $index = '' ) {
		$result = [];
		foreach ( $records as $idx => $record ) {
			foreach ( $filters as $key => $filter ) {
				if ( ! preg_match( $filter, $record[ $key ] ) ) {
					continue 2;
				}
			}
			$result[ $idx ] = $record;
		}
		if ( '' !== $index ) {
			$tmp = [];
			foreach ( $result as $key => $record ) {
				if ( 0 < strcmp( $key, $index ) ) {
					$tmp[ $key ] = $record;
				}
			}
			$result = $tmp;
		}
		uksort( $result, 'strcmp' );
		return $result;
	}

	/**
	 * Format records records.
	 *
	 * @param array   $records    The records to display.
	 * @param integer $pad Optional. Line padding.
	 *
	 * @return  array   The ready to print records.
	 * @since   1.0.0
	 */
	public static function records_format( $records, $pad = 160 ) {
		$result = [];
		$geoip  = new GeoIP();
		foreach ( $records as $idx => $record ) {
			if ( $geoip->is_installed() ) {
				$country = EmojiFlag::get( $record['country'] );
			} else {
				$country = ' ';
			}
			$line  = '[' . $record['timestamp'] . '] ';
			$line .= strtoupper( str_pad( $record['type'], 10 ) ) . ' ';
			$line .= $country . ' ';
			$line .= vibes_mb_str_pad( Device::get_icon( $record['class'] ) . ' ', ( 'Desktop' === Device::get_id_name( $record['class'] ) && 1 === Environment::exec_mode() ) ? 4 : 2 ) . strtoupper( str_pad( Device::get_id_name( $record['class'] ), 7 ) . ' ' );
			if ( 'webvital' === $record['type'] ) {
				$line .= WebVitals::get_info_line( $record ) . ' ';
				$line .= $record['endpoint'];
			} elseif ( 'navigation' === $record['type'] || 'resource' === $record['type'] ) {
				$line .= BrowserPerformance::get_info_line( $record ) . ' ';
			}
			$line = preg_replace( '/[\x00-\x1F\x7F\xA0]/u', '', $line );
			if ( $pad - 1 < strlen( $line ) ) {
				$line = substr( $line, 0, $pad - 1 ) . '…';
			}
			$result[ $idx ] = [
				'type' => strtolower( $record['type'] ),
				'line' => vibes_mb_str_pad( $line, $pad ),
			];
		}
		return $result;
	}

	/**
	 * Displays records.
	 *
	 * @param   array   $records    The records to display.
	 * @param   string  $theme      Optional. Colors scheme.
	 * @param   integer $pad        Optional. Line padding.
	 * @since   1.0.0
	 */
	private function records_display( $records, $theme = 'standard', $pad = 160 ) {
		if ( ! array_key_exists( $theme, $this->level_color ) ) {
			$theme = 'standard';
		}
		foreach ( self::records_format( $records, $pad ) as $record ) {
			\WP_CLI::line( \WP_CLI::colorize( $this->level_color[ $theme ][ strtolower( $record['type'] ) ] ) . $record['line'] . \WP_CLI::colorize( '%n' ) );
		}
	}

	/**
	 * Get Vibes details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp vibes status
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-vibes/blob/master/WP-CLI.md ===
	 *
	 */
	public function status( $args, $assoc_args ) {
		\WP_CLI::line( sprintf( '%s is running.', Environment::plugin_version_text() ) );
		if ( Option::network_get( 'capture' ) ) {
			\WP_CLI::line( 'Navigation analytics: enabled.' );
		} else {
			\WP_CLI::line( 'Navigation analytics: disabled.' );
		}
		if ( Option::network_get( 'rcapture' ) ) {
			\WP_CLI::line( 'Resources analytics: enabled.' );
		} else {
			\WP_CLI::line( 'Resources analytics: disabled.' );
		}
		if ( Option::network_get( 'livelog' ) ) {
			\WP_CLI::line( 'Auto-Monitoring: enabled.' );
		} else {
			\WP_CLI::line( 'Auto-Monitoring: disabled.' );
		}
		if ( Option::network_get( 'metrics' ) ) {
			\WP_CLI::line( 'Metrics collation: enabled.' );
		} else {
			\WP_CLI::line( 'Metrics collation: disabled.' );
		}
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			\WP_CLI::line( 'Logging support: ' . \DecaLog\Engine::getVersionString() . '.' );
		} else {
			\WP_CLI::line( 'Logging support: no.' );
		}
		$geo = new GeoIP();
		if ( $geo->is_installed() ) {
			\WP_CLI::line( 'IP information support: yes (' . $geo->get_full_name() . ').' );
		} else {
			\WP_CLI::line( 'IP information support: no.' );
		}
		if ( defined( 'PODD_VERSION' ) ) {
			\WP_CLI::line( 'Device detection support: yes (Device Detector v' . PODD_VERSION . ').');
		} else {
			\WP_CLI::line( 'Device detection support: no.' );
		}
		if ( SharedMemory::$available ) {
			\WP_CLI::line( 'Shared memory support: yes (shmop v' . phpversion( 'shmop' ) . ').' );
		} else {
			\WP_CLI::line( 'Shared memory support: no.' );
		}
		\WP_CLI::line( 'MIME types support: yes (pooMT v' . VIBES_MIME_VERSION . ').' );
	}

	/**
	 * Modify Vibes main settings.
	 *
	 * ## OPTIONS
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <navigation-analytics|resource-analytics|auto-monitoring|smart-filter|metrics>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by Vibes.
	 *
	 * ## EXAMPLES
	 *
	 * wp vibes settings enable auto-monitoring
	 * wp vibes settings disable early-monitoring --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-vibes/blob/master/WP-CLI.md ===
	 *
	 */
	public function settings( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'navigation-analytics':
						Option::network_set( 'capture', true );
						$this->success( 'navigation analytics are now activated.', '', $stdout );
						break;
					case 'resource-analytics':
						Option::network_set( 'rcapture', true );
						$this->success( 'resources analytics are now activated.', '', $stdout );
						break;
					case 'auto-monitoring':
						Option::network_set( 'livelog', true );
						$this->success( 'auto-monitoring is now activated.', '', $stdout );
						break;
					case 'smart-filter':
						Option::network_set( 'smart_filter', true );
						$this->success( 'smart filter is now activated.', '', $stdout );
						break;
					case 'metrics':
						Option::network_set( 'metrics', true );
						$this->success( 'metrics collation is now activated.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'navigation-analytics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate navigation analytic?', $assoc_args );
						Option::network_set( 'capture', false );
						$this->success( 'navigation analytics are now deactivated.', '', $stdout );
						break;
					case 'resource-analytics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate resources analytic?', $assoc_args );
						Option::network_set( 'rcapture', false );
						$this->success( 'resources analytics are now deactivated.', '', $stdout );
						break;
					case 'auto-monitoring':
						\WP_CLI::confirm( 'Are you sure you want to deactivate auto-monitoring?', $assoc_args );
						Option::network_set( 'livelog', false );
						$this->success( 'auto-monitoring is now deactivated.', '', $stdout );
						break;
					case 'smart-filter':
						\WP_CLI::confirm( 'Are you sure you want to deactivate smart filter?', $assoc_args );
						Option::network_set( 'smart_filter', false );
						$this->success( 'smart filter is now deactivated.', '', $stdout );
						break;
					case 'metrics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate metrics collation?', $assoc_args );
						Option::network_set( 'metrics', false );
						$this->success( 'metrics collation is now deactivated.', '', $stdout );
						break;
					default:
						$this->error( 1, $stdout );
				}
				break;
			default:
				$this->error( 2, $stdout );
		}
	}

	/**
	 * Get information on exit codes.
	 *
	 * ## OPTIONS
	 *
	 * <list>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing exit codes.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * Lists available exit codes:
	 * + wp vibes exitcode list
	 * + wp vibes exitcode list --format=json
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-vibes/blob/master/WP-CLI.md ===
	 *
	 */
	public function exitcode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		$codes  = [];
		foreach ( $this->exit_codes as $key => $msg ) {
			$codes[ $key ] = [
				'code'    => $key,
				'meaning' => ucfirst( $msg ),
			];
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					$this->write_ids( $codes );
				} else {
					\WP_CLI\Utils\format_items( $format, $codes, [ 'code', 'meaning' ] );
				}
				break;
		}
	}

	/**
	 * Display past or current performance signals.
	 *
	 * ## OPTIONS
	 *
	 * [<count>]
	 * : An integer value [1-60] indicating how many most recent signals to display. If 0 or nothing is supplied as value, a live session is launched, displaying signals as soon as they occur.
	 *
	 * [--signal=<signal_type>]
	 * : The signal type to display.
	 * ---
	 * default: all
	 * options:
	 *  - all
	 *  - navigation
	 *  - webvital
	 *  - resource
	 * ---
	 *
	 *[--filter=<filter>]
	 * : The misc. filters to apply. Show only signals matching the specified pattern.
	 * MUST be a json string containing pairs "field":"regexp".
	 * ---
	 * default: '{}'
	 * available fields: 'site' and 'endpoint'
	 * example: '{"endpoint":"/\/blog\/(.*)/"}'
	 * ---
	 *
	 * [--col=<columns>]
	 * : The Number of columns (char in a row) to display. Default is 160. Min is 80 and max is 400.
	 *
	 * [--theme=<theme>]
	 * : Modifies the colors scheme.
	 * ---
	 * default: standard
	 * options:
	 *  - standard
	 *  - soft
	 * ---
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## NOTES
	 *
	 * + This command needs shared memory support for PHP: the PHP module "shmop" must be activated in your PHP web configuration AND in your PHP command-line configuration.
	 * + This command relies on an internal monitor. If this monitor is not started at launch time, you will be prompted to starting it.
	 * + If the monitor has just been started there will not be much to display if <count> is different from 0...
	 * + In a live session, just use CTRL-C to terminate it.
	 *
	 * ## EXAMPLES
	 *
	 * wp vibes tail
	 * wp vibes tail 20
	 * wp vibes tail 20 --signal=navigation
	 * wp vibes tail --filter='{"endpoint":"/\/blog\/(.*)/"}'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-vibes/blob/master/WP-CLI.md ===
	 *
	 */
	public function tail( $args, $assoc_args ) {
		if ( ! function_exists( 'shmop_open' ) || ! function_exists( 'shmop_read' ) || ! function_exists( 'shmop_write' ) || ! function_exists( 'shmop_delete' ) ) {
			\WP_CLI::error( 'unable to launch tail command, no shared memory manager found.' );
		}
		if ( ! Option::network_get( 'livelog' ) ) {
			\WP_CLI::warning( 'monitoring is currently disabled. The tail command needs monitoring...' );
			\WP_CLI::confirm( 'Would you like to enable monitoring and to resume command?', $assoc_args );
			Option::network_set( 'livelog', true );
		}
		$filters = [];
		$count   = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( 0 > $count || 60 < $count ) {
			$count = 0;
		}
		$col = isset( $assoc_args['col'] ) ? (int) $assoc_args['col'] : 160;
		if ( 80 > $col ) {
			$col = 80;
		}
		if ( 400 < $col ) {
			$col = 400;
		}
		$filter = \json_decode( isset( $assoc_args['filter'] ) ? (string) $assoc_args['filter'] : '{}', true );
		if ( is_array( $filter ) ) {
			foreach ( [ 'site', 'endpoint' ] as $field ) {
				if ( array_key_exists( $field, $filter ) ) {
					$value = (string) $filter[ $field ];
					if ( '' === $value ) {
						continue;
					}
					$filters[ $field ] = $value;
				}
			}
		}
		$signal = isset( $assoc_args['signal'] ) ? (string) $assoc_args['signal'] : 'all';
		switch ( $signal ) {
			case 'navigation':
			case 'webvital':
			case 'resource':
				$filters['type'] = '/' . $signal . '/';
				break;
			default:
				if ( isset( $filters['type'] ) ) {
					unset( $filters['type'] );
				}
		}
		$records = Memory::read();
		if ( 0 === $count ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->notice( 'Live console launched.' );
			while ( true ) {
				$this->records_display( self::records_filter( Memory::read(), $filters ), $assoc_args['theme'] ?? 'standard', $col );
				$this->flush();
			}
		} else {
			$this->records_display( array_slice( self::records_filter( $records, $filters ), -$count ), $assoc_args['theme'] ?? 'standard', $col );
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'WP-CLI.md', $attributes );
	}

}

add_shortcode( 'vibes-wpcli', [ 'Vibes\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once VIBES_ASSETS_DIR . 'mime-types.php';
	\WP_CLI::add_command( 'vibes', 'Vibes\Plugin\Feature\Wpcli' );
}
