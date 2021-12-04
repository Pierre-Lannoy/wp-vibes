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

use Vibes\System\Role;

wp_enqueue_script( 'vibes-moment-with-locale' );
wp_enqueue_script( 'vibes-daterangepicker' );
wp_enqueue_script( 'vibes-chartist' );
wp_enqueue_script( 'vibes-chartist-tooltip' );
wp_enqueue_script( VIBES_ASSETS_ID );
wp_enqueue_style( VIBES_ASSETS_ID );
wp_enqueue_style( 'vibes-daterangepicker' );
wp_enqueue_style( 'vibes-tooltip' );
wp_enqueue_style( 'vibes-chartist' );
wp_enqueue_style( 'vibes-chartist-tooltip' );


?>

<div class="wrap">
	<div class="vibes-dashboard">
		<div class="vibes-row">
			<?php echo wp_kses( $analytics->get_title_bar(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
		</div>
		<?php if ( 'summary' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo wp_kses( $analytics->get_top_domain_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_category_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo wp_kses( $analytics->get_top_authority_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_category_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo wp_kses( $analytics->get_top_endpoint_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_category_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'summary' === $analytics->type || 'domain' === $analytics->type || 'authority' === $analytics->type || 'endpoint' === $analytics->type ) { ?>
		    <?php $network = ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site ); ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-33-33-33-line">
					<?php echo wp_kses( $analytics->get_initiator_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_security_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_cache_box(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			</div>
			<div class="vibes-row first-full-row">
				<?php echo wp_kses( $analytics->get_categories_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
			<div class="vibes-row <?php if ( ! $network ) { echo ' last-full-row'; } ?>">
				<?php echo wp_kses( $analytics->get_mimes_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
			<?php if ( $network ) { ?>
				<div class="vibes-row last-full-row">
					<?php echo wp_kses( $analytics->get_sites_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			<?php } ?>
		<?php } ?>
		<?php if ( 'domains' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo wp_kses( $analytics->get_domains_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo wp_kses( $analytics->get_authorities_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo wp_kses( $analytics->get_endpoints_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
		<?php } ?>
	</div>
</div>
