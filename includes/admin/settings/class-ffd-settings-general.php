<?php
/**
 * FFD Integration General Settings
 *
 * @package FFD Integration/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FFD_Settings_General', false ) ) {
	return new FFD_Settings_General();
}

/**
 * FFD_Admin_Settings_General.
 */
class FFD_Settings_General extends FFD_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'ffd-integration' );

		parent::__construct();
	}


	
	/**
	 * Output the settings.
	 */
	public function output() {

		global $current_section;
        
		
		if ( $current_section ) {
			do_action( 'ffd_load_options_' . $this->id . '_' . $current_section );
        } 

		$settings = $this->get_settings($current_section);


		FFD_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		$limit_enabled = $settings['ffd_listing_view_limit_enabled'];
		if( isset($_POST['ffd_listing_view_limit_reset']) && $_POST['ffd_listing_view_limit_reset'] === 'yes' ){
			$settings['ffd_listing_view_limit_reset'] = 'no';
			update_option('ffd_visit_counter', '');
		} 


		FFD_Admin_Settings::save_fields( $settings );


		if ( $current_section ) {
			do_action( 'ffd_update_options_' . $this->id . '_' . $current_section );
        } 

        foreach($settings as $setting ){
            if( isset($setting['type']) && 'title' === $setting['type'] ){
                do_action( $setting['id'] . '_settings_submitted');
                break;
            }
		}
		
	}


	 /**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''             => __( 'General', 'ffd-integration' ),
			'other_settings'     => __( 'Other Settings', 'ffd-integration' )
		);

        
         
		return apply_filters( 'ffd_get_sections_' . $this->id, $sections );
	}
	
	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings($current_section = '' ) {

		if ( '' === $current_section ) { 

			return $this->general_section();

		} else if ( 'other_settings' === $current_section ){

			return $this->other_settings_section();
		}
		
	}


	public function other_settings_section(){

		$styles = array(
					'jq-ui',
					'jq-uitheme',
					'google-fonts',
					'FontAwesome',
					'Swiper',
					'niceSelect',
					'MagnificPopup',
					'jRangeStyles',
					'Bootstrap',
					'FFDLStyle',
					'FFDLResponsiveStyle',
					'FFDLTheme',
					'ui-contentStyle',
			);
		$styles_select = array();
		foreach($styles as $style ){
			$styles_select[$style] = ucwords(str_replace(array('-', '_'), ' ', $style));
		}

		$scripts = array(
				'BootstrapCore',
				'jQueryUI',
				'SwiperCore',
				'jRangeScript',
				'niceSelectScript',
				'FFDLFunctions',
				'GoogleMap',
				'MarkerWithLabel',
				'MarkerClusterer',
				'AppLibrary',
				'MagnificPopupScript',
				'InfiniteScrollScript'
		);

		$scripts_select = array();
		foreach($scripts as $script ){
			$scripts_select[$script] = ucwords(str_replace(array('-', '_'), ' ', $script));
		}

		

		$settings = apply_filters(
			'ffd_other_settings', array(

				





				array(
					'title' => __( 'Functionality & Post Types', 'ffd-integration' ),
					'type'  => 'title',
					'id'    => 'ffd_other_settings_templates_options',
				),

				array(
					'title'    => __( 'FFD Integration Search', 'ffd-integration' ),
					'desc'     => __( 'Enable FFD Integration Search', 'ffd-integration' ),
					'id'       => 'ffd_integration_search_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => ''
				),

				array(
					'title'    => __( 'Listing Post Type', 'ffd-integration' ),
					'desc'     => __( 'Enable Listing Custom Post Type', 'ffd-integration' ),
					'id'       => 'ffd_listing_posttype_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => ''
				),

				array(
					'title'    => __( 'Modules Functionality', 'ffd-integration' ),
					'desc'     => __( 'Enable Modules Functionality', 'ffd-integration' ),
					'id'       => 'ffd_add_legacy_support',
					'default'  => 'yes',
					'type'     => 'checkbox',
					'desc_tip' => __( 'Enable various functionality modules. required for ui section, webtoprospect', 'ffd-integration' ),
				),

				array(
					'title'    => __( 'UI Section', 'ffd-integration' ),
					'desc'     => __( 'Enable UI Section Post Type', 'ffd-integration' ),
					'id'       => 'ffd_ui_posttype_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => ''
				),

				array(
					'title'    => __( 'Single Template', 'ffd-integration' ),
					'desc'     => __( 'Enable Listing Details Template', 'ffd-integration' ),
					'id'       => 'ffd_listing_details_template',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => __( 'Enable listing details template using FFD plugin.', 'ffd-integration' ),
				),

				array(
					'title'    => 'test',
					'desc'     => 'test',
					'id'       => 'ffd_admin_force_refresh',
					'default'  => 'ignore_this_value',
					'type'     => 'hidden',
					'desc_tip' => ''
				),

				array(
					'type' => 'sectionend',
					'id'   => 'ffd_other_settings_templates_options',
				),



				
				array(
					'title' => __( 'Scripts And Styles', 'ffd-integration' ),
					'type'  => 'title',
					'id'    => 'ffd_other_settings_scripts_styles'
				),

				array(
					'title'    => __( 'Styles', 'ffd-integration' ),
					'desc'     => __( 'Include FFD Styles', 'ffd-integration' ),
					'id'       => 'ffd_include_legacy_styles',
					'options'  => $styles_select,
					'default' => $styles,
					'type'     => 'multiselect',
					'desc_tip' => __( 'Include FFD Styles.', 'ffd-integration' )
				),

				array(
					'title'    => __( 'Scripts', 'ffd-integration' ),
					'desc'     => __( 'Include FFD Scripts', 'ffd-integration' ),
					'id'       => 'ffd_include_legacy_scripts',
					'options'  => $scripts_select,
					'default' => $scripts,
					'type'     => 'multiselect',
					'desc_tip' => __( 'Include FFD Scripts.', 'ffd-integration' )
				),


				array(
					'title' => 'Listings Data',
					'desc' => 'Generate Custom Table data for listings (used by search)',
					'id'	=> 'ffd_createupdate_listing_data',
					'text'	=> 'Create/Update Data',
					'type'	=> 'button'
				),

				array(
					'title' => 'Delete FFD Sync Data',
					'desc' => 'Delete Listings Synced By FFD Plugin ( Careful: Not Reversable ) <br>. This is helpful when fresh re-sync needed.',
					'id'	=> 'ffd_delete_pbtowpsync_data',
					'text'	=> 'Delete ALL Listings',
					'type'	=> 'button'
				),

				array(
					'type' => 'sectionend',
					'id'   => 'ffd_other_settings_scripts_styles'
				),

				

			)
		);


		$popup_settings = apply_filters(
			'ffd_limitview_settings', array(

				array(
					'title' => __( 'Listing View Restriction', 'ffd-integration' ),
					'desc'     => __( 'Listing Details Page Restriction for non-logged user', 'ffd-integration' ),
					'type'  => 'title',
					'id'    => 'ffd_other_settings_limitview_options',
				),


				array(
					'title'    => __( 'Enable', 'ffd-integration' ),
					'desc'     => __( ' ', 'ffd-integration' ),
					'id'       => 'ffd_listing_view_limit_enabled',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => __( '', 'ffd-integration' ),
				),

				array(
					'title'    => __( 'Limit', 'ffd-integration' ),
					//'desc'     => __( '', 'ffd-integration' ),
					'id'       => 'ffd_listing_view_limit',
					'default'  => '4',
					'type'     => 'text',
					//'desc_tip' => __( '', 'ffd-integration' ),
				),


				array(
					'title'    => __( 'Popup HTML', 'ffd-integration' ),
					'desc'     => __( 'HTML Allowed' ),
					'id'       => 'ffd_listing_view_limit_popup',
					'default'  => '',
					'type'     => 'textarea',
					'custom_attributes' => array('rows'=>20)
				),
				
				array(
					'title'    => __( 'Reset View Logs', 'ffd-integration' ),
					'desc'     => __( ' ', 'ffd-integration' ),
					'id'       => 'ffd_listing_view_limit_reset',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => __( '', 'ffd-integration' ),
				),



				array(
					'type' => 'sectionend',
					'id'   => 'ffd_other_settings_limitview_options'
				)
			)
		);

		$settings = array_merge($settings, $popup_settings);

		return apply_filters( 'ffd_get_settings_' . $this->id, $settings );
	
	}

	public function general_section(){


		$currency_code_options = get_ffd_currencies();

		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_ffd_currency_symbol( $code ) . ')';
		}

		$settings = apply_filters(
			'ffd_general_settings', array(
				
				array(
					'title' => __( 'Office Address', 'ffd-integration' ),
					'type'  => 'title',
					'desc'  => __( 'This is where your business is located.', 'ffd-integration' ),
					'id'    => 'office_address',
				),

				array(
					'title'    => __( 'Address line 1', 'ffd-integration' ),
					'desc'     => __( 'The street address for your business location.', 'ffd-integration' ),
					'id'       => 'ffd_office_address',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Address line 2', 'ffd-integration' ),
					'desc'     => __( 'An additional, optional address line for your business location.', 'ffd-integration' ),
					'id'       => 'ffd_office_address_2',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'City', 'ffd-integration' ),
					'desc'     => __( 'The city in which your business is located.', 'ffd-integration' ),
					'id'       => 'ffd_office_city',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Country / State', 'ffd-integration' ),
					'desc'     => __( 'The country and state or province, if any, in which your business is located.', 'ffd-integration' ),
					'id'       => 'ffd_default_country',
					'default'  => 'US',
					'type'     => 'single_select_country',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Postcode / ZIP', 'ffd-integration' ),
					'desc'     => __( 'The postal code, if any, in which your business is located.', 'ffd-integration' ),
					'id'       => 'ffd_office_postcode',
					'css'      => 'min-width:50px;',
					'default'  => '',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'office_address',
				),

				array(
					'title' => __( 'General options', 'ffd-integration' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options',
				),

				array(
					'title' => __( 'Currency options', 'ffd-integration' ),
					'type'  => 'title',
					'desc'  => __( 'The following options affect how prices are displayed on the frontend.', 'ffd-integration' ),
					'id'    => 'pricing_options',
				),

				array(
					'title'    => __( 'Currency', 'ffd-integration' ),
					'desc'     => __( 'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.', 'ffd-integration' ),
					'id'       => 'ffd_currency',
					'default'  => 'USD',
					'type'     => 'select',
					'class'    => 'ffd-enhanced-select',
					'desc_tip' => true,
					'options'  => $currency_code_options,
				),

				array(
					'title'    => __( 'Currency position', 'ffd-integration' ),
					'desc'     => __( 'This controls the position of the currency symbol.', 'ffd-integration' ),
					'id'       => 'ffd_currency_pos',
					'class'    => 'ffd-enhanced-select',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => array(
						'left'        => __( 'Left', 'ffd-integration' ),
						'right'       => __( 'Right', 'ffd-integration' ),
						'left_space'  => __( 'Left with space', 'ffd-integration' ),
						'right_space' => __( 'Right with space', 'ffd-integration' ),
					),
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Thousand separator', 'ffd-integration' ),
					'desc'     => __( 'This sets the thousand separator of displayed prices.', 'ffd-integration' ),
					'id'       => 'ffd_price_thousand_sep',
					'css'      => 'width:50px;',
					'default'  => ',',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Decimal separator', 'ffd-integration' ),
					'desc'     => __( 'This sets the decimal separator of displayed prices.', 'ffd-integration' ),
					'id'       => 'ffd_price_decimal_sep',
					'css'      => 'width:50px;',
					'default'  => '.',
					'type'     => 'text',
					'desc_tip' => true,
				),

				array(
					'title'             => __( 'Number of decimals', 'ffd-integration' ),
					'desc'              => __( 'This sets the number of decimal points shown in displayed prices.', 'ffd-integration' ),
					'id'                => 'ffd_price_num_decimals',
					'css'               => 'width:50px;',
					'default'           => '2',
					'desc_tip'          => true,
					'type'              => 'number',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
				),

				array(
					'type' => 'sectionend',
					'id'   => 'pricing_options',
				),

			)
		);

		return apply_filters( 'ffd_get_settings_' . $this->id, $settings );

	
	}

	/**
	 * Output a color picker input box.
	 *
	 * @param mixed  $name Name of input.
	 * @param string $id ID of input.
	 * @param mixed  $value Value of input.
	 * @param string $desc (default: '') Description for input.
	 */
	public function color_picker( $name, $id, $value, $desc = '' ) {
		echo '<div class="color_box">' . ffd_help_tip( $desc ) . '
			<input name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
		</div>';
	}

}

return new FFD_Settings_General();
