<?php
/**
 * Vibes schema
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin\Feature;

use DecaLog\Engine;
use Vibes\System\Blog;
use Vibes\System\Option;
use Vibes\System\Database;
use Vibes\System\Device;
use Vibes\System\WebVitals;
use Vibes\System\BrowserPerformance;
use Vibes\System\Http;
use Vibes\System\Favicon;
use Vibes\System\Cache;
use Vibes\System\Mime;

/**
 * Define the schema functionality.
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Schema {

	/**
	 * Statistics table name.
	 *
	 * @since  1.0.0
	 * @var    string    $statistics    The statistics table name.
	 */
	private static $statistics = VIBES_SLUG . '_statistics';

	/**
	 * Resources table name.
	 *
	 * @since  1.0.0
	 * @var    string    $resources    The resources table name.
	 */
	private static $resources = VIBES_SLUG . '_resources';

	/**
	 * Statistics buffer.
	 *
	 * @since  1.0.0
	 * @var    array    $statistics_buffer    The statistics buffer.
	 */
	private static $statistics_buffer = [];

	/**
	 * The list of standard fields.
	 *
	 * @since  1.0.0
	 * @var    array    $standard_fields    Maintains the standard fields list.
	 */
	public static $standard_fields = [ 'timestamp', 'site', 'id', 'scheme', 'mime', 'category', 'endpoint', 'authent', 'country', 'class', 'device', 'authority' ];

	/**
	 * The list of fields to delete.
	 *
	 * @since  1.0.0
	 * @var    array    $deletable_fields    Maintains the deletable standard fields list.
	 */
	public static $deletable_fields = [
		'navigation' => [ 'authority', 'initiator', 'id', 'scheme', 'mime', 'category' ],
		'resource'   => [ 'country', 'class', 'device', 'authent' ],
		'webvital'   => [ 'authority', 'initiator', 'id', 'scheme', 'mime', 'category' ],
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'shutdown', [ 'Vibes\Plugin\Feature\Schema', 'write' ], DECALOG_MAX_SHUTDOWN_PRIORITY, 0 );
	}

	/**
	 * Write all buffers to database.
	 *
	 * @since    1.0.0
	 */
	public static function write() {
		foreach ( self::$statistics_buffer as $record ) {
			self::write_statistics_records_to_database( $record );
		}
		self::purge();
	}

	/**
	 * Effectively write a buffer element in the database.
	 *
	 * @param   array $record     The record to write.
	 * @since    1.0.0
	 */
	private static function write_statistics_records_to_database( $record ) {
		if ( array_key_exists( 'type', $record ) ) {
			$type = $record['type'];
			unset( $record['type'] );
		} else {
			return;
		}
		if ( array_key_exists( 'host', $record ) ) {
			if ( 'invalid' === $record['host'] ) {
				\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'Invalid record.' );
				return;
			}
		}
		if ( array_key_exists( $type, self::$deletable_fields ) ) {
			foreach ( self::$deletable_fields[ $type ] as $field ) {
				if ( array_key_exists( $field, $record ) ) {
					unset( $record[ $field ] );
				}
			}
		}
		$field_insert = [];
		$value_insert = [];
		$value_update = [];
		foreach ( self::$standard_fields as $field ) {
			if ( array_key_exists( $field, $record ) ) {
				$field_insert[] = '`' . $field . '`';
				$value_insert[] = "'" . $record[ $field ] . "'";
			}
		}
		if ( array_key_exists( 'initiator', $record ) ) {
			$field_insert[] = '`initiator`';
			$value_insert[] = "'" . $record['initiator'] . "'";
		}
		foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics, BrowserPerformance::$unrated_metrics ) as $metric ) {
			foreach ( [ 'sum', 'good', 'impr', 'poor', 'hit' ] as $cmp ) {
				$field = $metric . '_' . $cmp;
				if ( array_key_exists( $field, $record ) ) {
					$field_insert[] = '`' . $field . '`';
					$value_insert[] = "'" . (int) $record[ $field ] . "'";
					$value_update[] = '`' . $field . '`=' . $field . '+' . (int) $record[ $field ];
				}
			}
		}
		foreach ( BrowserPerformance::$spans as $metric ) {
			foreach ( [ 'start', 'duration' ] as $cmp ) {
				$field = 'span_' . $metric . '_' . $cmp;
				if ( array_key_exists( $field, $record ) ) {
					$field_insert[] = '`' . $field . '`';
					$value_insert[] = "'" . (int) $record[ $field ] . "'";
					$value_update[] = '`' . $field . '`=' . $field . '+' . (int) $record[ $field ];
				}
			}
		}
		if ( array_key_exists( 'hit', $record ) ) {
			$field_insert[] = '`hit`';
			$value_insert[] = "'" . (int) $record['hit'] . "'";
			$value_update[] = '`hit`=hit+' . (int) $record['hit'];
		}
		if ( count( $field_insert ) === count( $value_insert ) && 0 < count( $value_update ) ) {
			global $wpdb;
			if ( 'resource' === $type ) {
				$sql = 'INSERT INTO `' . $wpdb->base_prefix . self::$resources . '` ';
			} else {
				$sql = 'INSERT INTO `' . $wpdb->base_prefix . self::$statistics . '` ';
			}
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Store statistics in buffer.
	 *
	 * @param   array $record     The record to bufferize.
	 * @since    1.0.0
	 */
	public static function store_statistics( $record ) {
		self::$statistics_buffer[] = $record;
	}

	/**
	 * Initialize the schema.
	 *
	 * @since    1.1.0
	 */
	public function initialize() {
		global $wpdb;
		try {
			$this->create_tables( 'created' );
			Option::network_set( 'capture', true );
			Option::network_set( 'rcapture', true );
		} catch ( \Throwable $e ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->alert( sprintf( 'Unable to create a table: %s', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->alert( 'Schema not installed.', [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Update the schema.
	 *
	 * @since    1.1.0
	 */
	public function update() {
		global $wpdb;
		try {
			$this->create_tables( 'updated' );
		} catch ( \Throwable $e ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->alert( sprintf( 'Unable to update "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), [ 'code' => $e->getCode() ] );
		}
	}

	/**
	 * Purge old records.
	 *
	 * @since    1.0.0
	 */
	private static function purge() {
		$database = new Database();
		$days     = (int) Option::network_get( 'history' );
		if ( ! is_numeric( $days ) || 30 > $days ) {
			$days = 30;
			Option::network_set( 'history', $days );
		}
		$count = $database->purge( self::$statistics, 'timestamp', 24 * $days );
		$days  = (int) Option::network_get( 'rhistory' );
		if ( ! is_numeric( $days ) || 1 > $days ) {
			$days = 2;
			Option::network_set( 'rhistory', $days );
		}
		$count += $database->purge( self::$resources, 'timestamp', 24 * $days );
		if ( 0 === $count ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'No old records to delete.' );
		} elseif ( 1 === $count ) {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( '1 old record deleted.' );
			Cache::delete_global( 'data/oldestdate_statistices' );
		} else {
			\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( '%1$s old records deleted.', $count ) );
			Cache::delete_global( 'data/oldestdate_resources' );
		}
	}

	/**
	 * Get the performance subset.
	 *
	 * @return  string  The create subset query.
	 * @since    1.0.0
	 */
	private function browser_performance_subset() {
		$sql = " `hit` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		foreach ( BrowserPerformance::$spans as $span ) {
			foreach ( [ 'start', 'duration' ] as $field ) {
				$sql .= ' `span_' . $span . '_' . $field . "` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			}
		}
		foreach ( BrowserPerformance::$unrated_metrics as $metric ) {
			foreach ( [ 'sum' ] as $field ) {
				$sql .= ' `' . $metric . '_' . $field . "` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			}
		}
		return $sql;
	}

	/**
	 * Get the performance select.
	 *
	 * @return  array  The select items.
	 * @since    1.0.0
	 */
	private static function browser_performance_select() {
		$sql = [ 'sum(hit) as sum_hit' ];
		foreach ( BrowserPerformance::$spans as $span ) {
			foreach ( [ 'start', 'duration' ] as $field ) {
				$sql[] = 'sum(span_' . $span . '_' . $field . ')/sum(hit) as avg_span_' . $span . '_' . $field;
			}
		}
		$sql[] = 'sum(load_sum)/sum(hit) as avg_load';
		$sql[] = 'sum(redirects_sum)/sum(hit) as avg_redirects';
		$sql[] = 'if(0<>(sum(hit)-sum(cache_sum)),sum(size_sum)/(sum(hit)-sum(cache_sum)),0) as avg_size';
		$sql[] = 'sum(cache_sum)/sum(hit) as avg_cache';
		return $sql;
	}

	/**
	 * Get the web vitals subset.
	 *
	 * @return  string  The create subset query.
	 * @since    1.0.0
	 */
	private function webvitals_subset() {
		$sql = '';
		foreach ( WebVitals::$rated_metrics as $metric ) {
			foreach ( [ 'sum', 'good', 'impr', 'poor' ] as $field ) {
				$sql .= ' `' . $metric . '_' . $field . "` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			}
		}
		foreach ( WebVitals::$unrated_metrics as $metric ) {
			foreach ( [ 'sum', 'hit' ] as $field ) {
				$sql .= ' `' . $metric . '_' . $field . "` int(11) UNSIGNED NOT NULL DEFAULT '0',";
			}
		}
		return $sql;
	}

	/**
	 * Get the web vitals select.
	 *
	 * @return  array  The select items.
	 * @since    1.0.0
	 */
	private static function webvitals_select() {
		$sql = [];
		foreach ( WebVitals::$rated_metrics as $metric ) {
			$sql[] = 'sum(' . $metric . '_sum)/sum(' . $metric . '_good+' . $metric . '_impr+' . $metric . '_poor) as avg_' . $metric;
			$sql[] = 'sum(' . $metric . '_good+' . $metric . '_impr+' . $metric . '_poor) as hit_' . $metric;
			foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
				$sql[] = 'sum(' . $metric . '_' . $field . ')/sum(' . $metric . '_good+' . $metric . '_impr+' . $metric . '_poor) as pct_' . $metric . '_' . $field;
			}
		}
		foreach ( WebVitals::$unrated_metrics as $metric ) {
			$sql[] = 'sum(' . $metric . '_sum)/sum(' . $metric . '_hit) as avg_' . $metric;
			$sql[] = 'sum(' . $metric . '_hit) as hit_' . $metric;
		}
		return $sql;
	}

	/**
	 * Get the web vitals hits sum.
	 *
	 * @return  string  The sum definition.
	 * @since    1.0.0
	 */
	private static function webvitals_sum() {
		$sum = [];
		foreach ( WebVitals::$rated_metrics as $metric ) {
			foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
				$sum[] = 'sum(' . $metric . '_' . $field . ')';
			}
		}
		foreach ( WebVitals::$unrated_metrics as $metric ) {
			$sum[] = 'sum(' . $metric . '_hit' . ')';
		}
		return '(' . implode( '+', $sum ) . ')';
	}

	/**
	 * Create the table.
	 *
	 * @param   string  $text   The text to log.
	 * @since    1.0.0
	 */
	private function create_tables( $text ) {
		global $wpdb;
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$statistics;
		$sql            .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql            .= " `site` bigint(20) NOT NULL DEFAULT '0',";
		$sql            .= " `endpoint` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `authent` tinyint(1) DEFAULT '0',";
		$sql            .= " `country` varchar(2) DEFAULT '00',";
		$sql            .= " `class` enum('" . implode( "','", Device::$classes ) . "') NOT NULL DEFAULT 'other',";
		$sql            .= " `device` enum('" . implode( "','", Device::$observable ) . "') NOT NULL DEFAULT 'other',";
		$sql            .= self::webvitals_subset();
		$sql            .= self::browser_performance_subset();
		$sql            .= ' UNIQUE KEY u_stat (timestamp, site, endpoint, authent, country, device)';
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( 'Table "%s" %s.', $wpdb->base_prefix . self::$statistics, $text ) );

		$sql  = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$resources;
		$sql .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql .= " `site` bigint(20) NOT NULL DEFAULT '0',";
		$sql .= " `id` varchar(40) NOT NULL DEFAULT '-',";
		$sql .= " `scheme` enum('" . implode( "','", Http::$extended_schemes ) . "') NOT NULL DEFAULT 'unknown',";
		$sql .= " `authority` varchar(250) NOT NULL DEFAULT '-',";
		$sql .= " `category` enum('" . implode( "','", array_merge( Mime::$categories, Mime::$subcategories, [ 'unknown' ] ) ) . "') NOT NULL DEFAULT 'unknown',";
		$sql .= " `mime` varchar(90) NOT NULL DEFAULT '(unknown)',";
		$sql .= " `endpoint` varchar(250) NOT NULL DEFAULT '-',";
		$sql .= " `initiator` varchar(6) NOT NULL DEFAULT '-',";
		$sql .= self::browser_performance_subset();
		$sql .= ' UNIQUE KEY u_stat (timestamp, site, scheme, authority, mime, endpoint, initiator)';
		$sql .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( 'Table "%s" %s.', $wpdb->base_prefix . self::$resources, $text ) );

		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->info( sprintf( 'Schema %s.', $text ) );
	}

	/**
	 * Finalize the schema.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$statistics ) );
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$resources;
		// phpcs:ignore
		$wpdb->query( $sql );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$resources ) );
		\DecaLog\Engine::eventsLogger( VIBES_SLUG )->debug( 'Schema destroyed.' );
	}

	/**
	 * Get an empty record.
	 *
	 * @param   string  $type       The metrics type.
	 * @return  array   An empty, ready to use, record.
	 * @since    1.0.0
	 */
	public static function init_record( $type ) {
		switch ( $type ) {
			case 'webvital':
				$record = [
					'timestamp' => '0000-00-00',
					'site'      => 0,
					'endpoint'  => '-',
					'authent'   => 0,
					'country'   => '00',
					'class'     => 'other',
					'device'    => 'other',
					'type'      => '-',
				];
				break;
			default:
				$record = [
					'timestamp' => '0000-00-00',
					'site'      => 0,
					'authority' => '-',
					'endpoint'  => '-',
					'initiator' => '-',
				];
		}
		return $record;
	}

	/**
	 * Get "where" clause of a query.
	 *
	 * @param array $filters Optional. An array of filters.
	 * @return string The "where" clause.
	 * @since 1.0.0
	 */
	private static function get_where_clause( $filters = [] ) {
		$result = '';
		if ( 0 < count( $filters ) ) {
			$w = [];
			foreach ( $filters as $key => $filter ) {
				if ( is_array( $filter ) ) {
					$w[] = '`' . $key . '` IN (' . implode( ',', $filter ) . ')';
				} else {
					$w[] = '`' . $key . '`="' . $filter . '"';
				}
			}
			$result = 'WHERE (' . implode( ' AND ', $w ) . ')';
		}
		return $result;
	}

	/**
	 * Get the oldest date.
	 *
	 * @param   string  $source   The source of data.
	 * @return  string   The oldest timestamp in the statistics table.
	 * @since    1.0.0
	 */
	public static function get_oldest_date( $source ) {
		$result = Cache::get_global( 'data/' . $source . '/oldestdate' );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . ( 'resource' === $source ? self::$resources : self::$statistics ) . ' ORDER BY `timestamp` ASC LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) && array_key_exists( 'timestamp', $result[0] ) ) {
			Cache::set_global( 'data/' . $source . '/oldestdate', $result[0]['timestamp'], 'infinite' );
			return $result[0]['timestamp'];
		}
		return '';
	}

	/**
	 * Get the authority.
	 *
	 * @param   string  $source   The source of data.
	 * @param   array   $filter   The filter of the query.
	 * @return  string   The authority.
	 * @since    1.0.0
	 */
	public static function get_authority( $source, $filter ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . $source . serialize( $filter ) );
		$result = Cache::get_global( $id );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT authority FROM ' . $wpdb->base_prefix . ( 'resource' === $source ? self::$resources : self::$statistics ) . ' WHERE (' . implode( ' AND ', $filter ) . ') LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			$authority = $result[0]['authority'];
			Cache::set_global( $id, $authority, 'infinite' );
			return $authority;
		}
		return '';
	}

	/**
	 * Get the distinct countries.
	 *
	 * @param   string  $source     The source of data.
	 * @param   array   $filter     The filter of the query.
	 * @param   boolean $cache      Optional. Has this query to be cached.
	 * @return  array   The distinct countries.
	 * @since    1.0.0
	 */
	public static function get_distinct_countries( $source, $filter, $cache = true ) {
		if ( array_key_exists( 'country', $filter ) ) {
			unset( $filter['country'] );
		}
		$q = (int) Option::network_get( 'quality' );
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . $source . serialize( $filter ) );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		global $wpdb;
		$sql = 'SELECT sum(hit) as sum_hit, country FROM ' . $wpdb->base_prefix . ( 'resource' === $source ? self::$resources : self::$statistics ) . ' WHERE (' . implode( ' AND ', $filter ) . ') GROUP BY country';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			$contexts = [];
			foreach ( $result as $item ) {
				if ( $q < $item['sum_hit'] ) {
					$contexts[] = $item['country'];
				}
			}
			if ( $cache ) {
				Cache::set_global( $id, $contexts, 'infinite' );
			}
			return $contexts;
		}
		return [];
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @return  array   The standard KPIs.
	 * @since    1.0.0
	 */
	public static function get_std_kpi( $filter, $cache = true, $extra_field = '', $extras = [], $not = false ) {
		if ( array_key_exists( 'context', $filter ) ) {
			unset( $filter['context'] );
		}
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') );
		$result = Cache::get_global( $id );
		if ( $result ) {
			return $result;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT sum(hit) as sum_hit, sum(kb_in) as sum_kb_in, sum(kb_out) as sum_kb_out, sum(hit*latency_avg)/sum(hit) as avg_latency, min(latency_min) as min_latency, max(latency_max) as max_latency FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ') ' . $where_extra;
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 1 === count( $result ) ) {
			Cache::set_global( $id, $result[0], $cache ? 'infinite' : 'ephemeral' );
			return $result[0];
		}
		return [];
	}

	/**
	 * Get a time series.
	 *
	 * @param   string  $source      The source of data.
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The time series.
	 * @since    1.0.0
	 */
	public static function get_time_series( $source, $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $limit = 0 ) {
		$data   = self::get_grouped_list( $source, 'timestamp', [], $filter, $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
		$result = [];
		foreach ( $data as $datum ) {
			$result[ $datum['timestamp'] ] = $datum;
		}
		return $result;
	}

	/**
	 * Get a grouped list.
	 *
	 * @param   string  $source      The source of data.
	 * @param   string  $group       The group of the query.
	 * @param   array   $count       The sub-groups of the query.
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   string  $order       Optional. The sort order of results.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @param   integer $quality     Optional. Min hit number.
	 * @return  array   The grouped list.
	 * @since    1.0.0
	 */
	public static function get_grouped_list( $source, $group, $count, $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $order = '', $limit = 0, $quality = 0 ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . $source . $group . serialize( $count ) . serialize( $filter ) . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') . $order . (string) $limit);
		$result = Cache::get_global( $id );
		if ( $result ) {
			return $result;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra .= ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		$sel = [];
		switch ( $source ) {
			case 'resource':
				$sel = array_merge( self::browser_performance_select(), [ 'timestamp', 'site', 'id', 'scheme', 'category', 'mime', 'endpoint', 'authority', 'initiator' ] );
				if ( 1 < $quality ) {
					$group .= ' HAVING sum(hit) > ' . $quality;
				}
				break;
			case 'navigation':
				$sel = array_merge( self::browser_performance_select(), [ 'timestamp', 'site', 'endpoint', 'authent', 'country', 'class', 'device' ] );
				if ( 1 < $quality ) {
					$group .= ' HAVING sum(hit) > ' . $quality;
				}
				break;
			case 'webvital':
				$sel = array_merge( self::webvitals_select(), [ 'timestamp', 'site', 'endpoint', 'authent', 'country', 'class', 'device' ] );
				if ( 1 < $quality ) {
					$group .= ' HAVING ' . self::webvitals_sum() . ' > ' . $quality;
				}
				break;
		}
		foreach ( $count as $c ) {
			$sel[] = 'count(distinct(' . $c . ')) as cnt_' . $c;
		}
		global $wpdb;
		$sql  = 'SELECT ' . implode( ', ', $sel ) . ' FROM ';
		$sql .= $wpdb->base_prefix . ( 'resource' === $source ? self::$resources : self::$statistics ) . ' WHERE (' . implode( ' AND ', $filter ) . ') ' . $where_extra . ' GROUP BY ' . $group . ' ' . $order . ( $limit > 0 ? 'LIMIT ' . $limit : '' ) . ';';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			Cache::set_global( $id, $result, $cache ? 'infinite' : 'ephemeral' );
			return $result;
		}
		return [];
	}

	/**
	 * Get a cache ration.
	 *
	 * @param   string  $source      The source of data.
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @return  array   The cache ratio.
	 * @since    1.0.0
	 */
	public static function get_cache_ratio( $source, $filter, $cache = true ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . $source . serialize( $filter ) );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		$sel = [ 'sum(hit) as sum_hit', 'sum(cache_sum)/sum(hit) as avg_cache' ];
		switch ( $source ) {
			case 'resource':
				$sel = array_merge( $sel, [ 'timestamp', 'site', 'id', 'scheme', 'category', 'mime', 'endpoint', 'authority', 'initiator' ] );
				break;
			case 'navigation':
				$sel = array_merge( $sel, [ 'timestamp', 'site', 'endpoint', 'authent', 'country', 'class', 'device' ] );
				break;
		}
		global $wpdb;
		$sql  = 'SELECT ' . implode( ', ', $sel ) . ' FROM ';
		$sql .= $wpdb->base_prefix . ( 'resource' === $source ? self::$resources : self::$statistics ) . ' WHERE (' . implode( ' AND ', $filter ) . ');';
		// phpcs:ignore
		$query = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $query ) && 0 < count( $query ) ) {
			$q = $query[0];
			$v = 0;
			if ( array_key_exists( 'avg_cache', $q ) ) {
				$v = $q['avg_cache'];
			}
			unset( $q['avg_cache'] );
			$result       = [];
			$q['cache']   = 'Hit';
			$q['sum_hit'] = $v;
			$result[]     = $q;
			$q['cache']   = 'Miss';
			$q['sum_hit'] = 1 - $v;
			$result[]     = $q;
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}
}

Schema::init();
