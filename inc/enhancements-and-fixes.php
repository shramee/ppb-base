<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 26/6/15
 * Time: 5:43 PM
 */

/**
 * Makes page builder content visible to WP SEO
 *
 * @param string $content Post content
 * @param object $post Post object
 * @return string Post content
 */
function ppb_wp_seo_filter( $content, $post ) {

	$id          = $post->ID;
	$panels_data = get_post_meta( $id, 'panels_data', true );
	if ( ! empty( $panels_data['widgets'] ) ) {
		foreach ( $panels_data['widgets'] as $widget ) {
			if ( ! empty( $widget['text'] ) ) {
				$content .= $widget['text'] . "\n\n";
			}
		}
	}

	return $content;
}
add_filter( 'wpseo_pre_analysis_post_content', 'ppb_wp_seo_filter', 10, 2 );

/**
 * No admin notices on our settings page
 *
 */
function ppb_no_admin_notices() {
	global $pagenow;

	if ( 'options-general.php' == $pagenow && 'page_builder' == filter_input( INPUT_GET, 'page' ) ) {
		remove_all_actions( 'admin_notices' );
	}
}
add_action( 'admin_notices', 'ppb_no_admin_notices', 0 );


/**
 * Add a filter to import panels_data meta key. This fixes serialized PHP.
 */
function siteorigin_panels_wp_import_post_meta( $post_meta ) {
	foreach ( $post_meta as $i => $meta ) {
		if ( $meta['key'] == 'panels_data' ) {
			$value = $meta['value'];
			$value = preg_replace( "/[\r\n]/", "<<<br>>>", $value );
			$value = preg_replace( '!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $value );
			$value = unserialize( $value );
			$value = array_map( 'siteorigin_panels_wp_import_post_meta_map', $value );

			$post_meta[ $i ]['value'] = $value;
		}
	}

	return $post_meta;
}
add_filter( 'wp_import_post_meta', 'siteorigin_panels_wp_import_post_meta' );
