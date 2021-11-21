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
			<?php echo $analytics->get_title_bar(); ?>
		</div>
		<?php if ( 'summary' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo $analytics->get_top_domain_box(); ?>
					<?php echo $analytics->get_category_box(); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo $analytics->get_top_authority_box(); ?>
					<?php echo $analytics->get_category_box(); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-60-40-line">
					<?php echo $analytics->get_top_endpoint_box(); ?>
					<?php echo $analytics->get_category_box(); ?>
				</div>
			</div>
		<?php } ?>
		<?php if ( 'summary' === $analytics->type || 'domain' === $analytics->type || 'authority' === $analytics->type || 'endpoint' === $analytics->type ) { ?>
		    <?php $network = ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site ); ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-33-33-33-line">
					<?php echo $analytics->get_initiator_box(); ?>
					<?php echo $analytics->get_security_box(); ?>
					<?php echo $analytics->get_cache_box(); ?>
				</div>
			</div>
			<div class="vibes-row first-full-row">
				<?php echo $analytics->get_categories_list(); ?>
			</div>
			<div class="vibes-row <?php if ( ! $network ) { echo ' last-full-row'; } ?>">
				<?php echo $analytics->get_mimes_list(); ?>
			</div>
			<?php if ( $network ) { ?>
				<div class="vibes-row last-full-row">
					<?php echo $analytics->get_sites_list(); ?>
				</div>
			<?php } ?>
		<?php } ?>
		<?php if ( 'domains' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo $analytics->get_domains_list(); ?>
			</div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo $analytics->get_authorities_list(); ?>
			</div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<?php echo $analytics->get_endpoints_list(); ?>
			</div>
		<?php } ?>
	</div>
</div>
