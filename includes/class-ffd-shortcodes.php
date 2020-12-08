<?php
/**
 * Shortcodes
 *
 * @package FFD Integration/Classes
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * FFD Integration Shortcodes class.
 */
class FFD_Shortcodes {

    /**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'ffd_listing_field'          => __CLASS__ . '::ffd_field',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

    }
    

    /**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'ffd-integration',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
    }
    
	



	public static function ffd_field( $atts ) {
		
		if( !isset($atts['listing_id']) || empty($atts['listing_id'])){
			global $post;
			$listing_id = $post->ID;
		}

		// @codingStandardsIgnoreStart
		$atts = shortcode_atts( array(
			'listing_id'    => $listing_id,
		), $atts, 'ffd_field' );
		// @codingStandardsIgnoreEnd

		if( empty($atts['listing_id']) )
			return '';

		ob_start();
		$feild_value = get_post_meta($listing_id, $atts['listing_id'], true);
		echo $feild_value;

		return ob_get_clean();
	}



	public static function ffd_listing( $atts, $content='') {
		
		global $post;

		if( !isset($atts['listing_id']) || empty($atts['listing_id'])){
			$listing_id = $post->ID;
		} else {
			$post = get_post($atts['listing_id']);
			if( $post ){
				setup_postdata($post);
			}
		}

		// @codingStandardsIgnoreStart
		$atts = shortcode_atts( array(
			'listing_id'    => $listing_id,
		), $atts, 'ffd_listing' );
		// @codingStandardsIgnoreEnd

		if( empty($atts['listing_id']) )
			return '';
			
		ob_start();
		$content = do_shortcode($content);
		echo $content;

		return ob_get_clean();
	}


	public static function ffd_listing_images($atts, $content=''){

		global $post;

		if( !isset($atts['listing_id']) || empty($atts['listing_id'])){
			$listing_id = $post->ID;
		} else {
			$post = get_post($atts['listing_id']);
			if( $post ){
				setup_postdata($post);
			}
		}


		$atts = shortcode_atts( array(
			'listing_id'    => $listing_id,
			'display_arrows'    => true,
		), $atts, 'ffd_listing_images' );
	
		return ffd_listing_images_slider($echo = false, $atts);

	}




}