<?php
use Elementor\Group_Control_Image_Size;
use Elementor\Icons_Manager;
?>
<div class="xpro-counter-wrapper">
		<div class="xpro-counter-wrapper-inner">
			<?php if($settings['show_media_icons']=='yes'){ ?>
				<?php if ( 'icon' === $settings['media_type'] || 'image' === $settings['media_type'] || 'lottie' === $settings['media_type'] ) : ?>
					<div class="xpro-counter-icon-item">
							<?php
							if ( 'icon' === $settings['media_type'] && $settings['icon'] ) {
								Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) );
							}
							if ( 'image' === $settings['media_type'] ) {
								$image_markup = ( ! empty( $settings['image']['id'] ) ) ? wp_get_attachment_image( $settings['image']['id'], $settings['thumbnail_size'] ) : '';
								echo ! empty( $image_markup ) ? $image_markup : '<img src="' . esc_url( $settings['image']['url'] ) . '">';
							}
							if ( 'lottie' === $settings['media_type'] ) {
								?> <div id="xpro-counter-box-lottie"  class="xpro-counter-lottie-animation"></div> 
								<?php } 
							?>
						</div>
				<?php endif; ?>
			<?php } ?>
			<div>
					<?php if ( $settings['badge_text'] ) : ?>
						<div <?php $this->print_render_attribute_string( 'badge_text' ); ?>><?php echo esc_html( $settings['badge_text'] ); ?></div>
					<?php endif; ?>

					<?php if ( ! empty( $settings['value'] ) ) : ?>
						<div class="xpro-counter-item">
							<span class="value"><?php echo esc_html( $settings['value'] ); ?></span>
							<?php if ( $settings['symbol'] ) { ?>
								<span class="symbol"><?php echo esc_html( $settings['symbol'] ); ?></span>
							<?php } ?>
						</div>
					<?php endif; ?>

					<div class="xpro-counter-content">
						<?php
						if ( $settings['title'] ) :

							$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
							$html_tag = isset($settings['title_tag']) ? $settings['title_tag'] : 'h3';
							$html_tag = strtolower($html_tag);
							$html_tag = in_array( $html_tag, $allowed_tags, true ) ? $html_tag : 'h3';
							
							printf('<%1$s %2$s>%3$s</%1$s>', esc_attr( $html_tag ), $this->get_render_attribute_string( 'title' ), wp_kses_post( $settings['title'] )); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							
						endif;
						if ( $settings['description'] ) :
							?>
							<p <?php $this->print_render_attribute_string( 'description' ); ?>><?php echo esc_html( $settings['description'] ); ?></p>
						<?php endif; ?>
				   </div>
		     </div>
	  </div>
</div>
