<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class pageAssetsOptimizer_shortcode extends pageAssetsOptimizer_hooks {
	
	function __construct(){
		parent::__construct();
		add_action( 'init', array($this, 'addShortcodes') );
	}
	
	public function addShortcodes(){
		//
	}
}