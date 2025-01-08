<?php

namespace Elementor;

defined('ABSPATH') || exit;

class Ekit_Wb_14 extends Widget_Base {

	public function get_name() {
		return 'ekit_wb_14';
	}


	public function get_title() {
		return esc_html__( 'New Widget', 'elementskit-lite' );
	}


	public function get_categories() {
		return ['basic'];
	}


	public function get_icon() {
		return 'eicon-cog';
	}


	protected function register_controls() {
	}


	protected function render() {
	}


}
