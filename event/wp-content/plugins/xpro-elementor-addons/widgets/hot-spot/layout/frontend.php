
<?php
$hover_animation = ( '2d-transition' === $settings['social_hot_spot_hover_animation'] ) ? 'xpro-button-2d-animation ' . $settings['social_hot_spot_hover_2d_css_animation'] : ( ( 'background-transition' === $settings['social_hot_spot_hover_animation'] ) ? 'xpro-button-bg-animation ' . $settings['social_hot_spot_hover_background_css_animation'] : ( ( 'hover-effect' === $settings['social_hot_spot_hover_animation'] ) ? 'xpro-unique-' . $settings['social_hot_spot_hover_effect_animation'] : 'xpro-elementor-button-animation-none' ) );
?>
<div class="xpro-hotspot-wrapper">
	<!-- Image -->
	<figure class="xpro-hotspot-image">
		<?php if ( $settings['image'] ) {
			echo wp_kses_post( \Elementor\Group_Control_Image_Size::get_attachment_image_html( $settings, 'media_thumbnail', 'image' ) );
		} ?>
	</figure>
	<?php
	foreach ( $settings['hotspot_items'] as $i => $item ) :

		$html_tag = ( $item['link']['url'] ) ? 'a' : 'span';
		$attr     = $item['link']['is_external'] ? ' target="_blank"' : '';
		$attr    .= $item['link']['nofollow'] ? ' rel="nofollow"' : '';
		$attr    .= $item['link']['url'] ? ' href="' . esc_url( $item['link']['url'] ) . '"' : '';

		if ( $item['link'] && $item['link']['custom_attributes'] ) {
			$attributes = explode( ',', $item['link']['custom_attributes'] );

			foreach ( $attributes as $attribute ) {
				if ( ! empty( $attribute ) ) {
					$custom_attr = explode( '|', $attribute, 2 );
					if ( ! isset( $custom_attr[1] ) ) {
						$custom_attr[1] = '';
					}
					$attr .= ' ' . $custom_attr[0] . '="' . $custom_attr[1] . '"';
				}
			}
		}
		?>
	<<?php echo esc_attr( $html_tag ); ?> <?php xpro_elementor_kses( $attr ); ?> class="elementor-repeater-item-<?php echo esc_attr( $item['_id'] ); ?> xpro-hotspot-item">

				<span class="xpro-hotspot-item-wrap xpro-hotspot-type-<?php echo esc_attr( $settings['type'] ); ?> ">

			<?php if ( 'yes' === $item['show_tooltip'] ) : ?>

				<span class="<?php echo $hover_animation ?>  xpro-hotspot-tooltip-text  <?php echo esc_attr( $item['show_default_tooltip'] === 'yes' ? 'xpro-active' : '' ); ?> xpro-hotspot-<?php echo esc_attr( $item['position'] ); ?>">
					<?php echo wp_kses_post( $item['tooltip_text'] ); ?>
				</span>
				<?php if ( 'auto' === $settings['type'] ) : ?>

					<span class="<?php echo esc_attr( $hover_animation ); ?> xpro-hotspot-tooltip-text xpro-active xpro-hotspot-<?php echo esc_attr( $item['position'] ); ?>">
						 <?php echo wp_kses_post( $item['tooltip_text'] ); ?>
					</span>
				<?php endif; ?>
				<?php if ( 'virtual-tour' === $settings['type'] ) : ?>
				<span class="<?php echo $hover_animation ?>  xpro-hotspot-tooltip-text xpro-active xpro-hotspot-animations xpro-hotspot-<?php echo esc_attr( $item['position'] ); ?>">
					<?php echo wp_kses_post( $item['tooltip_text'] ); ?>
				</span>
				<?php endif; ?>

			<?php endif; ?>

			<?php
			if ( 'icon' === $item['hot_media_type'] ) {
				\Elementor\Icons_Manager::render_icon( $item['hot_icon'], array( 'aria-hidden' => 'true' ) );
			}
			if ( 'image' === $item['hot_media_type'] ) {
				echo wp_kses_post( \Elementor\Group_Control_Image_Size::get_attachment_image_html( $item, 'spots_thumbnail', 'spots_image' ) );
			}
			?>
			</span>
	</<?php echo esc_attr( $html_tag ); ?>>
	<?php endforeach; ?>

</div>
