<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package FFD Integration/Classes/Listings
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class FFD_Post_Types {

    /**
	 * Hook in methods.
	 */
	public static function init() {

			add_action('init', array( __CLASS__, 'wp_init' ), 5 );

	}
	
	public static function wp_init(){
		
		$listing_posttype = ffd_listing_posttype_enabled();
		

		if( true === $listing_posttype ){
			add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 6 );
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 6 );
			add_action( 'init', array( __CLASS__, 'register_post_status' ), 9 );
			add_filter( 'rest_api_allowed_post_types', array( __CLASS__, 'rest_api_allowed_post_types' ) );
			add_action( 'ffd_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
			add_action( 'ffd_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
			add_filter( 'gutenberg_can_edit_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
			add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
		}
	}

    /**
	 * Flush rules if the event is queued.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'ffd_queue_flush_rewrite_rules' ) ) {
			update_option( 'ffd_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

    /**
	 * Disable Gutenberg for listings.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
		return apply_filters('ffd_listing_posttype', 'listing') === $post_type ? false : $can_edit;
  }
    
    /**
	 * Added listing for Jetpack related posts.
	 *
	 * @param  array $post_types Post types.
	 * @return array
	 */
	public static function rest_api_allowed_post_types( $post_types ) {
		$post_types[] = apply_filters('ffd_listing_posttype', 'listing');

		return $post_types;
	}
    
    /**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies() {

        do_action( 'ffd_register_taxonomy' );
        $permalinks = ffd_get_permalink_structure();
        
        register_taxonomy(
			'listing_cat',
			apply_filters( 'ffd_taxonomy_objects_listing_cat', array( apply_filters('ffd_listing_posttype', 'listing') ) ),
			apply_filters(
				'ffd_taxonomy_args_listing_cat', array(
					'hierarchical'          => true,
					'update_count_callback' => '_ffd_term_recount',
					'label'                 => __( 'Categories', 'ffd-integration' ),
					'labels'                => array(
						'name'              => __( 'Listing categories', 'ffd-integration' ),
						'singular_name'     => __( 'Category', 'ffd-integration' ),
						'menu_name'         => _x( 'Categories', 'Admin menu name', 'ffd-integration' ),
						'search_items'      => __( 'Search categories', 'ffd-integration' ),
						'all_items'         => __( 'All categories', 'ffd-integration' ),
						'parent_item'       => __( 'Parent category', 'ffd-integration' ),
						'parent_item_colon' => __( 'Parent category:', 'ffd-integration' ),
						'edit_item'         => __( 'Edit category', 'ffd-integration' ),
						'update_item'       => __( 'Update category', 'ffd-integration' ),
						'add_new_item'      => __( 'Add new category', 'ffd-integration' ),
						'new_item_name'     => __( 'New category name', 'ffd-integration' ),
						'not_found'         => __( 'No categories found', 'ffd-integration' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_listing_terms',
						'edit_terms'   => 'edit_listing_terms',
						'delete_terms' => 'delete_listing_terms',
						'assign_terms' => 'assign_listing_terms',
					),
					'rewrite'               => array(
						'slug'         => $permalinks['category_rewrite_slug'],
						'with_front'   => false,
						'hierarchical' => true,
					),
				)
			)
		);

		register_taxonomy(
			'listing_tag',
			apply_filters( 'ffd_taxonomy_objects_listing_tag', array( apply_filters('ffd_listing_posttype', 'listing') ) ),
			apply_filters(
				'ffd_taxonomy_args_listing_tag', array(
					'hierarchical'          => false,
					'update_count_callback' => '_ffd_term_recount',
					'label'                 => __( 'Listing tags', 'ffd-integration' ),
					'labels'                => array(
						'name'                       => __( 'Listing tags', 'ffd-integration' ),
						'singular_name'              => __( 'Tag', 'ffd-integration' ),
						'menu_name'                  => _x( 'Tags', 'Admin menu name', 'ffd-integration' ),
						'search_items'               => __( 'Search tags', 'ffd-integration' ),
						'all_items'                  => __( 'All tags', 'ffd-integration' ),
						'edit_item'                  => __( 'Edit tag', 'ffd-integration' ),
						'update_item'                => __( 'Update tag', 'ffd-integration' ),
						'add_new_item'               => __( 'Add new tag', 'ffd-integration' ),
						'new_item_name'              => __( 'New tag name', 'ffd-integration' ),
						'popular_items'              => __( 'Popular tags', 'ffd-integration' ),
						'separate_items_with_commas' => __( 'Separate tags with commas', 'ffd-integration' ),
						'add_or_remove_items'        => __( 'Add or remove tags', 'ffd-integration' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags', 'ffd-integration' ),
						'not_found'                  => __( 'No tags found', 'ffd-integration' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_listing_terms',
						'edit_terms'   => 'edit_listing_terms',
						'delete_terms' => 'delete_listing_terms',
						'assign_terms' => 'assign_listing_terms',
					),
					'rewrite'               => array(
						'slug'       => $permalinks['tag_rewrite_slug'],
						'with_front' => false,
					),
				)
			)
		);
    }


    /**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( apply_filters('ffd_listing_posttype', 'listing') ) ) {
			return;
		}

		do_action( 'ffd_register_post_type' );

		$permalinks = ffd_get_permalink_structure();
		$supports   = array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown' );

		if ( 'yes' === get_option( 'ffd_enable_reviews', 'yes' ) ) {
			$supports[] = 'comments';
		}

		$listings_page_id = 0;//ffd_get_page_id( 'listings' );

		if ( current_theme_supports( 'FFD_Integration' ) ) {
			$has_archive = $listings_page_id && get_post( $listings_page_id ) ? urldecode( get_page_uri( $listings_page_id ) ) : 'listings';
		} else {
			$has_archive = false;
		}

		// If theme support changes, we may need to flush permalinks since some are changed based on this flag.
		if ( update_option( 'current_theme_supports_ffd', current_theme_supports( 'FFD_Integration' ) ? 'yes' : 'no' ) ) {
			update_option( 'ffd_queue_flush_rewrite_rules', 'yes' );
		}

		register_post_type(
			apply_filters('ffd_listing_posttype', 'listing'),
			apply_filters(
				'ffd_register_post_type_listing',
				array(
					'labels'              => array(
						'name'                  => __( 'Listings', 'ffd-integration' ),
						'singular_name'         => __( 'Listing', 'ffd-integration' ),
						'all_items'             => __( 'All Listings', 'ffd-integration' ),
						'menu_name'             => _x( 'Listings', 'Admin menu name', 'ffd-integration' ),
						'add_new'               => __( 'Add New', 'ffd-integration' ),
						'add_new_item'          => __( 'Add new listing', 'ffd-integration' ),
						'edit'                  => __( 'Edit', 'ffd-integration' ),
						'edit_item'             => __( 'Edit listing', 'ffd-integration' ),
						'new_item'              => __( 'New listing', 'ffd-integration' ),
						'view_item'             => __( 'View listing', 'ffd-integration' ),
						'view_items'            => __( 'View listings', 'ffd-integration' ),
						'search_items'          => __( 'Search listings', 'ffd-integration' ),
						'not_found'             => __( 'No listings found', 'ffd-integration' ),
						'not_found_in_trash'    => __( 'No listings found in trash', 'ffd-integration' ),
						'parent'                => __( 'Parent listing', 'ffd-integration' ),
						'featured_image'        => __( 'Listing image', 'ffd-integration' ),
						'set_featured_image'    => __( 'Set listing image', 'ffd-integration' ),
						'remove_featured_image' => __( 'Remove listing image', 'ffd-integration' ),
						'use_featured_image'    => __( 'Use as listing image', 'ffd-integration' ),
						'insert_into_item'      => __( 'Insert into listing', 'ffd-integration' ),
						'uploaded_to_this_item' => __( 'Uploaded to this listing', 'ffd-integration' ),
						'filter_items_list'     => __( 'Filter listings', 'ffd-integration' ),
						'items_list_navigation' => __( 'Listings navigation', 'ffd-integration' ),
						'items_list'            => __( 'Listings list', 'ffd-integration' ),
					),
					'description'         => __( 'This is where you can add new listings to your site.', 'ffd-integration' ),
					'public'              => true,
					'show_ui'             => true,
					'capability_type'     => 'listing',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => $permalinks['listing_rewrite_slug'] ? array(
						'slug'       => $permalinks['listing_rewrite_slug'],
						'with_front' => false,
						'feeds'      => true,
					) : false,
					'query_var'           => true,
					'supports'            => $supports,
					'has_archive'         => $has_archive,
					'show_in_nav_menus'   => true,
					'show_in_rest'        => true,
				)
			)
		);


    }


    /**
	 * Register our custom post statuses, used for listing status.
	 */
	public static function register_post_status() {

		$listing_statuses = apply_filters(
			'ffd_register_listing_post_statuses',
			array(
				'ffd-active'    => array(
					'label'                     => _x( 'Active', 'Listing status', 'ffd-integration' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of listings */
					'label_count'               => _n_noop( 'Active Listings <span class="count">(%s)</span>', 'Active Listings <span class="count">(%s)</span>', 'ffd-integration' ),
				),
				'ffd-contingent' => array(
					'label'                     => _x( 'Contingent', 'Listing status', 'ffd-integration' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of listings */
					'label_count'               => _n_noop( 'Contingent <span class="count">(%s)</span>', 'Contingent <span class="count">(%s)</span>', 'ffd-integration' ),
				),
				'ffd-on-hold'    => array(
					'label'                     => _x( 'On hold', 'Listing status', 'ffd-integration' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of listings */
					'label_count'               => _n_noop( 'On hold <span class="count">(%s)</span>', 'On hold <span class="count">(%s)</span>', 'ffd-integration' ),
				),
				'ffd-sold'  => array(
					'label'                     => _x( 'Sold', 'Listing status', 'ffd-integration' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of listings */
					'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'ffd-integration' ),
				),
				'ffd-expired'  => array(
					'label'                     => _x( 'Expired', 'Listing status', 'ffd-integration' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of listings */
					'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'ffd-integration' ),
				)
			)
		);

		foreach ( $listing_statuses as $listing_status => $values ) {
			register_post_status( $listing_status, $values );
		}
    }
    


}

FFD_Post_Types::init();
