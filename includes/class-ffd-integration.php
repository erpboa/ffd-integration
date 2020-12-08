<?php
/**
 * Plugin setup
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
final class FFD_Integration {

    /**
	 * FFD Integration version.
	 *
	 * @var string
	 */
	public $version = '3.5.3';

	/**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var FFD_Session|FFD_Session_Handler
	 */
	public $session = null;

	/**
	 * Liquid instance.
	 *
	 * @var FFD_Liquid
	 */
	public $liquid = null;


	/**
	 * FFD Integration Search instance.
	 *
	 * @var FFD_Liquid
	 */
	public $search = null;

	/**
	 * Listing factory instance.
	 *
	 * @var FFD_Listing_Factory
	 */
	public $listing_factory = null;

	/**
	 * Countries instance.
	 *
	 * @var FFD_Countries
	 */
	public $countries = null;

	/**
	 * Structured data instance.
	 *
	 * @var FFD_Structured_Data
	 */
	public $structured_data = null;
    

    /**
	 * Main FFD_Integration Instance.
	 *
	 * Ensures only one instance of FFD_Integration is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
  }
    

    /**
	 * FFD_Integration Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'ffd_integration_loaded' );
  }
    

    /**
	 * Define FFD Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'FFD_ABSPATH', dirname( FFD_PLUGIN_FILE ) . '/' );
		$this->define( 'FFD_PLUGIN_BASENAME', plugin_basename( FFD_PLUGIN_FILE ) );
		$this->define( 'FFD_VERSION', $this->version );
		$this->define( 'FFD_ROUNDING_PRECISION', 6 );
		$this->define( 'FFD_DELIMITER', '|' );
		$this->define( 'FFD_LOG_DIR', $upload_dir['basedir'] . '/ffd-logs/' );
		$this->define( 'FFD_SESSION_CACHE_GROUP', 'ffd_session_id' );
		$this->define( 'FFD_TEMPLATE_DEBUG_MODE', false );
		
		
		$this->define( 'FFD_LISTING_POST_TYPE', 'listing');


  }

    
    /**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
    }


    /**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
        switch ( $type ) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined( 'DOING_AJAX' );
            case 'cron':
                return defined( 'DOING_CRON' );
            case 'frontend':
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
        }
    }
    


    /**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {



			/**
			 * Core classes.
			*/
			
			include_once FFD_ABSPATH . 'includes/ffd-file-cache-system.php';
			include_once FFD_ABSPATH . 'includes/ffd-core-functions.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-post-types.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-install.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-countries.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-liquid.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-shortcodes.php';
			include_once FFD_ABSPATH . 'includes/class-ffd-propertybase-api.php';
            include_once FFD_ABSPATH . 'includes/class-ffd-trestle-api.php'; //Add to Trestle API
			include_once FFD_ABSPATH . 'includes/class-ffd-listings-sync.php';
            include_once FFD_ABSPATH . 'includes/class-ffd-listings-trestle-sync.php'; //Add to Trestle Sync
			include_once FFD_ABSPATH . 'includes/acf-fields/acf-fields.php';
			
			//ffd salesforce rest api
			include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-salesforce.php';

			//ffd pb sync
			include_once FFD_ABSPATH . 'includes/ffd/ffd-propertybase-sync.php';

			//ffd trestle sync
            include_once FFD_ABSPATH . 'includes/ffd/ffd-trestle-sync.php';

			//ffd ui
			include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-ui.php';

			//ffd analytic
			include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-analytic.php';

			//ffd organizer
			include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-orgnizer.php';
		

			if ( $this->is_request( 'admin' ) ) {
				include_once FFD_ABSPATH . 'includes/libraries/hesh/html-editor-syntax-highlighter.php';
				include_once FFD_ABSPATH . 'includes/admin/class-ffd-admin.php';
			}
        

      		if ( $this->is_request( 'frontend' ) ) {
				$this->frontend_includes();
			}


			$this->liquid = FFD_Liquid();
			if( 'yes' === get_option('ffd_integration_search_enabled') ){
				include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-search.php';
				$this->search = FFD_Search();
			}

			

			//wp rest api support
			include_once FFD_ABSPATH . 'includes/ffd/ffd-integration-rest.php';

    }
    

    /**
	 * Include required frontend files.
	 */
	public function frontend_includes() {

			do_action('ffd_frontend_includes');
  }

    /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
	private function init_hooks() {

	    /* Llamada original a Property Base
		register_activation_hook( FFD_PLUGIN_FILE, array( 'FFD_Listings_Sync', 'activated' ) );
		register_deactivation_hook( FFD_PLUGIN_FILE, array( 'FFD_Listings_Sync', 'de_activated' ) );
        */

        //Nueva llamada a Trestle
        register_activation_hook( FFD_PLUGIN_FILE, array( 'FFD_Listings_Trestle_Sync', 'activated' ) );
        register_deactivation_hook( FFD_PLUGIN_FILE, array( 'FFD_Listings_Trestle_Sync', 'de_activated' ) );

		register_activation_hook( FFD_PLUGIN_FILE, array( 'FFD_Install', 'install' ) );
		
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ));
		add_action( 'init', array( 'FFD_Shortcodes', 'init' ) );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
    }


    /**
	 * Function used to Init FFD Integration Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once FFD_ABSPATH . 'includes/ffd-template-functions.php';
	}

    /**
	 * Init FFD Integration when WordPress Initialises.
	 */
	public function init() {

			// Load class instances.
			$this->countries = new FFD_Countries();
			// Init action.
			do_action( 'ffd_init' );
  }
    

    /**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		
			$this->add_thumbnail_support();
  }
    

    /**
	 * Ensure post thumbnail support is turned on.
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( 'listing', 'thumbnail' );
  }
    

    /**
	 * Add FFD Integration Image sizes to WP.
	 *
	 *
	 * ffd_thumbnail - Used in listings. We assume these work for a 3 column grid layout.
	 * ffd_single - Used on single pages for the main image.
	 *
	 * @since 1.0
	 */
	public function add_image_sizes() {
		$thumbnail         = ffd_get_image_size( 'thumbnail' );
		$single            = ffd_get_image_size( 'single' );
		$gallery_thumbnail = ffd_get_image_size( 'gallery_thumbnail' );

		add_image_size( 'ffd_thumbnail', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'ffd_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'ffd_gallery_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );

  }
    


    /**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', FFD_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( FFD_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'ffd_template_path', 'ffd-integration/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
  }
    

}