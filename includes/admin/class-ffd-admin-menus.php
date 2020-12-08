<?php

/**
 * Setup menus in WP admin.
 *
 * @package FFD Integration\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FFD_Admin_Menus', false ) ) {
	return new FFD_Admin_Menus();
}

/**
 * FFD_Admin_Menus Class.
 */
class FFD_Admin_Menus {

   /**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		

		
		add_filter( 'menu_order', array( $this, 'menu_order' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
    }
    

    /**
	 * Add menu items.
	 */
	public function admin_menu() {
		global $menu;

		if ( current_user_can( 'manage_ffd_integration' ) ) {
			$menu[] = array( '', 'read', 'separator-ffd', '', 'wp-menu-separator ffd-integration' ); // WPCS: override ok.
		}

		add_menu_page( __( 'FFD Integration', 'ffd-integeration' ), __( 'FFD Integration', 'ffd-integeration' ), 'manage_ffd_integration', 'ffd-integration',  array( $this, 'main_menu_page' ), null, '55.5' );
	}

	 /**
	 * main_page
	 */
	public function main_menu_page() {

		include('views/html-admin-welcome.php');
	}

    /**
	 * Add menu item.
	 */
	
	public function settings_menu() {
		global $submenu;
		$settings_page = add_submenu_page( 'ffd-integration', __( 'FFD Integration Settings', 'ffd-integration' ), __( 'Settings', 'ffd-integration' ), 'manage_ffd_integration', 'ffd-settings', array( $this, 'settings_page' ) );

		/*if( isset($_GET['section']) && $_GET['section'] === 'propertybase' ){
			$submenucurrent = 'current';
		} else {
			$submenucurrent = '';
		}
		$submenu['ffd-integration'][] = array( 'Propertybase', 'manage_options', admin_url('admin.php?page=ffd-settings&tab=platforms&section=propertybase'), 'FFD Integration Settings PropertyBase', $submenucurrent . ' ffd-settingmenu-links link-propertybase-platform');*/

        if( isset($_GET['section']) && $_GET['section'] === 'propertybase' ){
            $submenucurrent = 'current';
        }else if(isset($_GET['section']) && $_GET['section'] === 'trestle' ) { //Add option trestle
            $submenucurrent = 'current';
        }else {
            $submenucurrent = '';
        }
        $submenu['ffd-integration'][] =
            array(
                'Propertybase',
                'manage_options',
                admin_url('admin.php?page=ffd-settings&tab=platforms&section=propertybase'),
                'FFD Integration Settings PropertyBase',
                $submenucurrent . ' ffd-settingmenu-links link-propertybase-platform',
                'Trestle', //add option trestle
                'manage_options',
                admin_url('admin.php?page=ffd-settings&tab=platforms&section=trestle'),
                'FFD Integration Settings Trestle',
                $submenucurrent . ' ffd-settingmenu-links link-trestle-platform'
            );

        $submenu['ffd-integration'][] =
            array(
                'Trestle', //add option trestle
                'manage_options',
                admin_url('admin.php?page=ffd-settings&tab=platforms&section=trestle'),
                'FFD Integration Settings Trestle',
                $submenucurrent . ' ffd-settingmenu-links link-trestle-platform'
            );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
    }
    

    /**
	 * Loads  methods into memory for use within settings.
	 */
	public function settings_page_init() {
        
        global $current_tab, $current_section;

		if ( ! class_exists( 'FFD_Admin_Settings', false ) ) {
			include dirname( __FILE__ ) . '/class-ffd-admin-settings.php';
		}
		
        // Include settings pages.
		$settings = FFD_Admin_Settings::get_settings_pages();
		

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
		$current_section =  empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.
		
		//$current_tab = apply_filters('ffd_settings_current_tab', $current_tab);
		//$current_section = apply_filters('ffd_settings_current_section', $current_section);

		// Save settings if data has been posted.
		if ( '' !== $current_section && apply_filters( "ffd_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST ) ) ) { // WPCS: input var okay, CSRF ok.
			FFD_Admin_Settings::save();
		} elseif ( '' === $current_section && apply_filters( "ffd_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { // WPCS: input var okay, CSRF ok.
			FFD_Admin_Settings::save();
		}

		// Add any posted messages.
		if ( ! empty( $_GET['ffd_error'] ) ) { // WPCS: input var okay, CSRF ok.
			FFD_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['ffd_error'] ) ) ); // WPCS: input var okay, CSRF ok.
		}

		if ( ! empty( $_GET['ffd_message'] ) ) { // WPCS: input var okay, CSRF ok.
			FFD_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['ffd_message'] ) ) ); // WPCS: input var okay, CSRF ok.
        }
        

        do_action( 'ffd_settings_page_init' );
    }


	/**
	 * Reorder the WC menu items in admin.
	 *
	 * @param int $menu_order Menu order.
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array.
		$ffd_menu_order = array();

		// Get the index of our custom separator.
		$ffd_separator = array_search( 'separator-ffd', (array) $menu_order, true );

		// Get index of listing menu.
		$ffd_listing = array_search( 'edit.php?post_type=listing', (array) $menu_order, true );
		
		$ffd_ui_section = array_search( 'edit.php?post_type=ui-section', (array) $menu_order, true );

		// Loop through menu order and do some rearranging.
		foreach ( $menu_order as $index => $item ) {

			if ( 'ffd-integration' === $item ) {
				$ffd_menu_order[] = 'ffd-integration';
				$ffd_menu_order[] = $item;
				unset( $menu_order[ $ffd_separator ] );

				if( $ffd_listing !== false ){
					$ffd_menu_order[] = 'edit.php?post_type=listing';
					unset( $menu_order[ $ffd_listing ] );
				}

				if( $ffd_ui_section !== false ){
					$ffd_menu_order[] = 'edit.php?post_type=ui-section';
					unset( $menu_order[ $ffd_ui_section ] );
				}

			} elseif ( ! in_array( $item, array( 'separator-ffd' ), true ) ) {
				$ffd_menu_order[] = $item;
			}
		}

		// Return order.
		return $ffd_menu_order;
	}


	/**
	 * Custom menu order.
	 *
	 * @param bool $enabled Whether custom menu ordering is already enabled.
	 * @return bool
	 */
	public function custom_menu_order( $enabled ) {
		return $enabled || current_user_can( 'manage_ffd_integration' );
	}


	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'ffd_keys_per_page', 'ffd_webhooks_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

    /**
	 * Init the settings page.
	 */
	public function settings_page() {
		FFD_Admin_Settings::output();
	}
    
}


return new FFD_Admin_Menus();