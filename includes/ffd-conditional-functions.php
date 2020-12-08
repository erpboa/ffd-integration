<?php
/**
 * FFD Integration Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @package     FFD Integration/Functions
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is_FFD_Integration - Returns true if on a page which uses FFD templates .
 *
 * @return bool
 */
function is_ffd_integration() {
	return apply_filters( 'is_ffd_integration', is_listings() || is_listing_taxonomy() || is_listing() );
}


if ( ! function_exists( 'is_listings' ) ) {

	/**
	 * is_listings - Returns true when viewing the listing type archive (listings).
	 *
	 * @return bool
	 */
	function is_listings() {
		return ( is_post_type_archive( 'listing' ) || is_page( ffd_get_page_id( 'listings' ) ) );
	}
}

if ( ! function_exists( 'is_listing_taxonomy' ) ) {

	/**
	 * Is_listing_taxonomy - Returns true when viewing a listing taxonomy archive.
	 *
	 * @return bool
	 */
	function is_listing_taxonomy() {
		return is_tax( get_object_taxonomies( 'listing' ) );
	}
}

if ( ! function_exists( 'is_listing_category' ) ) {

	/**
	 * Is_listing_category - Returns true when viewing a listing category.
	 *
	 * @param  string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_listing_category( $term = '' ) {
		return is_tax( 'listing_cat', $term );
	}
}

if ( ! function_exists( 'is_listing_tag' ) ) {

	/**
	 * Is_listing_tag - Returns true when viewing a listing tag.
	 *
	 * @param  string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_listing_tag( $term = '' ) {
		return is_tax( 'listing_tag', $term );
	}
}

if ( ! function_exists( 'is_listing' ) ) {

	/**
	 * Is_listing - Returns true when viewing a single listing.
	 *
	 * @return bool
	 */
	function is_listing() {
		return is_singular( array( 'listing' ) );
	}
}


if ( ! function_exists( 'is_ffd_endpoint_url' ) ) {

	/**
	 * Is_ffd_endpoint_url - Check if an endpoint is showing.
	 *
	 * @param string|false $endpoint Whether endpoint.
	 * @return bool
	 */
	function is_ffd_endpoint_url( $endpoint = false ) {
		global $wp;

		$ffd_endpoints = FFD()->query->get_query_vars();

		if ( false !== $endpoint ) {
			if ( ! isset( $ffd_endpoints[ $endpoint ] ) ) {
				return false;
			} else {
				$endpoint_var = $ffd_endpoints[ $endpoint ];
			}

			return isset( $wp->query_vars[ $endpoint_var ] );
		} else {
			foreach ( $ffd_endpoints as $key => $value ) {
				if ( isset( $wp->query_vars[ $key ] ) ) {
					return true;
				}
			}

			return false;
		}
	}
}



if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	}
}

if ( ! function_exists( 'is_office_notice_showing' ) ) {

	/**
	 * Is_office_notice_showing - Returns true when office notice is active.
	 *
	 * @return bool
	 */
	function is_office_notice_showing() {
		return 'no' !== get_option( 'ffd_demo_office', 'no' );
	}
}



if ( ! function_exists( 'taxonomy_is_listing_attribute' ) ) {

	/**
	 * Returns true when the passed taxonomy name is a listing attribute.
	 *
	 * @uses   $ffd_listing_attributes global which stores taxonomy names upon registration
	 * @param  string $name of the attribute.
	 * @return bool
	 */
	function taxonomy_is_listing_attribute( $name ) {
		global $ffd_listing_attributes;

		return taxonomy_exists( $name ) && array_key_exists( $name, (array) $ffd_listing_attributes );
	}
}

if ( ! function_exists( 'meta_is_listing_attribute' ) ) {

	/**
	 * Returns true when the passed meta name is a listing attribute.
	 *
	 * @param  string $name of the attribute.
	 * @param  string $value of the attribute.
	 * @param  int    $listing_id to check for attribute.
	 * @return bool
	 */
	function meta_is_listing_attribute( $name, $value, $listing_id ) {
		$listing = ffd_get_listing( $listing_id );

		if ( $listing && method_exists( $listing, 'get_variation_attributes' ) ) {
			$variation_attributes = $listing->get_variation_attributes();
			$attributes           = $listing->get_attributes();
			return ( in_array( $name, array_keys( $attributes ), true ) && in_array( $value, $variation_attributes[ $attributes[ $name ]['name'] ], true ) );
		} else {
			return false;
		}
	}
}


/**
 * Simple check for validating a URL, it must start with http:// or https://.
 * and pass FILTER_VALIDATE_URL validation.
 *
 * @param  string $url to check.
 * @return bool
 */
function ffd_is_valid_url( $url ) {

	// Must start with http:// or https://.
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// Must pass validation.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}

/**
 * Check if the home URL is https. If it is, we don't need to do things such as 'force ssl'.
 *
 * @since  2.4.13
 * @return bool
 */
function ffd_site_is_https() {
	return false !== strstr( get_option( 'home' ), 'https:' );
}


/**
 * Checks whether the content passed contains a specific short code.
 *
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function ffd_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}


if( !function_exists('ffd_get_field') ){
	/**
	 * acf get_field function incase not exist
	 *
	 * @param $selector (string) (Required) The field name or field key.
	* @param $post_id (mixed) (Optional) The post ID where the value is saved. Defaults to the current post.
	* @param  $format_value (bool) (Optional) Whether to apply formatting logic. Defaults to true.
	* @return bool
	*/
	function ffd_get_field( $meta_key, $post_id='',  $format_value=true) {

		if(function_exists('the_field')){
			return get_field($meta_key, $post_id,  $format_value);
		}

		if( empty($post_id) ){
			global $post;
			if( is_a( $post, 'WP_Post' ) ){
				$post_id = $post->ID;
			}
		}

		$single = true;

		return get_post_meta($post_id, $meta_key, $single);
	}
}


if( !function_exists('ffd_the_field') ){
	/**
	 * acf the_field function incase not exist
	 *
	 * @param $selector (string) (Required) The field name or field key.
	* @param $post_id (mixed) (Optional) The post ID where the value is saved. Defaults to the current post.
	* @param  $format_value (bool) (Optional) Whether to apply formatting logic. Defaults to true.
	* @return bool
	*/
	function ffd_the_field( $meta_key, $post_id='',  $format_value='') {

		if(function_exists('the_field')){
			return the_field($meta_key, $post_id,  $format_value);
		}

		if( empty($post_id) ){
			global $post;
			if( is_a( $post, 'WP_Post' ) ){
				$post_id = $post->ID;
			}
		}	
		$single = true;	

		echo get_post_meta($post_id, $meta_key, $single);
	}
}


function ffd_is_wpcf7_active(){

	return class_exists('WPCF7') ? true : false;
}


function ffd_render_contact_form_7($shortcode){

	


	if( !ffd_is_wpcf7_active() ){
		return '<p class="error_render_contact_form_7" style="background-color: #ccc;color: #333;padding: 20px;">contact form 7 ( version 5.1+ ) is required.</p>';
	} 
	
	$atts = shortcode_parse_atts($shortcode);

	if( !empty($atts['title']) && $contact_form = get_page_by_title($atts['title'], 'OBJECT', 'wpcf7_contact_form') ){

		ob_start();
			echo do_shortcode($shortcode);
		return ob_get_clean();

	} else {

		return '<p class="error_render_contact_form_7" style="background-color: #ccc;color: #333;padding: 20px;">contact form '.$atts['title'].' not found.</p>';
	
	}

}