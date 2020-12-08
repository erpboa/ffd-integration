<?php
/**
 * Load assets
 *
 * @package     FFD-Integration/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FFD_Admin_Assets', false ) ) :

	/**
	 * FFD_Admin_Assets Class.
	 */
	class FFD_Admin_Assets {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}

		/**
		 * Enqueue styles.
		 */
		public function admin_styles() {
			global $wp_scripts;

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			// Register admin styles.
			wp_register_style( 'ffd_admin_styles', FFD()->plugin_url() . '/assets/css/admin.css', array(), FFD_VERSION );
			


			// Admin styles for FFD pages only.
			if ( in_array( $screen_id, ffd_get_screen_ids() ) ) {
				wp_enqueue_style( 'ffd_admin_styles' );
			}

			
		}


		/**
		 * Enqueue scripts.
		 */
		public function admin_scripts() {
			global $wp_query, $post;

			$screen       = get_current_screen();
			$screen_id    = $screen ? $screen->id : '';
			$ffd_screen_id = sanitize_title( __( 'FFD Integration', 'ffd-integration' ) );
			$suffix       = ''; //defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register scripts.
			wp_register_script( 'ffd_admin', FFD()->plugin_url() . '/assets/js/admin/ffd_admin' . $suffix . '.js', array( 'jquery'), FFD_VERSION );
			wp_register_script( 'jquery-blockui', FFD()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
			wp_register_script( 'jquery-tiptip', FFD()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), FFD_VERSION, true );
			

			// FFD admin pages.
			if ( in_array( $screen_id, ffd_get_screen_ids() ) ) {
				wp_enqueue_script( 'ffd_admin' );


				$params = array(
					
					'ajax_url'                          => admin_url( 'admin-ajax.php' ),
				);
				wp_localize_script( 'ffd_admin', 'ffd_admin', $params );
			}
		}
	}

endif;

return new FFD_Admin_Assets();
