<?php
/*
Plugin Name: Quarters Login
Plugin URI: https://www.pocketfulofquarters.com
Description: Quarters Login allows you to login into woocomemrce by using OAuth2
Version: 1.0.0
Author: pocketfulofquarters
Author URI: https://www.pocketfulofquarters.com
Text Domain: quarters_login
Domain Path: /languages
*/

if( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'Quarters_Login' ) ){
	/**
	 * Main Quartets Login Class.
	 *
	 * @since 1.0.0
	 */
	final class Quarters_Login {

		/**
		 * The single instance of the class.
		 *
		 * @var Quarters_Login
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main Quarters Login Instance.
		 *
		 * Ensures only one instance of Quarters Login is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return Quarters_Login - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.0
		 */
		public function __construct(){
			if( !$this->ready_to_active() ){
				$this->admin_notices_hooks();
				return;
			}

			$this->define_constants();
			$this->includes();
			register_activation_hook( QUARTERS_LOGIN_FILE, array( $this, 'setup_ql' ) );
		}

		/**
		 * Define WC Constants.
		 *
		 * @since 1.0.0
		 */
		private function define_constants(){
			define( 'QUARTERS_LOGIN_VERSION', '1.0.0' );
			define( 'QUARTERS_LOGIN_FILE', __FILE__ );
			define( 'QUARTERS_LOGIN_DIR', plugin_dir_path( QUARTERS_LOGIN_FILE ) );
			define( 'QUARTERS_LOGIN_URL', plugins_url( '/', QUARTERS_LOGIN_FILE ) );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 1.0.0
		 */
		public function includes(){
			
			/**
			 * Admin Classes.
			 */
			if( is_admin() ){
				include_once QUARTERS_LOGIN_DIR . 'includes/admin/quarters-login-settings.php';
			}

			/**
			 * Frontend Classes.
			 */
			include_once QUARTERS_LOGIN_DIR . 'includes/frontend/quarters-login-general.php';
			include_once QUARTERS_LOGIN_DIR . 'includes/woocommerce_settings.php';
		}

		public function setup_ql(){
			include_once dirname( __FILE__ ) . '/admin/wc-admin-functions.php';

			$pages = apply_filters(
				'ql_create_pages',
				array(
					'ql_login'  => array(
						'name'    => _x( 'ql-login', 'Page slug', 'quarters_login' ),
						'title'   => _x( 'Quarters Login', 'Page title', 'quarters_login' ),
						'content' => '<!-- ql:shortcode -->[ql_setup_account]<!-- /ql:shortcode -->',
					),
				)
			);

			foreach ( $pages as $key => $page ) {
				wc_create_page( esc_sql( $page['name'] ), 'ql_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );
			}
		}

		/**
		 * Check for necessary requirements for plugin to active.
		 *
		 * @return boolean
		 * @since 1.0.0
		 */
		private function ready_to_active(){
			if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
				return false;
			}

			return true;
		}

		/**
		 * Hook for Admin Notice Hooks
		 *
		 * @since 1.0.0
		 */
		private function admin_notices_hooks(){
			add_action('admin_notices', array( $this, 'quartets_login_admin_notices' ) );
		}

		/**
		 * Output a admin notice when necessary requirements not met
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function quartets_login_admin_notices(){
			$message = null;
			$plugin  = get_plugin_data(__FILE__);

			if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$message = __('<strong><a href="http://www.woothemes.com/woocommerce/" target="_blank">Woocommerce</a></strong> requires In order to use <strong>%s</strong>.','quarters_login');
			}

			if ( $message ) {
				echo '<div class="error">';
				echo '<p>' . sprintf( $message, $plugin['Name'] ) . '</p>';
				echo '</div>';
			}
		}
	}
}

new Quarters_Login();