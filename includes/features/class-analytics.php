<?php
/**
 * Vibes analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Vibes\Plugin\Feature;

use Vibes\Plugin\Feature\Schema;
use Vibes\System\Blog;
use Vibes\System\Cache;
use Vibes\System\Date;
use Vibes\System\Conversion;
use Vibes\System\Device;
use Vibes\System\Mime;
use Vibes\System\Option;
use Vibes\System\Role;

use Vibes\System\L10n;
use Vibes\System\Http;
use Vibes\System\Favicon;
use Vibes\System\Timezone;
use Vibes\System\UUID;
use Feather;
use Vibes\System\GeoIP;
use Vibes\System\BrowserPerformance;
use Vibes\System\WebVitals;
use Vibes\Plugin\Feature\Capture;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The source of data.
	 *
	 * @since  1.0.0
	 * @var    string    $source    The source of data.
	 */
	public $source = '';

	/**
	 * The domain name.
	 *
	 * @since  1.0.0
	 * @var    string    $domain    The domain name.
	 */
	public $domain = '';

	/**
	 * The subdomain name.
	 *
	 * @since  1.0.0
	 * @var    string    $subdomain    The subdomain name.
	 */
	public $subdomain = '';

	/**
	 * The dashboard type.
	 *
	 * @since  1.0.0
	 * @var    string    $title    The dashboard type.
	 */
	public $type = '';

	/**
	 * The dashboard extra.
	 *
	 * @since  1.0.0
	 * @var    string    $extra    The dashboard extra.
	 */
	public $extra = '';

	/**
	 * The queried ID.
	 *
	 * @since  1.0.0
	 * @var    string    $id    The queried ID.
	 */
	private $id = '';

	/**
	 * The queried site.
	 *
	 * @since  1.0.0
	 * @var    string    $site    The queried site.
	 */
	public $site = 'all';

	/**
	 * The queried mode.
	 *
	 * @since  1.0.0
	 * @var    string    $mode    The queried mode.
	 */
	public $mode = 'none';

	/**
	 * The queried country.
	 *
	 * @since  1.0.0
	 * @var    string    $country    The queried country.
	 */
	public $country = 'all';

	/**
	 * The queried authent.
	 *
	 * @since  1.0.0
	 * @var    string    $authent    The queried authent.
	 */
	public $authent = 'all';

	/**
	 * The queried channel.
	 *
	 * @since  1.1.0
	 * @var    string    $channel    The queried channel.
	 */
	public $channel = 'all';

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in seconds.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in seconds.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter for the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#B2C326', '#FFA5A5', '#A5F8D3', '#FEE440', '#BDC3C6' ];

	/**
	 * Colors for timeline.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $span_colors = [
		'redirect' => '#3398DB',
		'dns'      => '#3398DB',
		'tcp'      => '#3398DB',
		'ssl'      => '#3398DB',
		'wait'     => '#9B59B6',
		'download' => '#b2c326',
	];

	/**
	 * Available countries.
	 *
	 * @since  1.0.0
	 * @var    array    $available_countries    The available countries.
	 */
	private $available_countries = [];

	/**
	 * The trace ticks.
	 *
	 * @since  1.0.0
	 * @var    integer    $traces_tick    The trace ticks.
	 */
	private $traces_tick = 100;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $source  The source of data.
	 * @param   string  $domain  The domain name, if disambiguation is needed.
	 * @param   string  $type    The type of analytics ( summary, domain, authority, endpoint, country).
	 * @param   string  $site    The site to analyze (all or ID).
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   string  $id      The queried ID.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @param   string  $extra   The extra view to render.
	 * @param   string  $authent The authent mode.
	 * @param   string  $country The country.
	 * @param   string  $channel The channel.
	 * @since    1.0.0
	 */
	public function __construct( $source, $domain, $type, $site, $start, $end, $id, $reload, $extra, $authent, $country, $channel ) {
		$this->source = $source;
		$this->id     = $id;
		$this->extra  = $extra;
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		$this->site = $site;
		if ( 'all' !== $site ) {
			$this->filter[]   = "site='" . $site . "'";
			$this->previous[] = "site='" . $site . "'";
		}
		if ( '' !== $domain ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start = $start;
		$this->end   = $end;
		$this->type  = $type;
		if ( 'resource' === $this->source ) {
			if ( false !== strpos( $this->type, '~' ) ) {
				$ttype      = substr( $this->type, 0, strpos( $this->type, '~' ) );
				$this->mode = substr( $this->type, strpos( $this->type, '~' ) + 1 );
				$this->type = $ttype;
			}
			if ( 'summary' !== $type ) {
				$this->mode = $type;
			}
		}
		if ( '' !== $id ) {
			switch ( $type ) {
				case 'domain':
				case 'authorities':
					$this->filter[]   = "id='" . $id . "'";
					$this->previous[] = "id='" . $id . "'";
					break;
				case 'authority':
				case 'endpoints':
					$this->filter[]   = "authority='" . $id . "'";
					$this->previous[] = "authority='" . $id . "'";
					$this->subdomain  = Schema::get_authority( $this->source, $this->filter );
					break;
				case 'endpoint':
					$this->filter[]   = "endpoint='" . $id . "'";
					$this->previous[] = "endpoint='" . $id . "'";
					if ( 'resource' === $this->source ) {
						$this->subdomain = Schema::get_authority( $this->source, $this->filter );
					}
					break;
				default:
					if ( 'webvital' === $this->source ) {
						$this->filter[]   = "endpoint='" . $id . "'";
						$this->previous[] = "endpoint='" . $id . "'";
					}
					$this->type = 'summary';
			}
		}
		if ( '' !== $domain && 'domain' !== $type && 'authorities' !== $type ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		$this->timezone = Timezone::network_get();
		$datetime       = new \DateTime( 'now', $this->timezone );
		$this->is_today = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		$start          = new \DateTime( $this->start, $this->timezone );
		$end            = new \DateTime( $this->end, $this->timezone );
		$start->sub( new \DateInterval( 'P1D' ) );
		$end->sub( new \DateInterval( 'P1D' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration = $delta->days + 1;
		if ( $start === $end ) {
			$this->previous[] = "timestamp='" . $start->format( 'Y-m-d' ) . "'";
		} else {
			$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d' ) . "' and timestamp<='" . $end->format( 'Y-m-d' ) . "'";
		}
		if ( 'resource' !== $source ) {
			if ( 'all' !== $authent ) {
				$this->filter[] = 'authent=' . $authent;
			}
			$this->authent             = $authent;
			$this->available_countries = Schema::get_distinct_countries( $this->source, $this->filter, ! $this->is_today );
			if ( 'all' !== strtolower( $country ) && ! in_array( strtoupper( $country ), $this->available_countries, true ) ) {
				$country = 'all';
			}
			if ( 'all' !== strtolower( $country ) ) {
				$this->filter[] = "country='" . strtoupper( $country ) . "'";
			}
			$this->country = strtoupper( $country );
			if ( 'all' !== strtolower( $channel ) ) {
				$url_parts       = wp_parse_url( admin_url( '', '' ) );
				$cleaned_enpoint = Capture::clean_endpoint( '(self)', $url_parts['path'], 50, false );
				switch ( strtolower( $channel ) ) {
					case 'wfront':
						$this->filter[] = "endpoint not like '" . $cleaned_enpoint . "%'";
						break;
					case 'wback':
						$this->filter[] = "endpoint like'" . $cleaned_enpoint . "%'";
						break;
				}
			}
			$this->channel = strtolower( $channel );
		}
	}

	/**
	 * Get the sampling factor.
	 *
	 * @return integer  The sampling factor to apply.
	 * @since    1.0.0
	 */
	private function sampling_factor() {
		if ( 'resource' === $this->source ) {
			return (int) ( ( 1000 / Option::network_get( 'sampling' ) ) * ( 1000 / Option::network_get( 'resource_sampling' ) ) );
		}
		return (int) ( 1000 / Option::network_get( 'sampling' ) );
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		if ( 0 < strpos( $query, '.' ) ) {
			$this->source = substr( $query, 0, strpos( $query, '.' ) );
			$query        = str_replace( $this->source . '.', '', $query );
		}
		if ( 0 < strpos( $query, '_' ) ) {
			$tquery = substr( $query, 0, strpos( $query, '_' ) );
			$sub    = str_replace( $tquery . '_', '', $query );
			$query  = $tquery;
		} else {
			$sub = '';
		}
		if ( 0 < strpos( $query, '~' ) ) {
			$tquery     = substr( $query, 0, strpos( $query, '~' ) );
			$this->mode = substr( $query, strpos( $query, '~' ) + 1 );
			$query      = $tquery;
			if ( '' !== $this->id ) {
				switch ( $this->mode ) {
					case 'domain':
					case 'authorities':
						$this->filter[]   = "id='" . $this->id . "'";
						$this->previous[] = "id='" . $this->id . "'";
						break;
					case 'authority':
					case 'endpoints':
						$this->filter[]   = "authority='" . $this->id . "'";
						$this->previous[] = "authority='" . $this->id . "'";
						$this->subdomain  = Schema::get_authority( $this->source, $this->filter );
						break;
					case 'endpoint':
						$this->filter[]   = "endpoint='" . $this->id . "'";
						$this->previous[] = "endpoint='" . $this->id . "'";
						if ( 'resource' === $this->source ) {
							$this->subdomain = Schema::get_authority( $this->source, $this->filter );
						}
						break;
				}
			}
		}
		switch ( $query ) {
			case 'top-domains':
				return $this->query_top( 'domains', (int) $queried );
			case 'top-authorities':
				return $this->query_top( 'authorities', (int) $queried );
			case 'top-endpoints':
				return $this->query_top( 'endpoints', (int) $queried );
			case 'category':
				return $this->query_pie( 'category', (int) $queried );
			case 'initiator':
				return $this->query_pie( 'initiator', (int) $queried );
			case 'security':
				return $this->query_pie( 'security', (int) $queried );
			case 'cache':
				return $this->query_pie( 'cache', (int) $queried );
			case 'mimes':
				return $this->query_list( 'mimes' );
			case 'categories':
				return $this->query_list( 'categories' );
			case 'domains':
				return $this->query_list( 'domains' );
			case 'authorities':
				return $this->query_list( 'authorities' );
			case 'endpoints':
				if ( 'webvital' === $this->source ) {
					return $this->query_webvital_list( 'endpoints' );
				}
				return $this->query_navigation_list( 'endpoints' );
			case 'sites':
				if ( 'webvital' === $this->source ) {
					return $this->query_webvital_list( 'sites' );
				}
				return $this->query_navigation_list( 'sites' );
			case 'class':
			case 'device':
				if ( 'webvital' === $this->source ) {
					return $this->query_webvital( $query, $sub );
				}
				return $this->query_navigation( $query, $sub );
			case 'main-webvital-chart':
				return $this->query_webvital_chart();
			case 'main-navigation-chart':
				return $this->query_navigation_chart();
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   array  $row        The type of widget.
	 * @param   string  $field     The class or device id.
	 * @return string  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function get_webvital( $row, $field ) {
		if ( 0 === (int) $row[ 'avg_' . $field ] ) {
			$value = '-';
			$level = 'none';
		} else {
			$value = WebVitals::display_value( $field, $row[ 'avg_' . $field ] );
			$level = WebVitals::get_rate_field( $field, $row[ 'avg_' . $field ] );
		}
		$result  = '<div class="vibes-webvital-container">';
		$result .= '<div class="vibes-webvital-text">';
		$result .= '<span class="vibes-webvital-definition vibes-webvital-definition-' . $level . '">' . WebVitals::$metrics_names[ $field ] . '</span>';
		$result .= '<span class="vibes-webvital-index vibes-webvital-index-' . $level . '">' . $value . '</span>';
		$result .= '</div>';
		$result .= '<div class="vibes-webvital-bar">';
		$result .= '<div class="vibes-webvital-bar-none">' . esc_html__( 'Not Enough Data', 'vibes' ) . '</div>';
		$prec    = 0;
		$shift   = 0;
		$cpt     = 20;
		if ( 'none' !== $level ) {
			foreach ( [ 'good', 'impr', 'poor' ] as $key => $spec ) {
				$remaining = 0;
				if ( 'good' === $spec ) {
					$remaining = (int) round( 100 * ( $row[ 'pct_' . $field . '_impr' ] + $row[ 'pct_' . $field . '_poor' ] ) );
				}
				if ( 'impr' === $spec ) {
					$remaining = (int) round( 100 * $row[ 'pct_' . $field . '_poor' ] );
				}
				if ( 0 === $remaining ) {
					$val = 100 - $shift;
					$pos = 'width:101%;';
				} else {
					$val = (int) round( 100 * $row[ 'pct_' . $field . '_' . $spec ] );
					$pos = 'width:' . ( 1 + 100 * $val / ( 100 - $shift ) ) . '%;';
				}

				$shift_str = ( 0 === $shift ? '' : 'right:-' . $shift . '%;' );
				$up_str    = 'top:-' . $cpt . 'px;';
				if ( 0 === $prec && 0 !== $val ) {
					$width_str = 'width:100%;';
				} else {
					$width_str = 'width:' . ( 0 === $remaining ? $val + 0.1 : $val ) . '%;';
				}
				if ( 0 !== $remaining ) {
					$width_str = 'width:' . ( 100 - $shift ) . '%;';
				}
				if ( 0 < $val ) {
					$result .= '<div class="vibes-webvital-bar-' . $spec . '" style="' . $width_str . $shift_str . $up_str . '"><span class="vibes-webvital-percent" style="' . $pos . '">' . ( 2 < $val ? $val . '%' : '&nbsp;' ) . '</span></div>';
					$prec   += $val;
					$cpt    += 20;
				} else {
					$val = 0;
				}
				$shift += $val;
			}
		}
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type   The type of widget.
	 * @param   string  $id     The class or device id.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_webvital( $type, $id ) {
		$query = Schema::get_grouped_list( $this->source, $type, [], $this->filter, ! $this->is_today, '', [], false, '', 0, Option::site_get( 'quality' ) );
		$data  = [];
		if ( 0 < count( $query ) ) {
			foreach ( $query as $row ) {
				if ( $id === $row[ $type ] ) {
					$data[] = $row;
				}
			}
		}
		if ( 0 === count( $data ) ) {
			foreach ( WebVitals::$rated_metrics as $metric ) {
				$data[0][ 'avg_' . $metric ] = 0;
				foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
					$data[0][ 'pct_' . $metric . '_' . $field ] = 0;
				}
			}
			foreach ( WebVitals::$unrated_metrics as $metric ) {
				$data[0][ 'avg_' . $metric ] = 0;
			}
			$data[0]['q'] = 0.0;
		} else {
			$data[0]['q'] = 0.0;
			$idx          = Option::network_get( 'qstat' );
			foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
				$q = $data[0][ 'hit_' . $metric ] / $idx;
				if ( 1 < $q ) {
					$q = 1.0;
				}
				$data[0]['q'] += $q * 20;
			}
		}
		$data = $data[0];
		$q    = (string) round( $data['q'], 5 );
		if ( false === strpos( $q, '.' ) ) {
			$q .= '.';
			$q  = str_pad( $q, strlen( $q ) + 1, '0' );
		}
		$result  = '<div class="vibes-webvital-box">';
		$result .= '<div class="vibes-corewebvital-box">';
		$result .= $this->get_webvital( $data, 'LCP' );
		$result .= '<div class="vibes-webvital-separator">&nbsp;</div>';
		$result .= $this->get_webvital( $data, 'FID' );
		$result .= '<div class="vibes-webvital-separator">&nbsp;</div>';
		$result .= $this->get_webvital( $data, 'CLS' );
		$result .= '<div class="vibes-webvital-separator">&nbsp;</div>';
		$result .= $this->get_webvital( $data, 'FCP' );
		$result .= '</div>';
		$result .= '<div class="vibes-kpiwebvital-box">';
		$result .= '<div class="vibes-kpiwebvital-item">';
		$result .= '<div class="vibes-kpiwebvital-title">' . WebVitals::$metrics_names['TTFB'] . '</div>';
		$result .= '<div class="vibes-kpiwebvital-value">';
		$result .= '<div class="vibes-kpiwebvital-number">' . str_replace( '&nbsp;ms', '', WebVitals::display_value( 'TTFB', $data['avg_TTFB'] ) ) . '</div>';
		$result .= '<div class="vibes-kpiwebvital-unit">ms</div>';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<div class="vibes-kpiwebvital-item">';
		$result .= '<div class="vibes-kpiwebvital-title">' . esc_html__( 'Confidence Index', 'vibes' ) . '</div>';
		$result .= '<div class="vibes-kpiwebvital-value">';
		$result .= '<div class="vibes-kpiwebvital-number">' . $q . '</div>';
		$result .= '<div class="vibes-kpiwebvital-unit">%</div>';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'vibes-' . $type . '_' . $id => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$uuid = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'category':
				$group = 'category';
				$data  = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$size  = 200;
				break;
			case 'initiator':
				$group = 'initiator';
				$data  = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$size  = 120;
				break;
			case 'cache':
				$group = 'cache';
				$data  = Schema::get_cache_ratio( $this->source, $this->filter, ! $this->is_today );
				$size  = 120;
				break;
			case 'security':
				$group = 'scheme';
				$data  = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$size  = 120;
				break;

		}
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		if ( 0 < count( $data ) ) {
			$cpt    = 0;
			$labels = [];
			$series = [];
			while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
				if ( 0 < $total ) {
					$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
				} else {
					$percent = 100;
				}
				if ( 0.1 > $percent ) {
					$percent = 0.1;
				}
				$meta = strtoupper( $data[ $cpt ][ $group ] );
				if ( 'category' === $type ) {
					$labels[] = ucwords( strtolower( Mime::get_category_name( $data[ $cpt ][ $group ] ) ) );
				} else {
					$labels[] = ucwords( strtolower( $data[ $cpt ][ $group ] ) );
				}
				$series[] = [
					'meta'  => $meta,
					'value' => (float) $percent,
				];
				++ $cpt;
			}
			if ( 0 < $other ) {
				if ( 0 < $total ) {
					$percent = round( 100 * $other / $total, 1 );
				} else {
					$percent = 100;
				}
				if ( 0.1 > $percent ) {
					$percent = 0.1;
				}
				$labels[] = esc_html__( 'Other', 'vibes' );
				$series[] = [
					'meta'  => esc_html__( 'Other', 'vibes' ),
					'value' => (float) $percent,
				];
			}
			$result  = '<div class="vibes-pie-box">';
			$result .= '<div class="vibes-pie-graph">';
			$result .= '<div class="vibes-pie-graph-handler-' . $size . '" id="vibes-pie-' . $type . '"></div>';
			$result .= '</div>';
			$result .= '<div class="vibes-pie-legend">';
			foreach ( $labels as $key => $label ) {
				$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
				$result .= '<div class="vibes-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
			}
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
				[
					'labels' => $labels,
					'series' => $series,
				]
			) . ';';
			$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
			$result .= ' var option' . $uuid . ' = {width: ' . $size . ', height: ' . $size . ', showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
			$result .= ' new Chartist.Pie("#vibes-pie-' . $type . '", data' . $uuid . ', option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="vibes-pie-box">';
			$result .= '<div class="vibes-pie-graph" style="margin:0 !important;">';
			$result .= '<div class="vibes-pie-graph-nodata-handler-' . $size . '" id="vibes-pie-' . $type . '"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'No Data', 'vibes' ) . '&nbsp;-</span></div>';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '</div>';
		}
		return [ 'vibes-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'authorities':
				$group  = 'authority';
				$follow = 'authority';
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			default:
				$group  = 'id';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$factor = $this->sampling_factor();
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $data[ $cpt ][ $group ],
					'domain' => $data[ $cpt ]['id'],
				]
			);
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$text    = $data[ $cpt ][ $group ];
			if ( 60 < strlen( $text ) ) {
				$text = substr( $text, 0, 60 ) . '…';
			}
			$result .= '<div class="vibes-top-line">';
			$result .= '<div class="vibes-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="vibes-top-line-title-text"><a href="' . esc_url( $url ) . '">' . $text . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="vibes-top-line-content">';
			$result .= '<div class="vibes-bar-graph"><div class="vibes-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="vibes-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'] * $factor, 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="vibes-top-line vibes-minor-data">';
		$result .= '<div class="vibes-top-line-title">';
		$result .= '<span class="vibes-top-line-title-text">' . esc_html__( 'Other', 'vibes' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="vibes-top-line-content">';
		$result .= '<div class="vibes-bar-graph"><div class="vibes-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="vibes-bar-detail">' . Conversion::number_shorten( $other * $factor, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'vibes-top-' . $type => $result ];
	}

	/**
	 * Print the single span name.
	 *
	 * @since 1.0.0
	 */
	private function get_span_name( $name ) {
		switch ( $name ) {
			case 'redirect':
				return __( 'Redirections', 'vibes' );
			case 'dns':
				return __( 'DNS Lookup', 'vibes' );
			case 'tcp':
				return __( 'TCP Connection', 'vibes' );
			case 'ssl':
				return __( 'SSL Handshake', 'vibes' );
			case 'wait':
				return __( 'Waiting', 'vibes' );
			case 'download':
				return __( 'Download', 'vibes' );
		}
	}

	/**
	 * Print the single span visualization.
	 *
	 * @since 1.0.0
	 */
	private function get_span( $span, $level = 0, $start = 0, $duration = 0 ) {
		$time = (string) round( ( $span['duration'] ), 1 );
		if ( false === strpos( $time, '.' ) ) {
			$time .= '.0';
		}
		$time  .= '&nbsp;ms';
		$result = '<div class="vibes-span-wrap">';
		// Span text
		$result .= '<div class="vibes-span-text">';
		$result .= '<span class="vibes-span-text-label">' . str_pad( '', ( $level * 3 ) * 6, '&nbsp;' ) . ( 0 === $level ? '' : '&nbsp;&nbsp;' ) . $this->get_span_name( $span['name'] ) . $span['comp'] . '</span>';
		$result .= '<span class="vibes-span-text-time">' . $time . '</span>';
		$result .= '</div>';
		// Span timeline
		$bblank = round( 100 * ( $span['start'] - $start ) / $duration, 2 );
		$lblank = round( 100 * ( $span['duration'] ) / $duration, 2 );
		if ( 0.1 > $lblank ) {
			$lblank = 0.1;
		}
		$eblank = 100.0 - $bblank - $lblank;
		$tick    = round( 200 * $this->traces_tick / $duration, 3 );
		$color   = $this->span_colors[ $span['name'] ];
		if ( 0 > $eblank ) {
			$eblank = 0.0;
			$bblank = 100.0 - $lblank;
		}
		if ( 90 > $bblank ) {
			$result .= '<div class="vibes-span-timeline" style="background-size: ' . $tick . '% 100%;">';
			$result .= '<div class="vibes-span-timeline-blank" style="width:' . $bblank . '%">';
			$result .= '</div>';
			$result .= '<div class="vibes-span-timeline-line" style="background-color:' . $color . ';width:' . $lblank . '%">';
			$result .= '</div>';
			$result .= '<div class="vibes-span-timeline-blank" style="width:' . $eblank . '%;">';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '</div>';
		} else {
			$result .= '<div class="vibes-span-timeline" style="background-size: ' . $tick . '% 100%;">';
			$result .= '<div class="vibes-span-timeline-blank" style="width:' . $bblank . '%;vertical-align: middle;">';
			$result .= '</div>';
			$result .= '<div class="vibes-span-timeline-line" style="background-color:' . $color . ';width:' . $lblank . '%">';
			$result .= '</div>';
			$result .= '<div class="vibes-span-timeline-blank" style="width:' . $eblank . '%;">';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '</div>';
		}
		return $result;
	}

	/**
	 * Get a trace visualization.
	 *
	 * @param   array   $row    The type of list.
	 * @return  string  The span, ready to print.
	 * @since    1.0.0
	 */
	private function get_spans( $row ) {
		$result   = '<div class="vibes-spans-wrap" style="width:100%">';
		$spans    = [];
		$duration = 0;
		foreach ( BrowserPerformance::$spans as $span ) {
			if ( array_key_exists( 'avg_span_' . $span . '_start', $row ) && array_key_exists( 'avg_span_' . $span . '_duration', $row ) ) {
				$s = [];
				if ( 'redirect' === $span && array_key_exists( 'avg_redirects', $row ) ) {
					$s['comp'] = ' (' . (int) round( $row['avg_redirects'], 0 ) . ')';
				} else {
					$s['comp'] = '';
				}
				$s['name']     = $span;
				$s['start']    = $row[ 'avg_span_' . $span . '_start' ];
				$s['duration'] = $row[ 'avg_span_' . $span . '_duration' ];
				$spans[]       = $s;
			}
		}
		if ( array_key_exists( 'avg_span_download_start', $row ) && array_key_exists( 'avg_span_download_duration', $row ) ) {
			$duration = $row['avg_span_download_start'] + $row['avg_span_download_duration'] + 1;
		}
		$duration += (int) ( $this->traces_tick / 10 );
		if ( 3 * $this->traces_tick > $duration ) {
			$duration = 3 * $this->traces_tick;
		}
		foreach ( $spans as $span ) {
			$result .= $this->get_span( $span, 0, 0, $duration );
		}
		$result .= '</div>';
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type   The type of widget.
	 * @param   string  $id     The class or device id.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_navigation( $type, $id ) {
		$query = Schema::get_grouped_list( $this->source, $type, [], $this->filter, ! $this->is_today, '', [], false, '', 0, Option::site_get( 'quality' ) );
		$data  = [];
		if ( 0 < count( $query ) ) {
			foreach ( $query as $row ) {
				if ( $id === $row[ $type ] ) {
					$data[] = $row;
				}
			}
		}
		if ( 0 !== count( $data ) ) {
			$result = '<div class="vibes-span-widget">' . $this->get_spans( $data[0] ) . '</div>';
		} else {
			$result  = '<div class="vibes-pie-box">';
			$result .= '<div class="vibes-pie-graph" style="margin:0 !important;">';
			$result .= '<div class="vibes-pie-graph-nodata-handler-200" id="vibes-pie-' . $type . '"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'Not Enough Data', 'vibes' ) . '&nbsp;-</span></div>';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '</div>';
		}
		return [ 'vibes-' . $type . '_' . $id => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		$follow     = '';
		$has_detail = false;
		$detail     = '';
		$count      = [];
		$toggle     = false;
		switch ( $type ) {
			case 'mimes':
				$group  = 'category, mime';
				$toggle = true;
				break;
			case 'categories':
				$group  = 'category';
				$toggle = true;
				break;
			case 'domains':
				$group      = 'id';
				$follow     = 'domain';
				$count      = [ 'authority', 'endpoint' ];
				$has_detail = true;
				break;
			case 'authorities':
				$group      = 'authority';
				$follow     = 'authority';
				$count      = [ 'endpoint' ];
				$has_detail = true;
				break;
			case 'endpoints':
				$group      = 'endpoint';
				$follow     = 'endpoint';
				$count      = [ 'mime' ];
				$has_detail = true;
				break;
			case 'sites':
				$group = 'site';
				break;
		}
		$data         = Schema::get_grouped_list( $this->source, $group, $count, $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$factor       = $this->sampling_factor();
		$detail_name  = esc_html__( 'Details', 'vibes' );
		$calls_name   = esc_html__( 'Hits', 'vibes' );
		$data_name    = esc_html__( 'Size', 'vibes' );
		$latency_name = esc_html__( 'Latency', 'vibes' );
		$cache_name   = esc_html__( 'Browser Cache', 'vibes' );
		$result       = '<table class="vibes-table">';
		$result      .= '<tr>';
		$result      .= '<th>&nbsp;</th>';
		if ( $has_detail ) {
			$result .= '<th>' . $detail_name . '</th>';
		}
		$result   .= '<th>' . $calls_name . '</th>';
		$result   .= '<th>' . $data_name . '</th>';
		$result   .= '<th>' . $latency_name . '</th>';
		$result   .= '<th>' . $cache_name . '</th>';
		$result   .= '</tr>';
		$other     = false;
		$other_str = '';
		$geoip     = new GeoIP();
		foreach ( $data as $key => $row ) {
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => false === strpos( $group, ',' ) ? $row[ $group ] : 'unknown',
					'domain' => $row['id'],
				]
			);
			switch ( $type ) {
				case 'mimes':
					$name = '<details class="vibes-span-details vibes-span-' . $type . '"><summary class="vibes-span-summary"><img style="width:14px;vertical-align:text-bottom;" src="' . Mime::get_category_icon( $row['category'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text">' . $row['mime'] . '</span></summary><div class="vibes-span-container">' . $this->get_spans( $row ) . '</div></details>';
					break;
				case 'categories':
					$name = '<details class="vibes-span-details vibes-span-' . $type . '"><summary class="vibes-span-summary"><img style="width:14px;vertical-align:text-bottom;" src="' . Mime::get_category_icon( $row['category'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text">' . Mime::get_category_name( $row['category'] ) . '</span></summary><div class="vibes-span-container">' . $this->get_spans( $row ) . '</div></details>';
					break;
				case 'domains':
					$authorities = sprintf( esc_html( _n( '%d subdomain', '%d subdomains', $row['cnt_authority'], 'vibes' ) ), $row['cnt_authority'] );
					$endpoints   = sprintf( esc_html( _n( '%d endpoint', '%d endpoints', $row['cnt_endpoint'], 'vibes' ) ), $row['cnt_endpoint'] );
					$detail      = $authorities . ' - ' . $endpoints;
					$name        = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row[ $group ] . '</a></span>';
					break;
				case 'authorities':
					$detail = sprintf( esc_html( _n( '%d endpoint', '%d endpoints', $row['cnt_endpoint'], 'vibes' ) ), $row['cnt_endpoint'] );
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row[ $group ] . '</a></span>';
					break;
				case 'endpoints':
					if ( 1 === (int) $row['cnt_mime'] ) {
						$detail = $row['mime'];
						$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Mime::get_category_icon( $row['category'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row[ $group ] . '</a></span>';
					} else {
						$detail = sprintf( esc_html( _n( '%d mime type', '%d different mime types', $row['cnt_mime'], 'vibes' ) ), $row['cnt_mime'] );
						$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row[ $group ] . '</a></span>';
					}
					break;
				case 'sites':
					if ( 0 === (int) $row['sum_hit'] ) {
						break;
					}
					if ( 'summary' === $this->type ) {
						$url = $this->get_url(
							[],
							[
								'site' => $row['site'],
							]
						);
					} else {
						$url = $this->get_url(
							[],
							[
								'site'   => $row['site'],
								'domain' => $row['id'],
							]
						);
					}
					$site = Blog::get_blog_url( $row['site'] );
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $site ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $site . '</a></span>';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'] * $factor, 2, false, '&nbsp;' );
			if ( 0 < $row['avg_size'] ) {
				$data = Conversion::data_shorten( (int) $row['avg_size'], 2, false, '&nbsp;' );
			} else {
				$data = '-';
			}
			$cache   = round( 100 * $row['avg_cache'], 1 ) . '%';
			$latency = (int) $row['avg_load'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' );
			if ( 'codes' === $type && '0' === $row[ $group ] ) {
				$latency = '-';
			}
			$row_str  = '<tr>';
			$row_str .= '<td data-th="">' . $name . '</td>';
			if ( $has_detail ) {
				$row_str .= '<td data-th="' . $detail_name . '">' . $detail . '</td>';
			}
			$row_str .= '<td data-th="' . $calls_name . '">' . $calls . '</td>';
			$row_str .= '<td data-th="' . $data_name . '">' . $data . '</td>';
			$row_str .= '<td data-th="' . $latency_name . '">' . $latency . '</td>';
			$row_str .= '<td data-th="' . $cache_name . '">' . $cache . '</td>';
			$row_str .= '</tr>';
			if ( $other ) {
				$other_str = $row_str;
			} else {
				$result .= $row_str;
			}
		}
		$result .= $other_str . '</table>';
		if ( $toggle ) {
			$result .= '<script>jQuery(document).ready(function($){$(".vibes-span-' . $type . '").on("toggle",function(e){if(this.open){$(".vibes-span-' . $type . '").not(this).attr({open:false});}})});</script>';
		}
		return [ 'vibes-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_webvital_list( $type ) {
		$follow = '';
		$order  = '';
		switch ( $type ) {
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				$order  = 'ORDER BY endpoint ASC';
				break;
			case 'sites':
				$group = 'site';
				$order = 'ORDER BY site DESC';
				break;
		}
		$data    = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, $order );
		$result  = '<table class="vibes-table">';
		$result .= '<tr>';
		$result .= '<th>&nbsp;</th>';
		foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
			$result .= '<th>' . $metric . '</th>';
		}
		$result .= '</tr>';
		$site    = '';
		$icon    = '';
		foreach ( $data as $key => $row ) {
			$url = $this->get_url(
				[],
				[
					'type' => $follow,
					'id'   => false === strpos( $group, ',' ) ? $row[ $group ] : 'unknown',
				]
			);
			switch ( $type ) {
				case 'endpoints':
					if ( '' === $site ) {
						$site = Blog::get_blog_url( $row['site'] );
						$icon = Favicon::get_base64( $site );
					}
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row['endpoint'] . '</a></span>';
					break;
				case 'sites':
					$url  = $this->get_url(
						[],
						[
							'site' => $row['site'],
						]
					);
					$site = Blog::get_blog_url( $row['site'] );
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $site ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $site . '</a></span>';
					break;
			}
			$result .= '<tr>';
			$result .= '<td data-th="">' . $name . '</td>';
			foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
				$result .= '<td data-th="' . $metric . '"><span class="vibes-list-item-' . WebVitals::get_rate_field( $metric, $row[ 'avg_' . $metric ] ) . '">' . WebVitals::display_value( $metric, $row[ 'avg_' . $metric ] ) . '</span></td>';
			}
			$result .= '</tr>';
		}
		$result .= '</table>';
		return [ 'vibes-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_navigation_list( $type ) {
		$follow = '';
		$order  = '';
		switch ( $type ) {
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				$order  = 'ORDER BY endpoint ASC';
				break;
			case 'sites':
				$group = 'site';
				$order = 'ORDER BY site DESC';
				break;
		}
		$data          = Schema::get_grouped_list( $this->source, $group, [], $this->filter, ! $this->is_today, '', [], false, $order );
		$factor        = $this->sampling_factor();
		$calls_name    = esc_html__( 'Hits', 'vibes' );
		$redirect_name = esc_html__( 'Redirects', 'vibes' );
		$data_name     = esc_html__( 'Size', 'vibes' );
		$latency_name  = esc_html__( 'Latency', 'vibes' );
		$cache_name    = esc_html__( 'Browser Cache', 'vibes' );
		$result        = '<table class="vibes-table">';
		$result       .= '<tr>';
		$result       .= '<th>&nbsp;</th>';
		$result       .= '<th>' . $calls_name . '</th>';
		$result       .= '<th>' . $redirect_name . '</th>';
		$result       .= '<th>' . $data_name . '</th>';
		$result       .= '<th>' . $latency_name . '</th>';
		$result       .= '<th>' . $cache_name . '</th>';
		$result       .= '</tr>';
		$site          = '';
		$icon          = '';
		foreach ( $data as $key => $row ) {
			$url = $this->get_url(
				[],
				[
					'type' => $follow,
					'id'   => false === strpos( $group, ',' ) ? $row[ $group ] : 'unknown',
				]
			);
			switch ( $type ) {
				case 'endpoints':
					if ( '' === $site ) {
						$site = Blog::get_blog_url( $row['site'] );
						$icon = Favicon::get_base64( $site );
					}
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $row['endpoint'] . '</a></span>';
					break;
				case 'sites':
					$url  = $this->get_url(
						[],
						[
							'site' => $row['site'],
						]
					);
					$site = Blog::get_blog_url( $row['site'] );
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $site ) . '" />&nbsp;&nbsp;<span class="vibes-table-text"><a href="' . esc_url( $url ) . '">' . $site . '</a></span>';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'] * $factor, 2, false, '&nbsp;' );
			$redir = (int) $row['avg_redirects'];
			if ( 0 < $row['avg_size'] ) {
				$data = Conversion::data_shorten( (int) $row['avg_size'], 2, false, '&nbsp;' );
			} else {
				$data = '-';
			}
			$cache   = round( 100 * $row['avg_cache'], 1 ) . '%';
			$latency = (int) $row['avg_load'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' );
			$result .= '<tr>';
			$result .= '<td data-th="">' . $name . '</td>';
			$result .= '<td data-th="' . $calls_name . '">' . $calls . '</td>';
			$result .= '<td data-th="' . $redirect_name . '">' . $redir . '</td>';
			$result .= '<td data-th="' . $data_name . '">' . $data . '</td>';
			$result .= '<td data-th="' . $latency_name . '">' . $latency . '</td>';
			$result .= '<td data-th="' . $cache_name . '">' . $cache . '</td>';
			$result .= '</tr>';
		}
		$result .= '</table>';
		return [ 'vibes-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_webvital_chart() {
		$data = Schema::get_time_series( 'webvital', $this->filter, ! $this->is_today );
		if ( 0 === count( $data ) ) {
			return [ 'vibes-webvital-chart' => '<div class="vibes-multichart-handler"><div class="vibes-multichart-nodata-handler"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'Not Enough Data', 'vibes' ) . '&nbsp;-</span></div></div>' ];
		}
		$uuid    = UUID::generate_unique_id( 5 );
		$series  = [];
		$metrics = array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics );
		$start   = '';
		$max     = [];
		$init    = strtotime( $this->start ) - 86400;
		for ( $i = 0; $i < $this->duration + 2; $i++ ) {
			$ts = 'new Date(' . (string) ( ( $i * 86400 ) + $init ) . '000)';
			foreach ( $metrics as $metric ) {
				$series[ strtolower( $metric ) ]['avg'][] = [
					'x' => $ts,
					'y' => 'null',
				];
				foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
					$series[ strtolower( $metric ) ][ $field ][] = [
						'x' => $ts,
						'y' => 'null',
					];
				}
			}
		}
		foreach ( $data as $timestamp => $row ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts  = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			$idx = (int) ( ( strtotime( $timestamp ) - $init ) / 86400 );
			foreach ( $metrics as $metric ) {
				$val = WebVitals::get_graphable_value( $metric, $row[ 'avg_' . $metric ] );
				if ( ( array_key_exists( strtolower( $metric ), $max ) && $max[ strtolower( $metric ) ] < $val ) || ! array_key_exists( strtolower( $metric ), $max ) ) {
					$max[ strtolower( $metric ) ] = $val;
				}
				$series[ strtolower( $metric ) ]['avg'][$idx] = [
					'x' => $ts,
					'y' => $val,
				];
				foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
					if ( array_key_exists( 'pct_' . $metric . '_' . $field, $row ) ) {
						$series[ strtolower( $metric ) ][ $field ][$idx] = [
							'x' => $ts,
							'y' => round( 100 * $row[ 'pct_' . $metric . '_' . $field ], 1 ),
						];
					}
				}
			}
		}
		$scale  = [];
		foreach ( $metrics as $metric ) {
			$metric = strtolower( $metric );
			$series[ $metric ]['avg']   = wp_json_encode(
				[
					'series' => [
						[
							'name' => strtoupper( $metric ),
							'data' => $series[ $metric ]['avg'],
						],
					],
				],
			);
			$series[ $metric ]['avg']   = str_replace( '"x":"new', '"x":new', $series[ $metric ]['avg'] );
			$series[ $metric ]['avg']   = str_replace( ')","y"', '),"y"', $series[ $metric ]['avg'] );
			$series[ $metric ]['avg']   = str_replace( '"null"', 'null', $series[ $metric ]['avg'] );
			if ( 'ttfb' !== $metric ) {
				$series[ $metric ]['pct'] = wp_json_encode(
					[
						'series' => [
							[
								'name' => esc_html__( 'Good', 'vibes' ),
								'data' => $series[ $metric ]['good'],
							],
							[
								'name' => esc_html__( 'Needs Improvement', 'vibes' ),
								'data' => $series[ $metric ]['impr'],
							],
							[
								'name' => esc_html__( 'Poor', 'vibes' ),
								'data' => $series[ $metric ]['poor'],
							],
						],
					],
				);
			} else {
				$series[ $metric ]['pct'] = wp_json_encode(
					[
						'series' => [],
					],
				);
			}
			$series[ $metric ]['pct'] = str_replace( '"x":"new', '"x":new', $series[ $metric ]['pct'] );
			$series[ $metric ]['pct'] = str_replace( ')","y"', '),"y"', $series[ $metric ]['pct'] );
			$series[ $metric ]['pct'] = str_replace( '"null"', 'null', $series[ $metric ]['pct'] );
			if ( 'ttfb' === $metric ) {
				$scale[ $metric ] = [ 500, 1000, 1500, 2000, 2500 ];
				$scale[ $metric ] = 'ticks: [' . implode( ',', $scale[ $metric ] ) . '], ';
				$max[ $metric ]   = '';
			} else {
				foreach ( WebVitals::$metrics_rates[ strtoupper( $metric ) ] as $rate ) {
					$val = ( $rate / WebVitals::$metrics_ratios[ strtoupper( $metric ) ] );
					if ( $max[ $metric ] < $val ) {
						$max[ $metric ] = $val;
					}
					$scale[ $metric ][] = $val;
				}
				$scale[ $metric ] = 'ticks: [' . implode( ',', $scale[ $metric ] ) . '], ';
				$max[ $metric ]   = 'high: ' . $max[ $metric ] . ', ';
			}
		}

		// Rendering.
		$ticks  = (int) ( 1 + ( $this->duration / 15 ) );
		$result = '<div class="vibes-multichart-handler">';
		foreach ( $metrics as $metric ) {
			$metric  = strtolower( $metric );
			$result .= '<div class="vibes-multichart-item' . ( 'lcp' === $metric ? ' active' : '' ) . '" id="vibes-chart-' . $metric . '">';
			$result .= '<div class="vibes-multichart-line-container" id="vibes-line-' . $metric . '"></div>';
			$result .= '<div class="vibes-multichart-bars-container" id="vibes-bars-' . $metric . '"></div>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var ' . $metric . '_line_data' . $uuid . ' = ' . $series[ $metric ]['avg'] . ';';
			$result .= ' var ' . $metric . '_bars_data' . $uuid . ' = ' . $series[ $metric ]['pct'] . ';';
			$result .= ' var ' . $metric . '_line_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var ' . $metric . '_bars_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
			$result .= ' var ' . $metric . '_line_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: false,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [' . $metric . '_line_tooltip' . $uuid . '],';
			$result .= '  axisX: {scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {type: Chartist.FixedScaleAxis, ' . $scale[ $metric ] . $max[ $metric ] . 'low: 0, labelInterpolationFnc: function (value) {return value.toString()' . ( 'cls' === $metric ? '' : ' + " ms"' ) . ';}},';
			$result .= ' };';
			$result .= ' var ' . $metric . '_bars_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 0,high: 100, low: 0,';
			$result .= '  plugins: [' . $metric . '_bars_tooltip' . $uuid . '],';
			$result .= '  axisX: {scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . '},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#vibes-line-' . $metric . '", ' . $metric . '_line_data' . $uuid . ', ' . $metric . '_line_option' . $uuid . ');';
			$result .= ' new Chartist.Bar("#vibes-bars-' . $metric . '", ' . $metric . '_bars_data' . $uuid . ', ' . $metric . '_bars_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		}
		$result .= '</div>';
		return [ 'vibes-webvital-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_navigation_chart() {
		$data = Schema::get_time_series( 'navigation', $this->filter, ! $this->is_today );
		if ( 0 === count( $data ) ) {
			return [ 'vibes-navigation-chart' => '<div class="vibes-multichart-handler"><div class="vibes-multichart-nodata-handler"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'Not Enough Data', 'vibes' ) . '&nbsp;-</span></div></div>' ];
		}
		$uuid      = UUID::generate_unique_id( 5 );
		$series    = [];
		$metrics   = [];
		$metrics[] = [ 'avg_load', 'avg_span_wait_duration', 'avg_span_download_duration' ];
		$metrics[] = [ 'avg_span_redirect_duration', 'avg_span_dns_duration', 'avg_span_tcp_duration' ];
		$start     = '';
		$max       = [];
		$max[0]    = [];
		$max[1]    = [];
		$init    = strtotime( $this->start ) - 86400;
		for ( $i = 0; $i < $this->duration + 2; $i++ ) {
			$ts = 'new Date(' . (string) ( ( $i * 86400 ) + $init ) . '000)';
			foreach ( $metrics as $stack ) {
				foreach ( $stack as $metric ) {
					$series[ $metric ]['avg'][] = [
						'x' => $ts,
						'y' => 'null',
					];
				}
			}
		}
		foreach ( $data as $timestamp => $row ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts  = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			$idx = (int) ( ( strtotime( $timestamp ) - $init ) / 86400 );
			foreach ( $metrics as $key => $stack ) {
				foreach ( $stack as $metric ) {
					$val = $row[ $metric ];
					if ( ( array_key_exists( $metric, $max[ $key ] ) && $max[ $key ][ $metric ] < $val ) || ! array_key_exists( $metric, $max[ $key ] ) ) {
						$max[ $key ][ $metric ] = $val;
					}
					$series[ $metric ]['avg'][$idx] = [
						'x' => $ts,
						'y' => $val,
					];
				}
			}
		}
		$series['stack0']['avg'] = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Latency', 'vibes' ),
						'data' => $series['avg_load']['avg'],
					],
					[
						'name' => esc_html__( 'Waiting', 'vibes' ),
						'data' => $series['avg_span_wait_duration']['avg'],
					],
					[
						'name' => esc_html__( 'Download', 'vibes' ),
						'data' => $series['avg_span_download_duration']['avg'],
					],
				],
			],
		);
		$series['stack1']['avg'] = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Redirections', 'vibes' ),
						'data' => $series['avg_span_redirect_duration']['avg'],
					],
					[
						'name' => esc_html__( 'DNS Lookup', 'vibes' ),
						'data' => $series['avg_span_dns_duration']['avg'],
					],
					[
						'name' => esc_html__( 'Connection', 'vibes' ),
						'data' => $series['avg_span_redirect_duration']['avg'],
					],
				],
			],
		);
		foreach ( $metrics as $key => $stack ) {
			$series[ 'stack' . $key ]['avg'] = str_replace( '"x":"new', '"x":new', $series[ 'stack' . $key ]['avg'] );
			$series[ 'stack' . $key ]['avg'] = str_replace( ')","y"', '),"y"', $series[ 'stack' . $key ]['avg'] );
			$series[ 'stack' . $key ]['avg'] = str_replace( '"null"', 'null', $series[ 'stack' . $key ]['avg'] );
		}

		// Rendering.
		$ticks   = (int) ( 1 + ( $this->duration / 15 ) );
		$result  = '<div class="vibes-multichart-handler">';
		$result .= '<div class="vibes-multichart-item active" id="vibes-chart-time">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var time_data' . $uuid . ' = ' . $series['stack0']['avg'] . ';';
		$result .= ' var time_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var time_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [time_tooltip' . $uuid . '],';
		//$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisX: {scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
		$result .= '  axisY: {type: Chartist.FixedScaleAxis, ticks: [1000, 2000, 3000],low: 0, labelInterpolationFnc: function (value) {return value.toString() + " ms";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#vibes-chart-time", time_data' . $uuid . ', time_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="vibes-multichart-item" id="vibes-chart-net">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var net_net' . $uuid . ' = ' . $series['stack1']['avg'] . ';';
		$result .= ' var net_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var net_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [net_tooltip' . $uuid . '],';
		//$result .= '  axisX: {type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisX: {scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ms";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#vibes-chart-net", net_net' . $uuid . ', net_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '</div>';
		return [ 'vibes-navigation-chart' => $result ];
	}

	/**
	 * Get the channel selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.1.0
	 */
	public function get_channel_selector() {
		$breadcrumbs[] = [
			'title' => ' - ' . esc_html__( 'All', 'vibes' ) . ' - ',
			'url'   => $this->get_url( [ 'channel' ] ),
		];
		$breadcrumbs[] = [
			'title' => esc_html__( 'Frontend', 'vibes' ),
			'url'   => $this->get_url( [], [ 'channel' => 'wfront' ] ),
		];
		$breadcrumbs[] = [
			'title' => esc_html__( 'Backend', 'vibes' ),
			'url'   => $this->get_url( [], [ 'channel' => 'wback' ] ),
		];
		if ( 'all' === $this->channel ) {
			$title = esc_html__( 'All', 'vibes' );
		} elseif ( 'wfront' === $this->channel ) {
			$title = esc_html__( 'Frontend', 'vibes' );
		} elseif ( 'wback' === $this->channel ) {
			$title = esc_html__( 'Backend', 'vibes' );
		}
		$result = '<select name="channel" id="channel" data="' . Feather\Icons::get_base64( 'activity', 'none', '#5A738E' ) . '" class="vibes-select channel" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '</option>';
		}
		$result .= '</select>';
		$result .= '';
		return $result;
	}

	/**
	 * Get the user selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_user_selector() {
		$breadcrumbs[] = [
			'title' => ' - ' . esc_html__( 'All', 'vibes' ) . ' - ',
			'url'   => $this->get_url( [ 'authent' ] ),
		];
		$breadcrumbs[] = [
			'title' => esc_html__( 'Anonymous', 'vibes' ),
			'url'   => $this->get_url( [], [ 'authent' => 0 ] ),
		];
		$breadcrumbs[] = [
			'title' => esc_html__( 'Authenticated', 'vibes' ),
			'url'   => $this->get_url( [], [ 'authent' => 1 ] ),
		];
		if ( 'all' === $this->authent ) {
			$title = esc_html__( 'All', 'vibes' );
		} elseif ( 0 === (int) $this->authent ) {
			$title = esc_html__( 'Anonymous', 'vibes' );
		} elseif ( 1 === (int) $this->authent ) {
			$title = esc_html__( 'Authenticated', 'vibes' );
		}
		$result = '<select name="users" id="users" data="' . Feather\Icons::get_base64( 'users', 'none', '#5A738E' ) . '" class="vibes-select users" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '</option>';
		}
		$result .= '</select>';
		$result .= '';
		return $result;
	}

	/**
	 * Get the user selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_country_selector() {
		$breadcrumbs[] = [
			'title' => ' - ' . esc_html__( 'All', 'vibes' ) . ' - ',
			'url'   => $this->get_url( [ 'country' ] ),
		];
		$b             = [];
		foreach ( $this->available_countries as $country ) {
			$b[] = [
				'title' => L10n::get_country_name( strtoupper( $country ) ),
				'url'   => $this->get_url( [], [ 'country' => $country ] ),
			];
		}
		uasort(
			$b,
			function ( $a, $b ) {
				if ( $a['title'] === $b['title'] ) {
					return 0;
				} return ( strtoupper( $a['title'] ) < strtoupper( $b['title'] ) ) ? -1 : 1;
			}
		);
		$title = esc_html__( 'All', 'vibes' );
		if ( in_array( $this->country, $this->available_countries, true ) ) {
			$title = L10n::get_country_name( strtoupper( $this->country ) );
		}
		$result = '<select name="countries" id="countries" data="' . Feather\Icons::get_base64( 'globe', 'none', '#5A738E' ) . '" class="vibes-select countries" placeholder="' . $title . '" style="display:none;">';
		foreach ( array_merge( $breadcrumbs, $b ) as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '</option>';
		}
		$result .= '</select>';
		$result .= '';

		return $result;
	}

	/**
	 * Get the title selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_title_selector() {
		switch ( $this->type ) {
			case 'domains':
				$title = esc_html__( 'Domains Details', 'vibes' );
				break;
			case 'domain':
				$title = esc_html__( 'Domain Summary', 'vibes' );
				break;
			case 'authorities':
				$title         = esc_html__( 'Domain Details', 'vibes' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'vibes' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'authority':
				$title         = esc_html__( 'Subdomain Summary', 'vibes' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'vibes' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoints':
				$title         = esc_html__( 'Subdomain Details', 'vibes' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Subdomain Summary', 'vibes' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->subdomain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'authority',
							'domain' => $this->domain,
							'id'     => $this->subdomain,
						]
					),
				];
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'vibes' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoint':
				switch ( $this->extra ) {
					case 'devices':
						$title         = esc_html__( 'Mobiles Breakdown', 'vibes' );
						$breadcrumbs[] = [
							'title'    => esc_html__( 'Endpoint Summary', 'vibes' ),
							'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->id ),
							'url'      => $this->get_url(
								[ 'extra' ],
								[
									'type' => 'endpoint',
									'id'   => $this->id,
								]
							),
						];
						break;
					default:
						$title = esc_html__( 'Endpoint Summary', 'vibes' );
				}
				if ( 'resource' === $this->source ) {
					$breadcrumbs[] = [
						'title'    => esc_html__( 'Subdomain Summary', 'vibes' ),
						'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->subdomain ),
						'url'      => $this->get_url(
							[ 'extra' ],
							[
								'type'   => 'authority',
								'domain' => $this->domain,
								'id'     => $this->subdomain,
							]
						),
					];
					$breadcrumbs[] = [
						'title'    => esc_html__( 'Domain Summary', 'vibes' ),
						'subtitle' => sprintf( esc_html__( 'Return to %s', 'vibes' ), $this->domain ),
						'url'      => $this->get_url(
							[ 'extra' ],
							[
								'type'   => 'domain',
								'domain' => $this->domain,
								'id'     => $this->domain,
							]
						),
					];
				}
				break;
			case 'country':
				$title    = esc_html__( 'Country', 'vibes' );
				$subtitle = L10n::get_country_name( $this->id );
				break;
			default:
				switch ( $this->extra ) {
					case 'devices':
						$title = esc_html__( 'Mobiles Breakdown', 'vibes' );
						break;
					default:
						$title = esc_html__( 'Main Summary', 'vibes' );
				}
		}
		switch ( $this->source ) {
			case 'webvital':
				$name = esc_html__( 'Web Vitals', 'vibes' );
				break;
			case 'resource':
				$name = esc_html__( 'Resources', 'vibes' );
				break;
			case 'navigation':
				$name = esc_html__( 'Performances', 'vibes' );
				break;
			default:
				$name = VIBES_PRODUCT_NAME;
		}
		$breadcrumbs[] = [
			'title'    => esc_html__( 'Main Summary', 'vibes' ),
			'subtitle' => sprintf( esc_html__( 'Return to %s main page.', 'vibes' ), $name ),
			'url'      => $this->get_url( [ 'domain', 'id', 'extra', 'type' ] ),
		];
		$result        = '<select name="sources" id="sources" data="" class="vibes-select sources" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '~-' . $breadcrumb['subtitle'] . '-~</option>';
		}
		$result .= '</select>';
		$result .= '';

		return $result;
	}

	/**
	 * Get the site selection bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_site_bar() {
		if ( Role::SINGLE_ADMIN === Role::admin_type() ) {
			return '';
		}
		if ( 'all' === $this->site ) {
			$result = '<span class="vibes-site-text">' . esc_html__( 'All Sites', 'vibes' ) . '</span>';
		} else {
			if ( Role::SUPER_ADMIN === Role::admin_type() ) {
				$quit   = '<a href="' . esc_url( $this->get_url( [ 'site' ] ) ) . '"><img style="width:12px;vertical-align:text-top;" src="' . Feather\Icons::get_base64( 'x-circle', 'none', '#FFFFFF' ) . '" /></a>';
				$result = '<span class="vibes-site-button">' . sprintf( esc_html__( 'Site ID %s', 'vibes' ), $this->site ) . $quit . '</span>';
			} else {
				$result = '<span class="vibes-site-text">' . sprintf( esc_html__( 'Site ID %s', 'vibes' ), $this->site ) . '</span>';
			}
		}
		return '<span class="vibes-site">' . $result . '</span>';
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$subtitle = $this->id;
		if ( 60 < strlen( $subtitle ) ) {
			$subtitle = substr( $subtitle, 0, 60 ) . '…';
		}
		switch ( $this->type ) {
			case 'summary':
				if ( '' === $this->extra ) {
					$title = esc_html__( 'Main Summary', 'vibes' );
				} else {
					$title = $this->get_title_selector();
				}
				break;
			case 'domain':
			case 'authority':
			case 'endpoint':
			case 'domains':
			case 'authorities':
			case 'endpoints':
			case 'devices':
				$title = $this->get_title_selector();
				break;
		}
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= $this->get_site_bar();
		$result .= '<span class="vibes-title">' . $title . '</span>';
		$result .= '<span class="vibes-subtitle">' . $subtitle . '</span>';
		if ( 'resource' !== $this->source ) {
			$pickers = $this->get_country_selector() . $this->get_channel_selector() . $this->get_user_selector() . $this->get_date_box();
		} else {
			$pickers = $this->get_date_box();
		}
		$result .= '<span class="vibes-picker">' . $pickers . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_webvital_chart() {
		if ( 1 < $this->duration ) {
			$sep    = '';
			$detail = '';
			foreach ( array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics ) as $metric ) {
				$detail .= $sep . '<span class="vibes-chart-button not-ready left" id="vibes-chart-button-' . strtolower( $metric ) . '" data-position="left" data-tooltip="' . preg_replace( '/\(.*\)/iU', '', WebVitals::$metrics_names[ $metric ] ) . '"><span class="vibes-graph-button">' . $metric . '</span></span>';
				if ( '' === $sep ) {
					$sep = '&nbsp;&nbsp;&nbsp;';
				}
			}
			$result  = '<div class="vibes-row">';
			$result .= '<div class="vibes-box vibes-box-full-line">';
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Web Vitals Variations', 'vibes' ) . '<span class="vibes-module-more">' . $detail . '</span></span></div>';
			$result .= '<div class="vibes-module-content" id="vibes-webvital-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			$result .= $this->get_refresh_script(
				[
					'query'   => 'main-webvital-chart',
					'country' => $this->country,
					'authent' => $this->authent,
					'channel' => $this->channel,
					'queried' => 0,
				]
			);
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_navigation_chart() {
		if ( 1 < $this->duration ) {
			$help_time = esc_html__( 'Timings distribution.', 'vibes' );
			$help_net  = esc_html__( 'Network stages.', 'vibes' );
			$detail    = '<span class="vibes-chart-button not-ready left" id="vibes-chart-button-time" data-position="left" data-tooltip="' . $help_time . '"><span class="vibes-graph-button" style="padding: 1px 3px;"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'clock', 'none', '#73879C' ) . '" /></span></span>';
			$detail   .= '&nbsp;&nbsp;&nbsp;<span class="vibes-chart-button not-ready left" id="vibes-chart-button-net" data-position="left" data-tooltip="' . $help_net . '"><span class="vibes-graph-button" style="padding: 1px 3px;"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'link', 'none', '#73879C' ) . '"/></span></span>';
			$result    = '<div class="vibes-row">';
			$result   .= '<div class="vibes-box vibes-box-full-line">';
			$result   .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Metrics Variations', 'vibes' ) . '<span class="vibes-module-more">' . $detail . '</span></span></div>';
			$result   .= '<div class="vibes-module-content" id="vibes-navigation-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result   .= '</div>';
			$result   .= '</div>';
			$result   .= $this->get_refresh_script(
				[
					'query'   => 'main-navigation-chart',
					'country' => $this->country,
					'authent' => $this->authent,
					'channel' => $this->channel,
					'queried' => 0,
				]
			);
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get the web vital widget.
	 *
	 * @return string  The widget ready to print.
	 * @since    1.0.0
	 */
	public function get_webvital_class( $class, $position ) {
		switch ( $this->type ) {
			case 'endpoint':
				$url = $this->get_url(
					[],
					[
						'type'  => 'endpoint',
						'extra' => 'devices',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[],
					[
						'type'  => 'summary',
						'extra' => 'devices',
					]
				);
		}
		$result = '<div class="vibes-50-module vibes-50-module-' . $position . '">';
		if ( 'mobile' === $class && class_exists( 'PODeviceDetector\API\Device' ) ) {
			$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
			$help    = esc_html__( 'View the mobiles breakdown.', 'vibes' );
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_class_name( $class ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		} else {
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_class_name( $class ) . '</span></div>';
		}
		$result .= '<div class="vibes-module-content" id="vibes-class_' . $class . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.class_' . $class,
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the navigation widget.
	 *
	 * @return string  The widget ready to print.
	 * @since    1.0.0
	 */
	public function get_navigation_class( $class, $position ) {
		switch ( $this->type ) {
			case 'endpoint':
				$url = $this->get_url(
					[],
					[
						'type'  => 'endpoint',
						'extra' => 'devices',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[],
					[
						'type'  => 'summary',
						'extra' => 'devices',
					]
				);
		}
		$result = '<div class="vibes-50-module-small vibes-50-module vibes-50-module-' . $position . '">';
		if ( 'mobile' === $class && class_exists( 'PODeviceDetector\API\Device' ) ) {
			$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
			$help    = esc_html__( 'View the mobiles breakdown.', 'vibes' );
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_class_name( $class ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		} else {
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_class_name( $class ) . '</span></div>';
		}
		$result .= '<div class="vibes-module-content" id="vibes-class_' . $class . '">' . $this->get_graph_placeholder( 100 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.class_' . $class,
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the web vital widget.
	 *
	 * @return string  The widget ready to print.
	 * @since    1.0.0
	 */
	public function get_webvital_device( $device, $position ) {
		$result  = '<div class="vibes-50-module vibes-50-module-' . $position . '">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_device_name( $device ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-device_' . $device . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.device_' . $device,
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the navigation widget.
	 *
	 * @return string  The widget ready to print.
	 * @since    1.0.0
	 */
	public function get_navigation_device( $device, $position ) {
		$result  = '<div class="vibes-50-module vibes-50-module-small vibes-50-module-' . $position . '">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . Device::get_device_name( $device ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-device_' . $device . '">' . $this->get_graph_placeholder( 100 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.device_' . $device,
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the mime list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_mimes_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Mime Types Breakdown', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-mimes">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'resource.mimes' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 0,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the category list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_categories_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Sources Breakdown', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-categories">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'resource.categories' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 0,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_sites_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Sites Breakdown', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'sites',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_webvital_sites_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Sites Overview', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'webvital.sites',
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_navigation_sites_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Sites Overview', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'navigation.sites',
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_webvital_endpoints_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Endpoints Overview', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'webvital.endpoints',
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_navigation_endpoints_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Endpoints Overview', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'navigation.endpoints',
				'country' => $this->country,
				'authent' => $this->authent,
				'channel' => $this->channel,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_domains_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'All Domains', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'resource.domains',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the authorities list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_authorities_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'All Subdomains', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'authorities',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the endpoints list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_endpoints_list() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'All Endpoints', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'endpoints',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the extra list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_extra_list() {
		switch ( $this->extra ) {
			case 'devices':
				$title = esc_html__( 'Mobiles Breakdown', 'vibes' );
				break;
			default:
				$title = esc_html__( 'All Endpoints', 'vibes' );
		}
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . $title . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-' . $this->extra . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->extra,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the top domains box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_domain_box() {
		$url     = $this->get_url( [ 'domain' ], [ 'type' => 'domains' ] );
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all domains.', 'vibes' );
		$result  = '<div class="vibes-60-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Top Domains', 'vibes' ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-top-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.top-domains',
				'queried' => 5,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the top authority box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_authority_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'authorities',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all subdomains.', 'vibes' );
		$result  = '<div class="vibes-60-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Top Subdomains', 'vibes' ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-top-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.top-authorities',
				'queried' => 5,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the top endpoint box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_endpoint_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'endpoints',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all endpoints.', 'vibes' );
		$result  = '<div class="vibes-60-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Top Endpoints', 'vibes' ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-top-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.top-endpoints',
				'queried' => 5,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the catgory box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_category_box() {
		$result  = '<div class="vibes-40-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Content', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-category">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.category' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 7,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the initiator box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_initiator_box() {
		$result  = '<div class="vibes-33-module vibes-33-left-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Initiators', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-initiator">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.initiator' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 4,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the security box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_security_box() {
		$result  = '<div class="vibes-33-module vibes-33-center-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Protocols', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-security">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.security' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 4,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get the cache box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_cache_box() {
		$result  = '<div class="vibes-33-module vibes-33-right-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Browser Cache', 'vibes' ) . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-cache">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->source . '.cache' . ( 'none' !== $this->mode ? '~' . $this->mode : '' ),
				'queried' => 4,
				'domain' => $this->domain,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . VIBES_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"vibes_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_vibes' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		if ( '' !== $this->id ) {
			$result .= '  id:"' . $this->id . '",';
		}
		$result .= '  type:"' . $args['query'] . '",';
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && ( 'main-webvital-chart' === $args['query'] ) ) {
			$result .= '$(".vibes-chart-button").removeClass("not-ready");';
			$result .= '$("#vibes-chart-button-lcp").addClass("active");';
		}
		if ( array_key_exists( 'query', $args ) && ( 'main-navigation-chart' === $args['query'] ) ) {
			$result .= '$(".vibes-chart-button").removeClass("not-ready");';
			$result .= '$("#vibes-chart-button-time").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @param   array $replace Optional. The args to replace or add.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [] ) {
		$params           = [];
		$params['type']   = $this->source . '.' . $this->type . ( 'none' !== $this->mode ? '~' . $this->mode : '' );
		$params['site']   = $this->site;
		$params['domain'] = $this->domain;
		if ( 'resource' !== $this->source ) {
			$params['authent'] = $this->authent;
			$params['country'] = $this->country;
			$params['channel'] = $this->channel;
		}
		if ( '' !== $this->id ) {
			$params['id'] = $this->id;
		}
		if ( '' !== $this->extra ) {
			$params['extra'] = $this->extra;
		}
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			if ( 'type' === $key ) {
				$params[ $key ] = $this->source . '.' . $arg;
			} else {
				$params[ $key ] = $arg;
			}
		}
		$url = admin_url( 'admin.php?page=vibes-' . $this->source . '-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . $arg;
			}
		}
		return $url;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<span class="vibes-datepicker"><img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="vibes-datepicker-value"></span></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.vibes-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
		$result .= ' }';
		$result .= ' $(".vibes-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date( $this->source ) . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'vibes' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'vibes' ) . '", applyLabel: "' . esc_html__( 'Apply', 'vibes' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'vibes' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'vibes' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'vibes' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'vibes' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".vibes-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], [ 'domain' => $this->domain ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url.replaceAll("&amp;", String.fromCharCode(38)));';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
