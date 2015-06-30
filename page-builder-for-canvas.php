<?php
/*
Plugin Name: Pootle Page Builder
Plugin URI: http://pootlepress.com/
Description: A page builder for WooThemes Canvas.
Version: 2.9.9
Author: PootlePress
Author URI: http://pootlepress.com/
License: GPL version 3
*/

/** Include PPB abstract class */
require_once 'inc/class-abstract.php';

/**
 * Pootle Page Builder admin class
 * Class Pootle_Page_Builder_Public
 * Use Pootle_Page_Builder::instance() to get an instance
 */
final class Pootle_Page_Builder extends Pootle_Page_Builder_Abstract {

	/**
	 * @var Pootle_Page_Builder instance of Pootle_Page_Builder
	 * @access protected
	 */
	protected static $instance;

	/**
	 * @var Pootle_Page_Builder_Admin Admin class instance
	 * @access protected
	 */
	protected $admin;

	/**
	 * @var Pootle_Page_Builder_Public Public class instance
	 * @access protected
	 */
	protected $public;

	/**
	 * Magic __construct
	 * @since 0.9.0
	 */
	protected function __construct() {
		$this->constants();
		$this->includes();
		$this->hooks();
	}

	/**
	 * Set the constants
	 * @since 0.9.0
	 */
	private function constants() {
		define( 'POOTLEPAGE_VERSION', '2.9.9' );
		define( 'POOTLEPAGE_BASE_FILE', __FILE__ );
		define( 'POOTLEPAGE_DIR', __DIR__ . '/' );
		define( 'POOTLEPAGE_URL', plugin_dir_url( __FILE__ ) );
		// Tracking presence of version older than 3.0.0
		if ( - 1 == version_compare( get_option( 'siteorigin_panels_initial_version' ), '2.5' ) ) {
			define( 'POOTLEPAGE_OLD_V', get_option( 'siteorigin_panels_initial_version' ) );
		}
	}

	/**
	 * Include the required files
	 * @since 0.9.0
	 */
	private function includes() {

		/** Variables used throughout the plugin */
		require_once POOTLEPAGE_DIR . 'inc/vars.php';
		/** Functions used throughout the plugin */
		require_once POOTLEPAGE_DIR . 'page-builder-for-canvas-functions.php';
		/** Enhancements and fixes */
		require_once POOTLEPAGE_DIR . '/inc/enhancements-and-fixes.php';
		/** Pootle Press Updater */
		require_once POOTLEPAGE_DIR . 'inc/class-pootlepress-updater.php';
		/** PPB Admin Class */
		require_once POOTLEPAGE_DIR . 'inc/class-admin.php';
		/** Instantiating PPB Admin Class */
		$this->admin = Pootle_Page_Builder_Admin::instance();
		/** PPB Public Class */
		require_once POOTLEPAGE_DIR . 'inc/class-public.php';
		/** Instantiating PPB Public Class */
		$this->public = Pootle_Page_Builder_Public::instance();

		//@TODO Get rid of these
		require_once POOTLEPAGE_DIR . 'inc/cxpb-support.php';
	}

	/**
	 * Adds the actions and filter hooks for plugin functioning
	 * @since 0.9.0
	 */
	private function hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'in_plugin_update_message-' . plugin_basename( __FILE__ ), 'plugin_update_message', 10, 2 );
		add_action( 'init', array( $this, 'pp_updater' ) );
	}

	/**
	 * Hook for activation of Page Builder.
	 * @since 0.9.0
	 */
	public function activate() {
		add_option( 'ppb_initial_version', POOTLEPAGE_VERSION, '', 'no' );

		$current_user = wp_get_current_user();

		//Get first name if set
		$username = '';
		if ( ! empty( $current_user->user_firstname ) ) {
			$username = " {$current_user->user_firstname}";
		}

		$welcome_message = "<b>Hey{$username}! Welcome to Page builder.</b> You're all set to start building stunning pages!<br><a class='button pootle' href='" . admin_url( '/admin.php?page=page_builder' ) . "'>Get started</a>";

		ppb_add_admin_notice( 'welcome', $welcome_message, 'updated pootle' );
	}

	/**
	 * Initialize the language files
	 * @action plugins_loaded
	 * @since 0.9.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ppb-panels', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Enqueue admin scripts and styles
	 * @global $pagenow
	 * @action admin_notices
	 * @since 0.9.0
	 */
	public function enqueue(){
		global $pagenow;

		wp_enqueue_style( 'pootlepage-main-admin', plugin_dir_url( __FILE__ ) . 'css/main-admin.css', array(), POOTLEPAGE_VERSION );

		if ( $pagenow == 'admin.php' && false !== strpos( filter_input( INPUT_GET, 'page' ), 'page_builder' ) ) {
			wp_enqueue_script( 'ppb-settings-script', plugin_dir_url( __FILE__ ) . 'js/settings.js', array() );
			wp_enqueue_style( 'ppb-settings-styles', plugin_dir_url( __FILE__ ) . 'css/settings.css', array() );
			wp_enqueue_style( 'ppb-option-admin', plugin_dir_url( __FILE__ ) . 'css/option-admin.css', array(), POOTLEPAGE_VERSION );
			wp_enqueue_script( 'ppb-option-admin', plugin_dir_url( __FILE__ ) . 'js/option-admin.js', array( 'jquery' ), POOTLEPAGE_VERSION );
		}
	}

	/**
	 * Outputs admin notices
	 * @since 1.0.0
	 * @action admin_notices
	 * @since 0.9.0
	 */
	public function admin_notices() {

		$notices = get_option( 'pootle_page_admin_notices', array() );

		delete_option( 'pootle_page_admin_notices' );

		if ( 0 < count( $notices ) ) {
			$html = '';
			foreach ( $notices as $k => $v ) {
				$html .= '<div id="' . esc_attr( $k ) . '" class="fade ' . esc_attr( $v['type'] ) . '">' . wpautop( $v['message'] ) . '</div>' . "\n";
			}
			echo $html;
		}
	}

	/**
	 * Add plugin action links.
	 * @param $links
	 * @action plugin_action_links_$file
	 * @return array
	 * @since 0.9.0
	 * @TODO Use this
	 */
	public function plugin_action_links( $links ) {
		//$links[] = '<a href="http://pootlepress.com/pootle-page-builder/">' . __( 'Support Forum', 'ppb-panels' ) . '</a>';
		//$links[] = '<a href="http://pootlepress.com/page-builder/#newsletter">' . __( 'Newsletter', 'ppb-panels' ) . '</a>';

		return $links;
	}

	/**
	 * Filter update message
	 * @param array $args Plugin metadata.
	 * @param array $r Metadata about the available plugin update
	 * @action in_plugin_update_message-$file
	 * @since 0.9.0
	 */
	public function plugin_update_message( $args, $r ) {
		if ( $args['update'] ) {
			$transient_name = 'pp_pb_upgrade_notice_' . $args['Version'];

			if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {

				$response = wp_remote_post( $args['url'], array(
					'body' => array(
						'action' => 'upgrade-notice',
						'plugin' => $args['slug']
					)
				) );

				if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) && $response['body'] != 'false' ) {

					// Output Upgrade Notice
					$upgrade_notice = '';

					// css from WooCommerce
					$upgrade_notice .= '<style>.wc_plugin_upgrade_notice{font-weight:400;color:#fff;background:#d54d21;padding:1em;margin:9px 0}.wc_plugin_upgrade_notice a{color:#fff;text-decoration:underline}.wc_plugin_upgrade_notice:before{content:"\f348";display:inline-block;font:400 18px/1 dashicons;speak:none;margin:0 8px 0 -2px;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;vertical-align:top}</style>';

					$upgrade_notice .= '<div class="wc_plugin_upgrade_notice">';

					$upgrade_notice .= $response['body'];

					$upgrade_notice .= '</div> ';

					set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
				}
			}

			echo $upgrade_notice;
		}
	}

	/**
	 * Initiates pootlepress updater
	 * @action init
	 */
	public function pp_updater() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			include( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$data                          = get_plugin_data( __FILE__ );
		$wptuts_plugin_current_version = $data['Version'];
		$wptuts_plugin_remote_path     = 'http://www.pootlepress.com/?updater=1';
		$wptuts_plugin_slug            = plugin_basename( __FILE__ );
		new Pootlepress_Updater ( $wptuts_plugin_current_version, $wptuts_plugin_remote_path, $wptuts_plugin_slug );
	}

} //class Pootle_Page_Builder

//Instantiating Pootle_Page_Builder
Pootle_Page_Builder::instance();