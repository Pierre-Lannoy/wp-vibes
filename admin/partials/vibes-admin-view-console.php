<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Vibes\System\Option;

if ( ! Option::network_get( 'livelog' ) ) {
	Option::network_set( 'livelog', true );
}

wp_localize_script(
	VIBES_LIVELOG_ID,
	'livelog',
	[
		'restUrl'   => esc_url_raw( rest_url() . VIBES_REST_NAMESPACE . '/livelog' ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
		'buffer'    => 200,
		'frequency' => 750,
	]
);

wp_enqueue_style( VIBES_LIVELOG_ID );
wp_enqueue_script( VIBES_LIVELOG_ID );
?>

<div class="wrap">
	<h2><?php echo sprintf( esc_html__( '%s Live Performance Signals', 'vibes' ), VIBES_PRODUCT_NAME );?></h2>
    <div class="media-toolbar wp-filter vibes-pilot-toolbar" style="border-radius:4px;">
        <div class="media-toolbar-secondary" data-children-count="2">
            <div class="view-switch media-grid-view-switch">
                <span class="dashicons dashicons-controls-play vibes-control vibes-control-inactive" id="vibes-control-play"></span>
                <span class="dashicons dashicons-controls-pause vibes-control vibes-control-inactive" id="vibes-control-pause"></span>
            </div>
            <select id="vibes-select-filter" class="attachment-filters">
                <option value="all"><?php echo esc_html__( 'All', 'vibes' );?></option>
                <option value="webvital"><?php echo esc_html__( 'Web Vitals', 'vibes' );?></option>
                <option value="source"><?php echo esc_html__( 'Sources', 'vibes' );?>
                <option value="navigation"><?php echo esc_html__( 'Navigation', 'vibes' );?>
            </select>
            <div class="view-switch media-grid-view-switch" style="display: inline;">
                <span class="vibes-control-hint" style="float: right">initializing&nbsp;&nbsp;&nbsp;⚪</span>
            </div>
        </div></div>

    <div class="vibes-logger-view"><div id="vibes-logger-lines"></div></div>

</div>
