<?php
/**
 * shmop handling
 *
 * Handles all shmop operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\System;

use Decalog\System\Option;
use Decalog\System\File;

/**
 * Define the shmop functionality.
 *
 * Handles all shmop operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class SharedMemory {

	/**
	 * Is shmop module available?
	 *
	 * @since  2.0.0
	 * @var boolean $available Maintains availability of shmop module.
	 */
	public static $available = false;

	/**
	 * The system V id.
	 *
	 * @since  2.0.0
	 * @var integer $id    Maintains the system V id.
	 */
	private $id;

	/**
	 * The opened resource.
	 *
	 * @since  2.0.0
	 * @var resource    $shmid     Maintains the opened resource.
	 */
	private $shmid = null;

	/**
	 * The permissions to access the memory block.
	 *
	 * @since  2.0.0
	 * @var integer $id    Maintains the octal permission mask.
	 */
	private $perms = 0666;

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$available = ( function_exists( 'shmop_open' ) && function_exists( 'shmop_read' ) && function_exists( 'shmop_write' ) && function_exists( 'shmop_delete' ) );
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $id     The system V id.
	 * @since    2.0.0
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * Acquire a ressource.
	 *
	 * @param   string  $flags     Optional. The flags for opening.
	 * @param   integer $mode      Optional. The permissions needed.
	 * @param   integer $size      Optional. The size of opening.
	 * @return  null|resource   The opened resource, or null if it's not possible.
	 * @since    2.0.0
	 */
	private function acquire( $flags = 'a', $mode = 0, $size = 0 ) {
		if ( ! self::$available ) {
			return null;
		}
		$this->shmid = null;
		// phpcs:ignore
		set_error_handler( null );
		// phpcs:ignore
		$this->shmid = @shmop_open( $this->id, $flags, $mode, $size );
		// phpcs:ignore
		restore_error_handler();
		return $this->shmid;
	}

	/**
	 * Check if block exists.
	 *
	 * @return  boolean   True if the block already exists, false otherwise.
	 * @since    2.0.0
	 */
	private function exists() {
		$result = vibes_is_shmop_resource( $this->acquire() );
		if ( $result ) {
			vibes_shmop_close( $this->shmid );
		}
		return $result;
	}
	/**
	 * Writes an array.
	 *
	 * @param   array   $data   The data to write.
	 * @return  false|int       TThe number of written bytes, false if something went wrong.
	 * @since    2.0.0
	 */
	public function write( $data ) {
		if ( ! self::$available || ! is_array( $data ) ) {
			return 0;
		}
		$data = wp_json_encode( $data, true );
		$size = mb_strlen( $data, 'UTF-8' );
		if ( $this->exists() ) {
			$cpt = 0;
			while ( 20 > $cpt ) {
				$this->shmid = $this->acquire( 'w', $this->perms, 0 );
				if ( vibes_is_shmop_resource( $this->shmid ) ) {
					break;
				} else {
					$cpt++;
					usleep( 100 );
				}
			}
			if ( vibes_is_shmop_resource( $this->shmid ) ) {
				shmop_delete( $this->shmid );
				vibes_shmop_close( $this->shmid );
			} else {
				return false;
			}
		}
		$this->shmid = $this->acquire( 'c', $this->perms, $size );
		if ( vibes_is_shmop_resource( $this->shmid ) ) {
			$result = shmop_write( $this->shmid, $data, 0 );
			vibes_shmop_close( $this->shmid );
			return $result;
		}
		return false;
	}

	/**
	 * Reads an array.
	 *
	 * @return  array   The read data.
	 * @since    2.0.0
	 */
	public function read() {
		if ( ! self::$available ) {
			return [];
		}
		$data = '';
		if ( $this->exists() ) {
			$cpt = 0;
			while ( 20 > $cpt ) {
				$this->shmid = $this->acquire( 'w', $this->perms, 0 );
				if ( vibes_is_shmop_resource( $this->shmid ) ) {
					break;
				} else {
					$cpt++;
					usleep( 100 );
				}
			}
			if ( vibes_is_shmop_resource( $this->shmid ) ) {
				$size = shmop_size( $this->shmid );
				$data = shmop_read( $this->shmid, 0, $size );
				vibes_shmop_close( $this->shmid );
			} else {
				return [];
			}
		}
		if ( '' === (string) $data ) {
			$data = '{}';
		}
		$data = \json_decode( $data, true );
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		return $data;
	}

}

SharedMemory::init();
