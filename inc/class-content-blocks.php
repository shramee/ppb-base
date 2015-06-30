<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 25/6/15
 * Time: 11:29 PM
 */

/**
 * Class Pootle_Page_Builder_Content_Block
 */
final class Pootle_Page_Builder_Content_Block extends Pootle_Page_Builder_Abstract {
	/**
	 * @var Pootle_Page_Builder_Content_Block
	 */
	protected static $instance;

	/**
	 * Magic __construct
	 * $since 1.0.0
	 */
	protected function __construct() {
		add_filter( 'ppb_content_block', array( $this, 'auto_embed' ), 8 );
		add_action( 'ppb_panels_render_content_block', array( $this, 'open_block' ), 5, 6 );
		add_action( 'ppb_panels_render_content_block', array( $this, 'render_content_block' ) );
		add_action( 'ppb_panels_render_content_block', array( $this, 'close_block' ), 99 );
		add_action( 'wp_head', array( $this, 'print_inline_css' ), 12 );
		add_action( 'wp_footer', array( $this, 'print_inline_css' ) );
		add_action( 'ppb_content_block_editor_form', array( $this, 'panels_editor' ) );
		add_action( 'wp_ajax_ppb_panels_editor_form', array( $this, 'ajax_content_panel' ) );
		add_action( 'ppb_add_content_woocommerce_tab', array( $this, 'wc_tab' ) );
	}

	/**
	 * Enables oEmbed in content blocks
	 *
	 * @param string $text
	 *
	 * @return string
	 * @filter ppb_content_block
	 * @since 1.0.0
	 */
	public function auto_embed( $text ) {

		$text = str_replace(
			array( '<p>', '</p>', ), array(
			"<p>\n",
			"\n</p>",
		), $text
		);

		return $GLOBALS['wp_embed']->autoembed( $text );
	}

	/**
	 * Opens the content block container with styles and classes
	 *
	 * @param $block_info
	 * @param $gi
	 * @param $ci
	 * @param $pi
	 * @param $blocks_num
	 * @param $post_id
	 *
	 * @action ppb_panels_render_content_block
	 * @since 1.0.0
	 */
	public function open_block( $block_info, $gi, $ci, $pi, $blocks_num, $post_id ) {

		$styleArray = $widgetStyle = isset( $block_info['info']['style'] ) ? json_decode( $block_info['info']['style'], true ) : ppb_default_content_block_style();;

		//Classes for this content block
		$classes = array( 'panel' );
		if ( 0 == $pi ) {
			$classes[] = 'panel-first-child';
		}
		if ( ( $blocks_num - 1 ) == $pi ) {
			$classes[] = 'panel-last-child';
		}

		//Id for this content block
		$id = 'panel-' . $post_id . '-' . $gi . '-' . $ci . '-' . $pi;

		$inlineStyle = '';

		$widgetStyleFields = ppb_block_styling_fields();

		$styleWithSelector = '';

		foreach ( $widgetStyleFields as $key => $field ) {
			if ( $field['type'] == 'border' ) {
				// a border field has 2 settings
				$key1 = $key . '-width';
				$key2 = $key . '-color';

				if ( isset( $styleArray[ $key1 ] ) && $styleArray[ $key1 ] != '' ) {
					if ( ! is_array( $field['css'] ) ) {
						$cssArr = array( $field['css'] );
					} else {
						$cssArr = $field['css'];
					}

					foreach ( $cssArr as $cssProperty ) {
						$inlineStyle .= $cssProperty . '-width: ' . $styleArray[ $key1 ] . 'px; border-style: solid;';
					}
				}

				if ( isset( $styleArray[ $key2 ] ) && $styleArray[ $key2 ] != '' ) {
					if ( ! is_array( $field['css'] ) ) {
						$cssArr = array( $field['css'] );
					} else {
						$cssArr = $field['css'];
					}

					foreach ( $cssArr as $cssProperty ) {
						$inlineStyle .= $cssProperty . '-color: ' . $styleArray[ $key2 ] . ';';
					}
				}

			} elseif ( $key == 'inline-css' ) {

				if ( ! empty( $styleArray[ $key ] ) ) {
					$inlineStyle .= $styleArray[ $key ];
				}

			} else {


				if ( isset( $styleArray[ $key ] ) && $styleArray[ $key ] != '' ) {
					if ( ! is_array( $field['css'] ) ) {
						$cssArr = array( $field['css'] );
					} else {
						$cssArr = $field['css'];
					}

					foreach ( $cssArr as $cssProperty ) {
						if ( isset( $field['unit'] ) ) {
							$unit = $field['unit'];
						} else {
							$unit = '';
						}

						if ( ! isset( $field['selector'] ) ) {
							$inlineStyle .= $cssProperty . ': ' . $styleArray[ $key ] . $unit . ';';
						} else {
							$styleWithSelector .= '#' . $id . ' > ' . $field['selector'] . ' { ' . $cssProperty . ': ' . $styleArray[ $key ] . $unit . '; }';
						}
					}
				}
			}
		}

		if ( $styleWithSelector != '' ) {
			echo "<style>\n";
			echo str_replace( 'display', 'display:none;display', $styleWithSelector );
			echo "</style>\n";
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" id="' . $id . '" style="' . $inlineStyle . '" >';
	}

	/**
	 * Render the Content Panel.
	 * @param string $widget_info The widget class name.
	 * @since 1.0.0
	 */
	public function render_content_block( $block_info ) {
		if ( ! empty( $block_info['text'] ) ) {
			echo apply_filters( 'ppb_content_block', $block_info['text'] );
		}
	}

	/**
	 * Closes the content block container
	 * @since 1.0.0
	 */
	public function close_block() {
		echo '</div>';
	}

	/**
	 * Print inline CSS
	 * @since 1.0.0
	 */
	public function print_inline_css() {
		global $ppb_panels_inline_css;

		if ( ! empty( $ppb_panels_inline_css ) ) {
			?>
			<!----------Pootle Page Builder Inline Styles---------->
			<style type="text/css" media="all"><?php echo $ppb_panels_inline_css ?></style><?php
		}

		$ppb_panels_inline_css = '';
	}

	/**
	 * Output TMCE Editor
	 * @param $request
	 * @since 1.0.0
	 */
	public function panels_editor( $request ) {
		//Init text to populate in editor
		$text = '';
		if ( ! empty( $request['instance'] ) ) {
			$instance = json_decode( $request['instance'] );
			if ( ! empty( $instance->text ) ) {
				$text = $instance->text;
			}
		}

		wp_editor(
			$text,
			'ppbeditor',
			array(
				'textarea_name'  => 'widgets[{$id}][text]',
				'default_editor' => 'tmce',
				'tinymce'        => array(
					'force_p_newlines' => false,
				)
			)
		);
	}

	/**
	 * Display a widget form with the provided data
	 * @param array|null $request Request data ($_POST/$_GET)
	 * @since 1.0.0
	 */
	public function editor_panel( $request = null ) {
		require POOTLEPAGE_DIR . 'tpl/content-block-panel.php';
	}

	/**
	 * Handles ajax requests for the content panel
	 * @since 1.0.0
	 * @uses Pootle_Page_Builder_Content_Block::editor_panel()
	 */
	public function ajax_content_panel() {
		$request = array_map( 'stripslashes_deep', $_REQUEST );
		$this->editor_panel( $request );
		exit();
	}

	/**
	 * Output woo commerce tab
	 * @since 1.0.0
	 */
	public function wc_tab() {
		?>
		Using WooCommerce? You can now build a stunning shop with Page Builder. Just get our WooCommerce extension and start building!
	<?php
	}
}

//Instantiating Pootle_Page_Builder_Content_Block class
Pootle_Page_Builder_Content_Block::instance();