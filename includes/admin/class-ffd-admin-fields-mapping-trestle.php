<?php
/**
 * FFD Integration Admin
 *
 * @class    FFD_Fields_Mapping
 * @author   FrozenFish
 * @category Admin
 * @package  FFD Integration/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * FFD_Fields_Mapping class.
 */
class FFD_Fields_Mapping_Trestle extends FFD_Trestle_API {

    protected static $db_map_type='post_meta';



    /**
     * Constructor.
     */
    public static function init(){

        self::$fields = array();


        //do things when platform settings updated
        add_action('ffd_load_options_platforms_trestle', array('FFD_Fields_Mapping', 'trestle_db_settings_load'));
        add_action('ffd_update_options_platforms_trestle', array('FFD_Fields_Mapping', 'trestle_db_settings_updated'));
        add_action('ffd_trestle_api_settings_submitted', array('FFD_Fields_Mapping', 'trestle_api_settings_submitted'));



    }


    public static function trestle_db_settings_load(){

        self::trestle_set_api_vars();

        //save trestle token
        if( isset($_REQUEST['state']) && 'trestle_updatetoken' == $_REQUEST['state'] && isset($_REQUEST['code'])){
            self::trestle_api_get_token();
        }

    }

    public static function trestle_db_settings_updated(){

        self::trestle_set_api_vars();


    }


    public static function trestle_api_settings_submitted(){

        self::trestle_set_api_vars();


        $authorized = self::is_platform_authorized();
        $api_set = self::trestle_api_is_var_set(array('client_id', 'client_secret'));


        if( !$authorized && $api_set){
            self::trestle_api_get_response_code();
        }

    }


    /*
    *@var check  array | string
     */
    protected static function trestle_api_is_var_set($check=''){

        if(!empty($check) ){

            if( is_string($check) ){
                $check = array($check);
            }

            if( is_array($check)){
                foreach($check as $var ){

                    if( empty(self::$api_settings[$var]) ) return false;
                }
                return true;
            }
        }


        return false;

    }






}

FFD_Fields_Mapping_Trestle::init();