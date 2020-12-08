<?php
/**
 * Plugin Name: FFD Integration
 * Plugin URI: https://www.frozenfishdev.com/
 * Description: A toolkit that helps you creates Real Estate websites / portals
 * Version: 1.0.0
 * Author: Frozen Fish Dev
 * Author URI: https://www.frozenfishdev.com/
 * Text Domain: ffd-integration
 * Domain Path: /i18n/languages/
 *
 * @package FFD Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define FFD_PLUGIN_FILE.
if ( ! defined( 'FFD_PLUGIN_FILE' ) ) {
	define( 'FFD_PLUGIN_FILE', __FILE__ );
}


// Define FFD_PLUGIN_PATH.
if ( ! defined( 'FFD_PLUGIN_PATH' ) ) {
	define( 'FFD_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ));
}

// Define FFD_PLUGIN_URL.
if ( ! defined( 'FFD_PLUGIN_URL' ) ) {
	define( 'FFD_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ));
}


function ffd_include_core_files(){


	
	// Include the data logger class
	if ( ! class_exists( 'FFD_Debug_Logger' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-ffd-data-logger.php';
	}
	
	// Include the main ffd-integration class.
	if ( ! class_exists( 'FFD_Integration' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-ffd-integration.php';
	}


	// Include ffd integration legacy class
	include_once dirname( __FILE__ ) . '/includes/class-ffd-legacy-support.php';


}

ffd_include_core_files();

/**
 * Main instance of FFD_Integration.
 *
 * Returns the main instance of ffd to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration
 */
function FFD() {
	return FFD_Integration::instance();
}
FFD();

/**
 * Main instance of FFD_Legacy_Support.
 *
 * Returns the main instance of FFD_Legacy_Support to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Legacy_Support
 */
function FFD_Legacy() {
	return FFD_Legacy_Support::instance();
}

if ( 'yes' === get_option('ffd_add_legacy_support', 'yes')  ) {

	// Define FFD_LEGACY_SUPPORT.
	if ( ! defined( 'FFD_LEGACY_SUPPORT' ) ) {
		define( 'FFD_LEGACY_SUPPORT', true);
	}

	FFD_Legacy();

}


//
function ffd_plugins_loaded(){

		
}

add_action('plugins_loaded', 'ffd_plugins_loaded');