<?php
/**
 * FFD Integration Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package FFD Integration\Functions
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend).
require FFD_ABSPATH . 'includes/ffd-conditional-functions.php';
require FFD_ABSPATH . 'includes/ffd-formatting-functions.php';
require FFD_ABSPATH . 'includes/ffd-page-functions.php';




add_filter('ffd_listing_posttype', 'ffd_get_listing_posttype', 1, 1);
function ffd_get_listing_posttype($post_type='listing'){

	$_post_type = $post_type;
	if( $post_type = get_option('ffd_listing_posttype', 'listing') ){
		$_post_type = $post_type;
	}

	if( empty($_post_type) ){
		$_post_type = 'listing';
	}

	return $_post_type;
}

function ffd_listing_posttype_slug(){
	$type = apply_filters('ffd_listing_posttype_slug', 'listing');
	return $type;
}

function ffd_listing_posttype_enabled(){

	$pre = apply_filters( 'pre_ffd_listing_posttype_enabled', true );
	
	
	if( !$pre )
		return false;

	$is_register_post_types = get_option('ffd_listing_posttype_enabled', 'no');

	
	
	return ('yes' === $is_register_post_types );

}

function ffd_ui_posttype_enabled(){

	$pre = apply_filters( 'pre_ffd_ui_posttype_enabled', true );
	
	
	if( !$pre )
		return false;

	$is_register_post_types = get_option('ffd_ui_posttype_enabled', 'no');

	
	
	return ('yes' === $is_register_post_types );

}


function ffd_add_legacy_support(){

	$pre = apply_filters( 'pre_ffd_add_legacy_support', true );
	
	
	if( !$pre )
		return false;

	$is_register_post_types = get_option('ffd_add_legacy_support', 'no');

	
	
	return ('yes' === $is_register_post_types );

}

/**
 * Get permalink settings for things like listings and taxonomies.
 *
 * @since  1.0.0
 * @return array
 */
function ffd_get_permalink_structure() {
	$saved_permalinks = (array) get_option( 'ffd_permalinks', array() );
	$permalinks       = wp_parse_args(
		array_filter( $saved_permalinks ), array(
			'listing_base'           => _x( 'listing', 'slug', 'ffd-integration' ),
			'category_base'          => _x( 'listing-category', 'slug', 'ffd-integration' ),
			'tag_base'               => _x( 'listing-tag', 'slug', 'ffd-integration' ),
			'attribute_base'         => '',
			'use_verbose_page_rules' => false,
		)
	);

	if ( $saved_permalinks !== $permalinks ) {
		update_option( 'ffd_permalinks', $permalinks );
	}

	$permalinks['listing_rewrite_slug']   = untrailingslashit( $permalinks['listing_base'] );
	$permalinks['category_rewrite_slug']  = untrailingslashit( $permalinks['category_base'] );
	$permalinks['tag_rewrite_slug']       = untrailingslashit( $permalinks['tag_base'] );
	$permalinks['attribute_rewrite_slug'] = untrailingslashit( $permalinks['attribute_base'] );

	return $permalinks;
}



/**
 * Get an image size by name or defined dimensions.
 *
 * The returned variable is filtered by ffd_get_image_size_{image_size} filter to
 * allow 3rd party customisation.
 *
 * Sizes defined by the theme take priority over settings. Settings are hidden when a theme
 * defines sizes.
 *
 * @param array|string $image_size Name of the image size to get, or an array of dimensions.
 * @return array Array of dimensions including width, height, and cropping mode. Cropping mode is 0 for no crop, and 1 for hard crop.
 */
function ffd_get_image_size( $image_size ) {
	$size = array(
		'width'  => 600,
		'height' => 600,
		'crop'   => 1,
	);

	if ( is_array( $image_size ) ) {
		$size       = array(
			'width'  => isset( $image_size[0] ) ? absint( $image_size[0] ) : 600,
			'height' => isset( $image_size[1] ) ? absint( $image_size[1] ) : 600,
			'crop'   => isset( $image_size[2] ) ? absint( $image_size[2] ) : 1,
		);
		$image_size = $size['width'] . '_' . $size['height'];
	} else {
		$image_size = str_replace( 'ffd_', '', $image_size );

		

		if ( 'single' === $image_size ) {
			$size['width']  = absint( ffd_get_theme_support( 'single_image_width', get_option( 'ffd_single_image_width', 600 ) ) );
			$size['height'] = '';
			$size['crop']   = 0;

		} elseif ( 'gallery_thumbnail' === $image_size ) {
			$size['width']  = absint( ffd_get_theme_support( 'gallery_thumbnail_image_width', 100 ) );
			$size['height'] = $size['width'];
			$size['crop']   = 1;

		} elseif ( 'thumbnail' === $image_size ) {
			$size['width'] = absint( ffd_get_theme_support( 'thumbnail_image_width', get_option( 'ffd_thumbnail_image_width', 300 ) ) );
			$cropping      = get_option( 'ffd_thumbnail_cropping', '1:1' );

			if ( 'uncropped' === $cropping ) {
				$size['height'] = '';
				$size['crop']   = 0;
			} elseif ( 'custom' === $cropping ) {
				$width          = max( 1, get_option( 'ffd_thumbnail_cropping_custom_width', '4' ) );
				$height         = max( 1, get_option( 'ffd_thumbnail_cropping_custom_height', '3' ) );
				$size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			} else {
				$cropping_split = explode( ':', $cropping );
				$width          = max( 1, current( $cropping_split ) );
				$height         = max( 1, end( $cropping_split ) );
				$size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			}
		}
	}

	return apply_filters( 'ffd_get_image_size_' . $image_size, $size );
}



/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
 */
function ffd_enqueue_js( $code ) {
	global $ffd_queued_js;

	if ( empty( $ffd_queued_js ) ) {
		$ffd_queued_js = '';
	}

	$ffd_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function ffd_print_js($dom=true) {
	global $ffd_queued_js;

	if ( ! empty( $ffd_queued_js ) ) {
		// Sanitize.
		$ffd_queued_js = wp_check_invalid_utf8( $ffd_queued_js );
		$ffd_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $ffd_queued_js );
		$ffd_queued_js = str_replace( "\r", '', $ffd_queued_js );
		if( $dom ){
			$js = "<!-- FFD Integration JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $ffd_queued_js });\n</script>\n";
		} else {
			$js = "<!-- FFD Integration JavaScript -->\n<script type=\"text/javascript\">\n $ffd_queued_js \n</script>\n";
		}

		/**
		 * Queued jsfilter.
		 *
		 * @since 2.6.0
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'ffd_queued_js', $js ); // WPCS: XSS ok.

		unset( $ffd_queued_js );
	}
}

/**
 * Output js.
 */
function ffd_echo_js($ffd_echo_js, $dom=true, $return=false) {
	

	if ( ! empty( $ffd_echo_js ) ) {
		// Sanitize.
		$ffd_echo_js = wp_check_invalid_utf8( $ffd_echo_js );
		$ffd_echo_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $ffd_echo_js );
		$ffd_echo_js = str_replace( "\r", '', $ffd_echo_js );
		if( $dom ){
			$js = "<!-- FFD Integration JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $ffd_echo_js });\n</script>\n";
		} else {
			$js = "<!-- FFD Integration JavaScript -->\n<script type=\"text/javascript\">\n $ffd_echo_js \n</script>\n";
		}

		/**
		 * Queued jsfilter.
		 *
		 * @since 2.6.0
		 * @param string $js JavaScript code.
		 */
		
		$js = apply_filters( 'ffd_echo_js', $js ); // WPCS: XSS ok.
		if( $return )
			return $js;
		else
			echo $js;
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param  string  $name   Name of the cookie being set.
 * @param  string  $value  Value of the cookie.
 * @param  integer $expire Expiry of the cookie.
 * @param  bool    $secure Whether the cookie should be served only over https.
 */
function ffd_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'ffd_cookie_httponly', false, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
	}
}

/**
 * Return "theme support" values from the current theme, if set.
 *
 * @since  1.0.0
 * @param  string $prop Name of prop (or key::subkey for arrays of props) if you want a specific value. Leave blank to get all props as an array.
 * @param  mixed  $default Optional value to return if the theme does not declare support for a prop.
 * @return mixed  Value of prop(s).
 */
function ffd_get_theme_support( $prop = '', $default = null ) {
	$theme_support = get_theme_support( 'ffd-integration' );
	$theme_support = is_array( $theme_support ) ? $theme_support[0] : false;

	if ( ! $theme_support ) {
		return $default;
	}

	if ( $prop ) {
		$prop_stack = explode( '::', $prop );
		$prop_key   = array_shift( $prop_stack );

		if ( isset( $theme_support[ $prop_key ] ) ) {
			$value = $theme_support[ $prop_key ];

			if ( count( $prop_stack ) ) {
				foreach ( $prop_stack as $prop_key ) {
					if ( is_array( $value ) && isset( $value[ $prop_key ] ) ) {
						$value = $value[ $prop_key ];
					} else {
						$value = $default;
						break;
					}
				}
			}
		} else {
			$value = $default;
		}

		return $value;
	}

	return $theme_support;
}



/**
 * Get template part ().
 *
 * FFD_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function ffd_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/ffd-templates/slug-name.php.
	if ( $name && ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", FFD()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php.
	if ( ! $template && $name && file_exists( FFD()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = FFD()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/ffd-templates/slug.php.
	if ( ! $template && ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", FFD()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'ffd_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}


/**
 * Get template part ().
 *
 * FFD_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function ffd_get_args_template_part( $slug, $name = '', $args=array()) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/ffd-templates/slug-name.php.
	if ( $name && ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", FFD()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php.
	if ( ! $template && $name && file_exists( FFD()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = FFD()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/ffd-templates/slug.php.
	if ( ! $template && ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", FFD()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'ffd_get_args_template_part', $template, $slug, $name );

	if ( $template ) {
		global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;
		if ( is_array( $wp_query->query_vars ) ) {
			extract( $wp_query->query_vars, EXTR_SKIP );
		}

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP); // @codingStandardsIgnoreLine
			
		}

		if ( isset( $s ) ) {
			$s = esc_attr( $s );
		}

		require( $template );
	}
}



/**
 * Get other templates (e.g. listing attributes) passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function ffd_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // @codingStandardsIgnoreLine
	}

	$located = ffd_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		/* translators: %s template */
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'ffd_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'ffd_before_template_part', $template_name, $template_path, $located, $args );

	include $located;

	do_action( 'ffd_after_template_part', $template_name, $template_path, $located, $args );
}



/**
 * Like ffd_get_template, but returns the HTML instead of outputting.
 *
 * @see ffd_get_template
 * @since 2.5.0
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function ffd_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	ffd_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}


/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function ffd_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = FFD()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = FFD()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/.
	if ( ! $template || FFD_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'ffd_locate_template', $template, $template_name, $template_path );
}


/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function ffd_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}


/**
 * Get Base Currency Code.
 *
 * @return string
 */
function get_ffd_currency() {
	return apply_filters( 'ffd_currency', get_option( 'ffd_currency' ) );
}

/**
 * Display a FFD Integration help tip.
 *
 * @since  2.5.0
 *
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
 * @return string
 */
function ffd_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = ffd_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="ffd-help-tip" data-tip="' . $tip . '"></span>';
}



/**
 * Formats a string in the format COUNTRY:STATE into an array.
 *
 * @since 1.0.0
 * @param  string $country_string Country string.
 * @return array
 */
function ffd_format_country_state_string( $country_string ) {
	if ( strstr( $country_string, ':' ) ) {
		list( $country, $state ) = explode( ':', $country_string );
	} else {
		$country = $country_string;
		$state   = '';
	}
	return array(
		'country' => $country,
		'state'   => $state,
	);
}

/**
 * Get the store's base location.
 *
 * @since 1.0.0
 * @return array
 */
function ffd_get_base_location() {
	$default = apply_filters( 'ffd_get_base_location', get_option( 'ffd_default_country' ) );

	return ffd_format_country_state_string( $default );
}

/**
 * Get full list of currency codes.
 *
 * @return array
 */
function get_ffd_currencies() {
	static $currencies;

	if ( ! isset( $currencies ) ) {
		$currencies = array_unique(
			apply_filters(
				'ffd_currencies',
				array(
					'AED' => __( 'United Arab Emirates dirham', 'ffd-integration' ),
					'AFN' => __( 'Afghan afghani', 'ffd-integration' ),
					'ALL' => __( 'Albanian lek', 'ffd-integration' ),
					'AMD' => __( 'Armenian dram', 'ffd-integration' ),
					'ANG' => __( 'Netherlands Antillean guilder', 'ffd-integration' ),
					'AOA' => __( 'Angolan kwanza', 'ffd-integration' ),
					'ARS' => __( 'Argentine peso', 'ffd-integration' ),
					'AUD' => __( 'Australian dollar', 'ffd-integration' ),
					'AWG' => __( 'Aruban florin', 'ffd-integration' ),
					'AZN' => __( 'Azerbaijani manat', 'ffd-integration' ),
					'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'ffd-integration' ),
					'BBD' => __( 'Barbadian dollar', 'ffd-integration' ),
					'BDT' => __( 'Bangladeshi taka', 'ffd-integration' ),
					'BGN' => __( 'Bulgarian lev', 'ffd-integration' ),
					'BHD' => __( 'Bahraini dinar', 'ffd-integration' ),
					'BIF' => __( 'Burundian franc', 'ffd-integration' ),
					'BMD' => __( 'Bermudian dollar', 'ffd-integration' ),
					'BND' => __( 'Brunei dollar', 'ffd-integration' ),
					'BOB' => __( 'Bolivian boliviano', 'ffd-integration' ),
					'BRL' => __( 'Brazilian real', 'ffd-integration' ),
					'BSD' => __( 'Bahamian dollar', 'ffd-integration' ),
					'BTC' => __( 'Bitcoin', 'ffd-integration' ),
					'BTN' => __( 'Bhutanese ngultrum', 'ffd-integration' ),
					'BWP' => __( 'Botswana pula', 'ffd-integration' ),
					'BYR' => __( 'Belarusian ruble (old)', 'ffd-integration' ),
					'BYN' => __( 'Belarusian ruble', 'ffd-integration' ),
					'BZD' => __( 'Belize dollar', 'ffd-integration' ),
					'CAD' => __( 'Canadian dollar', 'ffd-integration' ),
					'CDF' => __( 'Congolese franc', 'ffd-integration' ),
					'CHF' => __( 'Swiss franc', 'ffd-integration' ),
					'CLP' => __( 'Chilean peso', 'ffd-integration' ),
					'CNY' => __( 'Chinese yuan', 'ffd-integration' ),
					'COP' => __( 'Colombian peso', 'ffd-integration' ),
					'CRC' => __( 'Costa Rican col&oacute;n', 'ffd-integration' ),
					'CUC' => __( 'Cuban convertible peso', 'ffd-integration' ),
					'CUP' => __( 'Cuban peso', 'ffd-integration' ),
					'CVE' => __( 'Cape Verdean escudo', 'ffd-integration' ),
					'CZK' => __( 'Czech koruna', 'ffd-integration' ),
					'DJF' => __( 'Djiboutian franc', 'ffd-integration' ),
					'DKK' => __( 'Danish krone', 'ffd-integration' ),
					'DOP' => __( 'Dominican peso', 'ffd-integration' ),
					'DZD' => __( 'Algerian dinar', 'ffd-integration' ),
					'EGP' => __( 'Egyptian pound', 'ffd-integration' ),
					'ERN' => __( 'Eritrean nakfa', 'ffd-integration' ),
					'ETB' => __( 'Ethiopian birr', 'ffd-integration' ),
					'EUR' => __( 'Euro', 'ffd-integration' ),
					'FJD' => __( 'Fijian dollar', 'ffd-integration' ),
					'FKP' => __( 'Falkland Islands pound', 'ffd-integration' ),
					'GBP' => __( 'Pound sterling', 'ffd-integration' ),
					'GEL' => __( 'Georgian lari', 'ffd-integration' ),
					'GGP' => __( 'Guernsey pound', 'ffd-integration' ),
					'GHS' => __( 'Ghana cedi', 'ffd-integration' ),
					'GIP' => __( 'Gibraltar pound', 'ffd-integration' ),
					'GMD' => __( 'Gambian dalasi', 'ffd-integration' ),
					'GNF' => __( 'Guinean franc', 'ffd-integration' ),
					'GTQ' => __( 'Guatemalan quetzal', 'ffd-integration' ),
					'GYD' => __( 'Guyanese dollar', 'ffd-integration' ),
					'HKD' => __( 'Hong Kong dollar', 'ffd-integration' ),
					'HNL' => __( 'Honduran lempira', 'ffd-integration' ),
					'HRK' => __( 'Croatian kuna', 'ffd-integration' ),
					'HTG' => __( 'Haitian gourde', 'ffd-integration' ),
					'HUF' => __( 'Hungarian forint', 'ffd-integration' ),
					'IDR' => __( 'Indonesian rupiah', 'ffd-integration' ),
					'ILS' => __( 'Israeli new shekel', 'ffd-integration' ),
					'IMP' => __( 'Manx pound', 'ffd-integration' ),
					'INR' => __( 'Indian rupee', 'ffd-integration' ),
					'IQD' => __( 'Iraqi dinar', 'ffd-integration' ),
					'IRR' => __( 'Iranian rial', 'ffd-integration' ),
					'IRT' => __( 'Iranian toman', 'ffd-integration' ),
					'ISK' => __( 'Icelandic kr&oacute;na', 'ffd-integration' ),
					'JEP' => __( 'Jersey pound', 'ffd-integration' ),
					'JMD' => __( 'Jamaican dollar', 'ffd-integration' ),
					'JOD' => __( 'Jordanian dinar', 'ffd-integration' ),
					'JPY' => __( 'Japanese yen', 'ffd-integration' ),
					'KES' => __( 'Kenyan shilling', 'ffd-integration' ),
					'KGS' => __( 'Kyrgyzstani som', 'ffd-integration' ),
					'KHR' => __( 'Cambodian riel', 'ffd-integration' ),
					'KMF' => __( 'Comorian franc', 'ffd-integration' ),
					'KPW' => __( 'North Korean won', 'ffd-integration' ),
					'KRW' => __( 'South Korean won', 'ffd-integration' ),
					'KWD' => __( 'Kuwaiti dinar', 'ffd-integration' ),
					'KYD' => __( 'Cayman Islands dollar', 'ffd-integration' ),
					'KZT' => __( 'Kazakhstani tenge', 'ffd-integration' ),
					'LAK' => __( 'Lao kip', 'ffd-integration' ),
					'LBP' => __( 'Lebanese pound', 'ffd-integration' ),
					'LKR' => __( 'Sri Lankan rupee', 'ffd-integration' ),
					'LRD' => __( 'Liberian dollar', 'ffd-integration' ),
					'LSL' => __( 'Lesotho loti', 'ffd-integration' ),
					'LYD' => __( 'Libyan dinar', 'ffd-integration' ),
					'MAD' => __( 'Moroccan dirham', 'ffd-integration' ),
					'MDL' => __( 'Moldovan leu', 'ffd-integration' ),
					'MGA' => __( 'Malagasy ariary', 'ffd-integration' ),
					'MKD' => __( 'Macedonian denar', 'ffd-integration' ),
					'MMK' => __( 'Burmese kyat', 'ffd-integration' ),
					'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'ffd-integration' ),
					'MOP' => __( 'Macanese pataca', 'ffd-integration' ),
					'MRO' => __( 'Mauritanian ouguiya', 'ffd-integration' ),
					'MUR' => __( 'Mauritian rupee', 'ffd-integration' ),
					'MVR' => __( 'Maldivian rufiyaa', 'ffd-integration' ),
					'MWK' => __( 'Malawian kwacha', 'ffd-integration' ),
					'MXN' => __( 'Mexican peso', 'ffd-integration' ),
					'MYR' => __( 'Malaysian ringgit', 'ffd-integration' ),
					'MZN' => __( 'Mozambican metical', 'ffd-integration' ),
					'NAD' => __( 'Namibian dollar', 'ffd-integration' ),
					'NGN' => __( 'Nigerian naira', 'ffd-integration' ),
					'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'ffd-integration' ),
					'NOK' => __( 'Norwegian krone', 'ffd-integration' ),
					'NPR' => __( 'Nepalese rupee', 'ffd-integration' ),
					'NZD' => __( 'New Zealand dollar', 'ffd-integration' ),
					'OMR' => __( 'Omani rial', 'ffd-integration' ),
					'PAB' => __( 'Panamanian balboa', 'ffd-integration' ),
					'PEN' => __( 'Peruvian nuevo sol', 'ffd-integration' ),
					'PGK' => __( 'Papua New Guinean kina', 'ffd-integration' ),
					'PHP' => __( 'Philippine peso', 'ffd-integration' ),
					'PKR' => __( 'Pakistani rupee', 'ffd-integration' ),
					'PLN' => __( 'Polish z&#x142;oty', 'ffd-integration' ),
					'PRB' => __( 'Transnistrian ruble', 'ffd-integration' ),
					'PYG' => __( 'Paraguayan guaran&iacute;', 'ffd-integration' ),
					'QAR' => __( 'Qatari riyal', 'ffd-integration' ),
					'RON' => __( 'Romanian leu', 'ffd-integration' ),
					'RSD' => __( 'Serbian dinar', 'ffd-integration' ),
					'RUB' => __( 'Russian ruble', 'ffd-integration' ),
					'RWF' => __( 'Rwandan franc', 'ffd-integration' ),
					'SAR' => __( 'Saudi riyal', 'ffd-integration' ),
					'SBD' => __( 'Solomon Islands dollar', 'ffd-integration' ),
					'SCR' => __( 'Seychellois rupee', 'ffd-integration' ),
					'SDG' => __( 'Sudanese pound', 'ffd-integration' ),
					'SEK' => __( 'Swedish krona', 'ffd-integration' ),
					'SGD' => __( 'Singapore dollar', 'ffd-integration' ),
					'SHP' => __( 'Saint Helena pound', 'ffd-integration' ),
					'SLL' => __( 'Sierra Leonean leone', 'ffd-integration' ),
					'SOS' => __( 'Somali shilling', 'ffd-integration' ),
					'SRD' => __( 'Surinamese dollar', 'ffd-integration' ),
					'SSP' => __( 'South Sudanese pound', 'ffd-integration' ),
					'STD' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'ffd-integration' ),
					'SYP' => __( 'Syrian pound', 'ffd-integration' ),
					'SZL' => __( 'Swazi lilangeni', 'ffd-integration' ),
					'THB' => __( 'Thai baht', 'ffd-integration' ),
					'TJS' => __( 'Tajikistani somoni', 'ffd-integration' ),
					'TMT' => __( 'Turkmenistan manat', 'ffd-integration' ),
					'TND' => __( 'Tunisian dinar', 'ffd-integration' ),
					'TOP' => __( 'Tongan pa&#x2bb;anga', 'ffd-integration' ),
					'TRY' => __( 'Turkish lira', 'ffd-integration' ),
					'TTD' => __( 'Trinidad and Tobago dollar', 'ffd-integration' ),
					'TWD' => __( 'New Taiwan dollar', 'ffd-integration' ),
					'TZS' => __( 'Tanzanian shilling', 'ffd-integration' ),
					'UAH' => __( 'Ukrainian hryvnia', 'ffd-integration' ),
					'UGX' => __( 'Ugandan shilling', 'ffd-integration' ),
					'USD' => __( 'United States (US) dollar', 'ffd-integration' ),
					'UYU' => __( 'Uruguayan peso', 'ffd-integration' ),
					'UZS' => __( 'Uzbekistani som', 'ffd-integration' ),
					'VEF' => __( 'Venezuelan bol&iacute;var', 'ffd-integration' ),
					'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'ffd-integration' ),
					'VUV' => __( 'Vanuatu vatu', 'ffd-integration' ),
					'WST' => __( 'Samoan t&#x101;l&#x101;', 'ffd-integration' ),
					'XAF' => __( 'Central African CFA franc', 'ffd-integration' ),
					'XCD' => __( 'East Caribbean dollar', 'ffd-integration' ),
					'XOF' => __( 'West African CFA franc', 'ffd-integration' ),
					'XPF' => __( 'CFP franc', 'ffd-integration' ),
					'YER' => __( 'Yemeni rial', 'ffd-integration' ),
					'ZAR' => __( 'South African rand', 'ffd-integration' ),
					'ZMW' => __( 'Zambian kwacha', 'ffd-integration' ),
				)
			)
		);
	}

	return $currencies;
}


/**
 * Get Currency symbol.
 *
 * @param string $currency Currency. (default: '').
 * @return string
 */
function get_ffd_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = get_ffd_currency();
	}

	$symbols         = apply_filters(
		'ffd_currency_symbols', array(
			'AED' => '&#x62f;.&#x625;',
			'AFN' => '&#x60b;',
			'ALL' => 'L',
			'AMD' => 'AMD',
			'ANG' => '&fnof;',
			'AOA' => 'Kz',
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => 'Afl.',
			'AZN' => 'AZN',
			'BAM' => 'KM',
			'BBD' => '&#36;',
			'BDT' => '&#2547;&nbsp;',
			'BGN' => '&#1083;&#1074;.',
			'BHD' => '.&#x62f;.&#x628;',
			'BIF' => 'Fr',
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => 'Bs.',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTC' => '&#3647;',
			'BTN' => 'Nu.',
			'BWP' => 'P',
			'BYR' => 'Br',
			'BYN' => 'Br',
			'BZD' => '&#36;',
			'CAD' => '&#36;',
			'CDF' => 'Fr',
			'CHF' => '&#67;&#72;&#70;',
			'CLP' => '&#36;',
			'CNY' => '&yen;',
			'COP' => '&#36;',
			'CRC' => '&#x20a1;',
			'CUC' => '&#36;',
			'CUP' => '&#36;',
			'CVE' => '&#36;',
			'CZK' => '&#75;&#269;',
			'DJF' => 'Fr',
			'DKK' => 'DKK',
			'DOP' => 'RD&#36;',
			'DZD' => '&#x62f;.&#x62c;',
			'EGP' => 'EGP',
			'ERN' => 'Nfk',
			'ETB' => 'Br',
			'EUR' => '&euro;',
			'FJD' => '&#36;',
			'FKP' => '&pound;',
			'GBP' => '&pound;',
			'GEL' => '&#x20be;',
			'GGP' => '&pound;',
			'GHS' => '&#x20b5;',
			'GIP' => '&pound;',
			'GMD' => 'D',
			'GNF' => 'Fr',
			'GTQ' => 'Q',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => 'L',
			'HRK' => 'kn',
			'HTG' => 'G',
			'HUF' => '&#70;&#116;',
			'IDR' => 'Rp',
			'ILS' => '&#8362;',
			'IMP' => '&pound;',
			'INR' => '&#8377;',
			'IQD' => '&#x639;.&#x62f;',
			'IRR' => '&#xfdfc;',
			'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
			'ISK' => 'kr.',
			'JEP' => '&pound;',
			'JMD' => '&#36;',
			'JOD' => '&#x62f;.&#x627;',
			'JPY' => '&yen;',
			'KES' => 'KSh',
			'KGS' => '&#x441;&#x43e;&#x43c;',
			'KHR' => '&#x17db;',
			'KMF' => 'Fr',
			'KPW' => '&#x20a9;',
			'KRW' => '&#8361;',
			'KWD' => '&#x62f;.&#x643;',
			'KYD' => '&#36;',
			'KZT' => 'KZT',
			'LAK' => '&#8365;',
			'LBP' => '&#x644;.&#x644;',
			'LKR' => '&#xdbb;&#xdd4;',
			'LRD' => '&#36;',
			'LSL' => 'L',
			'LYD' => '&#x644;.&#x62f;',
			'MAD' => '&#x62f;.&#x645;.',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => '&#x434;&#x435;&#x43d;',
			'MMK' => 'Ks',
			'MNT' => '&#x20ae;',
			'MOP' => 'P',
			'MRO' => 'UM',
			'MUR' => '&#x20a8;',
			'MVR' => '.&#x783;',
			'MWK' => 'MK',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => 'MT',
			'NAD' => '&#36;',
			'NGN' => '&#8358;',
			'NIO' => 'C&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#x631;.&#x639;.',
			'PAB' => 'B/.',
			'PEN' => 'S/.',
			'PGK' => 'K',
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PRB' => '&#x440;.',
			'PYG' => '&#8370;',
			'QAR' => '&#x631;.&#x642;',
			'RMB' => '&yen;',
			'RON' => 'lei',
			'RSD' => '&#x434;&#x438;&#x43d;.',
			'RUB' => '&#8381;',
			'RWF' => 'Fr',
			'SAR' => '&#x631;.&#x633;',
			'SBD' => '&#36;',
			'SCR' => '&#x20a8;',
			'SDG' => '&#x62c;.&#x633;.',
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&pound;',
			'SLL' => 'Le',
			'SOS' => 'Sh',
			'SRD' => '&#36;',
			'SSP' => '&pound;',
			'STD' => 'Db',
			'SYP' => '&#x644;.&#x633;',
			'SZL' => 'L',
			'THB' => '&#3647;',
			'TJS' => '&#x405;&#x41c;',
			'TMT' => 'm',
			'TND' => '&#x62f;.&#x62a;',
			'TOP' => 'T&#36;',
			'TRY' => '&#8378;',
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => 'Sh',
			'UAH' => '&#8372;',
			'UGX' => 'UGX',
			'USD' => '&#36;',
			'UYU' => '&#36;',
			'UZS' => 'UZS',
			'VEF' => 'Bs F',
			'VND' => '&#8363;',
			'VUV' => 'Vt',
			'WST' => 'T',
			'XAF' => 'CFA',
			'XCD' => '&#36;',
			'XOF' => 'CFA',
			'XPF' => 'Fr',
			'YER' => '&#xfdfc;',
			'ZAR' => '&#82;',
			'ZMW' => 'ZK',
		)
	);
	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

	return apply_filters( 'ffd_currency_symbol', $currency_symbol, $currency );
}


/**
 * used for debuging $var value
 */
function ffd_debug( $var = '' , $exit=false) {

	echo '<pre style="/*display: none;*/">';
		if( is_object($var) || is_array($var) )
			print_r($var);
		else 
			var_dump($var);
    echo '</pre>';
    if( $exit )
        die();
}

/* 
* return mustache instance
 */

function ffd_mustache($args=array(), $options = array()){

	require_once FFD_ABSPATH . '/includes/libraries//Mustache/Autoloader.php';
	Mustache_Autoloader::register();

	$template_dir = $args['template_dir'];
	$partials_dir = $template_dir . 'partials/';

	$options =  array('extension' => '.html');
	$settings = array(
		'loader' => new Mustache_Loader_FilesystemLoader($template_dir, $options),
		'partials_loader' => new Mustache_Loader_FilesystemLoader($partials_dir, $options),
		
	);
	

	$mustache_engine = new Mustache_Engine($settings, $options);

	$mustache_engine->addHelper('case', [
		'lower' => function($value) { return strtolower((string) $value); },
		'upper' => function($value) { return strtoupper((string) $value); },
		'capitalize' => function($value) { return ucwords((string) $value); },
	]);

	$mustache_engine->addHelper('currency', function($value) { return '$' . number_format($value); });
	$mustache_engine->addHelper('number', function($value) { return floor($value); });
	$mustache_engine->addHelper('size', function($value) { return number_format($value); });

	return $mustache_engine;
}


function ffd_mustache_render_template($slug, $data, $echo=true){

	global $ffd_mustache;

	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/ffd-templates/slug-name.php.
	if (  ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", FFD()->template_path() . "html/{$slug}.html" ) );
	}

	// If template file doesn't exist, look in yourtheme/slug.html and yourtheme/ffd-templates/slug.html.
	if ( ! $template && ! FFD_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( FFD()->template_path() . "html/{$slug}.html", "{$slug}.html") );
	}
	
	if( $template ){
		$template_dir = str_replace("{$slug}.html", '', $template);
	
	// Allow 3rd party plugins to filter template file from their plugin.
	//$template = apply_filters( 'ffd_mustache_render_template', $template_dir, $slug);
	
	if( !isset($ffd_mustache) ){
		$ffd_mustache = ffd_mustache(array('template_dir' => $template_dir ));
	}

	//$mustache->render('listing-card', $data);
	$template = $ffd_mustache->loadTemplate($slug);
	$render = $template->render($data);
	if( $echo )
		echo $render; 
	else
		return $render;
		 
	}

	return false;

}


if( !function_exists('ffd_get_page_permalink') ){

	function ffd_get_page_permalink($name=''){

		// @todo
		return home_url();
	}
}


function ffd_query_listings_by_params($meta_query=array(), $args=array()){

    $default_args = array(
        'post_type' => 'listing', 
        'post_status' => 'publish', 
        'posts_per_page' => 12
    );
    $args = array_merge($default_args, $args);
    $args['meta_query'] = $meta_query;
	
    $query = new WP_Query($args);
	
    return $query;
}