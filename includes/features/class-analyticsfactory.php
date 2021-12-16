<?php
/**
 * Analytics factory
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin\Feature;

use Vibes\Plugin\Feature\Analytics;
use Vibes\System\Blog;
use Vibes\System\Date;
use Vibes\System\Timezone;

/**
 * Define the analytics factory functionality.
 *
 * Handles all analytics creation and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class AnalyticsFactory {

	/**
	 * Allowed types.
	 *
	 * @since  1.0.0
	 * @var    array    $allowed_types    Maintain the allowed types.
	 */
	private static $allowed_types = [ 'domain', 'domains', 'authority', 'authorities', 'endpoint', 'endpoints', 'devices' ];

	/**
	 * Ajax callback.
	 *
	 * @since    1.0.0
	 */
	public static function get_stats_callback() {
		check_ajax_referer( 'ajax_vibes', 'nonce' );
		$analytics = self::get_analytics( true );
		$query     = filter_input( INPUT_POST, 'query' );
		$queried   = filter_input( INPUT_POST, 'queried' );
		exit( wp_json_encode( $analytics->query( $query, $queried ) ) );
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @param   boolean $reload  Optional. Is it a reload of an already displayed analytics.
	 * @param   string  $source  Optional. The source of data.
	 * @since 1.0.0
	 */
	public static function get_analytics( $reload = false, $source = '' ) {
		$timezone = Timezone::network_get();
		// ID.
		if ( ! ( $id = filter_input( INPUT_GET, 'id' ) ) ) {
			$id = filter_input( INPUT_POST, 'id' );
		}
		if ( empty( $id ) ) {
			$id = '';
		}
		// Domain.
		if ( ! ( $domain = filter_input( INPUT_GET, 'domain' ) ) ) {
			$domain = filter_input( INPUT_POST, 'domain' );
		}
		if ( empty( $domain ) ) {
			$domain = '';
		}
		// Extra.
		if ( ! ( $extra = filter_input( INPUT_GET, 'extra' ) ) ) {
			$extra = filter_input( INPUT_POST, 'extra' );
		}
		if ( empty( $extra ) ) {
			$extra = '';
		}
		// Analytics source & type.
		if ( ! ( $type = filter_input( INPUT_GET, 'type' ) ) ) {
			$type = filter_input( INPUT_POST, 'type' );
		}
		if ( false !== strpos( $type, '.' ) ) {
			$source = substr( $type, 0, strpos( $type, '.' ) );
			$type   = str_replace( $source . '.', '', $type );
		}
		if ( 0 < strpos( $type, '_' ) ) {
			$type = substr( $type, strpos( $type, '_' ) + 1 );
		}
		if ( ! isset( $type ) || ! in_array( (string) $type, self::$allowed_types, true ) ) {
			$type = 'summary';
		}
		// Filters.
		if ( ! ( $site = filter_input( INPUT_GET, 'site' ) ) ) {
			$site = filter_input( INPUT_POST, 'site' );
		}
		if ( empty( $site ) || ! Blog::is_blog_exists( (int) $site ) ) {
			$site = 'all';
		}
		$authent = filter_input( INPUT_GET, 'authent' );
		if ( ! isset( $authent ) ) {
			$authent = filter_input( INPUT_POST, 'authent' );
		}
		if ( ! isset( $authent ) ) {
			$authent = 'all';
		}
		if ( ! ( $country = filter_input( INPUT_POST, 'country' ) ) ) {
			$country = filter_input( INPUT_GET, 'country' );
		}
		if ( '' === $country ) {
			$country = 'all';
		}
		$channel = filter_input( INPUT_GET, 'channel' );
		if ( ! isset( $channel ) ) {
			$channel = filter_input( INPUT_POST, 'channel' );
		}
		if ( ! isset( $channel ) ) {
			$channel = 'all';
		}
		if ( ! ( $start = filter_input( INPUT_GET, 'start' ) ) ) {
			$start = filter_input( INPUT_POST, 'start' );
		}
		if ( empty( $start ) || ! Date::is_date_exists( $start, 'Y-m-d' ) ) {
			$sdatetime = new \DateTime( 'now', $timezone );
			$start     = $sdatetime->format( 'Y-m-d' );
		} else {
			$sdatetime = new \DateTime( $start, $timezone );
		}
		if ( ! ( $end = filter_input( INPUT_GET, 'end' ) ) ) {
			$end = filter_input( INPUT_POST, 'end' );
		}
		if ( empty( $end ) || ! Date::is_date_exists( $end, 'Y-m-d' ) ) {
			$edatetime = new \DateTime( 'now', $timezone );
			$end       = $edatetime->format( 'Y-m-d' );
		} else {
			$edatetime = new \DateTime( $end, $timezone );
		}
		if ( $edatetime->getTimestamp() < $sdatetime->getTimestamp() ) {
			$start = $edatetime->format( 'Y-m-d' );
			$end   = $sdatetime->format( 'Y-m-d' );
		}
		return new Analytics( $source, $domain, $type, $site, $start, $end, $id, $reload, $extra, $authent, $country, $channel );
	}

}
