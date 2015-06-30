<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 25/6/15
 * Time: 11:22 PM
 */

final class Pootle_Page_Builder_Render_Layout extends Pootle_Page_Builder_Abstract {
	/**
	 * @var Pootle_Page_Builder_Render_Layout
	 * @access protected
	 */
	protected static $instance;

	/**
	 * Magic __construct
	 * @since 0.9.0
	 */
	protected function __construct() {
		$this->hooks();
	}

	/**
	 * Adds the actions and filter hooks for plugin functioning
	 * @since 0.9.0
	 */
	private function hooks() {
		add_filter( 'the_content', array( $this, 'content_filter' ), 0 );
	}

	/**
	 * Filter the content of the panel, adding all the widgets.
	 * @param string $content Post content
	 * @return string Pootle page builder post content
	 * @filter the_content
	 */
	function content_filter( $content ) {

		$postID = get_the_ID();

		$isWooCommerceInstalled =
			function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' );

		if ( $isWooCommerceInstalled ) {
			// prevent Page Builder overwrite taxonomy description with widget content
			if ( ( is_tax( array( 'product_cat', 'product_tag' ) ) && get_query_var( 'paged' ) == 0 ) || ( is_post_type_archive() && ! is_shop() ) ) {
				return $content;
			}

			if ( is_shop() ) {
				$postID = wc_get_page_id( 'shop' );
			}
		} else {
			if ( is_post_type_archive() ) {
				return $content;
			}
		}

		//If product done once set $postID to Tabs Post ID
		if ( isset( $GLOBALS['canvasPB_ProductDoneOnce'] ) ) {
			global $wpdb;
			$results = $wpdb->get_results(
				"SELECT ID FROM "
				. $wpdb->posts
				. " WHERE "
				. "post_content LIKE '"
				. esc_sql( $content )
				. "'"
				. " AND post_type LIKE 'wc_product_tab'"
				. " AND post_status LIKE 'publish'" );
			foreach ( $results as $id ) {
				$postID = $id->ID;
			}
		}
		//If its product set canvasPB_ProductDoneOnce to skip this for TAB
		if ( function_exists( 'is_product' ) ) {
			if ( is_single() && is_product() ) {
				$GLOBALS['canvasPB_ProductDoneOnce'] = true;
			}
		}

		$post = get_post( $postID );

		if ( empty( $post ) ) {
			return $content;
		}
		if ( in_array( $post->post_type, pootle_pb_settings( 'post-types' ) ) ) {
			$panel_content = $this->panels_render( $post->ID );

			if ( ! empty( $panel_content ) ) {
				$content = $panel_content;
			}
		}

		return $content;
	}

	/**
	 * Render the panels
	 *
	 * @param int|string|bool $post_id The Post ID or 'home'.
	 * @param bool $enqueue_css Should we also enqueue the layout CSS.
	 * @param array|bool $panels_data Existing panels data. By default load from settings or post meta.
	 * @uses Pootle_Page_Builder_Front_Css_Js::panels_generate_css()
	 * @return string
	 */
	function panels_render( $post_id = false, $enqueue_css = true, $panels_data = false ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		global $siteorigin_panels_current_post;
		$old_current_post               = $siteorigin_panels_current_post;
		$siteorigin_panels_current_post = $post_id;

		if ( empty( $panels_data ) ) {
			if ( $post_id == 'home' ) {
				$panels_data = get_option( 'siteorigin_panels_home_page', get_theme_mod( 'panels_home_page', null ) );

				if ( is_null( $panels_data ) ) {
					// Load the default layout
					$layouts     = apply_filters( 'siteorigin_panels_prebuilt_layouts', array() );
					$panels_data = ! empty( $layouts['home'] ) ? $layouts['home'] : current( $layouts );
				}
			} else {
				//Allowing rendering for password protected Tab( wc_product_tab ) post types
				if ( post_password_required( $post_id ) && get_post_type( $post_id ) != 'wc_product_tab' ) {
					return false;
				}
				$panels_data = get_post_meta( $post_id, 'panels_data', true );
			}
		}

		$panels_data = apply_filters( 'siteorigin_panels_data', $panels_data, $post_id );
		if ( empty( $panels_data ) || empty( $panels_data['grids'] ) ) {
			return '';
		}

		//Removing filters for proper functionality
		remove_filter( 'the_content', 'wptexturize' );    //wptexturize : Replaces each & with &#038; unless it already looks like an entity
		remove_filter( 'the_content', 'convert_chars' );    //convert_chars : Converts lone & characters into &#38; ( a.k.a. &amp; )
		remove_filter( 'the_content', 'wpautop' );    //wpautop : Adds the Stupid Paragraphs for two line breaks

		// Create the skeleton of the grids
		$grids = array();
		if ( ! empty( $panels_data['grids'] ) ) {
			foreach ( $panels_data['grids'] as $gi => $grid ) {
				$gi           = intval( $gi );
				$grids[ $gi ] = array();
				for ( $i = 0; $i < $grid['cells']; $i ++ ) {
					$grids[ $gi ][ $i ] = array();
				}
			}
		}

		if ( ! empty( $panels_data['widgets'] ) && is_array( $panels_data['widgets'] ) ) {
			foreach ( $panels_data['widgets'] as $widget ) {

				if ( ! empty( $widget['info'] ) ) {
					$grids[ intval( $widget['info']['grid'] ) ][ intval( $widget['info']['cell'] ) ][] = $widget;
				}
			}
		}

		ob_start();

		global $ppb_panels_inline_css;
		if ( empty( $ppb_panels_inline_css ) ) {
			$ppb_panels_inline_css = '';
		}

		if ( $enqueue_css ) {
			$ppb_panels_inline_css .= Pootle_Page_Builder_Front_Css_Js::instance()->panels_generate_css( $post_id, $panels_data );
		}

		foreach ( $grids as $gi => $cells ) {

			// This allows other themes and plugins to add html before the row
			echo apply_filters( 'siteorigin_panels_before_row', '', $panels_data['grids'][ $gi ] );

			$grid_classes    = apply_filters( 'siteorigin_panels_row_classes', array( 'panel-grid' ), $panels_data['grids'][ $gi ] );
			$grid_attributes = apply_filters( 'siteorigin_panels_row_attributes', array(
				'class' => implode( ' ', $grid_classes ),
				'id'    => 'pg-' . $post_id . '-' . $gi
			), $panels_data['grids'][ $gi ] );

			echo '<div ';
			foreach ( $grid_attributes as $name => $value ) {
				echo $name . '="' . esc_attr( $value ) . '" ';
			}
			echo '>';

			$style_attributes = array();

			if ( ! empty( $panels_data['grids'][ $gi ]['style']['class'] ) ) {
				$style_attributes['class'] = array( 'panel-row-style-' . $panels_data['grids'][ $gi ]['style']['class'] );
			}

			// Themes can add their own attributes to the style wrapper
			$styleArray       = ! empty( $panels_data['grids'][ $gi ]['style'] ) ? $panels_data['grids'][ $gi ]['style'] : array();
			$style_attributes = apply_filters( 'siteorigin_panels_row_style_attributes', $style_attributes, $styleArray );

			$bgVideo = ! empty( $styleArray['background_toggle'] ) ? '.bg_video' == $styleArray['background_toggle'] : false;

			if ( ! empty( $style_attributes ) ) {
				if ( empty( $style_attributes['class'] ) ) {
					$style_attributes['class'] = array();
				}
				$style_attributes['class'][] = 'panel-row-style';
				if ( $bgVideo ) {
					$style_attributes['class'][] = 'video-bg';
				}
				if ( ! empty( $styleArray['full_width'] ) ) {
					$style_attributes['class'][] = 'ppb-full-width-row';
					$style_attributes['class'][] = 'ppb-full-width-no-bg';
				}
				$style_attributes['class']   = array_unique( $style_attributes['class'] );

				$style_attributes['style'] .= ! empty( $styleArray['style'] ) ? $styleArray['style'] : '';

				if ( ! empty( $styleArray['background_parallax'] ) ) {
					$style_attributes['class'][] = 'ppb-parallax';
					$style_attributes['style'] .= 'background-attachment: fixed;background-size: cover;';
				}

				if ( $bgVideo ) {
					if ( ! empty( $style['background_image'] ) ) {
						$style_attributes['style'] .= 'background-image: url( ' . esc_url( $style['bg_mobile_image'] ) . ' ); ';
					}
					$style_attributes['style'] .= ! empty( $styleArray['style'] ) ? $styleArray['style'] : '';
				}

				//Apply height if row doesn't contain widgets
				$contains_widgets = false;
				foreach ( $cells as $cell ) {

					if ( ! empty( $cell ) ) {
						$contains_widgets = true;
					}
				}

				if ( ! $contains_widgets ) {
					$style_attributes['style'] .= ! empty( $styleArray['row_height'] ) ? 'height:' . $styleArray['row_height'] . 'px' : '';
				}

				if ( ! empty( $styleArray['hide_row'] ) ) {
					$style_attributes['style'] .= 'display:none;';
				}

				echo '<div ';
				foreach ( $style_attributes as $name => $value ) {
					if ( is_array( $value ) ) {
						echo $name . '="' . esc_attr( implode( " ", array_unique( $value ) ) ) . '" ';
					} else {
						echo $name . '="' . esc_attr( $value ) . '" ';
					}
				}
				echo '>';

				if ( ! empty( $styleArray['bg_video'] ) && $bgVideo ) {

					$videoClasses = 'ppb-bg-video';

					if ( ! empty( $styleArray['bg_mobile_image'] ) ) {
						$videoClasses .= ' hide-on-mobile';
					}
					?>
					<video class="<?php echo $videoClasses; ?>" preload="auto" autoplay="true" loop="loop" muted="muted"
					       volume="0">
						<?php
						echo "<source src='{$styleArray['bg_video']}' type='video/mp4'>";
						echo "<source src='{$styleArray['bg_video']}' type='video/webm'>";
						?>
						Sorry, your browser does not support HTML5 video.
					</video>
				<?php
				}
			}
			$rowID = '#pg-' . $post_id . '-' . $gi;

			?>
			<style>

				<?php
					if ( ! empty( $styleArray['col_gutter'] ) ) {
						 echo esc_attr( $rowID ) . ' .panel-grid-cell { padding: 0 ' . ( $styleArray['col_gutter']/2 ) . 'px 0; }';
					}
							if ( isset( $styleArray['background'] ) && ! empty( $styleArray['bg_overlay_color'] ) ) {
								$overlay_color = $styleArray['bg_overlay_color'];
							if ( ! empty( $styleArray['bg_overlay_opacity'] ) ) {
								$overlay_color = 'rgba( ' . ppb_hex2rgb($overlay_color) . ", {$styleArray['bg_overlay_opacity']} )";
							}
						?>
				/* make this sit under .panel-row-style:before, so background color will be on top on background image */
				<?php echo esc_attr( $rowID ) ?> > .panel-row-style:before {
					background-color: <?php echo $overlay_color ?>;
				}

				<?php echo esc_attr( $rowID ) ?> > .panel-row-style {
					                                   position: relative;
					                                   z-index: 10;
				                                   }

				<?php echo esc_attr( $rowID ) ?>
				>
				.panel-grid-cell-container {
					position: relative;
					z-index: 30; /* row content needs to be on top of row background color */
				}
				<?php
				}
				?>
			</style>
			<?php

			echo "<div class='panel-grid-cell-container'>";

			foreach ( $cells as $ci => $widgets ) {
				// Themes can add their own styles to cells
				$cellId          = 'pgc-' . $post_id . '-' . $gi . '-' . $ci;
				$cell_classes    = apply_filters( 'siteorigin_panels_row_cell_classes', array( 'panel-grid-cell' ), $panels_data );
				$cell_attributes = apply_filters( 'siteorigin_panels_row_cell_attributes', array(
					'class' => implode( ' ', $cell_classes ),
					'id'    => $cellId
				), $panels_data );

				echo '<div ';
				foreach ( $cell_attributes as $name => $value ) {
					echo $name . '="' . esc_attr( $value ) . '" ';
				}
				echo '>';

				foreach ( $widgets as $pi => $widget_info ) {
					$data = $widget_info;

					unset( $data['info'] );

					/**
					 * Render the content block via this hook
					 *
					 * @param array $widget_info - Info for this block - backwards compatible with widgets
					 * @param int   $gi          - Grid Index
					 * @param int   $ci          - Cell Index
					 * @param int   $pi          - Panel/Content Block Index
					 * @param int   $blocks_num  - Total number of Blocks in cell
					 * @param int   $post_id     - The current post ID
					 */
					do_action( 'ppb_panels_render_content_block', $widget_info, $gi, $ci, $pi, count( $widgets ), $post_id );
				}
				if ( empty( $widgets ) ) {
					echo '&nbsp;';
				}
				echo '</div>';
			}
			echo "</div>";
			echo '</div>';

			if ( ! empty( $style_attributes ) ) {
				echo '</div>';
			}

			// This allows other themes and plugins to add html after the row
			echo apply_filters( 'siteorigin_panels_after_row', '', $panels_data['grids'][ $gi ] );
		}

		$html = ob_get_clean();

		// Reset the current post
		$siteorigin_panels_current_post = $old_current_post;

		return apply_filters( 'siteorigin_panels_render', $html, $post_id, null );
	}
}

//Instantiating Pootle_Page_Builder_Render_Layout class
Pootle_Page_Builder_Render_Layout::instance();