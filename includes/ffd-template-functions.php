<?php


/**
 * Sets up the ffd_loop global from the passed args or from the main query.
 *
 * @since 1.0.0
 * @param array $args Args to pass into the global.
 */
function ffd_setup_loop( $args = array() ) {
	$default_args = array(
		'loop'         => 0,
		'columns'      => ffd_get_default_products_per_row(),
		'name'         => '',
		'is_shortcode' => false,
		'is_paginated' => true,
		'is_search'    => false,
		'is_filtered'  => false,
		'total'        => 0,
		'total_pages'  => 0,
		'per_page'     => 0,
		'current_page' => 1,
	);

	// If this is a main FFD query, use global args as defaults.
	if ( $GLOBALS['wp_query']->get( 'ffd_query' ) ) {
		$default_args = array_merge( $default_args, array(
			'is_search'    => $GLOBALS['wp_query']->is_search(),
			'is_filtered'  => is_filtered(),
			'total'        => $GLOBALS['wp_query']->found_posts,
			'total_pages'  => $GLOBALS['wp_query']->max_num_pages,
			'per_page'     => $GLOBALS['wp_query']->get( 'posts_per_page' ),
			'current_page' => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
		) );
	}

	// Merge any existing values.
	if ( isset( $GLOBALS['ffd_loop'] ) ) {
		$default_args = array_merge( $default_args, $GLOBALS['ffd_loop'] );
	}

	$GLOBALS['ffd_loop'] = wp_parse_args( $args, $default_args );
}
add_action( 'ffd_before_listing_loop', 'ffd_setup_loop' );

/**
 * Resets the ffd_loop global.
 *
 * @since 1.0.0
 */
function ffd_reset_loop() {
	unset( $GLOBALS['ffd_loop'] );
}
add_action( 'ffd_after_listing_loop', 'ffd_reset_loop', 999 );

/**
 * Gets a property from the ffd_loop global.
 *
 * @since 1.0.0
 * @param string $prop Prop to get.
 * @param string $default Default if the prop does not exist.
 * @return mixed
 */
function ffd_get_loop_prop( $prop, $default = '' ) {
	ffd_setup_loop(); // Ensure shop loop is setup.

	return isset( $GLOBALS['ffd_loop'], $GLOBALS['ffd_loop'][ $prop ] ) ? $GLOBALS['ffd_loop'][ $prop ] : $default;
}




/**
 * Sets a property in the ffd_loop global.
 *
 * @since 1.0.0
 * @param string $prop Prop to set.
 * @param string $value Value to set.
 */
function ffd_set_loop_prop( $prop, $value = '' ) {
	if ( ! isset( $GLOBALS['ffd_loop'] ) ) {
		ffd_setup_loop();
	}
	$GLOBALS['ffd_loop'][ $prop ] = $value;
}



if ( ! function_exists( 'ffd_listing_loop_start' ) ) {

	/**
	 * Output the start of a listing loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_loop_start( $echo = true ) {
		ob_start();

		ffd_set_loop_prop( 'loop', 0 );

		ffd_get_template( 'loop/loop-start.php' );

		$loop_start = apply_filters( 'ffd_listing_loop_start', ob_get_clean() );

		if ( $echo ) {
			echo $loop_start; // WPCS: XSS ok.
		} else {
			return $loop_start;
		}
	}
}

if ( ! function_exists( 'ffd_listing_loop_end' ) ) {

	/**
	 * Output the end of a listing loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_loop_end( $echo = true ) {
		ob_start();

		ffd_get_template( 'loop/loop-end.php' );

		$loop_end = apply_filters( 'ffd_listing_loop_end', ob_get_clean() );

		if ( $echo ) {
			echo $loop_end; // WPCS: XSS ok.
		} else {
			return $loop_end;
		}
	}
}


if ( ! function_exists( 'ffd_listing_field' ) ) {

	/**
	 * Output listing field value
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_field($field='', $post_id=null, $echo=true) {

		if( !isset($post_id) || empty($post_id) ){
			global $post;
			$post_id = $post->ID;
		}
		
		if( !empty($field) ){
			$field_value = get_post_meta($post_id, 'field', true);
		} else {
			$field_value = '';
		}


		$field_value = apply_filters( 'ffd_listing_field_value', $field_value);

		if ( $echo ) {
			echo $field_value; // WPCS: XSS ok.
		} else {
			return $field_value;
		}

	}

}


if ( ! function_exists( 'ffd_listing_all_fields' ) ) {

	/**
	 * Output a listing all fields values
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_all_fields( $post_id=null, $echo=true) {

		if( !isset($post_id) || empty($post_id) ){
			global $post;
			$post_id = $post->ID;
		
		}

		global $wpdb;
		$all_fields = array();
		$results = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key LIKE 'ffd_%' AND meta_value !='';", 'ARRAY_A');
		if( !empty($results) ){
			foreach($results as $result ){
				$value = maybe_unserialize($result['meta_value']);
				if( $echo && is_array($value) ){
					$value = implode(',', $value);
				}
				$all_fields[$result['meta_key']] = $value;
			}
		}

	


		


		$all_fields = apply_filters( 'ffd_listing_all_fields', $all_fields);

		if ( $echo ) {
			echo ( is_array($all_fields) ? implode(',', $all_fields) : $all_fields ); // WPCS: XSS ok.
		} else {
			return $all_fields;
		}

	}

}



if ( ! function_exists( 'ffd_listing_all_meta' ) ) {

	/**
	 * Output a listing all fields values
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_all_meta( $post_id=null, $echo=true) {

		if( !isset($post_id) || empty($post_id) ){
			global $post;
			$post_id = $post->ID;
		
		}

		$all_fields = array();
		/* Llamada original a PropertyBase
		$fields = FFD_Listings_Sync::fields(null, 'meta');
		*/

		//Nueva llamada a Trestle
        $fields = FFD_Listings_Trestle_Sync::fields(null, 'meta');

		foreach($fields as $pb_name => $meta_key ){
			$value = get_post_meta($post_id, $meta_key, true);
			if( "" != $value ){
				if( $echo && is_array($value) ){
					$value = implode(',', $value);
				}
				$all_fields[$meta_key] = $value;
			}
		}
		
		


		$all_fields = apply_filters( 'ffd_listing_all_meta', $all_fields);

		if ( $echo ) {
			echo ( is_array($all_fields) ? implode(',', $all_fields) : $all_fields ); // WPCS: XSS ok.
		} else {
			return $all_fields;
		}

	}

}


if ( ! function_exists( 'ffd_listing_field_values_by_name' ) ) {

	/**
	 * Output a listing all fields values
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_field_values_by_name( $name, $echo=true) {

		if( !isset($post_id) || empty($post_id) ){
			global $post;
			$post_id = $post->ID;
		}
		
		global $wpdb;
		$field = array();
		$sql = "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE  meta_key = '{$name}' AND meta_value !='' GROUP BY meta_value;";
		$results = $wpdb->get_results($sql, 'ARRAY_A');
		if( !empty($results) ){
			foreach($results as $result ){
				$field[$result['meta_value']] = $result['meta_value'];
			}
		}


		$field = apply_filters( 'ffd_listing_field_values_by_name', $field);

		if ( $echo ) {
			echo ( is_array($field) ? implode(',', $field) : $field ); // WPCS: XSS ok.
		} else {
			return $field;
		}

	}

}


if ( ! function_exists( 'ffd_sync_fields' ) ) {

	/**
	 * Output ffd_sync_fields
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_sync_fields($platform='platform', $echo=true) {

		if( !$platform )
			/* original para Propertybase
		    $platform = 'propertybase';
			*/

			//Para Trestle
		    $platform = 'trestle';

		/* original para propertybase
		$fields = FFD_Listings_Sync::fields($platform);
		*/

		//Nuevo para Trestle
        $fields = FFD_Listings_Trestle_Sync::fields($platform);
		
		$_fields = array();
		foreach ($fields as $pb_name => $db_name) {
			$_fields[$db_name] = ffd_clean_db_field_name($db_name) . ' ('.$db_name.')';
		}

		$fields = $_fields;
		$fields = apply_filters( 'ffd_listing_field_values_by_name', $fields);

		if ( $echo ) {
			echo ( is_array($fields) ? implode(',', $fields) : $fields ); // WPCS: XSS ok.
		} else {
			return $fields;
		}

	}

}


if ( ! function_exists( 'ffd_listing_images_slider' ) ) {

	/**
	 * Output the end of a listing image slider. 
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function ffd_listing_images_slider( $echo = true, $args=array()) {
		
		if( !isset($args['listing_id']) || empty($args['listing_id']) ){
			global $post;
			$args['listing_id'] = $post->ID;
		}

		$listing_id = $args['listing_id'];

		if( !is_singular('listing') && ( !isset($args['permalink']) || empty($args['permalink']) ) ){
			$args['permalink'] = get_permalink($listing_id);
		} 

		if( !isset($args['attach_permalink']) || empty($args['attach_permalink']) ){
			$args['attach_permalink'] = true;
		} 

		if( !isset($args['display_arrows']) || empty($args['display_arrows']) ){
			$args['display_arrows'] = true;
		} 

		if( !isset($args['slider']) || empty($args['slider']) ){
			$args['slider'] = true;
		} 

		if( !isset($args['images']) || empty($args['images']) ){
			$args['images'] = get_post_meta($listing_id, 'ffd_media', true);
		} else if(!is_array($args['images']) ) {
			$args['images'] = explode(',', $args['images']);
			$args['images'] = array_map('trim', $args['images']);
		}
		
		$first_image = (isset($args['images'][0]) ? $args['images'][0] : '' );
		ob_start();
		?>

		<div class="swiper-container ffd-listing-images-slider">
    		<div class="swiper-wrapper ffd-listing-images-slider-wraper">
				<?php if(!empty($args['images']) && is_array($args['images']) ): ?>
					<?php foreach($args['images'] as $image_url): ?>
						
					<div class="swiper-slide ffd-listing-image-slide ffd-listing-image-bgslide ffd-bg-cover" style="background-image:url(<?php echo $image_url; ?>);">

						<?php if($args['attach_permlink']): ?>
							<a href="<?php echo $args['permalink']; ?>?>"></a>
						<?php endif; ?>
						
					</div>
					<?php if(  !$args['slider'] ) break; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if( !empty($args['display_arrows']) ): ?>{
				<!-- Add Arrows -->
				<div class="swiper-button-next"></div>
				<div class="swiper-button-prev"></div>
			<?php endif; ?>

  		</div>
		<?php

		$images_slider = apply_filters( 'ffd_listing_images_slider', ob_get_clean() );

		if ( $echo ) {
			echo $images_slider; // WPCS: XSS ok.
		} else {
			return $images_slider;
		}
	}
}




if( !function_exists('ffd_generate_form_field_html') ){


	function ffd_generate_form_field_html($args, $echo=true){
		
		$value['title'] 		= isset($args['title']) ? $args['title'] : '';
		$value['id'] 		= isset($args['id']) ? $args['id'] : '';
		$value['label'] 	= isset($args['label']) ? $args['label'] : '';
		$value['name'] 		= isset($args['name']) ? $args['name'] : '';
		$value['type'] 		= isset($args['type']) ? $args['type'] : '';
		$value['css'] 		= isset($args['css']) ? $args['css'] : '';
		$value['class'] 	= isset($args['class']) ? $args['class'] : '';
		$value['placeholder'] = isset($args['placeholder']) ? $args['placeholder'] : '';
		$value['suffix'] = isset($args['suffix']) ? $args['suffix'] : '';
		$value['options']  = isset($args['options']) ? $args['options'] : array();
		
		if( isset($value['default_option']) && !$value['default_option']  ){
			$value['default_option'] = null;
		} else {
			$value['default_option'] = 'Select ' . $value['label'];
		}

		$option_value 	= isset($args['value']) ? $args['value'] : '';

		// Custom attribute handling.
		$custom_attributes = array();

		if( isset($args['required'])){
			$value['custom_attributes']['required'] = "";
		}

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		ob_start();
		
		switch ($value['type']) {
			case 'text':
			case 'password':
			case 'datetime':
			case 'datetime-local':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
			?>
			<div class="form-group">
			<label class="form-control-label" for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['label'] ); ?></label>
			<input
				name="<?php echo esc_attr( $value['name'] ); ?>"
				id="<?php echo esc_attr( $value['id'] ); ?>"
				type="<?php echo esc_attr( $value['type'] ); ?>"
				style="<?php echo esc_attr( $value['css'] ); ?>"
				value="<?php echo esc_attr( $option_value ); ?>"
				class="<?php echo esc_attr( $value['class'] ); ?> form-control"
				placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
				<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
				/><?php echo esc_html( $value['suffix'] ); ?>
			</div>
			<?php
				break;
			
			case 'dropdown':
			case 'select':
			case 'multiselect':
			?>
			<div class="form-group">
					<label  class="form-control-label" for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['label'] ); ?></label>
					<select
						name="<?php echo esc_attr( $value['name'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						style="<?php echo esc_attr( $value['css'] ); ?>"
						class="<?php echo esc_attr( $value['class'] ); ?> form-control"
						<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
						<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
						>
						<?php echo isset($value['default_option']) ? '<option value="">'.$value['default_option'].'</option>' : ''; ?>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"
								<?php

								if ( is_array( $option_value ) ) {
									selected( in_array( (string) $key, $option_value, true ), true );
								} else {
									selected( $option_value, (string) $key );
								}

							?>
							>
							<?php echo esc_html( $val ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
			<?php
				break;

			case 'textarea':
				?>
				<div class="form-group">
				<label  class="form-control-label"  for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['label'] ); ?></label>
				<textarea
					name="<?php echo esc_attr( $value['name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?> form-control"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
					><?php echo esc_textarea( $option_value ); // WPCS: XSS ok. ?></textarea>
				</div>
				<?php
				break;

			case 'radio':
			?>	
			<div class="form-group">
				<?php if( !empty( $value['options']) ): ?>

					<?php if(isset($value['title'])): ?>
						<label  class="form-control-label"  for="<?php echo esc_attr( $value['title'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php endif; ?>

					<ul>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							?>
							<li>
								<label><input
									name="<?php echo esc_attr( $value['name'] ); ?>"
									value="<?php echo esc_attr( $key ); ?>"
									type="radio"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									<?php checked( $key, $option_value ); ?>
									/> <?php echo esc_html( $val ); ?></label>
							</li>
							<?php
						} ?>
					</ul>

					<?php else: ?>
					<?php if(isset($value['title'])): ?>
						<label  class="form-control-label"  for="<?php echo esc_attr( $value['title'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php endif; ?>
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<input
							name="<?php echo esc_attr( $value['name'] ); ?>"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							type="radio"
							class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?> form-control"
							value="1"
							<?php checked( $option_value, '1' ); ?>
							<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
						/> <?php echo $value['label']; // WPCS: XSS ok. ?>
					</label> ?>

					<?php endif; ?>
				</div>
			<?php
				break;
			
			case 'checkbox':
			?>
			<div class="form-group">
				<?php if( !empty( $value['options']) ): ?>

					<?php if(isset($value['title'])): ?>
						<label  class="form-control-label"  for="<?php echo esc_attr( $value['title'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php endif; ?>

					<ul>
						<?php
						foreach ( $value['options'] as $key => $val ) {
							?>
							<li>
								<label><input
									name="<?php echo esc_attr( $value['name'] ); ?>"
									value="<?php echo esc_attr( $key ); ?>"
									type="checkbox"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?> form-control"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									<?php checked( $key, $option_value ); ?>
									/> <?php echo esc_html( $val ); ?></label>
							</li>
							<?php
						} ?>
					</ul>

					<?php else: ?>

						<?php if(isset($value['title'])): ?>
							<label  class="form-control-label"  for="<?php echo esc_attr( $value['title'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php endif; ?>
						
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
								name="<?php echo esc_attr( $value['name'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?> form-control"
								value="1"
								<?php checked( $option_value, '1' ); ?>
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
							/> <?php echo $value['label']; // WPCS: XSS ok. ?>
						</label> ?>

					<?php endif; ?>
				</div>
			<?php
				break;
			case 'range':
				# code...
			break;
			default:
				# code...
				break;
		}

		$html = ob_get_clean();

		if( $echo )
			echo $html;
		else
			return $html;
	}
}