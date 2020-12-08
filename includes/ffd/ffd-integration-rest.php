<?php

/**
 *
 *
 * @package FFD_Integration_Rest
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * 
 *
 * @class FFD_Integration_Rest
 */
class FFD_Integration_Rest {

    /**
	 * The single instance of the class.
	 *
	 * @var FFD_Integration
	 * @since 2.1
	*/
    protected static $_instance = null;



    /**
	 * Main FFD_Integration_Rest Instance.
	 *
	 * Ensures only one instance of FFD_Integration_Rest is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see FFD()
	 * @return FFD_Integration_Rest - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
    }




    /**
	 * FFD_Integration_Search Constructor.
	 */
	public function __construct() {

        $this->init_hooks();
       
    
    }


     /**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
    public function init_hooks(){

        add_action( 'rest_api_init', array($this, 'rest_api_init'));

        /**
         * Add REST API support to an already registered post type.
         */
        add_filter( 'register_post_type_args', array($this, 'listing_post_type_args'), 10, 2 );
        
        /**
         * Add REST API support to an already registered taxonomy.
         */
        add_filter( 'register_taxonomy_args', array($this, 'listing_taxonomy_args'), 10, 2 );
        

       
        add_filter( 'rest_prepare_property', array($this, 'prepare_listing_data'), 10, 3 );
        add_filter( 'rest_prepare_listing', array($this, 'prepare_listing_data'), 10, 3 );
        
       
    }


    public function rest_api_init(){
        
        
        register_rest_route( 'ffd/v1', '/listings/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'listings'),
        ) );
        
    }


    public function listings(WP_REST_Request $request){

    }

    public function listing_taxonomy_args( $args, $taxonomy_name ) {
        
        if ( 'genre' === $taxonomy_name ) {
            $args['show_in_rest'] = true;
    
            // Optionally customize the rest_base or rest_controller_class
            //$args['rest_base']             = 'genres';
            //$args['rest_controller_class'] = 'WP_REST_Terms_Controller';
        }
    
        return $args;
    }



    public function listing_post_type_args( $args, $post_type ) {
       
        if ( 'listing' === $post_type || 'property' === $post_type ) {
            
            $args['show_in_rest'] = true;
    
            // Optionally customize the rest_base or rest_controller_class
            $args['rest_base']             = $post_type;
            //$args['rest_controller_class'] = 'WP_REST_Posts_Controller';
           
        }
    
        return $args;
    }

    public function listing_meta_args(){

        /*Original para propertybase
        $fields = FFD_Listings_Sync::fields('propertybase', 'meta');
        foreach( $fields as  $pb_key => $meta_key){
            $meta_args = array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
            );
            register_post_meta( 'page', $meta_key, $meta_args );
        }
        */

        $fields = FFD_Listings_Trestle_Sync::fields('trestle', 'meta');
        foreach( $fields as  $pb_key => $meta_key){
            $meta_args = array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
            );
            register_post_meta( 'page', $meta_key, $meta_args );
        }

        
    }

    public function prepare_listing_data( $data, $post, $context ) {
        if( isset($post->ID) )
            $post_id = $post->ID;
        else
            $post_id = $post;
        /*original para Propertybase
        $fields = FFD_Listings_Sync::fields('propertybase', 'meta');
        */
        $fields = FFD_Listings_Trestle_Sync::fields('trestle', 'meta');
        foreach( $fields as  $pb_key => $meta_key){
            $single = true;
            if( 'ffd_media' === $meta_key ){
                $single = false;
            }
            $value = get_post_meta($post_id, $meta_key, $single);
        
            if( $value ) {
                $data->data['fields'][$pb_key] = $value;
            }
        }
        return $data;
           

    }

    
    
}


/**
 * Main instance of FFD_Integration_Rest.
 *
 * Returns the main instance of FFD_REST to prevent the need to use globals.
 *
 * @since  1.0
 * @return FFD_Integration_Rest
 */
function FFD_REST() {
	return FFD_Integration_Rest::instance();
}
FFD_REST();
