<?php

use Elementor\Icons_Manager;

$layout = ( 'none' !== $settings['separator_layout_style'] ) ? ' xpro-step-' . $settings['separator_layout_style'] : '';
?>

<!-- Step Flow -->
<div class="xpro-step-flow-wrapper <?php echo esc_attr( $layout ); ?> xpro-step-flow-separator-disable-<?php echo esc_attr( $settings['separator_hide_on'] ); ?>">
	<div class="xpro-step-flow-icon">

	   <?php
			if ( $settings['step_flow_icon'] && 'icon' === $settings['media_type'] ) {
				Icons_Manager::render_icon( $settings['step_flow_icon'], array( 'aria-hidden' => 'true' ) );
			}

			if ( 'image' === $settings['media_type'] ) {
				$image_markup = ( ! empty( $settings['image']['id'] ) ) ? wp_get_attachment_image( $settings['image']['id'] ) : '';
				echo ! empty( $image_markup ) ? $image_markup : '<img src="' . esc_url( $settings['image']['url'] ) . '">';
			}
		?>

		<!-- Separator -->
		<?php if ( 'yes' === $settings['step_flow_separator'] ) { ?>
			<span class="xpro-step-flow-<?php echo esc_attr( $settings['separator_layout_style'] ); ?>"></span>
		<?php } ?>

		<!-- Badge -->
		<?php if ( $settings['step_flow_badge_text'] ) { ?>
			<span class="xpro-step-flow-badge xpro-badge xpro-badge-<?php echo esc_attr( $settings['badge_position'] ); ?>">
				<?php echo esc_html( $settings['step_flow_badge_text'] ); ?>
			</span>
		<?php } ?>

	</div>
	<div class="xpro-step-flow-content">
		<!-- Title -->
		<?php if ( $settings['step_flow_title'] ) { ?>
			<h2 class="xpro-step-flow-title"><?php echo esc_html( $settings['step_flow_title'] ); ?></h2>
		<?php } ?>

		<!-- Description -->
		<?php if ( $settings['step_flow_description'] ) { ?>
			<div class="xpro-step-flow-description"><?php xpro_elementor_kses( $settings['step_flow_description'] ); ?></div>
		<?php } ?>

	</div>
</div>
