<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 26/6/15
 * Time: 10:38 PM
 */
/**
 * Handles creating the preview.
 */
function siteorigin_panels_preview() {
	if ( isset( $_GET['siteorigin_panels_preview'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'ppb-panels-preview' ) ) {
		global $siteorigin_panels_is_preview;
		$siteorigin_panels_is_preview = true;
		// Set the panels home state to true
		$post_id = filter_input( INPUT_POST, 'post_id' );
		if ( empty( $post_id ) ) {
			$GLOBALS['siteorigin_panels_is_panels_home'] = true;
		}
		add_action( 'option_siteorigin_panels_home_page', 'siteorigin_panels_preview_load_data' );
		locate_template( pootle_pb_settings( 'home-template' ), true );
		exit();
	}
}

add_action( 'template_redirect', 'siteorigin_panels_preview' );

/**
 * Is this a preview.
 *
 * @return bool
 */
function siteorigin_panels_is_preview() {
	global $siteorigin_panels_is_preview;
	return (bool) $siteorigin_panels_is_preview;
}

/**
 * This is a way to show previews of panels, especially for the home page.
 *
 * @param $val
 * @return array
 */
function siteorigin_panels_preview_load_data( $val ) {
	if ( isset( $_GET['siteorigin_panels_preview'] ) ) {

		$val = siteorigin_panels_get_panels_data_from_post( $_POST );
	}

	return $val;
}
