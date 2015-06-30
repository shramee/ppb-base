<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 26/6/15
 * Time: 6:46 PM
 */

/**
 * Pootle Page Builder admin class
 * Class Pootle_Page_Builder_Public
 * Use Pootle_Page_Builder_Public::instance() to get an instance
 */
final class Pootle_Page_Builder_Public extends Pootle_Page_Builder_Abstract {
	/**
	 * @var Pootle_Page_Builder_Public
	 */
	protected static $instance;

	/**
	 * Magic __construct
	 * $since 1.0.0
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	protected function includes() {
		require_once POOTLEPAGE_DIR . 'inc/class-render-layout.php';
		require_once POOTLEPAGE_DIR . 'inc/class-front-css-js.php';
	}

	/**
	 * Adds the actions anf filter hooks for plugin
	 * @since 0.9.0
	 */
	protected function actions(){

		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Add all the necessary body classes.
	 * @param $classes
	 * @return array
	 */
	function body_class( $classes ) {

		if ( ppb_is_panel() ) {
			$classes[] = 'ppb-panels';
		}

		return $classes;
	}
}