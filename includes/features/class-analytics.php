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
	 * The query filter fro the previous range.
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
		'redirect' => '#73879C',
		'dns'      => '#73879C',
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
	 * @since    1.0.0
	 */
	public function __construct( $source, $domain, $type, $site, $start, $end, $id, $reload, $extra, $authent, $country ) {
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
				return $this->query_list( 'endpoints' );
			case 'sites':
				if ( 'webvital' === $this->source ) {
					return $this->query_webvital_list( 'sites' );
				}
				return $this->query_list( 'sites' );
			case 'class':
			case 'device':
				return $this->query_webvital( $query, $sub );
			case 'main-webvital-chart':
				return $this->query_webvital_chart();

			case 'main-chart':
				return $this->query_chart();
			case 'map':
				return $this->query_map();
			case 'kpi':
				return $this->query_kpi( $queried );

			case 'codes':
				return $this->query_list( 'codes' );
			case 'schemes':
				return $this->query_list( 'schemes' );
			case 'methods':
				return $this->query_list( 'methods' );
			case 'countries':
				return $this->query_list( 'countries' );
			case 'code':
				return $this->query_pie( 'code', (int) $queried );
			case 'sssssssssecurity':
				return $this->query_pie( 'sssssssssecurity', (int) $queried );
			case 'method':
				return $this->query_pie( 'method', (int) $queried );
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
					$pos = 'width:100%;';
				} else {
					$val = (int) round( 100 * $row[ 'pct_' . $field . '_' . $spec ] );
					$pos = 'width:' . ( $val + 1 + ( 2 * $key ) ) . '%;';
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
					$result .= '<div class="vibes-webvital-bar-' . $spec . '" style="' . $width_str . $shift_str . $up_str . '"><span class="vibes-webvital-percent" style="' . $pos . '">' . $val . '%</span></div>';
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
		//TODO:\DecaLog\Engine::eventsLogger( VIBES_SLUG )->warning( print_r($this->filter,true) );
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
		}
		$data    = $data[0];
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

		$result .= '<span>aaa aaa</span>';

		$result .= '<div class="vibes-webvital-separator">&nbsp;</div>';

		$result .= '<span>aaa aaa</span>';

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
			$result .= '<div class="vibes-top-line">';
			$result .= '<div class="vibes-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="vibes-top-line-title-text"><a href="' . esc_url( $url ) . '">' . $data[ $cpt ][ $group ] . '</a></span>';
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
		$bblank = round( 100 * ( $span['start'] - $start ) / $duration, 3 );
		$lblank = round( 100 * ( $span['duration'] ) / $duration, 3 );
		if ( 0.1 > $lblank ) {
			$lblank = 0.1;
		}
		$eblank = 100.0 - $bblank - $lblank;
		if ( 0 > $eblank ) {
			$lblank += $eblank;
			$eblank  = 0;
		}
		$tick    = round( 200 * $this->traces_tick / $duration, 3 );
		$color   = $this->span_colors[ $span['name'] ];
		$result .= '<div class="vibes-span-timeline" style="background-size: ' . $tick . '% 100%;">';
		$result .= '<div class="vibes-span-timeline-blank" style="width:' . $bblank . '%">';
		$result .= '</div>';
		$result .= '<div class="vibes-span-timeline-line" style="background-color:' . $color . ';width:' . $lblank . '%">';
		$result .= '</div>';
		$result .= '<div class="vibes-span-timeline-blank" style="width:' . $eblank . '%;">';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '</div>';
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
		$duration = 0;
		$spans    = [];
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
				$duration     += $s['duration'];
				$spans[]       = $s;
			}
		}
		if ( 0 < $duration % $this->traces_tick ) {
			$duration = $this->traces_tick * ( 1 + (int) ( $duration / $this->traces_tick ) );
		} else {
			$duration += $this->traces_tick;
		}
		if ( 3 * $this->traces_tick > $duration ) {
			$duration = 3 * $this->traces_tick;
		}/* else {
			$duration *= 1.02;
		}*/
		foreach ( $spans as $span ) {
			$result .= $this->get_span( $span, 0, 0, $duration * 1.02 );
		}
		$result .= '</div>';
		return $result;
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

			case 'codes':
				$group = 'code';
				break;
			case 'schemes':
				$group = 'scheme';
				break;
			case 'methods':
				$group = 'verb';
				break;
			case 'countries':
				$group = 'country';
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

				case 'codes':
					if ( '0' === $name ) {
						$name = '000';
					}
					$code = (int) $name;
					if ( 100 > $code ) {
						$http = '0xx';
					} elseif ( 200 > $code ) {
						$http = '1xx';
					} elseif ( 300 > $code ) {
						$http = '2xx';
					} elseif ( 400 > $code ) {
						$http = '3xx';
					} elseif ( 500 > $code ) {
						$http = '4xx';
					} elseif ( 600 > $code ) {
						$http = '5xx';
					} else {
						$http = 'nxx';
					}
					$name  = '<span class="vibes-http vibes-http-' . $http . '">' . $name . '</span>&nbsp;&nbsp;<span class="vibes-table-text">' . Http::$http_status_codes[ $code ] . '</span>';
					$group = 'code';
					break;
				case 'schemes':
					$icon = Feather\Icons::get_base64( 'unlock', 'none', '#E74C3C' );
					if ( 'HTTPS' === strtoupper( $name ) ) {
						$icon = Feather\Icons::get_base64( 'lock', 'none', '#18BB9C' );
					}
					$name  = '<img style="width:14px;vertical-align:text-top;" src="' . $icon . '" />&nbsp;&nbsp;<span class="vibes-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'scheme';
					break;
				case 'methods':
					$name  = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'code', 'none', '#73879C' ) . '" />&nbsp;&nbsp;<span class="vibes-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'verb';
					break;
				case 'countries':
					if ( $other ) {
						$name = esc_html__( 'Other', 'vibes' );
					} else {
						$country_name = L10n::get_country_name( $name );
						$icon         = $geoip->get_flag_from_country_code( $name, '', 'width:16px;vertical-align:baseline;' );
						$name         = $icon . '&nbsp;&nbsp;<span class="vibes-table-text" style="vertical-align: text-bottom;">' . $country_name . '</span>';
					}
					$group = 'country';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'] * $factor, 2, false, '&nbsp;' );
			//$in    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="vibes-table-text">' . Conversion::data_shorten( $row['sum_kb_in'] * 1024, 2, false, '&nbsp;' ) . '</span>';
			//$out   = '<span class="vibes-table-text">' . Conversion::data_shorten( $row['sum_kb_out'] * 1024, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			if ( 0 < $row['avg_size'] ) {
				$data = Conversion::data_shorten( (int) $row['avg_size'], 2, false, '&nbsp;' );
			} else {
				$data = '-';
			}
			$cache = round( 100 * $row['avg_cache'], 1 ) . '%';

			/*if ( 1 < $row['sum_hit'] ) {
				$min = Conversion::number_shorten( $row['min_latency'], 0 );
				if ( false !== strpos( $min, 'K' ) ) {
					$min = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'vibes' ), $min );
				} else {
					$min = $min . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' );
				}
				$max = Conversion::number_shorten( $row['max_latency'], 0 );
				if ( false !== strpos( $max, 'K' ) ) {
					$max = str_replace( 'K', esc_html_x( 's', 'Unit symbol - Stands for "second".', 'vibes' ), $max );
				} else {
					$max = $max . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' );
				}
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' ) . '&nbsp;<small>(' . $min . '→' . $max . ')</small>';
			} else {
				$latency = (int) $row['avg_latency'] . '&nbsp;' . esc_html_x( 'ms', 'Unit symbol - Stands for "millisecond".', 'vibes' );
			}*/
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
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_map() {
		$uuid   = UUID::generate_unique_id( 5 );
		$data   = Schema::get_grouped_list( $this->source, 'country', [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$series = [];
		foreach ( $data as $datum ) {
			if ( array_key_exists( 'country', $datum ) && ! empty( $datum['country'] ) ) {
				$series[ strtoupper( $datum['country'] ) ] = $datum['sum_hit'];
			}
		}
		$plus    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'plus-square', 'none', '#73879C' ) . '"/>';
		$minus   = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'minus-square', 'none', '#73879C' ) . '"/>';
		$result  = '<div class="vibes-map-handler">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var mapdata' . $uuid . ' = ' . wp_json_encode( $series ) . ';';
		$result .= ' $(".vibes-map-handler").vectorMap({';
		$result .= ' map: "world_mill",';
		$result .= ' backgroundColor: "#FFFFFF",';
		$result .= ' series: {';
		$result .= '  regions: [{';
		$result .= '   values: mapdata' . $uuid . ',';
		$result .= '   scale: ["#BDC7D1", "#73879C"],';
		$result .= '   normalizeFunction: "polynomial"';
		$result .= '  }]';
		$result .= ' },';
		$result .= '  regionStyle: {';
		$result .= '   initial: {fill: "#EEEEEE", "fill-opacity": 0.7},';
		$result .= '   hover: {"fill-opacity": 1,cursor: "default"},';
		$result .= '   selected: {},';
		$result .= '   selectedHover: {},';
		$result .= ' },';
		$result .= ' onRegionTipShow: function(e, el, code){if (mapdata' . $uuid . '[code]){el.html(el.html() + " (" + mapdata' . $uuid . '[code] + " ' . esc_html__( 'calls', 'vibes' ) . ')")};},';
		$result .= ' });';
		$result .= ' $(".jvectormap-zoomin").html(\'' . $plus . '\');';
		$result .= ' $(".jvectormap-zoomout").html(\'' . $minus . '\');';
		$result .= '});';
		$result .= '</script>';
		return [ 'vibes-map' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_webvital_chart() {
		$uuid    = UUID::generate_unique_id( 5 );
		$data    = Schema::get_time_series( 'webvital', $this->filter, ! $this->is_today );
		$series  = [];
		$metrics = array_merge( WebVitals::$rated_metrics, WebVitals::$unrated_metrics );
		$start   = '';
		$max     = [];
		foreach ( $data as $timestamp => $row ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			foreach ( $metrics as $metric ) {
				$val = WebVitals::get_graphable_value( $metric, $row[ 'avg_' . $metric ] );
				if ( ( array_key_exists( strtolower( $metric ), $max ) && $max[ strtolower( $metric ) ] < $val ) || ! array_key_exists( strtolower( $metric ), $max ) ) {
					$max[ strtolower( $metric ) ] = $val;
				}
				$series[ strtolower( $metric ) ]['avg'][] = [
					'x' => $ts,
					'y' => $val,
				];
				foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
					if ( array_key_exists( 'pct_' . $metric . '_' . $field, $row ) ) {
						$series[ strtolower( $metric ) ][ $field ][] = [
							'x' => $ts,
							'y' => round( 100 * $row[ 'pct_' . $metric . '_' . $field ], 1 ),
						];
					}
				}
			}
		}
		$before = [
			'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
			'y' => 'null',
		];
		$after  = [
			'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
			'y' => 'null',
		];
		$scale  = [];
		foreach ( $metrics as $metric ) {
			$metric = strtolower( $metric );
			array_unshift( $series[ $metric ]['avg'], $before );
			$series[ $metric ]['avg'][] = $after;
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
			foreach ( [ 'good', 'impr', 'poor' ] as $field ) {
				if ( 'ttfb' !== $metric ) {
					array_unshift( $series[ $metric ][ $field ], $before );
					$series[ $metric ][ $field ][] = $after;
				}
			}
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
		$divisor = $this->duration + 1;
		while ( 11 < $divisor ) {
			foreach ( [ 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317, 331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397 ] as $divider ) {
				if ( 0 === $divisor % $divider ) {
					$divisor = $divisor / $divider;
					break;
				}
			}
		}
		$result = '<div class="vibes-multichart-handler">';
		foreach ( $metrics as $metric ) {
			$metric  = strtolower( $metric );
			$result .= '<div class="vibes-multichart-item' . ( 'cls' === $metric ? ' active' : '' ) . '" id="vibes-chart-' . $metric . '">';
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
			$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.FixedScaleAxis, ' . $scale[ $metric ] . $max[ $metric ] . 'low: 0, labelInterpolationFnc: function (value) {return value.toString()' . ( 'cls' === $metric ? '' : ' + " ms"' ) . ';}},';
			$result .= ' };';
			$result .= ' var ' . $metric . '_bars_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 0,high: 100, low: 0,';
			$result .= '  plugins: [' . $metric . '_bars_tooltip' . $uuid . '],';
			$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
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
	private function query_chart() {
		$uuid           = UUID::generate_unique_id( 5 );
		$data_total     = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data_uptime    = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_failure_codes, true );
		$data_error     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', array_diff( Http::$http_error_codes, Http::$http_quota_codes ), false );
		$data_success   = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_success_codes, false );
		$data_quota     = Schema::get_time_series( $this->filter, ! $this->is_today, 'code', Http::$http_quota_codes, false );
		$series_uptime  = [];
		$suc            = [];
		$err            = [];
		$quo            = [];
		$series_success = [];
		$series_error   = [];
		$series_quota   = [];
		$call_max       = 0;
		$kbin           = [];
		$kbout          = [];
		$series_kbin    = [];
		$series_kbout   = [];
		$data_max       = 0;
		$start          = '';
		foreach ( $data_total as $timestamp => $total ) {
			if ( '' === $start ) {
				$start = $timestamp;
			}
			$ts = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
			// Calls.
			if ( array_key_exists( $timestamp, $data_success ) ) {
				$val = $data_success[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$suc[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$suc[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_error ) ) {
				$val = $data_error[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$err[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$err[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			if ( array_key_exists( $timestamp, $data_quota ) ) {
				$val = $data_quota[ $timestamp ]['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$quo[] = [
					'x' => $ts,
					'y' => $val,
				];
			} else {
				$quo[] = [
					'x' => $ts,
					'y' => 0,
				];
			}
			// Data.
			$val = $total['sum_kb_in'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbin[] = [
				'x' => $ts,
				'y' => $val,
			];
			$val    = $total['sum_kb_out'] * 1024;
			if ( $val > $data_max ) {
				$data_max = $val;
			}
			$kbout[] = [
				'x' => $ts,
				'y' => $val,
			];
			// Uptime.
			if ( array_key_exists( $timestamp, $data_uptime ) ) {
				if ( 0 !== $total['sum_hit'] ) {
					$val             = round( $data_uptime[ $timestamp ]['sum_hit'] * 100 / $total['sum_hit'], 2 );
					$series_uptime[] = [
						'x' => $ts,
						'y' => $val,
					];
				} else {
					$series_uptime[] = [
						'x' => $ts,
						'y' => 100,
					];
				}
			} else {
				$series_uptime[] = [
					'x' => $ts,
					'y' => 100,
				];
			}
		}
		$before = [
			'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
			'y' => 'null',
		];
		$after  = [
			'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
			'y' => 'null',
		];
		// Calls.
		$short     = Conversion::number_shorten( $call_max, 2, true );
		$call_max  = 0.5 + floor( $call_max / $short['divisor'] );
		$call_abbr = $short['abbreviation'];
		foreach ( $suc as $item ) {
			$item['y']        = $item['y'] / $short['divisor'];
			$series_success[] = $item;
		}
		foreach ( $err as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_error[] = $item;
		}
		foreach ( $quo as $item ) {
			$item['y']      = $item['y'] / $short['divisor'];
			$series_quota[] = $item;
		}
		array_unshift( $series_success, $before );
		array_unshift( $series_error, $before );
		array_unshift( $series_quota, $before );
		$series_success[] = $after;
		$series_error[]   = $after;
		$series_quota[]   = $after;
		$json_call        = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Success', 'vibes' ),
						'data' => $series_success,
					],
					[
						'name' => esc_html__( 'Error', 'vibes' ),
						'data' => $series_error,
					],
					[
						'name' => esc_html__( 'Quota Error', 'vibes' ),
						'data' => $series_quota,
					],
				],
			]
		);
		$json_call        = str_replace( '"x":"new', '"x":new', $json_call );
		$json_call        = str_replace( ')","y"', '),"y"', $json_call );
		$json_call        = str_replace( '"null"', 'null', $json_call );
		// Data.
		$short     = Conversion::data_shorten( $data_max, 2, true );
		$data_max  = (int) ceil( $data_max / $short['divisor'] );
		$data_abbr = $short['abbreviation'];
		foreach ( $kbin as $kb ) {
			$kb['y']       = $kb['y'] / $short['divisor'];
			$series_kbin[] = $kb;
		}
		foreach ( $kbout as $kb ) {
			$kb['y']        = $kb['y'] / $short['divisor'];
			$series_kbout[] = $kb;
		}
		array_unshift( $series_kbin, $before );
		array_unshift( $series_kbout, $before );
		$series_kbin[]  = $after;
		$series_kbout[] = $after;
		$json_data      = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Incoming Data', 'vibes' ),
						'data' => $series_kbin,
					],
					[
						'name' => esc_html__( 'Outcoming Data', 'vibes' ),
						'data' => $series_kbout,
					],
				],
			]
		);
		$json_data      = str_replace( '"x":"new', '"x":new', $json_data );
		$json_data      = str_replace( ')","y"', '),"y"', $json_data );
		$json_data      = str_replace( '"null"', 'null', $json_data );
		// Uptime.
		array_unshift( $series_uptime, $before );
		$series_uptime[] = $after;
		$json_uptime     = wp_json_encode(
			[
				'series' => [
					[
						'name' => esc_html__( 'Perceived Uptime', 'vibes' ),
						'data' => $series_uptime,
					],
				],
			]
		);
		$json_uptime     = str_replace( '"x":"new', '"x":new', $json_uptime );
		$json_uptime     = str_replace( ')","y"', '),"y"', $json_uptime );
		$json_uptime     = str_replace( '"null"', 'null', $json_uptime );
		// Rendering.
		$divisor = $this->duration + 1;
		while ( 11 < $divisor ) {
			foreach ( [ 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317, 331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397 ] as $divider ) {
				if ( 0 === $divisor % $divider ) {
					$divisor = $divisor / $divider;
					break;
				}
			}
		}
		$result  = '<div class="vibes-multichart-handler">';
		$result .= '<div class="vibes-multichart-item active" id="vibes-chart-calls">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var call_data' . $uuid . ' = ' . $json_call . ';';
		$result .= ' var call_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var call_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [call_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $call_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $call_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#vibes-chart-calls", call_data' . $uuid . ', call_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="vibes-multichart-item" id="vibes-chart-data">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data_data' . $uuid . ' = ' . $json_data . ';';
		$result .= ' var data_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var data_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [data_tooltip' . $uuid . '],';
		$result .= '  axisX: {type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $data_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $data_abbr . '";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#vibes-chart-data", data_data' . $uuid . ', data_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '<div class="vibes-multichart-item" id="vibes-chart-uptime">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var uptime_data' . $uuid . ' = ' . $json_uptime . ';';
		$result .= ' var uptime_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
		$result .= ' var uptime_option' . $uuid . ' = {';
		$result .= '  height: 300,';
		$result .= '  fullWidth: true,';
		$result .= '  showArea: true,';
		$result .= '  showLine: true,';
		$result .= '  showPoint: false,';
		$result .= '  plugins: [uptime_tooltip' . $uuid . '],';
		$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
		$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
		$result .= ' };';
		$result .= ' new Chartist.Line("#vibes-chart-uptime", uptime_data' . $uuid . ', uptime_option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		$result .= '</div>';
		return [ 'vibes-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		if ( 'call' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$current = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$previous = (float) $pdata['sum_hit'];
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '.' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'avg_latency', $data ) && ! empty( $data['avg_latency'] ) ) {
				$result[ 'kpi-bottom-' . $queried ] = '<span class="vibes-kpi-large-bottom-text">' . sprintf( esc_html__( 'avg latency: %s ms.', 'vibes' ), (int) $data['avg_latency'] ) . '</span>';
			}
		}
		if ( 'data' === $queried ) {
			$data         = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata        = Schema::get_std_kpi( $this->previous );
			$current_in   = 0.0;
			$current_out  = 0.0;
			$previous_in  = 0.0;
			$previous_out = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_kb_in', $data ) && ! empty( $data['sum_kb_in'] ) ) {
				$current_in = (float) $data['sum_kb_in'] * 1024;
			}
			if ( is_array( $data ) && array_key_exists( 'sum_kb_out', $data ) && ! empty( $data['sum_kb_out'] ) ) {
				$current_out = (float) $data['sum_kb_out'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_in', $pdata ) && ! empty( $pdata['sum_kb_in'] ) ) {
				$previous_in = (float) $pdata['sum_kb_in'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_out', $pdata ) && ! empty( $pdata['sum_kb_out'] ) ) {
				$previous_out = (float) $pdata['sum_kb_out'] * 1024;
			}
			$current                          = $current_in + $current_out;
			$previous                         = $previous_in + $previous_out;
			$result[ 'kpi-main-' . $queried ] = Conversion::data_shorten( $current, 1, false, '&nbsp;' );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '.' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			$in                                 = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="vibes-kpi-large-bottom-text">' . Conversion::data_shorten( $current_in, 2, false, '&nbsp;' ) . '</span>';
			$out                                = '<span class="vibes-kpi-large-bottom-text">' . Conversion::data_shorten( $current_out, 2, false, '&nbsp;' ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$result[ 'kpi-bottom-' . $queried ] = $in . ' &nbsp;&nbsp; ' . $out;
		}
		if ( 'server' === $queried || 'quota' === $queried || 'pass' === $queried || 'uptime' === $queried ) {
			$not = false;
			if ( 'server' === $queried ) {
				$codes = Http::$http_error_codes;
			} elseif ( 'quota' === $queried ) {
				$codes = Http::$http_quota_codes;
			} elseif ( 'pass' === $queried ) {
				$codes = Http::$http_effective_pass_codes;
			} elseif ( 'uptime' === $queried ) {
				$codes = Http::$http_failure_codes;
				$not   = true;
			}
			$base        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pbase       = Schema::get_std_kpi( $this->previous );
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'code', $codes, $not );
			$pdata       = Schema::get_std_kpi( $this->previous, true, 'code', $codes, $not );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $base ) && ! empty( $base['sum_hit'] ) ) {
				$base_value = (float) $base['sum_hit'];
			}
			if ( is_array( $pbase ) && array_key_exists( 'sum_hit', $pbase ) && ! empty( $pbase['sum_hit'] ) ) {
				$pbase_value = (float) $pbase['sum_hit'];
			}
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$data_value = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$pdata_value = (float) $pdata['sum_hit'];
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '&nbsp;%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100&nbsp;%';
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0&nbsp;%';
				} else {
					$result[ 'kpi-main-' . $queried ] = '-';
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '.' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'server':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="vibes-kpi-large-bottom-text">' . sprintf( esc_html__( '%s calls in error', 'vibes' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'quota':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="vibes-kpi-large-bottom-text">' . sprintf( esc_html__( '%s blocked calls', 'vibes' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'pass':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="vibes-kpi-large-bottom-text">' . sprintf( esc_html__( '%s successful calls', 'vibes' ), Conversion::number_shorten( $data_value, 2, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( 1 - ( $data_value / $base_value ) ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no downtime', 'vibes' );
						} else {
							$duration = sprintf( esc_html__( 'down %s', 'vibes' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="vibes-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
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
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'vibes' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'vibes' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'vibes' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'vibes' );
						break;
					default:
						$title = esc_html__( 'Domains Details', 'vibes' );
				}
				break;
			case 'domain':
				$title = esc_html__( 'Domain Summary', 'vibes' );
				break;
			case 'authorities':
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'vibes' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'vibes' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'vibes' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'vibes' );
						break;
					default:
						$title = esc_html__( 'Domain Details', 'vibes' );
				}
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
				switch ( $this->extra ) {
					case 'codes':
						$title = esc_html__( 'HTTP Codes Details', 'vibes' );
						break;
					case 'schemes':
						$title = esc_html__( 'Protocols Details', 'vibes' );
						break;
					case 'methods':
						$title = esc_html__( 'Methods Details', 'vibes' );
						break;
					case 'countries':
						$title = esc_html__( 'Countries Details', 'vibes' );
						break;
					default:
						$title = esc_html__( 'Subdomain Details', 'vibes' );
				}
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
				$title    = 'unknow';
				$subtitle = 'unknown';

		}
		switch ( $this->source ) {
			case 'webvital':
				$name = esc_html__( 'Web Vitals', 'vibes' );
				break;
			case 'resource':
				$name = esc_html__( 'Resources', 'vibes' );
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
		switch ( $this->type ) {
			case 'summary':
				$title = esc_html__( 'Main Summary', 'vibes' );
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
			$pickers = $this->get_country_selector() . $this->get_user_selector() . $this->get_date_box();
		} else {
			$pickers = $this->get_date_box();
		}
		$result .= '<span class="vibes-picker">' . $pickers . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="vibes-box vibes-box-full-line">';
		$result .= '<div class="vibes-kpi-bar">';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'call' ) . '</div>';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'data' ) . '</div>';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'server' ) . '</div>';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'quota' ) . '</div>';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'pass' ) . '</div>';
		$result .= '<div class="vibes-kpi-large">' . $this->get_large_kpi( 'uptime' ) . '</div>';
		$result .= '</div>';
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
			$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Metrics Variations', 'vibes' ) . '<span class="vibes-module-more">' . $detail . '</span></span></div>';
			$result .= '<div class="vibes-module-content" id="vibes-webvital-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			$result .= $this->get_refresh_script(
				[
					'query'   => 'main-webvital-chart',
					'country' => $this->country,
					'authent' => $this->authent,
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
	public function get_main_chart() {
		if ( 1 < $this->duration ) {
			$help_calls  = esc_html__( 'Responses types distribution.', 'vibes' );
			$help_data   = esc_html__( 'Data volume distribution.', 'vibes' );
			$help_uptime = esc_html__( 'Uptime variation.', 'vibes' );
			$detail      = '<span class="vibes-chart-button not-ready left" id="vibes-chart-button-calls" data-position="left" data-tooltip="' . $help_calls . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
			$detail     .= '&nbsp;&nbsp;&nbsp;<span class="vibes-chart-button not-ready left" id="vibes-chart-button-data" data-position="left" data-tooltip="' . $help_data . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'link-2', 'none', '#73879C' ) . '" /></span>&nbsp;&nbsp;&nbsp;';
			$detail     .= '<span class="vibes-chart-button not-ready left" id="vibes-chart-button-uptime" data-position="left" data-tooltip="' . $help_uptime . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
			$result      = '<div class="vibes-row">';
			$result     .= '<div class="vibes-box vibes-box-full-line">';
			$result     .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Metrics Variations', 'vibes' ) . '<span class="vibes-module-more">' . $detail . '</span></span></div>';
			$result     .= '<div class="vibes-module-content" id="vibes-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result     .= '</div>';
			$result     .= '</div>';
			$result     .= $this->get_refresh_script(
				[
					'query'   => 'main-chart',
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
				'query'   => 'resource.mimes',
				'queried' => 0,
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
				'query'   => 'resource.categories',
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

			case 'codes':
				$title = esc_html__( 'All HTTP Codes', 'vibes' );
				break;
			case 'schemes':
				$title = esc_html__( 'All Protocols', 'vibes' );
				break;
			case 'methods':
				$title = esc_html__( 'All Methods', 'vibes' );
				break;
			case 'countries':
				$title = esc_html__( 'All Countries', 'vibes' );
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
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_map_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			default:
				$url = $this->get_url(
					[ 'domain' ],
					[
						'type'  => 'domains',
						'extra' => 'countries',
					]
				);
		}
		$detail  = '<a href="' . esc_url( $url ) . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all countries.', 'vibes' );
		$result  = '<div class="vibes-60-module">';
		$result .= '<div class="vibes-module-title-bar"><span class="vibes-module-title">' . esc_html__( 'Countries', 'vibes' ) . '</span><span class="vibes-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="vibes-module-content" id="vibes-map">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'map',
				'queried' => 0,
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
				'query'   => $this->source . '.category',
				'queried' => 7,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
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
				'query'   => $this->source . '.initiator',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
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
				'query'   => $this->source . '.security',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
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
				'query'   => $this->source . '.cache',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'call':
				$icon  = Feather\Icons::get_base64( 'hash', 'none', '#73879C' );
				$title = esc_html_x( 'Number of Calls', 'Noun - Number API calls.', 'vibes' );
				$help  = esc_html__( 'Number of API calls.', 'vibes' );
				break;
			case 'data':
				$icon  = Feather\Icons::get_base64( 'link-2', 'none', '#73879C' );
				$title = esc_html_x( 'Data Volume', 'Noun - Volume of transferred data.', 'vibes' );
				$help  = esc_html__( 'Volume of transferred data.', 'vibes' );
				break;
			case 'server':
				$icon  = Feather\Icons::get_base64( 'x-octagon', 'none', '#73879C' );
				$title = esc_html_x( 'Server Error Rate', 'Noun - Ratio of the number of HTTP errors to the total number of calls.', 'vibes' );
				$help  = esc_html__( 'Ratio of the number of HTTP errors to the total number of calls.', 'vibes' );
				break;
			case 'quota':
				$icon  = Feather\Icons::get_base64( 'shield-off', 'none', '#73879C' );
				$title = esc_html_x( 'Quotas Error Rate', 'Noun - Ratio of the quota enforcement number to the total number of calls.', 'vibes' );
				$help  = esc_html__( 'Ratio of the quota enforcement number to the total number of calls.', 'vibes' );
				break;
			case 'pass':
				$icon  = Feather\Icons::get_base64( 'check-circle', 'none', '#73879C' );
				$title = esc_html_x( 'Effective Pass Rate', 'Noun - Ratio of the number of HTTP success to the total number of calls.', 'vibes' );
				$help  = esc_html__( 'Ratio of the number of HTTP success to the total number of calls.', 'vibes' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Perceived Uptime', 'Noun - Perceived uptime, from the viewpoint of the site.', 'vibes' );
				$help  = esc_html__( 'Perceived uptime, from the viewpoint of the site.', 'vibes' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="vibes-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="vibes-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="vibes-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="vibes-kpi-large-middle"><div class="vibes-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="vibes-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="vibes-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
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
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . VIBES_ADMIN_URL . 'medias/three-dots.svg" />';
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
		//$result .= '  type:"' . $this->source . '.' . $this->type . '",';
		$result .= '  type:"' . $args['query'] . '",';
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' console.log(val);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && ( 'main-chart' === $args['query'] || 'main-webvital-chart' === $args['query'] ) ) {
			$result .= '$(".vibes-chart-button").removeClass("not-ready");';
			$result .= '$("#vibes-chart-button-lcp").addClass("active");';
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
		$params['type']   = $this->source . '.' . $this->type;
		$params['site']   = $this->site;
		$params['domain'] = $this->domain;
		if ( 'resource' !== $this->source ) {
			$params['authent'] = $this->authent;
			$params['country'] = $this->country;
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
	 * Get a large kpi box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_switch_box( $bound ) {
		$enabled = false;
		$other   = false;
		$other_t = 'both';
		if ( 'inbound' === $bound ) {
			$enabled = $this->has_inbound;
			$other   = $this->is_outbound;
			$other_t = 'outbound';
		}
		if ( 'outbound' === $bound ) {
			$enabled = $this->has_outbound;
			$other   = $this->is_inbound;
			$other_t = 'inbound';
		}
		if ( $enabled ) {
			$opacity = '';
			if ( 'inbound' === $bound ) {
				$checked = $this->is_inbound;
			}
			if ( 'outbound' === $bound ) {
				$checked = $this->is_outbound;
			}
		} else {
			$opacity = ' style="opacity:0.4"';
			$checked = false;
		}
		$result = '<input type="checkbox" class="vibes-input-' . $bound . '-switch"' . ( $checked ? ' checked' : '' ) . ' />';
		// phpcs:ignore
		$result .= '&nbsp;<span class="vibes-text-' . $bound . '-switch"' . $opacity . '>' . esc_html__( $bound, 'vibes' ) . '</span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var elem = document.querySelector(".vibes-input-' . $bound . '-switch");';
		$result .= ' var params = {size: "small", color: "#5A738E", disabledOpacity:0.6 };';
		$result .= ' var ' . $bound . ' = new Switchery(elem, params);';
		if ( $enabled ) {
			$result .= ' ' . $bound . '.enable();';
		} else {
			$result .= ' ' . $bound . '.disable();';
		}
		$result .= ' elem.onchange = function() {';
		$result .= '  var url="' . $this->get_url( [ 'context' ], [ 'domain' => $this->domain ] ) . '";';
		if ( $other ) {
			$result .= ' if (!elem.checked) {url = url + "&context=' . $other_t . '";}';
		} else {
			$result .= ' if (elem.checked) {url = url + "&context=' . $other_t . '";}';
		}
		$result .= '  $(location).attr("href", url);';
		$result .= ' };';
		$result .= '});';
		$result .= '</script>';
		return $result;
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
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
