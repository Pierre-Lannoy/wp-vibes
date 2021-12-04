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
use Vibes\System\Device;

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
		<?php if ( ( 'summary' === $analytics->type || 'endpoint' === $analytics->type )  && '' === $analytics->extra ) { ?>
			<div class="vibes-row">
				<div class="vibes-box vibes-box-50-50-line">
					<?php echo wp_kses( $analytics->get_webvital_class( 'mobile', 'left' ), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
					<?php echo wp_kses( $analytics->get_webvital_class( 'desktop', 'right' ), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			</div>
			<div class="vibes-row first-full-row">
				<?php echo wp_kses( $analytics->get_webvital_chart(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
			</div>
		<?php } ?>
		<?php if ( 'summary' === $analytics->type  && '' === $analytics->extra ) { ?>
			<?php $network = ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site ); ?>
			<?php if ( $network ) { ?>
				<div class="vibes-row last-full-row">
					<?php echo wp_kses( $analytics->get_webvital_sites_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			<?php } else { ?>
				<div class="vibes-row last-full-row">
					<?php echo wp_kses( $analytics->get_webvital_endpoints_list(), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				</div>
			<?php } ?>
		<?php } ?>
		<?php if ( 'devices' === $analytics->extra ) { ?>
			<?php foreach ( Device::$types as $key => $device ) { ?>
				<?php if ( ! ( $key & 1 ) ) { ?>
                    <div class="vibes-row">
                        <div class="vibes-box vibes-box-50-50-line">
                        <?php echo wp_kses( $analytics->get_webvital_device( $device, 'left' ), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
				<?php } else { ?>
                        <?php echo wp_kses( $analytics->get_webvital_device( $device, 'right' ), PERFOO_ALLOWED_HTML_FOR_DASHBOARD, PERFOO_ALLOWED_PROTOCOLS_FOR_DASHBOARD ); ?>
                        </div>
                    </div>
				<?php } ?>
			<?php } ?>
		<?php } ?>

	</div>
</div>
