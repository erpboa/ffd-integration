<?php
/**
 * FFD Integration Admin
 *
 * @class    FFD_Admin
 * @author   FrozenFish
 * @category Admin
 * @package  FFD Integration/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * FFD_Admin class.
 */
class FFD_Admin {
    
    /**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'prevent_admin_access' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'ffd_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		add_action( 'wp_ajax_setup_wizard_check_jetpack', array( $this, 'setup_wizard_check_jetpack' ) );

		//add_action('wp_ajax_ffd/integration/deletepbtowpsyncdata', array($this, 'delete_pbtowpsync_data') );
		
	}
	
	function delete_pbtowpsync_data(){

		global $wpdb;

		$listing_posttype = ffd_get_listing_posttype();

		$result = false;
		$response = array();

		if( !empty($listing_posttype) ){

			$sql = "DELETE a,b FROM $wpdb->posts as a LEFT JOIN $wpdb->postmeta b ON a.ID=b.post_id WHERE a.post_type='$listing_posttype' AND b.meta_key='ffd_salesforce_id';";
			$result = $wpdb->query($sql);

			$response['sql'] = $sql;
		}

		
		if( $result !== false ){
			$response['message']  = 'Done. Deleted:' . $result ;
		} else {
			$response['message'] = 'Error';
		}
		echo json_encode($response);
		die();
	}


    /**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
    }
    

    /**
	 * Include any classes we need within admin.
	 */
	public function includes() {

		
        include_once dirname( __FILE__ ) . '/ffd-admin-functions.php';
		include_once dirname( __FILE__ ) . '/class-ffd-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-ffd-admin-assets.php';
		
        include_once dirname( __FILE__ ) . '/class-ffd-admin-fields-mapping.php';
        include_once dirname( __FILE__ ) . '/class-ffd-admin-fields-mapping-trestle.php'; //add to Trestle Sync
    }


	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes($screen) {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		$listing_posttype = ffd_listing_posttype_enabled();
		

		
		$ffd_pages = ffd_get_screen_ids();

		switch ( $screen->id ) {
			case 'options-permalink':
				if( true === $listing_posttype ){
					include 'class-ffd-admin-permalink-settings.php';
				}

			break;
		}

		if ( in_array( $screen->id, $ffd_pages ) ) {
			$this->disable_admin_notices();
		}
	}


	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {

	}
	

	/**
	 * Hide notices from plugins/themes
	 */
	public function hide_notices() {

		
		
	}

	public function disable_admin_notices() {


		global $wp_filter;
		if ( is_user_admin() ) {
			if ( isset( $wp_filter['user_admin_notices'] ) ) {
				unset( $wp_filter['user_admin_notices'] );
			}
		} 
		if ( isset( $wp_filter['admin_notices'] ) ) {
			unset( $wp_filter['admin_notices'] );
		}
		if ( isset( $wp_filter['all_admin_notices'] ) ) {
			unset( $wp_filter['all_admin_notices'] );
		}

		
	}

	/**
	 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from accessing admin.
	 */
	public function prevent_admin_access() {
		$prevent_access = false;

		if ( 'yes' === get_option( 'ffd_lock_down_admin', 'yes' ) && ! is_ajax() && basename( $_SERVER['SCRIPT_FILENAME'] ) !== 'admin-post.php' ) {
			$has_cap     = false;
			$access_caps = array( 'edit_posts', 'manage_ffd_integration', 'view_admin_dashboard' );

			foreach ( $access_caps as $access_cap ) {
				if ( current_user_can( $access_cap ) ) {
					$has_cap = true;
					break;
				}
			}

			if ( ! $has_cap ) {
				$prevent_access = true;
			}
		}

		
	}



	/**
	 * Change the admin footer text on FFD Integration admin pages.
	 *
	 * @since  1.0.0
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_ffd_integration' ) || ! function_exists( 'ffd_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$ffd_pages       = ffd_get_screen_ids();

		// Set only FFD  pages.
		$ffd_pages = array_diff( $ffd_pages, array( 'profile', 'user-edit' ) );

		// Check to make sure we're on a FFD Integration admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'ffd_display_admin_footer_text', in_array( $current_screen->id, $ffd_pages ) ) ) {
			// Change the footer text
			
			$footer_text = __( 'Thank you for using FFD Integration.', 'ffd-integration' );
			
		}

		return $footer_text;
	}

}


return new FFD_Admin();