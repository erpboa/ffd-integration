<?php

/**
 * FFD Legacy Support setup
 *
 * @package FFD_Integration
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main  Plugin Class.
 *
 * @class FFD_Integration
 */
final class FFD_Legacy_Support {
    
    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Legacy_Support
	 * @since 2.1
	 */
    protected static $_instance = null;
    

    /**
	 * Main FFD_Integration Instance.
	 *
	 * Ensures only one instance of FFD_Legacy_Support is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Legacy_Support - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }


    /**
	 * FFD_Legacy_Support Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

	

		do_action( 'ffdl_loaded' );
    }
    

    private function define_constants(){


    }


    private function includes(){

		
		
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffd-class-legacy-analytic.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffd-class-legacy-helper.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffd-legacy-page-templater.php';
			
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffd-legacy-shortcodes.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffd-legacy-functions.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/ffdl-save-to-pb.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/class-ffd-ui-section-rendrer.php';
			include_once FFD_ABSPATH . 'includes/legacy-includes/cf7-user-registeration.php';
			//include_once FFD_ABSPATH . 'includes/legacy-includes/ffdl-map-search.php';
		
		}



    private function init_hooks(){

			register_activation_hook( FFD_PLUGIN_FILE, array( 'FFDAnalytics', 'activation' ) );
			register_deactivation_hook( FFD_PLUGIN_FILE, array( 'FFDAnalytics', 'deactivation' ) );

			add_action('init', array($this, 'after_wp_init'));
			add_action('after_setup_theme', array($this, 'after_theme_init'));
        
		}
	
		public function after_wp_init(){

			
		

		}

		public function after_theme_init(){

			
				
			
		}

}