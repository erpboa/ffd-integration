<?php
/**
 * Installation related functions and actions.
 *
 * @package ffd-integration/Classes
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * FFD_Install Class.
 */
class FFD_Install {


    /**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
    private static $db_updates = array(); // array('{version}'=>array('ffd_update_{version_without_dot}_{fixed_name}'))
    

    /**
	 * Background update class.
	 *
	 * @var object
	 */
    private static $background_updater;
    

    /**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'plugin_action_links_' . FFD_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
    }


	/**
	 * Check FFD Integration version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'ffd_version' ), FFD()->version, '<' ) ) {
			self::install();
			do_action( 'ffd_updated' );
		}
	}


	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {

	}


    /**
	 * Install FFD Integration.
	 */
	public static function install() {

        if ( ! is_blog_installed() ) {
			return;
        }
        
        // Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'ffd_installing' ) ) {
			return;
        }
        
        // If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'ffd_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		ffd_maybe_define_constant( 'FFD_INSTALLING', true );

        //self::remove_admin_notices();
        //self::create_options();
        //self::create_tables();
        self::create_roles();
		self::setup_environment();
		self::maybe_enable_setup_wizard();
  		self::update_ffd_version();
  		self::maybe_update_db_version();

        delete_transient( 'ffd_installing' );

		do_action( 'ffd_flush_rewrite_rules' );
		do_action( 'ffd_installed' );
    }


    /**
	 * Reset any notices added to admin.
	 *
	 * @since 1.0.0
	 */
	private static function remove_admin_notices() {
		
    }


    /**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {

    }
    

    /**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		// Dummy gettext calls to get strings in the catalog.
		/* translators: user role */
		_x( 'Agent', 'User role', 'ffd-integration' );
		/* translators: user role */
		_x( 'Listing manager', 'User role', 'ffd-integration' );

		// Agent role.
		add_role(
			'agent',
			'Agent',
			array(
				'read' => true,
			)
		);

		// listing manager role.
		add_role(
			'listing_manager',
			'listing manager',
			array(
				'level_9'                => true,
				'level_8'                => true,
				'level_7'                => true,
				'level_6'                => true,
				'level_5'                => true,
				'level_4'                => true,
				'level_3'                => true,
				'level_2'                => true,
				'level_1'                => true,
				'level_0'                => true,
				'read'                   => true,
				'read_private_pages'     => true,
				'read_private_posts'     => true,
				'edit_users'             => true,
				'edit_posts'             => true,
				'edit_pages'             => true,
				'edit_published_posts'   => true,
				'edit_published_pages'   => true,
				'edit_private_pages'     => true,
				'edit_private_posts'     => true,
				'edit_others_posts'      => true,
				'edit_others_pages'      => true,
				'publish_posts'          => true,
				'publish_pages'          => true,
				'delete_posts'           => true,
				'delete_pages'           => true,
				'delete_private_pages'   => true,
				'delete_private_posts'   => true,
				'delete_published_pages' => true,
				'delete_published_posts' => true,
				'delete_others_posts'    => true,
				'delete_others_pages'    => true,
				'manage_categories'      => true,
				'manage_links'           => true,
				'moderate_comments'      => true,
				'upload_files'           => true,
				'export'                 => true,
				'import'                 => true,
				'list_users'             => true,
				'edit_theme_options'     => true,
			)
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'listing_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}


    /**
	 * Get capabilities for FFD Integration - these are assigned to admin/listing manager during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_ffd_integration'
		);

		$capability_types = array( 'listing');

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
    }
    

	/**
	 * Is this a brand new FFD install?
	 *
	 * @since  3.2.0
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'ffd_version', null ) ) && is_null( get_option( 'ffd_db_version', null ) );
	}


	/**
	 * Is a DB update needed?
	 *
	 * @since  3.2.0
	 * @return boolean
	 */
	private static function needs_db_update() {
		$current_db_version = get_option( 'ffd_db_version', null );
		$updates            = self::get_db_update_callbacks();
		
		if( empty($updates) ) return false;

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $updates ) ), '<' );
	}

    /**
	 * Setup FFD environment - post types, taxonomies, endpoints.
	 *
	 * @since 3.2.0
	 */
	private static function setup_environment() {
		FFD_Post_types::register_post_types();
		FFD_Post_types::register_taxonomies();
	
	}


	/**
	 * See if we need the wizard or not.
	 *
	 * @since 3.2.0
	 */
	private static function maybe_enable_setup_wizard() {
		if ( apply_filters( 'ffd_enable_setup_wizard', self::is_new_install() ) ) {
			//show install intall notice
		}
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 3.2.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'ffd_enable_auto_update_db', false ) ) {
				self::init_background_updater();
				self::update();
			} else {
				FFD_Admin_Notices::add_notice( 'update' );
			}
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Update FFD version to current.
	 */
	private static function update_ffd_version() {
		delete_option( 'ffd_version' );
		add_option( 'ffd_version', FFD()->version );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		
	}



	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=ffd-settings' ) . '" aria-label="' . esc_attr__( 'View FFD Integration settings', 'ffd-integration' ) . '">' . esc_html__( 'Settings', 'ffd-integration' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}



	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New FFD Integration DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'ffd_db_version' );
		add_option( 'ffd_db_version', is_null( $version ) ? FFD()->version : $version );
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param array $schedules List of WP scheduled cron jobs.
	 *
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'ffd-integration' ),
		);
		return $schedules;
	}


	/**
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {

	}


	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {


	}
}
FFD_Install::init();