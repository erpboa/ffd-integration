<?php
/**
 * FFD Integration Admin Settings Class
 *
 * @package  FFD Integration/Admin
 * @version  3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FFD_Admin_Settings', false ) ) :

	/**
	 * FFD_Admin_Settings Class.
	 */
	class FFD_Admin_Settings {

		/**
		 * Setting pages.
		 *
		 * @var array
		 */
		private static $settings = array();

		/**
		 * Error messages.
		 *
		 * @var array
		 */
		private static $errors = array();

		/**
		 * Update messages.
		 *
		 * @var array
		 */
		private static $messages = array();

		/**
		 * Include the settings page classes.
		 */
		public static function get_settings_pages() {
			if ( empty( self::$settings ) ) {
				$settings = array();

				include_once dirname( __FILE__ ) . '/settings/class-ffd-settings-page.php';

				$settings[] = include 'settings/class-ffd-settings-general.php';
				$settings[] = include 'settings/class-ffd-settings-platforms.php';

				self::$settings = apply_filters( 'ffd_get_settings_pages', $settings );
			}

			return self::$settings;
		}

		/**
		 * Save the settings.
		 */
		public static function save() {
			global $current_tab, $current_section;

			//check_admin_referer( 'ffd-settings' );

			// Trigger actions.
			do_action( 'ffd_settings_save_' . $current_tab );
			do_action( 'ffd_update_options_' . $current_tab );
			do_action( 'ffd_update_options' );

			self::add_message( __( 'Your settings have been saved.', 'ffd-integration' ) );

			// Clear any unwanted data and flush rules.
			update_option( 'ffd_queue_flush_rewrite_rules', 'yes' );
			
			
			do_action( 'ffd_settings_saved' );

			if ( isset($_POST) && isset($_POST['ffd_admin_force_refresh']) ) {
				$url = admin_url( 'admin.php?page=ffd-settings' );
				$arg = array();

				if( !empty($current_tab) ){
					$arg['tab'] = $current_tab;
				}
				if( !empty($current_section) ){
					$arg['section'] = $current_section;
				}
				
				wp_safe_redirect( add_query_arg($arg, $url) );
				exit;
			}
			
		}

		/**
		 * Add a message.
		 *
		 * @param string $text Message.
		 */
		public static function add_message( $text ) {
			self::$messages[] = $text;
		}

		/**
		 * Add an error.
		 *
		 * @param string $text Message.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text;
		}

		/**
		 * Output messages + errors.
		 */
		public static function show_messages() {
			if ( count( self::$errors ) > 0 ) {
				foreach ( self::$errors as $error ) {
					echo '<div id="message" class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
				}
			} elseif ( count( self::$messages ) > 0 ) {
				foreach ( self::$messages as $message ) {
					echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
				}
			}
		}

		/**
		 * Settings page.
		 *
		 * Handles the display of the main FFD Integration settings page in admin.
		 */
		public static function output() {
			global $current_section, $current_tab;

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			do_action( 'ffd_settings_start' );

			wp_enqueue_script( 'ffd_settings', FFD()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'selectFFD' ), FFD()->version, true );

			wp_localize_script(
				'ffd_settings', 'ffd_settings_params', array(
					'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'ffd-integration' ),
					'i18n_moved_up'    => __( 'Item moved up', 'ffd-integration' ),
					'i18n_moved_down'  => __( 'Item moved down', 'ffd-integration' ),
				)
			);

			// Get tabs for the settings page.
			$tabs = apply_filters( 'ffd_settings_tabs_array', array() );

			include dirname( __FILE__ ) . '/views/html-admin-settings.php';
		}

		/**
		 * Get a setting from the settings API.
		 *
		 * @param string $option_name Option name.
		 * @param mixed  $default     Default value.
		 * @return mixed
		 */
		public static function get_option( $option_name, $default = '' ) {
			// Array value.
			if ( strstr( $option_name, '[' ) ) {

				parse_str( $option_name, $option_array );

				// Option name is first key.
				$option_name = current( array_keys( $option_array ) );

				// Get value.
				$option_values = get_option( $option_name, '' );

				$key = key( $option_array[ $option_name ] );

				if ( isset( $option_values[ $key ] ) ) {
					$option_value = $option_values[ $key ];
				} else {
					$option_value = null;
				}
			} else {
				// Single value.
				$option_value = get_option( $option_name, null );
			}

			if ( is_array( $option_value ) ) {
				$option_value = array_map( 'stripslashes', $option_value );
			} elseif ( ! is_null( $option_value ) ) {
				$option_value = stripslashes( $option_value );
			}

			return ( null === $option_value ) ? $default : $option_value;
		}

		/**
		 * Output admin fields.
		 *
		 * Loops though the FFD Integration options array and outputs each field.
		 *
		 * @param array[] $options Opens array to output.
		 */
		public static function output_fields( $options ) {
			foreach ( $options as $value ) {
				if ( ! isset( $value['type'] ) ) {
					continue;
				}
				if ( ! isset( $value['id'] ) ) {
					$value['id'] = '';
				}
				if ( ! isset( $value['title'] ) ) {
					$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
				}
				if ( ! isset( $value['class'] ) ) {
					$value['class'] = '';
				}
				if ( ! isset( $value['css'] ) ) {
					$value['css'] = '';
				}
				if ( ! isset( $value['default'] ) ) {
					$value['default'] = '';
				}
				if ( ! isset( $value['desc'] ) ) {
					$value['desc'] = '';
				}
				if ( ! isset( $value['desc_tip'] ) ) {
					$value['desc_tip'] = false;
				}
				if ( ! isset( $value['placeholder'] ) ) {
					$value['placeholder'] = '';
				}
				if ( ! isset( $value['suffix'] ) ) {
					$value['suffix'] = '';
				}

				if ( ! isset( $value['tr_css'] ) ) {
					$value['tr_css'] = '';
				}

				

				// Custom attribute handling.
				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				// Description handling.
				$field_description = self::get_field_description( $value );
				$description       = $field_description['description'];
				$tooltip_html      = $field_description['tooltip_html'];

				// Switch based on type.
				switch ( $value['type'] ) {

					case 'html':

							$allowed = array(
								'a' => array(
								  // Here, we whitelist 'href' and 'title' attributes - nothing else allowed!
								  'href' => array(),
								  'title' => array()
								),
								'br' => array(),
								'em' => array(),
								'strong' => array(),
								'p' => array(),
								'button' => array()
							);
							?><tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label>&nbsp;</label>
							</th>
							<td class="formcustomhtml"><?php echo wp_kses($value['html'], $allowed); ?></td>
						</tr>
							<?php
						  break;
					case 'button':
							$option_value = $value['text'];
							$button_type = isset($value['button_type']) ? $value['button_type'] : 'button';
						  ?><tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
						  <th scope="row" class="titledesc">
							  <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						  </th>
						  <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							  <button
								  id="<?php echo esc_attr( $value['id'] ); ?>"
								  name="<?php echo esc_attr( $value['id'] ); ?>"
								  type="<?php echo esc_attr( $button_type ); ?>"
								  style="<?php echo esc_attr( $value['css'] ); ?>"
								  class="<?php echo esc_attr( $value['class'] ); ?>"
								  <?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								  ><?php echo esc_attr( $option_value ); ?></button><?php echo $description; // WPCS: XSS ok. ?>
						  </td>
					  </tr>
						  <?php
						break;
					// Section Titles.
					case 'title':
						if ( ! empty( $value['title'] ) ) {
							echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
						}
						if ( ! empty( $value['desc'] ) ) {
							echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
							echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
							echo '</div>';
						}
						if ( ! empty( $value['sub_title_field'] ) ) {
							$subfield = $value['sub_title_field'];
							echo '<table class="form-table" style="background-color:#fff;">';
							echo '<tbody>';
							?>
							<tr valign="top" id="<?php echo $subfield['row_id']; ?>">
								<th scope="row" class="titledesc" style="padding-left:18px;">
									<label><?php echo esc_html( $subfield['label'] ); ?></label>
								</th>
								<td class="forminp forminp-text" style="line-height: 1.3;font-weight: 600;padding-left:30px;">
									<label title="toggle unchecked field"><?php echo esc_html( $subfield['text'] ); ?></label>
								</td>
							</tr>
							<?php
							echo '</tbody>';
							echo '</table>';
						}
						echo '<table class="form-table '. (isset($value['table_class']) ? $value['table_class'] : '' ) .'" style="'.(isset($value['table_style']) ? $value['table_style'] : '' ).'" >' . "\n\n";
						if ( ! empty( $value['id'] ) ) {
							do_action( 'ffd_settings_' . sanitize_title( $value['id'] ) );
						}
						break;

					// Section Ends.
					case 'sectionend':
						if ( ! empty( $value['id'] ) ) {
							do_action( 'ffd_settings_' . sanitize_title( $value['id'] ) . '_end' );
						}
						echo '</table>';
						if ( ! empty( $value['id'] ) ) {
							do_action( 'ffd_settings_' . sanitize_title( $value['id'] ) . '_after' );
						}
						break;

					// Standard text inputs and subtypes like 'number'.
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
						$option_value = self::get_option( $value['id'], $value['default'] );

						?><tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="<?php echo esc_attr( $value['type'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;

					case 'hidden':
						$option_value = self::get_option( $value['id'], $value['default'] );

						?><tr class="hidden-field" style="display:none;width:0;height:0;">
							<th scope="row"></th>
							<td>
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="<?php echo esc_attr( $value['type'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;

					// Color picker.
					case 'color':
						$option_value = self::get_option( $value['id'], $value['default'] );

						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
								<span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>">&nbsp;</span>
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="text"
									dir="ltr"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									/>&lrm; <?php echo $description; // WPCS: XSS ok. ?>
									<div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
							</td>
						</tr>
						<?php
						break;

					// Textarea.
					case 'textarea':
						$option_value = self::get_option( $value['id'], $value['default'] );

						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<?php echo $description; // WPCS: XSS ok. ?>

								<textarea
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									><?php echo esc_textarea( $option_value ); // WPCS: XSS ok. ?></textarea>
							</td>
						</tr>
						<?php
						break;

					// Select boxes.
					case 'select':
					case 'multiselect':
						$option_value = self::get_option( $value['id'], $value['default'] );

						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<select
									name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?> <?php echo 'multiselect' === $value['type'] ? 'multiple' : ''; ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
									>
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
								</select> <?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;


						// Select boxes.
					case 'map_fields':
						//$option_value = self::get_option( $value['id'], $value['default'] );
						$trestle_fields_mapping = get_option('trestle_fields_mapping', array());
						
						$api_field_name = $value['value'];


						if( !empty($trestle_fields_mapping) ){
							$map_field_value = isset($trestle_fields_mapping[$api_field_name]['key']) ? $trestle_fields_mapping[$api_field_name]['key'] : $value['map_field_default'];
							$map_field_check_value = isset($trestle_fields_mapping[$api_field_name]['enabled']) ? $trestle_fields_mapping[$api_field_name]['enabled'] : 0;
						} else {
							$map_field_value = $value['map_field_default'];
							$map_field_check_value = $value['map_field_check_default'];
						}
						
						$edit_keys = ('yes' === get_option('ffd_trestle_edit_keys', 'no') );

						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
								<p class="description api_field_name"><?php echo esc_html( $api_field_name ); ?></p>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									type="hidden"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $api_field_name ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
									/>

								


									<input
									name="<?php echo esc_attr( $value['map_field_check'] ); ?>"
									type="hidden" value="0" />
									<input
									name="<?php echo esc_attr( $value['map_field_check'] ); ?>"
									type="checkbox" value="1" <?php checked( $map_field_check_value, '1' ); ?> />

									<input
									name="<?php echo esc_attr( $value['map_field_id'] ); ?>"
									type="text" 
									<?php if(!$edit_keys): ?> readonly="readonly" <?php endif; ?> 
									value="<?php echo esc_attr( $map_field_value ); ?>" />
								<?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;

					// Radio inputs.
					case 'radio':
						$option_value = self::get_option( $value['id'], $value['default'] );

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
								<fieldset>
									<?php echo $description; // WPCS: XSS ok. ?>
									<ul>
									<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<li>
											<label><input
												name="<?php echo esc_attr( $value['id'] ); ?>"
												value="<?php echo esc_attr( $key ); ?>"
												type="radio"
												style="<?php echo esc_attr( $value['css'] ); ?>"
												class="<?php echo esc_attr( $value['class'] ); ?>"
												<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
												<?php checked( $key, $option_value ); ?>
												/> <?php echo esc_html( $val ); ?></label>
										</li>
										<?php
									}
									?>
									</ul>
								</fieldset>
							</td>
						</tr>
						<?php
						break;

					// Checkbox input.
					case 'checkbox':
						$option_value     = isset($value['value']) ? $value['value'] : self::get_option( $value['id'], $value['default'] );
						$visibility_class = array();

						if ( ! isset( $value['hide_if_checked'] ) ) {
							$value['hide_if_checked'] = false;
						}
						if ( ! isset( $value['show_if_checked'] ) ) {
							$value['show_if_checked'] = false;
						}
						if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
							$visibility_class[] = 'hidden_option';
						}
						if ( 'option' === $value['hide_if_checked'] ) {
							$visibility_class[] = 'hide_options_if_checked';
						}
						if ( 'option' === $value['show_if_checked'] ) {
							$visibility_class[] = 'show_options_if_checked';
						}

						if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
							?>
								<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
									<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
									<td class="forminp forminp-checkbox">
										<fieldset>
							<?php
						} else {
							?>
								<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
							<?php
						}

						if ( ! empty( $value['title'] ) ) {
							?>
								<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
							<?php
						}

						?>
							<label for="<?php echo esc_attr( $value['id'] ); ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="checkbox"
									class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
									value="1"
									<?php checked( $option_value, 'yes' ); ?>
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								/> <?php echo $description; // WPCS: XSS ok. ?>
							</label> <?php echo $tooltip_html; // WPCS: XSS ok. ?>
						<?php

						if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
										?>
										</fieldset>
									</td>
								</tr>
							<?php
						} else {
							?>
								</fieldset>
							<?php
						}
						break;

					// Single page selects.
					case 'single_select_page':
						$args = array(
							'name'             => $value['id'],
							'id'               => $value['id'],
							'sort_column'      => 'menu_order',
							'sort_order'       => 'ASC',
							'show_option_none' => ' ',
							'class'            => $value['class'],
							'echo'             => false,
							'selected'         => absint( self::get_option( $value['id'], $value['default'] ) ),
							'post_status'      => 'publish,private,draft',
						);

						if ( isset( $value['args'] ) ) {
							$args = wp_parse_args( $value['args'], $args );
						}

						?>
						<tr valign="top" class="single_select_page" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp">
								<?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'ffd-integration' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); // WPCS: XSS ok. ?> <?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;

					// Single country selects.
					case 'single_select_country':
						$country_setting = (string) self::get_option( $value['id'], $value['default'] );

						if ( strstr( $country_setting, ':' ) ) {
							$country_setting = explode( ':', $country_setting );
							$country         = current( $country_setting );
							$state           = end( $country_setting );
						} else {
							$country = $country_setting;
							$state   = '*';
						}
						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'ffd-integration' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'ffd-integration' ); ?>" class="wc-enhanced-select">
								<?php FFD()->countries->country_dropdown_options( $country, $state ); ?>
							</select> <?php echo $description; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;

					// Country multiselects.
					case 'multi_select_countries':
						$selections = (array) self::get_option( $value['id'], $value['default'] );

						if ( ! empty( $value['options'] ) ) {
							$countries = $value['options'];
						} else {
							$countries = FFD()->countries->countries;
						}

						asort( $countries );
						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp">
								<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'ffd-integration' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'ffd-integration' ); ?>" class="wc-enhanced-select">
									<?php
									if ( ! empty( $countries ) ) {
										foreach ( $countries as $key => $val ) {
											echo '<option value="' . esc_attr( $key ) . '"' . ffd_selected( $key, $selections ) . '>' . esc_html( $val ) . '</option>'; // WPCS: XSS ok.
										}
									}
									?>
								</select> <?php echo ( $description ) ? $description : ''; // WPCS: XSS ok. ?> <br /><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'ffd-integration' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'ffd-integration' ); ?></a>
							</td>
						</tr>
						<?php
						break;

					// Days/months/years selector.
					case 'relative_date_selector':
						$periods      = array(
							'days'   => __( 'Day(s)', 'ffd-integration' ),
							'weeks'  => __( 'Week(s)', 'ffd-integration' ),
							'months' => __( 'Month(s)', 'ffd-integration' ),
							'years'  => __( 'Year(s)', 'ffd-integration' ),
						);
						$option_value = ffd_parse_relative_date_option( self::get_option( $value['id'], $value['default'] ) );
						?>
						<tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
							</th>
							<td class="forminp">
							<input
									name="<?php echo esc_attr( $value['id'] ); ?>[number]"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="number"
									style="width: 80px;"
									value="<?php echo esc_attr( $option_value['number'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									step="1"
									min="1"
									<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								/>&nbsp;
								<select name="<?php echo esc_attr( $value['id'] ); ?>[unit]" style="width: auto;">
									<?php
									foreach ( $periods as $value => $label ) {
										echo '<option value="' . esc_attr( $value ) . '"' . selected( $option_value['unit'], $value, false ) . '>' . esc_html( $label ) . '</option>';
									}
									?>
								</select> <?php echo ( $description ) ? $description : ''; // WPCS: XSS ok. ?>
							</td>
						</tr>
						<?php
						break;
					
					case 'column_text':
					?>
						<tr valign="top" style="position: absolute;background-color:#fff;width: 100%;z-index:1;">
							<th scope="row" style="padding:10px;">
								<label><?php echo esc_html( $value['0'] ); ?></label>
							</th>
							<th style="padding:10px 0 0 0;">
								<label><?php echo esc_html( $value['1'] ); ?></label>
							</th>
						</tr>
					<?php
					break;
					case 'plain_text':
					$option_value = self::get_option( $value['id'], $value['default'] );

					?><tr valign="top" style="<?php echo esc_attr( $value['tr_css'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<p
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								style="<?php echo esc_attr( $value['css'] ); ?>"
								class="<?php echo esc_attr( $value['class'] ); ?>"
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
								>
								<?php echo esc_attr( $option_value ); ?>
							</p><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

					// Default: run an action.
					default:
						do_action( 'ffd_admin_field_' . $value['type'], $value );
						break;
				}
			}
		}

		/**
		 * Helper function to get the formatted description and tip HTML for a
		 * given form field. Plugins can call this when implementing their own custom
		 * settings types.
		 *
		 * @param  array $value The form field value array.
		 * @return array The description and tip as a 2 element array.
		 */
		public static function get_field_description( $value ) {
			$description  = '';
			$tooltip_html = '';

			if ( true === $value['desc_tip'] ) {
				$tooltip_html = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description  = $value['desc'];
				$tooltip_html = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$description = wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
			}

			if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
			} elseif ( $tooltip_html ) {
				$tooltip_html = ffd_help_tip( $tooltip_html );
			}

			return array(
				'description'  => $description,
				'tooltip_html' => $tooltip_html,
			);
		}

		/**
		 * Save admin fields.
		 *
		 * Loops though the FFD Integration options array and outputs each field.
		 *
		 * @param array $options Options array to output.
		 * @param array $data    Optional. Data to use for saving. Defaults to $_POST.
		 * @return bool
		 */
		public static function save_fields( $options, $data = null ) {
			if ( is_null( $data ) ) {
				$data = $_POST; // WPCS: input var okay, CSRF ok.
			}
			if ( empty( $data ) ) {
				return false;
			}

			
			
			// Options to update will be stored here and saved later.
			$update_options   = array();
			$autoload_options = array();

			// Loop options and get values to save.
			foreach ( $options as $option ) {
				if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) ) {
					continue;
				}

				if (  $option['type'] == 'map_fields' ) {
					continue;
				}

				// Get posted value.
				if ( strstr( $option['id'], '[' ) ) {
					parse_str( $option['id'], $option_name_array );
					$option_name  = current( array_keys( $option_name_array ) );
					$setting_name = key( $option_name_array[ $option_name ] );
					$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
				} else {
					$option_name  = $option['id'];
					$setting_name = '';
					$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
				}
				
				// Format the value based on option type.
				switch ( $option['type'] ) {
					case 'checkbox':
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'textarea':
						$value = wp_kses_post( trim( $raw_value ) );
						break;
					case 'multiselect':
					case 'multi_select_countries':
						$value = array_filter( array_map( 'ffd_clean', (array) $raw_value ) );
						break;
					/* case 'map_fields':
						$value = array_filter( array_map( 'ffd_clean', (array) $raw_value ) );
						break; */
					case 'image_width':
						$value = array();
						if ( isset( $raw_value['width'] ) ) {
							$value['width']  = ffd_clean( $raw_value['width'] );
							$value['height'] = ffd_clean( $raw_value['height'] );
							$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
						} else {
							$value['width']  = $option['default']['width'];
							$value['height'] = $option['default']['height'];
							$value['crop']   = $option['default']['crop'];
						}
						break;
					case 'select':
						$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
						if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
							$value = null;
							break;
						}
						$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
						$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
						break;
					case 'relative_date_selector':
						$value = ffd_parse_relative_date_option( $raw_value );
						break;
					default:
						$value = ffd_clean( $raw_value );
						break;
				}


				/**
				 * Sanitize the value of an option.
				 *
				 * @since 2.4.0
				 */
				$value = apply_filters( 'ffd_admin_settings_sanitize_option', $value, $option, $raw_value );

				/**
				 * Sanitize the value of an option by option name.
				 *
				 * @since 2.4.0
				 */
				$value = apply_filters( "ffd_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

				if ( is_null( $value ) || $value === 'ignore_this_input') {
					continue;
				}

				// Check if option is an array and handle that differently to single values.
				if ( $option_name && $setting_name ) {
					if ( ! isset( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = get_option( $option_name, array() );
					}
					if ( ! is_array( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = array();
					}
					$update_options[ $option_name ][ $setting_name ] = $value;
				} else {
					$update_options[ $option_name ] = $value;
				}

				$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;

			}

			//save fields mapping data
			$update_options = self::set_trestle_field_mapping_data($update_options);


			// Save all other options in our array.
			foreach ( $update_options as $name => $value ) {
				$auto_load = isset($autoload_options[ $name ]) ? 'yes' : 'no';
				update_option( $name, $value, $auto_load);
			}

			return true;
		}


		public static function set_trestle_field_mapping_data($update_options){

			//$trestle_fields = get_option('trestle_fields_mapping', array());

			if( isset($_POST['map_fields']) && !empty($_POST['map_fields']) ){
				$map_fields = 
				$trestle_fields = array();
				foreach($_POST['map_fields']['trestle_field_name'] as $map_field_key => $map_field_value ){
					
					$trestle_fields[$map_field_value] = array(
																'key'=>$_POST['map_fields']['trestle_field_db'][$map_field_key],
																'enabled'=>$_POST['map_fields']['trestle_field_enabled'][$map_field_key],
					);
				}
				$autoload_options[ 'trestle_fields_mapping' ] = true;
				$update_options[ 'trestle_fields_mapping' ] = $trestle_fields;
			}

			if( isset($_GET['debug_map_fields']) ){
				ffd_debug($update_options, false);
			}
			return $update_options;
		}
		/**
		 * Checks which method we're using to serve downloads.
		 *
		 * If using force or x-sendfile, this ensures the .htaccess is in place.
		 */
		public static function check_download_folder_protection() {
			$upload_dir      = wp_upload_dir();
			$downloads_url   = $upload_dir['basedir'] . '/ffd_uploads';
			$download_method = get_option( 'ffd_file_download_method' );

			if ( 'redirect' === $download_method ) {

				// Redirect method - don't protect.
				if ( file_exists( $downloads_url . '/.htaccess' ) ) {
					unlink( $downloads_url . '/.htaccess' ); // @codingStandardsIgnoreLine
				}
			} else {

				// Force method - protect, add rules to the htaccess file.
				if ( ! file_exists( $downloads_url . '/.htaccess' ) ) {
					$file_handle = @fopen( $downloads_url . '/.htaccess', 'w' ); // @codingStandardsIgnoreLine
					if ( $file_handle ) {
						fwrite( $file_handle, 'deny from all' ); // @codingStandardsIgnoreLine
						fclose( $file_handle ); // @codingStandardsIgnoreLine
					}
				}
			}
		}
	}

endif;
