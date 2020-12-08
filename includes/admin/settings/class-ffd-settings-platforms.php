<?php
/**
 * FFD Integration General Settings
 *
 * @package FFD Integration/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'FFD_Settings_Platforms', false ) ) {
	return new FFD_Settings_Platforms();
}

/**
 * FFD_Admin_Settings_Platforms.
 */
class FFD_Settings_Platforms extends FFD_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'platforms';
        $this->label = __( 'Platforms', 'ffd-integration' );
        
        add_filter('ffd_settings_current_section', function($section){
            
            if( !empty($section) ){
                $sections = $this->get_sections();
                $section = ( isset($sections[$section]) ? $section : '');
            }

            return $section;
        });

		parent::__construct();
    }


    
	/**
	 * Output the settings.
	 */
	public function output() {
        global $current_section;
        
        do_action( 'ffd_load_options_' . $this->id . '_' . $current_section );
       

		$settings = $this->get_settings($current_section);

		FFD_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
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
			''             => __( 'General', 'ffd-integration' )
		);

        
        if( 'yes' === get_option('ffd_propertybase_enabled', 'yes') ){
            $sections['propertybase'] = __( 'PropertyBase', 'ffd-integration' );
        }

        //Add option Trestle
        /********************************************************************/

        if( 'yes' === get_option('ffd_trestle_enabled', 'yes') ){
            $sections['trestle'] = __( 'Trestle', 'ffd-integration' );
        }
        /*********************************************************************/

		return apply_filters( 'ffd_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings($current_section = '' ) {
        
        $settings = array();

        if ( '' === $current_section ) { 
            $settings = apply_filters(
                'ffd_platforms_settings', array(
                            array(
                                'title' => __( 'Platforms option', 'ffd-integration' ),
                                'type'  => 'title',
                                'id'    => 'ffd_propertybase_general',
                            ),

                            array(
                                'title'    => __( 'PropertyBase', 'ffd-integration' ),
                                'desc'     => __( 'Use Propertybase for listings sync', 'ffd-integration' ),
                                'id'       => 'ffd_propertybase_enabled',
                                'default'  => 'yes',
                                'type'     => 'checkbox',
                                'desc_tip' => __( 'Propertybase will be used for listing sync.', 'ffd-integration' ),
                            ),

                            // Add option Trestle
                            /*******************************************************************************/

                            array(
                                'title'    => __( 'Trestle', 'ffd-integration' ),
                                'desc'     => __( 'Use Trestle for listings sync', 'ffd-integration' ),
                                'id'       => 'ffd_trestle_enabled',
                                'default'  => 'no',
                                'type'     => 'checkbox',
                                'desc_tip' => __( 'Trestle will be used for listing sync.', 'ffd-integration' ),
                            ),

                            /*******************************************************************************/

                            array(
                                'type' => 'sectionend',
                                'id'   => 'ffd_propertybase_general',
                            ),
                )
            );

        } else if ( 'propertybase' === $current_section ) { 

            $instance_url = get_option('ffd_propertybase_instance_url');
            $api_authorized = FFD_Fields_Mapping::is_platform_authorized();

            $api_settings = apply_filters(
                'ffd_propertybase_settings', array(

                    

                    array(
                        'title' => __( 'API Settings', 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Add Propertybase oauth info.', 'ffd-integration' ),
                        'id'    => 'ffd_propertybase_api'
                    ),

                    array(
                        'title'       => __( 'Client ID', 'ffd-integration' ),
                        'id'          => 'ffd_propertybase_client_id',
                        'type'        => 'text',
                        'custom_attributes' => array('autocomplete' =>'off'),
                        'default'     => '',
                        'class'       => '',
                        'css'         => '',
                        'placeholder' => __( 'Client ID', 'ffd-integration' ),
                        'desc_tip'    => __( 'Enter Propertybase Client ID.', 'ffd-integration' ),
                    ),

                    array(
                        'title'       => __( 'Client Secret', 'ffd-integration' ),
                        'id'          => 'ffd_propertybase_client_secret',
                        'type'        => 'text',
                        'custom_attributes' => array('autocomplete' =>'off'),
                        'default'     => '',
                        'class'       => '',
                        'css'         => '',
                        'placeholder' => __( 'Client Secret', 'ffd-integration' ),
                        'desc_tip'    => __( 'Enter Propertybase Client Secret.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Sandbox', 'ffd-integration' ),
                        'desc'     => __( 'Check to enable sandbox mode', 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_sandbox',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Sandbox mode will be used for api authorization.', 'ffd-integration' ),
                    ),

                   


                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_propertybase_api',
                    ),




                )
            );

            $test_api = '';
            if( isset($_POST['ffd_propertybase_test_api'])  ){
                update_option('ffd_propertybase_test_api', 'no');
                $test_api = FFD_Listings_Sync::test_propertybase_query();
            }

            if( isset($_POST['ffd_propertybase_sync_interval'])  ){
                FFD_Listings_Sync::reschedule_main_sync();
            }
            
            $sync_cron_schedules = FFD_Listings_Sync::sync_intervals(array());
            $sync_intervals = array();
            foreach($sync_cron_schedules as $interval => $sync_cron_schedule ) {
                $sync_intervals[$interval] = $sync_cron_schedule['display'];
            }
            $sync_stats = get_option('ffd_sync_stats');
            $stats_text = '';
            $post_type = ffd_get_listing_posttype();
            if( !empty($sync_stats) ){
                $count_posts = wp_count_posts($post_type);
                if ( $count_posts ) {
                    $published_posts = $count_posts->publish;
                }
                $test = print_r($sync_stats, true);
                $test = '<pre>'. $test . '</pre>';
                $synced = (int) $sync_stats['updated_records'] + (int) $sync_stats['new_records'];
                $synced = ' Synced ' . $synced . ' listings. ';
                $stats_text = '<br> Next Run <b>'. date("Y-m-d\Th:i:s",wp_next_scheduled("ffd_main_sync")) . '</b>';
                $stats_text .= '<br> Last Sync on <b>'.$sync_stats['sync_time'] . '</b>';
                $stats_text .= '<br> Overall Listings Synced: ' . $published_posts;
            }
            $sync_settings = apply_filters(
                'ffd_propertybase_settings', array(

                    

                    array(
                        'title' => __( 'Propertybase Sync ' , 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Settings related to sync and fields mapping. <br> ' . ( !empty($instance_url) ? ' ( Connected to: ' .  $instance_url . ' ) ' : "" ), 'ffd-integration' ),
                        'id'    => 'ffd_propertybase_sync',
                    ),

                    array(
                        'title'    => __( 'Status ', 'ffd-integration' ),
                        'desc'     => __( 'Current status of sync' . '<br>' . $stats_text, 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_sync_status',
                        'default'  => 'idle',
                        'type'     => 'plain_text',
                        'desc_tip' => __( 'Current status of listings sync.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Enable Propertybase Sync', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sync From Propertybase to WordPress ', 'ffd-integration' ),
                        'id'       => 'ffd_propertybasetowp_sync',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Listing data will be automatically updated.', 'ffd-integration' ),
                        /* 'tr_css' => ( 'yes' === get_option('ffd_wptopropertybase_sync', 'no')  ? 'display:none;' : '') */
                    ),

                    array(
                        'title'    => __( 'Enable Wordpress Sync', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sync From WordPress to Propertybase ', 'ffd-integration' ),
                        'id'       => 'ffd_wptopropertybase_sync',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Listing data will be automatically updated.', 'ffd-integration' ),
                        /* 'tr_css' => ( !isset($_GET['show_hidden'])  ? 'display:none;' : '') */
                    ),

                    array(
                        'title'    => __( 'Sync Interval', 'ffd-integration' ),
                        'desc'     => __( 'Set interval for Sync', 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_sync_interval',
                        'default'  => 'ffd_hourly',
                        'type'     => 'select',
                        'options'  => $sync_intervals,
                        'desc_tip' => __( '', 'ffd-integration' ),
                        /* 'tr_css' => ( !isset($_GET['show_hidden'])  ? 'display:none;' : '') */
                    ),

                    array(
                        'title'    => __( 'Sandbox', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sandbox Mode', 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_sandbox',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Sandbox will be used for listing sync.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Post Type Slug', 'ffd-integration' ),
                        'desc'     => __( 'Listings post type slug for importing properties', 'ffd-integration' ),
                        'id'       => 'ffd_listing_posttype',
                        'default'  => 'listing',
                        'type'     => 'text',
                        'desc_tip' => __( 'Required for importing properties from pb.', 'ffd-integration' ),
                    ),
                    
                    array(
                        'title'    => __( 'Reset Auth', 'ffd-integration' ),
                        'desc'     => __( 'Reset Auth', 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_reset_auth',
                        'default'  => 'no',
                        'value'    => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( '', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Sync Notification', 'ffd-integration' ),
                        'desc'     => __( 'Check to enable sync not running notification', 'ffd-integration' ),
                        'id'       => 'ffd_sync_notification',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Email will be sent if email is provided below.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Sync Notification Email', 'ffd-integration' ),
                        'desc'     => __( 'Email for sending notification if sync not running', 'ffd-integration' ),
                        'id'       => 'ffd_sync_notification_email',
                        'default'  => '',
                        'type'     => 'text',
                        'desc_tip' => __( 'Required for Sending sync not running notification.', 'ffd-integration' ),
                    ),


                    array(
                        'title'    => __( 'Test API', 'ffd-integration' ),
                        'desc'     => __( 'Test PB to WP API' . $test_api, 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_test_api',
                        'default'  => 'no',
                        'value'    => 'no',
                        'type'     => 'button',
                        'button_type'     => 'submit',
                        'text' => 'Test API',
                        'desc_tip' => __( '', 'ffd-integration' ),
                        'tr_css' => ( 'yes' !== get_option('ffd_propertybasetowp_sync', 'no')  ? 'display:none;' : '') 
                    ),

                    array(
                        'title'    => __( 'Edit Field Keys', 'ffd-integration' ),
                        'desc'     => __( 'Enable editing field keys', 'ffd-integration' ),
                        'id'       => 'ffd_propertybase_edit_keys',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( '', 'ffd-integration' ),
                    ),

                    

                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_propertybase_sync',
                    ),


                    array(
                        'title' => __( 'Special Fields', 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Settings related to specific field types.', 'ffd-integration' ),
                        'id'    => 'ffd_propertybase_special_fields',
                        'tr_css' => ( 'yes' !== get_option('ffd_propertybase_edit_keys', 'no') ? 'display:none;' : '')
                    ),

                    array(
                            'title'       => __( 'Media Field', 'ffd-integration' ),
                            'id'          => 'ffd_propertybase_media_field',
                            'type'        => 'text',
                            'default'     => 'ffd_media',
                            'class'       => '',
                            'css'         => '',
                            'placeholder' => __( 'Media Field', 'ffd-integration' ),
                            'desc'    => __( 'Media field key for saving listing image data.', 'ffd-integration' ),
                            'desc_tip'    => __( 'Media field key for saving listing image data.', 'ffd-integration' ),
                            'tr_css'        => ( 'yes' !== get_option('ffd_propertybase_edit_keys', 'no') ? 'display:none;' : '')
                    ),

                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_propertybase_special_fields',
                    ),

                )
            );

            

            if( !$api_authorized ){
                $settings = $api_settings;
            } else {
               $refresh_field_url = admin_url( 'admin.php?page=ffd-settings&tab=' . $this->id . '&section=' . sanitize_title( $current_section ));
               $propertybase_fields = FFD_PropertyBase_API::propertybase_api_get_api_fields();
               $propertybase_default_fields = FFD_PropertyBase_API::propertybase_api_default_fields();
                
                $propertybase_fields_settings = array();
                $propertybase_fields_settings[] = array(
                                                    'title' => __( 'Fields Settings', 'ffd-integration' ),
                                                    'type'  => 'title',
                                                    'desc'  => __( 'setting related to api fields <a href="'.$refresh_field_url.'&propertybase_refresh_fields=1&_ffd_redirect='.$refresh_field_url.'">Refresh API Fields</a>.', 'ffd-integration' ),
                                                    'id'    => 'ffd_propertybase_fields',
                                                    'table_class' => 'table_propertybase_fields',
                                                    'table_style' => 'position:relative;display:block;height:300px;overflow-y:scroll;background-color:#f8f8f8;padding-left:15px',
                                                    'sub_title_field' => array(
                                                                'label'     => 'Field Name',
                                                                'text'     => 'Enable',
                                                                'row_id'        => 'propertybase_toggle_enabled'
                                                            )
                                                );
                

                $i=0;
               foreach($propertybase_fields as $field_key => $field_title){
                
                $map_field_value = 'ffd_' . strtolower(trim(str_replace(array('pba__', '__c'), '', $field_key), '_'));

                $propertybase_fields_settings[] = array(
                    
                                                    'type'     => 'map_fields',
                                                    'title'    => $field_title,
                                                    'desc'     => '',
                                                    'id'            => 'map_fields[propertybase_field_name]['. $i .']',
                                                    'value'  => $field_key,
                                                    'class'    => 'ffd-enhanced-select',
                                                    /*'options'  => $propertybase_fields,
                                                     'custom_attributes' => array('readonly'=>'', 'disabled'=>'') */

                                                    'map_field_id'  => 'map_fields[propertybase_field_db]['. $i .']',
                                                    'map_field_value'  => $map_field_value,
                                                    'map_field_default' => $map_field_value,

                                                    'map_field_check'  => 'map_fields[propertybase_field_enabled]['. $i .']',
                                                    'map_field_check_default'  => ( isset($propertybase_default_fields[strtolower($field_key)]) ? 1 : 0 ),
                                                    
                                                );
                $i++;
               }
               $propertybase_fields_settings[] =array(
                                                'type' => 'sectionend',
                                                'id'   => 'ffd_propertybase_fields',
                                            );

                
                $sync_conditions = apply_filters(
                    'ffd_propertybase_condition', array(
    
                        array(
                            'title' => __( 'Propertybase Sync Conditions', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_propertybase_sync_conditions',
                        ),
    
                        array(
                            'title'    => __( 'Where Clause', 'ffd-integration' ),
                            'desc'     => __( 'Where clause for listing import i.e [AND] [condition1] [AND [OR]]  [condition2]', 'ffd-integration' ),
                            'id'       => 'ffd_sync_where_condition',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Delete Clause', 'ffd-integration' ),
                            'desc'     => __( 'Delete clause for listing delete i.e [AND] [condition1] [AND [OR]]  [condition2]', 'ffd-integration' ),
                            'id'       => 'ffd_sync_prune_listings_condition',
                            'default'  => '',
                            'type'     => 'text'
                        ),
    
                        
    
                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_propertybase_sync_conditions',
                        ),
    
                    )
                );


                $api_webtoprospect = apply_filters(
                    'ffd_webtoprospect_api', array(
    
                        array(
                            'title' => __( 'Propertybase WebToProspect API', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_propertybase_api_webtoprospect',
                        ),
    
                        array(
                            'title'    => __( 'Domain Name', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_site',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Base URL', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_base_url',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Token', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_token',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Form Fields Mapping ', 'ffd-integration' ),
                            'desc'     => __( ' one per line using format <b>formtype:pbfield:form_field_name</b> <br> pb fields are: email,first_name,last_name,name,phone,interest,search-frequency,message,property(sf id),optout(1 or 0) <br>  formtype are: cf7,ninja,gravity <br> ' ),
                            'id'       => 'ffd_form_fields_mappings',
                            'default'  => '',
                            'type'     => 'textarea',
                            'custom_attributes' => array('rows'=>10)
                        ),
    
                        
    
                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_propertybase_api_webtoprospect',
                        ),
    
                    )
                );


                $api_googlemaps = apply_filters(
                    'ffd_googlemaps_settings', array(
    
                        array(
                            'title' => __( 'Google API', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_propertybase_googlemaps',
                        ),
    
                        array(
                            'title'    => __( 'Google Map Key', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_gmap_api_key',
                            'default'  => '',
                            'type'     => 'text'
                        ),
    
                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_propertybase_googlemaps',
                        ),
    
                    )
                );
                
                $sync_settings = array_merge($sync_settings, $propertybase_fields_settings, $sync_conditions, $api_webtoprospect, $api_googlemaps);
               $settings = $sync_settings;
            }
        //Add option Trestle
        }else if ( 'trestle' === $current_section ) {
            $instance_url = get_option('ffd_trestle_instance_url','https://api-prod.corelogic.com/trestle/odata');

            $api_authorized = FFD_Fields_Mapping_Trestle::is_platform_authorized(); //Falta implementar

            //var_dump('api_authorized',$api_authorized);exit;

            /*Nuevo para trestle*/
            $api_settings = apply_filters(
                'ffd_trestle_settings', array(

                    array(
                        'title' => __( 'API Settings', 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Add Trestle oauth info.', 'ffd-integration' ),
                        'id'    => 'ffd_trestle_api'
                    ),

                    array(
                        'title'       => __( 'Client ID', 'ffd-integration' ),
                        'id'          => 'ffd_trestle_client_id',
                        'type'        => 'text',
                        'custom_attributes' => array('autocomplete' =>'off'),
                        'default'     => '',
                        'class'       => '',
                        'css'         => '',
                        'placeholder' => __( 'Client ID', 'ffd-integration' ),
                        'desc_tip'    => __( 'Enter Trestle Client ID.', 'ffd-integration' ),
                    ),

                    array(
                        'title'       => __( 'Client Secret', 'ffd-integration' ),
                        'id'          => 'ffd_trestle_client_secret',
                        'type'        => 'text',
                        'custom_attributes' => array('autocomplete' =>'off'),
                        'default'     => '',
                        'class'       => '',
                        'css'         => '',
                        'placeholder' => __( 'Client Secret', 'ffd-integration' ),
                        'desc_tip'    => __( 'Enter Trestle Client Secret.', 'ffd-integration' ),
                    ),
                    /* Eliminar al final
                    array(
                        'title'    => __( 'Sandbox', 'ffd-integration' ),
                        'desc'     => __( 'Check to enable sandbox mode', 'ffd-integration' ),
                        'id'       => 'ffd_trestle_sandbox',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Sandbox mode will be used for api authorization.', 'ffd-integration' ),
                    ),
                    */



                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_trestle_api',
                    ),

                )
            );

            /*FIN Nuevo*/

            /*franklin no es necesario testear*/
            /*$test_api = '';
            if( isset($_POST['ffd_trestle_test_api'])  ){
                update_option('ffd_trestle_test_api', 'no');
                $test_api = FFD_Listings_Trestle_Sync::test_trestle_query();// create in class-ffd-listings-sync.php
            }*/
            //var_dump('ffd_trestle_sync_interval', isset($_POST['ffd_trestle_sync_interval']));exit;
            if( isset($_POST['ffd_trestle_sync_interval'])  ){
                FFD_Listings_Trestle_Sync::reschedule_main_sync();
            }

            $sync_cron_schedules = FFD_Listings_Trestle_Sync::sync_intervals(array());
            $sync_intervals = array();
            foreach($sync_cron_schedules as $interval => $sync_cron_schedule ) {
                $sync_intervals[$interval] = $sync_cron_schedule['display'];
            }

            /**/
            $sync_stats = get_option('ffd_sync_stats');
            $stats_text = '';
            $post_type = ffd_get_listing_posttype();

            if( !empty($sync_stats) ){
                $count_posts = wp_count_posts($post_type);
                if ( $count_posts ) {
                    $published_posts = $count_posts->publish;
                }
                $test = print_r($sync_stats, true);
                $test = '<pre>'. $test . '</pre>';
                $synced = (int) $sync_stats['updated_records'] + (int) $sync_stats['new_records'];
                $synced = ' Synced ' . $synced . ' listings. ';
                $stats_text = '<br> Next Run <b>'. date("Y-m-d\Th:i:s",wp_next_scheduled("ffd_main_sync")) . '</b>';
                $stats_text .= '<br> Last Sync on <b>'.$sync_stats['sync_time'] . '</b>';
                $stats_text .= '<br> Overall Listings Synced: ' . $published_posts;
            }
            /**/
            $sync_settings = apply_filters(
                'ffd_trestles_settings', array(

                    array(
                        'title' => __( 'Trestle Sync ' , 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Settings related to sync and fields mapping. <br> ' . ( !empty($instance_url) ? ' ( Connected to: ' .  $instance_url . ' ) ' : "" ), 'ffd-integration' ),
                        'id'    => 'ffd_trestle_sync',
                    ),

                    array(
                        'title'    => __( 'Status ', 'ffd-integration' ),
                        'desc'     => __( 'Current status of sync' . '<br>' . $stats_text, 'ffd-integration' ),
                        'id'       => 'ffd_trestle_sync_status',
                        'default'  => 'idle',
                        'type'     => 'plain_text',
                        'desc_tip' => __( 'Current status of listings sync.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Enable Trestle Sync', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sync From Trestle to WordPress ', 'ffd-integration' ),
                        'id'       => 'ffd_trestletowp_sync',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Listing data will be automatically updated.', 'ffd-integration' ),
                        /* 'tr_css' => ( 'yes' === get_option('ffd_wptotrestle_sync', 'no')  ? 'display:none;' : '') */
                    ),
                    /* Eliminar al final
                    array(
                        'title'    => __( 'Enable Wordpress Sync', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sync From WordPress to Trestle ', 'ffd-integration' ),
                        'id'       => 'ffd_wptotrestle_sync',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Listing data will be automatically updated.', 'ffd-integration' ),
                        'tr_css' => ( !isset($_GET['show_hidden'])  ? 'display:none;' : '')
                    ),
                    */

                    array(
                        'title'    => __( 'Sync Interval', 'ffd-integration' ),
                        'desc'     => __( 'Set interval for Sync', 'ffd-integration' ),
                        'id'       => 'ffd_trestle_sync_interval',
                        'default'  => 'ffd_hourly',
                        'type'     => 'select',
                        'options'  => $sync_intervals,
                        'desc_tip' => __( '', 'ffd-integration' ),
                        /* 'tr_css' => ( !isset($_GET['show_hidden'])  ? 'display:none;' : '') */
                    ),
                    /* Eliminar al final
                    array(
                        'title'    => __( 'Sandbox', 'ffd-integration' ),
                        'desc'     => __( 'Enable Sandbox Mode', 'ffd-integration' ),
                        'id'       => 'ffd_trestle_sandbox',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Sandbox will be used for listing sync.', 'ffd-integration' ),
                    ),
                    */

                    array(
                        'title'    => __( 'Post Type Slug', 'ffd-integration' ),
                        'desc'     => __( 'Listings post type slug for importing properties', 'ffd-integration' ),
                        'id'       => 'ffd_listing_posttype',
                        'default'  => 'listing',
                        'type'     => 'text',
                        'desc_tip' => __( 'Required for importing properties from pb.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Reset Auth', 'ffd-integration' ),
                        'desc'     => __( 'Reset Auth', 'ffd-integration' ),
                        'id'       => 'ffd_trestle_reset_auth',
                        'default'  => 'no',
                        'value'    => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( '', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Sync Notification', 'ffd-integration' ),
                        'desc'     => __( 'Check to enable sync not running notification', 'ffd-integration' ),
                        'id'       => 'ffd_sync_notification',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( 'Email will be sent if email is provided below.', 'ffd-integration' ),
                    ),

                    array(
                        'title'    => __( 'Sync Notification Email', 'ffd-integration' ),
                        'desc'     => __( 'Email for sending notification if sync not running', 'ffd-integration' ),
                        'id'       => 'ffd_sync_notification_email',
                        'default'  => '',
                        'type'     => 'text',
                        'desc_tip' => __( 'Required for Sending sync not running notification.', 'ffd-integration' ),
                    ),

                    /* Eliminar al final
                    array(
                        'title'    => __( 'Test API', 'ffd-integration' ),
                        'desc'     => __( 'Test PB to WP API' . $test_api, 'ffd-integration' ),
                        'id'       => 'ffd_trestle_test_api',
                        'default'  => 'no',
                        'value'    => 'no',
                        'type'     => 'button',
                        'button_type'     => 'submit',
                        'text' => 'Test API',
                        'desc_tip' => __( '', 'ffd-integration' ),
                        'tr_css' => ( 'yes' !== get_option('ffd_trestletowp_sync', 'no')  ? 'display:none;' : '')
                    ),
                    */

                    array(
                        'title'    => __( 'Edit Field Keys', 'ffd-integration' ),
                        'desc'     => __( 'Enable editing field keys', 'ffd-integration' ),
                        'id'       => 'ffd_trestle_edit_keys',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => __( '', 'ffd-integration' ),
                    ),



                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_trestle_sync',
                    ),


                    array(
                        'title' => __( 'Special Fields', 'ffd-integration' ),
                        'type'  => 'title',
                        'desc'  => __( 'Settings related to specific field types.', 'ffd-integration' ),
                        'id'    => 'ffd_trestle_special_fields',
                        'tr_css' => ( 'yes' !== get_option('ffd_trestle_edit_keys', 'no') ? 'display:none;' : '')
                    ),

                    array(
                        'title'       => __( 'Media Field', 'ffd-integration' ),
                        'id'          => 'ffd_trestle_media_field',
                        'type'        => 'text',
                        'default'     => 'ffd_media',
                        'class'       => '',
                        'css'         => '',
                        'placeholder' => __( 'Media Field', 'ffd-integration' ),
                        'desc'    => __( 'Media field key for saving listing image data.', 'ffd-integration' ),
                        'desc_tip'    => __( 'Media field key for saving listing image data.', 'ffd-integration' ),
                        'tr_css'        => ( 'yes' !== get_option('ffd_trestle_edit_keys', 'no') ? 'display:none;' : '')
                    ),

                    array(
                        'type' => 'sectionend',
                        'id'   => 'ffd_trestle_special_fields',
                    ),


                )
            );

            /*FALTA AUMENTAR ....................................................................*/            /*FALTA AUMENTAR*/
            if( !$api_authorized ){
                $settings = $api_settings;
            } else {
                $refresh_field_url = admin_url( 'admin.php?page=ffd-settings&tab=' . $this->id . '&section=' . sanitize_title( $current_section ));
                $trestle_fields = FFD_Trestle_API::trestle_api_get_api_fields();
                $trestle_default_fields = FFD_Trestle_API::trestle_api_default_fields();


                $trestle_fields_settings = array();
                $trestle_fields_settings[] = array(
                    'title' => __( 'Fields Settings', 'ffd-integration' ),
                    'type'  => 'title',
                    'desc'  => __( 'setting related to api fields <a href="'.$refresh_field_url.'&trestle_refresh_fields=1&_ffd_redirect='.$refresh_field_url.'">Refresh API Fields</a>.', 'ffd-integration' ),
                    'id'    => 'ffd_trestle_fields',
                    'table_class' => 'table_trestle_fields',
                    'table_style' => 'position:relative;display:block;height:300px;overflow-y:scroll;background-color:#f8f8f8;padding-left:15px',
                    'sub_title_field' => array(
                        'label'     => 'Field Name',
                        'text'     => 'Enable',
                        'row_id'        => 'trestle_toggle_enabled'
                    )
                );


                $i=0;
                foreach($trestle_fields as $field_key => $field_title){

                    $map_field_value = 'ffd_' . strtolower(trim(str_replace(array('pba__', '__c'), '', $field_key), '_'));
//var_dump($map_field_value, $field_key, $field_title);exit;
                    $trestle_fields_settings[] = array(

                        'type'     => 'map_fields',
                        'title'    => $field_title,
                        'desc'     => '',
                        'id'            => 'map_fields[trestle_field_name]['. $i .']',
                        'value'  => $field_key,
                        'class'    => 'ffd-enhanced-select',
                        /*'options'  => $trestle_fields,
                         'custom_attributes' => array('readonly'=>'', 'disabled'=>'') */

                        'map_field_id'  => 'map_fields[trestle_field_db]['. $i .']',
                        'map_field_value'  => $map_field_value,
                        'map_field_default' => $map_field_value,

                        'map_field_check'  => 'map_fields[trestle_field_enabled]['. $i .']',
                        'map_field_check_default'  => ( isset($trestle_default_fields[strtolower($field_key)]) ? 1 : 0 ),

                    );
                    $i++;
                }

                $trestle_fields_settings[] =array(
                    'type' => 'sectionend',
                    'id'   => 'ffd_trestle_fields',
                );


                $sync_conditions = apply_filters(
                    'ffd_trestle_condition', array(

                        array(
                            'title' => __( 'Trestle Sync Conditions', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_trestle_sync_conditions',
                        ),

                        array(
                            'title'    => __( 'Select', 'ffd-integration' ),
                            'desc'     => __( 'Select clause for listing', 'ffd-integration' ),
                            'id'       => 'trestle_select',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Filter', 'ffd-integration' ),
                            'desc'     => __( 'Filter clause for listing', 'ffd-integration' ),
                            'id'       => 'trestle_filter',
                            'default'  => '',
                            'type'     => 'textarea'
                        ),

                        array(
                            'title'    => __( 'Expand', 'ffd-integration' ),
                            'desc'     => __( 'Expand clause for listing', 'ffd-integration' ),
                            'id'       => 'trestle_expand',
                            'default'  => '',
                            'type'     => 'textarea'
                        ),

                        array(
                            'title'    => __( 'Top', 'ffd-integration' ),
                            'desc'     => __( 'Top clause for listing', 'ffd-integration' ),
                            'id'       => 'trestle_top',
                            'default'  => '',
                            'type'     => 'text'
                        ),
                        /* Eliminar al final
                        array(
                            'title'    => __( 'Delete Clause', 'ffd-integration' ),
                            'desc'     => __( 'Delete clause for listing delete i.e [AND] [condition1] [AND [OR]]  [condition2]', 'ffd-integration' ),
                            'id'       => 'ffd_sync_prune_listings_condition',
                            'default'  => '',
                            'type'     => 'text'
                        ),
                        */


                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_trestle_sync_conditions',
                        ),

                    )
                );

                $api_webtoprospect = apply_filters(
                    'ffd_webtoprospect_api', array(

                        array(
                            'title' => __( 'Trestle WebToProspect API', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_trestle_api_webtoprospect',
                        ),

                        array(
                            'title'    => __( 'Domain Name', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_site',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        /*array(
                            'title'    => __( 'Base URL', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_base_url',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'title'    => __( 'Token', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_webtoprospect_token',
                            'default'  => '',
                            'type'     => 'textarea'
                        ),*/

                        array(
                            'title'    => __( 'Form Fields Mapping ', 'ffd-integration' ),
                            'desc'     => __( ' one per line using format <b>formtype:pbfield:form_field_name</b> <br> pb fields are: email,first_name,last_name,name,phone,interest,search-frequency,message,property(sf id),optout(1 or 0) <br>  formtype are: cf7,ninja,gravity <br> ' ),
                            'id'       => 'ffd_form_fields_mappings',
                            'default'  => '',
                            'type'     => 'textarea',
                            'custom_attributes' => array('rows'=>10)
                        ),



                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_trestle_api_webtoprospect',
                        ),

                    )
                );

                $api_googlemaps = apply_filters(
                    'ffd_googlemaps_settings', array(

                        array(
                            'title' => __( 'Google API', 'ffd-integration' ),
                            'type'  => 'title',
                            'desc'  => '',
                            'id'    => 'ffd_trestle_googlemaps',
                        ),

                        array(
                            'title'    => __( 'Google Map Key', 'ffd-integration' ),
                            'desc'     => __( '' ),
                            'id'       => 'ffd_gmap_api_key',
                            'default'  => '',
                            'type'     => 'text'
                        ),

                        array(
                            'type' => 'sectionend',
                            'id'   => 'ffd_trestle_googlemaps',
                        ),

                    )
                );

                $sync_settings = array_merge($sync_settings, $trestle_fields_settings, $sync_conditions, $api_webtoprospect, $api_googlemaps);
                //}



                /*HASTA ACA................................................................................*/

                $settings = $sync_settings;
            }

        }


            return apply_filters( 'ffd_get_settings_' . $this->id, $settings, $current_section );

    }

}

return new FFD_Settings_Platforms();