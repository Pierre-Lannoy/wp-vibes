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

?>

<form action="
	<?php
		echo esc_url(
			add_query_arg(
				[
					'page'   => 'vibes-settings',
					'action' => 'do-save',
					'tab'    => 'misc',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
	" method="POST">
	<?php do_settings_sections( 'vibes_navigation_options_section' ); ?>
	<?php do_settings_sections( 'vibes_resource_options_section' ); ?>
	<?php do_settings_sections( 'vibes_plugin_features_section' ); ?>
	<?php do_settings_sections( 'vibes_plugin_options_section' ); ?>
	<?php do_settings_sections( 'vibes_plugin_advanced_section' ); ?>
	<?php wp_nonce_field( 'vibes-plugin-options' ); ?>
	<p><?php echo get_submit_button( esc_html__( 'Reset to Defaults', 'vibes' ), 'secondary', 'reset-to-defaults', false ); ?>&nbsp;&nbsp;&nbsp;<?php echo get_submit_button( null, 'primary', 'submit', false ); ?></p>
</form>
