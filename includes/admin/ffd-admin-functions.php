<?php
/**
 * FFD Integration Admin Functions
 *
 * @author   FrozenFish
 * @category Core
 * @package  FFD Integration/Admin/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get all FFD Integration screen ids.
 *
 * @return array
 */
function ffd_get_screen_ids() {

	$ffd_screen_id = sanitize_title( __( 'FFD Integration', 'ffd-integration' ) );
	$screen_ids   = array(
		'toplevel_page_' . $ffd_screen_id,
		$ffd_screen_id . '_page_ffd-settings',
		'product_page_product_attributes',
		'product_page_product_exporter',
		'product_page_product_importer',
		'edit-product',
		'product',
		'edit-product_cat',
		'edit-product_tag',
		'profile',
		'user-edit',
	);



	return apply_filters( 'ffd_screen_ids', $screen_ids );
}

/**
 * Create a page and store the ID in an option.
 *
 * @param mixed  $slug Slug for the new page
 * @param string $option Option name to store the page's ID
 * @param string $page_title (default: '') Title for the new page
 * @param string $page_content (default: '') Content for the new page
 * @param int    $post_parent (default: 0) Parent for the new page
 * @return int page ID
 */
function ffd_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$option_value = get_option( $option );

	if ( $option_value > 0 && ( $page_object = get_post( $option_value ) ) ) {
		if ( 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ) ) ) {
			// Valid page is already in place
			return $page_object->ID;
		}
	}

	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode)
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		// Search for an existing page with the specified page slug
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
	}

	$valid_page_found = apply_filters( 'ffd_create_page_id', $valid_page_found, $slug, $page_content );

	if ( $valid_page_found ) {
		if ( $option ) {
			update_option( $option, $valid_page_found );
		}
		return $valid_page_found;
	}

	// Search for a matching valid trashed page
	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode)
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		// Search for an existing page with the specified page slug
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
	}

	if ( $trashed_page_found ) {
		$page_id   = $trashed_page_found;
		$page_data = array(
			'ID'          => $page_id,
			'post_status' => 'publish',
		);
		wp_update_post( $page_data );
	} else {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed',
		);
		$page_id   = wp_insert_post( $page_data );
	}

	if ( $option ) {
		update_option( $option, $page_id );
	}

	return $page_id;
}

/**
 * Output admin fields.
 *
 * Loops though the FFD Integration options array and outputs each field.
 *
 * @param array $options Opens array to output
 */
function ffd_admin_fields( $options ) {

	if ( ! class_exists( 'FFD_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-ffd-admin-settings.php';
	}

	FFD_Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options
 * @param array $data
 */
function ffd_update_options( $options, $data = null ) {

	if ( ! class_exists( 'FFD_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-ffd-admin-settings.php';
	}

	FFD_Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name
 * @param mixed $default
 * @return string
 */
function ffd_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'FFD_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-ffd-admin-settings.php';
	}

	return FFD_Admin_Settings::get_option( $option_name, $default );
}


/**
 * Get HTML for some action buttons. Used in list tables.
 *
 * @since 3.3.0
 * @param array $actions Actions to output.
 * @return string
 */
function ffd_render_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="ffd-action-button-group"><label>' . $action['group'] . '</label> <span class="ffd-action-button-group__items">' . ffd_render_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$actions_html .= sprintf( '<a class="button ffd-action-button ffd-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}


function ffd_is_protected_meta( $protected, $meta_key, $meta_type ) {
	
	if( strpos($meta_key, 'ffd_') === 0 ){
		$protected = true;
	}
 
    return $protected;
}
add_filter( 'is_protected_meta', 'ffd_is_protected_meta', 10, 3 );