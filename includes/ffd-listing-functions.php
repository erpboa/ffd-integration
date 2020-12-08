<?php
/**
 * FFD Integration Listing Functions
 *
 * Functions for listing specific things.
 *
 * @package FFD Integration/Functions
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Standard way of retrieving listings based on certain parameters.
 *
 * This function should be used for listing retrieval so that we have a data agnostic
 * way to get a list of listings.
 *
 *
 * @since  3.0.0
 * @param  array $args Array of args (above).
 * @return array|stdClass Number of pages and an array of listing objects if
 *                             paginate is true, or just an array of values.
 */
function ffd_get_listings( $args ) {
	// Handle some BW compatibility arg names where wp_query args differ in naming.
	$map_legacy = array(
		'numberposts'    => 'limit',
		'post_status'    => 'status',
		'post_parent'    => 'parent',
		'posts_per_page' => 'limit',
		'paged'          => 'page',
	);

	foreach ( $map_legacy as $from => $to ) {
		if ( isset( $args[ $from ] ) ) {
			$args[ $to ] = $args[ $from ];
		}
	}

	$query = new FFD_Listing_Query( $args );
	return $query->get_listings();
}

/**
 * Main function for returning listings, uses the FFD_Listing_Factory class.
 *
 * @since 2.2.0
 *
 * @param mixed $the_listing Post object or post ID of the listing.
 * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
 * @return FFD_Listing|null|false
 */
function ffd_get_listing( $the_listing = false, $deprecated = array() ) {
	if ( ! did_action( 'ffd_init' ) ) {
		/* translators: 1: ffd_get_listing 2: ffd_init */
		ffd_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s action.', 'ffd-integration' ), 'ffd_get_listing', 'ffd_init' ), '2.5' );
		return false;
	}
	if ( ! empty( $deprecated ) ) {
		ffd_deprecated_argument( 'args', '3.0', 'Passing args to ffd_get_listing is deprecated. If you need to force a type, construct the listing class directly.' );
	}
	return FFD()->listing_factory->get_listing( $the_listing, $deprecated );
}

/**
 * Returns whether or not SKUS are enabled.
 *
 * @return bool
 */
function ffd_listing_sku_enabled() {
	return apply_filters( 'ffd_listing_sku_enabled', true );
}

/**
 * Returns whether or not listing weights are enabled.
 *
 * @return bool
 */
function ffd_listing_weight_enabled() {
	return apply_filters( 'ffd_listing_weight_enabled', true );
}

/**
 * Returns whether or not listing dimensions (HxWxD) are enabled.
 *
 * @return bool
 */
function ffd_listing_dimensions_enabled() {
	return apply_filters( 'ffd_listing_dimensions_enabled', true );
}

/**
 * Clear all transients cache for listing data.
 *
 * @param int $post_id (default: 0).
 */
function ffd_delete_listing_transients( $post_id = 0 ) {
	// Core transients.
	$transients_to_clear = array(
		'ffd_listings_sold',
		'ffd_featured_listings',
		'ffd_outofstock_count',
		'ffd_low_stock_count',
		'ffd_count_comments',
	);

	// Transient names that include an ID.
	$post_transient_names = array(
		'ffd_listing_children_',
		'ffd_var_prices_',
		'ffd_related_',
		'ffd_child_has_weight_',
		'ffd_child_has_dimensions_',
	);

	if ( $post_id > 0 ) {
		foreach ( $post_transient_names as $transient ) {
			$transients_to_clear[] = $transient . $post_id;
		}

		// Does this listing have a parent?
		$listing = ffd_get_listing( $post_id );

		if ( $listing ) {
			if ( $listing->get_parent_id() > 0 ) {
				ffd_delete_listing_transients( $listing->get_parent_id() );
			}

			if ( 'variable' === $listing->get_type() ) {
				wp_cache_delete(
					FFD_Cache_Helper::get_cache_prefix( 'listings' ) . 'listing_variation_attributes_' . $listing->get_id(),
					'listings'
				);
			}
		}
	}

	// Delete transients.
	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Increments the transient version to invalidate cache.
	FFD_Cache_Helper::get_transient_version( 'listing', true );

	do_action( 'ffd_delete_listing_transients', $post_id );
}

/**
 * Function that returns an array containing the IDs of the listings that are sold.
 *
 * @since 2.0
 * @return array
 */
function ffd_get_listing_ids_sold() {
	// Load from cache.
	$listing_ids_sold = get_transient( 'ffd_listings_sold' );

	// Valid cache found.
	if ( false !== $listing_ids_sold ) {
		return $listing_ids_sold;
	}

	$data_office          = FFD_Data_Office::load( 'listing' );
	$sold_listings    = $data_store->get_sold_listings();
	$listing_ids_sold = wp_parse_id_list( array_merge( wp_list_pluck( $sold_listings, 'id' ), array_diff( wp_list_pluck( $sold_listings, 'parent_id' ), array( 0 ) ) ) );

	set_transient( 'ffd_listings_sold', $listing_ids_sold, DAY_IN_SECONDS * 30 );

	return $listing_ids_sold;
}

/**
 * Function that returns an array containing the IDs of the featured listings.
 *
 * @since 2.1
 * @return array
 */
function ffd_get_featured_listing_ids() {
	// Load from cache.
	$featured_listing_ids = get_transient( 'ffd_featured_listings' );

	// Valid cache found.
	if ( false !== $featured_listing_ids ) {
		return $featured_listing_ids;
	}

	$data_office           = FFD_Data_Office::load( 'listing' );
	$featured             = $data_store->get_featured_listing_ids();
	$listing_ids          = array_keys( $featured );
	$parent_ids           = array_values( array_filter( $featured ) );
	$featured_listing_ids = array_unique( array_merge( $listing_ids, $parent_ids ) );

	set_transient( 'ffd_featured_listings', $featured_listing_ids, DAY_IN_SECONDS * 30 );

	return $featured_listing_ids;
}

/**
 * Filter to allow listing_cat in the permalinks for listings.
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post WP_Post object.
 * @return string
 */
function ffd_listing_post_type_link( $permalink, $post ) {
	// Abort if post is not a listing.
	if ( 'listing' !== $post->post_type ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, 'listing_cat' );

	if ( ! empty( $terms ) ) {
		if ( function_exists( 'wp_list_sort' ) ) {
			$terms = wp_list_sort( $terms, 'term_id', 'ASC' );
		} else {
			usort( $terms, '_usort_terms_by_ID' );
		}
		$category_object = apply_filters( 'ffd_listing_post_type_link_listing_cat', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'listing_cat' );
		$listing_cat     = $category_object->slug;

		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'listing_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'listing_cat' );
				$listing_cat     = $ancestor_object->slug . '/' . $listing_cat;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
		$listing_cat = _x( 'uncategorized', 'slug', 'ffd-integration' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%listing_cat%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$listing_cat,
		$listing_cat,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
add_filter( 'post_type_link', 'ffd_listing_post_type_link', 10, 2 );


/**
 * Get the placeholder image URL for listings etc.
 *
 * @param string $size Image size.
 * @return string
 */
function ffd_placeholder_img_src( $size = 'ffd_thumbnail' ) {
	$src               = FFD()->plugin_url() . '/assets/images/placeholder.png';
	$placeholder_image = get_option( 'ffd_placeholder_image', 0 );

	if ( ! empty( $placeholder_image ) ) {
		if ( is_numeric( $placeholder_image ) ) {
			$image      = wp_get_attachment_image_src( $placeholder_image, $size );

			if ( ! empty( $image[0] ) ) {
				$src = $image[0];
			}
		} else {
			$src = $placeholder_image;
		}
	}

	return apply_filters( 'ffd_placeholder_img_src', $src );
}

/**
 * Get the placeholder image.
 *
 * @param string $size Image size.
 * @return string
 */
function ffd_placeholder_img( $size = 'ffd_thumbnail' ) {
	$dimensions = ffd_get_image_size( $size );

	return apply_filters( 'ffd_placeholder_img', '<img src="' . ffd_placeholder_img_src( $size ) . '" alt="' . esc_attr__( 'Placeholder', 'ffd-integration' ) . '" width="' . esc_attr( $dimensions['width'] ) . '" class="ffd-placeholder wp-post-image" height="' . esc_attr( $dimensions['height'] ) . '" />', $size, $dimensions );
}

/**
 * Variation Formatting.
 *
 * Gets a formatted version of variation data or item meta.
 *
 * @param array|FFD_Listing_Variation $variation Variation object.
 * @param bool                       $flat Should this be a flat list or HTML list? (default: false).
 * @param bool                       $include_names include attribute names/labels in the list.
 * @param bool                       $skip_attributes_in_name Do not list attributes already part of the variation name.
 * @return string
 */
function ffd_get_formatted_variation( $variation, $flat = false, $include_names = true, $skip_attributes_in_name = false ) {
	$return = '';

	if ( is_a( $variation, 'FFD_Listing_Variation' ) ) {
		$variation_attributes = $variation->get_attributes();
		$listing              = $variation;
		$variation_name       = $variation->get_name();
	} else {
		$listing        = false;
		$variation_name = '';
		// Remove attribute_ prefix from names.
		$variation_attributes = array();
		if ( is_array( $variation ) ) {
			foreach ( $variation as $key => $value ) {
				$variation_attributes[ str_replace( 'attribute_', '', $key ) ] = $value;
			}
		}
	}

	$list_type = $include_names ? 'dl' : 'ul';

	if ( is_array( $variation_attributes ) ) {

		if ( ! $flat ) {
			$return = '<' . $list_type . ' class="variation">';
		}

		$variation_list = array();

		foreach ( $variation_attributes as $name => $value ) {
			// If this is a term slug, get the term's nice name.
			if ( taxonomy_exists( $name ) ) {
				$term = get_term_by( 'slug', $value, $name );
				if ( ! is_wp_error( $term ) && ! empty( $term->name ) ) {
					$value = $term->name;
				}
			}

			// Do not list attributes already part of the variation name.
			if ( '' === $value || ( $skip_attributes_in_name && ffd_is_attribute_in_listing_name( $value, $variation_name ) ) ) {
				continue;
			}

			if ( $include_names ) {
				if ( $flat ) {
					$variation_list[] = ffd_attribute_label( $name, $listing ) . ': ' . rawurldecode( $value );
				} else {
					$variation_list[] = '<dt>' . ffd_attribute_label( $name, $listing ) . ':</dt><dd>' . rawurldecode( $value ) . '</dd>';
				}
			} else {
				if ( $flat ) {
					$variation_list[] = rawurldecode( $value );
				} else {
					$variation_list[] = '<li>' . rawurldecode( $value ) . '</li>';
				}
			}
		}

		if ( $flat ) {
			$return .= implode( ', ', $variation_list );
		} else {
			$return .= implode( '', $variation_list );
		}

		if ( ! $flat ) {
			$return .= '</' . $list_type . '>';
		}
	}
	return $return;
}

/**
 * Function which handles the start and end of scheduled sales via cron.
 */
function ffd_scheduled_sales() {
	$data_office = FFD_Data_Office::load( 'listing' );

	// Sales which are due to start.
	$listing_ids = $data_store->get_starting_sales();
	if ( $listing_ids ) {
		do_action( 'ffd_before_listings_starting_sales', $listing_ids );
		foreach ( $listing_ids as $listing_id ) {
			$listing = ffd_get_listing( $listing_id );

			if ( $listing ) {
				$sale_price = $listing->get_sale_price();

				if ( $sale_price ) {
					$listing->set_price( $sale_price );
					$listing->set_date_sold_from( '' );
				} else {
					$listing->set_date_sold_to( '' );
					$listing->set_date_sold_from( '' );
				}

				$listing->save();
			}
		}
		do_action( 'ffd_after_listings_starting_sales', $listing_ids );

		delete_transient( 'ffd_listings_sold' );
	}

	// Sales which are due to end.
	$listing_ids = $data_store->get_ending_sales();
	if ( $listing_ids ) {
		do_action( 'ffd_before_listings_ending_sales', $listing_ids );
		foreach ( $listing_ids as $listing_id ) {
			$listing = ffd_get_listing( $listing_id );

			if ( $listing ) {
				$regular_price = $listing->get_regular_price();
				$listing->set_price( $regular_price );
				$listing->set_sale_price( '' );
				$listing->set_date_sold_to( '' );
				$listing->set_date_sold_from( '' );
				$listing->save();
			}
		}
		do_action( 'ffd_after_listings_ending_sales', $listing_ids );

		FFD_Cache_Helper::get_transient_version( 'listing', true );
		delete_transient( 'ffd_listings_sold' );
	}
}
add_action( 'ffd_scheduled_sales', 'ffd_scheduled_sales' );

/**
 * Get attachment image attributes.
 *
 * @param array $attr Image attributes.
 * @return array
 */
function ffd_get_attachment_image_attributes( $attr ) {
	if ( isset( $attr['src'] ) && strstr( $attr['src'], 'ffd_uploads/' ) ) {
		$attr['src'] = ffd_placeholder_img_src();

		if ( isset( $attr['srcset'] ) ) {
			$attr['srcset'] = '';
		}
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'ffd_get_attachment_image_attributes' );


/**
 * Prepare attachment for JavaScript.
 *
 * @param array $response JS version of a attachment post object.
 * @return array
 */
function ffd_prepare_attachment_for_js( $response ) {

	if ( isset( $response['url'] ) && strstr( $response['url'], 'ffd_uploads/' ) ) {
		$response['full']['url'] = ffd_placeholder_img_src();
		if ( isset( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $value ) {
				$response['sizes'][ $size ]['url'] = ffd_placeholder_img_src();
			}
		}
	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'ffd_prepare_attachment_for_js' );

/**
 * Track listing views.
 */
function ffd_track_listing_view() {
	if ( ! is_singular( 'listing' ) || ! is_active_widget( false, false, 'ffd_recently_viewed_listings', true ) ) {
		return;
	}

	global $post;

	if ( empty( $_COOKIE['ffd_recently_viewed'] ) ) { // @codingStandardsIgnoreLine.
		$viewed_listings = array();
	} else {
		$viewed_listings = wp_parse_id_list( (array) explode( '|', wp_unslash( $_COOKIE['ffd_recently_viewed'] ) ) ); // @codingStandardsIgnoreLine.
	}

	// Unset if already in viewed listings list.
	$keys = array_flip( $viewed_listings );

	if ( isset( $keys[ $post->ID ] ) ) {
		unset( $viewed_listings[ $keys[ $post->ID ] ] );
	}

	$viewed_listings[] = $post->ID;

	if ( count( $viewed_listings ) > 15 ) {
		array_shift( $viewed_listings );
	}

	// Store for session only.
	ffd_setcookie( 'ffd_recently_viewed', implode( '|', $viewed_listings ) );
}

add_action( 'template_redirect', 'ffd_track_listing_view', 20 );

/**
 * Get listing types.
 *
 * @since 2.2
 * @return array
 */
function ffd_get_listing_types() {
	return (array) apply_filters(
		'listing_type_selector', array(
			'simple'   => __( 'Simple listing', 'ffd-integration' ),
			'grouped'  => __( 'Grouped listing', 'ffd-integration' ),
			'external' => __( 'External/Affiliate listing', 'ffd-integration' ),
			'variable' => __( 'Variable listing', 'ffd-integration' ),
		)
	);
}

/**
 * Check if listing sku is unique.
 *
 * @since 2.2
 * @param int    $listing_id Listing ID.
 * @param string $sku Listing SKU.
 * @return bool
 */
function ffd_listing_has_unique_sku( $listing_id, $sku ) {
	$data_office = FFD_Data_Office::load( 'listing' );
	$sku_found  = $data_store->is_existing_sku( $listing_id, $sku );

	if ( apply_filters( 'ffd_listing_has_unique_sku', $sku_found, $listing_id, $sku ) ) {
		return false;
	}

	return true;
}

/**
 * Force a unique SKU.
 *
 * @since  3.0.0
 * @param  integer $listing_id Listing ID.
 */
function ffd_listing_force_unique_sku( $listing_id ) {
	$listing     = ffd_get_listing( $listing_id );
	$current_sku = $listing ? $listing->get_sku( 'edit' ) : '';

	if ( $current_sku ) {
		try {
			$new_sku = ffd_listing_generate_unique_sku( $listing_id, $current_sku );

			if ( $current_sku !== $new_sku ) {
				$listing->set_sku( $new_sku );
				$listing->save();
			}
		} catch ( Exception $e ) {} // @codingStandardsIgnoreLine.
	}
}

/**
 * Recursively appends a suffix until a unique SKU is found.
 *
 * @since  3.0.0
 * @param  integer $listing_id Listing ID.
 * @param  string  $sku Listing SKU.
 * @param  integer $index An optional index that can be added to the listing SKU.
 * @return string
 */
function ffd_listing_generate_unique_sku( $listing_id, $sku, $index = 0 ) {
	$generated_sku = 0 < $index ? $sku . '-' . $index : $sku;

	if ( ! ffd_listing_has_unique_sku( $listing_id, $generated_sku ) ) {
		$generated_sku = ffd_listing_generate_unique_sku( $listing_id, $sku, ( $index + 1 ) );
	}

	return $generated_sku;
}

/**
 * Get listing ID by SKU.
 *
 * @since  2.3.0
 * @param  string $sku Listing SKU.
 * @return int
 */
function ffd_get_listing_id_by_sku( $sku ) {
	$data_office = FFD_Data_Office::load( 'listing' );
	return $data_store->get_listing_id_by_sku( $sku );
}

/**
 * Get attibutes/data for an individual variation from the database and maintain it's integrity.
 *
 * @since  2.4.0
 * @param  int $variation_id Variation ID.
 * @return array
 */
function ffd_get_listing_variation_attributes( $variation_id ) {
	// Build variation data from meta.
	$all_meta                = get_post_meta( $variation_id );
	$parent_id               = wp_get_post_parent_id( $variation_id );
	$parent_attributes       = array_filter( (array) get_post_meta( $parent_id, '_listing_attributes', true ) );
	$found_parent_attributes = array();
	$variation_attributes    = array();

	// Compare to parent variable listing attributes and ensure they match.
	foreach ( $parent_attributes as $attribute_name => $options ) {
		if ( ! empty( $options['is_variation'] ) ) {
			$attribute                 = 'attribute_' . sanitize_title( $attribute_name );
			$found_parent_attributes[] = $attribute;
			if ( ! array_key_exists( $attribute, $variation_attributes ) ) {
				$variation_attributes[ $attribute ] = ''; // Add it - 'any' will be asumed.
			}
		}
	}

	// Get the variation attributes from meta.
	foreach ( $all_meta as $name => $value ) {
		// Only look at valid attribute meta, and also compare variation level attributes and remove any which do not exist at parent level.
		if ( 0 !== strpos( $name, 'attribute_' ) || ! in_array( $name, $found_parent_attributes, true ) ) {
			unset( $variation_attributes[ $name ] );
			continue;
		}
		/**
		 * Pre 2.4 handling where 'slugs' were saved instead of the full text attribute.
		 * Attempt to get full version of the text attribute from the parent.
		 */
		if ( sanitize_title( $value[0] ) === $value[0] && version_compare( get_post_meta( $parent_id, '_listing_version', true ), '2.4.0', '<' ) ) {
			foreach ( $parent_attributes as $attribute ) {
				if ( 'attribute_' . sanitize_title( $attribute['name'] ) !== $name ) {
					continue;
				}
				$text_attributes = ffd_get_text_attributes( $attribute['value'] );

				foreach ( $text_attributes as $text_attribute ) {
					if ( sanitize_title( $text_attribute ) === $value[0] ) {
						$value[0] = $text_attribute;
						break;
					}
				}
			}
		}

		$variation_attributes[ $name ] = $value[0];
	}

	return $variation_attributes;
}

/**
 * Get all listing cats for a listing by ID, including hierarchy
 *
 * @since  2.5.0
 * @param  int $listing_id Listing ID.
 * @return array
 */
function ffd_get_listing_cat_ids( $listing_id ) {
	$listing_cats = ffd_get_listing_term_ids( $listing_id, 'listing_cat' );

	foreach ( $listing_cats as $listing_cat ) {
		$listing_cats = array_merge( $listing_cats, get_ancestors( $listing_cat, 'listing_cat' ) );
	}

	return $listing_cats;
}

/**
 * Gets data about an attachment, such as alt text and captions.
 *
 * @since 2.6.0
 *
 * @param int|null        $attachment_id Attachment ID.
 * @param FFD_Listing|bool $listing FFD_Listing object.
 *
 * @return array
 */
function ffd_get_listing_attachment_props( $attachment_id = null, $listing = false ) {
	$props      = array(
		'title'   => '',
		'caption' => '',
		'url'     => '',
		'alt'     => '',
		'src'     => '',
		'srcset'  => false,
		'sizes'   => false,
	);
	$attachment = get_post( $attachment_id );

	if ( $attachment ) {
		$props['title']   = wp_strip_all_tags( $attachment->post_title );
		$props['caption'] = wp_strip_all_tags( $attachment->post_excerpt );
		$props['url']     = wp_get_attachment_url( $attachment_id );

		// Alt text.
		$alt_text = array( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ), $props['caption'], wp_strip_all_tags( $attachment->post_title ) );

		if ( $listing && $listing instanceof FFD_Listing ) {
			$alt_text[] = wp_strip_all_tags( get_the_title( $listing->get_id() ) );
		}

		$alt_text     = array_filter( $alt_text );
		$props['alt'] = isset( $alt_text[0] ) ? $alt_text[0] : '';

		// Large version.
		$full_size           = apply_filters( 'ffd_gallery_full_size', apply_filters( 'ffd_listing_thumbnails_large_size', 'full' ) );
		$src                 = wp_get_attachment_image_src( $attachment_id, $full_size );
		$props['full_src']   = $src[0];
		$props['full_src_w'] = $src[1];
		$props['full_src_h'] = $src[2];

		// Gallery thumbnail.
		$gallery_thumbnail                = ffd_get_image_size( 'gallery_thumbnail' );
		$gallery_thumbnail_size           = apply_filters( 'ffd_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
		$src                              = wp_get_attachment_image_src( $attachment_id, $gallery_thumbnail_size );
		$props['gallery_thumbnail_src']   = $src[0];
		$props['gallery_thumbnail_src_w'] = $src[1];
		$props['gallery_thumbnail_src_h'] = $src[2];

		// Thumbnail version.
		$thumbnail_size       = apply_filters( 'ffd_thumbnail_size', 'ffd_thumbnail' );
		$src                  = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
		$props['thumb_src']   = $src[0];
		$props['thumb_src_w'] = $src[1];
		$props['thumb_src_h'] = $src[2];

		// Image source.
		$image_size      = apply_filters( 'ffd_gallery_image_size', 'ffd_single' );
		$src             = wp_get_attachment_image_src( $attachment_id, $image_size );
		$props['src']    = $src[0];
		$props['src_w']  = $src[1];
		$props['src_h']  = $src[2];
		$props['srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, $image_size ) : false;
		$props['sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, $image_size ) : false;
	}
	return $props;
}

/**
 * Get listing visibility options.
 *
 * @since 3.0.0
 * @return array
 */
function ffd_get_listing_visibility_options() {
	return apply_filters(
		'ffd_listing_visibility_options', array(
			'visible' => __( 'Shop and search results', 'ffd-integration' ),
			'catalog' => __( 'Shop only', 'ffd-integration' ),
			'search'  => __( 'Search results only', 'ffd-integration' ),
			'hidden'  => __( 'Hidden', 'ffd-integration' ),
		)
	);
}

/**
 * Get min/max price meta query args.
 *
 * @since 3.0.0
 * @param array $args Min price and max price arguments.
 * @return array
 */
function ffd_get_min_max_price_meta_query( $args ) {
	$min = isset( $args['min_price'] ) ? floatval( $args['min_price'] ) : 0;
	$max = isset( $args['max_price'] ) ? floatval( $args['max_price'] ) : 9999999999;

	/**
	 * Adjust if the office taxes are not displayed how they are stored.
	 * Kicks in when prices excluding tax are displayed including tax.
	 */
	if ( ffd_tax_enabled() && 'incl' === get_option( 'ffd_tax_display_shop' ) && ! ffd_prices_include_tax() ) {
		$tax_classes = array_merge( array( '' ), FFD_Tax::get_tax_classes() );
		$class_min   = $min;
		$class_max   = $max;

		foreach ( $tax_classes as $tax_class ) {
			$tax_rates = FFD_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$class_min = $min + FFD_Tax::get_tax_total( FFD_Tax::calc_exclusive_tax( $min, $tax_rates ) );
				$class_max = $max - FFD_Tax::get_tax_total( FFD_Tax::calc_exclusive_tax( $max, $tax_rates ) );
			}
		}

		$min = $class_min;
		$max = $class_max;
	}

	return array(
		'key'     => '_price',
		'value'   => array( $min, $max ),
		'compare' => 'BETWEEN',
		'type'    => 'DECIMAL(10,' . ffd_get_price_decimals() . ')',
	);
}

/**
 * Get listing tax class options.
 *
 * @since 3.0.0
 * @return array
 */
function ffd_get_listing_tax_class_options() {
	$tax_classes           = FFD_Tax::get_tax_classes();
	$tax_class_options     = array();
	$tax_class_options[''] = __( 'Standard', 'ffd-integration' );

	if ( ! empty( $tax_classes ) ) {
		foreach ( $tax_classes as $class ) {
			$tax_class_options[ sanitize_title( $class ) ] = $class;
		}
	}
	return $tax_class_options;
}

/**
 * Get stock status options.
 *
 * @since 3.0.0
 * @return array
 */
function ffd_get_listing_stock_status_options() {
	return array(
		'instock'     => __( 'In stock', 'ffd-integration' ),
		'outofstock'  => __( 'Out of stock', 'ffd-integration' ),
		'onbackorder' => __( 'On backorder', 'ffd-integration' ),
	);
}

/**
 * Get backorder options.
 *
 * @since 3.0.0
 * @return array
 */
function ffd_get_listing_backorder_options() {
	return array(
		'no'     => __( 'Do not allow', 'ffd-integration' ),
		'notify' => __( 'Allow, but notify customer', 'ffd-integration' ),
		'yes'    => __( 'Allow', 'ffd-integration' ),
	);
}

/**
 * Get related listings based on listing category and tags.
 *
 * @since  3.0.0
 * @param  int   $listing_id  Listing ID.
 * @param  int   $limit       Limit of results.
 * @param  array $exclude_ids Exclude IDs from the results.
 * @return array
 */
function ffd_get_related_listings( $listing_id, $limit = 5, $exclude_ids = array() ) {

	$listing_id     = absint( $listing_id );
	$limit          = $limit >= -1 ? $limit : 5;
	$exclude_ids    = array_merge( array( 0, $listing_id ), $exclude_ids );
	$transient_name = 'ffd_related_' . $listing_id;
	$query_args     = http_build_query(
		array(
			'limit'       => $limit,
			'exclude_ids' => $exclude_ids,
		)
	);

	$transient     = get_transient( $transient_name );
	$related_posts = $transient && isset( $transient[ $query_args ] ) ? $transient[ $query_args ] : false;

	// We want to query related posts if they are not cached, or we don't have enough.
	if ( false === $related_posts || count( $related_posts ) < $limit ) {

		$cats_array = apply_filters( 'ffd_listing_related_posts_relate_by_category', true, $listing_id ) ? apply_filters( 'ffd_get_related_listing_cat_terms', ffd_get_listing_term_ids( $listing_id, 'listing_cat' ), $listing_id ) : array();
		$tags_array = apply_filters( 'ffd_listing_related_posts_relate_by_tag', true, $listing_id ) ? apply_filters( 'ffd_get_related_listing_tag_terms', ffd_get_listing_term_ids( $listing_id, 'listing_tag' ), $listing_id ) : array();

		// Don't bother if none are set, unless ffd_listing_related_posts_force_display is set to true in which case all listings are related.
		if ( empty( $cats_array ) && empty( $tags_array ) && ! apply_filters( 'ffd_listing_related_posts_force_display', false, $listing_id ) ) {
			$related_posts = array();
		} else {
			$data_office    = FFD_Data_Office::load( 'listing' );
			$related_posts = $data_store->get_related_listings( $cats_array, $tags_array, $exclude_ids, $limit + 10, $listing_id );
		}

		if ( $transient ) {
			$transient[ $query_args ] = $related_posts;
		} else {
			$transient = array( $query_args => $related_posts );
		}

		set_transient( $transient_name, $transient, DAY_IN_SECONDS );
	}

	$related_posts = apply_filters(
		'ffd_related_listings', $related_posts, $listing_id, array(
			'limit'        => $limit,
			'excluded_ids' => $exclude_ids,
		)
	);

	shuffle( $related_posts );

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Retrieves listing term ids for a taxonomy.
 *
 * @since  3.0.0
 * @param  int    $listing_id Listing ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @return array
 */
function ffd_get_listing_term_ids( $listing_id, $taxonomy ) {
	$terms = get_the_terms( $listing_id, $taxonomy );
	return ( empty( $terms ) || is_wp_error( $terms ) ) ? array() : wp_list_pluck( $terms, 'term_id' );
}

/**
 * For a given listing, and optionally price/qty, work out the price with tax included, based on office settings.
 *
 * @since  3.0.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @param  array      $args Optional arguments to pass listing quantity and price.
 * @return float
 */
function ffd_get_price_including_tax( $listing, $args = array() ) {
	$args = wp_parse_args(
		$args, array(
			'qty'   => '',
			'price' => '',
		)
	);

	$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $listing->get_price();
	$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

	if ( '' === $price ) {
		return '';
	} elseif ( empty( $qty ) ) {
		return 0.0;
	}

	$line_price   = $price * $qty;
	$return_price = $line_price;

	if ( $listing->is_taxable() ) {
		if ( ! ffd_prices_include_tax() ) {
			$tax_rates    = FFD_Tax::get_rates( $listing->get_tax_class() );
			$taxes        = FFD_Tax::calc_tax( $line_price, $tax_rates, false );
			$tax_amount   = FFD_Tax::get_tax_total( $taxes );
			$return_price = round( $line_price + $tax_amount, ffd_get_price_decimals() );
		} else {
			$tax_rates      = FFD_Tax::get_rates( $listing->get_tax_class() );
			$base_tax_rates = FFD_Tax::get_base_tax_rates( $listing->get_tax_class( 'unfiltered' ) );

			/**
			 * If the customer is excempt from VAT, remove the taxes here.
			 * Either remove the base or the user taxes depending on ffd_adjust_non_base_location_prices setting.
			 */
			if ( ! empty( FFD()->customer ) && FFD()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
				$remove_taxes = apply_filters( 'ffd_adjust_non_base_location_prices', true ) ? FFD_Tax::calc_tax( $line_price, $base_tax_rates, true ) : FFD_Tax::calc_tax( $line_price, $tax_rates, true );
				$remove_tax   = array_sum( $remove_taxes );
				$return_price = round( $line_price - $remove_tax, ffd_get_price_decimals() );

				/**
			 * The ffd_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
			 * e.g. If a listing costs 10 including tax, all users will pay 10 regardless of location and taxes.
			 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
			 */
			} elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'ffd_adjust_non_base_location_prices', true ) ) {
				$base_taxes   = FFD_Tax::calc_tax( $line_price, $base_tax_rates, true );
				$modded_taxes = FFD_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, false );
				$return_price = round( $line_price - array_sum( $base_taxes ) + ffd_round_tax_total( array_sum( $modded_taxes ), ffd_get_price_decimals() ), ffd_get_price_decimals() );
			}
		}
	}
	return apply_filters( 'ffd_get_price_including_tax', $return_price, $qty, $listing );
}

/**
 * For a given listing, and optionally price/qty, work out the price with tax excluded, based on office settings.
 *
 * @since  3.0.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @param  array      $args Optional arguments to pass listing quantity and price.
 * @return float
 */
function ffd_get_price_excluding_tax( $listing, $args = array() ) {
	$args = wp_parse_args(
		$args, array(
			'qty'   => '',
			'price' => '',
		)
	);

	$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $listing->get_price();
	$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

	if ( '' === $price ) {
		return '';
	} elseif ( empty( $qty ) ) {
		return 0.0;
	}

	$line_price = $price * $qty;

	if ( $listing->is_taxable() && ffd_prices_include_tax() ) {
		$tax_rates      = FFD_Tax::get_rates( $listing->get_tax_class() );
		$base_tax_rates = FFD_Tax::get_base_tax_rates( $listing->get_tax_class( 'unfiltered' ) );
		$remove_taxes   = apply_filters( 'ffd_adjust_non_base_location_prices', true ) ? FFD_Tax::calc_tax( $line_price, $base_tax_rates, true ) : FFD_Tax::calc_tax( $line_price, $tax_rates, true );
		$return_price   = $line_price - array_sum( $remove_taxes );
	} else {
		$return_price = $line_price;
	}

	return apply_filters( 'ffd_get_price_excluding_tax', $return_price, $qty, $listing );
}

/**
 * Returns the price including or excluding tax, based on the 'ffd_tax_display_shop' setting.
 *
 * @since  3.0.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @param  array      $args Optional arguments to pass listing quantity and price.
 * @return float
 */
function ffd_get_price_to_display( $listing, $args = array() ) {
	$args = wp_parse_args(
		$args, array(
			'qty'   => 1,
			'price' => $listing->get_price(),
		)
	);

	$price = $args['price'];
	$qty   = $args['qty'];

	return 'incl' === get_option( 'ffd_tax_display_shop' ) ?
		ffd_get_price_including_tax(
			$listing, array(
				'qty'   => $qty,
				'price' => $price,
			)
		) :
		ffd_get_price_excluding_tax(
			$listing, array(
				'qty'   => $qty,
				'price' => $price,
			)
		);
}

/**
 * Returns the listing categories in a list.
 *
 * @param int    $listing_id Listing ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function ffd_get_listing_category_list( $listing_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $listing_id, 'listing_cat', $before, $sep, $after );
}

/**
 * Returns the listing tags in a list.
 *
 * @param int    $listing_id Listing ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function ffd_get_listing_tag_list( $listing_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $listing_id, 'listing_tag', $before, $sep, $after );
}

/**
 * Callback for array filter to get visible only.
 *
 * @since  3.0.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @return bool
 */
function ffd_listings_array_filter_visible( $listing ) {
	return $listing && is_a( $listing, 'FFD_Listing' ) && $listing->is_visible();
}

/**
 * Callback for array filter to get visible grouped listings only.
 *
 * @since  3.1.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @return bool
 */
function ffd_listings_array_filter_visible_grouped( $listing ) {
	return $listing && is_a( $listing, 'FFD_Listing' ) && ( 'publish' === $listing->get_status() || current_user_can( 'edit_listing', $listing->get_id() ) );
}

/**
 * Callback for array filter to get listings the user can edit only.
 *
 * @since  3.0.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @return bool
 */
function ffd_listings_array_filter_editable( $listing ) {
	return $listing && is_a( $listing, 'FFD_Listing' ) && current_user_can( 'edit_listing', $listing->get_id() );
}

/**
 * Callback for array filter to get listings the user can view only.
 *
 * @since  3.4.0
 * @param  FFD_Listing $listing FFD_Listing object.
 * @return bool
 */
function ffd_listings_array_filter_readable( $listing ) {
	return $listing && is_a( $listing, 'FFD_Listing' ) && current_user_can( 'read_listing', $listing->get_id() );
}

/**
 * Sort an array of listings by a value.
 *
 * @since  3.0.0
 *
 * @param array  $listings List of listings to be ordered.
 * @param string $orderby Optional order criteria.
 * @param string $order Ascending or descending order.
 *
 * @return array
 */
function ffd_listings_array_orderby( $listings, $orderby = 'date', $order = 'desc' ) {
	$orderby = strtolower( $orderby );
	$order   = strtolower( $order );
	switch ( $orderby ) {
		case 'title':
		case 'id':
		case 'date':
		case 'modified':
		case 'menu_order':
		case 'price':
			usort( $listings, 'ffd_listings_array_orderby_' . $orderby );
			break;
		default:
			shuffle( $listings );
			break;
	}
	if ( 'desc' === $order ) {
		$listings = array_reverse( $listings );
	}
	return $listings;
}

/**
 * Sort by title.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_title( $a, $b ) {
	return strcasecmp( $a->get_name(), $b->get_name() );
}

/**
 * Sort by id.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_id( $a, $b ) {
	if ( $a->get_id() === $b->get_id() ) {
		return 0;
	}
	return ( $a->get_id() < $b->get_id() ) ? -1 : 1;
}

/**
 * Sort by date.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_date( $a, $b ) {
	if ( $a->get_date_created() === $b->get_date_created() ) {
		return 0;
	}
	return ( $a->get_date_created() < $b->get_date_created() ) ? -1 : 1;
}

/**
 * Sort by modified.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_modified( $a, $b ) {
	if ( $a->get_date_modified() === $b->get_date_modified() ) {
		return 0;
	}
	return ( $a->get_date_modified() < $b->get_date_modified() ) ? -1 : 1;
}

/**
 * Sort by menu order.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_menu_order( $a, $b ) {
	if ( $a->get_menu_order() === $b->get_menu_order() ) {
		return 0;
	}
	return ( $a->get_menu_order() < $b->get_menu_order() ) ? -1 : 1;
}

/**
 * Sort by price low to high.
 *
 * @since  3.0.0
 * @param  FFD_Listing $a First FFD_Listing object.
 * @param  FFD_Listing $b Second FFD_Listing object.
 * @return int
 */
function ffd_listings_array_orderby_price( $a, $b ) {
	if ( $a->get_price() === $b->get_price() ) {
		return 0;
	}
	return ( $a->get_price() < $b->get_price() ) ? -1 : 1;
}

/**
 * Queue a listing for syncing at the end of the request.
 *
 * @param int $listing_id Listing ID.
 */
function ffd_deferred_listing_sync( $listing_id ) {
	global $ffd_deferred_listing_sync;

	if ( empty( $ffd_deferred_listing_sync ) ) {
		$ffd_deferred_listing_sync = array();
	}

	$ffd_deferred_listing_sync[] = $listing_id;
}