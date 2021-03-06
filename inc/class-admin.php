<?php
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 26/6/15
 * Time: 6:39 PM
 */
final class Pootle_Page_Builder_Admin extends Pootle_Page_Builder_Abstract {
	/**
	 * @var Pootle_Page_Builder_Admin
	 */
	protected static $instance;

	/**
	 * Magic __construct
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	protected function includes() {

		/** Pootle Page Builder user interface */
		require_once POOTLEPAGE_DIR . 'inc/class-panels-ui.php';
		/** Content block - Editor panel and output */
		require_once POOTLEPAGE_DIR . 'inc/class-content-blocks.php';
		/** Take care of styling fields */
		require_once POOTLEPAGE_DIR . 'inc/styles.php';
		/** Handles PPB meta data *Revisions * */
		require_once POOTLEPAGE_DIR . 'inc/revisions.php';
		/** More styling */
		require_once POOTLEPAGE_DIR . 'inc/vantage-extra.php';
	}

	/**
	 * Adds the actions anf filter hooks for plugin functioning
	 * @access protected
	 * @since 0.9.0
	 */

	private function actions() {
		//Adding page builder help tab
		add_action( 'load-page.php', array( $this, 'add_help_tab' ), 12 );
		add_action( 'load-post-new.php', array( $this, 'add_help_tab' ), 12 );

		//Save panel data on post save
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );

		//Settings
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'options_init' ) );
		add_action( 'admin_init', array( $this, 'add_new' ) );

		add_action( '', array( $this, '' ) );
	}

	/**
	 * Add a help tab to pages with panels.
	 * @param $prefix
	 * @action load-post-new.php, load-page.php
	 */
	public function add_help_tab( $prefix ) {
		$screen = get_current_screen();
		if ( $screen->base == 'post' && in_array( $screen->id, pootle_pb_settings( 'post-types' ) ) ) {
			$screen->add_help_tab( array(
				'id'       => 'panels-help-tab', //unique id for the tab
				'title'    => __( 'Page Builder', 'ppb-panels' ), //unique visible title for the tab
				'callback' => array( $this, 'render_help_tab' )
			) );
		}
	}

	/**
	 * Display the content for the help tab.
	 * @TODO Make it more useful
	 */
	public function render_help_tab() {
		echo '<p>';
		_e( 'You can use Pootle Page Builder to create amazing pages, use addons to extend functionality.', 'siteorigin-panels' );
		_e( 'The page layouts are responsive and fully customizable.', 'siteorigin-panels' );
		echo '</p>';
	}

	/**
	 * Save the panels data
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @action save_post
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $_POST['_sopanels_nonce'] ) || ! wp_verify_nonce( $_POST['_sopanels_nonce'], 'save' ) ) {
			return;
		}
		if ( empty( $_POST['panels_js_complete'] ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Don't Save panels if Post Type for $post_id is not same as current post ID type
		// (Prevents population product panels data in saving Tabs via Meta)
		if ( get_post_type( $_POST['post_ID'] ) != 'wc_product_tab' and get_post_type( $post_id ) == 'wc_product_tab' ) {
			return;
		}

		$panels_data = siteorigin_panels_get_panels_data_from_post( $_POST );

		if ( function_exists( 'wp_slash' ) ) {
			$panels_data = wp_slash( $panels_data );
		}
		update_post_meta( $post_id, 'panels_data', $panels_data );
	}

	/**
	 * Add the options page
	 */
	public function admin_menu() {
		add_menu_page( 'Home', 'Page Builder', 'manage_options', 'page_builder', array( $this, 'menu_page' ), 'dashicons-screenoptions', 26 );
		add_submenu_page( 'page_builder', 'Add New', 'Add New', 'manage_options', 'page_builder_add', array( $this, 'submenu_page' ) );
		add_submenu_page( 'page_builder', 'Settings', 'Settings', 'manage_options', 'page_builder_settings', array( $this, 'submenu_page' ) );
		add_submenu_page( 'page_builder', 'Add-ons', 'Add-ons', 'manage_options', 'page_builder_addons', array( $this, 'submenu_page' ) );
	}

	/**
	 * Register all the settings fields.
	 */
	public function options_init() {
		register_setting( 'pootlepage-add-ons', 'pootlepage_add_ons', '__return_false' );
		register_setting( 'pootlepage-display', 'siteorigin_panels_display', array( $this, 'options_sanitize_display' ) );

		add_settings_section( 'display', __( 'Display', 'ppb-panels' ), '__return_false', 'pootlepage-display' );

		// The display fields
		add_settings_field( 'responsive', __( 'Responsive', 'ppb-panels' ), array( $this, 'options_field_generic' ), 'pootlepage-display', 'display', array( 'type' => 'responsive' ) );
		add_settings_field( 'mobile-width', __( 'Mobile Width', 'ppb-panels' ), array( $this, 'options_field_generic' ), 'pootlepage-display', 'display', array( 'type' => 'mobile-width' ) );
	}

	/**
	 * Display the admin page.
	 */
	public function submenu_page() {
		if ( 'page_builder_settings' == filter_input( INPUT_GET, 'page' ) ) {

			include plugin_dir_path( POOTLEPAGE_BASE_FILE ) . '/tpl/options.php';

		} elseif ( 'page_builder_addons' == filter_input( INPUT_GET, 'page' ) ) {

			include plugin_dir_path( POOTLEPAGE_BASE_FILE ) . '/tpl/add-ons.php';

		} elseif ( 'page_builder_add' == filter_input( INPUT_GET, 'page' ) ) {

			?>
			<div class="wrap">
				<h2 class="page_builder_add">If you are not automatically redirected. <a href="<?php echo admin_url( '/post-new.php?post_type=page&page_builder=pootle' ); ?>"> Click Here to Create New page with Pootle Page Builder.</a><h2>
			</div>
		<?php

		}
	}

	/**
	 * Display the admin page.
	 */
	public function menu_page() {
		include POOTLEPAGE_DIR . '/tpl/welcome.php';
	}

	/**
	 * Redirecting for Page Builder > Add New option
	 */
	public function add_new() {
		global $pagenow;

		if ( 'admin.php' == $pagenow && 'page_builder_add' == filter_input( INPUT_GET, 'page' ) ) {
			header( 'Location: ' . admin_url( '/post-new.php?post_type=page&page_builder=pootle' ) );
			die();
		}
	}

	/**
	 * Output settings field
	 * @param array $args
	 * @param string $groupName
	 */
	public function options_field_generic( $args, $groupName = 'siteorigin_panels_display' ) {
		$settings = pootle_pb_settings();
		switch ( $args['type'] ) {
			case 'responsive' :
				?><label><input type="checkbox"
				                name="<?php echo $groupName ?>[<?php echo esc_attr( $args['type'] ) ?>]" <?php checked( $settings[ $args['type'] ] ) ?>
				                value="1"/> <?php _e( 'Enabled', 'ppb-panels' ) ?></label><?php
				break;
			case 'mobile-width' :
				?><input type="text" name="<?php echo $groupName ?>[<?php echo esc_attr( $args['type'] ) ?>]"
				         value="<?php echo esc_attr( $settings[ $args['type'] ] ) ?>"
				         class="small-text" /> <?php _e( 'px', 'ppb-panels' ) ?><?php
				break;
		}

		if ( ! empty( $args['description'] ) ) {
			?><p class="description"><?php echo esc_html( $args['description'] ) ?></p><?php
		}
	}

	/**
	 * Sanitize display options
	 * @param $vals
	 * @return mixed
	 */
	public function options_sanitize_display( $vals ) {
		foreach ( $vals as $f => $v ) {
			switch ( $f ) {
				case 'responsive' :
				case 'bundled-widgets' :
					$vals[ $f ] = ! empty( $vals[ $f ] );
					break;
				case 'mobile-width' :
					$vals[ $f ] = intval( $vals[ $f ] );
					break;
			}
		}
		$vals['copy-content']    = false;
		$vals['animations']      = true;
		$vals['inline-css']      = true;
		$vals['responsive']      = ! empty( $vals['responsive'] );
		$vals['bundled-widgets'] = ! empty( $vals['bundled-widgets'] );

		return $vals;
	}
}